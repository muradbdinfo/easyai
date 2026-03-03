<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    CreditCard, CheckCircle, Clock, AlertCircle,
    Download, Filter
} from 'lucide-vue-next'

const props = defineProps({
    payments: Object,
    filters:  Object,
})

const method = ref(props.filters?.method ?? '')
const status = ref(props.filters?.status ?? '')

function applyFilters() {
    router.get(route('admin.payments.index'), {
        method: method.value,
        status: status.value,
    }, { preserveState: true, replace: true })
}

function approveCod(payment) {
    if (!confirm('Approve this COD payment?')) return
    router.put(route('admin.payments.approve', payment.id), {}, {
        preserveScroll: true,
    })
}

function statusBg(s) {
    return s === 'completed' ? 'bg-green-50 text-green-700'
         : s === 'pending'   ? 'bg-amber-50 text-amber-700'
         : s === 'failed'    ? 'bg-red-50 text-red-700'
         : 'bg-slate-100 text-slate-600'
}

function statusIcon(s) {
    return s === 'completed' ? CheckCircle
         : s === 'pending'   ? Clock
         : AlertCircle
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric',
    })
}
</script>

<template>
    <AdminLayout title="Payments">

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-2">
                <CreditCard class="w-5 h-5 text-slate-500" />
                <h1 class="text-xl font-bold text-slate-800">Payments</h1>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl border border-slate-200 p-4 mb-5 flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <Filter class="w-4 h-4 text-slate-400" />
                <select v-model="method" @change="applyFilters"
                    class="text-sm border border-slate-200 rounded-lg px-3 py-1.5 outline-none text-slate-700 focus:border-indigo-400">
                    <option value="">All Methods</option>
                    <option value="cod">COD</option>
                    <option value="sslcommerz">SSLCommerz</option>
                    <option value="stripe">Stripe</option>
                </select>
                <select v-model="status" @change="applyFilters"
                    class="text-sm border border-slate-200 rounded-lg px-3 py-1.5 outline-none text-slate-700 focus:border-indigo-400">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div v-if="payments.data.length === 0" class="text-center py-12 text-slate-400 text-sm">
                No payments found.
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50">
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Tenant</th>
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Plan</th>
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Amount</th>
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Method</th>
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Status</th>
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Date</th>
                        <th class="text-right text-slate-500 font-medium px-5 py-3 text-xs">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-slate-50">
                        <td class="px-5 py-3">
                            <p class="font-medium text-slate-800">{{ payment.tenant?.name ?? '—' }}</p>
                        </td>
                        <td class="px-5 py-3 text-slate-600 text-xs">
                            {{ payment.plan?.name ?? '—' }}
                        </td>
                        <td class="px-5 py-3 font-medium text-slate-800">
                            {{ payment.currency }} {{ Number(payment.amount).toFixed(2) }}
                        </td>
                        <td class="px-5 py-3">
                            <span class="bg-slate-100 text-slate-600 text-xs px-2 py-0.5 rounded-full uppercase">
                                {{ payment.method }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full capitalize"
                                :class="statusBg(payment.status)">
                                <component :is="statusIcon(payment.status)" class="w-3 h-3" />
                                {{ payment.status }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-500 text-xs">
                            {{ formatDate(payment.created_at) }}
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <!-- Approve COD -->
                                <button
                                    v-if="payment.method === 'cod' && payment.status === 'pending'"
                                    @click="approveCod(payment)"
                                    class="inline-flex items-center gap-1.5 bg-green-50 hover:bg-green-100 text-green-700 text-xs font-medium px-3 py-1 rounded-lg transition-colors"
                                >
                                    <CheckCircle class="w-3.5 h-3.5" />
                                    Approve
                                </button>
                                <!-- Download invoice (Module 12) -->
                                <button
                                    v-if="payment.invoice_path"
                                    class="text-slate-400 hover:text-indigo-600 transition-colors"
                                    title="Download Invoice"
                                >
                                    <Download class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="payments.last_page > 1" class="flex items-center justify-between px-5 py-3 border-t border-slate-100">
                <span class="text-xs text-slate-400">
                    Showing {{ payments.from }}–{{ payments.to }} of {{ payments.total }}
                </span>
                <div class="flex gap-1">
                    <Link v-if="payments.prev_page_url" :href="payments.prev_page_url"
                        class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50">
                        Prev
                    </Link>
                    <Link v-if="payments.next_page_url" :href="payments.next_page_url"
                        class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50">
                        Next
                    </Link>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>