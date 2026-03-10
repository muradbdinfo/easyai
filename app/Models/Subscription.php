<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'plan_id', 'payment_id',
        'seats', 'token_quota',
        'starts_at', 'ends_at', 'status',
    ];

    protected $casts = [
        'starts_at'   => 'datetime',
        'ends_at'     => 'datetime',
        'seats'       => 'integer',
        'token_quota' => 'integer',
    ];

    public function tenant()  { return $this->belongsTo(Tenant::class); }
    public function plan()    { return $this->belongsTo(Plan::class); }
    public function payment() { return $this->belongsTo(Payment::class); }
    public function isActive(): bool { return $this->status === 'active'; }
}
