<script setup>
// FILE: resources/js/Pages/Landing/Home.vue
import { onMounted, computed } from 'vue'
import LandingLayout from '@/Layouts/LandingLayout.vue'
import { Link } from '@inertiajs/vue3'
import {
    ArrowRight, ArrowDown, Check, ChevronDown, Sparkles, Star,
    Shield, Zap, Server, Users, Brain, FileText, Lock, Database,
    Cpu, Cloud, Code, MessageSquare, Bot, Layers, Settings,
    Workflow, Globe, KeyRound, Download, BarChart3, Rocket
} from 'lucide-vue-next'

const props = defineProps({ settings: Object, plans: Array })

/* Map a feature.icon string (emoji or keyword) to a Lucide component.
   Falls back to Sparkles. This lets the admin keep any existing values
   in the DB without breakage. */
const iconMap = {
    'shield': Shield, 'lock': Lock, 'security': Shield, '??': Lock, '???': Shield, '??': Shield,
    'zap': Zap, 'speed': Zap, 'fast': Zap, '?': Zap,
    'server': Server, 'self-hosted': Server, 'host': Server, '???': Server, '??': Server,
    'users': Users, 'team': Users, 'collab': Users, '??': Users, '??': Users,
    'brain': Brain, 'ai': Brain, 'memory': Brain, '??': Brain,
    'database': Database, 'storage': Database, '??': Database,
    'cpu': Cpu, 'ollama': Cpu, 'model': Cpu, '??': Cpu,
    'cloud': Cloud, 'deploy': Cloud, '??': Cloud,
    'code': Code, 'api': Code, '??': Code, '??': Layers,
    'chat': MessageSquare, 'message': MessageSquare, '??': MessageSquare,
    'bot': Bot, '??': Bot,
    'workflow': Workflow, 'automation': Workflow, '??': Settings, 'settings': Settings,
    'globe': Globe, 'web': Globe, '??': Globe,
    'key': KeyRound, 'auth': KeyRound, '??': KeyRound,
    'export': Download, 'download': Download, '??': Download,
    'analytics': BarChart3, 'stats': BarChart3, '??': BarChart3,
    'docs': FileText, 'file': FileText, '??': FileText, '??': FileText,
    'rocket': Rocket, '??': Rocket, 'sparkles': Sparkles, '?': Sparkles, '?': Star, 'star': Star,
}
function iconFor(raw) {
    if (!raw) return Sparkles
    const key = String(raw).toLowerCase().trim()
    return iconMap[key] || iconMap[raw] || Sparkles
}

const steps = [
    { icon: Download, title: 'Deploy in minutes',  desc: 'Clone, configure .env, run migrations. Works on VPS or shared hosting.' },
    { icon: Users,    title: 'Invite your team',   desc: 'Create a workspace, invite teammates, organize work into projects.' },
    { icon: Sparkles, title: 'Start chatting',     desc: 'Connect Ollama models and work completely privately, on your terms.' },
]

const heroStats = [
    { value: '100%',  label: 'Private', sub: 'Self-hosted'  },
    { value: '8',     label: 'Projects', sub: 'No vendor cap' },
    { value: '$0',    label: 'Setup',    sub: 'Open source'  },
]

const trustLogos = computed(() => props.settings?.trust_logos || [])

onMounted(() => {
    const io = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('revealed')
                io.unobserve(e.target)
            }
        })
    }, { threshold: 0.12 })
    document.querySelectorAll('[data-reveal]').forEach(el => io.observe(el))
})
</script>

<template>
    <LandingLayout :settings="settings" :chatbot-webhook="settings.chatbot_webhook">

        <!-- --- HERO ------------------------------------------------------- -->
        <section class="relative overflow-hidden pt-20 pb-24 md:pt-28 md:pb-32">

            <!-- Ambient gradient orbs -->
            <div class="hero-orb absolute -top-32 left-1/2 -translate-x-1/2 w-[900px] h-[600px] pointer-events-none"
                 style="background: radial-gradient(ellipse 60% 50% at 50% 50%, color-mix(in srgb, var(--brand) 22%, transparent), transparent 70%);"/>
            <div class="absolute top-40 -left-40 w-[400px] h-[400px] pointer-events-none opacity-50"
                 style="background: radial-gradient(circle, color-mix(in srgb, var(--brand) 14%, transparent), transparent 70%);"/>
            <div class="absolute top-60 -right-40 w-[400px] h-[400px] pointer-events-none opacity-40"
                 style="background: radial-gradient(circle, #8b5cf6 0%, transparent 70%); filter: blur(60px);"/>

            <!-- Subtle grid -->
            <div class="absolute inset-0 pointer-events-none opacity-[0.025]"
                 style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 32px 32px;"/>

            <div class="relative max-w-6xl mx-auto px-5">
                <div class="grid lg:grid-cols-[1.1fr,1fr] gap-12 lg:gap-16 items-center">

                    <!-- LEFT: Copy -->
                    <div class="text-center lg:text-left">

                        <div class="hero-badge inline-flex items-center gap-2 text-xs font-semibold px-3.5 py-1.5 rounded-full mb-7 border"
                             style="background: color-mix(in srgb, var(--brand) 10%, transparent); color: var(--brand); border-color: color-mix(in srgb, var(--brand) 28%, transparent)">
                            <span class="relative flex h-2 w-2">
                                <span class="live-ping absolute inline-flex h-full w-full rounded-full opacity-60" style="background: var(--brand)"/>
                                <span class="relative inline-flex rounded-full h-2 w-2" style="background: var(--brand)"/>
                            </span>
                            <span class="tracking-wide">Self-hosted AI workspace · Powered by Ollama</span>
                        </div>

                        <h1 class="hero-title text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-extrabold leading-[1.05] tracking-tight mb-6"
                            style="color: var(--heading)">
                            <span>{{ settings.hero_title }}</span>
                        </h1>

                        <p class="hero-sub text-base md:text-lg max-w-xl mx-auto lg:mx-0 mb-9 leading-relaxed"
                           style="color: color-mix(in srgb, var(--text) 88%, transparent)">
                            {{ settings.hero_subtitle }}
                        </p>

                        <div class="hero-ctas flex flex-col sm:flex-row items-center lg:items-start justify-center lg:justify-start gap-3">
                            <Link :href="route('register')"
                                  class="btn-primary group w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 text-white font-semibold rounded-xl text-sm shadow-lg"
                                  style="background: var(--brand); box-shadow: 0 10px 30px -10px color-mix(in srgb, var(--brand) 60%, transparent)">
                                {{ settings.hero_cta }}
                                <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-0.5"/>
                            </Link>
                            <a href="#features"
                               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 font-semibold rounded-xl text-sm transition-colors"
                               style="border: 1px solid var(--border); color: var(--heading)">
                                See features
                                <ArrowDown class="w-4 h-4"/>
                            </a>
                        </div>

                        <p class="hero-hint mt-6 text-xs tracking-wide opacity-60" style="color: var(--text)">
                            No credit card required · Your data stays private · Any server
                        </p>

                        <!-- Stats strip -->
                        <div class="hero-stats mt-12 grid grid-cols-3 gap-3 max-w-md mx-auto lg:mx-0">
                            <div v-for="s in heroStats" :key="s.label"
                                 class="stat-card rounded-xl py-3 px-3 text-center lg:text-left"
                                 style="background: color-mix(in srgb, var(--card) 70%, transparent); border: 1px solid var(--border)">
                                <div class="text-2xl font-extrabold leading-none mb-1" style="color: var(--brand)">{{ s.value }}</div>
                                <div class="text-xs font-semibold" style="color: var(--heading)">{{ s.label }}</div>
                                <div class="text-[10px] opacity-60 mt-0.5" style="color: var(--text)">{{ s.sub }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Live preview card (desktop only, decorative) -->
                    <div class="hero-preview hidden lg:block relative">
                        <!-- Glow behind card -->
                        <div class="absolute -inset-6 rounded-3xl opacity-50 blur-2xl pointer-events-none"
                             style="background: linear-gradient(135deg, color-mix(in srgb, var(--brand) 40%, transparent), transparent 60%)"/>

                        <div class="relative rounded-2xl border overflow-hidden shadow-2xl"
                             style="background: var(--card); border-color: var(--border); box-shadow: 0 30px 60px -20px rgba(0,0,0,.5)">

                            <!-- Window chrome -->
                            <div class="flex items-center gap-1.5 px-4 py-3 border-b"
                                 style="border-color: var(--border); background: color-mix(in srgb, var(--bg) 50%, transparent)">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-400/70"/>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-400/70"/>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-400/70"/>
                                <div class="flex-1 text-center text-[11px] opacity-60" style="color: var(--text)">
                                    {{ settings.app_name }} workspace
                                </div>
                            </div>

                            <!-- Chat body -->
                            <div class="p-5 space-y-4">
                                <!-- User message -->
                                <div class="flex justify-end">
                                    <div class="max-w-[80%] rounded-2xl rounded-br-sm px-4 py-2.5 text-sm text-white"
                                         style="background: var(--brand)">
                                        Summarize today's standup notes
                                    </div>
                                </div>

                                <!-- Bot reply -->
                                <div class="flex gap-2.5">
                                    <div class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center"
                                         style="background: color-mix(in srgb, var(--brand) 15%, transparent)">
                                        <Bot class="w-4 h-4" :style="{ color: 'var(--brand)' }"/>
                                    </div>
                                    <div class="flex-1 rounded-2xl rounded-tl-sm px-4 py-3 text-sm leading-relaxed"
                                         style="background: color-mix(in srgb, var(--bg) 60%, transparent); border: 1px solid var(--border); color: var(--heading)">
                                        Here are the key points from your standup:
                                        <ul class="mt-2 space-y-1.5 text-xs opacity-90">
                                            <li class="flex items-start gap-1.5"><Check class="w-3 h-3 mt-0.5 shrink-0" :style="{ color: 'var(--brand)' }"/> API rate-limit shipped to staging</li>
                                            <li class="flex items-start gap-1.5"><Check class="w-3 h-3 mt-0.5 shrink-0" :style="{ color: 'var(--brand)' }"/> Auth bug blocked by upstream review</li>
                                            <li class="flex items-start gap-1.5"><Check class="w-3 h-3 mt-0.5 shrink-0" :style="{ color: 'var(--brand)' }"/> RAG ingestion 80% complete</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Typing indicator -->
                                <div class="flex gap-2.5">
                                    <div class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center"
                                         style="background: color-mix(in srgb, var(--brand) 15%, transparent)">
                                        <Bot class="w-4 h-4" :style="{ color: 'var(--brand)' }"/>
                                    </div>
                                    <div class="rounded-2xl rounded-tl-sm px-4 py-3"
                                         style="background: color-mix(in srgb, var(--bg) 60%, transparent); border: 1px solid var(--border)">
                                        <span class="flex gap-1">
                                            <span class="typing-dot w-1.5 h-1.5 rounded-full" style="background: var(--brand); animation-delay: 0ms"/>
                                            <span class="typing-dot w-1.5 h-1.5 rounded-full" style="background: var(--brand); animation-delay: 200ms"/>
                                            <span class="typing-dot w-1.5 h-1.5 rounded-full" style="background: var(--brand); animation-delay: 400ms"/>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer hint -->
                            <div class="px-5 py-3 border-t text-[11px] flex items-center justify-between opacity-70"
                                 style="border-color: var(--border); color: var(--text)">
                                <span class="inline-flex items-center gap-1.5">
                                    <Cpu class="w-3 h-3"/> qwen2.5-coder · running locally
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <Lock class="w-3 h-3"/> Encrypted
                                </span>
                            </div>
                        </div>

                        <!-- Floating accent -->
                        <div class="absolute -bottom-4 -right-4 px-3 py-2 rounded-xl text-xs font-semibold flex items-center gap-1.5 shadow-lg"
                             style="background: var(--card); border: 1px solid var(--border); color: var(--heading)">
                            <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"/>
                            Live · 12ms latency
                        </div>
                    </div>
                </div>

                <!-- Trust logos strip -->
                <div v-if="trustLogos.length"
                     class="mt-20 pt-10 border-t" style="border-color: var(--border)">
                    <p class="text-center text-xs uppercase tracking-widest mb-6 opacity-50" style="color: var(--text)">
                        Trusted by teams running on their own infrastructure
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-4 opacity-60">
                        <img v-for="logo in trustLogos" :key="logo.alt"
                             :src="logo.src" :alt="logo.alt"
                             class="h-7 grayscale hover:grayscale-0 transition-all"/>
                    </div>
                </div>
            </div>
        </section>

        <!-- --- FEATURES --------------------------------------------------- -->
        <section id="features" class="py-24 px-5 relative">
            <div class="max-w-6xl mx-auto">

                <div class="text-center mb-16" data-reveal>
                    <p class="eyebrow">Features</p>
                    <h2 class="section-title">Everything your team needs</h2>
                    <p class="section-lead">Built for teams that care about privacy, productivity, and complete control over their AI stack.</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="(f, i) in settings.features" :key="f.title"
                         class="feature-card group relative rounded-2xl p-6 overflow-hidden"
                         style="background: var(--card); border: 1px solid var(--border)"
                         data-reveal :data-reveal-delay="(i % 6) + 1">

                        <!-- Gradient hover wash -->
                        <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"
                             style="background: radial-gradient(circle at top right, color-mix(in srgb, var(--brand) 8%, transparent), transparent 60%)"/>

                        <div class="relative">
                            <!-- Icon halo -->
                            <div class="relative w-12 h-12 mb-5">
                                <div class="absolute inset-0 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity blur-md"
                                     style="background: var(--brand)"/>
                                <div class="relative w-12 h-12 rounded-xl flex items-center justify-center"
                                     style="background: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand)">
                                    <component :is="iconFor(f.icon)" class="w-5 h-5"/>
                                </div>
                            </div>

                            <h3 class="font-semibold mb-2 text-base" style="color: var(--heading)">{{ f.title }}</h3>
                            <p class="text-sm leading-relaxed opacity-75" style="color: var(--text)">{{ f.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- --- HOW IT WORKS ----------------------------------------------- -->
        <section class="py-24 px-5 relative"
                 style="background: linear-gradient(180deg, transparent, color-mix(in srgb, var(--brand) 3%, transparent) 50%, transparent)">
            <div class="max-w-5xl mx-auto">

                <div class="text-center mb-16" data-reveal>
                    <p class="eyebrow">How it works</p>
                    <h2 class="section-title">Up and running in minutes</h2>
                    <p class="section-lead">Deploy on your own server. No vendor lock-in. No monthly AI bills.</p>
                </div>

                <div class="relative grid md:grid-cols-3 gap-10">
                    <!-- Connecting line -->
                    <div class="hidden md:block absolute top-7 left-[16%] right-[16%] h-px"
                         style="background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--brand) 40%, transparent) 20%, color-mix(in srgb, var(--brand) 40%, transparent) 80%, transparent)"/>

                    <div v-for="(s, i) in steps" :key="i"
                         class="text-center relative"
                         data-reveal :data-reveal-delay="i + 1">

                        <!-- Number badge with icon -->
                        <div class="relative inline-flex mb-6">
                            <div class="step-num w-14 h-14 rounded-2xl flex items-center justify-center relative z-10"
                                 style="background: var(--card); border: 1px solid var(--border)">
                                <component :is="s.icon" class="w-6 h-6" :style="{ color: 'var(--brand)' }"/>
                            </div>
                            <div class="absolute -top-2 -right-2 w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold text-white z-20"
                                 style="background: var(--brand)">{{ i + 1 }}</div>
                        </div>

                        <h3 class="font-semibold mb-2" style="color: var(--heading)">{{ s.title }}</h3>
                        <p class="text-sm leading-relaxed max-w-[260px] mx-auto opacity-75" style="color: var(--text)">{{ s.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- --- PRICING PREVIEW ------------------------------------------- -->
        <section v-if="settings.show_pricing && plans && plans.length" class="py-24 px-5">
            <div class="max-w-5xl mx-auto">

                <div class="text-center mb-14" data-reveal>
                    <p class="eyebrow">Pricing</p>
                    <h2 class="section-title">Simple, transparent pricing</h2>
                    <p class="section-lead">Pay with bKash, Nagad, Stripe, or Cash on Delivery.</p>
                </div>

                <div class="grid md:grid-cols-3 gap-5">
                    <div v-for="(plan, i) in plans" :key="plan.id"
                         class="plan-card relative rounded-2xl p-7"
                         :class="{ 'plan-popular': i === 1 }"
                         :style="i === 1
                            ? `background: var(--card); border: 1px solid color-mix(in srgb, var(--brand) 50%, transparent); box-shadow: 0 0 0 4px color-mix(in srgb, var(--brand) 10%, transparent), 0 20px 50px -20px color-mix(in srgb, var(--brand) 40%, transparent)`
                            : 'background: var(--card); border: 1px solid var(--border)'"
                         data-reveal :data-reveal-delay="i + 1">

                        <div v-if="i === 1"
                             class="absolute -top-3 left-1/2 -translate-x-1/2 inline-flex items-center gap-1 text-white text-[11px] font-bold px-3 py-1 rounded-full"
                             style="background: var(--brand)">
                            <Star class="w-3 h-3 fill-white"/> Most Popular
                        </div>

                        <p class="text-[11px] font-bold uppercase tracking-widest mb-3 opacity-60" style="color: var(--heading)">{{ plan.name }}</p>

                        <div class="flex items-baseline gap-1 mb-1">
                            <span class="text-5xl font-extrabold" style="color: var(--heading)">${{ plan.price }}</span>
                            <span class="text-sm opacity-50" style="color: var(--text)">/mo</span>
                        </div>
                        <p class="text-xs font-semibold mb-7" style="color: var(--brand)">
                            {{ Math.round(plan.monthly_token_limit / 1000) }}K tokens / month
                        </p>

                        <ul v-if="plan.features" class="space-y-2.5 mb-8">
                            <li v-for="f in plan.features" :key="f" class="flex items-start gap-2.5 text-sm" style="color: var(--text)">
                                <Check class="w-4 h-4 mt-0.5 shrink-0" :style="{ color: 'var(--brand)' }"/>
                                <span>{{ f }}</span>
                            </li>
                        </ul>

                        <Link :href="route('register')"
                              class="block text-center py-3 rounded-xl text-sm font-bold transition-all"
                              :class="i === 1 ? 'btn-primary text-white' : ''"
                              :style="i === 1
                                ? 'background: var(--brand)'
                                : 'background: color-mix(in srgb, var(--brand) 8%, transparent); color: var(--brand); border: 1px solid color-mix(in srgb, var(--brand) 25%, transparent)'">
                            Get started
                        </Link>
                    </div>
                </div>

                <p class="text-center mt-10">
                    <Link :href="route('landing.pricing')"
                          class="inline-flex items-center gap-1.5 text-sm font-medium hover:gap-2.5 transition-all"
                          style="color: var(--brand)">
                        Compare all plan features <ArrowRight class="w-4 h-4"/>
                    </Link>
                </p>
            </div>
        </section>

        <!-- --- FAQ -------------------------------------------------------- -->
        <section v-if="settings.faq && settings.faq.length" id="faq" class="py-24 px-5">
            <div class="max-w-2xl mx-auto">

                <div class="text-center mb-12" data-reveal>
                    <p class="eyebrow">FAQ</p>
                    <h2 class="section-title text-3xl">Frequently asked questions</h2>
                </div>

                <div class="space-y-2.5">
                    <details v-for="(item, i) in settings.faq" :key="item.q"
                             class="faq-item rounded-xl overflow-hidden transition-colors"
                             style="background: var(--card); border: 1px solid var(--border)"
                             data-reveal :data-reveal-delay="Math.min(i + 1, 5)">
                        <summary class="flex items-center justify-between gap-4 px-5 py-4 font-medium cursor-pointer list-none select-none"
                                 style="color: var(--heading)">
                            <span class="text-left text-sm">{{ item.q }}</span>
                            <ChevronDown class="faq-chevron w-4 h-4 shrink-0 opacity-60"/>
                        </summary>
                        <div class="px-5 pb-4">
                            <p class="text-sm leading-relaxed pt-3 border-t opacity-75"
                               style="border-color: var(--border); color: var(--text)">{{ item.a }}</p>
                        </div>
                    </details>
                </div>
            </div>
        </section>

        <!-- --- CTA -------------------------------------------------------- -->
        <section class="py-24 px-5">
            <div class="max-w-5xl mx-auto" data-reveal>
                <div class="relative rounded-3xl overflow-hidden"
                     style="background: linear-gradient(135deg, color-mix(in srgb, var(--brand) 12%, var(--card)), var(--card)); border: 1px solid color-mix(in srgb, var(--brand) 25%, transparent)">

                    <!-- Decorative gradient -->
                    <div class="absolute inset-0 pointer-events-none opacity-50"
                         style="background: radial-gradient(ellipse 60% 80% at 100% 0%, color-mix(in srgb, var(--brand) 25%, transparent), transparent 60%)"/>
                    <div class="absolute inset-0 pointer-events-none opacity-[0.04]"
                         style="background-image: radial-gradient(circle, currentColor 1px, transparent 1px); background-size: 24px 24px; color: var(--heading)"/>

                    <div class="relative grid md:grid-cols-[1.4fr,1fr] gap-10 p-10 md:p-14 items-center">
                        <div>
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-5"
                                 style="background: color-mix(in srgb, var(--brand) 18%, transparent); color: var(--brand)">
                                <Sparkles class="w-6 h-6"/>
                            </div>
                            <h2 class="text-3xl md:text-4xl font-bold mb-4 leading-tight" style="color: var(--heading)">
                                Ready to own your AI workspace?
                            </h2>
                            <p class="text-sm md:text-base leading-relaxed opacity-75" style="color: var(--text)">
                                Start your free trial. No credit card required. Deploy on your own server in minutes — and keep every conversation private.
                            </p>
                        </div>

                        <div class="flex flex-col gap-3">
                            <Link :href="route('register')"
                                  class="btn-primary group inline-flex items-center justify-center gap-2 px-7 py-4 text-white font-semibold rounded-xl text-sm shadow-lg"
                                  style="background: var(--brand); box-shadow: 0 10px 30px -10px color-mix(in srgb, var(--brand) 60%, transparent)">
                                {{ settings.hero_cta }}
                                <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-0.5"/>
                            </Link>
                            <Link v-if="settings.show_contact" :href="route('landing.contact')"
                                  class="inline-flex items-center justify-center gap-2 px-7 py-4 font-semibold rounded-xl text-sm transition-colors"
                                  style="border: 1px solid var(--border); color: var(--heading)">
                                Talk to us
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </LandingLayout>
</template>

<style scoped>
/* -- Section type system ------------------------------------------------- */
.eyebrow {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    margin-bottom: 14px;
    color: var(--brand);
}
.section-title {
    font-size: clamp(28px, 4vw, 40px);
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.15;
    margin-bottom: 14px;
    color: var(--heading);
}
.section-lead {
    max-width: 560px;
    margin: 0 auto;
    font-size: 15px;
    line-height: 1.6;
    color: var(--text);
    opacity: 0.8;
}

/* -- Reveal on scroll ---------------------------------------------------- */
[data-reveal] { opacity: 0; transform: translateY(22px); transition: opacity .65s cubic-bezier(.4,0,.2,1), transform .65s cubic-bezier(.4,0,.2,1); }
[data-reveal].revealed { opacity: 1; transform: none; }
[data-reveal-delay="1"] { transition-delay: .08s; }
[data-reveal-delay="2"] { transition-delay: .16s; }
[data-reveal-delay="3"] { transition-delay: .24s; }
[data-reveal-delay="4"] { transition-delay: .32s; }
[data-reveal-delay="5"] { transition-delay: .40s; }
[data-reveal-delay="6"] { transition-delay: .48s; }

/* -- Hero load animations ------------------------------------------------ */
.hero-badge   { animation: fadeUp .65s cubic-bezier(.4,0,.2,1) .10s both; }
.hero-title   { animation: fadeUp .65s cubic-bezier(.4,0,.2,1) .22s both; }
.hero-sub     { animation: fadeUp .65s cubic-bezier(.4,0,.2,1) .34s both; }
.hero-ctas    { animation: fadeUp .65s cubic-bezier(.4,0,.2,1) .46s both; }
.hero-hint    { animation: fadeUp .65s cubic-bezier(.4,0,.2,1) .56s both; }
.hero-stats   { animation: fadeUp .65s cubic-bezier(.4,0,.2,1) .68s both; }
.hero-preview { animation: fadeUpScale .8s cubic-bezier(.4,0,.2,1) .4s both; }
@keyframes fadeUp      { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }
@keyframes fadeUpScale { from { opacity: 0; transform: translateY(24px) scale(.96); } to { opacity: 1; transform: none; } }

/* -- Ambient orb breathing ----------------------------------------------- */
.hero-orb { animation: orbPulse 7s ease-in-out infinite; }
@keyframes orbPulse { 0%, 100% { opacity: .25; transform: translateX(-50%) scale(1); } 50% { opacity: .4; transform: translateX(-50%) scale(1.06); } }

/* -- Live ping dot ------------------------------------------------------- */
.live-ping { animation: ping 1.6s cubic-bezier(0,0,.2,1) infinite; }
@keyframes ping { 75%, 100% { transform: scale(2.2); opacity: 0; } }

/* -- Typing dots in hero preview ---------------------------------------- */
.typing-dot { animation: bounce 1.2s ease-in-out infinite; }
@keyframes bounce { 0%, 60%, 100% { transform: translateY(0); opacity: .4; } 30% { transform: translateY(-3px); opacity: 1; } }

/* -- Feature cards ------------------------------------------------------- */
.feature-card { transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease; }
.feature-card:hover {
    transform: translateY(-4px);
    border-color: color-mix(in srgb, var(--brand) 45%, var(--border)) !important;
    box-shadow: 0 18px 40px -16px rgba(0,0,0,.4);
}

/* -- Plan cards ---------------------------------------------------------- */
.plan-card { transition: transform .25s ease, box-shadow .25s ease; }
.plan-card:hover { transform: translateY(-4px); }
.plan-popular { transform: scale(1.02); }
.plan-popular:hover { transform: scale(1.02) translateY(-4px); }

/* -- Stat cards ---------------------------------------------------------- */
.stat-card { transition: transform .2s ease, border-color .2s ease; }
.stat-card:hover {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--brand) 30%, var(--border)) !important;
}

/* -- Step number -------------------------------------------------------- */
.step-num { transition: transform .3s ease, box-shadow .3s ease; }
.step-num:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px color-mix(in srgb, var(--brand) 40%, transparent); }

/* -- Buttons shimmer ---------------------------------------------------- */
.btn-primary { position: relative; overflow: hidden; transition: opacity .2s, transform .15s; }
.btn-primary::after { content: ''; position: absolute; inset: 0; background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,.22) 50%, transparent 60%); transform: translateX(-100%); transition: transform .6s ease; }
.btn-primary:hover::after { transform: translateX(100%); }
.btn-primary:hover { transform: translateY(-1px); }

/* -- FAQ accordion ------------------------------------------------------ */
.faq-item:hover { border-color: color-mix(in srgb, var(--brand) 30%, var(--border)) !important; }
details summary::-webkit-details-marker { display: none; }
.faq-chevron { transition: transform .25s ease; }
details[open] .faq-chevron { transform: rotate(180deg); }
details[open] { background: color-mix(in srgb, var(--brand) 3%, var(--card)) !important; }
</style>