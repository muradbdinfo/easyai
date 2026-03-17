<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\N8nWebhookLog;
use App\Models\Tenant;
use App\Services\N8nService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class N8nController extends Controller
{
    public function __construct(private N8nService $n8n) {}

    // ── Settings page (auth + addon:n8n-automation middleware from route) ──
public function settings()
{
    $tenant   = app('tenant');
    $settings = $this->n8n->settingsFor($tenant);

    $logs = N8nWebhookLog::where('tenant_id', $tenant->id)
        ->orderByDesc('created_at')
        ->limit(50)
        ->get();

    return Inertia::render('N8n/Settings', [
        'settings' => [
            'webhook_url'             => $settings->webhook_url ?? config('services.n8n.webhook_url'),
            'callback_secret'         => $settings->callback_secret ?? config('services.n8n.callback_secret'),
            'event_message_sent'      => $settings->event_message_sent,
            'event_assistant_replied' => $settings->event_assistant_replied,
            'event_payment_completed' => $settings->event_payment_completed,
            'event_tenant_registered' => $settings->event_tenant_registered,
            'is_enabled'              => $settings->is_enabled,
        ],
        'logs'         => $logs,
        'platform_url' => config('services.n8n.webhook_url'),
    ]);
}

    // ── Save settings ──────────────────────────────────────────────────────
    public function update(Request $request)
    {
        $tenant = app('tenant');

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

        return back()->with('success', 'n8n settings saved.');
    }

    // ── Clear logs ─────────────────────────────────────────────────────────
    public function clearLogs()
    {
        $tenant = app('tenant');
        N8nWebhookLog::where('tenant_id', $tenant->id)->delete();
        return back()->with('success', 'Webhook logs cleared.');
    }

    // ── Inbound callback from n8n ──────────────────────────────────────────
    // NO auth middleware on this route — n8n is an external server with no session.
    // Security = HMAC-SHA256 in X-N8n-Signature header.
    // Tenant resolved from the Chat model, NOT from app('tenant').
    public function callback(Request $request, Chat $chat)
    {
        // Resolve tenant from chat directly — no session exists
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
            'message_id' => $message->id,
        ]);
    }
}