<script setup>
// FILE: resources/js/Pages/Landing/Home.vue
import { onMounted } from 'vue'
import LandingLayout from '@/Layouts/LandingLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({ settings: Object, plans: Array })

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

        <!-- ─── HERO ──────────────────────────────────────────────── -->
        <section class="relative pt-28 pb-20 px-5 text-center overflow-hidden">
            <div class="hero-orb absolute inset-0 pointer-events-none"
                 style="background: radial-gradient(ellipse 75% 55% at 50% -5%, color-mix(in srgb, var(--brand) 22%, transparent), transparent)"/>
            <div class="absolute inset-0 pointer-events-none"
                 style="opacity:.025; background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 32px 32px;"/>

            <div class="relative max-w-4xl mx-auto">

                <div class="hero-badge inline-flex items-center gap-2 text-xs font-semibold px-4 py-1.5 rounded-full mb-8 border"
                     style="background: color-mix(in srgb, var(--brand) 10%, transparent); color: var(--brand); border-color: color-mix(in srgb, var(--brand) 28%, transparent)">
                    <span class="relative flex h-2 w-2">
                        <span class="live-ping absolute inline-flex h-full w-full rounded-full opacity-60"
                              style="background: var(--brand)"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2" style="background: var(--brand)"></span>
                    </span>
                    Powered by Ollama &nbsp;·&nbsp; 100% Private &nbsp;·&nbsp; Self-Hosted
                </div>

                <h1 class="hero-title text-5xl md:text-6xl lg:text-7xl font-extrabold text-white leading-tight tracking-tight mb-6">
                    {{ settings.hero_title }}
                </h1>

                <p class="hero-sub text-lg md:text-xl text-slate-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                    {{ settings.hero_subtitle }}
                </p>

                <div class="hero-ctas flex flex-col sm:flex-row items-center justify-center gap-3">
                    <Link :href="route('register')"
                          class="btn-primary w-full sm:w-auto px-8 py-3.5 text-white font-semibold rounded-xl text-sm"
                          style="background: var(--brand)">
                        {{ settings.hero_cta }} →
                    </Link>
                    <a href="#features"
                       class="w-full sm:w-auto px-8 py-3.5 border border-slate-700 hover:border-slate-500 text-slate-300 hover:text-white font-semibold rounded-xl transition-colors text-sm">
                        See Features ↓
                    </a>
                </div>

                <p class="hero-hint mt-5 text-xs text-slate-600 tracking-wide">
                    No credit card required &nbsp;·&nbsp; Your data stays private &nbsp;·&nbsp; Any server
                </p>

                <div class="hero-stats mt-14 grid grid-cols-3 gap-3 max-w-sm mx-auto">
                    <div v-for="[val, label] in [['100%','Private'],['∞','Projects'],['$0','Setup fee']]"
                         :key="label"
                         class="stat-card bg-slate-900 border border-slate-800 rounded-xl py-3.5 px-2 text-center">
                        <div class="text-xl font-extrabold mb-0.5" style="color: var(--brand)">{{ val }}</div>
                        <div class="text-xs text-slate-500">{{ label }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── FEATURES ─────────────────────────────────────────── -->
        <section id="features" class="py-24 px-5">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-16" data-reveal>
                    <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--brand)">Features</p>
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Everything your team needs</h2>
                    <p class="text-slate-400 max-w-xl mx-auto leading-relaxed">Built for teams that care about privacy, productivity, and control.</p>
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="(f, i) in settings.features" :key="f.title"
                         class="feature-card bg-slate-900 border border-slate-800 rounded-2xl p-6"
                         data-reveal :data-reveal-delay="(i % 6) + 1">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-5"
                             style="background: color-mix(in srgb, var(--brand) 12%, transparent)">
                            {{ f.icon }}
                        </div>
                        <h3 class="text-white font-semibold mb-2">{{ f.title }}</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">{{ f.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── HOW IT WORKS ─────────────────────────────────────── -->
        <section class="py-24 px-5 relative"
                 style="background: linear-gradient(180deg, transparent, color-mix(in srgb, var(--brand) 4%, rgb(15 23 42)) 50%, transparent)">
            <div class="max-w-4xl mx-auto text-center">
                <div data-reveal>
                    <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--brand)">Process</p>
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Up and running in minutes</h2>
                    <p class="text-slate-400 mb-16 max-w-md mx-auto">Deploy on your own server. No vendor lock-in. No monthly AI bills.</p>
                </div>
                <div class="grid md:grid-cols-3 gap-10 relative">
                    <div class="hidden md:block absolute top-5 left-[22%] right-[22%] h-px"
                         style="background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--brand) 35%, transparent) 50%, transparent)"/>
                    <div v-for="([num, title, desc], i) in [
                             ['1','Deploy','Clone, configure .env, run migrations. Works on VPS or cPanel.'],
                             ['2','Invite Your Team','Register workspace, invite teammates, create projects.'],
                             ['3','Start Chatting','Connect Ollama models and work completely privately.'],
                         ]" :key="i"
                         data-reveal :data-reveal-delay="i + 1">
                        <div class="step-num w-12 h-12 rounded-2xl flex items-center justify-center text-white font-bold text-lg mx-auto mb-5 relative z-10"
                             style="background: var(--brand)">{{ num }}</div>
                        <h3 class="text-white font-semibold mb-2">{{ title }}</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">{{ desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── PRICING PREVIEW ──────────────────────────────────── -->
        <section v-if="settings.show_pricing && plans && plans.length" class="py-24 px-5">
            <div class="max-w-5xl mx-auto">
                <div class="text-center mb-14" data-reveal>
                    <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--brand)">Pricing</p>
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Simple, transparent pricing</h2>
                    <p class="text-slate-400">Pay with bKash, Nagad, Stripe, or Cash on Delivery.</p>
                </div>
                <div class="grid md:grid-cols-3 gap-5">
                    <div v-for="(plan, i) in plans" :key="plan.id"
                         class="plan-card relative rounded-2xl p-7 border"
                         :class="i === 1 ? 'bg-slate-800' : 'bg-slate-900'"
                         :style="i === 1
                             ? `border-color: var(--brand); box-shadow: 0 0 0 1px color-mix(in srgb, var(--brand) 25%, transparent), 0 20px 40px -12px rgba(0,0,0,.4)`
                             : 'border-color: rgb(30 41 59)'"
                         data-reveal :data-reveal-delay="i + 1">
                        <div v-if="i === 1"
                             class="absolute -top-3.5 left-1/2 -translate-x-1/2 text-white text-xs font-bold px-4 py-1 rounded-full"
                             style="background: var(--brand)">⭐ Most Popular</div>
                        <p class="text-slate-500 text-xs uppercase tracking-widest mb-3">{{ plan.name }}</p>
                        <div class="flex items-end gap-1 mb-1">
                            <span class="text-5xl font-extrabold text-white">${{ plan.price }}</span>
                            <span class="text-slate-500 text-sm pb-2">/mo</span>
                        </div>
                        <p class="text-xs font-semibold mb-7" style="color: var(--brand)">
                            {{ Math.round(plan.monthly_token_limit / 1000) }}K tokens / month
                        </p>
                        <ul v-if="plan.features" class="space-y-2.5 mb-8">
                            <li v-for="f in plan.features" :key="f" class="flex items-center gap-2 text-sm text-slate-300">
                                <svg class="w-4 h-4 shrink-0" :style="{color: settings.primary_color}"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ f }}
                            </li>
                        </ul>
                        <Link :href="route('register')"
                              class="btn-primary block text-center py-3 rounded-xl text-sm font-bold"
                              :style="i === 1 ? 'background: var(--brand); color:#fff' : 'background:rgb(30 41 59); color:rgb(148 163 184)'">
                            Get Started
                        </Link>
                    </div>
                </div>
                <p class="text-center mt-8 text-sm">
                    <Link :href="route('landing.pricing')" class="hover:underline" style="color: var(--brand)">
                        Compare all plan features →
                    </Link>
                </p>
            </div>
        </section>

        <!-- ─── FAQ ──────────────────────────────────────────────── -->
        <section v-if="settings.faq && settings.faq.length" id="faq" class="py-24 px-5">
            <div class="max-w-2xl mx-auto">
                <div class="text-center mb-12" data-reveal>
                    <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--brand)">FAQ</p>
                    <h2 class="text-3xl font-bold text-white">Frequently asked questions</h2>
                </div>
                <div class="space-y-2">
                    <details v-for="(item, i) in settings.faq" :key="item.q"
                             class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden hover:border-slate-700 transition-colors"
                             data-reveal :data-reveal-delay="Math.min(i + 1, 5)">
                        <summary class="flex items-center justify-between gap-4 px-5 py-4 text-white font-medium cursor-pointer list-none select-none">
                            <span class="text-left text-sm">{{ item.q }}</span>
                            <svg class="faq-chevron w-4 h-4 text-slate-400 shrink-0"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </summary>
                        <div class="px-5 pb-4 border-t border-slate-800">
                            <p class="text-slate-400 text-sm leading-relaxed pt-3">{{ item.a }}</p>
                        </div>
                    </details>
                </div>
            </div>
        </section>

        <!-- ─── CTA ──────────────────────────────────────────────── -->
        <section class="py-24 px-5">
            <div class="max-w-2xl mx-auto" data-reveal>
                <div class="relative rounded-3xl p-12 border text-center overflow-hidden"
                     style="background: color-mix(in srgb, var(--brand) 6%, rgb(15 23 42)); border-color: color-mix(in srgb, var(--brand) 25%, transparent)">
                    <div class="absolute inset-0 pointer-events-none"
                         style="background: radial-gradient(ellipse 80% 60% at 50% 0%, color-mix(in srgb, var(--brand) 14%, transparent), transparent)"/>
                    <div class="relative">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-6 text-2xl"
                             style="background: color-mix(in srgb, var(--brand) 18%, transparent)">🚀</div>
                        <h2 class="text-3xl font-bold text-white mb-4">Ready to own your AI workspace?</h2>
                        <p class="text-slate-400 mb-8 text-sm leading-relaxed max-w-md mx-auto">
                            Start your free trial. No credit card required. Deploy on your own server.
                        </p>
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                            <Link :href="route('register')"
                                  class="btn-primary w-full sm:w-auto px-8 py-3.5 text-white font-semibold rounded-xl text-sm"
                                  style="background: var(--brand)">
                                {{ settings.hero_cta }} →
                            </Link>
                            <Link v-if="settings.show_contact" :href="route('landing.contact')"
                                  class="w-full sm:w-auto px-8 py-3.5 border border-slate-700 hover:border-slate-500 text-slate-300 hover:text-white font-semibold rounded-xl transition-colors text-sm">
                                Contact Us
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </LandingLayout>
</template>

<style scoped>
/* ── Reveal on scroll ── */
[data-reveal] { opacity:0; transform:translateY(22px); transition:opacity .6s cubic-bezier(.4,0,.2,1), transform .6s cubic-bezier(.4,0,.2,1); }
[data-reveal].revealed { opacity:1; transform:none; }
[data-reveal-delay="1"] { transition-delay:.1s; }
[data-reveal-delay="2"] { transition-delay:.2s; }
[data-reveal-delay="3"] { transition-delay:.3s; }
[data-reveal-delay="4"] { transition-delay:.4s; }
[data-reveal-delay="5"] { transition-delay:.5s; }
[data-reveal-delay="6"] { transition-delay:.6s; }

/* ── Hero load animations ── */
.hero-badge  { animation: fadeUp .65s cubic-bezier(.4,0,.2,1) .10s both; }
.hero-title  { animation: fadeUp .65s cubic-bezier(.4,0,.2,1) .25s both; }
.hero-sub    { animation: fadeUp .65s cubic-bezier(.4,0,.2,1) .40s both; }
.hero-ctas   { animation: fadeUp .65s cubic-bezier(.4,0,.2,1) .55s both; }
.hero-hint   { animation: fadeUp .65s cubic-bezier(.4,0,.2,1) .65s both; }
.hero-stats  { animation: fadeUp .65s cubic-bezier(.4,0,.2,1) .80s both; }
@keyframes fadeUp { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:none; } }

/* ── Ambient orb breathe ── */
.hero-orb { animation: orbPulse 6s ease-in-out infinite; }
@keyframes orbPulse { 0%,100% { opacity:.2; transform:scale(1); } 50% { opacity:.3; transform:scale(1.05); } }

/* ── Badge live dot ── */
.live-ping { animation: ping 1.5s cubic-bezier(0,0,.2,1) infinite; }
@keyframes ping { 75%,100% { transform:scale(2); opacity:0; } }

/* ── Feature cards ── */
.feature-card { transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
.feature-card:hover { transform:translateY(-4px); box-shadow:0 16px 36px -12px rgba(0,0,0,.55); border-color: color-mix(in srgb, var(--brand) 45%, transparent); }

/* ── Plan cards ── */
.plan-card { transition: transform .25s ease, box-shadow .25s ease; }
.plan-card:hover { transform:translateY(-3px); }

/* ── Stat cards ── */
.stat-card { transition: transform .2s ease; }
.stat-card:hover { transform: translateY(-2px); }

/* ── Buttons shimmer ── */
.btn-primary { position:relative; overflow:hidden; transition: opacity .2s, transform .15s; }
.btn-primary::after { content:''; position:absolute; inset:0; background:linear-gradient(105deg, transparent 40%, rgba(255,255,255,.18) 50%, transparent 60%); transform:translateX(-100%); transition:transform .5s ease; }
.btn-primary:hover::after { transform:translateX(100%); }
.btn-primary:hover { opacity:.92; transform:translateY(-1px); }

/* ── Step number hover glow ── */
.step-num { transition: box-shadow .3s ease; }
.step-num:hover { box-shadow: 0 0 0 8px color-mix(in srgb, var(--brand) 18%, transparent); }

/* ── FAQ chevron ── */
details summary::-webkit-details-marker { display:none; }
.faq-chevron { transition: transform .25s ease; }
details[open] .faq-chevron { transform:rotate(180deg); }
</style>