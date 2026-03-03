<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    FileText, Plus, Trash2, Copy,
    Globe, Lock, Edit2, CheckCircle, X
} from 'lucide-vue-next'

const props = defineProps({
    my_templates:     { type: Array, default: () => [] },
    shared_templates: { type: Array, default: () => [] },
})

// ── Create ─────────────────────────────────────────────────────────
const showCreate = ref(false)
const createForm = useForm({
    name:      '',
    content:   '',
    is_shared: false,
})

function submitCreate() {
    createForm.post(route('templates.store'), {
        onSuccess: () => {
            showCreate.value = false
            createForm.reset()
        },
    })
}

// ── Edit ───────────────────────────────────────────────────────────
const editing = ref(null)

function startEdit(template) {
    editing.value = { ...template }
}

function submitEdit() {
    router.put(route('templates.update', editing.value.id), {
        name:      editing.value.name,
        content:   editing.value.content,
        is_shared: editing.value.is_shared,
    }, {
        onSuccess: () => editing.value = null,
        preserveScroll: true,
    })
}

// ── Delete ─────────────────────────────────────────────────────────
function deleteTemplate(template) {
    if (!confirm(`Delete "${template.name}"?`)) return
    router.delete(route('templates.destroy', template.id), {
        preserveScroll: true,
    })
}

// ── Copy ───────────────────────────────────────────────────────────
const copied = ref(null)

function copyContent(template) {
    navigator.clipboard.writeText(template.content)
    copied.value = template.id
    setTimeout(() => copied.value = null, 2000)
}

// ── Toggle shared ──────────────────────────────────────────────────
function toggleShared(template) {
    router.put(route('templates.update', template.id), {
        name:      template.name,
        content:   template.content,
        is_shared: !template.is_shared,
    }, { preserveScroll: true })
}
</script>

<template>
    <AppLayout title="Templates">
        <div class="max-w-5xl mx-auto px-6 py-8">

            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <FileText class="w-6 h-6 text-indigo-400" />
                    <div>
                        <h1 class="text-2xl font-bold text-white">Prompt Templates</h1>
                        <p class="text-slate-400 text-sm mt-0.5">Reusable prompts for your AI chats</p>
                    </div>
                </div>
                <button
                    @click="showCreate = true"
                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                >
                    <Plus class="w-4 h-4" />
                    New Template
                </button>
            </div>

            <!-- ── My Templates ── -->
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4">
                    <Lock class="w-4 h-4 text-slate-400" />
                    <h2 class="text-white font-semibold text-sm">My Templates</h2>
                    <span class="bg-slate-800 text-slate-400 text-xs px-2 py-0.5 rounded-full">
                        {{ my_templates.length }}
                    </span>
                </div>

                <div v-if="my_templates.length === 0"
                     class="bg-slate-900 border border-slate-800 rounded-xl p-8 text-center">
                    <FileText class="w-8 h-8 text-slate-700 mx-auto mb-2" />
                    <p class="text-slate-500 text-sm">No templates yet.</p>
                    <button
                        @click="showCreate = true"
                        class="mt-3 text-indigo-400 hover:text-indigo-300 text-sm"
                    >
                        Create your first template
                    </button>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="template in my_templates"
                        :key="template.id"
                        class="bg-slate-900 border border-slate-800 rounded-xl p-4 hover:border-slate-700 transition-colors"
                    >
                        <!-- View mode -->
                        <template v-if="editing?.id !== template.id">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0 flex-1">
                                    <div class="w-8 h-8 bg-indigo-600/20 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                        <FileText class="w-4 h-4 text-indigo-400" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <p class="text-white font-medium text-sm">{{ template.name }}</p>
                                            <span
                                                class="flex items-center gap-1 text-xs px-1.5 py-0.5 rounded-full"
                                                :class="template.is_shared
                                                    ? 'bg-green-500/10 text-green-400'
                                                    : 'bg-slate-800 text-slate-500'"
                                            >
                                                <Globe v-if="template.is_shared" class="w-3 h-3" />
                                                <Lock v-else class="w-3 h-3" />
                                                {{ template.is_shared ? 'Shared' : 'Private' }}
                                            </span>
                                        </div>
                                        <p class="text-slate-400 text-xs leading-relaxed line-clamp-2">
                                            {{ template.content }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-1 shrink-0">
                                    <button
                                        @click="copyContent(template)"
                                        class="p-1.5 text-slate-500 hover:text-indigo-400 hover:bg-slate-800 rounded-lg transition-colors"
                                        :title="copied === template.id ? 'Copied!' : 'Copy content'"
                                    >
                                        <CheckCircle v-if="copied === template.id" class="w-4 h-4 text-green-400" />
                                        <Copy v-else class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click="toggleShared(template)"
                                        class="p-1.5 text-slate-500 hover:text-indigo-400 hover:bg-slate-800 rounded-lg transition-colors"
                                        :title="template.is_shared ? 'Make private' : 'Share with team'"
                                    >
                                        <Globe v-if="template.is_shared" class="w-4 h-4 text-green-400" />
                                        <Globe v-else class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click="startEdit(template)"
                                        class="p-1.5 text-slate-500 hover:text-indigo-400 hover:bg-slate-800 rounded-lg transition-colors"
                                        title="Edit"
                                    >
                                        <Edit2 class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click="deleteTemplate(template)"
                                        class="p-1.5 text-slate-500 hover:text-red-400 hover:bg-red-400/10 rounded-lg transition-colors"
                                        title="Delete"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- Edit mode -->
                        <template v-else>
                            <div class="space-y-3">
                                <input
                                    v-model="editing.name"
                                    type="text"
                                    placeholder="Template name"
                                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm outline-none focus:border-indigo-500"
                                />
                                <textarea
                                    v-model="editing.content"
                                    rows="3"
                                    placeholder="Template content..."
                                    class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm outline-none focus:border-indigo-500 resize-none"
                                />
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="editing.is_shared"
                                            class="rounded"
                                        />
                                        <span class="text-slate-400 text-xs">Share with team</span>
                                    </label>
                                    <div class="flex gap-2">
                                        <button
                                            @click="editing = null"
                                            class="text-slate-400 hover:text-slate-200 text-xs px-3 py-1.5 rounded-lg hover:bg-slate-800 transition-colors"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            @click="submitEdit"
                                            class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs px-3 py-1.5 rounded-lg transition-colors"
                                        >
                                            Save
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                    </div>
                </div>
            </div>

            <!-- ── Shared Templates ── -->
            <div v-if="shared_templates.length > 0">
                <div class="flex items-center gap-2 mb-4">
                    <Globe class="w-4 h-4 text-green-400" />
                    <h2 class="text-white font-semibold text-sm">Shared by Team</h2>
                    <span class="bg-slate-800 text-slate-400 text-xs px-2 py-0.5 rounded-full">
                        {{ shared_templates.length }}
                    </span>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="template in shared_templates"
                        :key="template.id"
                        class="bg-slate-900 border border-slate-800 rounded-xl p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 min-w-0 flex-1">
                                <div class="w-8 h-8 bg-green-600/20 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                    <FileText class="w-4 h-4 text-green-400" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-white font-medium text-sm mb-1">{{ template.name }}</p>
                                    <p class="text-slate-400 text-xs leading-relaxed line-clamp-2">
                                        {{ template.content }}
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="copyContent(template)"
                                class="p-1.5 text-slate-500 hover:text-indigo-400 hover:bg-slate-800 rounded-lg transition-colors shrink-0"
                                :title="copied === template.id ? 'Copied!' : 'Copy content'"
                            >
                                <CheckCircle v-if="copied === template.id" class="w-4 h-4 text-green-400" />
                                <Copy v-else class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── Create Modal ── -->
        <Teleport to="body">
            <div
                v-if="showCreate"
                class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 px-4"
                @click.self="showCreate = false"
            >
                <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-lg p-6">

                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-2">
                            <FileText class="w-5 h-5 text-indigo-400" />
                            <h2 class="text-white font-semibold">New Template</h2>
                        </div>
                        <button @click="showCreate = false" class="text-slate-500 hover:text-slate-300">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitCreate" class="space-y-4">

                        <div>
                            <label class="block text-slate-400 text-sm mb-1.5">Name</label>
                            <input
                                v-model="createForm.name"
                                type="text"
                                placeholder="e.g. Laravel Expert"
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2.5 text-white text-sm outline-none focus:border-indigo-500"
                            />
                            <p v-if="createForm.errors.name" class="text-red-400 text-xs mt-1">
                                {{ createForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-slate-400 text-sm mb-1.5">Content</label>
                            <textarea
                                v-model="createForm.content"
                                rows="5"
                                placeholder="You are a helpful Laravel expert..."
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2.5 text-white text-sm outline-none focus:border-indigo-500 resize-none"
                            />
                            <p v-if="createForm.errors.content" class="text-red-400 text-xs mt-1">
                                {{ createForm.errors.content }}
                            </p>
                        </div>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="createForm.is_shared"
                                class="rounded"
                            />
                            <div>
                                <p class="text-slate-300 text-sm font-medium">Share with team</p>
                                <p class="text-slate-500 text-xs">
                                    All workspace members can use this template
                                </p>
                            </div>
                        </label>

                        <div class="flex gap-3 pt-2">
                            <button
                                type="button"
                                @click="showCreate = false"
                                class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="createForm.processing || !createForm.name || !createForm.content"
                                class="flex-1 flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
                            >
                                <Plus class="w-4 h-4" />
                                {{ createForm.processing ? 'Creating...' : 'Create Template' }}
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>