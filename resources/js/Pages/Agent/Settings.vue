<script setup>
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Zap, CheckCircle, AlertCircle, Clock, Bot, BarChart2, MessageSquare, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
    recent_runs: { type: Array,  default: () => [] },
    stats:       { type: Object, default: () => ({}) },
})

const statusIcon  = (s) => ({ completed: CheckCircle, failed: AlertCircle, running: Clock, stopped: AlertCircle }[s] ?? Clock)
const statusColor = (s) => ({ completed: 'text-green-400', failed: 'text-red-400', running: 'text-yellow-400', stopped: 'text-slate-400' }[s] ?? 'text-slate-400')
</script>

<template>
    <AppLayout title="Agent AI">
        <div class="max-w-3xl mx-auto py-8 px-4 space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-3">
                <Zap class="w-6 h-6 text-yellow-400" />
                <div>
                    <h1 class="text-xl font-bold text-white">Agent AI</h1>
                    <p class="text-slate-400 text-sm">Autonomous multi-step reasoning with tools.</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div v-for="(item, i) in [
                    { label: 'Total Runs',  value: stats.total_runs  ?? 0 },
                    { label: 'Completed',   value: stats.completed   ?? 0 },
                    { label: 'Failed',      value: stats.failed      ?? 0 },
                    { label: 'Tokens Used', value: (stats.tokens_used ?? 0).toLocaleString() },
                ]" :key="i"
                    class="bg-slate-800 border border-slate-700 rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-white">{{ item.value }}</p>
                    <p class="text-slate-500 text-xs mt-1">{{ item.label }}</p>
                </div>
            </div>

            <!-- How to use -->
            <div class="bg-slate-800 border border-slate-700 rounded-xl p-5 space-y-3">
                <div class="flex items-center gap-2">
                    <Bot class="w-4 h-4 text-indigo-400" />
                    <p class="text-white font-medium text-sm">How to Use Agent AI</p>
                </div>
                <ol class="text-slate-400 text-sm space-y-2 list-decimal list-inside">
                    <li>Open any <strong class="text-slate-300">Project → Chat</strong></li>
                    <li>Click the <strong class="text-slate-300">Agent</strong> tab in the chat panel</li>
                    <li>Enter a goal — e.g. <em class="text-slate-300">"Search for Laravel 11 release notes and summarise"</em></li>
                    <li>Click <strong class="text-slate-300">Run Agent</strong> — it will reason step-by-step using tools</li>
                </ol>
            </div>

            <!-- Available tools -->
            <div class="bg-slate-800 border border-slate-700 rounded-xl p-5 space-y-3">
                <div class="flex items-center gap-2">
                    <Zap class="w-4 h-4 text-yellow-400" />
                    <p class="text-white font-medium text-sm">Available Tools</p>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div v-for="tool in ['Web Search','URL Reader','Knowledge Base Search','Calculator','Date & Time']"
                         :key="tool"
                         class="flex items-center gap-2 text-slate-300 text-sm">
                        <CheckCircle class="w-3.5 h-3.5 text-green-400 shrink-0" /> {{ tool }}
                    </div>
                </div>
            </div>

            <!-- Recent runs -->
            <div class="bg-slate-800 border border-slate-700 rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-700 flex items-center gap-2">
                    <BarChart2 class="w-4 h-4 text-slate-400" />
                    <p class="text-white font-medium text-sm">Recent Agent Runs</p>
                </div>

                <div v-if="!recent_runs.length" class="py-10 text-center text-slate-500 text-sm">
                    No agent runs yet. Start one from any chat.
                </div>

                <div v-else class="divide-y divide-slate-700">
                    <div v-for="run in recent_runs" :key="run.id"
                         class="px-5 py-3 flex items-center gap-3">
                        <component :is="statusIcon(run.status)"
                                   class="w-4 h-4 shrink-0"
                                   :class="statusColor(run.status)" />
                        <div class="flex-1 min-w-0">
                            <p class="text-slate-300 text-sm truncate">{{ run.goal }}</p>
                            <p class="text-slate-500 text-xs mt-0.5">
                                {{ run.chat?.project?.name }} · {{ run.steps_count ?? 0 }} steps · {{ (run.tokens_used ?? 0).toLocaleString() }} tokens
                            </p>
                        </div>
                        <span class="text-xs capitalize px-2 py-0.5 rounded-full"
                              :class="{
                                  'bg-green-900/40 text-green-400': run.status === 'completed',
                                  'bg-red-900/40 text-red-400':     run.status === 'failed',
                                  'bg-yellow-900/40 text-yellow-400': run.status === 'running',
                                  'bg-slate-700 text-slate-400':    run.status === 'stopped',
                              }">
                            {{ run.status }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>