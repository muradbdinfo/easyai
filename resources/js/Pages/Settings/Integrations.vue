<template>
  <AppLayout title="Integrations">
    <div class="max-w-2xl mx-auto py-8 px-4">

      <!-- Flash -->
      <div v-if="$page.props.flash?.success"
           class="mb-4 p-3 bg-green-500/20 border border-green-500/30 rounded-lg text-green-400 text-sm flex items-center gap-2">
        <CheckCircle class="w-4 h-4 shrink-0"/> {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error"
           class="mb-4 p-3 bg-red-500/20 border border-red-500/30 rounded-lg text-red-400 text-sm flex items-center gap-2">
        <AlertCircle class="w-4 h-4 shrink-0"/> {{ $page.props.flash.error }}
      </div>

      <h1 class="text-xl font-bold text-white mb-6">Integrations</h1>

      <!-- GitHub Card -->
      <div class="bg-slate-800 border border-slate-700 rounded-xl p-5">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center shrink-0">
              <Github class="w-5 h-5 text-slate-900"/>
            </div>
            <div>
              <p class="text-white font-medium text-sm">GitHub</p>
              <p class="text-slate-400 text-xs">Import repo files as project knowledge</p>
            </div>
          </div>

          <div v-if="github.connected" class="flex items-center gap-3">
            <span class="text-slate-400 text-xs flex items-center gap-1">
              <User class="w-3 h-3"/> {{ github.github_user }}
            </span>
            <button @click="openModal"
                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs rounded-lg transition flex items-center gap-1">
              <Plus class="w-3 h-3"/> Add content
            </button>
            <button @click="disconnect" title="Disconnect"
                    class="text-slate-500 hover:text-red-400 transition">
              <Unlink class="w-4 h-4"/>
            </button>
          </div>

          <a v-else :href="route('github.redirect')"
             class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-900 text-xs rounded-lg transition font-medium flex items-center gap-1">
            <Github class="w-3 h-3"/> Connect
          </a>
        </div>

        <!-- Attached files list -->
        <div v-if="github.connected && attachedFiles.length"
             class="mt-4 border-t border-slate-700 pt-4">
          <p class="text-slate-400 text-xs mb-2 uppercase tracking-wide">Added content</p>
          <div class="space-y-1">
            <div v-for="f in attachedFiles" :key="f.id"
                 class="flex items-center justify-between py-1.5 px-2 rounded-lg hover:bg-slate-700/50 group">
              <div class="flex items-center gap-2 text-sm text-slate-300 min-w-0">
                <FileText class="w-3.5 h-3.5 text-slate-400 shrink-0"/>
                <span class="truncate">{{ f.name }}</span>
                <span class="text-slate-500 text-xs shrink-0">{{ formatBytes(f.byte_size) }}</span>
              </div>
              <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                <button @click="syncFile(f)" title="Re-sync"
                        class="text-slate-400 hover:text-white">
                  <RefreshCw class="w-3 h-3"/>
                </button>
                <button @click="deleteFile(f)" title="Remove"
                        class="text-slate-400 hover:text-red-400">
                  <Trash2 class="w-3 h-3"/>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Google Drive placeholder (M26) -->
      <div class="bg-slate-800 border border-slate-700 rounded-xl p-5 mt-3 opacity-50">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 bg-slate-700 rounded-lg flex items-center justify-center shrink-0">
            <HardDrive class="w-5 h-5 text-slate-400"/>
          </div>
          <div>
            <p class="text-white font-medium text-sm">Google Drive</p>
            <p class="text-slate-400 text-xs">Coming soon</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Modal ── -->
    <Teleport to="body">
      <div v-if="showModal"
           class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
           @click.self="closeModal">
        <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl flex flex-col"
             style="max-height:80vh">

          <!-- Header -->
          <div class="flex items-start justify-between p-5 border-b border-slate-100 shrink-0">
            <div>
              <h2 class="text-slate-900 font-semibold text-base">Add content from GitHub</h2>
              <p class="text-slate-500 text-sm mt-0.5">Select the files you would like to add to this project</p>
            </div>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 mt-0.5 ml-4 shrink-0">
              <X class="w-5 h-5"/>
            </button>
          </div>

          <!-- Toolbar -->
          <div class="px-5 py-3 border-b border-slate-100 flex items-center gap-2 shrink-0 flex-wrap">
            <!-- Project -->
            <select v-model="selectedProjectId" @change="onProjectChange"
                    class="text-sm border border-slate-200 rounded-lg px-2 py-1.5 text-slate-700 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
              <option value="">Select project…</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>

            <!-- Repo -->
            <select v-if="selectedProjectId" v-model="selectedRepo" @change="onRepoChange"
                    class="text-sm border border-slate-200 rounded-lg px-2 py-1.5 text-slate-700 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 flex-1 min-w-0">
              <option value="">Select repo…</option>
              <option v-for="r in repos" :key="r.full_name" :value="r.full_name">
                {{ r.full_name }}
              </option>
            </select>

            <!-- Search -->
            <div v-if="selectedRepo" class="relative shrink-0">
              <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2 top-1/2 -translate-y-1/2"/>
              <input v-model="search" placeholder="Search…"
                     class="text-sm border border-slate-200 rounded-lg pl-7 pr-2 py-1.5 text-slate-700 w-28 focus:outline-none focus:ring-1 focus:ring-blue-500"/>
            </div>
          </div>

          <!-- Breadcrumb -->
          <div v-if="breadcrumbs.length"
               class="px-5 py-2 flex items-center gap-1 text-xs text-slate-400 border-b border-slate-100 shrink-0 flex-wrap">
            <button @click="loadContents('')" class="hover:text-slate-700 font-medium">root</button>
            <template v-for="(crumb, i) in breadcrumbs" :key="i">
              <ChevronRight class="w-3 h-3 shrink-0"/>
              <button @click="loadContents(crumb.path)" class="hover:text-slate-700">{{ crumb.name }}</button>
            </template>
          </div>

          <!-- File tree -->
          <div class="flex-1 overflow-y-auto">

            <!-- Empty state -->
            <div v-if="!selectedRepo"
                 class="flex flex-col items-center justify-center py-12 text-slate-400">
              <Github class="w-8 h-8 mb-2 opacity-30"/>
              <p class="text-sm">Select a project and repository to browse files</p>
            </div>

            <!-- Loading -->
            <div v-else-if="loadingContents"
                 class="flex items-center justify-center py-12 text-slate-400">
              <RefreshCw class="w-4 h-4 animate-spin mr-2"/> Loading…
            </div>

            <template v-else>
              <!-- Select all -->
              <label class="flex items-center gap-3 px-5 py-2.5 border-b border-slate-100 cursor-pointer hover:bg-slate-50">
                <input type="checkbox"
                       :checked="allSelected"
                       @change="toggleAll"
                       class="w-4 h-4 rounded accent-blue-600 shrink-0"/>
                <div class="flex items-center gap-2">
                  <Github class="w-4 h-4 text-slate-500"/>
                  <span class="text-slate-700 text-sm font-medium">{{ selectedRepo.split('/')[1] }}</span>
                </div>
              </label>

              <!-- Items -->
              <label v-for="item in filteredContents" :key="item.path"
                     class="flex items-center gap-3 px-5 py-2.5 border-b border-slate-50 last:border-0 cursor-pointer hover:bg-slate-50">

                <input type="checkbox"
                       :value="item.type === 'dir' ? item.path + '/' : item.path"
                       v-model="selectedPaths"
                       class="w-4 h-4 rounded accent-blue-600 shrink-0"/>

                <div class="flex items-center gap-2 flex-1 min-w-0">
                  <!-- Folder: click arrow to navigate, checkbox selects -->
                  <button v-if="item.type === 'dir'"
                          @click.prevent="loadContents(item.path)"
                          class="flex items-center gap-2 min-w-0 flex-1 text-left">
                    <ChevronRight class="w-3.5 h-3.5 text-slate-400 shrink-0"/>
                    <FolderOpen class="w-4 h-4 text-blue-500 shrink-0"/>
                    <span class="text-slate-700 text-sm truncate">{{ item.name }}</span>
                  </button>

                  <!-- File -->
                  <template v-else>
                    <FileText class="w-4 h-4 text-slate-400 shrink-0"/>
                    <span class="text-slate-700 text-sm truncate">{{ item.name }}</span>
                  </template>
                </div>

                <!-- Size % -->
                <span v-if="item.type === 'file' && item.size"
                      class="text-slate-400 text-xs shrink-0">
                  {{ sizePercent(item.size) }}%
                </span>
              </label>

              <div v-if="!filteredContents.length"
                   class="py-8 text-center text-slate-400 text-sm">
                No files found
              </div>
            </template>
          </div>

          <!-- Footer -->
          <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between shrink-0">
            <span class="text-slate-400 text-xs">
              <template v-if="importing">
                Importing {{ importProgress.done + importProgress.failed }}/{{ importProgress.total }}…
                <span v-if="importProgress.failed" class="text-orange-500 ml-1">
                  ({{ importProgress.failed }} skipped)
                </span>
              </template>
              <template v-else>
                {{ selectedPaths.length }} item{{ selectedPaths.length !== 1 ? 's' : '' }} selected
              </template>
            </span>
            <div class="flex items-center gap-2">
              <button @click="closeModal"
                      class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800 transition">
                Cancel
              </button>
              <button @click="importSelected"
                      :disabled="!selectedPaths.length || !selectedProjectId || importing"
                      class="px-4 py-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm rounded-lg transition flex items-center gap-2">
                <RefreshCw v-if="importing" class="w-3.5 h-3.5 animate-spin"/>
                {{ importing ? 'Importing…' : 'Add to project' }}
              </button>
            </div>
          </div>

        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  Github, HardDrive, CheckCircle, AlertCircle, Plus, X,
  RefreshCw, FolderOpen, FileText, Trash2, ChevronRight,
  User, Unlink, Search,
} from 'lucide-vue-next'

const props = defineProps({
  github:   Object,
  projects: Array,
})

// ── State ─────────────────────────────────────────────────────────
const showModal         = ref(false)
const selectedProjectId = ref('')
const selectedRepo      = ref('')
const repos             = ref([])
const contents          = ref([])
const breadcrumbs       = ref([])
const selectedPaths     = ref([])
const attachedFiles     = ref([])
const search            = ref('')
const loadingContents   = ref(false)
const importing         = ref(false)
const importProgress    = ref({ total: 0, done: 0, failed: 0 })

// ── Computed ──────────────────────────────────────────────────────
const filteredContents = computed(() => {
  if (!search.value) return contents.value
  return contents.value.filter(i =>
    i.name.toLowerCase().includes(search.value.toLowerCase())
  )
})

const totalSize = computed(() =>
  contents.value.reduce((s, i) => s + (i.size || 0), 0)
)

const allSelected = computed(() => {
  if (!contents.value.length) return false
  return contents.value.every(i => {
    const val = i.type === 'dir' ? i.path + '/' : i.path
    return selectedPaths.value.includes(val)
  })
})

const sizePercent = (size) => {
  if (!totalSize.value) return '<1'
  const p = Math.round(size / totalSize.value * 100)
  return p < 1 ? '<1' : p
}

// ── Modal ─────────────────────────────────────────────────────────
const openModal = () => {
  contents.value      = []
  breadcrumbs.value   = []
  selectedPaths.value = []
  search.value        = ''
  showModal.value     = true
  if (!repos.value.length && selectedProjectId.value) loadRepos()
}

const closeModal = () => {
  if (!importing.value) showModal.value = false
}

// ── Project / repo change ─────────────────────────────────────────
const onProjectChange = () => {
  selectedRepo.value  = ''
  contents.value      = []
  breadcrumbs.value   = []
  selectedPaths.value = []
  loadRepos()
  loadAttachedFiles()
}

const onRepoChange = () => {
  contents.value      = []
  breadcrumbs.value   = []
  selectedPaths.value = []
  if (selectedRepo.value) loadContents('')
}

// ── Load repos ────────────────────────────────────────────────────
const loadRepos = async () => {
  if (!props.github.connected) return
  try {
    const res  = await fetch(route('github.repos'))
    const json = await res.json()
    repos.value = json.data ?? []
  } catch {
    repos.value = []
  }
}

// ── Load directory contents ───────────────────────────────────────
const loadContents = async (path) => {
  if (!selectedRepo.value) return
  loadingContents.value = true
  selectedPaths.value   = []
  search.value          = ''

  breadcrumbs.value = path
    ? path.split('/').map((name, i, arr) => ({
        name,
        path: arr.slice(0, i + 1).join('/'),
      }))
    : []

  try {
    const url  = route('github.contents')
               + `?repo=${encodeURIComponent(selectedRepo.value)}`
               + `&path=${encodeURIComponent(path)}`
    const res  = await fetch(url)
    const json = await res.json()
    contents.value = json.data ?? []
  } catch {
    contents.value = []
  }

  loadingContents.value = false
}

// ── Toggle all ────────────────────────────────────────────────────
const toggleAll = () => {
  if (allSelected.value) {
    selectedPaths.value = []
  } else {
    selectedPaths.value = contents.value.map(i =>
      i.type === 'dir' ? i.path + '/' : i.path
    )
  }
}

// ── Import ────────────────────────────────────────────────────────
const importSelected = async () => {
  if (!selectedPaths.value.length || !selectedProjectId.value) return
  importing.value     = true
  importProgress.value = { total: 0, done: 0, failed: 0 }

  // Collect all file paths (expand folders recursively)
  const allFiles = []
  for (const selectedPath of selectedPaths.value) {
    if (selectedPath.endsWith('/')) {
      await collectFiles(selectedPath.slice(0, -1), allFiles)
    } else {
      const item = contents.value.find(i => i.path === selectedPath)
      if (item) allFiles.push({ path: item.path, name: item.name })
    }
  }

  importProgress.value.total = allFiles.length

  for (const file of allFiles) {
    const ok = await importFile(file.path, file.name)
    if (ok) importProgress.value.done++
    else    importProgress.value.failed++
  }

  importing.value     = false
  selectedPaths.value = []
  showModal.value     = false
  loadAttachedFiles()
}

const collectFiles = async (folderPath, result) => {
  try {
    const url  = route('github.contents')
               + `?repo=${encodeURIComponent(selectedRepo.value)}`
               + `&path=${encodeURIComponent(folderPath)}`
    const res  = await fetch(url)
    const json = await res.json()
    const items = json.data ?? []

    for (const item of items) {
      if (item.type === 'dir') {
        await collectFiles(item.path, result)
      } else {
        result.push({ path: item.path, name: item.name })
      }
    }
  } catch {
    // skip unreachable folder
  }
}

const importFile = async (path, name) => {
  try {
    const res = await fetch(route('github.import'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({
        project_id: selectedProjectId.value,
        repo:       selectedRepo.value,
        path,
        name,
      }),
    })
    return res.ok
  } catch {
    return false
  }
}

// ── Attached files ────────────────────────────────────────────────
const loadAttachedFiles = async () => {
  if (!selectedProjectId.value) return
  try {
    const res  = await fetch(`/api/v1/projects/${selectedProjectId.value}/integration-files`)
    const json = await res.json()
    attachedFiles.value = json.data ?? []
  } catch {
    attachedFiles.value = []
  }
}

const syncFile = async (f) => {
  await fetch(route('github.file.sync', f.id), {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
  })
  loadAttachedFiles()
}

const deleteFile = async (f) => {
  if (!confirm(`Remove "${f.name}"?`)) return
  await fetch(route('github.file.delete', f.id), {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
  })
  loadAttachedFiles()
}

const disconnect = () => router.post(route('github.disconnect'))

// ── Watch ─────────────────────────────────────────────────────────
watch(selectedRepo, val => { if (val) loadContents('') })

// ── Helpers ───────────────────────────────────────────────────────
const formatBytes = (b) => {
  if (!b) return ''
  if (b < 1024) return b + ' B'
  if (b < 1048576) return (b / 1024).toFixed(1) + ' KB'
  return (b / 1048576).toFixed(1) + ' MB'
}
</script>