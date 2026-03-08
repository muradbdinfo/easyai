<script setup>
// FILE: resources/js/Pages/Dashboard.vue
import { computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    FolderOpen, MessageSquare, Zap, TrendingUp,
    Plus, ArrowRight, BarChart2, Clock,
    AlertCircle, CreditCard, Star, CheckCircle,
} from 'lucide-vue-next'

const props = defineProps({
    stats:        { type: Object, default: () => ({}) },
    daily_usage:  { type: Array,  default: () => [] },
    recent_chats: { type: Array,  default: () => [] },
    plans:        { type: Array,  default: () => [] },
})

const page  = usePage()
const auth  = computed(() => page.props.auth)
const quota = computed(() => page.props.quota)

// Current plan name from shared auth.tenant (set in HandleInertiaRequests)
const currentPlanName = computed(() => auth.value?.tenant?.plan ?? 'Starter')

function fmt(n) {
    if (!n && n !== 0) return '0'
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
    return String(n)
}

function timeAgo(date) {
    const s = Math.floor((new Date() - new Date(date)) / 1000)
    if (s < 60)    return 'just now'
    if (s < 3600)  return Math.floor(s / 60) + 'm ago'
    if (s < 86400) return Math.floor(s / 3600) + 'h ago'
    return Math.floor(s / 86400) + 'd ago'
}

const maxTokens = computed(() => Math.max(...props.daily_usage.map(d => d.tokens), 1))
const barHeight = (tokens) => Math.max(4, Math.round((tokens / maxTokens.value) * 80))

const quotaPct   = computed(() => quota.value?.percent ?? 0)
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

            <!-- ── Stat Cards ── -->
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
                        <div class="w-7 h-7 bg-violet-600/20 rounded-lg flex items-center justify-center">
                            <MessageSquare class="w-3.5 h-3.5 text-violet-400" />
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
                    <p class="text-2xl font-bold text-white">{{ fmt(stats.tokens_today) }}</p>
                    <p class="text-slate-600 text-xs mt-1">{{ fmt(stats.tokens_month ?? 0) }} this month</p>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-slate-500 text-xs font-medium">Msgs This Month</span>
                        <div class="w-7 h-7 bg-green-600/20 rounded-lg flex items-center justify-center">
                            <TrendingUp class="w-3.5 h-3.5 text-green-400" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-white">{{ stats.messages_month ?? 0 }}</p>
                    <p class="text-slate-600 text-xs mt-1">this month</p>
                </div>

            </div>

            <!-- ── Interactive Plan / Pricing Cards ── -->
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <CreditCard class="w-4 h-4 text-indigo-400" />
                    <h2 class="text-white font-semibold text-sm">Subscription Plan</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        :class="[
                            'relative border rounded-xl p-5 transition-all',
                            plan.name === currentPlanName
                                ? 'border-indigo-500 bg-indigo-600/10 ring-1 ring-indigo-500/30'
                                : 'border-slate-800 bg-slate-900 hover:border-slate-700 cursor-default'
                        ]"
                    >
                        <!-- Current badge -->
                        <span
                            v-if="plan.name === currentPlanName"
                            class="absolute -top-3 left-4 bg-indigo-600 text-white text-xs px-3 py-0.5 rounded-full flex items-center gap-1 w-fit"
                        >
                            <Star class="w-3 h-3" /> Current
                        </span>

                        <h3 class="text-white font-bold text-sm mb-1">{{ plan.name }}</h3>
                        <div class="flex items-end gap-1 mb-2">
                            <span class="text-2xl font-bold text-white">${{ plan.price }}</span>
                            <span class="text-slate-500 text-xs mb-1">/mo</span>
                        </div>
                        <div class="flex items-center gap-1.5 mb-4">
                            <Zap class="w-3 h-3 text-indigo-400" />
                            <span class="text-slate-400 text-xs">{{ fmt(plan.monthly_token_limit) }} tokens/mo</span>
                        </div>

                        <Link
                            v-if="plan.name !== currentPlanName"
                            href="/billing/plans"
                            class="flex items-center gap-1 text-indigo-400 hover:text-indigo-300 text-xs font-medium transition-colors"
                        >
                            Upgrade <ArrowRight class="w-3 h-3" />
                        </Link>
                        <span v-else class="flex items-center gap-1 text-indigo-400 text-xs">
                            <CheckCircle class="w-3.5 h-3.5" /> Active
                        </span>
                    </div>
                </div>

                <!-- Token quota bar -->
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-1.5">
                            <Zap class="w-3.5 h-3.5 text-indigo-400" />
                            <span class="text-slate-400 text-xs">Token Usage This Month</span>
                        </div>
                        <span class="text-slate-400 text-xs">{{ fmt(quota?.used) }} / {{ fmt(quota?.total) }}</span>
                    </div>
                    <div class="h-2 bg-slate-800 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-700"
                            :class="quotaColor"
                            :style="{ width: Math.min(quotaPct, 100) + '%' }"
                        ></div>
                    </div>
                    <div class="flex items-center justify-between mt-1.5">
                        <p v-if="quota?.exceeded" class="text-red-400 text-xs flex items-center gap-1">
                            <AlertCircle class="w-3 h-3" />
                            Quota exceeded —
                            <Link href="/billing/plans" class="underline hover:text-red-300">upgrade now</Link>
                        </p>
                        <p v-else class="text-slate-600 text-xs">
                            {{ Math.round(quotaPct) }}% used · resets {{ new Date(new Date().getFullYear(), new Date().getMonth() + 1, 1).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                        </p>
                        <Link href="/billing" class="text-slate-500 hover:text-slate-400 text-xs transition-colors">
                            View billing →
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ── 7-Day Usage Bar Chart ── -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <BarChart2 class="w-4 h-4 text-indigo-400" />
                    <h2 class="text-white font-semibold text-sm">Token Usage — Last 7 Days</h2>
                </div>
                <div class="flex items-end gap-2 h-24">
                    <div
                        v-for="d in daily_usage"
                        :key="d.date"
                        class="flex-1 flex flex-col items-center gap-1"
                    >
                        <div
                            class="w-full rounded-t bg-indigo-600/60 hover:bg-indigo-500 transition-colors"
                            :style="{ height: barHeight(d.tokens) + 'px' }"
                            :title="`${fmt(d.tokens)} tokens`"
                        ></div>
                        <span class="text-slate-600 text-xs">{{ d.label }}</span>
                    </div>
                </div>
            </div>

            <!-- ── Recent Chats ── -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden mb-6">
                <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <MessageSquare class="w-4 h-4 text-slate-400" />
                        <h2 class="text-white font-semibold text-sm">Recent Chats</h2>
                    </div>
                    <Link :href="route('projects.index')" class="text-indigo-400 hover:text-indigo-300 text-xs flex items-center gap-1 transition-colors">
                        All projects <ArrowRight class="w-3 h-3" />
                    </Link>
                </div>

                <div v-if="recent_chats.length === 0" class="text-center py-10">
                    <MessageSquare class="w-8 h-8 text-slate-700 mx-auto mb-2" />
                    <p class="text-slate-600 text-sm">No chats yet</p>
                    <Link :href="route('projects.create')" class="mt-3 inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-4 py-2 rounded-lg transition-colors">
                        <Plus class="w-3.5 h-3.5" /> New Project
                    </Link>
                </div>

                <div v-else>
                    <Link
                        v-for="chat in recent_chats"
                        :key="chat.id"
                        :href="route('projects.chats.show', [chat.project_id, chat.id])"
                        class="flex items-center gap-3 px-5 py-3.5 border-b border-slate-800/50 hover:bg-slate-800/50 transition-colors last:border-0"
                    >
                        <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center flex-shrink-0">
                            <MessageSquare class="w-3.5 h-3.5 text-slate-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white text-sm truncate">{{ chat.title ?? 'New Chat' }}</p>
                            <p class="text-slate-600 text-xs">{{ chat.project?.name }}</p>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span class="flex items-center gap-1 text-slate-600 text-xs">
                                <Zap class="w-3 h-3" /> {{ fmt(chat.total_tokens) }}
                            </span>
                            <span class="flex items-center gap-1 text-slate-600 text-xs">
                                <Clock class="w-3 h-3" /> {{ timeAgo(chat.updated_at) }}
                            </span>
                            <span
                                class="text-xs px-2 py-0.5 rounded-full"
                                :class="chat.status === 'open'
                                    ? 'bg-green-900/40 text-green-400'
                                    : 'bg-slate-800 text-slate-500'"
                            >
                                {{ chat.status }}
                            </span>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- ── Quick Action ── -->
            <div class="flex justify-end">
                <button
                    @click="router.get(route('chat.new'))"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm px-5 py-2.5 rounded-xl transition-colors font-medium"
                >
                    <Plus class="w-4 h-4" /> New Chat
                </button>
            </div>

        </div>
    </AppLayout>
</template>