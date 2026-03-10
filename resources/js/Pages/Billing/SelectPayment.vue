<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Banknote, CreditCard, Globe, Zap, ChevronRight, Users, Minus, Plus } from 'lucide-vue-next'

const props = defineProps({
    plan:        Object,
    cod_enabled: { type: Boolean, default: true },
})

const isSeat = computed(() => props.plan.billing_type === 'seat')
const seats  = ref(props.plan.min_seats ?? 1)

function clamp(v) {
    let n = Math.max(parseInt(v) || props.plan.min_seats, props.plan.min_seats)
    if (props.plan.max_seats) n = Math.min(n, props.plan.max_seats)
    seats.value = n
}

const totalAmount = computed(() =>
    isSeat.value ? (props.plan.price_per_seat * seats.value).toFixed(2) : Number(props.plan.price).toFixed(2)
)
const totalTokens = computed(() =>
    isSeat.value ? props.plan.token_limit_per_seat * seats.value : props.plan.monthly_token_limit
)

function fmt(n) {
    if (!n) return '—'
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    if (n >= 1_000)     return (n / 1_000).toFixed(0) + 'K'
    return String(n)
}

function pay(routeName) {
    const data = isSeat.value ? { seats: seats.value } : {}
    router.post(route(routeName, props.plan.id), data)
}
</script>

<template>
    <AppLayout title="Select Payment">
        <div class="max-w-lg mx-auto px-6 py-10">

            <!-- Plan summary -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 mb-6">
                <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-3">Selected Plan</p>

                <!-- Seat selector — only for seat plans -->
                <div v-if="isSeat" class="mb-4">
                    <label class="block text-slate-400 text-xs mb-2 flex items-center gap-1.5">
                        <Users class="w-3.5 h-3.5" /> Number of seats
                    </label>
                    <div class="flex items-center gap-3">
                        <button @click="clamp(seats - 1)"
                                class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white flex items-center justify-center">
                            <Minus class="w-3.5 h-3.5" />
                        </button>
                        <input type="number" :value="seats" @change="e => clamp(e.target.value)"
                               :min="plan.min_seats" :max="plan.max_seats || 9999"
                               class="w-16 text-center bg-slate-800 border border-slate-700 text-white rounded-lg py-1.5 text-sm outline-none focus:border-indigo-500" />
                        <button @click="clamp(seats + 1)"
                                class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white flex items-center justify-center">
                            <Plus class="w-3.5 h-3.5" />
                        </button>
                        <span class="text-slate-500 text-xs">min {{ plan.min_seats }}{{ plan.max_seats ? ` · max ${plan.max_seats}` : '' }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-white font-bold text-lg">{{ plan.name }}</h2>
                        <div class="flex items-center gap-1.5 mt-1">
                            <Zap class="w-3.5 h-3.5 text-indigo-400" />
                            <span class="text-indigo-300 text-sm">{{ fmt(totalTokens) }} tokens/month</span>
                        </div>
                        <p v-if="isSeat" class="text-slate-500 text-xs mt-0.5">
                            {{ fmt(plan.token_limit_per_seat) }} × {{ seats }} seats (shared pool)
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-white">${{ totalAmount }}</div>
                        <div class="text-slate-400 text-xs">/month</div>
                        <div v-if="isSeat" class="text-slate-500 text-xs">${{ Number(plan.price_per_seat).toFixed(0) }}/seat</div>
                    </div>
                </div>
            </div>

            <!-- Payment methods -->
            <h3 class="text-white font-semibold text-sm mb-3">Choose Payment Method</h3>

            <div class="space-y-3">
                <!-- COD -->
                <button v-if="cod_enabled" @click="pay('billing.cod')"
                        class="w-full flex items-center gap-4 bg-slate-900 border border-slate-800 hover:border-indigo-500 hover:bg-slate-800 rounded-2xl p-4 text-left transition-all group">
                    <div class="w-10 h-10 bg-amber-500/10 rounded-xl flex items-center justify-center shrink-0">
                        <Banknote class="w-5 h-5 text-amber-400" />
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-semibold text-sm">Cash on Delivery (COD)</p>
                        <p class="text-slate-400 text-xs mt-0.5">Manual payment — Admin approves within 24 hours</p>
                    </div>
                    <ChevronRight class="w-4 h-4 text-slate-600 group-hover:text-slate-400 transition-colors" />
                </button>

                <!-- SSLCommerz -->
                <button @click="pay('billing.sslcommerz')"
                        class="w-full flex items-center gap-4 bg-slate-900 border border-slate-800 hover:border-indigo-500 hover:bg-slate-800 rounded-2xl p-4 text-left transition-all group">
                    <div class="w-10 h-10 bg-green-500/10 rounded-xl flex items-center justify-center shrink-0">
                        <Globe class="w-5 h-5 text-green-400" />
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-semibold text-sm">SSLCommerz</p>
                        <p class="text-slate-400 text-xs mt-0.5">bKash, Nagad, Rocket, Net Banking (Bangladesh)</p>
                    </div>
                    <ChevronRight class="w-4 h-4 text-slate-600 group-hover:text-slate-400 transition-colors" />
                </button>

                <!-- Stripe -->
                <button @click="pay('billing.stripe')"
                        class="w-full flex items-center gap-4 bg-slate-900 border border-slate-800 hover:border-indigo-500 hover:bg-slate-800 rounded-2xl p-4 text-left transition-all group">
                    <div class="w-10 h-10 bg-blue-500/10 rounded-xl flex items-center justify-center shrink-0">
                        <CreditCard class="w-5 h-5 text-blue-400" />
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-semibold text-sm">Stripe</p>
                        <p class="text-slate-400 text-xs mt-0.5">International credit / debit card</p>
                    </div>
                    <ChevronRight class="w-4 h-4 text-slate-600 group-hover:text-slate-400 transition-colors" />
                </button>
            </div>

        </div>
    </AppLayout>
</template>
