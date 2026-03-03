<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Bot, User, Send, ChevronRight, AlertCircle,
    CheckCircle, FileText, Zap, FolderOpen, Clock
} from 'lucide-vue-next'

const props = defineProps({
    project: Object,
    chat:    Object,
})

const messageInput = ref('')
const sending = ref(false)

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
                    <Link :href="route('projects.index')" class="hover:text-slate-300 flex items-center gap-1">
                        <FolderOpen class="w-4 h-4" />
                        Projects
                    </Link>
                    <ChevronRight class="w-4 h-4" />
                    <Link :href="route('projects.show', project.id)" class="hover:text-slate-300">
                        {{ project.name }}
                    </Link>
                    <ChevronRight class="w-4 h-4" />
                    <span class="text-slate-300 font-medium">{{ chat.title || 'New Chat' }}</span>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Model badge -->
                    <div class="flex items-center gap-1.5 bg-slate-800 px-3 py-1 rounded-full">
                        <Bot class="w-3.5 h-3.5 text-indigo-400" />
                        <span class="text-xs text-slate-300">{{ project.model }}</span>
                    </div>

                    <!-- Status -->
                    <div class="flex items-center gap-1.5">
                        <CheckCircle v-if="chat.status === 'open'" class="w-4 h-4 text-green-400" />
                        <AlertCircle v-else class="w-4 h-4 text-slate-500" />
                        <span class="text-xs text-slate-400 capitalize">{{ chat.status }}</span>
                    </div>
                </div>
            </div>

            <!-- ── Closed banner ── -->
            <div
                v-if="chat.status === 'closed'"
                class="flex items-center gap-3 px-6 py-3 bg-amber-500/10 border-b border-amber-500/20 shrink-0"
            >
                <AlertCircle class="w-4 h-4 text-amber-400 shrink-0" />
                <p class="text-amber-300 text-sm">
                    This chat is closed. <span v-if="chat.closed_reason">{{ chat.closed_reason }}</span>
                </p>
            </div>

            <!-- ── Messages area ── -->
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">

                <!-- Empty state -->
                <div
                    v-if="!chat.messages || chat.messages.length === 0"
                    class="flex flex-col items-center justify-center h-full text-center"
                >
                    <div class="w-16 h-16 bg-indigo-600/20 rounded-2xl flex items-center justify-center mb-4">
                        <Bot class="w-9 h-9 text-indigo-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-slate-300 mb-2">Start a conversation</h3>
                    <p class="text-slate-500 text-sm max-w-sm">
                        Ask anything. Your AI assistant is ready using <span class="text-indigo-400">{{ project.model }}</span>.
                    </p>
                </div>

                <!-- Message bubbles -->
                <div
                    v-for="message in chat.messages"
                    :key="message.id"
                    :class="message.role === 'user' ? 'flex justify-end' : 'flex justify-start'"
                >
                    <!-- Assistant -->
                    <div v-if="message.role === 'assistant'" class="flex items-start gap-3 max-w-2xl">
                        <div class="w-8 h-8 bg-indigo-600/20 rounded-full flex items-center justify-center shrink-0 mt-1">
                            <Bot class="w-4 h-4 text-indigo-400" />
                        </div>
                        <div>
                            <div class="bg-slate-800 rounded-2xl rounded-tl-sm px-4 py-3">
                                <p class="text-slate-200 text-sm leading-relaxed whitespace-pre-wrap">{{ message.content }}</p>
                            </div>
                            <div class="flex items-center gap-1.5 mt-1 px-1">
                                <Clock class="w-3 h-3 text-slate-600" />
                                <span class="text-xs text-slate-600">{{ formatTime(message.created_at) }}</span>
                                <span v-if="message.tokens" class="text-xs text-slate-600">· {{ message.tokens }} tokens</span>
                            </div>
                        </div>
                    </div>

                    <!-- User -->
                    <div v-else-if="message.role === 'user'" class="flex items-start gap-3 max-w-2xl">
                        <div>
                            <div class="bg-indigo-600 rounded-2xl rounded-tr-sm px-4 py-3">
                                <p class="text-white text-sm leading-relaxed whitespace-pre-wrap">{{ message.content }}</p>
                            </div>
                            <div class="flex items-center justify-end gap-1.5 mt-1 px-1">
                                <Clock class="w-3 h-3 text-slate-600" />
                                <span class="text-xs text-slate-600">{{ formatTime(message.created_at) }}</span>
                            </div>
                        </div>
                        <div class="w-8 h-8 bg-slate-700 rounded-full flex items-center justify-center shrink-0 mt-1">
                            <User class="w-4 h-4 text-slate-400" />
                        </div>
                    </div>
                </div>

            </div>

            <!-- ── Input area ── -->
            <div class="shrink-0 border-t border-slate-800 bg-slate-950 px-6 py-4">

                <!-- Closed state — no input -->
                <div v-if="chat.status === 'closed'" class="text-center py-3">
                    <p class="text-slate-500 text-sm mb-3">This chat is closed.</p>
                    <Link
                        :href="route('projects.show', project.id)"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    >
                        <ChevronRight class="w-4 h-4" />
                        Back to Project
                    </Link>
                </div>

                <!-- Open state — input enabled (messages wired in Module 5) -->
                <div v-else class="flex items-end gap-3">
                    <div class="flex-1 relative">
                        <textarea
                            v-model="messageInput"
                            :disabled="sending"
                            placeholder="Type a message... (Ctrl+Enter to send)"
                            rows="1"
                            class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 pr-12 text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 text-sm resize-none disabled:opacity-50"
                            style="min-height: 48px; max-height: 200px;"
                        />
                        <!-- Templates icon placeholder -->
                        <button
                            type="button"
                            class="absolute right-3 bottom-3 text-slate-600 hover:text-slate-400 transition-colors"
                            title="Templates (Module 9)"
                        >
                            <FileText class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Token / char counter -->
                    <div class="flex items-center gap-1.5 text-slate-600 text-xs shrink-0 pb-3">
                        <Zap class="w-3.5 h-3.5" />
                        <span>{{ messageInput.length }}</span>
                    </div>

                    <!-- Send button (wired in Module 5) -->
                    <button
                        type="button"
                        :disabled="sending || !messageInput.trim()"
                        class="flex items-center justify-center w-11 h-11 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-xl transition-colors shrink-0"
                        title="Send (Ctrl+Enter)"
                    >
                        <Send class="w-4 h-4" />
                    </button>
                </div>

                <p class="text-xs text-slate-700 mt-2 text-right">
                    Ctrl+Enter to send · Messages powered in Module 5
                </p>
            </div>

        </div>
    </AppLayout>
</template>