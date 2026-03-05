<?php

namespace App\Jobs;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Services\ChunkingService;
use App\Services\DocumentParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(public int $documentId) {}

    public function handle(DocumentParserService $parser, ChunkingService $chunker): void
    {
        $doc = KnowledgeDocument::find($this->documentId);
        if (!$doc) return;

        $doc->update(['status' => 'processing']);

        try {
            $text   = $parser->parse($doc->file_path, $doc->file_type);
            $chunks = $chunker->chunk($text);

            if (empty($chunks)) {
                $doc->update(['status' => 'failed', 'error_message' => 'No text extracted.']);
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

            $doc->update(['status' => 'ready', 'chunk_count' => count($chunks)]);
        } catch (\Throwable $e) {
            $doc->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }
}