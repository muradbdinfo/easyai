<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Project;

class MemoryService
{
    /**
     * Build the Ollama messages array for a chat.
     *
     * Order:
     *   1. system_prompt (if set on project)
     *   2. context_summary (if set on project)
     *   3. Last 20 user/assistant messages ordered ASC
     */
    public function buildContext(Chat $chat, Project $project): array
    {
        $messages = [];

        // a) System prompt
        if (!empty($project->system_prompt)) {
            $messages[] = [
                'role'    => 'system',
                'content' => $project->system_prompt,
            ];
        }

        // b) Long-term memory (context summary)
        if (!empty($project->context_summary)) {
            $messages[] = [
                'role'    => 'system',
                'content' => 'Previous context summary:' . "\n" . $project->context_summary,
            ];
        }

        // c) Last 20 user/assistant messages
        $history = Message::where('chat_id', $chat->id)
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at', 'asc')
            ->take(20)
            ->get();

        foreach ($history as $msg) {
            $messages[] = [
                'role'    => $msg->role,
                'content' => $msg->content,
            ];
        }

        return $messages;
    }
}