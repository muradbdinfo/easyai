<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { CheckCircle, Zap, Star, ArrowRight, CreditCard } from 'lucide-vue-next'

const props = defineProps({
    plans:        { type: Array, default: () => [] },
    current_plan: { type: Object, default: null },
})

function fmt(n) {
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    if (n >= 1_000)     return (n / 1_000).toFixed(0) + 'K'
    return String(n)
}

const features = {
    'Starter':    ['500K tokens/month', 'Unlimited projects', 'Chat export PDF/MD', 'Prompt templates', 'Email support'],
    'Pro':        ['2M tokens/month', 'Unlimited projects', 'Chat export PDF/MD', 'Prompt templates', 'Team sharing', 'Priority support'],
    'Enterprise': ['10M tokens/month', 'Unlimited projects', 'Chat export PDF/MD', 'Prompt templates', 'Team sharing', 'API access', 'Dedicated support'],
}
</script>

<template>
    <AppLayout title="Plans">
        <div class="max-w-5xl mx-auto px-6 py-10">

            <div class="text-center mb-10">
                <div class="flex items-center justify-center gap-2 mb-3">
                    <CreditCard class="w-6 h-6 text-indigo-400" />
                    <h1 class="text-2xl font-bold text-white">Choose Your Plan</h1>
                </div>
                <p class="text-slate-400 text-sm">Upgrade anytime. Cancel anytime.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="relative bg-slate-900 border rounded-2xl p-6 flex flex-col transition-all"
                    :class="plan.name === 'Pro'
                        ? 'border-indigo-500 shadow-lg shadow-indigo-500/10'
                        : 'border-slate-800 hover:border-slate-700'"
                >
                    <!-- Popular badge -->
                    <div v-if="plan.name === 'Pro'"
                         class="absolute -top-3 left-1/2 -translate-x-1/2 flex items-center gap-1 bg-indigo-600 text-white text-xs font-bold px-3 py-1 rounded-full">
                        <Star class="w-3 h-3" />
                        Most Popular
                    </div>

                    <!-- Current badge -->
                    <div v-if="current_plan?.id === plan.id"
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
                            <span class="text-indigo-300 text-sm font-medium">
                                {{ fmt(plan.monthly_token_limit) }} tokens/month
                            </span>
                        </div>
                    </div>

                    <!-- Features -->
                    <ul class="space-y-2 mb-6 flex-1">
                        <li
                            v-for="f in (features[plan.name] ?? [])"
                            :key="f"
                            class="flex items-center gap-2 text-slate-300 text-sm"
                        >
                            <CheckCircle class="w-4 h-4 text-green-400 shrink-0" />
                            {{ f }}
                        </li>
                    </ul>

                    <Link
                        :href="route('billing.select', plan.id)"
                        class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl text-sm font-semibold transition-colors"
                        :class="plan.name === 'Pro'
                            ? 'bg-indigo-600 hover:bg-indigo-500 text-white'
                            : 'bg-slate-800 hover:bg-slate-700 text-slate-200'"
                    >
                        {{ current_plan?.id === plan.id ? 'Renew Plan' : 'Select Plan' }}
                        <ArrowRight class="w-4 h-4" />
                    </Link>
                </div>
            </div>

        </div>
    </AppLayout>
</template>