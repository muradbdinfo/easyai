<script setup>
// FILE: resources/js/Pages/Landing/Pricing.vue
import LandingLayout from '@/Layouts/LandingLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
    settings: Object,
    plans:    Array,
    addons:   { type: Array, default: () => [] },
})
</script>

<template>
   <LandingLayout :settings="settings" :chatbot-webhook="settings.chatbot_webhook">

        <!-- Hero -->
        <section class="pt-24 pb-14 px-5 text-center">
            <div class="max-w-2xl mx-auto">
                <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4">
                    Simple Pricing
                </h1>
                <p class="text-slate-400 text-lg">
                    No hidden fees. Pay with bKash, Nagad, Stripe, or Cash on Delivery.
                </p>
            </div>
        </section>

        <section class="pb-20 px-5">
            <div class="max-w-5xl mx-auto">

                <!-- Plan cards -->
                <div class="grid md:grid-cols-3 gap-6 mb-14">
                    <div v-for="(plan, i) in plans" :key="plan.id"
                         class="relative bg-slate-900 border rounded-2xl p-7"
                         :style="i === 1 ? 'border-color: var(--brand)' : 'border-color: rgb(30 41 59)'">
                        <div v-if="i === 1" class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                            <span class="text-white text-xs font-bold px-4 py-1 rounded-full"
                                  style="background: var(--brand)">⭐ Most Popular</span>
                        </div>
                        <h3 class="text-white font-bold text-xl mb-2">{{ plan.name }}</h3>
                        <div class="flex items-end gap-1 mb-1">
                            <span class="text-5xl font-extrabold text-white">${{ plan.price }}</span>
                            <span class="text-slate-400 text-sm pb-2">/month</span>
                        </div>
                        <p class="text-sm font-medium mb-6" style="color: var(--brand)">
                            {{ Math.round(plan.monthly_token_limit / 1000) }}K tokens / month
                        </p>
                        <ul v-if="plan.features" class="space-y-3 mb-8">
                            <li v-for="f in plan.features" :key="f"
                                class="flex items-start gap-2 text-sm text-slate-300">
                                <svg class="w-4 h-4 shrink-0 mt-0.5" :style="{color: settings.primary_color}"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ f }}
                            </li>
                        </ul>
                        <Link :href="route('register')"
                              class="block text-center py-3 rounded-xl text-sm font-bold transition-opacity hover:opacity-90"
                              :style="i === 1
                                  ? 'background: var(--brand); color:#fff'
                                  : 'background: rgb(30 41 59); color: rgb(203 213 225)'">
                            Start Free Trial
                        </Link>
                    </div>
                </div>

                <!-- Add-ons section -->
                <div v-if="addons.length" class="mb-14">

                    <div class="text-center mb-10">
                        <p class="text-xs font-bold uppercase tracking-widest mb-2"
                           style="color: var(--brand)">Power-Ups</p>
                        <h2 class="text-2xl md:text-3xl font-bold text-white mb-3">
                            Add-on Modules
                        </h2>
                        <p class="text-slate-400 text-sm max-w-xl mx-auto">
                            Extend any plan with optional add-ons.
                            Purchase separately, activate instantly, cancel anytime.
                        </p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-6">
                        <div v-for="addon in addons" :key="addon.id"
                             class="bg-slate-900 border border-slate-700 rounded-2xl p-6 flex flex-col gap-4
                                    hover:border-slate-500 transition-colors">

                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-lg">⚡</span>
                                        <h3 class="text-white font-bold text-lg">{{ addon.name }}</h3>
                                    </div>
                                    <p class="text-slate-400 text-sm leading-relaxed">
                                        {{ addon.description }}
                                    </p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <p class="text-2xl font-extrabold text-white">
                                        +{{ addon.currency }}&nbsp;{{ addon.price }}
                                    </p>
                                    <p class="text-slate-500 text-xs">/{{ addon.billing_cycle }}</p>
                                </div>
                            </div>

                            <ul v-if="addon.features?.length" class="space-y-1.5">
                                <li v-for="f in addon.features" :key="f"
                                    class="flex items-center gap-2 text-sm text-slate-300">
                                    <svg class="w-3.5 h-3.5 shrink-0" :style="{color: settings.primary_color}"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ f }}
                                </li>
                            </ul>

                            <!-- CHANGED: route('addons.index') so after login user lands on purchase page -->
                            <Link :href="route('addons.index')"
                                  class="mt-auto block text-center py-2.5 rounded-xl text-sm font-semibold
                                         border transition-all hover:text-white"
                                  :style="`border-color: var(--brand); color: var(--brand);`"
                                  @mouseover="$event.target.style.background='var(--brand)';$event.target.style.color='#fff'"
                                  @mouseout="$event.target.style.background='transparent';$event.target.style.color='var(--brand)'">
                                Get {{ addon.name }} →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Payment methods -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 mb-12">
                    <h2 class="text-white font-bold text-xl text-center mb-8">Accepted Payment Methods</h2>
                    <div class="grid sm:grid-cols-3 gap-6">
                        <div v-for="[icon, title, desc] in [
                            ['💵','Cash on Delivery','Pay manually after admin approval.'],
                            ['📱','bKash / Nagad / Cards','Instant via SSLCommerz gateway.'],
                            ['💳','International Cards','Visa, Mastercard, Amex via Stripe.'],
                        ]" :key="title" class="text-center">
                            <div class="text-4xl mb-3">{{ icon }}</div>
                            <h3 class="text-white font-semibold mb-1">{{ title }}</h3>
                            <p class="text-slate-400 text-sm">{{ desc }}</p>
                        </div>
                    </div>
                </div>

                <!-- Self-hosted CTA -->
                <div class="border rounded-2xl p-8 text-center"
                     style="background: color-mix(in srgb, var(--brand) 8%, transparent);
                            border-color: color-mix(in srgb, var(--brand) 30%, transparent)">
                    <div class="text-3xl mb-3">🖥️</div>
                    <h3 class="text-white font-bold text-xl mb-2">Need a Self-Hosted License?</h3>
                    <p class="text-slate-400 mb-6 max-w-lg mx-auto text-sm leading-relaxed">
                        Buy the source code once and deploy on your own infrastructure forever. One-time payment.
                    </p>
                    <Link v-if="settings.show_contact" :href="route('landing.contact')"
                          class="inline-flex items-center gap-2 px-6 py-3 text-white font-semibold
                                 rounded-xl transition-opacity hover:opacity-90 text-sm"
                          style="background: var(--brand)">
                        Contact for License →
                    </Link>
                </div>

            </div>
        </section>

    </LandingLayout>
</template>