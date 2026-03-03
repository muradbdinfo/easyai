<script setup>
import { ref } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import {
    LayoutDashboard, FolderOpen, CreditCard,
    BarChart2, LogOut, Menu, X, User, Bot
} from 'lucide-vue-next'

defineProps({
    title: String,
})

const page = usePage()
const auth = page.props.auth
const sidebarOpen = ref(false)

function logout() {
    router.post(route('logout'))
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 flex">

        <!-- ── Sidebar ── -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 border-r border-slate-800 flex flex-col transition-transform duration-200 md:translate-x-0 md:static md:flex"
        >
            <!-- Logo -->
            <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-800">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center shrink-0">
                    <Bot class="w-5 h-5 text-white" />
                </div>
                <div class="min-w-0">
                    <p class="text-white font-bold text-sm">EasyAI</p>
                    <p class="text-slate-500 text-xs truncate">
                        {{ auth?.user?.name ?? 'Workspace' }}
                    </p>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 px-3 py-4 space-y-1">
                <Link
                    :href="route('dashboard')"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors text-sm font-medium"
                    :class="{ 'bg-slate-800 text-white': $page.url === '/dashboard' }"
                >
                    <LayoutDashboard class="w-4 h-4 shrink-0" />
                    Dashboard
                </Link>

                <Link
                    :href="route('projects.index')"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors text-sm font-medium"
                    :class="{ 'bg-slate-800 text-white': $page.url.startsWith('/projects') }"
                >
                    <FolderOpen class="w-4 h-4 shrink-0" />
                    Projects
                </Link>

                <Link
                    href="/billing"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors text-sm font-medium"
                >
                    <CreditCard class="w-4 h-4 shrink-0" />
                    Billing
                </Link>

                <Link
                    href="/usage"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors text-sm font-medium"
                >
                    <BarChart2 class="w-4 h-4 shrink-0" />
                    Usage
                </Link>
            </nav>

            <!-- User + Logout -->
            <div class="px-3 py-4 border-t border-slate-800">
                <div class="flex items-center gap-3 px-3 py-2 mb-1">
                    <div class="w-7 h-7 bg-slate-700 rounded-full flex items-center justify-center shrink-0">
                        <User class="w-4 h-4 text-slate-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-white text-xs font-medium truncate">
                            {{ auth?.user?.name }}
                        </p>
                        <p class="text-slate-500 text-xs capitalize">
                            {{ auth?.user?.role }}
                        </p>
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

            <!-- Top bar (mobile only) -->
            <header class="md:hidden flex items-center justify-between px-4 py-3 bg-slate-900 border-b border-slate-800">
                <button @click="sidebarOpen = !sidebarOpen" class="text-slate-400 hover:text-white">
                    <Menu v-if="!sidebarOpen" class="w-5 h-5" />
                    <X v-else class="w-5 h-5" />
                </button>
                <span class="text-white font-semibold text-sm">{{ title ?? 'EasyAI' }}</span>
                <div class="w-5" />
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-auto">
                <slot />
            </main>

        </div>
    </div>
</template>