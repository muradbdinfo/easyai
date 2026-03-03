<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'                => 'Starter',
                'monthly_token_limit' => 500000,
                'price'               => 29.00,
                'features'            => [
                    '500K tokens/month',
                    'Up to 3 projects',
                    'Basic chat history',
                    'Email support',
                ],
                'is_active' => true,
            ],
            [
                'name'                => 'Pro',
                'monthly_token_limit' => 2000000,
                'price'               => 79.00,
                'features'            => [
                    '2M tokens/month',
                    'Unlimited projects',
                    'Full chat history',
                    'Priority support',
                    'Prompt templates',
                    'PDF export',
                ],
                'is_active' => true,
            ],
            [
                'name'                => 'Enterprise',
                'monthly_token_limit' => 10000000,
                'price'               => 199.00,
                'features'            => [
                    '10M tokens/month',
                    'Unlimited everything',
                    'Advanced analytics',
                    'Dedicated support',
                    'Custom AI personas',
                    'API access',
                    'SSO (coming soon)',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}