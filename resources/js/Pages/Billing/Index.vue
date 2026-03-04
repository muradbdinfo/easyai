<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    CreditCard, Zap, Calendar, Download,
    CheckCircle, Clock, AlertCircle,
    ArrowRight, Receipt, Banknote, Globe
} from 'lucide-vue-next'

const props = defineProps({
    payments:     Object,
    subscription: { type: Object, default: null },
    current_plan: { type: Object, default: null },
})

function fmt(n) {
    if (!n) return '0'
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    if (n >= 1_000)     return (n / 1_000).toFixed(0) + 'K'
    return String(n)
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric',
    })
}

function methodIcon(method) {
    return method === 'cod'        ? Banknote
         : method === 'sslcommerz' ? Globe
         : CreditCard
}

function methodColor(method) {
    return method === 'cod'        ? 'text-amber-400'
         : method === 'sslcommerz' ? 'text-green-400'
         : 'text-indigo-400'
}
</script>

<template>
    <AppLayout title="Billing">
        <div class="max-w-4xl mx-auto px-6 py-8">

            <div class="flex items-center gap-3 mb-8">
                <CreditCard class="w-6 h-6 text-indigo-400" />
                <h1 class="text-2xl font-bold text-white">Billing</h1>
            </div>

            <!-- Current plan card -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">
                            Current Plan
                        </p>
                        <h2 class="text-white text-xl font-bold">
                            {{ current_plan?.name ?? 'No Plan' }}
                        </h2>
                        <div class="flex items-center gap-1.5 mt-1">
                            <Zap class="w-3.5 h-3.5 text-indigo-400" />
                            <span class="text-indigo-300 text-sm">
                                {{ fmt(current_plan?.monthly_token_limit ?? 0) }} tokens/month
                            </span>
                        </div>
                        <div v-if="subscription" class="flex items-center gap-1.5 mt-2">
                            <Calendar class="w-3.5 h-3.5 text-slate-500" />
                            <span class="text-slate-400 text-xs">
                                Renews {{ formatDate(subscription.ends_at) }}
                            </span>
                        </div>
                    </div>
                    <Link
                        :href="route('billing.plans')"
                        class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors shrink-0"
                    >
                        Change Plan
                        <ArrowRight class="w-4 h-4" />
                    </Link>
                </div>
            </div>

            <!-- Payment history -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
                <div class="flex items-center gap-2 px-6 py-4 border-b border-slate-800">
                    <Receipt class="w-4 h-4 text-slate-400" />
                    <h3 class="text-white font-semibold text-sm">Payment History</h3>
                </div>

                <!-- Empty state -->
                <div v-if="!payments?.data?.length" class="py-12 text-center">
                    <Receipt class="w-8 h-8 text-slate-700 mx-auto mb-3" />
                    <p class="text-slate-500 text-sm">No payments yet.</p>
                    <Link :href="route('billing.plans')"
                          class="text-indigo-400 hover:text-indigo-300 text-sm mt-2 inline-block">
                        View plans
                    </Link>
                </div>

                <!-- Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-slate-500 text-xs uppercase tracking-wider border-b border-slate-800">
                                <th class="text-left px-6 py-3">Invoice</th>
                                <th class="text-left px-6 py-3">Plan</th>
                                <th class="text-left px-6 py-3">Method</th>
                                <th class="text-left px-6 py-3">Amount</th>
                                <th class="text-left px-6 py-3">Status</th>
                                <th class="text-left px-6 py-3">Date</th>
                                <th class="text-left px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            <tr
                                v-for="payment in payments.data"
                                :key="payment.id"
                                class="hover:bg-slate-800/50 transition-colors"
                            >
                                <td class="px-6 py-3 text-slate-300 font-mono text-xs">
                                    {{ payment.invoice_number ?? '#' + payment.id }}
                                </td>
                                <td class="px-6 py-3 text-slate-300">
                                    {{ payment.plan?.name }}
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <component
                                            :is="methodIcon(payment.method)"
                                            class="w-3.5 h-3.5"
                                            :class="methodColor(payment.method)"
                                        />
                                        <span class="text-slate-400 text-xs capitalize">
                                            {{ payment.method }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-white font-semibold">
                                    {{ payment.currency }} {{ payment.amount }}
                                </td>
                                <td class="px-6 py-3">
                                    <span
                                        class="flex items-center gap-1 w-fit px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-green-500/10 text-green-400':  payment.status === 'completed',
                                            'bg-amber-500/10 text-amber-400':  payment.status === 'pending',
                                            'bg-red-500/10   text-red-400':    payment.status === 'failed',
                                        }"
                                    >
                                        <CheckCircle v-if="payment.status === 'completed'" class="w-3 h-3" />
                                        <Clock       v-else-if="payment.status === 'pending'" class="w-3 h-3" />
                                        <AlertCircle v-else class="w-3 h-3" />
                                        {{ payment.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-slate-400 text-xs">
                                    {{ formatDate(payment.created_at) }}
                                </td>
                                <td class="px-6 py-3">
                                    <a
                                        v-if="payment.invoice_path"
                                        :href="route('billing.invoice.download', payment.id)"
                                        class="flex items-center gap-1 text-indigo-400 hover:text-indigo-300 text-xs transition-colors"
                                    >
                                        <Download class="w-3.5 h-3.5" />
                                        PDF
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>