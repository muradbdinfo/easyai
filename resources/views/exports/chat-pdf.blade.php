<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: 11px;
        color: #1e293b;
        background: #ffffff;
        line-height: 1.6;
    }

    /* ── Header ── */
    .doc-header {
        background: #4f46e5;
        color: #ffffff;
        padding: 18px 24px;
        margin-bottom: 0;
    }
    .doc-header .brand {
        font-size: 18px;
        font-weight: 700;
        letter-spacing: 1px;
    }
    .doc-header .meta {
        font-size: 10px;
        color: #c7d2fe;
        margin-top: 4px;
    }
    .doc-header .chat-title {
        font-size: 14px;
        font-weight: 600;
        color: #e0e7ff;
        margin-top: 6px;
    }

    /* ── Info bar ── */
    .info-bar {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 10px 24px;
        font-size: 10px;
        color: #64748b;
    }
    .info-bar table { width: 100%; }
    .info-bar td { padding: 1px 0; }
    .info-bar .label { font-weight: 600; color: #475569; width: 80px; }

    /* ── Messages ── */
    .messages {
        padding: 16px 24px;
    }

    .message {
        margin-bottom: 14px;
        page-break-inside: avoid;
    }

    .message-header {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .message-user .message-header {
        color: #4f46e5;
        text-align: right;
    }

    .message-assistant .message-header {
        color: #475569;
    }

    .message-body {
        font-size: 11px;
        line-height: 1.65;
        padding: 10px 14px;
        border-radius: 6px;
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    .message-user .message-body {
        background: #eef2ff;
        border-left: 3px solid #4f46e5;
        color: #1e1b4b;
        margin-left: 40px;
    }

    .message-assistant .message-body {
        background: #f8fafc;
        border-left: 3px solid #94a3b8;
        color: #1e293b;
        margin-right: 40px;
    }

    .message-time {
        font-size: 9px;
        color: #94a3b8;
        margin-top: 3px;
        text-align: right;
    }

    .message-user .message-time {
        text-align: right;
    }

    .message-assistant .message-time {
        text-align: left;
    }

    .divider {
        border: none;
        border-top: 1px dashed #e2e8f0;
        margin: 10px 0;
    }

    /* ── No messages ── */
    .empty {
        text-align: center;
        padding: 40px;
        color: #94a3b8;
        font-size: 12px;
    }

    /* ── Footer ── */
    .doc-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 6px 24px;
        font-size: 9px;
        color: #94a3b8;
    }
    .doc-footer table { width: 100%; }
    .doc-footer .left  { text-align: left; }
    .doc-footer .right { text-align: right; }
</style>
</head>
<body>

{{-- Footer (fixed at bottom of every page) --}}
<div class="doc-footer">
    <table>
        <tr>
            <td class="left">EasyAI — Confidential</td>
            <td class="right">Page {PAGENO} of {nbpg}</td>
        </tr>
    </table>
</div>

{{-- Header --}}
<div class="doc-header">
    <div class="brand">EasyAI</div>
    <div class="chat-title">{{ $chat->title ?? 'Chat Export' }}</div>
    <div class="meta">Project: {{ $project->name }} &nbsp;|&nbsp; Exported: {{ now()->format('M d, Y H:i') }}</div>
</div>

{{-- Info bar --}}
<div class="info-bar">
    <table>
        <tr>
            <td><span class="label">Project:</span> {{ $project->name }}</td>
            <td><span class="label">Model:</span> {{ $project->model }}</td>
            <td><span class="label">Messages:</span> {{ $messages->count() }}</td>
        </tr>
        <tr>
            <td><span class="label">Chat:</span> {{ $chat->title }}</td>
            <td><span class="label">Status:</span> {{ ucfirst($chat->status) }}</td>
            <td><span class="label">Tokens:</span> {{ number_format($chat->total_tokens) }}</td>
        </tr>
    </table>
</div>

{{-- Messages --}}
<div class="messages">

    @if($messages->isEmpty())
        <div class="empty">No messages in this chat.</div>
    @else
        @foreach($messages as $index => $message)

            @if($message->role === 'system')
                @continue
            @endif

            <div class="message message-{{ $message->role }}">

                <div class="message-header">
                    @if($message->role === 'user')
                        You
                    @else
                        EasyAI
                    @endif
                </div>

                <div class="message-body">{{ $message->content }}</div>

                <div class="message-time">
                    {{ \Carbon\Carbon::parse($message->created_at)->format('M d, Y H:i') }}
                </div>

            </div>

            @if(!$loop->last)
                <hr class="divider">
            @endif

        @endforeach
    @endif

</div>

</body>
</html>