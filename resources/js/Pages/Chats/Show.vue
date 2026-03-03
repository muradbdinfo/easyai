<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Bot, User, Send, ChevronRight, AlertCircle,
    CheckCircle, FileText, Zap, FolderOpen,
    Copy, X, Brain, Plus
} from 'lucide-vue-next'

const props = defineProps({
    project: Object,
    chat:    Object,
})

const page         = usePage()
const templates    = computed(() => page.props.templates ?? [])

const messages      = ref(props.chat.messages ?? [])
const messageInput  = ref('')
const sending       = ref(false)
const messagesEnd   = ref(null)
const textareaRef   = ref(null)
const showTemplates = ref(false)
const copied        = ref(null)

let pollTimer = null

// ── Scroll to bottom ──────────────────────────────────────────────
function scrollToBottom() {
    nextTick(() => {
        messagesEnd.value?.scrollIntoView({ behavior: 'smooth' })
    })
}

// ── Poll for AI response ──────────────────────────────────────────
function startPolling() {
    pollTimer = setInterval(async () => {
        try {
            const res = await axios.get(
                route('projects.chats.messages.index', [props.project.id, props.chat.id])
            )
            messages.value          = res.data.messages
            props.chat.status       = res.data.chat.status
            props.chat.total_tokens = res.data.chat.total_tokens

            const last = messages.value[messages.value.length - 1]
            if (last?.role === 'assistant') {
                sending.value = false
                stopPolling()
            }
            scrollToBottom()
        } catch (e) {
            stopPolling()
        }
    }, 3000)
}

function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer)
        pollTimer = null
    }
}

onUnmounted(() => stopPolling())

// ── Send message ──────────────────────────────────────────────────
async function sendMessage() {
    const content = messageInput.value.trim()
    if (!content || sending.value || props.chat.status === 'closed') return

    sending.value = true

    // Optimistic UI
    messages.value.push({
        id:         Date.now(),
        role:       'user',
        content:    content,
        created_at: new Date().toISOString(),
    })

    messageInput.value = ''
    resizeTextarea()
    scrollToBottom()

    try {
        await axios.post(
            route('projects.chats.messages.store', [props.project.id, props.chat.id]),
            { content },
            { headers: { 'X-XSRF-TOKEN': getCsrfToken() } }
        )
        startPolling()
    } catch (e) {
        sending.value = false
        if (e.response?.status === 402) {
            alert('Token quota exceeded. Please upgrade your plan.')
        }
    }
}

function getCsrfToken() {
    return decodeURIComponent(
        document.cookie.split(';')
            .find(c => c.trim().startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? ''
    )
}

// ── Textarea auto-resize ──────────────────────────────────────────
function resizeTextarea() {
    if (!textareaRef.value) return
    textareaRef.value.style.height = 'auto'
    textareaRef.value.style.height = Math.min(textareaRef.value.scrollHeight, 200) + 'px'
}

// ── Keyboard shortcut ─────────────────────────────────────────────
function handleKeydown(e) {
    if (e.key === 'Enter' && e.ctrlKey) {
        e.preventDefault()
        sendMessage()
    }
}

// ── Copy message ──────────────────────────────────────────────────
function copyMessage(msg) {
    navigator.clipboard.writeText(msg.content)
    copied.value = msg.id
    setTimeout(() => copied.value = null, 2000)
}

// ── Insert template ───────────────────────────────────────────────
function insertTemplate(template) {
    messageInput.value  = template.content
    showTemplates.value = false
    nextTick(() => {
        textareaRef.value?.focus()
        resizeTextarea()
    })
}

// ── New chat ──────────────────────────────────────────────────────
function newChat() {
    router.post(route('projects.chats.store', props.project.id))
}

// ── Format helpers ────────────────────────────────────────────────
function formatTime(date) {
    return new Date(date).toLocaleTimeString('en-US', {
        hour: '2-digit', minute: '2-digit',
    })
}

// Scroll on mount
onMounted(() => scrollToBottom())
</script>

<template>
    <AppLayout :title="chat.title || 'Chat'">
        <div class="flex flex-col h-[calc(100vh-0px)] md:h-screen">

            <!-- ── Header ── -->
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-800 bg-slate-950 shrink-0">
                <div class="flex items-center gap-2 text-sm text-slate-500 min-w-0">
                    <Link
                        :href="route('projects.index')"
                        class="hover:text-slate-300 hidden sm:flex items-center gap-1 shrink-0"
                    >
                        <FolderOpen class="w-3.5 h-3.5" />
                    </Link>
                    <ChevronRight class="w-3.5 h-3.5 hidden sm:block shrink-0" />
                    <Link
                        :href="route('projects.show', project.id)"
                        class="hover:text-slate-300 truncate max-w-24 hidden sm:block"
                    >
                        {{ project.name }}
                    </Link>
                    <ChevronRight class="w-3.5 h-3.5 hidden sm:block shrink-0" />
                    <span class="text-slate-300 font-medium truncate max-w-48">
                        {{ chat.title || 'New Chat' }}
                    </span>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <div class="flex items-center gap-1.5 bg-slate-800 px-2.5 py-1 rounded-full">
                        <Bot class="w-3.5 h-3.5 text-indigo-400" />
                        <span class="text-xs text-slate-300">{{ project.model }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <CheckCircle v-if="chat.status === 'open'" class="w-3.5 h-3.5 text-green-400" />
                        <AlertCircle v-else class="w-3.5 h-3.5 text-slate-500" />
                        <span class="text-xs text-slate-400 capitalize hidden sm:block">
                            {{ chat.status }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- ── Closed banners ── -->
            <div v-if="chat.status === 'closed'" class="shrink-0">

                <!-- Memory saved notice -->
                <div class="flex items-center gap-3 px-4 py-2 bg-purple-500/10 border-b border-purple-500/20">
                    <Brain class="w-4 h-4 text-purple-400 shrink-0" />
                    <p class="text-purple-300 text-sm flex-1">
                        Summary saved to project memory.
                    </p>
                    <button
                        @click="newChat"
                        class="flex items-center gap-1.5 text-xs text-purple-400 hover:text-purple-300 bg-purple-500/10 hover:bg-purple-500/20 px-3 py-1 rounded-full transition-colors shrink-0"
                    >
                        <Plus class="w-3.5 h-3.5" />
                        New Chat
                    </button>
                </div>

                <!-- Closed reason banner -->
                <div class="flex items-center gap-3 px-4 py-2.5 bg-amber-500/10 border-b border-amber-500/20">
                    <AlertCircle class="w-4 h-4 text-amber-400 shrink-0" />
                    <p class="text-amber-300 text-sm">
                        This chat is closed.
                        <span v-if="chat.closed_reason" class="text-amber-400/70 ml-1">
                            {{ chat.closed_reason }}
                        </span>
                    </p>
                </div>

            </div>

            <!-- ── Messages ── -->
            <div class="flex-1 overflow-y-auto px-4 py-6 space-y-6">

                <!-- Empty state -->
                <div
                    v-if="messages.length === 0"
                    class="flex flex-col items-center justify-center h-full text-center"
                >
                    <div class="w-14 h-14 bg-indigo-600/20 rounded-2xl flex items-center justify-center mb-4">
                        <Bot class="w-7 h-7 text-indigo-400" />
                    </div>
                    <p class="text-white font-semibold mb-1">Start a conversation</p>
                    <p class="text-slate-500 text-sm">
                        Ask anything about {{ project.name }}
                    </p>
                </div>

                <!-- Message bubbles -->
                <div v-for="msg in messages" :key="msg.id">

                    <!-- User message -->
                    <div v-if="msg.role === 'user'" class="flex justify-end gap-2 group">
                        <div class="max-w-[75%]">
                            <div class="bg-indigo-600 text-white rounded-2xl rounded-tr-sm px-4 py-2.5 text-sm leading-relaxed">
                                {{ msg.content }}
                            </div>
                            <div class="flex items-center justify-end gap-2 mt-1">
                                <span class="text-slate-600 text-xs">{{ formatTime(msg.created_at) }}</span>
                            </div>
                        </div>
                        <div class="w-7 h-7 bg-slate-700 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                            <User class="w-4 h-4 text-slate-400" />
                        </div>
                    </div>

                    <!-- Assistant message -->
                    <div v-else-if="msg.role === 'assistant'" class="flex gap-2 group">
                        <div class="w-7 h-7 bg-indigo-600/20 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                            <Bot class="w-4 h-4 text-indigo-400" />
                        </div>
                        <div class="max-w-[75%]">
                            <div class="bg-slate-800 text-slate-100 rounded-2xl rounded-tl-sm px-4 py-2.5 text-sm leading-relaxed whitespace-pre-wrap">
                                {{ msg.content }}
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-slate-600 text-xs">{{ formatTime(msg.created_at) }}</span>
                                <button
                                    @click="copyMessage(msg)"
                                    class="opacity-0 group-hover:opacity-100 text-slate-600 hover:text-slate-400 transition-all"
                                    title="Copy"
                                >
                                    <CheckCircle v-if="copied === msg.id" class="w-3.5 h-3.5 text-green-400" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Thinking indicator -->
                <div v-if="sending" class="flex gap-2">
                    <div class="w-7 h-7 bg-indigo-600/20 rounded-full flex items-center justify-center shrink-0">
                        <Bot class="w-4 h-4 text-indigo-400" />
                    </div>
                    <div class="bg-slate-800 rounded-2xl rounded-tl-sm px-4 py-3 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 bg-slate-500 rounded-full animate-bounce" style="animation-delay:0ms" />
                        <span class="w-1.5 h-1.5 bg-slate-500 rounded-full animate-bounce" style="animation-delay:150ms" />
                        <span class="w-1.5 h-1.5 bg-slate-500 rounded-full animate-bounce" style="animation-delay:300ms" />
                    </div>
                </div>

                <div ref="messagesEnd" />
            </div>

            <!-- ── Template picker ── -->
            <div
                v-if="showTemplates && templates.length > 0"
                class="border-t border-slate-800 bg-slate-900 max-h-48 overflow-y-auto shrink-0"
            >
                <div class="flex items-center justify-between px-4 py-2 border-b border-slate-800">
                    <span class="text-slate-400 text-xs font-medium">Select a template</span>
                    <button @click="showTemplates = false" class="text-slate-500 hover:text-slate-300">
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <div class="p-2 space-y-1">
                    <button
                        v-for="t in templates"
                        :key="t.id"
                        @click="insertTemplate(t)"
                        class="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-800 transition-colors"
                    >
                        <p class="text-white text-xs font-medium">{{ t.name }}</p>
                        <p class="text-slate-500 text-xs truncate mt-0.5">{{ t.content }}</p>
                    </button>
                </div>
            </div>

            <!-- ── No templates notice ── -->
            <div
                v-else-if="showTemplates && templates.length === 0"
                class="border-t border-slate-800 bg-slate-900 shrink-0"
            >
                <div class="flex items-center justify-between px-4 py-2 border-b border-slate-800">
                    <span class="text-slate-400 text-xs font-medium">No templates yet</span>
                    <button @click="showTemplates = false" class="text-slate-500 hover:text-slate-300">
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <div class="px-4 py-3 text-center">
                    <p class="text-slate-500 text-xs">
                        Create templates in the
                        <Link :href="route('templates.index')" class="text-indigo-400 hover:text-indigo-300">
                            Templates
                        </Link>
                        page.
                    </p>
                </div>
            </div>

            <!-- ── Input area ── -->
            <div class="border-t border-slate-800 bg-slate-950 px-4 py-3 shrink-0">
                <div class="max-w-4xl mx-auto">
                    <div
                        class="flex items-end gap-2 bg-slate-900 border border-slate-700 rounded-2xl px-3 py-2 transition-colors"
                        :class="{
                            'border-slate-600 opacity-60': chat.status === 'closed',
                            'focus-within:border-slate-500': chat.status === 'open',
                        }"
                    >
                        <!-- Templates button -->
                        <button
                            @click="showTemplates = !showTemplates"
                            :disabled="chat.status === 'closed'"
                            class="p-1.5 transition-colors shrink-0 mb-0.5"
                            :class="showTemplates
                                ? 'text-indigo-400'
                                : 'text-slate-500 hover:text-indigo-400'"
                            title="Insert template"
                        >
                            <FileText class="w-4 h-4" />
                        </button>

                        <!-- Textarea -->
                        <textarea
                            ref="textareaRef"
                            v-model="messageInput"
                            @keydown="handleKeydown"
                            @input="resizeTextarea"
                            :disabled="sending || chat.status === 'closed'"
                            :placeholder="chat.status === 'closed'
                                ? 'Chat is closed. Start a new chat above.'
                                : 'Message EasyAI... (Ctrl+Enter to send)'"
                            rows="1"
                            class="flex-1 bg-transparent text-white placeholder-slate-500 text-sm outline-none resize-none max-h-48 py-1"
                        />

                        <!-- Char counter -->
                        <div class="flex items-center gap-1 text-slate-600 shrink-0 mb-0.5">
                            <Zap class="w-3 h-3" />
                            <span class="text-xs">{{ messageInput.length }}</span>
                        </div>

                        <!-- Send button -->
                        <button
                            @click="sendMessage"
                            :disabled="!messageInput.trim() || sending || chat.status === 'closed'"
                            class="p-1.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-lg transition-colors shrink-0 mb-0.5"
                        >
                            <Send class="w-4 h-4" />
                        </button>
                    </div>

                    <p class="text-slate-700 text-xs text-center mt-1.5">
                        Ctrl+Enter to send
                    </p>
                </div>
            </div>

        </div>
    </AppLayout>
</template>