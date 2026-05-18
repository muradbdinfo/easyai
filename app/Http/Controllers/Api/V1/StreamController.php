<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
    public function stream(Request $request, Project $project, Chat $chat, MemoryService $memory, QuotaService $quota, TokenCounterService $counter): StreamedResponse
    {
        $tenant = app('tenant');
        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->tenant_id    !== $tenant->id, 403);
        abort_if($chat->project_id   !== $project->id, 404);

        $lastUserMsg  = Message::where('chat_id', $chat->id)->where('role', 'user')->latest()->value('content') ?? '';
        $messages     = $memory->buildContext($chat, $project, $lastUserMsg);
        $ollamaUrl    = rtrim(config('ollama.url'), '/');
        $projectModel = $project->model ?? config('ollama.model');
        $chatId       = $chat->id;
        $tenantId     = $tenant->id;
        $userId       = $request->user()->id;

        return response()->stream(function () use (
            $messages, $ollamaUrl, $projectModel,
            $chatId, $tenantId, $userId,
            $quota, $counter, $tenant, $chat
        ) {
            // Close all output buffers safely
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            // Disable buffering at PHP level
            ini_set('output_buffering', 'off');
            ini_set('zlib.output_compression', false);

            $payload      = json_encode(['model' => $projectModel, 'messages' => $messages, 'stream' => true]);
            $fullContent  = '';
            $promptTokens = 0;
            $outputTokens = 0;

            $ch = curl_init("$ollamaUrl/api/chat");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300);

            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$fullContent, &$promptTokens, &$outputTokens) {
                $json = json_decode($data, true);

                if (isset($json['message']['content'])) {
                    $chunk = $json['message']['content'];
                    $fullContent .= $chunk;
                    echo "data: " . json_encode(['token' => $chunk]) . "\n\n";
                    flush(); // Only flush(), no ob_flush()
                }

                if (!empty($json['done'])) {
                    $promptTokens = $json['prompt_eval_count'] ?? 0;
                    $outputTokens = $json['eval_count']        ?? 0;
                }

                return strlen($data);
            });

            curl_exec($ch);
            curl_close($ch);

            if ($fullContent) {
                $totalTokens = $promptTokens + $outputTokens;
                if ($totalTokens === 0) {
                    $totalTokens = $counter->estimate($fullContent);
                }

                Message::create([
                    'chat_id'   => $chatId,
                    'tenant_id' => $tenantId,
                    'role'      => 'assistant',
                    'content'   => $fullContent,
                    'model'     => $projectModel,
                    'tokens'    => $totalTokens,
                ]);

                $chat->increment('total_tokens', $totalTokens);

                UsageLog::create([
                    'tenant_id'         => $tenantId,
                    'user_id'           => $userId,
                    'chat_id'           => $chatId,
                    'model'             => $projectModel,
                    'prompt_tokens'     => $promptTokens,
                    'completion_tokens' => $outputTokens,
                    'total_tokens'      => $totalTokens,
                    'cost'              => 0,
                ]);

                $quota->deduct($tenant, $totalTokens);
                $tenant->refresh();

                if (!$chat->title || $chat->title === 'New Chat') {
                    $firstMsg = Message::where('chat_id', $chatId)
                        ->where('role', 'user')
                        ->oldest()
                        ->value('content') ?? '';
                    if ($firstMsg) {
                        $chat->update(['title' => \Illuminate\Support\Str::limit($firstMsg, 50)]);
                    }
                }
            }

            // Signal done to client
            echo "data: " . json_encode(['done' => true]) . "\n\n";
            flush();

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }
}
