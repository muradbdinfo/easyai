<?php
namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatAttachment;
use App\Models\Project;
use App\Services\FileExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    public function __construct(private FileExtractorService $extractor) {}

    public function store(Request $request, Project $project, Chat $chat)
    {
        $tenant = app('tenant');
        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->tenant_id   !== $tenant->id, 403);
        abort_if($chat->isClosed(), 422, 'Chat is closed.');

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB max
                'mimes:jpg,jpeg,png,gif,webp,txt,pdf,xls,xlsx',
            ],
        ]);

        $file      = $request->file('file');
        $ext       = strtolower($file->getClientOriginalExtension());
        $extracted = $this->extractor->extract($file);

        // Store file: storage/app/public/attachments/{tenant_id}/{chat_id}/
        $folder     = "attachments/{$tenant->id}/{$chat->id}";
        $storedName = Str::uuid() . '.' . $ext;
        $path       = $file->storeAs($folder, $storedName, 'public');

        // Public URL only for images
        $url = in_array($extracted['type'], ['image'])
            ? asset('storage/' . $path)
            : null;

        $attachment = ChatAttachment::create([
            'chat_id'        => $chat->id,
            'message_id'     => null, // linked after message saved
            'tenant_id'      => $tenant->id,
            'user_id'        => $request->user()->id,
            'original_name'  => $file->getClientOriginalName(),
            'stored_name'    => $storedName,
            'mime_type'      => $file->getMimeType(),
            'extension'      => $ext,
            'type'           => $extracted['type'],
            'path'           => $path,
            'url'            => $url,
            'extracted_text' => $extracted['extracted_text'],
            'file_size'      => $file->getSize(),
            'meta'           => $extracted['meta'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'attachment_id' => $attachment->id,
                'type'          => $attachment->type,
                'original_name' => $attachment->original_name,
                'url'           => $attachment->getPublicUrl(),
                'file_size'     => $attachment->file_size,
                'meta'          => $attachment->meta,
                // Let Vue know if text was extracted (show preview)
                'has_text'      => !empty($attachment->extracted_text),
                'text_preview'  => $attachment->extracted_text
                    ? mb_substr($attachment->extracted_text, 0, 200) . '...'
                    : null,
            ],
        ]);
    }

    public function destroy(Request $request, ChatAttachment $attachment)
    {
        $tenant = app('tenant');
        abort_if($attachment->tenant_id !== $tenant->id, 403);
        abort_if($attachment->message_id !== null, 422, 'Cannot delete sent attachment.');

        \Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return response()->json(['success' => true]);
    }
}