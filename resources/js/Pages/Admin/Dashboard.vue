<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    Users, Activity, Zap, CreditCard,
    TrendingUp, CheckCircle, AlertCircle, Clock, ChevronRight
} from 'lucide-vue-next'

const props = defineProps({
    stats:          Object,
    recent_tenants: Array,
})

function fmt(n) {
    if (!n && n !== 0) return '0'
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
    return String(n)
}

function statusIcon(status) {
    return status === 'active' ? CheckCircle
         : status === 'suspended' ? AlertCircle
         : Clock
}

function statusColor(status) {
    return status === 'active'    ? 'text-green-500'
         : status === 'suspended' ? 'text-red-500'
         : 'text-amber-500'
}

function statusBg(status) {
    return status === 'active'    ? 'bg-green-50 text-green-700'
         : status === 'suspended' ? 'bg-red-50 text-red-700'
         : 'bg-amber-50 text-amber-700'
}
</script>

<template>
    <AdminLayout title="Dashboard">

        <!-- Stat cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-slate-500 text-xs font-medium">Total Tenants</span>
                    <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center">
                        <Users class="w-4 h-4 text-indigo-600" />
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-800">{{ stats.total_tenants }}</p>
                <p class="text-slate-400 text-xs mt-1">registered workspaces</p>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-slate-500 text-xs font-medium">Active</span>
                    <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center">
                        <Activity class="w-4 h-4 text-green-600" />
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-800">{{ stats.active_tenants }}</p>
                <p class="text-slate-400 text-xs mt-1">{{ stats.trial_tenants }} on trial</p>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-slate-500 text-xs font-medium">Tokens Today</span>
                    <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center">
                        <Zap class="w-4 h-4 text-amber-600" />
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-800">{{ fmt(stats.tokens_today) }}</p>
                <p class="text-slate-400 text-xs mt-1">across all tenants</p>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-slate-500 text-xs font-medium">Revenue (Month)</span>
                    <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                        <CreditCard class="w-4 h-4 text-purple-600" />
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-800">${{ Number(stats.revenue_month).toFixed(2) }}</p>
                <p class="text-slate-400 text-xs mt-1">completed payments</p>
            </div>

        </div>

        <!-- Recent tenants -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <Users class="w-4 h-4 text-slate-500" />
                    <h2 class="text-slate-700 font-semibold text-sm">Recent Signups</h2>
                </div>
                <Link
                    :href="route('admin.tenants.index')"
                    class="text-indigo-600 hover:text-indigo-500 text-xs flex items-center gap-1"
                >
                    View all <ChevronRight class="w-3 h-3" />
                </Link>
            </div>

            <div v-if="recent_tenants.length === 0" class="text-center py-8 text-slate-400 text-sm">
                No tenants yet.
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50">
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Tenant</th>
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Plan</th>
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Status</th>
                        <th class="text-right text-slate-500 font-medium px-5 py-3 text-xs">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="tenant in recent_tenants" :key="tenant.id" class="hover:bg-slate-50">
                        <td class="px-5 py-3">
                            <p class="font-medium text-slate-800">{{ tenant.name }}</p>
                            <p class="text-slate-400 text-xs">{{ tenant.slug }}</p>
                        </td>
                        <td class="px-5 py-3 text-slate-600 text-xs">
                            {{ tenant.plan?.name ?? '—' }}
                        </td>
                        <td class="px-5 py-3">
                            <span
                                class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full capitalize"
                                :class="statusBg(tenant.status)"
                            >
                                <component :is="statusIcon(tenant.status)" class="w-3 h-3" />
                                {{ tenant.status }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <Link
                                :href="route('admin.tenants.show', tenant.id)"
                                class="text-indigo-600 hover:text-indigo-500 text-xs font-medium"
                            >
                                View
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </AdminLayout>
</template>