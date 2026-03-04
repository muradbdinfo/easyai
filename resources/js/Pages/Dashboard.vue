<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    FolderOpen, MessageSquare, Zap, TrendingUp,
    Plus, ArrowRight, BarChart2, Clock, CheckCircle, AlertCircle
} from 'lucide-vue-next'

const props = defineProps({
    stats:        { type: Object,  default: () => ({}) },
    daily_usage:  { type: Array,   default: () => [] },
    recent_chats: { type: Array,   default: () => [] },
})

const page  = usePage()
const auth  = computed(() => page.props.auth)
const quota = computed(() => page.props.quota)

function fmt(n) {
    if (!n && n !== 0) return '0'
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
    return String(n)
}

function timeAgo(date) {
    const d   = new Date(date)
    const now = new Date()
    const s   = Math.floor((now - d) / 1000)
    if (s < 60)   return 'just now'
    if (s < 3600) return Math.floor(s / 60) + 'm ago'
    if (s < 86400) return Math.floor(s / 3600) + 'h ago'
    return Math.floor(s / 86400) + 'd ago'
}

const maxTokens = computed(() =>
    Math.max(...props.daily_usage.map(d => d.tokens), 1)
)

function barHeight(tokens) {
    return Math.max(4, Math.round((tokens / maxTokens.value) * 80))
}

const quotaPct = computed(() => quota.value?.percent ?? 0)
const quotaColor = computed(() => {
    if (quotaPct.value >= 90) return 'bg-red-500'
    if (quotaPct.value >= 70) return 'bg-amber-500'
    return 'bg-indigo-500'
})
</script>

<template>
    <AppLayout title="Dashboard">
        <div class="max-w-5xl mx-auto px-6 py-8">

            <!-- Welcome -->
            <div class="mb-8">
                <h1 class="text-xl font-bold text-white">
                    Welcome back, {{ auth?.user?.name }} 👋
                </h1>
                <p class="text-slate-500 text-sm mt-0.5">Here's your workspace overview.</p>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

                <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-slate-500 text-xs font-medium">Projects</span>
                        <div class="w-7 h-7 bg-indigo-600/20 rounded-lg flex items-center justify-center">
                            <FolderOpen class="w-3.5 h-3.5 text-indigo-400" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-white">{{ stats.total_projects ?? 0 }}</p>
                    <p class="text-slate-600 text-xs mt-1">total workspaces</p>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-slate-500 text-xs font-medium">Chats</span>
                        <div class="w-7 h-7 bg-green-600/20 rounded-lg flex items-center justify-center">
                            <MessageSquare class="w-3.5 h-3.5 text-green-400" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-white">{{ stats.total_chats ?? 0 }}</p>
                    <p class="text-slate-600 text-xs mt-1">{{ stats.open_chats ?? 0 }} open</p>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-slate-500 text-xs font-medium">Tokens Today</span>
                        <div class="w-7 h-7 bg-amber-600/20 rounded-lg flex items-center justify-center">
                            <Zap class="w-3.5 h-3.5 text-amber-400" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-white">{{ fmt(stats.tokens_today ?? 0) }}</p>
                    <p class="text-slate-600 text-xs mt-1">{{ fmt(stats.tokens_month ?? 0) }} this month</p>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-slate-500 text-xs font-medium">Messages</span>
                        <div class="w-7 h-7 bg-purple-600/20 rounded-lg flex items-center justify-center">
                            <TrendingUp class="w-3.5 h-3.5 text-purple-400" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-white">{{ fmt(stats.messages_month ?? 0) }}</p>
                    <p class="text-slate-600 text-xs mt-1">this month</p>
                </div>

            </div>

            <!-- Main grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- 7-day chart + quota -->
                <div class="lg:col-span-2 space-y-4">

                    <!-- 7-day token bar chart -->
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-5">
                            <BarChart2 class="w-4 h-4 text-indigo-400" />
                            <h2 class="text-white font-semibold text-sm">Token Usage — Last 7 Days</h2>
                        </div>

                        <div v-if="daily_usage.every(d => d.tokens === 0)"
                             class="flex flex-col items-center justify-center py-8 text-slate-600">
                            <BarChart2 class="w-8 h-8 mb-2 opacity-30" />
                            <p class="text-sm">No usage yet this week</p>
                        </div>

                        <div v-else class="flex items-end justify-between gap-2 h-24">
                            <div
                                v-for="day in daily_usage"
                                :key="day.date"
                                class="flex-1 flex flex-col items-center gap-1"
                            >
                                <span class="text-slate-600 text-xs">
                                    {{ day.tokens > 0 ? fmt(day.tokens) : '' }}
                                </span>
                                <div class="w-full flex items-end justify-center">
                                    <div
                                        class="w-full rounded-t-sm transition-all"
                                        :class="day.tokens > 0 ? 'bg-indigo-500' : 'bg-slate-800'"
                                        :style="{ height: barHeight(day.tokens) + 'px' }"
                                    />
                                </div>
                                <span class="text-slate-600 text-xs">{{ day.label }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quota bar -->
                    <div v-if="quota" class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <Zap class="w-4 h-4 text-indigo-400" />
                                <h2 class="text-white font-semibold text-sm">Token Quota</h2>
                            </div>
                            <span
                                class="text-xs font-medium px-2 py-0.5 rounded-full"
                                :class="quotaPct >= 90 ? 'bg-red-500/20 text-red-400' : quotaPct >= 70 ? 'bg-amber-500/20 text-amber-400' : 'bg-indigo-500/20 text-indigo-400'"
                            >
                                {{ quotaPct.toFixed(1) }}% used
                            </span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-2 mb-2">
                            <div
                                class="h-2 rounded-full transition-all"
                                :class="quotaColor"
                                :style="{ width: Math.min(quotaPct, 100) + '%' }"
                            />
                        </div>
                        <div class="flex justify-between text-xs text-slate-500">
                            <span>{{ fmt(quota.used) }} used</span>
                            <span>{{ fmt(quota.remaining) }} remaining</span>
                        </div>
                        <div v-if="quota.exceeded" class="mt-3 p-2 bg-red-500/10 border border-red-500/20 rounded-lg flex items-center gap-2">
                            <AlertCircle class="w-3.5 h-3.5 text-red-400 shrink-0" />
                            <span class="text-red-400 text-xs">Quota exceeded. </span>
                            <a href="/billing/plans" class="text-red-300 text-xs underline">Upgrade plan</a>
                        </div>
                    </div>

                </div>

                <!-- Right col -->
                <div class="space-y-4">

                    <!-- Recent chats -->
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-white font-semibold text-sm">Recent Chats</h2>
                            <Link :href="route('projects.index')" class="text-indigo-400 text-xs hover:text-indigo-300">
                                All
                            </Link>
                        </div>

                        <div v-if="!recent_chats.length" class="text-center py-6">
                            <MessageSquare class="w-7 h-7 text-slate-700 mx-auto mb-2" />
                            <p class="text-slate-600 text-xs">No chats yet</p>
                        </div>

                        <div v-else class="space-y-2">
                            <Link
                                v-for="chat in recent_chats"
                                :key="chat.id"
                                :href="route('projects.chats.show', [chat.project_id, chat.id])"
                                class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-800 transition-colors group"
                            >
                                <div class="w-6 h-6 rounded-md flex items-center justify-center shrink-0 mt-0.5"
                                     :class="chat.status === 'open' ? 'bg-green-600/20' : 'bg-slate-700'">
                                    <MessageSquare class="w-3 h-3"
                                        :class="chat.status === 'open' ? 'text-green-400' : 'text-slate-500'" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-slate-300 text-xs font-medium truncate group-hover:text-white">
                                        {{ chat.title || 'New Chat' }}
                                    </p>
                                    <p class="text-slate-600 text-xs mt-0.5">
                                        {{ chat.project?.name }} · {{ timeAgo(chat.updated_at) }}
                                    </p>
                                </div>
                                <ArrowRight class="w-3 h-3 text-slate-700 group-hover:text-slate-400 shrink-0 mt-1" />
                            </Link>
                        </div>
                    </div>

                    <!-- Quick actions -->
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                        <h2 class="text-white font-semibold text-sm mb-3">Quick Start</h2>
                        <Link
                            :href="route('projects.index')"
                            class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
                        >
                            <Plus class="w-4 h-4" />
                            New Project
                        </Link>
                    </div>

                </div>
            </div>

        </div>
    </AppLayout>
</template>