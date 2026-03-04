<?php

namespace App\Http\Controllers;

use App\Jobs\SummarizeChatJob;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Project;
use App\Models\UsageLog;
use App\Services\MemoryService;
use App\Services\QuotaService;
use App\Services\TokenCounterService;
use Illuminate\Http\Request;
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

        // Build context before the closure
        $messages     = $memory->buildContext($chat, $project);
        $ollamaUrl    = rtrim(config('ollama.url'), '/');
        $projectModel = $project->model ?? config('ollama.model');
        $chatId       = $chat->id;
        $tenantId     = $tenant->id;
        $userId       = $request->user()->id;

        return response()->stream(function () use (
            $chatId, $tenantId, $userId, $projectModel,
            $messages, $ollamaUrl, $quota, $counter,
            $tenant, $chat
        ) {
            // Disable all output buffering (critical for XAMPP/Windows)
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            @ini_set('zlib.output_compression', 'Off');
            @ini_set('output_buffering', 'Off');
            set_time_limit(0);

            $fullContent      = '';
            $promptTokens     = 0;
            $completionTokens = 0;
            $hasError         = false;

            // Keepalive so browser does not time out before first token
            echo ": keepalive\n\n";
            flush();

            // Stream from Ollama via cURL
            $ch = curl_init("{$ollamaUrl}/api/chat");
            curl_setopt($ch, CURLOPT_POST,           true);
            curl_setopt($ch, CURLOPT_POSTFIELDS,     json_encode([
                'model'    => $projectModel,
                'messages' => $messages,
                'stream'   => true,
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER,     ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT,        120);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION,  function ($ch, $data) use (&$fullContent, &$promptTokens, &$completionTokens) {
                foreach (explode("\n", $data) as $line) {
                    $line = trim($line);
                    if ($line === '') continue;

                    $json = json_decode($line, true);
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
            });

            curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Fallback if Ollama failed or empty response
            if ($curlError || empty(trim($fullContent))) {
                $hasError    = true;
                $fullContent = 'Sorry, I could not connect to the AI engine. Please try again.';
                echo 'data: ' . json_encode(['token' => $fullContent]) . "\n\n";
                flush();
            }

            // Save assistant message to DB
            $assistantMsg = Message::create([
                'chat_id'   => $chatId,
                'tenant_id' => $tenantId,
                'role'      => 'assistant',
                'content'   => $fullContent,
                'tokens'    => $completionTokens,
                'model'     => $projectModel,
            ]);

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

                // Close chat if quota exceeded
                $tenant->refresh();
                if ($quota->isExceeded($tenant)) {
                    $chat->update([
                        'status'        => 'closed',
                        'closed_reason' => 'Token quota exceeded.',
                    ]);
                }

                // Summarize every 20 messages
                $msgCount = Message::where('chat_id', $chatId)->count();
                if ($msgCount >= 20 && $msgCount % 20 === 0) {
                    SummarizeChatJob::dispatch($chatId);
                }
            }

            // Final done event
            $freshChat = $chat->fresh();
            echo 'data: ' . json_encode([
                'done'         => true,
                'message_id'   => $assistantMsg->id,
                'chat_status'  => $freshChat->status,
                'total_tokens' => $freshChat->total_tokens,
            ]) . "\n\n";
            flush();

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }
}