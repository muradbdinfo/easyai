<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Bot, User, Send, ChevronRight, AlertCircle,
    CheckCircle, FileText, Zap, FolderOpen,
    Clock, RefreshCw, Copy
} from 'lucide-vue-next'

const props = defineProps({
    project: Object,
    chat:    Object,
})

// ── State ──────────────────────────────────────────────────────────
const messages    = ref(props.chat.messages ?? [])
const chatStatus  = ref(props.chat.status)
const totalTokens = ref(props.chat.total_tokens ?? 0)
const sending     = ref(false)
const messagesEnd = ref(null)
const textarea    = ref(null)

const form = useForm({ content: '' })

// ── Polling ────────────────────────────────────────────────────────
let pollInterval = null

function startPolling() {
    stopPolling()
    pollInterval = setInterval(pollMessages, 3000)
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval)
        pollInterval = null
    }
}

async function pollMessages() {
    try {
        const res = await fetch(
            route('projects.chats.messages.index', [props.project.id, props.chat.id]),
            { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
        )
        const data = await res.json()

        const prevCount = messages.value.length
        messages.value  = data.messages
        chatStatus.value = data.chat.status
        totalTokens.value = data.chat.total_tokens

        // If we got a new message, stop showing spinner and scroll
        if (data.messages.length > prevCount) {
            sending.value = false
            await nextTick()
            scrollToBottom()
        }

        // Stop polling if chat is closed
        if (data.chat.status === 'closed') {
            stopPolling()
        }
    } catch (e) {
        // silent fail
    }
}

// ── Send message ───────────────────────────────────────────────────
async function sendMessage() {
    if (!form.content.trim() || sending.value || chatStatus.value === 'closed') return

    sending.value = true

    // Optimistic UI — add user message immediately
    messages.value.push({
        id:         Date.now(),
        role:       'user',
        content:    form.content,
        tokens:     0,
        created_at: new Date().toISOString(),
    })

    await nextTick()
    scrollToBottom()

    form.post(
        route('projects.chats.messages.store', [props.project.id, props.chat.id]),
        {
            onSuccess: () => {
                form.reset('content')
                resizeTextarea()
                startPolling()
            },
            onError: () => {
                sending.value = false
                // Remove optimistic message on error
                messages.value.pop()
            },
            preserveScroll: true,
        }
    )
}

// ── Keyboard shortcut ──────────────────────────────────────────────
function handleKeydown(e) {
    if (e.key === 'Enter' && e.ctrlKey) {
        e.preventDefault()
        sendMessage()
    }
}

// ── Textarea auto-resize ───────────────────────────────────────────
function resizeTextarea() {
    if (!textarea.value) return
    textarea.value.style.height = 'auto'
    textarea.value.style.height = Math.min(textarea.value.scrollHeight, 200) + 'px'
}

// ── Scroll helpers ─────────────────────────────────────────────────
function scrollToBottom() {
    nextTick(() => {
        if (messagesEnd.value) {
            messagesEnd.value.scrollIntoView({ behavior: 'smooth' })
        }
    })
}

// ── Copy message ───────────────────────────────────────────────────
function copyMessage(content) {
    navigator.clipboard.writeText(content)
}

// ── Lifecycle ──────────────────────────────────────────────────────
onMounted(() => {
    scrollToBottom()
    // If last message is from user (waiting for AI), start polling
    const last = messages.value[messages.value.length - 1]
    if (last && last.role === 'user') {
        sending.value = true
        startPolling()
    }
})

onUnmounted(() => stopPolling())

// ── Helpers ────────────────────────────────────────────────────────
function formatTime(date) {
    return new Date(date).toLocaleTimeString('en-US', {
        hour: '2-digit', minute: '2-digit',
    })
}
</script>

<template>
    <AppLayout :title="chat.title || 'Chat'">
        <div class="flex flex-col h-[calc(100vh-4rem)]">

            <!-- ── Header ── -->
            <div class="flex items-center justify-between px-6 py-3 border-b border-slate-800 bg-slate-950 shrink-0">
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <Link :href="route('projects.index')"
                          class="hover:text-slate-300 flex items-center gap-1">
                        <FolderOpen class="w-4 h-4" />
                        Projects
                    </Link>
                    <ChevronRight class="w-4 h-4" />
                    <Link :href="route('projects.show', project.id)"
                          class="hover:text-slate-300">
                        {{ project.name }}
                    </Link>
                    <ChevronRight class="w-4 h-4" />
                    <span class="text-slate-300 font-medium truncate max-w-[200px]">
                        {{ chat.title || 'New Chat' }}
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5 bg-slate-800 px-3 py-1 rounded-full">
                        <Bot class="w-3.5 h-3.5 text-indigo-400" />
                        <span class="text-xs text-slate-300">{{ project.model }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <CheckCircle v-if="chatStatus === 'open'"
                                     class="w-4 h-4 text-green-400" />
                        <AlertCircle v-else class="w-4 h-4 text-slate-500" />
                        <span class="text-xs text-slate-400 capitalize">{{ chatStatus }}</span>
                    </div>
                </div>
            </div>

            <!-- ── Closed banner ── -->
            <div v-if="chatStatus === 'closed'"
                 class="flex items-center gap-3 px-6 py-3 bg-amber-500/10 border-b border-amber-500/20 shrink-0">
                <AlertCircle class="w-4 h-4 text-amber-400 shrink-0" />
                <p class="text-amber-300 text-sm">
                    This chat is closed.
                    <span v-if="chat.closed_reason"> {{ chat.closed_reason }}</span>
                </p>
            </div>

            <!-- ── Messages ── -->
            <div class="flex-1 overflow-y-auto px-4 py-6 space-y-5">

                <!-- Empty state -->
                <div v-if="messages.length === 0 && !sending"
                     class="flex flex-col items-center justify-center h-full text-center">
                    <div class="w-16 h-16 bg-indigo-600/20 rounded-2xl flex items-center justify-center mb-4">
                        <Bot class="w-9 h-9 text-indigo-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-slate-300 mb-2">Start a conversation</h3>
                    <p class="text-slate-500 text-sm max-w-sm">
                        Ask anything. Powered by
                        <span class="text-indigo-400">{{ project.model }}</span>.
                    </p>
                </div>

                <!-- Message list -->
                <div v-for="message in messages" :key="message.id">

                    <!-- Assistant bubble -->
                    <div v-if="message.role === 'assistant'"
                         class="flex items-start gap-3 max-w-3xl group">
                        <div class="w-8 h-8 bg-indigo-600/20 rounded-full flex items-center justify-center shrink-0 mt-1">
                            <Bot class="w-4 h-4 text-indigo-400" />
                        </div>
                        <div class="flex-1">
                            <div class="relative bg-slate-800 rounded-2xl rounded-tl-sm px-4 py-3">
                                <p class="text-slate-200 text-sm leading-relaxed whitespace-pre-wrap">{{ message.content }}</p>
                                <button
                                    @click="copyMessage(message.content)"
                                    class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 p-1 text-slate-600 hover:text-slate-300 transition-all"
                                    title="Copy"
                                >
                                    <Copy class="w-3.5 h-3.5" />
                                </button>
                            </div>
                            <div class="flex items-center gap-1.5 mt-1 px-1">
                                <Clock class="w-3 h-3 text-slate-700" />
                                <span class="text-xs text-slate-700">{{ formatTime(message.created_at) }}</span>
                                <span v-if="message.tokens"
                                      class="text-xs text-slate-700">· {{ message.tokens }} tokens</span>
                            </div>
                        </div>
                    </div>

                    <!-- User bubble -->
                    <div v-else-if="message.role === 'user'"
                         class="flex items-start gap-3 max-w-3xl ml-auto justify-end">
                        <div class="flex-1 flex flex-col items-end">
                            <div class="bg-indigo-600 rounded-2xl rounded-tr-sm px-4 py-3 max-w-xl">
                                <p class="text-white text-sm leading-relaxed whitespace-pre-wrap">{{ message.content }}</p>
                            </div>
                            <div class="flex items-center gap-1.5 mt-1 px-1">
                                <Clock class="w-3 h-3 text-slate-700" />
                                <span class="text-xs text-slate-700">{{ formatTime(message.created_at) }}</span>
                            </div>
                        </div>
                        <div class="w-8 h-8 bg-slate-700 rounded-full flex items-center justify-center shrink-0 mt-1">
                            <User class="w-4 h-4 text-slate-400" />
                        </div>
                    </div>

                </div>

                <!-- AI loading indicator -->
                <div v-if="sending" class="flex items-start gap-3 max-w-3xl">
                    <div class="w-8 h-8 bg-indigo-600/20 rounded-full flex items-center justify-center shrink-0 mt-1">
                        <Bot class="w-4 h-4 text-indigo-400" />
                    </div>
                    <div class="bg-slate-800 rounded-2xl rounded-tl-sm px-4 py-3">
                        <div class="flex items-center gap-1.5">
                            <RefreshCw class="w-3.5 h-3.5 text-indigo-400 animate-spin" />
                            <span class="text-slate-400 text-sm">Thinking</span>
                            <span class="flex gap-1">
                                <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                                <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                                <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Scroll anchor -->
                <div ref="messagesEnd"></div>
            </div>

            <!-- ── Input area ── -->
            <div class="shrink-0 border-t border-slate-800 bg-slate-950 px-6 py-4">

                <!-- Quota exceeded error -->
                <div v-if="$page.props.errors?.message"
                     class="flex items-center gap-2 mb-3 text-amber-300 text-sm bg-amber-500/10 border border-amber-500/20 rounded-lg px-4 py-2">
                    <AlertCircle class="w-4 h-4 shrink-0" />
                    {{ $page.props.errors.message }}
                </div>

                <!-- Closed -->
                <div v-if="chatStatus === 'closed'" class="text-center py-3">
                    <p class="text-slate-500 text-sm mb-3">This chat is closed.</p>
                    <Link
                        :href="route('projects.show', project.id)"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    >
                        <ChevronRight class="w-4 h-4" />
                        Back to Project
                    </Link>
                </div>

                <!-- Open — real input -->
                <div v-else>
                    <div class="flex items-end gap-3">
                        <div class="flex-1 relative">
                            <textarea
                                ref="textarea"
                                v-model="form.content"
                                :disabled="sending"
                                placeholder="Type a message... (Ctrl+Enter to send)"
                                rows="1"
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 pr-12 text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 text-sm resize-none disabled:opacity-50 transition-colors"
                                style="min-height:48px; max-height:200px;"
                                @input="resizeTextarea"
                                @keydown="handleKeydown"
                            />
                            <button
                                type="button"
                                class="absolute right-3 bottom-3 text-slate-600 hover:text-slate-400 transition-colors"
                                title="Templates (Module 9)"
                            >
                                <FileText class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Token counter -->
                        <div class="flex items-center gap-1 text-slate-600 text-xs pb-3 shrink-0">
                            <Zap class="w-3.5 h-3.5" />
                            <span>{{ form.content.length }}</span>
                        </div>

                        <!-- Send -->
                        <button
                            type="button"
                            :disabled="sending || !form.content.trim()"
                            @click="sendMessage"
                            class="flex items-center justify-center w-11 h-11 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-xl transition-colors shrink-0"
                            title="Send (Ctrl+Enter)"
                        >
                            <Send class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="flex items-center justify-between mt-2">
                        <div class="flex items-center gap-1.5 text-slate-700 text-xs">
                            <Zap class="w-3 h-3" />
                            <span>{{ totalTokens }} tokens used in this chat</span>
                        </div>
                        <span class="text-xs text-slate-700">Ctrl+Enter to send</span>
                    </div>
                </div>

            </div>

        </div>
    </AppLayout>
</template>