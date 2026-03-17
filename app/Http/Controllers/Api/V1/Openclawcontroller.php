<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OpenClawService;

class OpenClawController extends Controller
{
    public function health()
    {
        $ok = app(OpenClawService::class)->health();

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'OpenClaw is reachable.' : 'OpenClaw not reachable.',
        ]);
    }
}