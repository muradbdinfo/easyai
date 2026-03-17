<?php

namespace App\Http\Controllers;

use App\Services\OpenClawService;
use Inertia\Inertia;

class OpenClawController extends Controller
{
    public function index()
    {
        return Inertia::render('OpenClaw/Settings', [
            'openclaw_url'   => config('openclaw.url'),
            'openclaw_model' => config('openclaw.model'),
        ]);
    }

    public function health()
    {
        $ok = app(OpenClawService::class)->health();

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'OpenClaw is reachable.' : 'OpenClaw is not reachable.',
        ]);
    }
}