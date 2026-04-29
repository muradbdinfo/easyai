<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocumentJob;
use App\Jobs\ProcessWebUrlJob;
use App\Models\Chat;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeDocument;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KnowledgeBaseController extends Controller
{
    // ── Project-level KB ─────────────────────────────────────────

    public function index(Project $project)
    {
        $this->authorizeTenant($project);

        $kb = $project->knowledgeBases()
            ->whereNull('chat_id')
            ->with(['documents' => fn($q) => $q->orderByDesc('created_at')])
            ->first();

        return Inertia::render('KnowledgeBases/Show', [
            'project' => $project->only('id', 'name'),
            'kb'      => $kb,
            'level'   => 'project',
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeTenant($project);

        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $project->knowledgeBases()->whereNull('chat_id')->updateOrCreate(
            ['project_id' => $project->id, 'chat_id' => null],
            [
                'tenant_id'   => app('tenant')->id,
                'name'        => $request->name,
                'description' => $request->description,
                'is_active'   => true,
            ]
        );

        return back()->with('success', 'Knowledge base saved.');
    }

    // ── Chat-level KB ─────────────────────────────────────────────

    public function chatIndex(Project $project, Chat $chat)
    {
        $this->authorizeTenant($project);
        $this->authorizeChat($project, $chat);

        $kb = KnowledgeBase::where('chat_id', $chat->id)
            ->with(['documents' => fn($q) => $q->orderByDesc('created_at')])
            ->first();

        return Inertia::render('KnowledgeBases/Show', [
            'project' => $project->only('id', 'name'),
            'chat'    => $chat->only('id', 'title'),
            'kb'      => $kb,
            'level'   => 'chat',
        ]);
    }

    public function chatStore(Request $request, Project $project, Chat $chat)
    {
        $this->authorizeTenant($project);
        $this->authorizeChat($project, $chat);

        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        KnowledgeBase::updateOrCreate(
            ['chat_id' => $chat->id],
            [
                'project_id'  => $project->id,
                'tenant_id'   => app('tenant')->id,
                'name'        => $request->name,
                'description' => $request->description,
                'is_active'   => true,
            ]
        );

        return back()->with('success', 'Chat knowledge base saved.');
    }

    // ── Document Upload (shared for project + chat KB) ────────────

    public function uploadDocument(Request $request, Project $project)
    {
        $this->authorizeTenant($project);

        $request->validate([
            'title'   => 'nullable|string|max:200',
            'chat_id' => 'nullable|integer|exists:chats,id',
        ]);

        $file = $request->file('file');
        if (!$request->hasFile('file')) {
            return back()->withErrors(['file' => 'No file uploaded.']);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['pdf', 'txt', 'md', 'docx', 'doc', 'xlsx', 'xls'])) {
            return back()->withErrors(['file' => 'Supported types: PDF, DOCX, DOC, XLSX, XLS, TXT, MD']);
        }

        if ($file->getSize() > 20 * 1024 * 1024) {
            return back()->withErrors(['file' => 'File must be under 20MB.']);
        }

        // Find correct KB
        if ($request->chat_id) {
            $kb = KnowledgeBase::where('chat_id', $request->chat_id)->where('is_active', true)->first();
        } else {
            $kb = $project->knowledgeBases()->whereNull('chat_id')->where('is_active', true)->first();
        }

        if (!$kb) {
            return back()->withErrors(['kb' => 'Create a knowledge base first.']);
        }

        $path  = $file->store('knowledge/' . app('tenant')->id);
        $title = $request->title ?: $file->getClientOriginalName();

        $doc = KnowledgeDocument::create([
            'knowledge_base_id' => $kb->id,
            'tenant_id'         => app('tenant')->id,
            'title'             => $title,
            'file_path'         => $path,
            'file_type'         => $ext,
            'file_size'         => $file->getSize(),
            'status'            => 'pending',
        ]);

        ProcessDocumentJob::dispatch($doc->id);

        return back()->with('success', 'Document uploaded. Processing...');
    }

    public function destroyDocument(Project $project, KnowledgeDocument $document)
    {
        $this->authorizeTenant($project);

        if ($document->knowledgeBase->project_id !== $project->id) {
            abort(403);
        }

        \Storage::delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }


    // ── URL Import ──────────────────────────────────────────────

    public function uploadUrl(Request $request, Project $project)
    {
        $this->authorizeTenant($project);

        $request->validate([
            'url'        => ['required', 'url', 'max:2048'],
            'title'      => ['nullable', 'string', 'max:200'],
            'chat_id'    => ['nullable', 'integer', 'exists:chats,id'],
            'max_pages'  => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        // Find correct KB
        if ($request->chat_id) {
            $kb = KnowledgeBase::where('chat_id', $request->chat_id)->where('is_active', true)->first();
        } else {
            $kb = $project->knowledgeBases()->whereNull('chat_id')->where('is_active', true)->first();
        }

        if (!$kb) {
            return back()->withErrors(['kb' => 'Create a knowledge base first.']);
        }

        $doc = KnowledgeDocument::create([
            'knowledge_base_id' => $kb->id,
            'tenant_id'         => app('tenant')->id,
            'title'             => $request->title ?: parse_url($request->url, PHP_URL_HOST),
            'source_type'       => 'url',
            'source_url'        => $request->url,
            'file_type'         => 'html',
            'file_size'         => 0,
            'status'            => 'pending',
        ]);

        ProcessWebUrlJob::dispatch($doc->id, $request->integer('max_pages', 1));

        return back()->with('success', 'URL submitted. Crawling and processing...');
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function authorizeTenant(Project $project): void
    {
        if ($project->tenant_id !== app('tenant')->id) abort(403);
    }

    private function authorizeChat(Project $project, Chat $chat): void
    {
        if ($chat->project_id !== $project->id || $chat->tenant_id !== app('tenant')->id) abort(403);
    }
}