<?php
namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // ── Flat plans ────────────────────────────────────────
            [
                'name' => 'Starter', 'billing_type' => 'flat',
                'monthly_token_limit' => 500_000, 'price' => 29.00,
                'features' => ['500K tokens/month','Up to 3 projects','Basic chat history','Email support'],
                'is_active' => true,
            ],
            [
                'name' => 'Pro', 'billing_type' => 'flat',
                'monthly_token_limit' => 2_000_000, 'price' => 79.00,
                'features' => ['2M tokens/month','Unlimited projects','Full chat history','Priority support','Prompt templates','PDF export'],
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise Flat', 'billing_type' => 'flat',
                'monthly_token_limit' => 10_000_000, 'price' => 199.00,
                'features' => ['10M tokens/month','Unlimited everything','Advanced analytics','Dedicated support','Custom AI personas','API access'],
                'is_active' => true,
            ],

            // ── Seat plans (monthly_token_limit = 0, computed from seats) ──
            [
                'name' => 'Team Standard', 'billing_type' => 'seat',
                'monthly_token_limit' => 0, 'price' => 0,
                'price_per_seat' => 20.00, 'min_seats' => 5, 'max_seats' => 150,
                'token_limit_per_seat' => 500_000,
                'features' => ['500K tokens/seat/month (shared pool)','Predictable per-seat billing','Central billing & administration','SSO and domain capture','Team member management','Priority support'],
                'is_active' => true,
            ],
            [
                'name' => 'Team Premium', 'billing_type' => 'seat',
                'monthly_token_limit' => 0, 'price' => 0,
                'price_per_seat' => 100.00, 'min_seats' => 5, 'max_seats' => 150,
                'token_limit_per_seat' => 2_500_000,
                'features' => ['2.5M tokens/seat/month (shared pool)','5× more usage than Standard','All Team Standard features','Advanced analytics & MIS','Custom AI personas','API access'],
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise', 'billing_type' => 'seat',
                'monthly_token_limit' => 0, 'price' => 0,
                'price_per_seat' => 20.00, 'min_seats' => 20, 'max_seats' => null,
                'token_limit_per_seat' => 5_000_000,
                'features' => ['5M tokens/seat/month (shared pool)','Flexible pooled usage','Role-based fine-grained permissions','Audit logs','Network-level access control','Custom data retention','IP allowlisting','Dedicated support'],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}