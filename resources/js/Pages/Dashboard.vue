<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    FolderOpen, MessageSquare, Zap, TrendingUp,
    Plus, ArrowRight, BarChart2, Bot, Clock
} from 'lucide-vue-next'

const page  = usePage()
const auth  = computed(() => page.props.auth)
const quota = computed(() => page.props.quota)
const projects = computed(() => page.props.sidebar_projects ?? [])

const recentChats = computed(() => {
    const chats = []
    projects.value.forEach(p => {
        (p.chats ?? []).forEach(c => {
            chats.push({ ...c, project_name: p.name, project_id: p.id })
        })
    })
    return chats
        .sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at))
        .slice(0, 5)
})

function formatTime(date) {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    })
}

function fmt(n) {
    if (!n && n !== 0) return '0'
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
    return String(n)
}
</script>

<template>
    <AppLayout title="Dashboard">
        <div class="max-w-5xl mx-auto px-6 py-8">

            <!-- Welcome -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-white">
                    Welcome back, {{ auth?.user?.name }} 👋
                </h1>
                <p class="text-slate-400 mt-1 text-sm">
                    Here's what's happening in your workspace.
                </p>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

                <!-- Total projects -->
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-slate-400 text-xs font-medium">Projects</span>
                        <div class="w-8 h-8 bg-indigo-600/20 rounded-lg flex items-center justify-center">
                            <FolderOpen class="w-4 h-4 text-indigo-400" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-white">{{ projects.length }}</p>
                    <p class="text-slate-500 text-xs mt-1">active workspaces</p>
                </div>

                <!-- Total chats -->
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-slate-400 text-xs font-medium">Chats</span>
                        <div class="w-8 h-8 bg-green-600/20 rounded-lg flex items-center justify-center">
                            <MessageSquare class="w-4 h-4 text-green-400" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-white">
                        {{ projects.reduce((sum, p) => sum + (p.chats?.length ?? 0), 0) }}
                    </p>
                    <p class="text-slate-500 text-xs mt-1">recent chats loaded</p>
                </div>

                <!-- Tokens used -->
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-slate-400 text-xs font-medium">Tokens Used</span>
                        <div class="w-8 h-8 bg-amber-600/20 rounded-lg flex items-center justify-center">
                            <Zap class="w-4 h-4 text-amber-400" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-white">{{ fmt(quota?.used ?? 0) }}</p>
                    <p class="text-slate-500 text-xs mt-1">this month</p>
                </div>

                <!-- Tokens remaining -->
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-slate-400 text-xs font-medium">Remaining</span>
                        <div class="w-8 h-8 bg-purple-600/20 rounded-lg flex items-center justify-center">
                            <TrendingUp class="w-4 h-4 text-purple-400" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-white">{{ fmt(quota?.remaining ?? 0) }}</p>
                    <p class="text-slate-500 text-xs mt-1">tokens left</p>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Recent chats -->
                <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <MessageSquare class="w-4 h-4 text-slate-400" />
                            <h2 class="text-white font-semibold text-sm">Recent Chats</h2>
                        </div>
                        <Link
                            :href="route('projects.index')"
                            class="text-indigo-400 hover:text-indigo-300 text-xs flex items-center gap-1"
                        >
                            All Projects <ArrowRight class="w-3 h-3" />
                        </Link>
                    </div>

                    <div v-if="recentChats.length === 0" class="text-center py-8">
                        <MessageSquare class="w-8 h-8 text-slate-700 mx-auto mb-2" />
                        <p class="text-slate-500 text-sm">No chats yet.</p>
                    </div>

                    <div v-else class="space-y-2">
                        <Link
                            v-for="chat in recentChats"
                            :key="chat.id"
                            :href="route('projects.chats.show', [chat.project_id, chat.id])"
                            class="flex items-center justify-between p-3 rounded-lg hover:bg-slate-800 transition-colors group"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-7 h-7 bg-indigo-600/20 rounded-lg flex items-center justify-center shrink-0">
                                    <Bot class="w-3.5 h-3.5 text-indigo-400" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-white text-sm font-medium truncate">
                                        {{ chat.title || 'New Chat' }}
                                    </p>
                                    <p class="text-slate-500 text-xs">{{ chat.project_name }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 text-slate-600 text-xs shrink-0">
                                <Clock class="w-3 h-3" />
                                {{ formatTime(chat.updated_at) }}
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Quick actions + MIS notice -->
                <div class="space-y-4">

                    <!-- Quick action -->
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                        <h2 class="text-white font-semibold text-sm mb-4">Quick Start</h2>
                        <Link
                            :href="route('projects.index')"
                            class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
                        >
                            <Plus class="w-4 h-4" />
                            New Project
                        </Link>
                    </div>

                    <!-- MIS notice -->
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2">
                            <BarChart2 class="w-4 h-4 text-indigo-400" />
                            <h2 class="text-white font-semibold text-sm">Analytics</h2>
                        </div>
                        <p class="text-slate-500 text-xs leading-relaxed">
                            Advanced MIS dashboard coming soon. View your usage stats in the
                            <a href="/usage" class="text-indigo-400 hover:underline">Usage</a> page.
                        </p>
                    </div>

                </div>
            </div>

        </div>
    </AppLayout>
</template>