<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');

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

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created.');
    }

    public function show(Project $project)
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);

        $project->load(['chats' => function ($q) {
            $q->latest()->take(20);
        }]);

        $project->loadCount('chats');

        return Inertia::render('Projects/Show', [
            'project' => $project,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);

        $validated = $request->validate([
            'name'            => ['sometimes', 'required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:1000'],
            'system_prompt'   => ['nullable', 'string', 'max:4000'],
            'context_summary' => ['nullable', 'string'],
            'model'           => ['nullable', 'string', 'max:100'],
        ]);

        $project->update($validated);

        return back()->with('success', 'Project updated.');
    }

    public function destroy(Project $project)
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted.');
    }

    public function clearMemory(Project $project)
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

    $request->validate([
        'context_summary' => ['nullable', 'string', 'max:20000'],
    ]);

    $project->update(['context_summary' => $request->context_summary]);

    return back()->with('success', 'Project memory updated.');
}

}