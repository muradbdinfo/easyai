<script setup>
import { ref } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    FolderOpen, MessageSquare, Settings, Bot, Zap,
    Save, ChevronRight, Plus, Trash2, Clock,
    CheckCircle, AlertCircle
} from 'lucide-vue-next'

const props = defineProps({
    project: Object,
})

const activeTab = ref('chats')

const models = ['llama3', 'mistral', 'codellama', 'llama3:70b']

// ── Settings form ──────────────────────────────────────────────────
const settingsForm = useForm({
    name:          props.project.name,
    description:   props.project.description ?? '',
    system_prompt: props.project.system_prompt ?? '',
    model:         props.project.model,
})

function saveSettings() {
    settingsForm.put(route('projects.update', props.project.id), {
        onSuccess: () => settingsForm.reset(),
        preserveScroll: true,
    })
}

// ── New chat ───────────────────────────────────────────────────────
function createChat() {
    router.post(route('chats.store', props.project.id), {})
}

// ── Delete chat ────────────────────────────────────────────────────
function deleteChat(chat) {
    if (!confirm('Delete this chat?')) return
    router.delete(route('chats.destroy', [props.project.id, chat.id]))
}

function formatTime(date) {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
    })
}
</script>

<template>
    <AppLayout :title="project.name">
        <div class="max-w-5xl mx-auto px-6 py-8">

            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-6">
                <Link :href="route('projects.index')" class="hover:text-slate-300 flex items-center gap-1">
                    <FolderOpen class="w-4 h-4" />
                    Projects
                </Link>
                <ChevronRight class="w-4 h-4" />
                <span class="text-slate-300 font-medium">{{ project.name }}</span>
            </div>

            <!-- Project header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-white">{{ project.name }}</h1>
                    <p v-if="project.description" class="text-slate-400 text-sm mt-1">{{ project.description }}</p>
                </div>
                <div class="flex items-center gap-2 bg-slate-800 px-3 py-1.5 rounded-full">
                    <Bot class="w-4 h-4 text-indigo-400" />
                    <span class="text-sm text-slate-300">{{ project.model }}</span>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 bg-slate-900 border border-slate-800 rounded-xl p-1 mb-6 w-fit">
                <button
                    @click="activeTab = 'chats'"
                    :class="activeTab === 'chats'
                        ? 'bg-indigo-600 text-white'
                        : 'text-slate-400 hover:text-slate-200'"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all"
                >
                    <MessageSquare class="w-4 h-4" />
                    Chats
                    <span class="bg-slate-700 text-slate-300 text-xs px-1.5 py-0.5 rounded-full">
                        {{ project.chats_count }}
                    </span>
                </button>
                <button
                    @click="activeTab = 'settings'"
                    :class="activeTab === 'settings'
                        ? 'bg-indigo-600 text-white'
                        : 'text-slate-400 hover:text-slate-200'"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all"
                >
                    <Settings class="w-4 h-4" />
                    Settings
                </button>
            </div>

            <!-- ── CHATS TAB ── -->
            <div v-if="activeTab === 'chats'">
                <div class="flex justify-end mb-4">
                    <button
                        @click="createChat"
                        class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    >
                        <Plus class="w-4 h-4" />
                        New Chat
                    </button>
                </div>

                <!-- Empty chats -->
                <div v-if="!project.chats || project.chats.length === 0" class="text-center py-16">
                    <MessageSquare class="w-12 h-12 text-slate-600 mx-auto mb-3" />
                    <p class="text-slate-400">No chats yet. Start a new conversation.</p>
                </div>

                <!-- Chats list -->
                <div v-else class="space-y-2">
                    <div
                        v-for="chat in project.chats"
                        :key="chat.id"
                        class="flex items-center justify-between bg-slate-900 border border-slate-800 rounded-xl px-5 py-4 hover:border-slate-700 transition-colors group"
                    >
                        <div class="flex items-center gap-4 min-w-0">
                            <!-- Status icon -->
                            <CheckCircle v-if="chat.status === 'open'" class="w-4 h-4 text-green-400 shrink-0" />
                            <AlertCircle v-else class="w-4 h-4 text-slate-500 shrink-0" />

                            <div class="min-w-0">
                                <p class="text-white font-medium text-sm truncate">
                                    {{ chat.title || 'New Chat' }}
                                </p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <Clock class="w-3 h-3 text-slate-600" />
                                    <span class="text-xs text-slate-500">{{ formatTime(chat.updated_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                @click="deleteChat(chat)"
                                class="opacity-0 group-hover:opacity-100 p-1.5 text-slate-600 hover:text-red-400 hover:bg-red-400/10 rounded-lg transition-all"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                            
                                :href="route('chats.show', [project.id, chat.id])"
                                class="flex items-center gap-1 bg-slate-800 hover:bg-slate-700 text-slate-300 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            >
                                Open
                                <ChevronRight class="w-3.5 h-3.5" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── SETTINGS TAB ── -->
            <div v-if="activeTab === 'settings'">
                <form @submit.prevent="saveSettings" class="space-y-6 max-w-2xl">

                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1.5">Project Name</label>
                        <input
                            v-model="settingsForm.name"
                            type="text"
                            class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-indigo-500 text-sm"
                        />
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1.5">Description</label>
                        <textarea
                            v-model="settingsForm.description"
                            rows="3"
                            class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-indigo-500 text-sm resize-none"
                        />
                    </div>

                    <!-- AI Persona / System Prompt -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1.5">
                            <div class="flex items-center gap-2">
                                <Bot class="w-4 h-4 text-indigo-400" />
                                AI Persona (System Prompt)
                            </div>
                        </label>
                        <textarea
                            v-model="settingsForm.system_prompt"
                            rows="6"
                            placeholder="You are a helpful assistant specialized in..."
                            class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 text-sm resize-none font-mono"
                        />
                        <p class="text-slate-500 text-xs mt-1">
                            This sets the AI's personality and instructions for all chats in this project.
                        </p>
                    </div>

                    <!-- Model selector -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1.5">
                            <div class="flex items-center gap-2">
                                <Zap class="w-4 h-4 text-yellow-400" />
                                AI Model
                            </div>
                        </label>
                        <select
                            v-model="settingsForm.model"
                            class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-indigo-500 text-sm"
                        >
                            <option v-for="m in models" :key="m" :value="m">{{ m }}</option>
                        </select>
                    </div>

                    <!-- Save button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="settingsForm.processing"
                            class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors"
                        >
                            <Save class="w-4 h-4" />
                            {{ settingsForm.processing ? 'Saving...' : 'Save Settings' }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </AppLayout>
</template>