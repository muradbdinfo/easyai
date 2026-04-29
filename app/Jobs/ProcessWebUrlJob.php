<?php

namespace App\Jobs;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Services\ChunkingService;
use App\Services\WebCrawlerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWebUrlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 2;

    public function __construct(
        public int  $documentId,
        public int  $maxPages = 1,
    ) {}

    public function handle(WebCrawlerService $crawler, ChunkingService $chunker): void
    {
        $doc = KnowledgeDocument::find($this->documentId);
        if (!$doc) return;

        $doc->update(['status' => 'processing']);

        try {
            // Single page or multi-page crawl
            if ($this->maxPages > 1) {
                $result = $crawler->crawlMultiple($doc->source_url, $this->maxPages);
            } else {
                $result = $crawler->crawl($doc->source_url);
            }

            $text = $result['text'];

            // Update title if it was auto-generated
            if ($doc->title === parse_url($doc->source_url, PHP_URL_HOST)) {
                $doc->update(['title' => $result['title']]);
            }

            // Update file_size with crawled content size
            $doc->update(['file_size' => $result['size']]);

            $chunks = $chunker->chunk($text);

            if (empty($chunks)) {
                $doc->update(['status' => 'failed', 'error_message' => 'No meaningful text extracted from URL.']);
                return;
            }

            // Delete old chunks
            KnowledgeChunk::where('document_id', $doc->id)->delete();

            // Insert in batches
            $inserts = [];
            foreach ($chunks as $i => $content) {
                $inserts[] = [
                    'document_id'       => $doc->id,
                    'knowledge_base_id' => $doc->knowledge_base_id,
                    'tenant_id'         => $doc->tenant_id,
                    'content'           => $content,
                    'chunk_index'       => $i,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];
            }

            foreach (array_chunk($inserts, 100) as $batch) {
                KnowledgeChunk::insert($batch);
            }

            $doc->update([
                'status'      => 'ready',
                'chunk_count' => count($chunks),
            ]);

        } catch (\Throwable $e) {
            $doc->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
