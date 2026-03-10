<?php

namespace App\Http\Controllers;

use App\Models\GitHubConnection;
use App\Models\IntegrationFile;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeChunk;
use App\Models\Project;
use App\Services\ChunkingService;
use App\Services\GitHubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GitHubController extends Controller
{
    public function __construct(
        private GitHubService   $github,
        private ChunkingService $chunker
    ) {}

    public function redirect()
    {
        return redirect($this->github->getAuthUrl());
    }

    public function callback(Request $request)
    {
        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('settings.integrations')->with('error', 'GitHub connection cancelled.');
        }

        $tokenData = $this->github->exchangeCode($code);
        if (empty($tokenData['access_token'])) {
            return redirect()->route('settings.integrations')->with('error', 'GitHub OAuth failed. Try again.');
        }

        $token  = $tokenData['access_token'];
        $ghUser = $this->github->getUser($token);
        $tenant = app('tenant');

        GitHubConnection::updateOrCreate(
            ['tenant_id' => $tenant->id, 'user_id' => Auth::id()],
            [
                'github_user'    => $ghUser['login'],
                'github_user_id' => (string) $ghUser['id'],
                'access_token'   => $token,
                'scopes'         => $tokenData['scope'] ?? null,
                'connected_at'   => now(),
            ]
        );

        return redirect()->route('settings.integrations')->with('success', 'GitHub connected successfully.');
    }

    public function disconnect()
    {
        GitHubConnection::where('tenant_id', app('tenant')->id)
            ->where('user_id', Auth::id())
            ->delete();

        return back()->with('success', 'GitHub disconnected.');
    }

    public function repos(Request $request)
    {
        $conn = $this->connection();
        if (!$conn) return response()->json(['error' => 'Not connected.'], 403);

        try {
            $repos = $this->github->listRepos($conn->access_token, (int) $request->get('page', 1));

            if (!is_array($repos) || isset($repos['message'])) {
                return response()->json(['data' => [], 'error' => $repos['message'] ?? 'GitHub error']);
            }

            return response()->json([
                'data' => array_map(fn($r) => [
                    'full_name'   => $r['full_name'] ?? '',
                    'name'        => $r['name'] ?? '',
                    'private'     => $r['private'] ?? false,
                    'description' => $r['description'] ?? '',
                    'updated_at'  => $r['updated_at'] ?? '',
                ], $repos),
            ]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'error' => $e->getMessage()], 200);
        }
    }

    public function contents(Request $request)
    {
        $request->validate([
            'repo' => 'required|string',
            'path' => 'nullable|string',
        ]);

        $conn = $this->connection();
        if (!$conn) return response()->json(['error' => 'Not connected.'], 403);

        try {
            $items = $this->github->listContents(
                $conn->access_token,
                $request->repo,
                (string) $request->get('path', '')
            );

            if (!is_array($items) || isset($items['message'])) {
                return response()->json(['data' => [], 'error' => $items['message'] ?? 'GitHub error']);
            }

            $mapped = array_map(fn($i) => [
                'name' => $i['name'] ?? '',
                'path' => $i['path'] ?? '',
                'type' => $i['type'] ?? 'file',
                'size' => $i['size'] ?? 0,
            ], $items);

            usort($mapped, fn($a, $b) => $a['type'] === 'dir' ? -1 : 1);

            return response()->json(['data' => $mapped]);

        } catch (\Exception $e) {
            return response()->json(['data' => [], 'error' => $e->getMessage()], 200);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'project_id' => 'required|integer',
            'repo'       => 'required|string',
            'path'       => 'required|string',
            'name'       => 'required|string',
        ]);

        $tenant = app('tenant');
        $conn   = $this->connection();
        if (!$conn) return response()->json(['error' => 'Not connected.'], 403);

        $project = Project::where('id', $request->project_id)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        try {
            $content = $this->github->getFileContent(
                $conn->access_token,
                $request->repo,
                $request->path
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not fetch file: ' . $e->getMessage()], 422);
        }

        if (empty($content)) {
            return response()->json(['error' => 'File is empty or could not be fetched.'], 422);
        }

        if (!mb_check_encoding($content, 'UTF-8')) {
            return response()->json(['error' => 'Binary files are not supported.'], 422);
        }

        $externalId = $request->repo . ':' . $request->path;

        $file = IntegrationFile::updateOrCreate(
            [
                'tenant_id'   => $tenant->id,
                'project_id'  => $project->id,
                'source'      => 'github',
                'external_id' => $externalId,
            ],
            [
                'name'      => $request->name,
                'path'      => $request->path,
                'content'   => $content,
                'byte_size' => strlen($content),
                'synced_at' => now(),
            ]
        );

        $this->storeChunks($file, $project->id, $tenant->id, $content, $request->name);

        return response()->json([
            'success' => true,
            'message' => 'File imported successfully.',
            'data'    => [
                'id'        => $file->id,
                'name'      => $file->name,
                'path'      => $file->path,
                'byte_size' => $file->byte_size,
                'synced_at' => $file->synced_at,
            ],
        ]);
    }

    public function syncFile(IntegrationFile $file)
    {
        abort_if($file->tenant_id !== app('tenant')->id, 403);

        $conn = $this->connection();
        if (!$conn) return response()->json(['error' => 'Not connected.'], 403);

        [$repo, $path] = explode(':', $file->external_id, 2);

        try {
            $content = $this->github->getFileContent($conn->access_token, $repo, $path);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not fetch file: ' . $e->getMessage()], 422);
        }

        if (empty($content)) {
            return response()->json(['error' => 'File is empty or could not be fetched.'], 422);
        }

        $file->update([
            'content'   => $content,
            'byte_size' => strlen($content),
            'synced_at' => now(),
        ]);

        $this->storeChunks($file, $file->project_id, $file->tenant_id, $content, $file->name);

        return response()->json(['success' => true, 'message' => 'File re-synced successfully.']);
    }

    public function deleteFile(IntegrationFile $file)
    {
        abort_if($file->tenant_id !== app('tenant')->id, 403);

        // Delete knowledge document (chunks cascade via FK)
        KnowledgeDocument::where('file_path', 'github::' . $file->id)->delete();
        $file->delete();

        return response()->json(['success' => true, 'message' => 'File removed.']);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function storeChunks(IntegrationFile $file, int $projectId, int $tenantId, string $content, string $title): void
    {
        $kb = KnowledgeBase::firstOrCreate(
            ['project_id' => $projectId, 'tenant_id' => $tenantId],
            ['name' => 'Project Knowledge', 'is_active' => true]
        );

        // Delete old document for this file (chunks cascade)
        KnowledgeDocument::where('knowledge_base_id', $kb->id)
            ->where('file_path', 'github::' . $file->id)
            ->delete();

        $doc = KnowledgeDocument::create([
            'knowledge_base_id' => $kb->id,
            'tenant_id'         => $tenantId,
            'title'             => $title,
            'file_path'         => 'github::' . $file->id,
            'file_type'         => 'txt',
            'file_size'         => $file->byte_size,
            'status'            => 'processing',
            'chunk_count'       => 0,
        ]);

        $chunks = $this->chunker->chunk($content);

        foreach ($chunks as $index => $chunk) {
            KnowledgeChunk::create([
                'knowledge_base_id' => $kb->id,
                'document_id'       => $doc->id,
                'tenant_id'         => $tenantId,
                'content'           => $chunk,
                'chunk_index'       => $index,
            ]);
        }

        $doc->update(['status' => 'ready', 'chunk_count' => count($chunks)]);
    }

    private function connection(): ?GitHubConnection
    {
        return GitHubConnection::where('tenant_id', app('tenant')->id)
            ->where('user_id', Auth::id())
            ->first();
    }
}