<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\N8nWebhookLog;
use App\Models\Tenant;
use App\Services\N8nService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class N8nController extends Controller
{
    public function __construct(private N8nService $n8n) {}

    // ── GET /api/v1/n8n/settings ──────────────────────────────────────────
    public function settings(Request $request): JsonResponse
    {
        $tenant = app('tenant');
        abort_unless($tenant->hasAddon('n8n-automation'), 403);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $this->n8n->settingsFor($tenant),
        ]);
    }

    // ── PUT /api/v1/n8n/settings ──────────────────────────────────────────
    public function update(Request $request): JsonResponse
    {
        $tenant = app('tenant');
        abort_unless($tenant->hasAddon('n8n-automation'), 403);

        $validated = $request->validate([
            'webhook_url'             => ['nullable', 'url', 'max:500'],
            'callback_secret'         => ['nullable', 'string', 'max:128'],
            'event_message_sent'      => ['boolean'],
            'event_assistant_replied' => ['boolean'],
            'event_payment_completed' => ['boolean'],
            'event_tenant_registered' => ['boolean'],
            'is_enabled'              => ['boolean'],
        ]);

        $settings = $this->n8n->settingsFor($tenant);
        $settings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Settings saved.',
            'data'    => $settings->fresh(),
        ]);
    }

    // ── GET /api/v1/n8n/logs ──────────────────────────────────────────────
    public function logs(Request $request): JsonResponse
    {
        $tenant = app('tenant');
        abort_unless($tenant->hasAddon('n8n-automation'), 403);

        $logs = N8nWebhookLog::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $logs->items(),
            'meta'    => [
                'current_page' => $logs->currentPage(),
                'total'        => $logs->total(),
                'per_page'     => $logs->perPage(),
            ],
        ]);
    }

    // ── DELETE /api/v1/n8n/logs ───────────────────────────────────────────
    public function clearLogs(Request $request): JsonResponse
    {
        $tenant = app('tenant');
        abort_unless($tenant->hasAddon('n8n-automation'), 403);

        N8nWebhookLog::where('tenant_id', $tenant->id)->delete();

        return response()->json(['success' => true, 'message' => 'Logs cleared.', 'data' => null]);
    }

    // ── POST /api/v1/n8n/callback/{chat} ──────────────────────────────────
    // NO auth middleware — n8n is external. Tenant resolved from Chat model.
    public function callback(Request $request, Chat $chat): JsonResponse
    {
        // Resolve tenant from chat — no Sanctum token or session available
        $tenant = Tenant::find($chat->tenant_id);

        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Tenant not found.'], 404);
        }

        if (!$tenant->hasAddon('n8n-automation')) {
            return response()->json(['success' => false, 'message' => 'n8n addon not active.'], 403);
        }

        // Verify HMAC signature
        $rawBody   = $request->getContent();
        $signature = $request->header('X-N8n-Signature', '');

        if (!$this->n8n->verifyCallback($tenant, $rawBody, $signature)) {
            $this->n8n->logInbound($tenant, 'callback.received', [], false, 'Invalid signature.');
            return response()->json(['success' => false, 'message' => 'Invalid signature.'], 401);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:8000'],
        ]);

        if ($chat->isClosed()) {
            $this->n8n->logInbound($tenant, 'callback.received', $validated, false, 'Chat is closed.');
            return response()->json(['success' => false, 'message' => 'Chat is closed.'], 422);
        }

        $message = Message::create([
            'chat_id'   => $chat->id,
            'tenant_id' => $tenant->id,
            'role'      => 'assistant',
            'content'   => $validated['content'],
            'tokens'    => (int) ceil(mb_strlen($validated['content']) / 4),
            'model'     => 'n8n',
        ]);

        $this->n8n->logInbound($tenant, 'callback.received', $validated, true);

        return response()->json([
            'success'    => true,
            'message'    => 'Message injected.',
            'data'       => ['message_id' => $message->id],
        ]);
    }
}