<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class ExportController extends Controller
{
    // ─── PDF Export ────────────────────────────────────────────────
    public function exportPdf(Request $request, Project $project, Chat $chat): Response
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->project_id   !== $project->id, 404);
        abort_if($chat->tenant_id    !== $tenant->id, 403);

        $messages = Message::where('chat_id', $chat->id)
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at', 'asc')
            ->get();

        $html = view('exports.chat-pdf', [
            'project'  => $project,
            'chat'     => $chat,
            'messages' => $messages,
        ])->render();

        $mpdf = new Mpdf([
            'margin_top'    => 10,
            'margin_bottom' => 20,
            'margin_left'   => 10,
            'margin_right'  => 10,
            'format'        => 'A4',
            'orientation'   => 'P',
            'tempDir'       => storage_path('app/mpdf-tmp'),
        ]);

        $mpdf->SetTitle($chat->title ?? 'Chat Export');
        $mpdf->SetAuthor('EasyAI');
        $mpdf->WriteHTML($html);

        $filename = 'chat-' . $chat->id . '-' . now()->format('Ymd-His') . '.pdf';

        return response(
            $mpdf->Output('', 'S'),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    // ─── Markdown Export ───────────────────────────────────────────
    public function exportMarkdown(Request $request, Project $project, Chat $chat): Response
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->project_id   !== $project->id, 404);
        abort_if($chat->tenant_id    !== $tenant->id, 403);

        $messages = Message::where('chat_id', $chat->id)
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at', 'asc')
            ->get();

        $lines   = [];
        $lines[] = '# ' . ($chat->title ?? 'Chat Export');
        $lines[] = '';
        $lines[] = '**Project:** ' . $project->name
                 . ' | **Date:** ' . now()->format('M d, Y')
                 . ' | **Model:** ' . $project->model;
        $lines[] = '';
        $lines[] = '---';
        $lines[] = '';

        foreach ($messages as $message) {
            if ($message->role === 'system') continue;

            $label = $message->role === 'user' ? '**You**' : '**EasyAI**';
            $time  = \Carbon\Carbon::parse($message->created_at)->format('H:i');

            $lines[] = $label . ' · ' . $time;
            $lines[] = '';
            $lines[] = $message->content;
            $lines[] = '';
            $lines[] = '---';
            $lines[] = '';
        }

        $markdown = implode("\n", $lines);
        $filename = 'chat-' . $chat->id . '-' . now()->format('Ymd-His') . '.md';

        return response(
            $markdown,
            200,
            [
                'Content-Type'        => 'text/markdown; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }
}