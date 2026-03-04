<?php

// FILE: app/Http/Controllers/ProjectController.php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index()
    {
        $tenant   = app('tenant');
        $projects = Project::where('tenant_id', $tenant->id)
            ->withCount('chats')
            ->latest()
            ->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
        ]);
    }

    public function store(Request $request)
    {
        $tenant          = app('tenant');
        $allowedModels   = config('ollama.available_models', [config('ollama.model')]);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'model'       => ['nullable', 'string', 'in:' . implode(',', $allowedModels)],
        ]);

        $project = Project::create([
            'tenant_id'   => $tenant->id,
            'user_id'     => $request->user()->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'model'       => $validated['model'] ?? config('ollama.model'),
        ]);

        return redirect()->route('projects.show', $project->id);
    }

    public function show(Request $request, Project $project)
    {
        $tenant = app('tenant');
        abort_if($project->tenant_id !== $tenant->id, 403);

        $project->load(['chats' => function ($q) {
            $q->latest()->take(50);
        }]);

        return Inertia::render('Projects/Show', [
            'project'       => $project,
            'chats'         => $project->chats,
            'ollama_models' => config('ollama.available_models', [config('ollama.model')]),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $tenant        = app('tenant');
        $allowedModels = config('ollama.available_models', [config('ollama.model')]);

        abort_if($project->tenant_id !== $tenant->id, 403);

        $validated = $request->validate([
            'name'           => ['sometimes', 'required', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'system_prompt'  => ['nullable', 'string', 'max:4000'],
            'model'          => ['nullable', 'string', 'in:' . implode(',', $allowedModels)],
        ]);

        // Only update fields that were actually sent
        $updateData = array_filter([
            'name'          => $validated['name']          ?? null,
            'description'   => $validated['description']   ?? null,
            'system_prompt' => $validated['system_prompt'] ?? null,
            'model'         => $validated['model']         ?? null,
        ], fn($v) => $v !== null);

        // Allow explicit null for description and system_prompt
        if (array_key_exists('description', $validated)) {
            $updateData['description'] = $validated['description'];
        }
        if (array_key_exists('system_prompt', $validated)) {
            $updateData['system_prompt'] = $validated['system_prompt'];
        }
        if (array_key_exists('model', $validated) && $validated['model']) {
            $updateData['model'] = $validated['model'];
        }

        $project->update($updateData);

        return back()->with('success', 'Project updated.');
    }

    public function destroy(Request $request, Project $project)
    {
        $tenant = app('tenant');
        abort_if($project->tenant_id !== $tenant->id, 403);

        $project->delete();

        return redirect()->route('projects.index');
    }

    public function clearMemory(Request $request, Project $project)
    {
        $tenant = app('tenant');
        abort_if($project->tenant_id !== $tenant->id, 403);

        $project->update(['context_summary' => null]);

        return back()->with('success', 'Project memory cleared.');
    }

    public function updateMemory(Request $request, Project $project)
    {
        $tenant = app('tenant');
        abort_if($project->tenant_id !== $tenant->id, 403);

        $validated = $request->validate([
            'context_summary' => ['nullable', 'string'],
        ]);

        $project->update(['context_summary' => $validated['context_summary']]);

        return back()->with('success', 'Project memory updated.');
    }
}