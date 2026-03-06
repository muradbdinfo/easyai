<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Addon extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price', 'currency',
        'billing_cycle', 'features', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'features'  => 'array',
        'is_active' => 'boolean',
        'price'     => 'decimal:2',
    ];

    public function tenantAddons(): HasMany
    {
        return $this->hasMany(TenantAddon::class);
    }

    public function activeTenants(): HasMany
    {
        return $this->hasMany(TenantAddon::class)->where('status', 'active');
    }
}