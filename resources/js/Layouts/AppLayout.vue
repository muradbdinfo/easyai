<script setup>
// FILE: resources/js/Layouts/AppLayout.vue
import { ref, computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import {
    LayoutDashboard, FolderOpen, CreditCard, BarChart2,
    LogOut, Menu, X, Bot, FileText, Users, Settings,
    User, Lock, Plug, Zap
} from 'lucide-vue-next'
import TokenBar from '@/Components/TokenBar.vue'
import UpgradeModal from '@/Components/UpgradeModal.vue'
import Sidebar from '@/Components/Sidebar.vue'
import NotificationBell from '@/Components/NotificationBell.vue'

defineProps({ title: String })

const page            = usePage()
const auth            = computed(() => page.props.auth)
const quota           = computed(() => page.props.quota)
const tenant          = computed(() => page.props.tenant)
const theme           = computed(() => page.props.theme ?? { brand: '#6366f1', tenant_mode: 'dark' })
const sidebarOpen     = ref(false)
const showUpgrade     = ref(false)
const sidebarProjects = computed(() => page.props.sidebar_projects ?? [])

const isAdmin      = computed(() => ['admin', 'superadmin'].includes(auth.value?.user?.role))
const light        = computed(() => theme.value.tenant_mode === 'light')

// ── Addon helpers ─────────────────────────────────────────────────────────
const activeAddons = computed(() => page.props.active_addons ?? [])
const hasAddon     = (slug) => activeAddons.value.includes(slug)

const vars = computed(() => ({
    '--brand':       theme.value.brand,
    '--sb-bg':       '#0f172a',
    '--sb-border':   '#1e293b',
    '--sb-text':     '#94a3b8',
    '--app-bg':      light.value ? '#f1f5f9' : '#020617',
    '--bar-bg':      light.value ? '#ffffff' : '#0f172a',
    '--bar-border':  light.value ? '#e2e8f0' : '#1e293b',
    '--text':        light.value ? '#0f172a' : '#f1f5f9',
    '--muted':       light.value ? '#64748b' : '#94a3b8',
    '--card':        light.value ? '#ffffff' : '#0f172a',
    '--card-border': light.value ? '#e2e8f0' : '#1e293b',
}))

function logout()       { router.post(route('logout')) }
function isActive(path) { return page.url.startsWith(path) }

const navStyle   = (path) => isActive(path) ? `background:var(--brand);color:#fff` : `color:var(--sb-text)`
const hoverEnter = (e, path) => { if (!isActive(path)) { e.currentTarget.style.background = 'rgba(255,255,255,.07)'; e.currentTarget.style.color = '#fff' } }
const hoverLeave = (e, path) => { if (!isActive(path)) { e.currentTarget.style.background = ''; e.currentTarget.style.color = '' } }
</script>

<template>
    <div class="min-h-screen flex" :style="vars" style="background:var(--app-bg);color:var(--text)">

        <!-- ── Sidebar ── -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col transition-transform duration-200 md:translate-x-0 md:static md:flex shrink-0"
               style="background:var(--sb-bg);border-right:1px solid var(--sb-border)">

            <!-- Logo -->
            <div class="flex items-center gap-3 px-5 py-5 shrink-0"
                 style="border-bottom:1px solid var(--sb-border)">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 overflow-hidden"
                     style="background:var(--brand)">
                    <img v-if="tenant?.logo_path"
                         :src="`/storage/${tenant.logo_path}`"
                         class="w-full h-full object-contain p-1"/>
                    <Bot v-else class="w-5 h-5 text-white"/>
                </div>
                <div class="min-w-0">
                    <p class="text-white text-sm font-bold truncate">{{ tenant?.name ?? 'EasyAI' }}</p>
                    <p class="text-xs truncate" style="color:var(--sb-text)">{{ auth?.user?.name }}</p>
                </div>
            </div>

            <!-- Top nav -->
            <nav class="px-3 pt-4 pb-2 space-y-0.5 shrink-0">

                <!-- Static nav items -->
                <Link v-for="item in [
                    { href: route('dashboard'),       path: '/dashboard', icon: LayoutDashboard, label: 'Dashboard' },
                    { href: route('projects.index'),  path: '/projects',  icon: FolderOpen,      label: 'Projects'  },
                    { href: route('templates.index'), path: '/templates', icon: FileText,        label: 'Templates' },
                ]" :key="item.label"
                      :href="item.href"
                      class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                      :style="navStyle(item.path)"
                      @mouseenter="e => hoverEnter(e, item.path)"
                      @mouseleave="e => hoverLeave(e, item.path)"
                      @click="sidebarOpen = false">
                    <component :is="item.icon" class="w-4 h-4 shrink-0"/> {{ item.label }}
                </Link>

                <!-- Team: admins only -->
                <Link v-if="isAdmin"
                      :href="route('team.index')"
                      class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                      :style="navStyle('/team')"
                      @mouseenter="e => hoverEnter(e, '/team')"
                      @mouseleave="e => hoverLeave(e, '/team')"
                      @click="sidebarOpen = false">
                    <Users class="w-4 h-4 shrink-0"/> Team
                </Link>

                <!-- Settings -->
                <Link :href="route('settings.index')"
                      class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                      :style="navStyle('/settings')"
                      @mouseenter="e => hoverEnter(e, '/settings')"
                      @mouseleave="e => hoverLeave(e, '/settings')"
                      @click="sidebarOpen = false">
                    <Settings class="w-4 h-4 shrink-0"/>
                    Settings
                    <Lock v-if="!isAdmin" class="w-3 h-3 ml-auto opacity-40"/>
                </Link>

                <!-- Integrations -->
                <Link :href="route('settings.integrations')"
                      class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                      :style="navStyle('/settings/integrations')"
                      @mouseenter="e => hoverEnter(e, '/settings/integrations')"
                      @mouseleave="e => hoverLeave(e, '/settings/integrations')"
                      @click="sidebarOpen = false">
                    <Plug class="w-4 h-4 shrink-0"/> Integrations
                </Link>

                <!-- Agent AI — only if addon active -->
                <Link v-if="hasAddon('agent-ai')"
                      :href="route('agent.settings')"
                      class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                      :style="navStyle('/agent')"
                      @mouseenter="e => hoverEnter(e, '/agent')"
                      @mouseleave="e => hoverLeave(e, '/agent')"
                      @click="sidebarOpen = false">
                    <Zap class="w-4 h-4 shrink-0 text-yellow-400"/> Agent AI
                </Link>

                <!-- n8n Automation — only if addon active -->
                <Link v-if="hasAddon('n8n-automation')"
                      :href="route('n8n.settings')"
                      class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                      :style="navStyle('/n8n')"
                      @mouseenter="e => hoverEnter(e, '/n8n')"
                      @mouseleave="e => hoverLeave(e, '/n8n')"
                      @click="sidebarOpen = false">
                    <Zap class="w-4 h-4 shrink-0 text-orange-400"/> n8n Automation
                </Link>

                <!-- OpenClaw Agent — only if addon active -->
                <Link v-if="hasAddon('openclaw')"
                      :href="route('openclaw.index')"
                      class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                      :style="navStyle('/openclaw')"
                      @mouseenter="e => hoverEnter(e, '/openclaw')"
                      @mouseleave="e => hoverLeave(e, '/openclaw')"
                      @click="sidebarOpen = false">
                    <Bot class="w-4 h-4 shrink-0 text-indigo-400"/> OpenClaw Agent
                </Link>

            </nav>

            <!-- Projects tree -->
            <div class="flex-1 overflow-y-auto py-2"
                 style="border-top:1px solid var(--sb-border)">
                <Sidebar :projects="sidebarProjects"/>
            </div>

            <!-- Bottom nav -->
            <nav class="px-3 py-2 space-y-0.5 shrink-0"
                 style="border-top:1px solid var(--sb-border)">
                <a href="/billing"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                   :style="navStyle('/billing')">
                    <CreditCard class="w-4 h-4 shrink-0"/> Billing
                </a>
                <a href="/usage"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                   :style="navStyle('/usage')">
                    <BarChart2 class="w-4 h-4 shrink-0"/> Usage
                </a>
            </nav>

            <!-- User / logout -->
            <div class="px-3 py-4 shrink-0"
                 style="border-top:1px solid var(--sb-border)">
                <div class="flex items-center gap-3 px-3 py-2 mb-1">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0"
                         style="background:var(--sb-border)">
                        <User class="w-3.5 h-3.5 text-white"/>
                    </div>
                    <div class="min-w-0">
                        <p class="text-white text-xs font-medium truncate">{{ auth?.user?.name }}</p>
                        <p class="text-xs truncate capitalize" style="color:var(--sb-text)">{{ auth?.user?.role }}</p>
                    </div>
                </div>
                <button @click="logout"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-red-500/10 hover:text-red-400"
                        style="color:var(--sb-text)">
                    <LogOut class="w-4 h-4 shrink-0"/> Log Out
                </button>
            </div>
        </aside>

        <!-- Mobile overlay -->
        <div v-if="sidebarOpen"
             class="fixed inset-0 z-30 bg-black/50 md:hidden"
             @click="sidebarOpen = false"/>

        <!-- ── Main ── -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="flex items-center justify-between px-4 py-3 shrink-0"
                    style="background:var(--bar-bg);border-bottom:1px solid var(--bar-border)">
                <div class="flex items-center gap-3">
                    <button class="md:hidden"
                            style="color:var(--muted)"
                            @click="sidebarOpen = !sidebarOpen">
                        <Menu v-if="!sidebarOpen" class="w-5 h-5"/>
                        <X v-else class="w-5 h-5"/>
                    </button>
                    <h1 v-if="title" class="text-sm font-semibold" style="color:var(--text)">{{ title }}</h1>
                </div>
                <div class="flex items-center gap-2">
                    <TokenBar v-if="quota" :quota="quota" @upgrade="showUpgrade = true"/>
                    <NotificationBell/>
                </div>
            </header>

            <div v-if="quota?.exceeded"
                 class="px-4 py-2 text-xs text-center text-amber-400 bg-amber-500/8 border-b border-amber-500/15">
                Token quota exceeded.
                <button @click="showUpgrade = true" class="underline ml-1">Upgrade plan</button>
            </div>

            <main class="flex-1 overflow-auto p-4 md:p-6"><slot/></main>
        </div>

        <UpgradeModal v-if="showUpgrade" @close="showUpgrade = false"/>
    </div>
</template>