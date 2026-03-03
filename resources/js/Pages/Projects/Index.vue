<script setup>
import { ref, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    FolderOpen, Plus, Trash2, ChevronRight,
    MessageSquare, X, Bot
} from 'lucide-vue-next'

const props = defineProps({
    projects: Array,
})

const models = computed(() => usePage().props.ollama_models)

const showModal = ref(false)
const form = useForm({
    name:        '',
    description: '',
    model:       computed(() => models.value?.[0] ?? ''),
})

function submit() {
    form.post(route('projects.store'), {
        onSuccess: () => {
            showModal.value = false
            form.reset()
        },
    })
}

function deleteProject(project) {
    if (!confirm(`Delete "${project.name}" and all its chats?`)) return
    router.delete(route('projects.destroy', project.id))
}
</script>

<template>
    <AppLayout title="Projects">
        <div class="max-w-6xl mx-auto px-6 py-8">

            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <FolderOpen class="w-6 h-6 text-indigo-400" />
                    <h1 class="text-2xl font-bold text-white">Projects</h1>
                </div>
                <button
                    @click="showModal = true"
                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                >
                    <Plus class="w-4 h-4" />
                    New Project
                </button>
            </div>

            <!-- Empty state -->
            <div v-if="projects.length === 0" class="text-center py-24">
                <FolderOpen class="w-16 h-16 text-slate-600 mx-auto mb-4" />
                <h3 class="text-lg font-semibold text-slate-300 mb-2">No projects yet</h3>
                <p class="text-slate-500 mb-6">
                    Create your first project to start chatting with AI.
                </p>
                <button
                    @click="showModal = true"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2.5 rounded-lg font-medium transition-colors"
                >
                    <Plus class="w-4 h-4" />
                    Create Project
                </button>
            </div>

            <!-- Project grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="project in projects"
                    :key="project.id"
                    class="bg-slate-900 border border-slate-800 rounded-xl p-5 hover:border-slate-700 transition-colors group"
                >
                    <!-- Card header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-indigo-600/20 rounded-lg flex items-center justify-center">
                                <FolderOpen class="w-5 h-5 text-indigo-400" />
                            </div>
                            <div>
                                <h3 class="text-white font-semibold text-sm leading-tight">
                                    {{ project.name }}
                                </h3>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <Bot class="w-3 h-3 text-slate-500" />
                                    <span class="text-xs text-slate-500">{{ project.model }}</span>
                                </div>
                            </div>
                        </div>
                        <button
                            @click.stop="deleteProject(project)"
                            class="opacity-0 group-hover:opacity-100 p-1.5 text-slate-600 hover:text-red-400 hover:bg-red-400/10 rounded-lg transition-all"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Description -->
                    <p v-if="project.description"
                       class="text-slate-400 text-sm mb-4 line-clamp-2">
                        {{ project.description }}
                    </p>
                    <p v-else class="text-slate-600 text-sm mb-4 italic">
                        No description
                    </p>

                    <!-- Footer -->
                    <div class="flex items-center justify-between pt-3 border-t border-slate-800">
                        <div class="flex items-center gap-1.5 text-slate-500 text-xs">
                            <MessageSquare class="w-3.5 h-3.5" />
                            <span>
                                {{ project.chats_count }}
                                chat{{ project.chats_count !== 1 ? 's' : '' }}
                            </span>
                        </div>
                        <a
                            :href="route('projects.show', project.id)"
                            class="flex items-center gap-1 text-indigo-400 hover:text-indigo-300 text-xs font-medium transition-colors"
                        >
                            Open
                            <ChevronRight class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <!-- New Project Modal -->
        <Teleport to="body">
            <div
                v-if="showModal"
                class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 px-4"
                @click.self="showModal = false"
            >
                <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md p-6">

                    <!-- Modal header -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-2">
                            <FolderOpen class="w-5 h-5 text-indigo-400" />
                            <h2 class="text-lg font-semibold text-white">New Project</h2>
                        </div>
                        <button
                            @click="showModal = false"
                            class="text-slate-500 hover:text-slate-300 transition-colors"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">

                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1.5">
                                Project Name <span class="text-red-400">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="e.g. Customer Support Bot"
                                autofocus
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 text-sm"
                            />
                            <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1.5">
                                Description
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="What is this project for?"
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 text-sm resize-none"
                            />
                        </div>

                        <!-- Model -->
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1.5">
                                <div class="flex items-center gap-1.5">
                                    <Bot class="w-3.5 h-3.5 text-slate-400" />
                                    AI Model
                                </div>
                            </label>
                            <select
                                v-model="form.model"
                                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-indigo-500 text-sm"
                            >
                                <option
                                    v-for="m in models"
                                    :key="m"
                                    :value="m"
                                >
                                    {{ m }}
                                </option>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3 pt-2">
                            <button
                                type="button"
                                @click="showModal = false"
                                class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || !form.name"
                                class="flex-1 flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
                            >
                                <Plus class="w-4 h-4" />
                                {{ form.processing ? 'Creating...' : 'Create Project' }}
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>