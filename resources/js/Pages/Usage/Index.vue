<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    BarChart2, TrendingUp, Zap, Calendar, Download
} from 'lucide-vue-next'

const props = defineProps({
    logs:    { type: Array,  default: () => [] },
    summary: { type: Object, default: () => ({}) },
})

const quota = computed(() => usePage().props.quota)

function fmt(n) {
    if (!n && n !== 0) return '0'
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
    return String(n)
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    })
}
</script>

<template>
    <AppLayout title="Usage">
        <div class="max-w-5xl mx-auto px-6 py-8">

            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <BarChart2 class="w-6 h-6 text-indigo-400" />
                    <div>
                        <h1 class="text-2xl font-bold text-white">Usage Analytics</h1>
                        <p class="text-slate-400 text-sm mt-0.5">Track your token consumption</p>
                    </div>
                </div>
                <button
                    class="flex items-center gap-2 bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    title="CSV export — Module 13"
                >
                    <Download class="w-4 h-4" />
                    Export CSV
                </button>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">

                <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <Zap class="w-4 h-4 text-amber-400" />
                        <span class="text-slate-400 text-sm">Tokens Used</span>
                    </div>
                    <p class="text-3xl font-bold text-white">{{ fmt(quota?.used ?? 0) }}</p>
                    <p class="text-slate-500 text-xs mt-1">this month</p>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <TrendingUp class="w-4 h-4 text-green-400" />
                        <span class="text-slate-400 text-sm">Remaining</span>
                    </div>
                    <p class="text-3xl font-bold text-white">{{ fmt(quota?.remaining ?? 0) }}</p>
                    <p class="text-slate-500 text-xs mt-1">{{ quota?.percent ?? 0 }}% used</p>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <Calendar class="w-4 h-4 text-indigo-400" />
                        <span class="text-slate-400 text-sm">Reset Date</span>
                    </div>
                    <p class="text-xl font-bold text-white">{{ quota?.reset_date ?? '—' }}</p>
                    <p class="text-slate-500 text-xs mt-1">monthly reset</p>
                </div>

            </div>

            <!-- Usage table -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden mb-6">
                <div class="px-5 py-4 border-b border-slate-800">
                    <h2 class="text-white font-semibold text-sm">Usage Log</h2>
                </div>

                <div v-if="!logs || logs.length === 0" class="text-center py-12">
                    <BarChart2 class="w-10 h-10 text-slate-700 mx-auto mb-3" />
                    <p class="text-slate-500 text-sm">No usage data yet.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-800">
                                <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Date</th>
                                <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Model</th>
                                <th class="text-right text-slate-500 font-medium px-5 py-3 text-xs">Prompt</th>
                                <th class="text-right text-slate-500 font-medium px-5 py-3 text-xs">Completion</th>
                                <th class="text-right text-slate-500 font-medium px-5 py-3 text-xs">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-800/50">
                                <td class="px-5 py-3 text-slate-400 text-xs">
                                    {{ formatDate(log.created_at) }}
                                </td>
                                <td class="px-5 py-3">
                                    <span class="bg-slate-800 text-slate-300 text-xs px-2 py-0.5 rounded-full">
                                        {{ log.model }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-slate-400 text-xs text-right">
                                    {{ log.prompt_tokens }}
                                </td>
                                <td class="px-5 py-3 text-slate-400 text-xs text-right">
                                    {{ log.completion_tokens }}
                                </td>
                                <td class="px-5 py-3 text-white font-medium text-xs text-right">
                                    {{ log.total_tokens }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MIS coming soon -->
            <div class="bg-indigo-600/10 border border-indigo-600/20 rounded-xl p-5 flex items-start gap-3">
                <BarChart2 class="w-5 h-5 text-indigo-400 shrink-0 mt-0.5" />
                <div>
                    <p class="text-indigo-300 font-medium text-sm">Advanced MIS Dashboard — Coming Soon</p>
                    <p class="text-indigo-400/60 text-xs mt-1">
                        Charts, per-project breakdowns, daily trends, and CSV exports will be
                        available in a future update.
                    </p>
                </div>
            </div>

        </div>
    </AppLayout>
</template>