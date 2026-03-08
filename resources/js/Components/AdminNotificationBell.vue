<script setup>
// FILE: resources/js/Components/AdminNotificationBell.vue
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    Bell, X, CheckCheck,
    AlertCircle, Zap, CreditCard, TrendingUp,
    DollarSign, CheckCircle, ShieldOff, UserPlus,
} from 'lucide-vue-next'

const open          = ref(false)
const notifications = ref([])
const unreadCount   = ref(0)
let   pollTimer     = null

// ── Icon + color map ───────────────────────────────────────────────
const typeIcon = {
    quota_warning:       Zap,
    quota_exceeded:      AlertCircle,
    payment_approved:    CreditCard,
    payment_received:    DollarSign,
    plan_changed:        TrendingUp,
    tenant_activated:    CheckCircle,
    tenant_suspended:    ShieldOff,
    new_user_registered: UserPlus,    // ← new
}

const typeColor = {
    quota_warning:       'text-amber-500 bg-amber-500/10',
    quota_exceeded:      'text-red-500 bg-red-500/10',
    payment_approved:    'text-green-500 bg-green-500/10',
    payment_received:    'text-indigo-500 bg-indigo-500/10',
    plan_changed:        'text-indigo-500 bg-indigo-500/10',
    tenant_activated:    'text-green-500 bg-green-500/10',
    tenant_suspended:    'text-red-500 bg-red-500/10',
    new_user_registered: 'text-violet-500 bg-violet-500/10',   // ← new
}

async function fetchNotifications() {
    try {
        const res = await axios.get('/notifications')
        notifications.value = res.data.notifications
        unreadCount.value   = res.data.unread_count
    } catch {}
}

async function markRead(id, actionUrl) {
    try {
        await axios.post(`/notifications/${id}/read`)
        const n = notifications.value.find(n => n.id === id)
        if (n) n.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch {}

    if (actionUrl) {
        open.value = false
        // Admin panel redirects need the admin domain prefix
        router.visit('http://' + window.location.host + actionUrl)
    }
}

async function markAllRead() {
    try {
        await axios.post('/notifications/read-all')
        notifications.value.forEach(n => n.read_at = new Date().toISOString())
        unreadCount.value = 0
    } catch {}
}

function timeAgo(date) {
    const s = Math.floor((new Date() - new Date(date)) / 1000)
    if (s < 60)    return 'just now'
    if (s < 3600)  return Math.floor(s / 60) + 'm ago'
    if (s < 86400) return Math.floor(s / 3600) + 'h ago'
    return Math.floor(s / 86400) + 'd ago'
}

onMounted(() => {
    fetchNotifications()
    pollTimer = setInterval(fetchNotifications, 30_000)
})
onUnmounted(() => { if (pollTimer) clearInterval(pollTimer) })
</script>

<template>
    <div class="relative">
        <button
            @click="open = !open"
            class="relative p-2 rounded-lg transition-colors text-slate-500 hover:text-slate-700 hover:bg-slate-100"
        >
            <Bell class="w-4 h-4" />
            <span
                v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 bg-indigo-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-0.5"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <div
            v-if="open"
            class="absolute right-0 top-10 w-80 bg-white border border-slate-200 rounded-xl shadow-xl z-50"
        >
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                <span class="text-slate-700 text-sm font-semibold">Notifications</span>
                <div class="flex items-center gap-2">
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllRead"
                        class="text-indigo-600 hover:text-indigo-500 text-xs flex items-center gap-1"
                    >
                        <CheckCheck class="w-3 h-3" /> All read
                    </button>
                    <button @click="open = false" class="text-slate-400 hover:text-slate-600">
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <div class="max-h-80 overflow-y-auto">
                <div v-if="!notifications.length" class="px-4 py-8 text-center">
                    <Bell class="w-7 h-7 text-slate-300 mx-auto mb-2" />
                    <p class="text-slate-400 text-xs">No notifications yet</p>
                </div>

                <div
                    v-for="n in notifications"
                    :key="n.id"
                    @click="markRead(n.id, n.action_url)"
                    class="flex items-start gap-3 px-4 py-3 border-b border-slate-100 cursor-pointer transition-colors"
                    :class="n.read_at ? 'opacity-50 hover:bg-slate-50' : 'bg-indigo-50/30 hover:bg-slate-50'"
                >
                    <div
                        class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 mt-0.5"
                        :class="typeColor[n.type] ?? 'text-slate-500 bg-slate-100'"
                    >
                        <component :is="typeIcon[n.type] ?? Bell" class="w-3.5 h-3.5" />
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-slate-700 text-xs font-medium leading-snug">{{ n.title }}</p>
                            <span v-if="!n.read_at" class="w-1.5 h-1.5 bg-indigo-600 rounded-full shrink-0 mt-1" />
                        </div>
                        <p v-if="n.body" class="text-slate-400 text-xs mt-0.5 leading-snug line-clamp-2">{{ n.body }}</p>
                        <p class="text-slate-300 text-xs mt-1">{{ timeAgo(n.created_at) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="open" @click="open = false" class="fixed inset-0 z-40" />
    </div>
</template>