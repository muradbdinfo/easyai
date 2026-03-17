<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Zap, Settings, ToggleLeft, ToggleRight,
    CheckCircle, AlertCircle, Clock, Trash2,
    Globe, Key, Eye, EyeOff, Copy, RefreshCw,
    ArrowUpRight, ArrowDownLeft
} from 'lucide-vue-next'

const props = defineProps({
    settings:     { type: Object, required: true },
    logs:         { type: Array,  default: () => [] },
    platform_url: { type: String, default: null },
})

const appUrl     = window.location.origin
const showSecret = ref(false)
const copied     = ref(false)

const form = useForm({
    webhook_url:             props.settings.webhook_url             ?? '',
    callback_secret:         props.settings.callback_secret         ?? '',
    event_message_sent:      props.settings.event_message_sent      ?? true,
    event_assistant_replied: props.settings.event_assistant_replied ?? true,
    event_payment_completed: props.settings.event_payment_completed ?? false,
    event_new_chat:          props.settings.event_new_chat          ?? true,
    event_tenant_registered: props.settings.event_tenant_registered ?? false,
    is_enabled:              props.settings.is_enabled              ?? true,
})

function save() {
    form.put(route('n8n.settings.update'), { preserveScroll: true })
}

function clearLogs() {
    if (!confirm('Clear all webhook logs?')) return
    router.delete(route('n8n.logs.clear'), { preserveScroll: true })
}

function copyCallbackUrl() {
    navigator.clipboard.writeText(`${appUrl}/n8n/callback/{chat_id}`)
    copied.value = true
    setTimeout(() => copied.value = false, 2000)
}

function logStatusClass(log) {
    if (log.success === true)  return 'text-emerald-400'
    if (log.success === false) return 'text-red-400'
    return 'text-slate-400'
}

function logStatusIcon(log) {
    if (log.success === true)  return CheckCircle
    if (log.success === false) return AlertCircle
    return Clock
}
</script>

<template>
    <AppLayout title="n8n Automation">
        <div class="max-w-4xl mx-auto px-4 py-8 space-y-8">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-orange-500/10 rounded-xl flex items-center justify-center">
                        <Zap class="w-5 h-5 text-orange-400" />
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold text-white">n8n Automation</h1>
                        <p class="text-sm text-slate-400">Connect EasyAI events to your n8n workflows</p>
                    </div>
                </div>

                <!-- Master on/off toggle -->
                <button @click="form.is_enabled = !form.is_enabled; save()"
                    class="flex items-center gap-2 text-sm transition-colors"
                    :class="form.is_enabled ? 'text-emerald-400' : 'text-slate-500'">
                    <component :is="form.is_enabled ? ToggleRight : ToggleLeft" class="w-6 h-6" />
                    {{ form.is_enabled ? 'Enabled' : 'Disabled' }}
                </button>
            </div>

            <!-- Flash messages -->
            <div v-if="$page.props.flash?.success"
                 class="p-3 bg-green-500/20 border border-green-500/30 rounded-lg text-green-400 text-sm flex items-center gap-2">
                <CheckCircle class="w-4 h-4 flex-shrink-0" /> {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error"
                 class="p-3 bg-red-500/20 border border-red-500/30 rounded-lg text-red-400 text-sm flex items-center gap-2">
                <AlertCircle class="w-4 h-4 flex-shrink-0" /> {{ $page.props.flash.error }}
            </div>

            <!-- Settings card -->
            <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 space-y-6">

                <h2 class="text-sm font-semibold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                    <Settings class="w-4 h-4" /> Configuration
                </h2>

                <!-- Webhook URL -->
                <div class="space-y-1.5">
                    <label class="text-sm text-slate-300 flex items-center gap-2">
                        <Globe class="w-4 h-4 text-slate-500" /> Webhook URL
                    </label>
                    <input v-model="form.webhook_url" type="url"
                        placeholder="https://your-n8n.com/webhook/... (leave blank to use platform default)"
                        class="w-full bg-slate-900 border border-slate-600 rounded-lg px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" />
                    <p v-if="platform_url" class="text-xs text-slate-500">
                        Platform default: <span class="text-slate-400 font-mono">{{ platform_url }}</span>
                    </p>
                </div>

                <!-- Callback Secret -->
                <div class="space-y-1.5">
                    <label class="text-sm text-slate-300 flex items-center gap-2">
                        <Key class="w-4 h-4 text-slate-500" /> Callback Secret (HMAC)
                    </label>
                    <div class="relative">
                        <input v-model="form.callback_secret"
                            :type="showSecret ? 'text' : 'password'"
                            placeholder="Leave blank to use platform secret"
                            class="w-full bg-slate-900 border border-slate-600 rounded-lg px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 pr-10" />
                        <button @click="showSecret = !showSecret"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                            <component :is="showSecret ? EyeOff : Eye" class="w-4 h-4" />
                        </button>
                    </div>
                    <p class="text-xs text-slate-500">
                        Signs outbound payloads (X-EasyAI-Signature) and verifies inbound callbacks (X-N8n-Signature).
                    </p>
                </div>

                <!-- Events -->
                <div class="space-y-2">
                    <label class="text-sm text-slate-300 font-medium">Events to fire</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label v-for="evt in [
                            { key: 'event_new_chat',          label: 'New chat created',      desc: 'Fires when a chat is started' },
                            { key: 'event_message_sent',      label: 'Message sent by user',  desc: 'Fires when user sends a message' },
                            { key: 'event_assistant_replied', label: 'Assistant replied',      desc: 'Fires after AI response saved' },
                            { key: 'event_payment_completed', label: 'Payment completed',      desc: 'Fires when plan activated' },
                            { key: 'event_tenant_registered', label: 'Tenant registered',      desc: 'Fires on new signup' },
                        ]" :key="evt.key"
                            class="flex items-start gap-3 bg-slate-900 border rounded-xl p-4 cursor-pointer transition-colors"
                            :class="form[evt.key] ? 'border-indigo-500/50' : 'border-slate-700 hover:border-slate-500'">
                            <input type="checkbox" v-model="form[evt.key]" class="mt-0.5 accent-indigo-500 w-4 h-4" />
                            <div>
                                <p class="text-sm text-white font-medium">{{ evt.label }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">{{ evt.desc }}</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Save button -->
                <div class="flex justify-end pt-2">
                    <button @click="save" :disabled="form.processing"
                        class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                        <RefreshCw v-if="form.processing" class="w-4 h-4 animate-spin" />
                        <CheckCircle v-else class="w-4 h-4" />
                        Save Settings
                    </button>
                </div>
            </div>

            <!-- Callback URL info -->
            <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 space-y-4">
                <h2 class="text-sm font-semibold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                    <ArrowDownLeft class="w-4 h-4" /> Inbound Callback
                </h2>
                <p class="text-sm text-slate-400">
                    To inject a message back into a chat from n8n, POST to this URL:
                </p>
                <div class="bg-slate-900 rounded-lg p-4 font-mono text-xs text-slate-300 flex items-center justify-between gap-4">
                    <span>
                        POST {{ appUrl }}/n8n/callback/<span class="text-orange-400">{chat_id}</span>
                    </span>
                    <button @click="copyCallbackUrl"
                        class="text-slate-500 hover:text-white flex-shrink-0 transition-colors"
                        :title="copied ? 'Copied!' : 'Copy'">
                        <Copy class="w-4 h-4" />
                    </button>
                </div>
                <div v-if="copied" class="text-xs text-emerald-400">Copied to clipboard!</div>

                <div class="text-xs text-slate-500 space-y-2">
                    <p class="text-slate-400 font-medium">Required headers:</p>
                    <div class="bg-slate-900 px-3 py-2 rounded font-mono text-slate-300 leading-6">
                        Content-Type: application/json<br>
                        X-N8n-Signature: sha256=&lt;hmac_sha256_of_body&gt;
                    </div>
                    <p class="text-slate-400 font-medium mt-3">Required body:</p>
                    <div class="bg-slate-900 px-3 py-2 rounded font-mono text-slate-300">
                        { "content": "Your message here" }
                    </div>
                </div>
            </div>

            <!-- Webhook Logs -->
            <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                        <Zap class="w-4 h-4" /> Webhook Logs
                        <span class="text-xs font-normal text-slate-500 normal-case">(last 50)</span>
                    </h2>
                    <button v-if="logs.length" @click="clearLogs"
                        class="text-xs text-slate-500 hover:text-red-400 flex items-center gap-1 transition-colors">
                        <Trash2 class="w-3.5 h-3.5" /> Clear
                    </button>
                </div>

                <div v-if="!logs.length" class="text-center py-10 text-slate-500 text-sm">
                    No webhook activity yet. Send a chat message to test.
                </div>

                <div v-else class="space-y-2">
                    <div v-for="log in logs" :key="log.id"
                        class="bg-slate-900 rounded-xl p-4 flex items-start gap-3 text-xs">

                        <component
                            :is="log.direction === 'outbound' ? ArrowUpRight : ArrowDownLeft"
                            class="w-4 h-4 mt-0.5 flex-shrink-0"
                            :class="log.direction === 'outbound' ? 'text-indigo-400' : 'text-orange-400'" />

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono text-white">{{ log.event }}</span>
                                <span class="px-1.5 py-0.5 rounded text-xs"
                                    :class="log.direction === 'outbound'
                                        ? 'bg-indigo-500/20 text-indigo-300'
                                        : 'bg-orange-500/20 text-orange-300'">
                                    {{ log.direction }}
                                </span>
                                <span v-if="log.response_code" class="text-slate-500">HTTP {{ log.response_code }}</span>
                                <span v-if="log.duration_ms" class="text-slate-500">{{ log.duration_ms }}ms</span>
                            </div>
                            <p v-if="log.url" class="text-slate-500 mt-1 truncate">{{ log.url }}</p>
                            <p v-if="log.error_message" class="text-red-400 mt-1">{{ log.error_message }}</p>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            <component :is="logStatusIcon(log)" class="w-4 h-4" :class="logStatusClass(log)" />
                            <span class="text-slate-500">{{ log.created_at }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>