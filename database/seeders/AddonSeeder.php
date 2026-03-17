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

        Addon::updateOrCreate(
            ['slug' => 'n8n-automation'],
            [
                'name'          => 'n8n Automation',
                'description'   => 'Connect EasyAI to your n8n server. Fire webhooks on AI events and receive callbacks back into chats for full workflow automation.',
                'price'         => 19.00,
                'currency'      => 'USD',
                'billing_cycle' => 'monthly',
                'features'      => [
                    'Fire webhooks on message sent',
                    'Fire webhooks on AI reply received',
                    'Fire webhooks on payment completed',
                    'Fire webhooks on new tenant registered',
                    'Receive n8n callbacks back into any chat',
                    'Per-tenant webhook URL configuration',
                    'Full webhook delivery logs',
                    'HMAC signature verification',
                ],
                'is_active'  => true,
                'sort_order' => 2,
            ]
        );
    }
}
