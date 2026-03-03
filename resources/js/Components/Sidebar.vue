<script setup>
import { ref } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import {
    FolderOpen, MessageSquare, Plus, ChevronDown,
    ChevronRight, Trash2
} from 'lucide-vue-next'

const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
})

const page             = usePage()
const expandedProjects = ref({})

function toggleProject(id) {
    expandedProjects.value[id] = !expandedProjects.value[id]
}

function isExpanded(id) {
    return !!expandedProjects.value[id]
}

function createChat(projectId) {
    router.post(route('projects.chats.store', projectId), {})
}

function deleteChat(projectId, chat) {
    if (!confirm('Delete this chat?')) return
    router.delete(route('projects.chats.destroy', [projectId, chat.id]))
}

function isChatActive(chatId) {
    return page.url.includes(`/chats/${chatId}`)
}

function isProjectActive(projectId) {
    return page.url.includes(`/projects/${projectId}`)
}
</script>

<template>
    <div class="px-3 py-2">
        <!-- Section header -->
        <div class="flex items-center justify-between px-2 mb-2">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                Projects
            </span>
            <Link
                :href="route('projects.index')"
                class="p-1 text-slate-500 hover:text-indigo-400 hover:bg-slate-800 rounded transition-colors"
                title="All Projects"
            >
                <Plus class="w-3.5 h-3.5" />
            </Link>
        </div>

        <!-- Empty -->
        <div v-if="!projects || projects.length === 0"
             class="px-2 py-3 text-slate-600 text-xs text-center">
            No projects yet
        </div>

        <!-- Project list -->
        <div v-else class="space-y-0.5">
            <div v-for="project in projects" :key="project.id">

                <!-- Project row -->
                <div
                    class="flex items-center gap-1 rounded-lg group"
                    :class="isProjectActive(project.id) ? 'bg-slate-800' : 'hover:bg-slate-800/50'"
                >
                    <button
                        @click="toggleProject(project.id)"
                        class="flex-1 flex items-center gap-2 px-2 py-2 text-left min-w-0"
                    >
                        <component
                            :is="isExpanded(project.id) ? ChevronDown : ChevronRight"
                            class="w-3 h-3 text-slate-600 shrink-0"
                        />
                        <FolderOpen class="w-3.5 h-3.5 text-slate-500 shrink-0" />
                        <span
                            class="text-xs font-medium truncate"
                            :class="isProjectActive(project.id) ? 'text-white' : 'text-slate-400'"
                        >
                            {{ project.name }}
                        </span>
                    </button>

                    <!-- New chat button (hover) -->
                    <button
                        @click.stop="createChat(project.id)"
                        class="opacity-0 group-hover:opacity-100 p-1.5 mr-1 text-slate-600 hover:text-indigo-400 transition-all shrink-0"
                        title="New Chat"
                    >
                        <Plus class="w-3 h-3" />
                    </button>
                </div>

                <!-- Chats list (expanded) -->
                <div v-if="isExpanded(project.id) && project.chats?.length" class="ml-4 mt-0.5 space-y-0.5">
                    <div
                        v-for="chat in project.chats"
                        :key="chat.id"
                        class="flex items-center gap-1 rounded-lg group"
                        :class="isChatActive(chat.id)
                            ? 'bg-indigo-600/20'
                            : 'hover:bg-slate-800/50'"
                    >
                        <Link
                            :href="route('projects.chats.show', [project.id, chat.id])"
                            class="flex-1 flex items-center gap-2 px-2 py-1.5 min-w-0"
                        >
                            <MessageSquare class="w-3 h-3 text-slate-600 shrink-0" />
                            <span
                                class="text-xs truncate"
                                :class="isChatActive(chat.id) ? 'text-indigo-300' : 'text-slate-500'"
                                style="max-width: 120px"
                            >
                                {{ chat.title || 'New Chat' }}
                            </span>
                        </Link>

                        <!-- Delete chat -->
                        <button
                            @click.stop="deleteChat(project.id, chat)"
                            class="opacity-0 group-hover:opacity-100 p-1 mr-1 text-slate-700 hover:text-red-400 transition-all shrink-0"
                            title="Delete chat"
                        >
                            <Trash2 class="w-3 h-3" />
                        </button>
                    </div>
                </div>

                <!-- Empty chats state -->
                <div
                    v-else-if="isExpanded(project.id)"
                    class="ml-6 py-1 text-slate-700 text-xs"
                >
                    No chats yet
                </div>

            </div>
        </div>
    </div>
</template>