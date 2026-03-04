<script setup>
import { ref, computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import {
    LayoutDashboard, FolderOpen, CreditCard, BarChart2,
    LogOut, Menu, X, User, Bot, FileText
} from 'lucide-vue-next'
import TokenBar from '@/Components/TokenBar.vue'
import UpgradeModal from '@/Components/UpgradeModal.vue'
import Sidebar from '@/Components/Sidebar.vue'
import NotificationBell from '@/Components/NotificationBell.vue'

defineProps({ title: String })

const page        = usePage()
const auth        = computed(() => page.props.auth)
const quota       = computed(() => page.props.quota)
const sidebarOpen = ref(false)
const showUpgrade = ref(false)

const sidebarProjects = computed(() => page.props.sidebar_projects ?? [])

function logout() {
    router.post(route('logout'))
}

function isActive(path) {
    return page.url.startsWith(path)
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 flex">

        <!-- Sidebar -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 border-r border-slate-800 flex flex-col transition-transform duration-200 md:translate-x-0 md:static md:flex shrink-0"
        >
            <!-- Logo -->
            <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-800 shrink-0">
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

            <!-- Top nav links -->
            <nav class="px-3 pt-4 pb-2 space-y-0.5 shrink-0">
                <Link
                    :href="route('dashboard')"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="isActive('/dashboard')
                        ? 'bg-indigo-600 text-white'
                        : 'text-slate-400 hover:text-white hover:bg-slate-800'"
                    @click="sidebarOpen = false"
                >
                    <LayoutDashboard class="w-4 h-4 shrink-0" />
                    Dashboard
                </Link>

                <Link
                    :href="route('projects.index')"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="isActive('/projects')
                        ? 'bg-indigo-600 text-white'
                        : 'text-slate-400 hover:text-white hover:bg-slate-800'"
                    @click="sidebarOpen = false"
                >
                    <FolderOpen class="w-4 h-4 shrink-0" />
                    Projects
                </Link>

                <Link
                    :href="route('templates.index')"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="isActive('/templates')
                        ? 'bg-indigo-600 text-white'
                        : 'text-slate-400 hover:text-white hover:bg-slate-800'"
                    @click="sidebarOpen = false"
                >
                    <FileText class="w-4 h-4 shrink-0" />
                    Templates
                </Link>
            </nav>

            <!-- Projects + Chats sidebar tree -->
            <div class="flex-1 overflow-y-auto border-t border-slate-800/50 py-2">
                <Sidebar :projects="sidebarProjects" />
            </div>

            <!-- Bottom nav -->
            <nav class="px-3 py-2 border-t border-slate-800 space-y-0.5 shrink-0">
                <a
                    href="/billing"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="isActive('/billing')
                        ? 'bg-indigo-600 text-white'
                        : 'text-slate-400 hover:text-white hover:bg-slate-800'"
                >
                    <CreditCard class="w-4 h-4 shrink-0" />
                    Billing
                </a>
                <a
                    href="/usage"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="isActive('/usage')
                        ? 'bg-indigo-600 text-white'
                        : 'text-slate-400 hover:text-white hover:bg-slate-800'"
                >
                    <BarChart2 class="w-4 h-4 shrink-0" />
                    Usage
                </a>
            </nav>

            <!-- Token Bar -->
            <div class="border-t border-slate-800 shrink-0">
                <TokenBar @upgrade="showUpgrade = true" />
            </div>

            <!-- User + Logout -->
            <div class="px-3 py-3 border-t border-slate-800 shrink-0">
                <div class="flex items-center gap-3 px-3 py-2 mb-1">
                    <div class="w-7 h-7 bg-slate-700 rounded-full flex items-center justify-center shrink-0">
                        <User class="w-4 h-4 text-slate-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-white text-xs font-medium truncate">{{ auth?.user?.name }}</p>
                        <p class="text-slate-500 text-xs capitalize">{{ auth?.user?.role }}</p>
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

        <!-- Main -->
        <div class="flex-1 flex flex-col min-w-0">

            <!-- Mobile top bar -->
            <header class="md:hidden flex items-center justify-between px-4 py-3 bg-slate-900 border-b border-slate-800 shrink-0">
                <button @click="sidebarOpen = !sidebarOpen" class="text-slate-400 hover:text-white">
                    <Menu v-if="!sidebarOpen" class="w-5 h-5" />
                    <X v-else class="w-5 h-5" />
                </button>
                <div class="flex items-center gap-2">
                    <Bot class="w-4 h-4 text-indigo-400" />
                    <span class="text-white font-semibold text-sm">{{ title ?? 'EasyAI' }}</span>
                </div>
                <NotificationBell />
            </header>


            <!-- Desktop top bar -->
<header class="hidden md:flex items-center justify-end px-6 py-3 bg-slate-950 border-b border-slate-800 shrink-0">
    <NotificationBell />
</header>

            <!-- Page content -->
            <main class="flex-1 overflow-auto">
                <slot />
            </main>
        </div>

        <!-- Upgrade Modal -->
        <UpgradeModal :show="showUpgrade" @close="showUpgrade = false" />

    </div>
</template>