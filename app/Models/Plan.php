<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'monthly_token_limit', 'price', 'features', 'is_active',
        'billing_type', 'price_per_seat', 'min_seats', 'max_seats', 'token_limit_per_seat',
    ];

    protected $casts = [
        'features'             => 'array',
        'is_active'            => 'boolean',
        'monthly_token_limit'  => 'integer',
        'price'                => 'decimal:2',
        'price_per_seat'       => 'decimal:2',
        'min_seats'            => 'integer',
        'max_seats'            => 'integer',
        'token_limit_per_seat' => 'integer',
    ];

    public function tenants() { return $this->hasMany(Tenant::class); }

    public function isSeatBased(): bool { return $this->billing_type === 'seat'; }

    public function tokenQuotaForSeats(int $seats = 1): int
    {
        if ($this->isSeatBased() && $this->token_limit_per_seat) {
            return $this->token_limit_per_seat * $seats;
        }
        return (int) $this->monthly_token_limit;
    }

    public function priceForSeats(int $seats = 1): float
    {
        if ($this->isSeatBased() && $this->price_per_seat) {
            return (float) $this->price_per_seat * $seats;
        }
        return (float) $this->price;
    }
}
