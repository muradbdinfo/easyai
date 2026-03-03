<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    // ─── Current Tenant ───────────────────────────────────────────
    public function show(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'No tenant assigned.',
                'data'    => null,
            ], 404);
        }

        $tenant->load('plan');

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => [
                'id'         => $tenant->id,
                'name'       => $tenant->name,
                'slug'       => $tenant->slug,
                'status'     => $tenant->status,
                'plan'       => [
                    'id'    => $tenant->plan->id,
                    'name'  => $tenant->plan->name,
                    'price' => $tenant->plan->price,
                ],
                'token_quota'  => $tenant->token_quota,
                'tokens_used'  => $tenant->tokens_used,
                'trial_ends_at' => $tenant->trial_ends_at,
            ],
        ]);
    }

    // ─── Usage Stats ──────────────────────────────────────────────
    public function usage(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'No tenant assigned.',
                'data'    => null,
            ], 404);
        }

        $remaining = $tenant->tokensRemaining();
        $percent   = $tenant->percentUsed();

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => [
                'used'       => $tenant->tokens_used,
                'total'      => $tenant->token_quota,
                'remaining'  => $remaining,
                'percent'    => $percent,
                'exceeded'   => $remaining <= 0,
                'reset_date' => now()->startOfMonth()->addMonth()->toDateString(),
            ],
        ]);
    }
}