<script setup>
// FILE: resources/js/Layouts/LandingLayout.vue
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import ChatbotWidget from '@/Components/ChatbotWidget.vue'

const props = defineProps({ settings: { type: Object, required: true }, chatbotWebhook: { type: String, default: '' } })

const page  = usePage()
const theme = computed(() => page.props.theme ?? {})
const brand = computed(() => theme.value.brand ?? '#6366f1')
const dark  = computed(() => (theme.value.landing_mode ?? 'dark') !== 'light')

// Auth state — HandleInertiaRequests shares auth.user globally
const isLoggedIn = computed(() => !!page.props.auth?.user)
const user       = computed(() => page.props.auth?.user)

const vars = computed(() => ({
    '--brand':   brand.value,
    '--bg':      dark.value ? '#020617' : '#f8fafc',
    '--card':    dark.value ? '#0f172a' : '#ffffff',
    '--border':  dark.value ? '#1e293b' : '#e2e8f0',
    '--text':    dark.value ? '#cbd5e1' : '#475569',
    '--heading': dark.value ? '#ffffff' : '#0f172a',
}))
</script>

<template>
    <div :style="vars" style="background:var(--bg);color:var(--text)" class="min-h-screen antialiased">

        <!-- Announcement -->
        <div v-if="settings.announcement" class="text-center text-sm py-2 px-4 font-medium text-white"
             style="background:var(--brand)">
            {{ settings.announcement }}
        </div>

        <!-- Navbar -->
        <nav class="sticky top-0 z-50 backdrop-blur-sm"
             :style="`background:${dark ? 'rgba(2,6,23,.92)' : 'rgba(248,250,252,.92)'};border-bottom:1px solid var(--border)`">
            <div class="max-w-6xl mx-auto px-5 flex items-center justify-between h-16">

                <Link :href="route('landing.home')" class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center overflow-hidden shrink-0"
                         style="background:var(--brand)">
                        <img v-if="settings.logo_url" :src="settings.logo_url" class="w-full h-full object-contain p-1"/>
                        <svg v-else class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5"/>
                        </svg>
                    </div>
                    <span class="font-bold text-lg" style="color:var(--heading)">{{ settings.app_name }}</span>
                </Link>

                <div class="hidden md:flex items-center gap-6 text-sm" style="color:var(--text)">
                    <a href="#features" class="hover:opacity-100 opacity-75 transition-opacity">Features</a>
                    <Link v-if="settings.show_pricing" :href="route('landing.pricing')" class="hover:opacity-100 opacity-75 transition-opacity">Pricing</Link>
                    <Link v-if="settings.show_contact" :href="route('landing.contact')" class="hover:opacity-100 opacity-75 transition-opacity">Contact</Link>
                </div>

                <!-- CHANGED: swap guest CTAs for dashboard link when logged in -->
                <div v-if="!isLoggedIn" class="flex items-center gap-3">
                    <Link :href="route('login')" class="text-sm opacity-70 hover:opacity-100 transition-opacity" style="color:var(--heading)">Sign in</Link>
                    <Link :href="route('register')" class="text-sm px-4 py-2 rounded-lg text-white font-medium hover:opacity-90 transition-opacity" style="background:var(--brand)">
                        {{ settings.hero_cta }}
                    </Link>
                </div>
                <div v-else class="flex items-center gap-3">
                    <span class="text-sm opacity-70" style="color:var(--heading)">{{ user.name }}</span>
                    <Link :href="route('dashboard')" class="text-sm px-4 py-2 rounded-lg text-white font-medium hover:opacity-90 transition-opacity flex items-center gap-1.5" style="background:var(--brand)">
                        Go to Dashboard
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </Link>
                </div>

            </div>
        </nav>

        <slot/>

        <!-- Footer -->
        <footer class="py-10 px-5" style="border-top:1px solid var(--border)">
            <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
                <span class="text-sm opacity-50" style="color:var(--heading)">
                    {{ settings.footer_text }} &copy; {{ new Date().getFullYear() }}
                </span>
                <div class="flex items-center gap-6 text-sm opacity-50" style="color:var(--heading)">
                    <Link :href="route('landing.home')" class="hover:opacity-100 transition-opacity">Home</Link>
                    <Link v-if="settings.show_pricing" :href="route('landing.pricing')" class="hover:opacity-100 transition-opacity">Pricing</Link>
                    <Link v-if="settings.show_contact" :href="route('landing.contact')" class="hover:opacity-100 transition-opacity">Contact</Link>
                    <!-- CHANGED: swap Sign in for Dashboard link when logged in -->
                    <Link v-if="!isLoggedIn" :href="route('login')" class="hover:opacity-100 transition-opacity">Sign in</Link>
                    <Link v-else :href="route('dashboard')" class="hover:opacity-100 transition-opacity">Dashboard</Link>
                </div>
            </div>
        </footer>


        <ChatbotWidget
    v-if="chatbotWebhook"
    :webhook-url="chatbotWebhook"
    :bot-name="settings.app_name + ' Assistant'"
    greeting="Hi! 👋 Ask me anything about our platform."
/>

    </div>
</template>