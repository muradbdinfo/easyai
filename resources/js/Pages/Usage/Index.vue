<script setup>
import { computed } from 'vue'
import { usePage }  from '@inertiajs/vue3'
import AppLayout    from '@/Layouts/AppLayout.vue'
import { BarChart2, TrendingUp, Zap, Calendar, Download, Activity, Cpu } from 'lucide-vue-next'

const props = defineProps({
    logs:    { type: Array,  default: () => [] },
    daily:   { type: Array,  default: () => [] },
    models:  { type: Array,  default: () => [] },
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
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

// ── SVG chart constants ──────────────────────────────────────
const SLOT   = 16   // px per day slot
const BW     = 13   // bar width
const CH     = 90   // chart area height (SVG units)

const maxTokens   = computed(() => Math.max(...props.daily.map(d => d.tokens),   1))
const maxMessages = computed(() => Math.max(...props.daily.map(d => d.messages), 1))
const svgW        = computed(() => props.daily.length * SLOT || 480)

function barH(val, max) { return Math.max(val > 0 ? (val / max) * CH : 0, val > 0 ? 2 : 0) }
function barX(i)        { return i * SLOT }
function barY(val, max) { return CH - barH(val, max) }

function tokenColor(val) {
    const p = val / maxTokens.value
    if (p >= 0.8) return '#ef4444'
    if (p >= 0.5) return '#f59e0b'
    return '#6366f1'
}

function linePts(key, max) {
    return props.daily.map((d, i) => {
        const x = i * SLOT + BW / 2
        const y = CH - (d[key] / max) * (CH - 10)
        return `${x},${y}`
    }).join(' ')
}

const modelTotal = computed(() => props.models.reduce((s, m) => s + (m.tokens || 0), 0) || 1)
const modelColors = ['#6366f1','#22d3ee','#34d399','#f59e0b','#f87171']
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
                        <p class="text-slate-400 text-sm mt-0.5">Token consumption — last 30 days</p>
                    </div>
                </div>
                <a :href="route('usage.export.csv')"
                   class="flex items-center gap-2 bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    <Download class="w-4 h-4" />
                    Export CSV
                </a>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <Zap class="w-4 h-4 text-amber-400" />
                        <span class="text-slate-400 text-sm">Tokens Today</span>
                    </div>
                    <p class="text-3xl font-bold text-white">{{ fmt(summary.total_tokens_today ?? 0) }}</p>
                </div>
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <TrendingUp class="w-4 h-4 text-green-400" />
                        <span class="text-slate-400 text-sm">Tokens This Month</span>
                    </div>
                    <p class="text-3xl font-bold text-white">{{ fmt(summary.total_tokens_month ?? 0) }}</p>
                    <p class="text-slate-500 text-xs mt-1">{{ quota?.percent ?? 0 }}% of quota</p>
                </div>
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <Activity class="w-4 h-4 text-indigo-400" />
                        <span class="text-slate-400 text-sm">Messages This Month</span>
                    </div>
                    <p class="text-3xl font-bold text-white">{{ fmt(summary.total_messages_month ?? 0) }}</p>
                </div>
            </div>

            <!-- ── Bar Chart: Daily Tokens ── -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <BarChart2 class="w-4 h-4 text-indigo-400" />
                    <h2 class="text-white font-semibold text-sm">Daily Token Usage — Last 30 Days</h2>
                </div>

                <div v-if="daily.length === 0 || maxTokens <= 1"
                     class="flex flex-col items-center justify-center py-10 text-slate-600">
                    <BarChart2 class="w-10 h-10 mb-2 opacity-30" />
                    <p class="text-sm">No token data yet</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <svg :viewBox="`0 0 ${svgW} 110`" :width="svgW" height="110"
                         class="min-w-full" style="display:block">
                        <!-- grid lines -->
                        <line v-for="n in 4" :key="n"
                              x1="0" :x2="svgW"
                              :y1="CH - (n/4)*CH" :y2="CH - (n/4)*CH"
                              stroke="#1e293b" stroke-width="1" />
                        <!-- bars -->
                        <g v-for="(d, i) in daily" :key="d.date">
                            <rect
                                :x="barX(i)"
                                :y="barY(d.tokens, maxTokens)"
                                :width="BW"
                                :height="barH(d.tokens, maxTokens)"
                                :fill="tokenColor(d.tokens)"
                                rx="2"
                                style="opacity:0.85"
                            >
                                <title>{{ d.label }}: {{ fmt(d.tokens) }} tokens</title>
                            </rect>
                        </g>
                        <!-- x-axis labels every 5 days -->
                        <g v-for="(d, i) in daily" :key="'lbl'+i">
                            <text v-if="i % 5 === 0 || i === daily.length - 1"
                                  :x="barX(i) + BW/2"
                                  y="107"
                                  fill="#64748b"
                                  font-size="8"
                                  text-anchor="middle">{{ d.short }}</text>
                        </g>
                    </svg>
                </div>

                <!-- legend -->
                <div class="flex items-center gap-4 mt-3 text-xs text-slate-500">
                    <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm" style="background:#6366f1"></span> Normal</span>
                    <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm" style="background:#f59e0b"></span> High (50%+)</span>
                    <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm" style="background:#ef4444"></span> Critical (80%+)</span>
                </div>
            </div>

            <!-- ── Line Chart: Daily Messages ── -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <TrendingUp class="w-4 h-4 text-green-400" />
                    <h2 class="text-white font-semibold text-sm">Daily Messages — Last 30 Days</h2>
                </div>

                <div v-if="daily.length === 0 || maxMessages <= 1"
                     class="flex flex-col items-center justify-center py-10 text-slate-600">
                    <TrendingUp class="w-10 h-10 mb-2 opacity-30" />
                    <p class="text-sm">No message data yet</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <svg :viewBox="`0 0 ${svgW} 110`" :width="svgW" height="110"
                         class="min-w-full" style="display:block">
                        <!-- grid lines -->
                        <line v-for="n in 4" :key="n"
                              x1="0" :x2="svgW"
                              :y1="CH - (n/4)*CH" :y2="CH - (n/4)*CH"
                              stroke="#1e293b" stroke-width="1" />
                        <!-- filled area -->
                        <polygon
                            :points="`0,${CH} ${linePts('messages', maxMessages)} ${svgW - SLOT + BW/2},${CH}`"
                            fill="#22c55e"
                            style="opacity:0.12"
                        />
                        <!-- line -->
                        <polyline
                            :points="linePts('messages', maxMessages)"
                            fill="none"
                            stroke="#22c55e"
                            stroke-width="2"
                            stroke-linejoin="round"
                            stroke-linecap="round"
                        />
                        <!-- dots -->
                        <circle v-for="(d, i) in daily" :key="'dot'+i"
                                :cx="i * SLOT + BW/2"
                                :cy="CH - (d.messages / maxMessages) * (CH - 10)"
                                r="2.5"
                                fill="#22c55e"
                                style="opacity:0.8"
                        >
                            <title>{{ d.label }}: {{ d.messages }} messages</title>
                        </circle>
                        <!-- x-axis labels -->
                        <g v-for="(d, i) in daily" :key="'ml'+i">
                            <text v-if="i % 5 === 0 || i === daily.length - 1"
                                  :x="i * SLOT + BW/2"
                                  y="107"
                                  fill="#64748b"
                                  font-size="8"
                                  text-anchor="middle">{{ d.short }}</text>
                        </g>
                    </svg>
                </div>
            </div>

            <!-- ── Model Breakdown ── -->
            <div v-if="models.length > 0" class="bg-slate-900 border border-slate-800 rounded-xl p-5 mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <Cpu class="w-4 h-4 text-cyan-400" />
                    <h2 class="text-white font-semibold text-sm">Model Usage — This Month</h2>
                </div>
                <div class="space-y-3">
                    <div v-for="(m, i) in models" :key="m.model">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-slate-300 text-xs font-medium">{{ m.model }}</span>
                            <span class="text-slate-500 text-xs">{{ fmt(m.tokens) }} tokens · {{ m.calls }} calls</span>
                        </div>
                        <div class="h-2 bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all"
                                 :style="{
                                     width: Math.round((m.tokens / modelTotal) * 100) + '%',
                                     background: modelColors[i % modelColors.length]
                                 }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Usage Log Table ── -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                    <h2 class="text-white font-semibold text-sm">Usage Log (last 50)</h2>
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
                                <td class="px-5 py-3 text-slate-400 text-xs">{{ formatDate(log.created_at) }}</td>
                                <td class="px-5 py-3">
                                    <span class="bg-slate-800 text-slate-300 text-xs px-2 py-0.5 rounded-full">{{ log.model }}</span>
                                </td>
                                <td class="px-5 py-3 text-slate-400 text-xs text-right">{{ log.prompt_tokens }}</td>
                                <td class="px-5 py-3 text-slate-400 text-xs text-right">{{ log.completion_tokens }}</td>
                                <td class="px-5 py-3 text-white font-medium text-xs text-right">{{ log.total_tokens }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>