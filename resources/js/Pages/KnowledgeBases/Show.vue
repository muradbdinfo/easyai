<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  Brain, FileText, Upload, Trash2, Plus, CheckCircle,
  AlertCircle, Clock, RefreshCw, Save, X, File
} from 'lucide-vue-next'

const props = defineProps({
  project: Object,
  chat:    Object,   // null = project-level
  kb:      Object,
  level:   String,   // 'project' | 'chat'
})

const isChatLevel   = computed(() => props.level === 'chat')
const showCreateForm = ref(!props.kb)
const fileInput      = ref(null)

// ── KB form ────────────────────────────────────────────────────────
const kbForm = useForm({
  name:        props.kb?.name        ?? '',
  description: props.kb?.description ?? '',
})

const saveKb = () => {
  const url = isChatLevel.value
    ? route('kb.chat.store', [props.project.id, props.chat.id])
    : route('kb.store', props.project.id)

  kbForm.post(url, {
    preserveScroll: true,
    onSuccess: () => { showCreateForm.value = false },
  })
}

// ── Upload form ────────────────────────────────────────────────────
const uploadForm = useForm({ file: null, title: '', chat_id: props.chat?.id ?? null })

const pickFile = (e) => { uploadForm.file = e.target.files[0] }

const uploadDoc = () => {
  if (!uploadForm.file) return

  const url = isChatLevel.value
    ? route('kb.chat.upload', [props.project.id, props.chat.id])
    : route('kb.upload', props.project.id)

  uploadForm.post(url, {
    preserveScroll: true,
    onSuccess: () => {
      uploadForm.reset()
      if (fileInput.value) fileInput.value.value = ''
    },
  })
}

const deleteDoc = (docId) => {
  if (!confirm('Delete this document and all its chunks?')) return
  router.delete(route('kb.document.destroy', [props.project.id, docId]), { preserveScroll: true })
}

// ── Status helpers ─────────────────────────────────────────────────
const statusIcon = (status) => ({
  ready:      CheckCircle,
  processing: RefreshCw,
  pending:    Clock,
  failed:     AlertCircle,
}[status] ?? Clock)

const statusColor = (status) => ({
  ready:      'text-green-500',
  processing: 'text-blue-400 animate-spin',
  pending:    'text-yellow-400',
  failed:     'text-red-500',
}[status] ?? 'text-slate-400')

const fileTypeColor = (type) => ({
  pdf:  'bg-red-500/20 text-red-400',
  docx: 'bg-blue-500/20 text-blue-400',
  doc:  'bg-blue-500/20 text-blue-400',
  xlsx: 'bg-green-500/20 text-green-400',
  xls:  'bg-green-500/20 text-green-400',
  txt:  'bg-slate-500/20 text-slate-300',
  md:   'bg-purple-500/20 text-purple-400',
}[type] ?? 'bg-slate-500/20 text-slate-300')

const pageTitle = computed(() =>
  isChatLevel.value
    ? `Chat KB — ${props.chat?.title ?? ''}`
    : `Knowledge Base — ${props.project.name}`
)
</script>

<template>
  <AppLayout :title="pageTitle">
    <div class="max-w-4xl mx-auto px-4 py-8 space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Brain class="w-6 h-6 text-indigo-400" />
          <div>
            <h1 class="text-xl font-semibold text-white">
              {{ isChatLevel ? 'Chat Knowledge Base' : 'Knowledge Base' }}
            </h1>
            <p class="text-xs text-slate-400 mt-0.5">
              {{ isChatLevel ? `Chat: ${chat?.title}` : `Project: ${project.name}` }}
              — {{ isChatLevel ? 'Only active in this chat' : 'Active across all chats in this project' }}
            </p>
          </div>
        </div>
        <button v-if="!showCreateForm" @click="showCreateForm = true"
          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg">
          <Plus class="w-4 h-4" /> {{ kb ? 'Edit KB' : 'Create KB' }}
        </button>
      </div>

      <!-- Level badge -->
      <div class="flex gap-3">
        <span :class="['px-3 py-1 rounded-full text-xs font-medium', isChatLevel ? 'bg-amber-500/20 text-amber-400' : 'bg-indigo-500/20 text-indigo-400']">
          {{ isChatLevel ? '💬 Chat-level KB' : '📁 Project-level KB' }}
        </span>
      </div>

      <!-- Create / Edit KB form -->
      <div v-if="showCreateForm" class="bg-slate-800 rounded-xl p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-white font-medium">{{ kb ? 'Edit' : 'Create' }} Knowledge Base</h2>
          <button @click="showCreateForm = false"><X class="w-5 h-5 text-slate-400 hover:text-white" /></button>
        </div>
        <input v-model="kbForm.name" placeholder="Name (e.g. Product Docs)"
          class="w-full bg-slate-700 text-white rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        <textarea v-model="kbForm.description" placeholder="Description (optional)" rows="2"
          class="w-full bg-slate-700 text-white rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none" />
        <button @click="saveKb" :disabled="kbForm.processing"
          class="flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg disabled:opacity-50">
          <Save class="w-4 h-4" /> Save
        </button>
      </div>

      <!-- No KB -->
      <div v-if="!kb" class="text-center py-20 text-slate-500">
        <Brain class="w-12 h-12 mx-auto mb-3 opacity-30" />
        <p>No knowledge base yet. Create one above.</p>
      </div>

      <template v-else>

        <!-- KB Info -->
        <div class="bg-slate-800 rounded-xl p-5">
          <div class="flex items-center gap-2 mb-1">
            <Brain class="w-5 h-5 text-indigo-400" />
            <span class="text-white font-medium">{{ kb.name }}</span>
            <span class="ml-auto text-xs text-slate-400">{{ kb.documents?.length ?? 0 }} documents</span>
          </div>
          <p v-if="kb.description" class="text-sm text-slate-400">{{ kb.description }}</p>
        </div>

        <!-- Upload -->
        <div class="bg-slate-800 rounded-xl p-5 space-y-4">
          <h3 class="text-white font-medium flex items-center gap-2">
            <Upload class="w-4 h-4 text-indigo-400" /> Upload Document
          </h3>
          <div class="flex flex-wrap gap-2">
            <span v-for="t in ['PDF','DOCX','DOC','XLSX','XLS','TXT','MD']" :key="t"
              class="px-2 py-0.5 rounded text-xs bg-slate-700 text-slate-300">{{ t }}</span>
          </div>
          <p class="text-xs text-slate-400">Max 20MB per file</p>

          <input ref="fileInput" type="file"
            accept=".pdf,.txt,.md,.docx,.doc,.xlsx,.xls"
            @change="pickFile"
            class="block text-sm text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-600 file:text-white file:text-sm hover:file:bg-indigo-500 cursor-pointer" />

          <input v-model="uploadForm.title" placeholder="Title (optional)"
            class="w-full bg-slate-700 text-white rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />

          <button @click="uploadDoc" :disabled="!uploadForm.file || uploadForm.processing"
            class="flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg disabled:opacity-50">
            <Upload class="w-4 h-4" />
            {{ uploadForm.processing ? 'Uploading...' : 'Upload & Process' }}
          </button>
        </div>

        <!-- Documents List -->
        <div class="bg-slate-800 rounded-xl overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-700 flex items-center gap-2">
            <FileText class="w-4 h-4 text-indigo-400" />
            <span class="text-white font-medium">Documents</span>
          </div>

          <div v-if="!kb.documents?.length" class="px-5 py-10 text-center text-slate-500">
            <FileText class="w-8 h-8 mx-auto mb-2 opacity-30" />
            <p class="text-sm">No documents yet.</p>
          </div>

          <div v-else class="divide-y divide-slate-700">
            <div v-for="doc in kb.documents" :key="doc.id"
              class="flex items-center gap-3 px-5 py-3 hover:bg-slate-700/50">

              <component :is="statusIcon(doc.status)"
                :class="['w-4 h-4 flex-shrink-0', statusColor(doc.status)]" />

              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <p class="text-sm text-white truncate">{{ doc.title }}</p>
                  <span :class="['px-1.5 py-0.5 rounded text-xs font-medium uppercase', fileTypeColor(doc.file_type)]">
                    {{ doc.file_type }}
                  </span>
                </div>
                <p class="text-xs text-slate-400 capitalize mt-0.5">
                  {{ doc.status }}
                  <span v-if="doc.status === 'ready'"> · {{ doc.chunk_count }} chunks</span>
                  <span v-if="doc.error_message" class="text-red-400"> · {{ doc.error_message }}</span>
                </p>
              </div>

              <button @click="deleteDoc(doc.id)" class="text-slate-500 hover:text-red-400 transition-colors flex-shrink-0">
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- RAG info -->
        <div class="bg-indigo-900/20 border border-indigo-800 rounded-xl p-4 flex gap-3">
          <Brain class="w-5 h-5 text-indigo-400 flex-shrink-0 mt-0.5" />
          <div>
            <p class="text-sm text-indigo-200 font-medium">RAG Active ✓</p>
            <p class="text-xs text-indigo-300 mt-1">
              <template v-if="isChatLevel">
                This knowledge base is only used in this specific chat.
              </template>
              <template v-else>
                This knowledge base is automatically searched for every chat in <strong>{{ project.name }}</strong>.
              </template>
              Relevant content is injected into AI responses automatically.
            </p>
          </div>
        </div>

      </template>
    </div>
  </AppLayout>
</template>