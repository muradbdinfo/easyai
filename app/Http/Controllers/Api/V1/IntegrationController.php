<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\IntegrationFile;
use App\Models\Project;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function files(Project $project)
    {
        abort_if($project->tenant_id !== auth()->user()->tenant_id, 403);

        $files = IntegrationFile::where('project_id', $project->id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->select('id', 'name', 'path', 'source', 'byte_size', 'synced_at')
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $files]);
    }
}