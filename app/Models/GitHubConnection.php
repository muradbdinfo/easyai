<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class GitHubConnection extends Model
{

protected $table = 'github_connections';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'github_user',
        'github_user_id',
        'access_token',
        'scopes',
        'connected_at',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
    ];

    protected $hidden = ['access_token'];

    // Encrypt on write
    public function setAccessTokenAttribute(string $value): void
    {
        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    // Decrypt on read
    public function getAccessTokenAttribute(string $value): string
    {
        return Crypt::decryptString($value);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}