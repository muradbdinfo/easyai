<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = app('tenant');

        $projects = Project::where('tenant_id', $tenant->id)
            ->withCount('chats')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $projects->items(),
            'meta'    => [
                'current_page' => $projects->currentPage(),
                'total'        => $projects->total(),
                'per_page'     => $projects->perPage(),
                'last_page'    => $projects->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'model'       => ['nullable', 'string', 'max:100'],
        ]);

        $project = Project::create([
            'tenant_id'   => $tenant->id,
            'user_id'     => $request->user()->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'model'       => $validated['model'] ?? 'llama3',
        ]);

        $project->loadCount('chats');

        return response()->json([
            'success' => true,
            'message' => 'Project created.',
            'data'    => $project,
        ], 201);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403, 'Forbidden.');

        $project->loadCount('chats');
        $project->load(['chats' => function ($q) {
            $q->latest()->take(5)->select('id', 'project_id', 'title', 'status', 'total_tokens', 'updated_at');
        }]);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $project,
        ]);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403, 'Forbidden.');

        $validated = $request->validate([
            'name'          => ['sometimes', 'required', 'string', 'max:255'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'system_prompt' => ['nullable', 'string', 'max:4000'],
            'model'         => ['nullable', 'string', 'max:100'],
        ]);

        $project->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Project updated.',
            'data'    => $project->fresh(),
        ]);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403, 'Forbidden.');

        $chatsCount = $project->chats()->count();
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => "Project deleted. {$chatsCount} chat(s) removed.",
            'data'    => null,
        ]);
    }


    public function clearMemory(Request $request, Project $project): JsonResponse
{
    $tenant = app('tenant');
    abort_if($project->tenant_id !== $tenant->id, 403);

    $project->update(['context_summary' => null]);

    return response()->json([
        'success' => true,
        'message' => 'Project memory cleared.',
        'data'    => null,
    ]);
}

public function updateMemory(Request $request, Project $project): JsonResponse
{
    $tenant = app('tenant');
    abort_if($project->tenant_id !== $tenant->id, 403);

    $request->validate([
        'context_summary' => ['nullable', 'string', 'max:20000'],
    ]);

    $project->update(['context_summary' => $request->context_summary]);

    return response()->json([
        'success' => true,
        'message' => 'Project memory updated.',
        'data'    => $project->fresh(['id', 'name', 'context_summary']),
    ]);
}


}