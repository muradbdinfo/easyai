<script setup>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Banknote, CreditCard, Globe, Zap, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
    plan:        Object,
    cod_enabled: { type: Boolean, default: true },
})

function payCod() {
    router.post(route('billing.cod', props.plan.id))
}

function paySsl() {
    router.post(route('billing.sslcommerz', props.plan.id))
}

function payStripe() {
    router.post(route('billing.stripe', props.plan.id))
}

function fmt(n) {
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    if (n >= 1_000)     return (n / 1_000).toFixed(0) + 'K'
    return String(n)
}
</script>

<template>
    <AppLayout title="Select Payment">
        <div class="max-w-lg mx-auto px-6 py-10">

            <!-- Plan summary -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 mb-6">
                <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-3">
                    Selected Plan
                </p>
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-white font-bold text-lg">{{ plan.name }}</h2>
                        <div class="flex items-center gap-1.5 mt-1">
                            <Zap class="w-3.5 h-3.5 text-indigo-400" />
                            <span class="text-indigo-300 text-sm">
                                {{ fmt(plan.monthly_token_limit) }} tokens/month
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-white">${{ plan.price }}</div>
                        <div class="text-slate-400 text-xs">/month</div>
                    </div>
                </div>
            </div>

            <!-- Payment methods -->
            <h3 class="text-white font-semibold text-sm mb-3">Choose Payment Method</h3>

            <div class="space-y-3">

                <!-- COD -->
                <button
                    v-if="cod_enabled"
                    @click="payCod"
                    class="w-full flex items-center gap-4 bg-slate-900 border border-slate-800 hover:border-indigo-500 hover:bg-slate-800 rounded-2xl p-4 text-left transition-all group"
                >
                    <div class="w-10 h-10 bg-amber-500/10 rounded-xl flex items-center justify-center shrink-0">
                        <Banknote class="w-5 h-5 text-amber-400" />
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-semibold text-sm">Cash on Delivery (COD)</p>
                        <p class="text-slate-400 text-xs mt-0.5">
                            Manual payment — Admin approves within 24 hours
                        </p>
                    </div>
                    <ChevronRight class="w-4 h-4 text-slate-600 group-hover:text-slate-400 transition-colors" />
                </button>

                <!-- SSLCommerz -->
                <button
                    @click="paySsl"
                    class="w-full flex items-center gap-4 bg-slate-900 border border-slate-800 hover:border-indigo-500 hover:bg-slate-800 rounded-2xl p-4 text-left transition-all group"
                >
                    <div class="w-10 h-10 bg-green-500/10 rounded-xl flex items-center justify-center shrink-0">
                        <Globe class="w-5 h-5 text-green-400" />
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-semibold text-sm">SSLCommerz</p>
                        <p class="text-slate-400 text-xs mt-0.5">
                            bKash · Nagad · Rocket · Bank Cards (Bangladesh)
                        </p>
                    </div>
                    <ChevronRight class="w-4 h-4 text-slate-600 group-hover:text-slate-400 transition-colors" />
                </button>

                <!-- Stripe -->
                <button
                    @click="payStripe"
                    class="w-full flex items-center gap-4 bg-slate-900 border border-slate-800 hover:border-indigo-500 hover:bg-slate-800 rounded-2xl p-4 text-left transition-all group"
                >
                    <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center shrink-0">
                        <CreditCard class="w-5 h-5 text-indigo-400" />
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-semibold text-sm">Stripe</p>
                        <p class="text-slate-400 text-xs mt-0.5">
                            International Credit / Debit Card
                        </p>
                    </div>
                    <ChevronRight class="w-4 h-4 text-slate-600 group-hover:text-slate-400 transition-colors" />
                </button>

            </div>
        </div>
    </AppLayout>
</template>