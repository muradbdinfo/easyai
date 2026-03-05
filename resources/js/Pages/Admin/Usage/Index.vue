<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { BarChart2, Zap, Calendar, Download, Filter, TrendingUp, Activity, Users } from 'lucide-vue-next'

const props = defineProps({
    logs:    Object,
    summary: Object,
    tenants: Array,
    filters: Object,
    daily:   { type: Array, default: () => [] },
})

const tenantId = ref(props.filters?.tenant_id ?? '')

function applyFilters() {
    router.get(route('admin.usage.index'), { tenant_id: tenantId.value },
               { preserveState: true, replace: true })
}

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
const SLOT = 16
const BW   = 13
const CH   = 80

const maxTokens = computed(() => Math.max(...props.daily.map(d => d.tokens), 1))
const svgW      = computed(() => props.daily.length * SLOT || 480)

function barH(val) { return Math.max(val > 0 ? (val / maxTokens.value) * CH : 0, val > 0 ? 2 : 0) }
function barX(i)   { return i * SLOT }
function barY(val) { return CH - barH(val) }

// Top tenants for horizontal bar chart
const topTenants    = computed(() => props.summary?.top_tenants ?? [])
const maxTenTokens  = computed(() => Math.max(...topTenants.value.map(t => t.tokens), 1))
</script>

<template>
    <AdminLayout title="Usage">

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-2">
                <BarChart2 class="w-5 h-5 text-slate-500" />
                <h1 class="text-xl font-bold text-slate-800">Usage Analytics</h1>
            </div>
            <a :href="route('admin.usage.export')"
               class="flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-sm px-4 py-2 rounded-lg transition-colors">
                <Download class="w-4 h-4" />
                Export CSV
            </a>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center gap-2 mb-3">
                    <Zap class="w-4 h-4 text-amber-500" />
                    <span class="text-slate-500 text-sm">Tokens Today</span>
                </div>
                <p class="text-2xl font-bold text-slate-800">{{ fmt(summary.total_tokens_today) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center gap-2 mb-3">
                    <TrendingUp class="w-4 h-4 text-indigo-500" />
                    <span class="text-slate-500 text-sm">Tokens This Month</span>
                </div>
                <p class="text-2xl font-bold text-slate-800">{{ fmt(summary.total_tokens_month) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center gap-2 mb-3">
                    <Activity class="w-4 h-4 text-green-500" />
                    <span class="text-slate-500 text-sm">Messages This Month</span>
                </div>
                <p class="text-2xl font-bold text-slate-800">{{ fmt(summary.total_messages_month) }}</p>
            </div>
        </div>

        <!-- ── Platform Bar Chart: Daily Tokens ── -->
        <div class="bg-white border border-slate-200 rounded-xl p-5 mb-6">
            <div class="flex items-center gap-2 mb-4">
                <BarChart2 class="w-4 h-4 text-indigo-500" />
                <h2 class="text-slate-700 font-semibold text-sm">Platform Token Usage — Last 30 Days</h2>
            </div>

            <div v-if="!daily || daily.length === 0 || maxTokens <= 1"
                 class="flex flex-col items-center justify-center py-10 text-slate-300">
                <BarChart2 class="w-10 h-10 mb-2" />
                <p class="text-sm">No data yet</p>
            </div>

            <div v-else class="overflow-x-auto">
                <svg :viewBox="`0 0 ${svgW} 100`" :width="svgW" height="100"
                     class="min-w-full" style="display:block">
                    <!-- grid -->
                    <line v-for="n in 4" :key="n"
                          x1="0" :x2="svgW"
                          :y1="CH - (n/4)*CH" :y2="CH - (n/4)*CH"
                          stroke="#f1f5f9" stroke-width="1" />
                    <!-- bars -->
                    <g v-for="(d, i) in daily" :key="d.date">
                        <rect :x="barX(i)" :y="barY(d.tokens)"
                              :width="BW" :height="barH(d.tokens)"
                              fill="#6366f1" rx="2" style="opacity:0.8">
                            <title>{{ d.label }}: {{ fmt(d.tokens) }} tokens</title>
                        </rect>
                    </g>
                    <!-- labels -->
                    <g v-for="(d, i) in daily" :key="'l'+i">
                        <text v-if="i % 5 === 0 || i === daily.length - 1"
                              :x="barX(i) + BW/2" y="96"
                              fill="#94a3b8" font-size="8" text-anchor="middle">
                            {{ d.short }}
                        </text>
                    </g>
                </svg>
            </div>
        </div>

        <!-- ── Top 5 Tenants Horizontal Bar Chart ── -->
        <div v-if="topTenants.length > 0" class="bg-white border border-slate-200 rounded-xl p-5 mb-6">
            <div class="flex items-center gap-2 mb-4">
                <Users class="w-4 h-4 text-indigo-500" />
                <h2 class="text-slate-700 font-semibold text-sm">Top Tenants by Token Usage — This Month</h2>
            </div>
            <div class="space-y-3">
                <div v-for="(t, i) in topTenants" :key="t.tenant_id">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-slate-700 text-xs font-medium">{{ t.tenant?.name ?? 'Unknown' }}</span>
                        <span class="text-slate-400 text-xs">{{ fmt(t.tokens) }}</span>
                    </div>
                    <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-500 rounded-full transition-all"
                             :style="{ width: Math.round((t.tokens / maxTenTokens) * 100) + '%' }"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white border border-slate-200 rounded-xl p-4 mb-5 flex items-center gap-3">
            <Filter class="w-4 h-4 text-slate-400" />
            <select v-model="tenantId"
                    class="flex-1 border-0 text-slate-700 text-sm focus:ring-0 focus:outline-none bg-transparent">
                <option value="">All Tenants</option>
                <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
            <button @click="applyFilters"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-1.5 rounded-lg transition-colors">
                Apply
            </button>
        </div>

        <!-- Log Table -->
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-slate-700 font-semibold text-sm">Usage Log</h2>
            </div>

            <div v-if="!logs.data || logs.data.length === 0"
                 class="text-center py-12 text-slate-400 text-sm">
                <BarChart2 class="w-10 h-10 mx-auto mb-3 text-slate-200" />
                No usage data found.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Date</th>
                            <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Tenant</th>
                            <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Model</th>
                            <th class="text-right text-slate-500 font-medium px-5 py-3 text-xs">Prompt</th>
                            <th class="text-right text-slate-500 font-medium px-5 py-3 text-xs">Completion</th>
                            <th class="text-right text-slate-500 font-medium px-5 py-3 text-xs">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-500 text-xs">{{ formatDate(log.created_at) }}</td>
                            <td class="px-5 py-3 text-slate-700 text-xs font-medium">{{ log.tenant?.name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <span class="bg-slate-100 text-slate-600 text-xs px-2 py-0.5 rounded-full">{{ log.model }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-500 text-xs text-right">{{ log.prompt_tokens }}</td>
                            <td class="px-5 py-3 text-slate-500 text-xs text-right">{{ log.completion_tokens }}</td>
                            <td class="px-5 py-3 text-slate-800 font-semibold text-xs text-right">{{ log.total_tokens }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="logs.last_page > 1" class="px-5 py-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-slate-400 text-xs">
                    Showing {{ logs.from }}–{{ logs.to }} of {{ logs.total }}
                </span>
                <div class="flex gap-2">
                    <a v-if="logs.prev_page_url" :href="logs.prev_page_url"
                       class="text-xs text-indigo-600 hover:underline">← Prev</a>
                    <a v-if="logs.next_page_url" :href="logs.next_page_url"
                       class="text-xs text-indigo-600 hover:underline">Next →</a>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>