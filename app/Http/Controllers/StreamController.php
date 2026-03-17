<?php

namespace App\Http\Controllers;

use App\Jobs\SummarizeChatJob;
use App\Models\Chat;
use App\Models\ChatAttachment;
use App\Models\Message;
use App\Models\Project;
use App\Models\UsageLog;
use App\Services\MemoryService;
use App\Services\QuotaService;
use App\Services\TokenCounterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamController extends Controller
{
    public function stream(
        Request             $request,
        Project             $project,
        Chat                $chat,
        MemoryService       $memory,
        QuotaService        $quota,
        TokenCounterService $counter,
    ): StreamedResponse {

        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->tenant_id    !== $tenant->id, 403);
        abort_if($chat->project_id   !== $project->id, 404);

        $messages = $memory->buildContext($chat, $project);
        $messages = $this->injectAttachment($chat, $messages, $project->model ?? config('ollama.model'));

        $ollamaUrl    = rtrim(config('ollama.url'), '/');
        $projectModel = $project->model ?? config('ollama.model');
        $chatId       = $chat->id;
        $tenantId     = $tenant->id;
        $userId       = $request->user()->id;

        return response()->stream(function () use (
            $chatId, $tenantId, $userId, $projectModel,
            $messages, $ollamaUrl,
            $quota, $counter, $tenant, $chat
        ) {
            while (ob_get_level() > 0) ob_end_clean();
            @ini_set('zlib.output_compression', 'Off');
            @ini_set('output_buffering', 'Off');
            set_time_limit(0);

            $fullContent      = '';
            $promptTokens     = 0;
            $completionTokens = 0;
            $hasError         = false;

            echo ": keepalive\n\n";
            flush();

            // ── Build cURL request ─────────────────────────────────────────
            $ch = curl_init("{$ollamaUrl}/api/chat");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'model'    => $projectModel,
                'messages' => $messages,
                'stream'   => true,
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

            curl_setopt($ch, CURLOPT_POST,           true);
            curl_setopt($ch, CURLOPT_TIMEOUT,        120);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);

            curl_setopt($ch, CURLOPT_WRITEFUNCTION,
                function ($ch, $data) use (&$fullContent, &$promptTokens, &$completionTokens) {
                    foreach (explode("\n", $data) as $line) {
                        $line = trim($line);
                        if ($line === '') continue;

                        $json   = json_decode($line, true);
                        if (!$json) continue;

                        $isDone = $json['done'] ?? false;

                        if (!$isDone && isset($json['message']['content'])) {
                            $token        = $json['message']['content'];
                            $fullContent .= $token;
                            echo 'data: ' . json_encode(['token' => $token]) . "\n\n";
                            flush();
                        }

                        if ($isDone) {
                            $promptTokens     = $json['prompt_eval_count'] ?? 0;
                            $completionTokens = $json['eval_count']        ?? 0;
                        }
                    }
                    return strlen($data);
                }
            );

            curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError || empty(trim($fullContent))) {
                $hasError    = true;
                $fullContent = 'Sorry, I could not connect to the AI engine. Please try again.';
                echo 'data: ' . json_encode(['token' => $fullContent]) . "\n\n";
                flush();
            }

            $assistantMsg = Message::create([
                'chat_id'   => $chatId,
                'tenant_id' => $tenantId,
                'role'      => 'assistant',
                'content'   => $fullContent,
                'tokens'    => $completionTokens,
                'model'     => $projectModel,
            ]);

            try {
                (new \App\Services\N8nService())->fire($tenant, 'assistant_replied', [
                    'chat_id'    => $chatId,
                    'chat_title' => $chat->title,
                    'project'    => $chat->project->name ?? '',
                    'content'    => mb_substr($fullContent, 0, 500),
                    'tokens'     => $completionTokens,
                    'model'      => $projectModel,
                ]);
            } catch (\Throwable) {}

            if (!$hasError) {
                $totalTokens = $promptTokens + $completionTokens;
                if ($totalTokens === 0) {
                    $totalTokens = $counter->estimateMessages($messages)
                                 + $counter->estimate($fullContent);
                }

                $chat->increment('total_tokens', $totalTokens);
                $quota->deduct($tenant, $totalTokens);

                UsageLog::create([
                    'tenant_id'         => $tenantId,
                    'user_id'           => $userId,
                    'chat_id'           => $chatId,
                    'model'             => $projectModel,
                    'prompt_tokens'     => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'total_tokens'      => $totalTokens,
                    'cost'              => 0.000000,
                    'created_at'        => now(),
                ]);

                $tenant->refresh();
                if ($quota->isExceeded($tenant)) {
                    $chat->update(['status' => 'closed', 'closed_reason' => 'Token quota exceeded.']);
                }

                $msgCount = Message::where('chat_id', $chatId)->count();
                if ($msgCount >= 20 && $msgCount % 20 === 0) {
                    SummarizeChatJob::dispatch($chatId);
                }
            }

            $freshChat = $chat->fresh();

            echo 'data: ' . json_encode([
                'done'         => true,
                'message_id'   => $assistantMsg->id,
                'chat_status'  => $freshChat->status,
                'total_tokens' => $freshChat->total_tokens,
                'chat_title'   => $freshChat->title,
            ]) . "\n\n";

            flush();

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    private function injectAttachment(Chat $chat, array $messages, string $model): array
    {
        $lastMsgWithAttachment = Message::where('chat_id', $chat->id)
            ->where('role', 'user')
            ->where('has_attachment', true)
            ->with('attachment')
            ->latest()
            ->first();

        if (!$lastMsgWithAttachment || !$lastMsgWithAttachment->attachment) {
            return $messages;
        }

        $attachment    = $lastMsgWithAttachment->attachment;
        $lastUserIndex = null;

        foreach ($messages as $i => $msg) {
            if ($msg['role'] === 'user') $lastUserIndex = $i;
        }

        if ($lastUserIndex === null) return $messages;

        $userContent = $messages[$lastUserIndex]['content'] ?? '';

        if ($attachment->isImage()) {
            if (str_contains(strtolower($model), 'llava') || str_contains(strtolower($model), 'vision')) {
                try {
                    $imageBytes = Storage::disk('public')->get($attachment->path);
                    $messages[$lastUserIndex]['images'] = [base64_encode($imageBytes)];
                } catch (\Throwable) {
                    $messages[$lastUserIndex]['content'] =
                        "[Image attached: {$attachment->original_name}. Describe or analyze it if you can.]\n\n" . $userContent;
                }
            } else {
                $messages[$lastUserIndex]['content'] =
                    "[Image attached: {$attachment->original_name} ({$attachment->extension}, "
                    . ($attachment->meta['width'] ?? '?') . 'x' . ($attachment->meta['height'] ?? '?')
                    . "px). I cannot view images directly, but please help me with: ]\n\n" . $userContent;
            }
            return $messages;
        }

        $extractedText = $attachment->extracted_text ?? '[Could not extract file content]';
        $typeLabel     = match ($attachment->type) {
            'pdf'   => 'PDF Document',
            'excel' => 'Excel Spreadsheet',
            default => 'Text File',
        };
        $metaNote = '';
        if ($attachment->type === 'pdf' && isset($attachment->meta['page_count'])) {
            $metaNote = " ({$attachment->meta['page_count']} pages)";
        } elseif ($attachment->type === 'excel' && isset($attachment->meta['sheet_count'])) {
            $sheetNames = implode(', ', $attachment->meta['sheet_names'] ?? []);
            $metaNote   = " ({$attachment->meta['sheet_count']} sheet(s): {$sheetNames})";
        }

        $fileContext = "=== Attached {$typeLabel}: {$attachment->original_name}{$metaNote} ===\n"
                    . $extractedText . "\n=== End of Attached File ===";

        array_splice($messages, $lastUserIndex, 0, [['role' => 'system', 'content' => $fileContext]]);

        return $messages;
    }
}