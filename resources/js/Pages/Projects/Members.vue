<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Users, UserPlus, Lock, Unlock, Trash2, Key, CheckCircle, ChevronLeft, Shield } from 'lucide-vue-next'

// ─── Props ────────────────────────────────────────────────────────────
const props = defineProps({
  project:      { type: Object, required: true },
  members:      { type: Array,  default: () => [] },
  addableUsers: { type: Array,  default: () => [] },
  canManage:    { type: Boolean, default: false },
})

// ─── State ────────────────────────────────────────────────────────────
const showAddModal = ref(false)

// ─── Add member form ──────────────────────────────────────────────────
const addForm = useForm({
  user_id: '',
  role:    'viewer',
})

function submitAdd() {
  addForm.post(route('project.members.add', props.project.id), {
    preserveScroll: true,
    onSuccess: () => {
      addForm.reset()
      showAddModal.value = false
    },
  })
}

// ─── Change role ──────────────────────────────────────────────────────
function changeRole(member, role) {
  router.put(
    route('project.members.role', [props.project.id, member.id]),
    { role },
    { preserveScroll: true }
  )
}

// ─── Remove ───────────────────────────────────────────────────────────
function removeMember(member) {
  if (!confirm(`Remove ${member.name} from this project?`)) return
  router.delete(
    route('project.members.remove', [props.project.id, member.id]),
    { preserveScroll: true }
  )
}

// ─── Toggle restriction ───────────────────────────────────────────────
function toggleRestricted() {
  const msg = props.project.is_restricted
    ? 'Open this project to all workspace members?'
    : 'Restrict this project? Only explicitly added members will have access.'
  if (!confirm(msg)) return
  router.put(route('project.restricted.toggle', props.project.id), {}, { preserveScroll: true })
}

// ─── Role display ─────────────────────────────────────────────────────
const roleColors = {
  owner:  'bg-amber-900/40 text-amber-300',
  editor: 'bg-blue-900/40 text-blue-300',
  viewer: 'bg-slate-700 text-slate-300',
}
</script>

<template>
  <AppLayout :title="`${project.name} – Members`">
    <div class="max-w-4xl mx-auto px-4 py-8 space-y-6">

      <!-- ── Back + Header ─────────────────────────────────────── -->
      <div>
        <a
          :href="`/projects/${project.id}`"
          class="inline-flex items-center gap-1 text-slate-400 hover:text-white text-sm mb-4 transition-colors"
        >
          <ChevronLeft class="w-4 h-4" />
          Back to Project
        </a>

        <div class="flex items-start justify-between">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-slate-700 rounded-lg">
              <Users class="w-5 h-5 text-slate-300" />
            </div>
            <div>
              <h1 class="text-xl font-bold text-white">Project Access</h1>
              <p class="text-slate-400 text-sm">{{ project.name }}</p>
            </div>
          </div>

          <!-- Restrict toggle -->
          <button
            v-if="canManage"
            @click="toggleRestricted"
            class="flex items-center gap-2 px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
            :class="project.is_restricted
              ? 'border-red-700/60 bg-red-900/20 text-red-400 hover:bg-red-900/30'
              : 'border-slate-600 bg-slate-800 text-slate-300 hover:border-slate-500'"
          >
            <Lock v-if="project.is_restricted" class="w-4 h-4" />
            <Unlock v-else class="w-4 h-4" />
            {{ project.is_restricted ? 'Restricted' : 'Open to all' }}
          </button>
        </div>
      </div>

      <!-- ── Access Mode Info ──────────────────────────────────── -->
      <div
        class="flex items-start gap-3 p-4 rounded-xl border text-sm"
        :class="project.is_restricted
          ? 'border-red-700/40 bg-red-900/10 text-red-300'
          : 'border-green-700/40 bg-green-900/10 text-green-300'"
      >
        <Lock v-if="project.is_restricted" class="w-4 h-4 flex-shrink-0 mt-0.5 text-red-400" />
        <Unlock v-else class="w-4 h-4 flex-shrink-0 mt-0.5 text-green-400" />
        <div>
          <strong class="font-medium">
            {{ project.is_restricted ? 'Restricted Access' : 'Open Access' }}
          </strong>
          <p class="mt-0.5 opacity-80">
            {{ project.is_restricted
              ? 'Only members listed below can access this project. Admins always have access.'
              : 'All workspace members can access this project. Enable restriction to limit access.' }}
          </p>
        </div>
      </div>

      <!-- ── Members list ──────────────────────────────────────── -->
      <div class="bg-slate-800/40 rounded-xl border border-slate-700/50 overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-700/50">
          <div class="flex items-center gap-2">
            <Users class="w-4 h-4 text-slate-400" />
            <span class="text-slate-300 font-medium text-sm">
              {{ members.length }} explicit member{{ members.length !== 1 ? 's' : '' }}
            </span>
          </div>

          <button
            v-if="canManage && addableUsers.length > 0"
            @click="showAddModal = true"
            class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-700 hover:bg-slate-600
                   text-slate-300 text-xs rounded-lg transition-colors"
          >
            <UserPlus class="w-3.5 h-3.5" />
            Add Member
          </button>
        </div>

        <!-- Empty -->
        <div v-if="members.length === 0" class="text-center py-10">
          <Users class="w-8 h-8 text-slate-600 mx-auto mb-2" />
          <p class="text-slate-400 text-sm">No explicit members yet.</p>
          <p class="text-slate-500 text-xs mt-1">
            {{ project.is_restricted
              ? 'Add members below to grant access.'
              : 'Restriction is off — all workspace members have access.' }}
          </p>
        </div>

        <!-- List -->
        <div v-else class="divide-y divide-slate-700/40">
          <div
            v-for="member in members"
            :key="member.id"
            class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-700/20 transition-colors"
          >
            <!-- Avatar -->
            <div class="w-8 h-8 rounded-full bg-slate-600 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
              {{ member.name.charAt(0).toUpperCase() }}
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-white text-sm font-medium">{{ member.name }}</span>
                <Shield v-if="member.tenant_role === 'admin'" class="w-3 h-3 text-indigo-400" title="Workspace Admin" />
              </div>
              <span class="text-slate-400 text-xs">{{ member.email }}</span>
            </div>

            <!-- Project role selector -->
            <div v-if="canManage" class="flex gap-1">
              <button
                v-for="r in ['owner', 'editor', 'viewer']"
                :key="r"
                @click="changeRole(member, r)"
                class="px-2 py-1 text-xs rounded-md transition-colors capitalize"
                :class="member.role === r
                  ? roleColors[r]
                  : 'bg-transparent text-slate-500 hover:text-slate-300'"
              >
                {{ r }}
              </button>
            </div>

            <!-- Role badge (view-only) -->
            <span
              v-else
              class="px-2 py-0.5 text-xs rounded-full capitalize"
              :class="roleColors[member.role]"
            >
              {{ member.role }}
            </span>

            <!-- Remove -->
            <button
              v-if="canManage"
              @click="removeMember(member)"
              class="p-1.5 text-slate-500 hover:text-red-400 hover:bg-red-900/20 rounded-lg transition-colors"
            >
              <Trash2 class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>

    </div>

    <!-- ── Add Member Modal ─────────────────────────────────────── -->
    <Teleport to="body">
      <div
        v-if="showAddModal"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="showAddModal = false"
      >
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-sm shadow-2xl">
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700">
            <h2 class="text-white font-semibold flex items-center gap-2">
              <UserPlus class="w-4 h-4 text-indigo-400" />
              Add to Project
            </h2>
            <button @click="showAddModal = false" class="text-slate-400 hover:text-white">✕</button>
          </div>

          <div class="px-6 py-5 space-y-4">
            <!-- User select -->
            <div>
              <label class="text-slate-300 text-sm font-medium block mb-1.5">Team Member</label>
              <select
                v-model="addForm.user_id"
                class="w-full bg-slate-800 border border-slate-600 rounded-xl px-3 py-2.5
                       text-white text-sm focus:outline-none focus:border-indigo-500"
              >
                <option value="" disabled>Select a member…</option>
                <option v-for="u in addableUsers" :key="u.id" :value="u.id">
                  {{ u.name }} ({{ u.email }})
                </option>
              </select>
            </div>

            <!-- Role -->
            <div>
              <label class="text-slate-300 text-sm font-medium block mb-1.5">Project Role</label>
              <div class="grid grid-cols-3 gap-2">
                <button
                  v-for="r in [
                    { value: 'owner',  desc: 'Full control' },
                    { value: 'editor', desc: 'Can chat' },
                    { value: 'viewer', desc: 'Read only' },
                  ]"
                  :key="r.value"
                  @click="addForm.role = r.value"
                  class="p-2 rounded-xl border text-center transition-colors"
                  :class="addForm.role === r.value
                    ? 'border-indigo-500 bg-indigo-600/20'
                    : 'border-slate-600 bg-slate-800 hover:border-slate-500'"
                >
                  <div class="text-xs font-medium capitalize"
                    :class="addForm.role === r.value ? 'text-white' : 'text-slate-300'">
                    {{ r.value }}
                  </div>
                  <div class="text-xs text-slate-500 mt-0.5">{{ r.desc }}</div>
                </button>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-700">
            <button @click="showAddModal = false" class="px-4 py-2 text-slate-400 hover:text-white text-sm">
              Cancel
            </button>
            <button
              @click="submitAdd"
              :disabled="addForm.processing || !addForm.user_id"
              class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50
                     text-white text-sm rounded-lg transition-colors font-medium"
            >
              {{ addForm.processing ? 'Adding…' : 'Add Member' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </AppLayout>
</template>
