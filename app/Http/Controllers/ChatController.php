<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatController extends Controller
{


public function createQuick(Request $request)
{
    $tenant = app('tenant');

    $project = \App\Models\Project::where('tenant_id', $tenant->id)
        ->where('is_default', true)
        ->firstOrFail();

    $chat = Chat::create([
        'project_id' => $project->id,
        'user_id'    => $request->user()->id,
        'tenant_id'  => $tenant->id,
        'title'      => 'New Chat',
        'status'     => 'open',
    ]);

    return redirect()->route('projects.chats.show', [$project->id, $chat->id]);
}


    public function store(Request $request, Project $project)
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);

        $chat = Chat::create([
            'project_id' => $project->id,
            'user_id'    => $request->user()->id,
            'tenant_id'  => $tenant->id,
            'title'      => 'New Chat',
            'status'     => 'open',
        ]);

        return redirect()->route('projects.chats.show', [$project->id, $chat->id]);
    }

    public function show(Request $request, Project $project, Chat $chat)
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->project_id !== $project->id, 404);
        abort_if($chat->tenant_id !== $tenant->id, 403);
        abort_if(!auth()->user()->canAccessProject($project), 403);

        $chat->load(['messages' => function ($q) {
            $q->orderBy('created_at', 'asc')->take(50);
        }]);

        return Inertia::render('Chats/Show', [
            'project' => $project,
            'chat'    => $chat,
        ]);
    }

    public function destroy(Request $request, Project $project, Chat $chat)
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->project_id !== $project->id, 404);
        abort_if($chat->tenant_id !== $tenant->id, 403);

        $chat->delete();

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Chat deleted.');
    }
}