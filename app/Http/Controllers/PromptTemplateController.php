<?php

namespace App\Http\Controllers;

use App\Models\PromptTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromptTemplateController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');

        $mine = PromptTemplate::where('tenant_id', $tenant->id)
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        $shared = PromptTemplate::where('tenant_id', $tenant->id)
            ->where('is_shared', true)
            ->where('user_id', '!=', auth()->id())
            ->latest()
            ->get();

        return Inertia::render('Templates/Index', [
            'my_templates'     => $mine,
            'shared_templates' => $shared,
        ]);
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'content'   => ['required', 'string', 'max:4000'],
            'is_shared' => ['boolean'],
        ]);

        PromptTemplate::create([
            'tenant_id' => $tenant->id,
            'user_id'   => auth()->id(),
            'name'      => $validated['name'],
            'content'   => $validated['content'],
            'is_shared' => $validated['is_shared'] ?? false,
        ]);

        return back()->with('success', 'Template created.');
    }

    public function update(Request $request, PromptTemplate $promptTemplate)
    {
        $tenant = app('tenant');

        abort_if($promptTemplate->tenant_id !== $tenant->id, 403);
        abort_if($promptTemplate->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'content'   => ['required', 'string', 'max:4000'],
            'is_shared' => ['boolean'],
        ]);

        $promptTemplate->update($validated);

        return back()->with('success', 'Template updated.');
    }

    public function destroy(PromptTemplate $promptTemplate)
    {
        $tenant = app('tenant');

        abort_if($promptTemplate->tenant_id !== $tenant->id, 403);
        abort_if($promptTemplate->user_id !== auth()->id(), 403);

        $promptTemplate->delete();

        return back()->with('success', 'Template deleted.');
    }
}