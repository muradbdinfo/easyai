<script setup>
import { ref } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import {
    LayoutDashboard, Users, Package, CreditCard,
    BarChart2, Settings, Shield, LogOut, Menu,
    X, Bell, ChevronRight, Activity
} from 'lucide-vue-next'

defineProps({ title: String })

const page        = usePage()
const auth        = page.props.auth
const sidebarOpen = ref(false)

function logout() {
    router.post(route('logout'))
}

function isActive(path) {
    return page.url.startsWith(path) || page.url === path
}

const navItems = [
    { label: 'Dashboard', icon: LayoutDashboard, href: '/',         name: 'admin.dashboard'     },
    { label: 'Tenants',   icon: Users,            href: '/tenants',  name: 'admin.tenants.index' },
    { label: 'Plans',     icon: Package,          href: '/plans',    name: 'admin.plans.index'   },
    { label: 'Payments',  icon: CreditCard,       href: '/payments', name: 'admin.payments.index'},
    { label: 'Usage',     icon: BarChart2,        href: '/usage',    name: 'admin.usage.index'   },
]
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex">

        <!-- ── Sidebar ── -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-800 flex flex-col transition-transform duration-200 md:translate-x-0 md:static md:flex shrink-0"
        >
            <!-- Logo -->
            <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-700">
                <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center shrink-0">
                    <Shield class="w-5 h-5 text-white" />
                </div>
                <div>
                    <p class="text-white font-bold text-sm">EasyAI Admin</p>
                    <p class="text-slate-400 text-xs">Super Admin Panel</p>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="route(item.name)"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="isActive(item.href)
                        ? 'bg-indigo-600 text-white'
                        : 'text-slate-400 hover:text-white hover:bg-slate-700'"
                    @click="sidebarOpen = false"
                >
                    <component :is="item.icon" class="w-4 h-4 shrink-0" />
                    {{ item.label }}
                </Link>
            </nav>

            <!-- User + Logout -->
            <div class="px-3 py-4 border-t border-slate-700">
                <div class="flex items-center gap-3 px-3 py-2 mb-2">
                    <div class="w-7 h-7 bg-slate-600 rounded-full flex items-center justify-center shrink-0">
                        <Shield class="w-4 h-4 text-slate-300" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-white text-xs font-medium truncate">{{ auth?.user?.name }}</p>
                        <p class="text-slate-400 text-xs">Super Admin</p>
                    </div>
                </div>
                <button
                    @click="logout"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-400/10 transition-colors text-sm font-medium"
                >
                    <LogOut class="w-4 h-4 shrink-0" />
                    Log Out
                </button>
            </div>
        </aside>

        <!-- Mobile overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-30 bg-black/50 md:hidden"
            @click="sidebarOpen = false"
        />

        <!-- ── Main ── -->
        <div class="flex-1 flex flex-col min-w-0">

            <!-- Top bar -->
            <header class="flex items-center justify-between px-6 py-4 bg-white border-b border-slate-200 shrink-0">
                <div class="flex items-center gap-3">
                    <button
                        class="md:hidden text-slate-500 hover:text-slate-700"
                        @click="sidebarOpen = !sidebarOpen"
                    >
                        <Menu v-if="!sidebarOpen" class="w-5 h-5" />
                        <X v-else class="w-5 h-5" />
                    </button>
                    <div class="flex items-center gap-2 text-sm text-slate-500">
                        <Shield class="w-4 h-4 text-indigo-500" />
                        <span class="font-semibold text-slate-700">{{ title ?? 'Admin Panel' }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                        <Bell class="w-4 h-4" />
                    </button>
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <Activity class="w-4 h-4 text-green-500" />
                        <span>System OK</span>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-auto p-6">
                <slot />
            </main>
        </div>

    </div>
</template>