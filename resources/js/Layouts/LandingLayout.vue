<script setup>
// FILE: resources/js/Layouts/LandingLayout.vue
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    settings: { type: Object, required: true },
})

// Inject brand color as CSS variable on root element
const brandStyle = computed(() => ({
    '--brand':      props.settings.primary_color ?? '#6366f1',
    '--brand-dark': darken(props.settings.primary_color ?? '#6366f1'),
}))

// Simple hex darkener (10%)
function darken(hex) {
    const n = parseInt(hex.replace('#',''), 16)
    const r = Math.max(0, (n >> 16) - 25)
    const g = Math.max(0, ((n >> 8) & 0xff) - 25)
    const b = Math.max(0, (n & 0xff) - 25)
    return '#' + [r, g, b].map(v => v.toString(16).padStart(2,'0')).join('')
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-300 antialiased" :style="brandStyle">

        <!-- Announcement banner -->
        <div v-if="settings.announcement"
             class="text-center text-sm py-2 px-4 font-medium"
             style="background: var(--brand); color: #fff;">
            {{ settings.announcement }}
        </div>

        <!-- Navbar -->
        <nav class="sticky top-0 z-50 bg-slate-950/90 backdrop-blur border-b border-slate-800">
            <div class="max-w-6xl mx-auto px-5 flex items-center justify-between h-16">

                <!-- Logo -->
                <Link :href="route('landing.home')" class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center overflow-hidden shrink-0"
                         style="background: var(--brand)">
                        <img v-if="settings.logo_url" :src="settings.logo_url"
                             class="w-full h-full object-contain p-1" />
                        <svg v-else class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3" />
                        </svg>
                    </div>
                    <span class="text-white font-bold text-lg">{{ settings.app_name }}</span>
                </Link>

                <!-- Links -->
                <div class="hidden md:flex items-center gap-6 text-sm">
                    <Link :href="route('landing.home') + '#features'"
                          class="hover:text-white transition-colors">Features</Link>
                    <Link v-if="settings.show_pricing"
                          :href="route('landing.pricing')"
                          class="hover:text-white transition-colors">Pricing</Link>
                    <Link v-if="settings.show_contact"
                          :href="route('landing.contact')"
                          class="hover:text-white transition-colors">Contact</Link>
                </div>

                <!-- CTA -->
                <div class="flex items-center gap-3">
                    <Link :href="route('login')"
                          class="text-sm text-slate-400 hover:text-white transition-colors">
                        Sign in
                    </Link>
                    <Link :href="route('register')"
                          class="text-sm px-4 py-2 rounded-lg text-white font-medium transition-colors"
                          style="background: var(--brand)">
                        {{ settings.hero_cta }}
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Page slot -->
        <slot />

        <!-- Footer -->
        <footer class="border-t border-slate-800 py-10 px-5">
            <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded flex items-center justify-center"
                         style="background: var(--brand)">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5" />
                        </svg>
                    </div>
                    <span class="text-slate-400 text-sm">{{ settings.footer_text }} &copy; {{ new Date().getFullYear() }}</span>
                </div>
                <div class="flex items-center gap-6 text-sm text-slate-500">
                    <Link :href="route('landing.home')" class="hover:text-white transition-colors">Home</Link>
                    <Link v-if="settings.show_pricing"
                          :href="route('landing.pricing')" class="hover:text-white transition-colors">Pricing</Link>
                    <Link v-if="settings.show_contact"
                          :href="route('landing.contact')" class="hover:text-white transition-colors">Contact</Link>
                    <Link :href="route('login')" class="hover:text-white transition-colors">Sign in</Link>
                </div>
            </div>
        </footer>

    </div>
</template>