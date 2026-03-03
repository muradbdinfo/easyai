<script setup>
import { ref } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    Users, Zap, CreditCard, BarChart2,
    CheckCircle, AlertCircle, Edit2, RefreshCw,
    MessageSquare, ChevronRight, Shield, Clock
} from 'lucide-vue-next'

const props = defineProps({
    tenant:     Object,
    plans:      Array,
    usage_logs: Array,
})

// ── Change plan ────────────────────────────────────────────────────
const planForm = useForm({ plan_id: props.tenant.plan_id })

function changePlan() {
    planForm.put(route('admin.tenants.plan', props.tenant.id), {
        preserveScroll: true,
    })
}

// ── Change status ──────────────────────────────────────────────────
function toggleStatus() {
    const newStatus = props.tenant.status === 'active' ? 'suspended' : 'active'
    if (!confirm(`Set tenant to "${newStatus}"?`)) return
    router.put(route('admin.tenants.status', props.tenant.id), {
        status: newStatus,
    }, { preserveScroll: true })
}

function usagePercent() {
    if (!props.tenant.token_quota || props.tenant.token_quota === 0) return 0
    return Math.min(100, Math.round((props.tenant.tokens_used / props.tenant.token_quota) * 100))
}

function fmt(n) {
    if (!n && n !== 0) return '0'
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
    return String(n)
}

function statusBg(s) {
    return s === 'active'    ? 'bg-green-50 text-green-700'
         : s === 'suspended' ? 'bg-red-50 text-red-700'
         : 'bg-amber-50 text-amber-700'
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    })
}
</script>

<template>
    <AdminLayout :title="tenant.name">

        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-6">
            <Link :href="route('admin.tenants.index')" class="hover:text-slate-700 flex items-center gap-1">
                <Users class="w-4 h-4" />
                Tenants
            </Link>
            <ChevronRight class="w-4 h-4" />
            <span class="text-slate-700 font-medium">{{ tenant.name }}</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left column -->
            <div class="space-y-5">

                <!-- Tenant info -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-slate-700 font-semibold text-sm">Tenant Info</h2>
                        <span
                            class="text-xs font-medium px-2 py-0.5 rounded-full capitalize"
                            :class="statusBg(tenant.status)"
                        >
                            {{ tenant.status }}
                        </span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Name</span>
                            <span class="text-slate-800 font-medium">{{ tenant.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Slug</span>
                            <span class="text-slate-600">{{ tenant.slug }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Plan</span>
                            <span class="text-slate-800 font-medium">{{ tenant.plan?.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Users</span>
                            <span class="text-slate-800">{{ tenant.users?.length ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- Token usage -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <Zap class="w-4 h-4 text-amber-500" />
                        <h2 class="text-slate-700 font-semibold text-sm">Token Usage</h2>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500 mb-2">
                        <span>{{ fmt(tenant.tokens_used) }} used</span>
                        <span>{{ usagePercent() }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 mb-2">
                        <div
                            class="h-2 rounded-full transition-all"
                            :class="usagePercent() >= 90 ? 'bg-red-500' : usagePercent() >= 70 ? 'bg-amber-500' : 'bg-indigo-500'"
                            :style="{ width: usagePercent() + '%' }"
                        />
                    </div>
                    <p class="text-xs text-slate-400">
                        {{ fmt(tenant.token_quota) }} total quota
                    </p>
                </div>

                <!-- Change plan -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <Edit2 class="w-4 h-4 text-slate-500" />
                        <h2 class="text-slate-700 font-semibold text-sm">Change Plan</h2>
                    </div>
                    <select
                        v-model="planForm.plan_id"
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 text-slate-700 outline-none focus:border-indigo-400 mb-3"
                    >
                        <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                            {{ plan.name }} — ${{ plan.price }}/mo
                        </option>
                    </select>
                    <button
                        @click="changePlan"
                        :disabled="planForm.processing"
                        class="w-full bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-sm px-4 py-2 rounded-lg transition-colors"
                    >
                        {{ planForm.processing ? 'Saving...' : 'Update Plan' }}
                    </button>
                </div>

                <!-- Suspend / Activate -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <Shield class="w-4 h-4 text-slate-500" />
                        <h2 class="text-slate-700 font-semibold text-sm">Account Status</h2>
                    </div>
                    <button
                        @click="toggleStatus"
                        class="w-full flex items-center justify-center gap-2 text-sm px-4 py-2 rounded-lg transition-colors font-medium"
                        :class="tenant.status === 'active'
                            ? 'bg-red-50 hover:bg-red-100 text-red-600 border border-red-200'
                            : 'bg-green-50 hover:bg-green-100 text-green-600 border border-green-200'"
                    >
                        <component
                            :is="tenant.status === 'active' ? AlertCircle : CheckCircle"
                            class="w-4 h-4"
                        />
                        {{ tenant.status === 'active' ? 'Suspend Tenant' : 'Activate Tenant' }}
                    </button>
                </div>

            </div>

            <!-- Right column -->
            <div class="lg:col-span-2 space-y-5">

                <!-- Users -->
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-100">
                        <Users class="w-4 h-4 text-slate-500" />
                        <h2 class="text-slate-700 font-semibold text-sm">Users</h2>
                    </div>
                    <div v-if="!tenant.users || tenant.users.length === 0"
                         class="text-center py-6 text-slate-400 text-sm">
                        No users.
                    </div>
                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="text-left text-slate-500 font-medium px-5 py-2 text-xs">Name</th>
                                <th class="text-left text-slate-500 font-medium px-5 py-2 text-xs">Email</th>
                                <th class="text-left text-slate-500 font-medium px-5 py-2 text-xs">Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="user in tenant.users" :key="user.id" class="hover:bg-slate-50">
                                <td class="px-5 py-2.5 text-slate-800 font-medium">{{ user.name }}</td>
                                <td class="px-5 py-2.5 text-slate-500 text-xs">{{ user.email }}</td>
                                <td class="px-5 py-2.5">
                                    <span class="bg-slate-100 text-slate-600 text-xs px-2 py-0.5 rounded-full capitalize">
                                        {{ user.role }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Usage logs -->
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-100">
                        <BarChart2 class="w-4 h-4 text-slate-500" />
                        <h2 class="text-slate-700 font-semibold text-sm">Recent Usage</h2>
                    </div>
                    <div v-if="usage_logs.length === 0"
                         class="text-center py-6 text-slate-400 text-sm">
                        No usage data.
                    </div>
                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="text-left text-slate-500 font-medium px-5 py-2 text-xs">Date</th>
                                <th class="text-left text-slate-500 font-medium px-5 py-2 text-xs">Model</th>
                                <th class="text-right text-slate-500 font-medium px-5 py-2 text-xs">Tokens</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="log in usage_logs" :key="log.id" class="hover:bg-slate-50">
                                <td class="px-5 py-2.5 text-slate-500 text-xs">
                                    {{ formatDate(log.created_at) }}
                                </td>
                                <td class="px-5 py-2.5">
                                    <span class="bg-slate-100 text-slate-600 text-xs px-2 py-0.5 rounded-full">
                                        {{ log.model }}
                                    </span>
                                </td>
                                <td class="px-5 py-2.5 text-slate-800 font-medium text-xs text-right">
                                    {{ log.total_tokens }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </AdminLayout>
</template>