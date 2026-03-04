<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatAttachment;
use App\Models\Project;
use App\Services\FileExtractorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    public function __construct(private FileExtractorService $extractor) {}

    /**
     * Upload a file attached to a pending chat message.
     * Returns attachment_id — Vue holds it until the user sends the message.
     *
     * POST /projects/{project}/chats/{chat}/upload
     */
    public function store(Request $request, Project $project, Chat $chat): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->tenant_id    !== $tenant->id, 403);

        if ($chat->isClosed()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat is closed.',
            ], 422);
        }

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',   // 10 MB
                'mimes:jpg,jpeg,png,gif,webp,txt,pdf,xls,xlsx',
            ],
        ]);

        $file      = $request->file('file');
        $ext       = strtolower($file->getClientOriginalExtension());
        $extracted = $this->extractor->extract($file);

        // Store under: storage/app/public/attachments/{tenant_id}/{chat_id}/
        $folder     = "attachments/{$tenant->id}/{$chat->id}";
        $storedName = Str::uuid() . '.' . $ext;
        $path       = $file->storeAs($folder, $storedName, 'public');

        // Public URL only for images (PDF/TXT/Excel are not served directly)
        $url = $extracted['type'] === 'image'
            ? asset('storage/' . $path)
            : null;

        $attachment = ChatAttachment::create([
            'chat_id'        => $chat->id,
            'message_id'     => null,   // linked when message is saved
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
            'data'    => [
                'attachment_id' => $attachment->id,
                'type'          => $attachment->type,
                'original_name' => $attachment->original_name,
                'extension'     => $attachment->extension,
                'file_size'     => $attachment->file_size,
                'url'           => $attachment->getPublicUrl(),
                'has_text'      => !empty($attachment->extracted_text),
                'text_preview'  => $attachment->extracted_text
                    ? mb_substr($attachment->extracted_text, 0, 200) . '…'
                    : null,
                'meta'          => $attachment->meta,
            ],
        ]);
    }

    /**
     * Delete a pending (unsent) attachment.
     *
     * DELETE /attachments/{attachment}
     */
    public function destroy(Request $request, ChatAttachment $attachment): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($attachment->tenant_id !== $tenant->id, 403);

        if ($attachment->message_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete an attachment that has already been sent.',
            ], 422);
        }

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return response()->json(['success' => true]);
    }
}