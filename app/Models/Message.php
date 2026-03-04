<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'tenant_id',
        'role',
        'content',
        'tokens',
        'model','attachment_id', 'has_attachment'
    ];

    protected $casts = [
        'tokens' => 'integer',
        'has_attachment' => 'boolean'
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }


    public function attachment()
  {
      return $this->belongsTo(ChatAttachment::class, 'attachment_id');
  }
  
}