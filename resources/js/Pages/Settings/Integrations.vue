<template>
  <AppLayout>
    <div class="max-w-3xl mx-auto py-8 px-4">
      <h1 class="text-2xl font-bold text-white mb-6 flex items-center gap-2">
        <Plug class="w-6 h-6" /> Integrations
      </h1>

      <!-- Flash messages -->
      <div v-if="$page.props.flash?.success"
           class="mb-4 p-3 bg-green-500/20 border border-green-500/30 rounded-lg text-green-400 text-sm flex items-center gap-2">
        <CheckCircle class="w-4 h-4" /> {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error"
           class="mb-4 p-3 bg-red-500/20 border border-red-500/30 rounded-lg text-red-400 text-sm flex items-center gap-2">
        <AlertCircle class="w-4 h-4" /> {{ $page.props.flash.error }}
      </div>

      <!-- GitHub Section -->
      <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 mb-4">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-slate-700 rounded-lg flex items-center justify-center">
              <Github class="w-5 h-5 text-white" />
            </div>
            <div>
              <h2 class="text-white font-semibold">GitHub</h2>
              <p class="text-slate-400 text-sm">Import files from your repositories as project knowledge</p>
            </div>
          </div>
          <!-- Connected badge -->
          <span v-if="github.connected"
                class="flex items-center gap-1 text-green-400 text-sm bg-green-500/10 px-3 py-1 rounded-full">
            <CheckCircle class="w-3 h-3" /> Connected
          </span>
        </div>

        <!-- Not connected -->
        <div v-if="!github.connected">
          <a :href="route('github.redirect')"
             class="inline-flex items-center gap-2 px-4 py-2 bg-white text-slate-900 rounded-lg text-sm font-medium hover:bg-slate-100 transition">
            <Github class="w-4 h-4" /> Connect GitHub
          </a>
        </div>

        <!-- Connected -->
        <div v-else>
          <div class="flex items-center justify-between mb-4">
            <span class="text-slate-300 text-sm flex items-center gap-2">
              <User class="w-4 h-4" /> {{ github.github_user }}
            </span>
            <button @click="disconnect"
                    class="flex items-center gap-1 text-red-400 hover:text-red-300 text-sm transition">
              <Unlink class="w-4 h-4" /> Disconnect
            </button>
          </div>

          <!-- Project selector -->
          <div class="mb-4">
            <label class="block text-slate-400 text-xs mb-1">Select Project</label>
            <select v-model="selectedProjectId"
                    class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-3 py-2 text-sm">
              <option value="">-- Choose a project --</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>

          <!-- Repo selector -->
          <div v-if="selectedProjectId" class="mb-4">
            <div class="flex items-center justify-between mb-1">
              <label class="text-slate-400 text-xs">Repository</label>
              <button @click="loadRepos" class="text-slate-400 hover:text-white">
                <RefreshCw class="w-3 h-3" :class="{ 'animate-spin': loadingRepos }" />
              </button>
            </div>
            <select v-model="selectedRepo" @change="loadContents('')"
                    class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-3 py-2 text-sm">
              <option value="">-- Choose a repo --</option>
              <option v-for="r in repos" :key="r.full_name" :value="r.full_name">
                {{ r.full_name }} {{ r.private ? '🔒' : '' }}
              </option>
            </select>
          </div>

          <!-- File browser -->
          <div v-if="selectedRepo" class="mb-4">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-1 text-xs text-slate-400 mb-2 flex-wrap">
              <button @click="loadContents('')" class="hover:text-white">root</button>
              <template v-for="(crumb, i) in breadcrumbs" :key="i">
                <ChevronRight class="w-3 h-3" />
                <button @click="loadContents(crumb.path)" class="hover:text-white">{{ crumb.name }}</button>
              </template>
            </div>

            <!-- File list -->
            <div v-if="loadingContents" class="text-slate-400 text-sm py-2">Loading...</div>
            <div v-else class="bg-slate-700/50 rounded-lg overflow-hidden divide-y divide-slate-700">
              <div v-for="item in contents" :key="item.path"
                   class="flex items-center justify-between px-3 py-2 hover:bg-slate-700 transition">
                <button v-if="item.type === 'dir'"
                        @click="loadContents(item.path)"
                        class="flex items-center gap-2 text-sm text-slate-300 hover:text-white">
                  <FolderOpen class="w-4 h-4 text-yellow-400" /> {{ item.name }}
                </button>
                <div v-else class="flex items-center gap-2 text-sm text-slate-300">
                  <File class="w-4 h-4 text-blue-400" /> {{ item.name }}
                  <span class="text-slate-500 text-xs">{{ formatBytes(item.size) }}</span>
                </div>
                <button v-if="item.type === 'file'"
                        @click="importFile(item)"
                        :disabled="importing[item.path]"
                        class="flex items-center gap-1 px-2 py-1 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white text-xs rounded transition">
                  <Plus class="w-3 h-3" />
                  {{ importing[item.path] ? 'Adding...' : 'Add to Project' }}
                </button>
              </div>
              <div v-if="!contents.length" class="px-3 py-4 text-slate-400 text-sm text-center">
                Empty directory
              </div>
            </div>
          </div>

          <!-- Attached files for selected project -->
          <div v-if="selectedProjectId && attachedFiles.length">
            <h3 class="text-slate-400 text-xs mb-2 uppercase tracking-wide">Attached Files</h3>
            <div class="space-y-2">
              <div v-for="f in attachedFiles" :key="f.id"
                   class="flex items-center justify-between bg-slate-700/50 rounded-lg px-3 py-2">
                <div class="flex items-center gap-2 text-sm text-slate-300">
                  <FileText class="w-4 h-4 text-green-400" />
                  <span>{{ f.name }}</span>
                  <span class="text-slate-500 text-xs">{{ formatBytes(f.byte_size) }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <button @click="syncAttached(f)" class="text-slate-400 hover:text-white" title="Re-sync">
                    <RefreshCw class="w-3 h-3" />
                  </button>
                  <button @click="deleteAttached(f)" class="text-red-400 hover:text-red-300" title="Remove">
                    <Trash2 class="w-3 h-3" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Google Drive Section (M26 placeholder) -->
      <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 opacity-60">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-slate-700 rounded-lg flex items-center justify-center">
            <HardDrive class="w-5 h-5 text-slate-400" />
          </div>
          <div>
            <h2 class="text-white font-semibold">Google Drive</h2>
            <p class="text-slate-400 text-sm">Coming soon — import Docs, Sheets, and files as knowledge</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  Github, HardDrive, CheckCircle, AlertCircle, Plug,
  RefreshCw, FolderOpen, File, FileText, Plus, Trash2,
  ChevronRight, User, Unlink,
} from 'lucide-vue-next'

const props = defineProps({
  github:   Object, // { connected, github_user }
  projects: Array,
})

// ── State ─────────────────────────────────────────────────────────
const selectedProjectId = ref('')
const selectedRepo      = ref('')
const repos             = ref([])
const contents          = ref([])
const breadcrumbs       = ref([])
const attachedFiles     = ref([])
const loadingRepos      = ref(false)
const loadingContents   = ref(false)
const importing         = ref({})

// ── Load repos ────────────────────────────────────────────────────
const loadRepos = async () => {
  loadingRepos.value = true
  const res = await fetch(route('github.repos'))
  const json = await res.json()
  repos.value = json.data ?? []
  loadingRepos.value = false
}

// ── Load dir contents ─────────────────────────────────────────────
const currentPath = ref('')

const loadContents = async (path) => {
  if (!selectedRepo.value) return
  loadingContents.value = true
  currentPath.value = path

  // Update breadcrumbs
  if (path === '') {
    breadcrumbs.value = []
  } else {
    const parts = path.split('/')
    breadcrumbs.value = parts.map((name, i) => ({
      name,
      path: parts.slice(0, i + 1).join('/'),
    }))
  }

  const url = route('github.contents') + `?repo=${encodeURIComponent(selectedRepo.value)}&path=${encodeURIComponent(path)}`
  const res  = await fetch(url)
  const json = await res.json()
  contents.value = json.data ?? []
  loadingContents.value = false
}

// ── Import file ───────────────────────────────────────────────────
const importFile = async (item) => {
  importing.value[item.path] = true
  const res = await fetch(route('github.import'), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    },
    body: JSON.stringify({
      project_id: selectedProjectId.value,
      repo: selectedRepo.value,
      path: item.path,
      name: item.name,
    }),
  })
  const json = await res.json()
  importing.value[item.path] = false
  if (json.success) loadAttachedFiles()
}

// ── Load attached files ───────────────────────────────────────────
const loadAttachedFiles = async () => {
  if (!selectedProjectId.value) return
  const url = `/api/v1/projects/${selectedProjectId.value}/integration-files`
  const res  = await fetch(url, {
    headers: { 'Accept': 'application/json' },
  })
  const json = await res.json()
  attachedFiles.value = json.data ?? []
}

// ── Sync / delete attached ────────────────────────────────────────
const syncAttached = async (f) => {
  await fetch(route('github.file.sync', f.id), {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
  })
  loadAttachedFiles()
}

const deleteAttached = async (f) => {
  if (!confirm(`Remove "${f.name}" from this project?`)) return
  await fetch(route('github.file.delete', f.id), {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
  })
  loadAttachedFiles()
}

// ── Disconnect ────────────────────────────────────────────────────
const disconnect = () => {
  router.post(route('github.disconnect'))
}

// ── Watch project change ──────────────────────────────────────────
watch(selectedProjectId, (val) => {
  if (val) {
    loadRepos()
    loadAttachedFiles()
    selectedRepo.value = ''
    contents.value = []
    breadcrumbs.value = []
  }
})

// ── Helpers ───────────────────────────────────────────────────────
const formatBytes = (bytes) => {
  if (!bytes) return ''
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / 1024 / 1024).toFixed(1) + ' MB'
}
</script>