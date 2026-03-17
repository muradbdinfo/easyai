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
                'description'   => 'Autonomous AI agent — web search, URL reader, multi-step reasoning.',
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
                'description'   => 'Connect EasyAI to n8n workflows — fire webhooks on chat events.',
                'price'         => 19.00,
                'currency'      => 'USD',
                'billing_cycle' => 'monthly',
                'features'      => [
                    'Webhook on new chat',
                    'Webhook on message sent',
                    'Webhook on assistant replied',
                    'HMAC signed payloads',
                    'Full webhook logs',
                ],
                'is_active'  => true,
                'sort_order' => 2,
            ]
        );

        Addon::updateOrCreate(
            ['slug' => 'openclaw'],
            [
                'name'          => 'OpenClaw Agent',
                'description'   => 'Self-hosted AI agent with MCP tools — web search, file access, shell commands.',
                'price'         => 0.00,
                'currency'      => 'USD',
                'billing_cycle' => 'monthly',
                'features'      => [
                    'MCP tool support',
                    'Web search',
                    'File access',
                    'Shell commands',
                    'OpenAI-compatible API',
                ],
                'is_active'  => true,
                'sort_order' => 3,
            ]
        );

        $this->command->info('Addons seeded: agent-ai, n8n-automation, openclaw');
    }
}