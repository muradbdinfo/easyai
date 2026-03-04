<script setup>
// FILE: resources/js/Pages/Chats/Show.vue
// M19: Replaced polling with SSE streaming. All other features unchanged.

import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Bot, User, Send, ChevronRight, AlertCircle, CheckCircle,
    FileText, Zap, Copy, X, Brain, Plus, Download,
    Paperclip, Image, File, Table2, AlertTriangle,
    RefreshCw, CheckCheck
} from 'lucide-vue-next'

// ─────────────────────────────────────────────────────────────────────────────
// Props
// ─────────────────────────────────────────────────────────────────────────────
const props = defineProps({
    project: Object,
    chat:    Object,
})

const page      = usePage()
const templates = computed(() => page.props.templates ?? [])

// ─────────────────────────────────────────────────────────────────────────────
// Core state
// ─────────────────────────────────────────────────────────────────────────────
const messages      = ref(props.chat.messages ?? [])
const messageInput  = ref('')
const sending       = ref(false)
const messagesEnd   = ref(null)
const textareaRef   = ref(null)
const showTemplates = ref(false)
const showExport    = ref(false)
const copied        = ref(null)
const chatStatus    = ref(props.chat.status)
const chatTokens    = ref(props.chat.total_tokens ?? 0)

// ─────────────────────────────────────────────────────────────────────────────
// SSE streaming state  (replaces pollTimer)
// ─────────────────────────────────────────────────────────────────────────────
const streamingContent = ref('')   // accumulates character-by-character
const isStreaming      = ref(false)
let   eventSource      = null

// ─────────────────────────────────────────────────────────────────────────────
// File upload state (unchanged from M17/M18)
// ─────────────────────────────────────────────────────────────────────────────
const pendingAttachment = ref(null)
const uploading         = ref(false)
const uploadError       = ref(null)
const fileInputRef      = ref(null)
const lightboxUrl       = ref(null)

// ─────────────────────────────────────────────────────────────────────────────
// Computed
// ─────────────────────────────────────────────────────────────────────────────
const attachmentIcon = computed(() => {
    if (!pendingAttachment.value) return null
    const t = pendingAttachment.value.type
    if (t === 'image') return Image
    if (t === 'pdf')   return FileText
    if (t === 'excel') return Table2
    return File
})

// ─────────────────────────────────────────────────────────────────────────────
// Scroll
// ─────────────────────────────────────────────────────────────────────────────
function scrollToBottom() {
    nextTick(() => messagesEnd.value?.scrollIntoView({ behavior: 'smooth' }))
}

// ─────────────────────────────────────────────────────────────────────────────
// SSE Streaming  (replaces startPolling / stopPolling)
// ─────────────────────────────────────────────────────────────────────────────
function startStream() {
    // Close any existing stream
    if (eventSource) {
        eventSource.close()
        eventSource = null
    }

    streamingContent.value = ''
    isStreaming.value      = true

    const url = route('projects.chats.stream', [props.project.id, props.chat.id])
    eventSource = new EventSource(url)

    eventSource.onmessage = (e) => {
        try {
            const data = JSON.parse(e.data)

            // Accumulate streaming tokens
            if (data.token) {
                streamingContent.value += data.token
                scrollToBottom()
            }

            // Stream complete
            if (data.done) {
                messages.value.push({
                    id:         data.message_id,
                    role:       'assistant',
                    content:    streamingContent.value,
                    model:      props.project.model,
                    created_at: new Date().toISOString(),
                })

                streamingContent.value = ''
                isStreaming.value      = false
                sending.value          = false
                chatStatus.value       = data.chat_status
                chatTokens.value       = data.total_tokens

                eventSource.close()
                eventSource = null
                scrollToBottom()
            }
        } catch { /* ignore JSON parse errors */ }
    }

    eventSource.onerror = () => {
        // Push partial content if any, or error message
        const content = streamingContent.value.trim()
        messages.value.push({
            id:         Date.now(),
            role:       'assistant',
            content:    content || 'Connection error. Please try again.',
            created_at: new Date().toISOString(),
        })

        streamingContent.value = ''
        isStreaming.value      = false
        sending.value          = false
        eventSource?.close()
        eventSource = null
        scrollToBottom()
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Send message
// ─────────────────────────────────────────────────────────────────────────────
async function sendMessage() {
    const content = messageInput.value.trim()
    if (!content || sending.value || chatStatus.value === 'closed') return

    sending.value = true

    // Optimistic UI — push user message immediately
    messages.value.push({
        id:             Date.now(),
        role:           'user',
        content:        content,
        created_at:     new Date().toISOString(),
        has_attachment: !!pendingAttachment.value,
        attachment:     pendingAttachment.value
            ? {
                type:          pendingAttachment.value.type,
                original_name: pendingAttachment.value.original_name,
                url:           pendingAttachment.value.url,
                extension:     pendingAttachment.value.extension,
              }
            : null,
    })

    const attachmentId      = pendingAttachment.value?.attachment_id ?? null
    messageInput.value      = ''
    pendingAttachment.value = null
    uploadError.value       = null
    resizeTextarea()
    scrollToBottom()

    try {
        const res = await axios.post(
            route('projects.chats.messages.store', [props.project.id, props.chat.id]),
            { content, attachment_id: attachmentId },
            { headers: { 'X-XSRF-TOKEN': getCsrfToken() } }
        )

        if (res.data.success) {
            // Open SSE stream — AI response streams in character by character
            startStream()
        } else {
            sending.value = false
        }
    } catch (e) {
        sending.value = false
        if (e.response?.status === 402) {
            alert('Token quota exceeded. Please upgrade your plan.')
        } else if (e.response?.status === 422) {
            alert(e.response.data.message ?? 'Error sending message.')
        }
    }
}

function getCsrfToken() {
    return decodeURIComponent(
        document.cookie
            .split(';')
            .find(c => c.trim().startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? ''
    )
}

// ─────────────────────────────────────────────────────────────────────────────
// Textarea
// ─────────────────────────────────────────────────────────────────────────────
function resizeTextarea() {
    if (!textareaRef.value) return
    textareaRef.value.style.height = 'auto'
    textareaRef.value.style.height = Math.min(textareaRef.value.scrollHeight, 200) + 'px'
}

function handleKeydown(e) {
    if (e.key === 'Enter' && e.ctrlKey) {
        e.preventDefault()
        sendMessage()
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Copy message
// ─────────────────────────────────────────────────────────────────────────────
function copyMessage(msg) {
    navigator.clipboard.writeText(msg.content)
    copied.value = msg.id
    setTimeout(() => copied.value = null, 2000)
}

// ─────────────────────────────────────────────────────────────────────────────
// Templates (unchanged)
// ─────────────────────────────────────────────────────────────────────────────
function insertTemplate(template) {
    messageInput.value  = template.content
    showTemplates.value = false
    nextTick(() => {
        textareaRef.value?.focus()
        resizeTextarea()
    })
}

// ─────────────────────────────────────────────────────────────────────────────
// New chat / export (unchanged)
// ─────────────────────────────────────────────────────────────────────────────
function newChat() {
    router.post(route('projects.chats.store', props.project.id))
}

function exportPdf() {
    showExport.value = false
    window.location.href = route('chats.export.pdf', [props.project.id, props.chat.id])
}

function exportMarkdown() {
    showExport.value = false
    window.location.href = route('chats.export.markdown', [props.project.id, props.chat.id])
}

// ─────────────────────────────────────────────────────────────────────────────
// File upload (unchanged from M17/M18)
// ─────────────────────────────────────────────────────────────────────────────
function triggerFileInput() {
    fileInputRef.value?.click()
}

async function handleFileSelect(e) {
    const file = e.target.files?.[0]
    if (!file) return

    uploadError.value       = null
    uploading.value         = true
    pendingAttachment.value = null

    const form = new FormData()
    form.append('file', file)

    try {
        const res = await axios.post(
            route('chats.upload', [props.project.id, props.chat.id]),
            form,
            {
                headers: {
                    'X-XSRF-TOKEN': getCsrfToken(),
                    'Content-Type': 'multipart/form-data',
                },
            }
        )
        if (!res.data.success) throw new Error(res.data.message ?? 'Upload failed')
        pendingAttachment.value = res.data.data
    } catch (err) {
        uploadError.value = err.response?.data?.message ?? err.message ?? 'Upload failed'
    } finally {
        uploading.value = false
        if (fileInputRef.value) fileInputRef.value.value = ''
    }
}

async function removePendingAttachment() {
    if (!pendingAttachment.value) return
    const id = pendingAttachment.value.attachment_id
    pendingAttachment.value = null
    try {
        await axios.delete(
            route('attachments.destroy', id),
            { headers: { 'X-XSRF-TOKEN': getCsrfToken() } }
        )
    } catch { /* ignore */ }
}

// ─────────────────────────────────────────────────────────────────────────────
// Lightbox (unchanged)
// ─────────────────────────────────────────────────────────────────────────────
function openLightbox(url) {
    lightboxUrl.value = url
}

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────
function formatTime(date) {
    return new Date(date).toLocaleTimeString('en-US', {
        hour: '2-digit', minute: '2-digit',
    })
}

function formatFileSize(bytes) {
    if (!bytes) return ''
    if (bytes < 1024)        return bytes + ' B'
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

onMounted(() => scrollToBottom())
onUnmounted(() => { eventSource?.close(); eventSource = null })
</script>

<template>
    <AppLayout :title="chat.title ?? 'Chat'">
        <div class="flex flex-col h-[calc(100vh-64px)] bg-slate-950">

            <!-- ── Header ── -->
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-800 bg-slate-900 shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    <ChevronRight class="w-4 h-4 text-slate-600 shrink-0" />
                    <span class="text-slate-400 text-sm truncate">{{ project.name }}</span>
                    <ChevronRight class="w-3.5 h-3.5 text-slate-600 shrink-0" />
                    <span class="text-white text-sm font-medium truncate">{{ chat.title ?? 'New Chat' }}</span>
                    <div class="flex items-center gap-1 px-2 py-0.5 bg-slate-800 rounded-full ml-2 shrink-0">
                        <Bot class="w-3 h-3 text-indigo-400" />
                        <span class="text-xs text-slate-400">{{ project.model ?? 'llama3' }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <!-- Status badge -->
                    <div class="flex items-center gap-1.5">
                        <CheckCircle v-if="chatStatus === 'open'" class="w-4 h-4 text-green-400" />
                        <AlertCircle v-else                        class="w-4 h-4 text-amber-400" />
                        <span class="text-xs"
                              :class="chatStatus === 'open' ? 'text-green-400' : 'text-amber-400'">
                            {{ chatStatus }}
                        </span>
                    </div>

                    <!-- Export dropdown -->
                    <div class="relative">
                        <button @click="showExport = !showExport"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-800
                                       hover:bg-slate-700 text-slate-300 rounded-lg text-xs transition-colors">
                            <Download class="w-3.5 h-3.5" />
                            Export
                        </button>
                        <div v-if="showExport"
                             class="absolute right-0 top-full mt-1 bg-slate-800 border border-slate-700
                                    rounded-lg shadow-xl z-50 min-w-36 overflow-hidden">
                            <button @click="exportPdf"
                                    class="flex items-center gap-2 w-full px-3 py-2 text-sm text-slate-300
                                           hover:bg-slate-700 transition-colors">
                                <FileText class="w-3.5 h-3.5 text-red-400" />
                                Export PDF
                            </button>
                            <button @click="exportMarkdown"
                                    class="flex items-center gap-2 w-full px-3 py-2 text-sm text-slate-300
                                           hover:bg-slate-700 transition-colors">
                                <FileText class="w-3.5 h-3.5 text-indigo-400" />
                                Export Markdown
                            </button>
                        </div>
                    </div>

                    <!-- New chat (always visible) -->
                    <button @click="newChat"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 bg-indigo-600
                                   hover:bg-indigo-500 text-white rounded-lg text-xs transition-colors">
                        <Plus class="w-3.5 h-3.5" />
                        New Chat
                    </button>
                </div>
            </div>

            <!-- ── Token bar ── -->
            <div class="px-4 py-1.5 border-b border-slate-800 bg-slate-900 shrink-0">
                <div class="flex items-center gap-2">
                    <Zap class="w-3.5 h-3.5 text-indigo-400 shrink-0" />
                    <div class="flex-1 h-1.5 bg-slate-800 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all"
                            :class="{
                                'bg-green-500':  (page.props.quota?.percent ?? 0) < 75,
                                'bg-yellow-500': (page.props.quota?.percent ?? 0) >= 75 && (page.props.quota?.percent ?? 0) < 90,
                                'bg-red-500':    (page.props.quota?.percent ?? 0) >= 90,
                            }"
                            :style="{ width: (page.props.quota?.percent ?? 0) + '%' }"
                        />
                    </div>
                    <span class="text-xs text-slate-500 shrink-0">
                        {{ (page.props.quota?.used ?? 0).toLocaleString() }}
                        / {{ (page.props.quota?.total ?? 0).toLocaleString() }} tokens
                    </span>
                </div>
            </div>

            <!-- ── Closed banner ── -->
            <div v-if="chatStatus === 'closed'"
                 class="flex items-center gap-2 px-4 py-2 bg-amber-500/10 border-b border-amber-500/20 shrink-0">
                <AlertCircle class="w-4 h-4 text-amber-400 shrink-0" />
                <p class="text-amber-400 text-xs">
                    This chat is closed.
                    <span v-if="chat.closed_reason" class="text-amber-400/70 ml-1">
                        {{ chat.closed_reason }}
                    </span>
                </p>
            </div>

            <!-- ── Messages ── -->
            <div class="flex-1 overflow-y-auto px-4 py-6 space-y-6">

                <!-- Empty state -->
                <div v-if="messages.length === 0 && !isStreaming"
                     class="flex flex-col items-center justify-center h-full text-center">
                    <div class="w-14 h-14 bg-indigo-600/20 rounded-2xl flex items-center justify-center mb-4">
                        <Bot class="w-7 h-7 text-indigo-400" />
                    </div>
                    <p class="text-white font-semibold mb-1">Start a conversation</p>
                    <p class="text-slate-500 text-sm">
                        Ask anything about {{ project.name }}. You can also attach files.
                    </p>
                </div>

                <!-- Message list (all existing features kept) -->
                <div v-for="msg in messages" :key="msg.id">

                    <!-- ── User message ── -->
                    <div v-if="msg.role === 'user'" class="flex justify-end gap-2 group">
                        <div class="max-w-[75%]">
                            <div class="bg-indigo-600 text-white rounded-2xl rounded-tr-sm px-4 py-2.5 text-sm leading-relaxed">
                                {{ msg.content }}

                                <!-- Attachment in user bubble -->
                                <div v-if="msg.has_attachment && msg.attachment"
                                     class="mt-2 pt-2 border-t border-indigo-500">
                                    <!-- Image preview -->
                                    <div v-if="msg.attachment.type === 'image'">
                                        <img :src="msg.attachment.url"
                                             class="max-w-xs max-h-48 rounded-lg object-contain
                                                    cursor-zoom-in border border-indigo-500/50"
                                             @click="openLightbox(msg.attachment.url)" />
                                        <p class="text-xs text-indigo-200 mt-1 flex items-center gap-1">
                                            <Image class="w-3 h-3" />
                                            {{ msg.attachment.original_name }}
                                        </p>
                                    </div>
                                    <!-- Non-image chip -->
                                    <div v-else
                                         class="flex items-center gap-2 px-2.5 py-1.5 bg-indigo-700/50
                                                rounded-lg text-xs">
                                        <FileText v-if="msg.attachment.type === 'pdf'"
                                                  class="w-3.5 h-3.5 text-red-300 shrink-0" />
                                        <Table2   v-else-if="msg.attachment.type === 'excel'"
                                                  class="w-3.5 h-3.5 text-green-300 shrink-0" />
                                        <File     v-else
                                                  class="w-3.5 h-3.5 text-indigo-200 shrink-0" />
                                        <span class="text-indigo-100 truncate">
                                            {{ msg.attachment.original_name }}
                                        </span>
                                        <span class="text-indigo-300/70 uppercase ml-auto shrink-0">
                                            {{ msg.attachment.extension }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-2 mt-1">
                                <span class="text-slate-600 text-xs">{{ formatTime(msg.created_at) }}</span>
                            </div>
                        </div>
                        <div class="w-7 h-7 bg-slate-700 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                            <User class="w-4 h-4 text-slate-400" />
                        </div>
                    </div>

                    <!-- ── Assistant message ── -->
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
                                <button @click="copyMessage(msg)"
                                        class="opacity-0 group-hover:opacity-100 text-slate-600
                                               hover:text-slate-400 transition-all"
                                        title="Copy">
                                    <CheckCheck v-if="copied === msg.id" class="w-3.5 h-3.5 text-green-400" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ── STREAMING bubble (shows while AI is typing) ── -->
                <div v-if="isStreaming" class="flex gap-2">
                    <div class="w-7 h-7 bg-indigo-600/20 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                        <Bot class="w-4 h-4 text-indigo-400" />
                    </div>
                    <div class="max-w-[75%] bg-slate-800 text-slate-100 rounded-2xl rounded-tl-sm px-4 py-2.5 text-sm leading-relaxed whitespace-pre-wrap">
                        <!-- Show content as it streams in -->
                        <span v-if="streamingContent">{{ streamingContent }}</span>
                        <!-- Bouncing dots while waiting for first token -->
                        <span v-else class="flex items-center gap-1.5 py-0.5">
                            <span class="w-1.5 h-1.5 bg-slate-500 rounded-full animate-bounce" style="animation-delay:0ms" />
                            <span class="w-1.5 h-1.5 bg-slate-500 rounded-full animate-bounce" style="animation-delay:150ms" />
                            <span class="w-1.5 h-1.5 bg-slate-500 rounded-full animate-bounce" style="animation-delay:300ms" />
                        </span>
                        <!-- Blinking cursor while streaming -->
                        <span v-if="streamingContent"
                              class="inline-block w-0.5 h-4 bg-indigo-400 ml-0.5 animate-pulse align-middle" />
                    </div>
                </div>

                <div ref="messagesEnd" />
            </div>

            <!-- ── Template picker (unchanged) ── -->
            <div v-if="showTemplates && templates.length > 0"
                 class="border-t border-slate-800 bg-slate-900 max-h-48 overflow-y-auto shrink-0">
                <div class="flex items-center justify-between px-4 py-2 border-b border-slate-800">
                    <span class="text-slate-400 text-xs font-medium">Select a template</span>
                    <button @click="showTemplates = false" class="text-slate-500 hover:text-slate-300">
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <button v-for="tpl in templates" :key="tpl.id"
                        @click="insertTemplate(tpl)"
                        class="w-full text-left px-4 py-2.5 hover:bg-slate-800 transition-colors border-b border-slate-800/50">
                    <div class="flex items-center gap-2">
                        <FileText class="w-3.5 h-3.5 text-indigo-400 shrink-0" />
                        <span class="text-sm text-slate-300 font-medium">{{ tpl.name }}</span>
                    </div>
                    <p class="text-slate-500 text-xs mt-0.5 line-clamp-1">{{ tpl.content }}</p>
                </button>
            </div>

            <!-- ── Input area (unchanged) ── -->
            <div class="border-t border-slate-800 bg-slate-900 px-4 pt-3 pb-4 shrink-0">

                <input ref="fileInputRef"
                       type="file"
                       accept=".jpg,.jpeg,.png,.gif,.webp,.txt,.pdf,.xls,.xlsx"
                       class="hidden"
                       @change="handleFileSelect" />

                <!-- Pending attachment preview -->
                <div v-if="pendingAttachment || uploading"
                     class="flex items-center gap-2 px-3 py-2 bg-slate-800 border border-slate-700
                            rounded-xl mb-2 text-sm">
                    <template v-if="uploading">
                        <RefreshCw class="w-4 h-4 text-indigo-400 animate-spin shrink-0" />
                        <span class="text-slate-400 text-xs">Uploading file…</span>
                    </template>
                    <template v-else-if="pendingAttachment?.type === 'image'">
                        <img :src="pendingAttachment.url"
                             class="w-10 h-10 rounded-lg object-cover border border-slate-600 shrink-0" />
                        <div class="min-w-0 flex-1">
                            <p class="text-slate-300 text-xs truncate">{{ pendingAttachment.original_name }}</p>
                            <p class="text-indigo-400 text-xs">image ready</p>
                        </div>
                        <button @click="removePendingAttachment"
                                class="ml-auto text-slate-500 hover:text-red-400 transition-colors shrink-0">
                            <X class="w-4 h-4" />
                        </button>
                    </template>
                    <template v-else-if="pendingAttachment">
                        <component :is="attachmentIcon" class="w-4 h-4 text-indigo-400 shrink-0" />
                        <div class="min-w-0 flex-1">
                            <p class="text-slate-300 text-xs truncate">{{ pendingAttachment.original_name }}</p>
                            <p class="text-indigo-400 text-xs uppercase">{{ pendingAttachment.type }} ready</p>
                        </div>
                        <button @click="removePendingAttachment"
                                class="ml-auto text-slate-500 hover:text-red-400 transition-colors shrink-0">
                            <X class="w-4 h-4" />
                        </button>
                    </template>
                </div>

                <!-- Upload error -->
                <div v-if="uploadError"
                     class="flex items-center gap-1.5 text-xs text-red-400 mb-2 px-1">
                    <AlertTriangle class="w-3.5 h-3.5 shrink-0" />
                    {{ uploadError }}
                </div>

                <!-- Input row -->
                <div class="flex items-end gap-2 bg-slate-800 rounded-2xl px-3 py-2.5 border border-slate-700 focus-within:border-indigo-500/50 transition-colors">

                    <button @click="triggerFileInput"
                            :disabled="chatStatus === 'closed' || uploading"
                            class="p-1.5 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed mb-0.5 shrink-0"
                            :class="pendingAttachment ? 'text-indigo-400 bg-indigo-500/10' : 'text-slate-500 hover:text-indigo-400'"
                            title="Attach file (image, PDF, TXT, Excel — max 10MB)">
                        <Paperclip class="w-4 h-4" />
                    </button>

                    <button @click="showTemplates = !showTemplates"
                            :disabled="chatStatus === 'closed'"
                            class="p-1.5 rounded-lg transition-colors disabled:opacity-40 mb-0.5 shrink-0"
                            :class="showTemplates ? 'text-indigo-400' : 'text-slate-500 hover:text-indigo-400'"
                            title="Insert template">
                        <FileText class="w-4 h-4" />
                    </button>

                    <textarea
                        ref="textareaRef"
                        v-model="messageInput"
                        @keydown="handleKeydown"
                        @input="resizeTextarea"
                        :disabled="sending || chatStatus === 'closed'"
                        :placeholder="chatStatus === 'closed'
                            ? 'Chat is closed. Start a new chat above.'
                            : 'Message EasyAI… (Ctrl+Enter to send)'"
                        rows="1"
                        class="flex-1 bg-transparent text-white placeholder-slate-500 text-sm outline-none resize-none max-h-48 py-1"
                    />

                    <div class="flex items-center gap-1 text-slate-600 shrink-0 mb-0.5">
                        <Zap class="w-3 h-3" />
                        <span class="text-xs">{{ messageInput.length }}</span>
                    </div>

                    <button @click="sendMessage"
                            :disabled="!messageInput.trim() || sending || chatStatus === 'closed'"
                            class="p-1.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40
                                   disabled:cursor-not-allowed text-white rounded-lg transition-colors
                                   shrink-0 mb-0.5">
                        <Send class="w-4 h-4" />
                    </button>
                </div>

                <p class="text-slate-700 text-xs text-center mt-1.5">
                    Ctrl+Enter to send · Attach: image, PDF, TXT, Excel
                </p>
            </div>

        </div>

        <!-- ── Lightbox (unchanged) ── -->
        <Teleport to="body">
            <div v-if="lightboxUrl"
                 class="fixed inset-0 z-50 bg-black/85 flex items-center justify-center p-4"
                 @click="lightboxUrl = null">
                <button class="absolute top-4 right-4 text-white/70 hover:text-white
                               bg-black/40 p-2 rounded-full transition-colors">
                    <X class="w-5 h-5" />
                </button>
                <img :src="lightboxUrl"
                     class="max-w-full max-h-full rounded-xl shadow-2xl"
                     @click.stop />
            </div>
        </Teleport>

    </AppLayout>
</template>