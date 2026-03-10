<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { CheckCircle, Zap, Star, ArrowRight, CreditCard, Users, Building2 } from 'lucide-vue-next'

const props = defineProps({
    plans:        { type: Array, default: () => [] },
    current_plan: { type: Object, default: null },
})

const flatPlans = computed(() => props.plans.filter(p => p.billing_type === 'flat' || !p.billing_type))
const seatPlans = computed(() => props.plans.filter(p => p.billing_type === 'seat'))

function fmt(n) {
    if (!n) return '—'
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    if (n >= 1_000)     return (n / 1_000).toFixed(0) + 'K'
    return String(n)
}

function isCurrent(plan) { return props.current_plan?.id === plan.id }
function isPopular(plan) { return plan.name === 'Pro' || plan.name === 'Team Standard' }
</script>

<template>
    <AppLayout title="Plans">
        <div class="max-w-5xl mx-auto px-6 py-10 space-y-14">

            <!-- ── Individual / Flat Plans ───────────────────────── -->
            <div>
                <div class="text-center mb-10">
                    <div class="flex items-center justify-center gap-2 mb-3">
                        <CreditCard class="w-6 h-6 text-indigo-400" />
                        <h1 class="text-2xl font-bold text-white">Individual Plans</h1>
                    </div>
                    <p class="text-slate-400 text-sm">Fixed monthly pricing. Upgrade anytime. Cancel anytime.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="plan in flatPlans" :key="plan.id"
                         class="relative bg-slate-900 border rounded-2xl p-6 flex flex-col transition-all"
                         :class="isPopular(plan) ? 'border-indigo-500 shadow-lg shadow-indigo-500/10' : 'border-slate-800 hover:border-slate-700'">

                        <div v-if="isPopular(plan)"
                             class="absolute -top-3 left-1/2 -translate-x-1/2 flex items-center gap-1 bg-indigo-600 text-white text-xs font-bold px-3 py-1 rounded-full">
                            <Star class="w-3 h-3" /> Most Popular
                        </div>
                        <div v-if="isCurrent(plan)"
                             class="absolute top-4 right-4 bg-green-500/10 text-green-400 text-xs font-semibold px-2 py-0.5 rounded-full">
                            Current
                        </div>

                        <div class="mb-5">
                            <h2 class="text-white font-bold text-lg mb-1">{{ plan.name }}</h2>
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-bold text-white">${{ plan.price }}</span>
                                <span class="text-slate-400 text-sm">/month</span>
                            </div>
                            <div class="flex items-center gap-1.5 mt-2">
                                <Zap class="w-3.5 h-3.5 text-indigo-400" />
                                <span class="text-indigo-300 text-sm font-medium">{{ fmt(plan.monthly_token_limit) }} tokens/month</span>
                            </div>
                        </div>

                        <ul class="space-y-2 mb-6 flex-1">
                            <li v-for="f in (plan.features ?? [])" :key="f" class="flex items-center gap-2 text-slate-300 text-sm">
                                <CheckCircle class="w-4 h-4 text-green-400 shrink-0" /> {{ f }}
                            </li>
                        </ul>

                        <Link :href="route('billing.select', plan.id)"
                              class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl text-sm font-semibold transition-colors"
                              :class="isPopular(plan) ? 'bg-indigo-600 hover:bg-indigo-500 text-white' : 'bg-slate-800 hover:bg-slate-700 text-white'">
                            {{ isCurrent(plan) ? '✓ Current Plan' : 'Select Plan' }}
                            <ArrowRight v-if="!isCurrent(plan)" class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ── Team / Seat Plans ─────────────────────────────── -->
            <div v-if="seatPlans.length">
                <div class="text-center mb-10">
                    <div class="flex items-center justify-center gap-2 mb-3">
                        <Users class="w-6 h-6 text-indigo-400" />
                        <h2 class="text-2xl font-bold text-white">Team Plans</h2>
                    </div>
                    <p class="text-slate-400 text-sm">Per-seat pricing with a shared pooled token budget for your whole workspace.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div v-for="(plan, i) in seatPlans" :key="plan.id"
                         class="relative bg-slate-900 border rounded-2xl p-6 flex flex-col transition-all"
                         :class="isPopular(plan) ? 'border-indigo-500 shadow-lg shadow-indigo-500/10' : 'border-slate-800 hover:border-slate-700'">

                        <div v-if="isPopular(plan)"
                             class="absolute -top-3 left-1/2 -translate-x-1/2 flex items-center gap-1 bg-indigo-600 text-white text-xs font-bold px-3 py-1 rounded-full">
                            <Star class="w-3 h-3" /> Most Popular
                        </div>
                        <div v-if="isCurrent(plan)"
                             class="absolute top-4 right-4 bg-green-500/10 text-green-400 text-xs font-semibold px-2 py-0.5 rounded-full">
                            Current
                        </div>

                        <div class="w-10 h-10 rounded-lg bg-indigo-600/20 flex items-center justify-center mb-4">
                            <Building2 v-if="i === 2" class="w-5 h-5 text-indigo-400" />
                            <Users v-else class="w-5 h-5 text-indigo-400" />
                        </div>

                        <div class="mb-5">
                            <h2 class="text-white font-bold text-lg mb-1">{{ plan.name }}</h2>
                            <div class="bg-slate-800 rounded-xl p-3 mb-3">
                                <div class="flex items-end justify-between">
                                    <span class="text-slate-400 text-xs">Per seat / month</span>
                                    <div>
                                        <span class="text-white text-2xl font-bold">${{ Number(plan.price_per_seat).toFixed(0) }}</span>
                                        <span class="text-slate-400 text-xs">/seat</span>
                                    </div>
                                </div>
                                <p class="text-slate-500 text-xs mt-1">
                                    Min {{ plan.min_seats }} seats{{ plan.max_seats ? ` · Max ${plan.max_seats}` : ' · Unlimited' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <Zap class="w-3.5 h-3.5 text-indigo-400" />
                                <span class="text-indigo-300 text-sm font-medium">{{ fmt(plan.token_limit_per_seat) }}/seat shared pool</span>
                            </div>
                        </div>

                        <ul class="space-y-2 mb-6 flex-1">
                            <li v-for="f in (plan.features ?? [])" :key="f" class="flex items-center gap-2 text-slate-300 text-sm">
                                <CheckCircle class="w-4 h-4 text-green-400 shrink-0" /> {{ f }}
                            </li>
                        </ul>

                        <Link :href="route('billing.select', plan.id)"
                              class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 text-white transition-colors">
                            {{ isCurrent(plan) ? '✓ Current Plan' : 'Get ' + plan.name }}
                            <ArrowRight v-if="!isCurrent(plan)" class="w-4 h-4" />
                        </Link>
                    </div>
                </div>

                <!-- Shared pool explainer -->
                <div class="bg-slate-900 border border-slate-700 rounded-xl p-5 flex items-start gap-3">
                    <Users class="w-4 h-4 text-indigo-400 shrink-0 mt-0.5" />
                    <p class="text-slate-400 text-sm">
                        <strong class="text-slate-300">Shared pool:</strong>
                        All seats share one token budget. Example — 10 seats on Team Standard = 5M tokens/month for the whole team. Heavy users draw more, light users draw less. No per-user cap.
                    </p>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
