<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatAttachment extends Model
{
    protected $fillable = [
        'chat_id', 'message_id', 'tenant_id', 'user_id',
        'original_name', 'stored_name', 'mime_type', 'extension',
        'type', 'path', 'url', 'extracted_text', 'file_size', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function chat()    { return $this->belongsTo(Chat::class); }
    public function message() { return $this->belongsTo(Message::class); }
    public function tenant()  { return $this->belongsTo(Tenant::class); }
    public function user()    { return $this->belongsTo(User::class); }

    public function isImage(): bool { return $this->type === 'image'; }
    public function isPdf():   bool { return $this->type === 'pdf'; }
    public function isExcel(): bool { return $this->type === 'excel'; }
    public function isText():  bool { return $this->type === 'text'; }

    public function getPublicUrl(): string
    {
        return $this->url ?? asset('storage/' . $this->path);
    }
}