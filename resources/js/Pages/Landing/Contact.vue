<script setup>
// FILE: resources/js/Pages/Landing/Contact.vue
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import LandingLayout from '@/Layouts/LandingLayout.vue'

const props  = defineProps({ settings: Object })
const page   = usePage()
const flash  = computed(() => page.props.flash ?? {})

const form = useForm({
    name:    '',
    email:   '',
    subject: 'general',
    message: '',
})

function send() {
    form.post(route('landing.contact.send'), { preserveScroll: true })
}
</script>

<template>
    <LandingLayout :settings="settings">

        <!-- Hero -->
        <section class="pt-24 pb-14 px-5 text-center">
            <div class="max-w-2xl mx-auto">
                <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4">Get in Touch</h1>
                <p class="text-slate-400 text-lg">Questions about pricing, licensing, or deployment? We'll reply within 24 hours.</p>
            </div>
        </section>

        <!-- Body -->
        <section class="pb-24 px-5">
            <div class="max-w-5xl mx-auto grid md:grid-cols-5 gap-10">

                <!-- Left info -->
                <div class="md:col-span-2 space-y-6">
                    <div v-for="[icon, label, value] in [
                        ['📧', 'Email',         settings.contact_email],
                        ['💬', 'Response Time', 'Within 24 hours on business days'],
                        ['🌏', 'Location',      'Bangladesh · Available worldwide'],
                    ]" :key="label" class="flex items-start gap-4">
                        <div class="text-2xl mt-0.5">{{ icon }}</div>
                        <div>
                            <p class="text-slate-500 text-xs uppercase tracking-wider mb-0.5">{{ label }}</p>
                            <p class="text-white text-sm">{{ value }}</p>
                        </div>
                    </div>

                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 mt-4">
                        <p class="text-slate-400 text-xs uppercase tracking-wider mb-3">Common enquiries</p>
                        <ul class="space-y-2">
                            <li v-for="item in ['Self-hosted license purchase','White-label / reseller pricing','Custom deployment help','Enterprise plan enquiry','Bug report or feature request']"
                                :key="item" class="flex items-center gap-2 text-sm text-slate-300">
                                <svg class="w-3.5 h-3.5 shrink-0" :style="{color: settings.primary_color}"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Form -->
                <div class="md:col-span-3">
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">

                        <div v-if="flash.success"
                             class="flex items-center gap-2 bg-green-900/30 border border-green-700/30
                                    text-green-400 text-sm px-4 py-3 rounded-lg mb-6">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ flash.success }}
                        </div>

                        <div class="space-y-5">
                            <div class="grid sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-slate-400 text-xs mb-1.5">Your Name *</label>
                                    <input v-model="form.name" type="text" required placeholder="Murad Hosen"
                                           class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5
                                                  text-white text-sm outline-none transition-colors"
                                           :class="form.errors.name ? 'border-red-500' : 'focus:border-indigo-500'"
                                           :style="!form.errors.name ? {'--tw-ring-color': settings.primary_color} : {}" />
                                    <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-slate-400 text-xs mb-1.5">Email *</label>
                                    <input v-model="form.email" type="email" required placeholder="you@example.com"
                                           class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5
                                                  text-white text-sm outline-none focus:border-indigo-500 transition-colors"
                                           :class="form.errors.email ? 'border-red-500' : ''" />
                                    <p v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-slate-400 text-xs mb-1.5">Subject *</label>
                                <select v-model="form.subject"
                                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5
                                               text-white text-sm outline-none focus:border-indigo-500 transition-colors">
                                    <option value="general">General Question</option>
                                    <option value="license">Self-Hosted License</option>
                                    <option value="white_label">White-Label / Reseller</option>
                                    <option value="enterprise">Enterprise Plan</option>
                                    <option value="support">Technical Support</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-slate-400 text-xs mb-1.5">Message *</label>
                                <textarea v-model="form.message" rows="5" required placeholder="Tell us what you need..."
                                          class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5
                                                 text-white text-sm outline-none focus:border-indigo-500 transition-colors resize-none"
                                          :class="form.errors.message ? 'border-red-500' : ''"/>
                                <p v-if="form.errors.message" class="text-red-400 text-xs mt-1">{{ form.errors.message }}</p>
                            </div>

                            <button @click="send" :disabled="form.processing"
                                    class="w-full py-3 text-white font-semibold rounded-xl transition-opacity hover:opacity-90 text-sm disabled:opacity-50"
                                    style="background: var(--brand)">
                                {{ form.processing ? 'Sending...' : 'Send Message →' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </LandingLayout>
</template>