<?php

namespace Database\Seeders;

use App\Models\Addon;
use Illuminate\Database\Seeder;

class AddonSeeder extends Seeder
{
    public function run(): void
    {
        Addon::updateOrCreate(
            ['slug' => 'agent-ai'],
            [
                'name'          => 'Agent AI',
                'description'   => 'Autonomous AI agent that can search the web, read URLs, and perform multi-step reasoning to complete complex tasks.',
                'price'         => 29.00,
                'currency'      => 'USD',
                'billing_cycle' => 'monthly',
                'features'      => [
                    'Autonomous multi-step reasoning',
                    'Web search tool',
                    'URL reader tool',
                    'Knowledge base search tool',
                    'Calculator tool',
                    'Up to 10 steps per run',
                    'Full step-by-step logs',
                ],
                'is_active'  => true,
                'sort_order' => 1,
            ]
        );
    }
}