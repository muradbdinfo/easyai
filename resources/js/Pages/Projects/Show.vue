<script setup>
import { ref, computed } from 'vue'
import { router, useForm, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    MessageSquare, Settings, Bot, Zap, Save,
    Brain, RefreshCw, Edit2, Trash2, Eye, EyeOff,
    Plus, FolderOpen, ChevronRight, Info, Users
} from 'lucide-vue-next'

const props = defineProps({
    project:       Object,
    chats:         { type: Array, default: () => [] },
    ollama_models: { type: Array, default: () => [] },
})

const page         = usePage()
const ollamaModels = computed(() =>
    props.ollama_models?.length
        ? props.ollama_models
        : (page.props.ollama_models ?? [])
)

const activeTab = ref('chats')

const settingsForm = useForm({
    name:          props.project.name,
    description:   props.project.description   ?? '',
    system_prompt: props.project.system_prompt ?? '',
    model:         props.project.model         ?? ollamaModels.value[0] ?? '',
})

function saveSettings() {
    settingsForm.put(route('projects.update', props.project.id), {
        preserveScroll: true,
    })
}

// ── Friendly model labels ──────────────────────────────────────────────────
function modelLabel(m) {
    const labels = {
        'smollm2:latest':      '⚡ SmolLM2 — Ultra fast, lightweight',
        'smollm2:135m':        '⚡ SmolLM2 135M — Smallest & fastest',
        'qwen2:1.5b':          '🤖 Qwen2 1.5B — Fast & efficient',
        'qwen2:7b':            '🤖 Qwen2 7B — Balanced quality',
        'phi3:mini':           '🔬 Phi-3 Mini — Microsoft, compact',
        'phi3:medium':         '🔬 Phi-3 Medium — Microsoft, capable',
        'llama3.2:latest':     '🦙 Llama 3.2 — Meta, balanced',
        'llama3.2:1b':         '🦙 Llama 3.2 1B — Meta, very fast',
        'llama3.2:3b':         '🦙 Llama 3.2 3B — Meta, fast',
        'llama3:latest':       '🦙 Llama 3 — Meta, general purpose',
        'mistral:latest':      '💨 Mistral 7B — Fast & capable',
        'codellama:latest':    '💻 CodeLlama — Best for coding',
        'deepseek-coder:latest': '💻 DeepSeek Coder — Code specialist',
        'gemma:2b':            '💎 Gemma 2B — Google, lightweight',
        'gemma:7b':            '💎 Gemma 7B — Google, capable',
        'ollama/qwen2:1.5b':          '🐾 OpenClaw + Qwen2 — Agent with MCP tools',
        'ollama/llama3.2':            '🐾 OpenClaw + Llama3.2 — Agent with MCP tools',
        'ollama/phi3:mini':           '🐾 OpenClaw + Phi-3 — Agent with MCP tools',
        'openclaw/ollama/qwen2:1.5b': '🐾 OpenClaw + Qwen2 — Agent with MCP tools',
        'openclaw/ollama/llama3.2':   '🐾 OpenClaw + Llama3.2 — Agent with MCP tools',
        'openclaw/ollama/phi3:mini':  '🐾 OpenClaw + Phi-3 — Agent with MCP tools',
    }
    return labels[m] ?? m
}

// ── Memory ─────────────────────────────────────────────────────────────────
const showFullMemory = ref(false)
const editingMemory  = ref(false)
const memoryContent  = ref(props.project.context_summary ?? '')

function toggleMemory()     { showFullMemory.value = !showFullMemory.value }
function startEditMemory()  { memoryContent.value = props.project.context_summary ?? ''; editingMemory.value = true }

function saveMemory() {
    router.patch(route('projects.memory.update', props.project.id), {
        context_summary: memoryContent.value,
    }, {
        preserveScroll: true,
        onSuccess: () => { editingMemory.value = false },
    })
}

function clearMemory() {
    if (!confirm('Clear all project memory? This cannot be undone.')) return
    router.delete(route('projects.memory.clear', props.project.id), {
        preserveScroll: true,
        onSuccess: () => {
            memoryContent.value  = ''
            editingMemory.value  = false
            showFullMemory.value = false
        },
    })
}

// ── Chats ──────────────────────────────────────────────────────────────────
function newChat() {
    router.post(route('projects.chats.store', props.project.id))
}

function deleteChat(chat) {
    if (!confirm('Delete this chat? This cannot be undone.')) return
    router.delete(route('projects.chats.destroy', [props.project.id, chat.id]), {
        preserveScroll: true,
    })
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    })
}

const memoryPreview = computed(() => {
    const s = props.project.context_summary ?? ''
    return s.length > 300 ? s.slice(0, 300) + '...' : s
})
</script>

<template>
    <AppLayout :title="project.name">
        <div class="max-w-5xl mx-auto px-6 py-8">

            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-6">
                <Link :href="route('projects.index')"
                      class="hover:text-slate-300 flex items-center gap-1 transition-colors">
                    <FolderOpen class="w-3.5 h-3.5" /> Projects
                </Link>
                <ChevronRight class="w-3.5 h-3.5" />
                <span class="text-white font-medium">{{ project.name }}</span>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 mb-6 bg-slate-900 p-1 rounded-xl w-fit">
                <button v-for="tab in [
                    { key:'chats',    icon: MessageSquare, label:'Chats'          },
                    { key:'kb',       icon: Brain,         label:'Knowledge Base' },
                    { key:'members',  icon: Users,         label:'Members'        },
                    { key:'settings', icon: Settings,      label:'Settings'       },
                ]" :key="tab.key"
                    @click="activeTab = tab.key"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    :class="activeTab === tab.key ? 'bg-slate-700 text-white' : 'text-slate-400 hover:text-white'"
                >
                    <component :is="tab.icon" class="w-4 h-4" />
                    {{ tab.label }}
                    <span v-if="tab.key === 'chats'"
                          class="bg-slate-600 text-slate-300 text-xs px-1.5 py-0.5 rounded-full">
                        {{ chats.length }}
                    </span>
                </button>
            </div>

            <!-- ── Chats Tab ── -->
            <div v-if="activeTab === 'chats'">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-white font-semibold">Conversations</h2>
                    <button @click="newChat"
                            class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <Plus class="w-4 h-4" /> New Chat
                    </button>
                </div>

                <div v-if="chats.length === 0"
                     class="bg-slate-900 border border-slate-800 rounded-xl p-10 text-center">
                    <MessageSquare class="w-8 h-8 text-slate-700 mx-auto mb-3" />
                    <p class="text-slate-400 text-sm mb-1">No conversations yet.</p>
                    <button @click="newChat" class="text-indigo-400 hover:text-indigo-300 text-sm mt-2 transition-colors">
                        Start your first chat
                    </button>
                </div>

                <div v-else class="space-y-2">
                    <div v-for="chat in chats" :key="chat.id"
                         class="flex items-center gap-3 bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-xl p-4 transition-colors group">
                        <div class="w-8 h-8 bg-indigo-600/20 rounded-lg flex items-center justify-center shrink-0">
                            <MessageSquare class="w-4 h-4 text-indigo-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <Link :href="route('projects.chats.show', [project.id, chat.id])"
                                  class="text-white font-medium text-sm hover:text-indigo-400 transition-colors truncate block">
                                {{ chat.title || 'New Chat' }}
                            </Link>
                            <div class="flex items-center gap-3 mt-0.5">
                                <span class="text-slate-500 text-xs">{{ formatDate(chat.updated_at) }}</span>
                                <span class="text-xs px-1.5 py-0.5 rounded-full capitalize"
                                      :class="chat.status === 'open' ? 'bg-green-500/10 text-green-400' : 'bg-slate-700 text-slate-400'">
                                    {{ chat.status }}
                                </span>
                                <span class="flex items-center gap-1 text-slate-500 text-xs">
                                    <Zap class="w-3 h-3" /> {{ chat.total_tokens.toLocaleString() }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <Link :href="route('projects.chats.show', [project.id, chat.id])"
                                  class="p-1.5 text-slate-500 hover:text-indigo-400 hover:bg-slate-800 rounded-lg transition-colors">
                                <ChevronRight class="w-4 h-4" />
                            </Link>
                            <button @click="deleteChat(chat)"
                                    class="p-1.5 text-slate-500 hover:text-red-400 hover:bg-red-400/10 rounded-lg transition-colors">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Knowledge Base Tab ── -->
            <div v-if="activeTab === 'kb'">
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-10 text-center">
                    <Brain class="w-8 h-8 text-slate-700 mx-auto mb-3" />
                    <p class="text-slate-400 text-sm mb-4">Store documents and context that the AI can reference across all chats.</p>
                    <a :href="route('kb.index', project.id)"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg transition-colors">
                        <Brain class="w-4 h-4" /> Manage Knowledge Base
                    </a>
                </div>
            </div>

            <!-- ── Members Tab ── -->
            <div v-if="activeTab === 'members'">
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-10 text-center">
                    <Users class="w-8 h-8 text-slate-700 mx-auto mb-3" />
                    <p class="text-slate-400 text-sm mb-4">Control who can access this project and assign their roles.</p>
                    <Link :href="route('project.members.index', project.id)"
                          class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg transition-colors">
                        <Users class="w-4 h-4" /> Manage Members
                    </Link>
                </div>
            </div>

            <!-- ── Settings Tab ── -->
            <div v-if="activeTab === 'settings'" class="space-y-6">

                <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <Settings class="w-4 h-4 text-slate-400" />
                        <h2 class="text-white font-semibold text-sm">Project Settings</h2>
                    </div>

                    <form @submit.prevent="saveSettings" class="space-y-4">

                        <div>
                            <label class="block text-slate-400 text-sm mb-1.5">Project Name</label>
                            <input v-model="settingsForm.name" type="text" required
                                   class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2.5 text-white text-sm outline-none focus:border-indigo-500 transition-colors" />
                            <p v-if="settingsForm.errors.name" class="text-red-400 text-xs mt-1">{{ settingsForm.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-slate-400 text-sm mb-1.5">Description</label>
                            <textarea v-model="settingsForm.description" rows="2"
                                      placeholder="What is this project about?"
                                      class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2.5 text-white text-sm outline-none focus:border-indigo-500 resize-none transition-colors" />
                        </div>

                        <div>
                            <label class="block text-sm mb-1.5">
                                <div class="flex items-center gap-1.5">
                                    <Bot class="w-3.5 h-3.5 text-indigo-400" />
                                    <span class="text-slate-400">AI Persona (System Prompt)</span>
                                </div>
                            </label>
                            <textarea v-model="settingsForm.system_prompt" rows="4"
                                      placeholder="You are a helpful assistant specialized in..."
                                      class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2.5 text-white text-sm outline-none focus:border-indigo-500 resize-none transition-colors" />
                            <p class="text-slate-600 text-xs mt-1 flex items-center gap-1">
                                <Info class="w-3 h-3" /> Sets the AI's behavior and personality for all chats in this project.
                            </p>
                        </div>

                        <!-- AI Model selector -->
                        <div>
                            <label class="block text-sm mb-1.5">
                                <div class="flex items-center gap-1.5">
                                    <Zap class="w-3.5 h-3.5 text-indigo-400" />
                                    <span class="text-slate-400">AI Model</span>
                                </div>
                            </label>
                            <select v-model="settingsForm.model"
                                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2.5 text-white text-sm outline-none focus:border-indigo-500 transition-colors">
                                <option v-for="m in ollamaModels" :key="m" :value="m">
                                    {{ modelLabel(m) }}
                                </option>
                            </select>
                            <p class="text-slate-600 text-xs mt-1 flex items-center gap-1">
                                <Info class="w-3 h-3" />
                                Currently saved: <span class="text-indigo-400 ml-1">{{ modelLabel(project.model) }}</span>
                            </p>
                            <p v-if="settingsForm.errors.model" class="text-red-400 text-xs mt-1">{{ settingsForm.errors.model }}</p>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <p v-if="settingsForm.wasSuccessful" class="text-green-400 text-sm flex items-center gap-1">
                                <RefreshCw class="w-3.5 h-3.5" /> Settings saved!
                            </p>
                            <div v-else />
                            <button type="submit" :disabled="settingsForm.processing"
                                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors">
                                <Save class="w-4 h-4" />
                                {{ settingsForm.processing ? 'Saving...' : 'Save Settings' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Project Memory -->
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <Brain class="w-4 h-4 text-purple-400" />
                            <h2 class="text-white font-semibold text-sm">Project Memory</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <button v-if="project.context_summary && !editingMemory" @click="toggleMemory"
                                    class="p-1.5 text-slate-500 hover:text-slate-300 hover:bg-slate-800 rounded-lg transition-colors">
                                <EyeOff v-if="showFullMemory" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                            <button v-if="project.context_summary && !editingMemory" @click="startEditMemory"
                                    class="p-1.5 text-slate-500 hover:text-indigo-400 hover:bg-slate-800 rounded-lg transition-colors">
                                <Edit2 class="w-4 h-4" />
                            </button>
                            <button v-if="project.context_summary" @click="clearMemory"
                                    class="p-1.5 text-slate-500 hover:text-red-400 hover:bg-red-400/10 rounded-lg transition-colors">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="!project.context_summary && !editingMemory" class="text-center py-6">
                        <Brain class="w-8 h-8 text-slate-700 mx-auto mb-2" />
                        <p class="text-slate-500 text-sm">No memory yet.</p>
                        <p class="text-slate-600 text-xs mt-1">Memory is automatically generated after 20 messages in a chat.</p>
                    </div>

                    <div v-else-if="project.context_summary && !editingMemory">
                        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
                            <p class="text-slate-300 text-sm leading-relaxed whitespace-pre-wrap">
                                {{ showFullMemory ? project.context_summary : memoryPreview }}
                            </p>
                        </div>
                        <p class="text-slate-600 text-xs mt-2 flex items-center gap-1">
                            <Info class="w-3 h-3" /> Automatically included in all new chats within this project.
                        </p>
                    </div>

                    <div v-else-if="editingMemory">
                        <textarea v-model="memoryContent" rows="8"
                                  placeholder="Enter project context and memory..."
                                  class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2.5 text-white text-sm outline-none focus:border-indigo-500 resize-none transition-colors" />
                        <div class="flex justify-end gap-2 mt-3">
                            <button @click="editingMemory = false"
                                    class="px-4 py-2 text-slate-400 hover:text-slate-200 text-sm rounded-lg hover:bg-slate-800 transition-colors">
                                Cancel
                            </button>
                            <button @click="saveMemory"
                                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                <Save class="w-4 h-4" /> Save Memory
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>