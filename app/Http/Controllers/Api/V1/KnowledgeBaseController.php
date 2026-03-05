<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDocumentJob;
use App\Models\KnowledgeDocument;
use App\Models\Project;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function show(Project $project)
    {
        if ($project->tenant_id !== auth()->user()->tenant_id) abort(403);

        $kb = $project->knowledgeBases()
            ->with(['documents' => fn($q) => $q->select('id','knowledge_base_id','title','file_type','status','chunk_count','created_at')])
            ->first();

        return response()->json(['success' => true, 'data' => $kb]);
    }

    public function store(Request $request, Project $project)
    {
        if ($project->tenant_id !== auth()->user()->tenant_id) abort(403);

        $request->validate(['name' => 'required|string|max:100']);

        $kb = $project->knowledgeBases()->updateOrCreate(
            ['project_id' => $project->id],
            ['tenant_id' => auth()->user()->tenant_id, 'name' => $request->name, 'is_active' => true]
        );

        return response()->json(['success' => true, 'data' => $kb]);
    }

    public function uploadDocument(Request $request, Project $project)
    {
        if ($project->tenant_id !== auth()->user()->tenant_id) abort(403);

        $request->validate(['file' => 'required|file|mimes:pdf,txt,md|max:10240']);

        $kb = $project->knowledgeBases()->where('is_active', true)->first();
        if (!$kb) return response()->json(['success' => false, 'message' => 'No active KB'], 422);

        $file = $request->file('file');
        $path = $file->store('knowledge/' . auth()->user()->tenant_id);

        $doc = KnowledgeDocument::create([
            'knowledge_base_id' => $kb->id,
            'tenant_id'         => auth()->user()->tenant_id,
            'title'             => $request->title ?: $file->getClientOriginalName(),
            'file_path'         => $path,
            'file_type'         => strtolower($file->getClientOriginalExtension()),
            'file_size'         => $file->getSize(),
            'status'            => 'pending',
        ]);

        ProcessDocumentJob::dispatch($doc->id);

        return response()->json(['success' => true, 'data' => $doc, 'message' => 'Processing...'], 201);
    }

    public function destroyDocument(Project $project, KnowledgeDocument $document)
    {
        if ($project->tenant_id !== auth()->user()->tenant_id) abort(403);
        \Storage::delete($document->file_path);
        $document->delete();
        return response()->json(['success' => true, 'message' => 'Deleted.']);
    }
}