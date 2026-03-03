<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'monthly_token_limit',
        'price',
        'features',
        'is_active',
    ];

    protected $casts = [
        'features'             => 'array',
        'is_active'            => 'boolean',
        'monthly_token_limit'  => 'integer',
        'price'                => 'decimal:2',
    ];

    // ─── Relationships ────────────────────────────────────────────
    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }
}