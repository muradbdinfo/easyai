<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Project;

class MemoryService
{
    public function __construct(private RagService $rag) {}

    public function buildContext(Chat $chat, Project $project, string $userQuery = ''): array
    {
        $messages = [];

        // 1. Project system prompt
        if ($project->system_prompt) {
            $messages[] = ['role' => 'system', 'content' => $project->system_prompt];
        }

        // 2. Context summary
        if ($project->context_summary) {
            $messages[] = ['role' => 'system', 'content' => "Previous context:\n" . $project->context_summary];
        }

        // 3. Last 20 chat history messages
        $history = Message::where('chat_id', $chat->id)
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at', 'asc')
            ->limit(20)
            ->get();

        foreach ($history as $msg) {
            $messages[] = ['role' => $msg->role, 'content' => $msg->content];
        }

        // 4. RAG — inject into current user message (pass chat for chat-level KB)
        if ($userQuery) {
            $chunks = $this->rag->search($project, $userQuery, 5, $chat);

            if (!empty($chunks)) {
                $enrichedQuery = $this->rag->injectIntoUserMessage($userQuery, $chunks);
                $messages[]    = ['role' => 'user', 'content' => $enrichedQuery];
            } else {
                $messages[] = ['role' => 'user', 'content' => $userQuery];
            }
        }

        return $messages;
    }
}