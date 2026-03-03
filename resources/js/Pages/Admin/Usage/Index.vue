<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { BarChart2, Zap, Calendar, Download, Filter, TrendingUp } from 'lucide-vue-next'

const props = defineProps({
    logs:    Object,
    summary: Object,
    tenants: Array,
    filters: Object,
})

const tenantId = ref(props.filters?.tenant_id ?? '')

function applyFilters() {
    router.get(route('admin.usage.index'), {
        tenant_id: tenantId.value,
    }, { preserveState: true, replace: true })
}

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
    <AdminLayout title="Usage">

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-2">
                <BarChart2 class="w-5 h-5 text-slate-500" />
                <h1 class="text-xl font-bold text-slate-800">Usage Analytics</h1>
            </div>
            <button class="flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-sm px-4 py-2 rounded-lg transition-colors">
                <Download class="w-4 h-4" />
                Export CSV
            </button>
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
                    <Calendar class="w-4 h-4 text-green-500" />
                    <span class="text-slate-500 text-sm">Messages This Month</span>
                </div>
                <p class="text-2xl font-bold text-slate-800">{{ summary.total_messages_month }}</p>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-white rounded-xl border border-slate-200 p-4 mb-5 flex items-center gap-3">
            <Filter class="w-4 h-4 text-slate-400" />
            <select v-model="tenantId" @change="applyFilters"
                class="text-sm border border-slate-200 rounded-lg px-3 py-1.5 outline-none text-slate-700 focus:border-indigo-400">
                <option value="">All Tenants</option>
                <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
        </div>

        <!-- Logs table -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div v-if="logs.data.length === 0" class="text-center py-12 text-slate-400 text-sm">
                No usage data.
            </div>
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50">
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
                        <td class="px-5 py-3 text-slate-700 font-medium text-xs">{{ log.tenant?.name ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <span class="bg-slate-100 text-slate-600 text-xs px-2 py-0.5 rounded-full">
                                {{ log.model }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-500 text-xs text-right">{{ log.prompt_tokens }}</td>
                        <td class="px-5 py-3 text-slate-500 text-xs text-right">{{ log.completion_tokens }}</td>
                        <td class="px-5 py-3 text-slate-800 font-semibold text-xs text-right">{{ log.total_tokens }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="logs.last_page > 1" class="flex items-center justify-between px-5 py-3 border-t border-slate-100">
                <span class="text-xs text-slate-400">
                    Showing {{ logs.from }}–{{ logs.to }} of {{ logs.total }}
                </span>
                <div class="flex gap-1">
                    <Link v-if="logs.prev_page_url" :href="logs.prev_page_url"
                        class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50">Prev</Link>
                    <Link v-if="logs.next_page_url" :href="logs.next_page_url"
                        class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50">Next</Link>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>