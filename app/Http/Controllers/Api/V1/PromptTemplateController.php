<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PromptTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromptTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = app('tenant');

        $templates = PromptTemplate::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)
                  ->orWhere('is_shared', true);
            })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $templates,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'content'   => ['required', 'string', 'max:4000'],
            'is_shared' => ['boolean'],
        ]);

        $template = PromptTemplate::create([
            'tenant_id' => $tenant->id,
            'user_id'   => $request->user()->id,
            'name'      => $validated['name'],
            'content'   => $validated['content'],
            'is_shared' => $validated['is_shared'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template created.',
            'data'    => $template,
        ], 201);
    }

    public function update(Request $request, PromptTemplate $promptTemplate): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($promptTemplate->tenant_id !== $tenant->id, 403);
        abort_if($promptTemplate->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'content'   => ['required', 'string', 'max:4000'],
            'is_shared' => ['boolean'],
        ]);

        $promptTemplate->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Template updated.',
            'data'    => $promptTemplate->fresh(),
        ]);
    }

    public function destroy(Request $request, PromptTemplate $promptTemplate): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($promptTemplate->tenant_id !== $tenant->id, 403);
        abort_if($promptTemplate->user_id !== $request->user()->id, 403);

        $promptTemplate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted.',
            'data'    => null,
        ]);
    }
}