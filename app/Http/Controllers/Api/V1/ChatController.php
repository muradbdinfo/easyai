<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403, 'Forbidden.');

        $chats = Chat::where('project_id', $project->id)
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $chats->items(),
            'meta'    => [
                'current_page' => $chats->currentPage(),
                'total'        => $chats->total(),
                'per_page'     => $chats->perPage(),
                'last_page'    => $chats->lastPage(),
            ],
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403, 'Forbidden.');

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $chat = Chat::create([
            'project_id' => $project->id,
            'user_id'    => $request->user()->id,
            'tenant_id'  => $tenant->id,
            'title'      => $validated['title'] ?? 'New Chat',
            'status'     => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Chat created.',
            'data'    => $chat,
        ], 201);
    }

    public function show(Request $request, Project $project, Chat $chat): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403, 'Forbidden.');
        abort_if($chat->project_id !== $project->id, 404, 'Not found.');
        abort_if($chat->tenant_id !== $tenant->id, 403, 'Forbidden.');

        $chat->load(['messages' => function ($q) {
            $q->orderBy('created_at', 'asc')->take(50);
        }]);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $chat,
        ]);
    }

    public function destroy(Request $request, Project $project, Chat $chat): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403, 'Forbidden.');
        abort_if($chat->project_id !== $project->id, 404, 'Not found.');
        abort_if($chat->tenant_id !== $tenant->id, 403, 'Forbidden.');

        $chat->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chat deleted.',
            'data'    => null,
        ]);
    }

    public function close(Request $request, Project $project, Chat $chat): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403, 'Forbidden.');
        abort_if($chat->tenant_id !== $tenant->id, 403, 'Forbidden.');

        $validated = $request->validate([
            'closed_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $chat->update([
            'status'        => 'closed',
            'closed_reason' => $validated['closed_reason'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Chat closed.',
            'data'    => $chat->fresh(),
        ]);
    }
}