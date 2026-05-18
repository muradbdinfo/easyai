<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Times New Roman', Times, serif;
        font-size: 11pt;
        color: #000;
        line-height: 1.6;
    }

    /* -- Header -- */
    .exam-header {
        border-bottom: 3px solid #000;
        padding-bottom: 12px;
        margin-bottom: 20px;
    }
    .exam-header table { width: 100%; }
    .school-name {
        font-size: 16pt;
        font-weight: bold;
        text-align: center;
        letter-spacing: 1px;
    }
    .exam-title {
        font-size: 13pt;
        font-weight: bold;
        text-align: center;
        margin-top: 4px;
    }
    .exam-meta {
        font-size: 10pt;
        text-align: center;
        color: #333;
        margin-top: 6px;
    }
    .exam-info {
        margin-top: 14px;
        border: 1px solid #000;
        padding: 8px 12px;
        font-size: 10pt;
    }
    .exam-info table { width: 100%; }
    .exam-info td { padding: 2px 8px; }
    .info-label { font-weight: bold; width: 140px; }

    /* -- Instructions box -- */
    .instructions {
        border: 2px solid #000;
        padding: 10px 14px;
        margin: 16px 0;
        background: #f9f9f9;
    }
    .instructions-title {
        font-weight: bold;
        font-size: 11pt;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .instructions ul {
        margin-left: 18px;
        font-size: 10pt;
    }
    .instructions ul li {
        margin-bottom: 3px;
    }

    /* -- Section header -- */
    .section-header {
        background: #000;
        color: #fff;
        padding: 5px 12px;
        font-weight: bold;
        font-size: 11pt;
        margin: 20px 0 12px 0;
        letter-spacing: 0.5px;
    }

    /* -- Messages / Q&A -- */
    .qa-block {
        margin-bottom: 18px;
        page-break-inside: avoid;
    }

    .qa-question {
        font-weight: bold;
        font-size: 10.5pt;
        margin-bottom: 4px;
        border-left: 3px solid #000;
        padding-left: 10px;
    }

    .qa-answer {
        font-size: 10.5pt;
        padding-left: 13px;
        margin-top: 6px;
        border-left: 3px solid #ccc;
        color: #111;
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    .qa-answer-label {
        font-size: 9pt;
        font-weight: bold;
        color: #555;
        text-transform: uppercase;
        margin-bottom: 2px;
    }

    /* Answer lines (blank exam mode) */
    .answer-lines {
        margin-top: 8px;
        padding-left: 13px;
    }
    .answer-line {
        border-bottom: 1px solid #999;
        margin-bottom: 12px;
        height: 18px;
    }

    /* -- Marks badge -- */
    .marks-badge {
        float: right;
        font-size: 9pt;
        font-weight: bold;
        color: #666;
        border: 1px solid #999;
        padding: 1px 6px;
        border-radius: 2px;
    }

    /* -- Message number -- */
    .msg-number {
        display: inline-block;
        background: #000;
        color: #fff;
        font-weight: bold;
        font-size: 9pt;
        padding: 2px 7px;
        margin-right: 6px;
        min-width: 22px;
        text-align: center;
    }

    /* -- Raw chat mode (fallback for non-Q&A content) -- */
    .chat-user {
        background: #f0f4ff;
        border-left: 4px solid #4f46e5;
        padding: 8px 12px;
        margin-bottom: 10px;
        font-size: 10.5pt;
    }
    .chat-assistant {
        background: #f8f8f8;
        border-left: 4px solid #059669;
        padding: 8px 12px;
        margin-bottom: 10px;
        font-size: 10.5pt;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    .chat-role-label {
        font-size: 8.5pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
        color: #444;
    }
    .chat-time {
        font-size: 8pt;
        color: #999;
        float: right;
    }

    /* -- Footer -- */
    .doc-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        border-top: 1px solid #000;
        padding: 5px 24px;
        font-size: 8.5pt;
        color: #333;
    }
    .doc-footer table { width: 100%; }
    .doc-footer .left  { text-align: left; }
    .doc-footer .right { text-align: right; }

    /* -- Page break -- */
    .page-break { page-break-after: always; }

    /* -- Clearfix -- */
    .clearfix::after { content: ''; display: table; clear: both; }
</style>
</head>
<body>

{{-- Fixed footer on every page --}}
<div class="doc-footer">
    <table>
        <tr>
            <td class="left">
                {{ $isQuestionPaper ? 'Question Paper' : 'EasyAI Export' }} —
                {{ $project->name }}
            </td>
            <td class="right">Page {PAGENO} of {nbpg} &nbsp;|&nbsp; Generated: {{ now()->format('M d, Y') }}</td>
        </tr>
    </table>
</div>

{{-- -- EXAM HEADER -- --}}
<div class="exam-header">
    <div class="school-name">PRESIDENCY INTERNATIONAL SCHOOL</div>
    <div class="exam-title">{{ $chat->title ?? 'Question Paper' }}</div>
    <div class="exam-meta">Project: {{ $project->name }} &nbsp;|&nbsp; Model: {{ $project->model }}</div>

    <div class="exam-info" style="margin-top: 12px;">
        <table>
            <tr>
                <td><span class="info-label">Date:</span> {{ now()->format('d M Y') }}</td>
                <td><span class="info-label">Total Questions:</span> {{ $messages->where('role','user')->count() }}</td>
            </tr>
            <tr>
                <td><span class="info-label">Subject:</span> {{ $project->name }}</td>
                <td><span class="info-label">Total Responses:</span> {{ $messages->where('role','assistant')->count() }}</td>
            </tr>
        </table>
    </div>
</div>

{{-- -- INSTRUCTIONS -- --}}
@if($isQuestionPaper)
<div class="instructions">
    <div class="instructions-title">Instructions to Candidates</div>
    <ul>
        <li>Read each question carefully before answering.</li>
        <li>Write your answers in the space provided.</li>
        <li>Show all working where required.</li>
        <li>This paper was generated using EasyAI based on uploaded Cambridge past papers.</li>
    </ul>
</div>
@endif

{{-- -- CONTENT -- --}}
@if($messages->isEmpty())
    <p style="text-align:center; color:#999; padding: 40px 0;">No messages in this chat.</p>
@else

    @if($isQuestionPaper)
        {{-- -- QUESTION PAPER MODE: pair user(Q) with assistant(A) -- --}}
        <div class="section-header">Questions &amp; Answers</div>

        @php
            $pairs = [];
            $currentQ = null;
            foreach ($messages as $msg) {
                if ($msg->role === 'system') continue;
                if ($msg->role === 'user') {
                    $currentQ = $msg;
                } elseif ($msg->role === 'assistant' && $currentQ) {
                    $pairs[] = ['question' => $currentQ, 'answer' => $msg];
                    $currentQ = null;
                }
            }
            // Unpaired question at end
            if ($currentQ) {
                $pairs[] = ['question' => $currentQ, 'answer' => null];
            }
        @endphp

        @foreach($pairs as $i => $pair)
        <div class="qa-block">
            {{-- Question --}}
            <div class="qa-question clearfix">
                <span class="msg-number">{{ $i + 1 }}</span>
                <span>{{ $pair['question']->content }}</span>
                <span class="chat-time">{{ \Carbon\Carbon::parse($pair['question']->created_at)->format('H:i') }}</span>
            </div>

            {{-- Answer --}}
            @if($pair['answer'])
                <div style="margin-top: 6px;">
                    <div class="qa-answer-label">Answer / Mark Scheme</div>
                    <div class="qa-answer">{{ $pair['answer']->content }}</div>
                </div>
            @else
                {{-- No answer yet — blank lines for exam mode --}}
                <div class="answer-lines">
                    @for($l = 0; $l < 5; $l++)
                        <div class="answer-line"></div>
                    @endfor
                </div>
            @endif
        </div>

        @if(!$loop->last)
            <hr style="border: none; border-top: 1px dashed #ccc; margin: 10px 0;">
        @endif
        @endforeach

    @else
        {{-- -- STANDARD CHAT MODE -- --}}
        <div class="section-header">Chat Transcript</div>

        @foreach($messages as $message)
            @if($message->role === 'system') @continue @endif

            @if($message->role === 'user')
            <div class="chat-user">
                <div class="chat-role-label">
                    You
                    <span class="chat-time">{{ \Carbon\Carbon::parse($message->created_at)->format('M d, Y H:i') }}</span>
                </div>
                {{ $message->content }}
            </div>
            @else
            <div class="chat-assistant">
                <div class="chat-role-label">
                    EasyAI
                    <span class="chat-time">{{ \Carbon\Carbon::parse($message->created_at)->format('M d, Y H:i') }}</span>
                </div>
                {{ $message->content }}
            </div>
            @endif
        @endforeach
    @endif

@endif

</body>
</html>