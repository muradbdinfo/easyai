<script setup>
import { ref, computed } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import { watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  Users, UserPlus, Mail, Shield, Crown, Trash2,
  Copy, CheckCircle, Clock, AlertCircle, X, RefreshCw,
  Link, UserCheck, UserX, Key
} from 'lucide-vue-next'

const props = defineProps({
  members:     { type: Array,   default: () => [] },
  invitations: { type: Array,   default: () => [] },
  canManage:   { type: Boolean, default: false },
  currentUser: { type: Object,  required: true },
  seats:       { type: Object,  default: null },
})

const activeTab    = ref('members')
const showInvite   = ref(false)
const copiedId     = ref(null)
const flashUrl     = ref(null)
const editRoleUser = ref(null)
const page         = usePage()

watch(() => page.props.flash, (flash) => {
  if (flash?.invite_url) { flashUrl.value = flash.invite_url; showInvite.value = false }
}, { deep: true })

const inviteForm = useForm({ email: '', role: 'member' })
function submitInvite() {
  inviteForm.post(route('team.invite'), { preserveScroll: true, onSuccess: () => inviteForm.reset() })
}

async function copyUrl(url, id = 'flash') {
  try { await navigator.clipboard.writeText(url) } catch { prompt('Copy this link:', url) }
  copiedId.value = id
  setTimeout(() => { copiedId.value = null }, 2000)
}

const roleForm = useForm({ role: '' })
function openEditRole(member) { editRoleUser.value = member; roleForm.role = member.role }
function saveRole() {
  roleForm.put(route('team.member.role', editRoleUser.value.id), { preserveScroll: true, onSuccess: () => { editRoleUser.value = null } })
}

function toggleStatus(member) {
  if (!confirm(`${member.is_active ? 'Deactivate' : 'Activate'} ${member.name}?`)) return
  router.put(route('team.member.status', member.id), {}, { preserveScroll: true })
}
function removeMember(member) {
  if (!confirm(`Remove ${member.name} from the workspace? They will lose all access immediately.`)) return
  router.delete(route('team.member.remove', member.id), { preserveScroll: true })
}
function resendInvite(inv) {
  router.post(route('team.invitation.resend', inv.id), {}, {
    preserveScroll: true,
    onSuccess: () => { if (page.props.flash?.invite_url) flashUrl.value = page.props.flash.invite_url },
  })
}
function cancelInvite(inv) {
  if (!confirm(`Cancel invitation for ${inv.email}?`)) return
  router.delete(route('team.invitation.cancel', inv.id), { preserveScroll: true })
}

const roleBadge = { admin: 'bg-indigo-900/50 text-indigo-300 border border-indigo-700', member: 'bg-slate-700 text-slate-300 border border-slate-600' }
const roleLabel = { admin: 'Admin', member: 'Member' }

const seatPercent  = computed(() => !props.seats?.is_seat_plan || !props.seats.purchased ? 0 : Math.min(100, Math.round(props.seats.used / props.seats.purchased * 100)))
const seatBarColor = computed(() => {
  if (!props.seats?.is_seat_plan) return 'bg-indigo-500'
  if (props.seats.available <= 0) return 'bg-red-500'
  return seatPercent.value > 80 ? 'bg-amber-500' : 'bg-indigo-500'
})
const seatLimitReached = computed(() => props.seats?.is_seat_plan && props.seats?.available <= 0)
</script>

<template>
  <AppLayout title="Team">
    <div class="max-w-5xl mx-auto px-4 py-8 space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-indigo-600/20 rounded-lg">
            <Users class="w-5 h-5 text-indigo-400" />
          </div>
          <div>
            <h1 class="text-xl font-bold text-white">Team Members</h1>
            <p class="text-sm text-slate-400">
              {{ members.length }} member{{ members.length !== 1 ? 's' : '' }}
              · {{ invitations.length }} pending invite{{ invitations.length !== 1 ? 's' : '' }}
            </p>
            <!-- Seat bar (seat plans only) -->
            <div v-if="seats?.is_seat_plan" class="mt-2 flex items-center gap-3">
              <div class="w-32 h-1.5 bg-slate-700 rounded-full overflow-hidden">
                <div class="h-1.5 rounded-full transition-all" :class="seatBarColor" :style="{ width: seatPercent + '%' }" />
              </div>
              <span class="text-xs text-slate-400">{{ seats.used }} / {{ seats.purchased }} seats</span>
              <span v-if="seats.available <= 0" class="text-xs text-red-400 font-medium">· Limit reached</span>
              <span v-else class="text-xs text-slate-500">· {{ seats.available }} available</span>
            </div>
          </div>
        </div>

        <button
          v-if="canManage"
          @click="showInvite = true; flashUrl = null"
          :disabled="seatLimitReached"
          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg transition-colors font-medium"
          :class="{ 'opacity-50 cursor-not-allowed': seatLimitReached }"
        >
          <UserPlus class="w-4 h-4" />
          Invite Member
        </button>
      </div>

      <!-- Flash invite URL -->
      <div v-if="flashUrl" class="bg-green-900/30 border border-green-700/50 rounded-xl p-4 space-y-3">
        <div class="flex items-center gap-2 text-green-400 font-medium text-sm">
          <CheckCircle class="w-4 h-4" /> Invitation created! Share this link:
        </div>
        <div class="flex items-center gap-2">
          <div class="flex-1 bg-slate-800 rounded-lg px-3 py-2 text-sm text-slate-300 font-mono truncate border border-slate-600">{{ flashUrl }}</div>
          <button @click="copyUrl(flashUrl)" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm transition-colors" :class="copiedId === 'flash' ? 'bg-green-700 text-white' : 'bg-slate-700 hover:bg-slate-600 text-slate-300'">
            <CheckCircle v-if="copiedId === 'flash'" class="w-4 h-4" /><Copy v-else class="w-4 h-4" />
            {{ copiedId === 'flash' ? 'Copied!' : 'Copy' }}
          </button>
          <button @click="flashUrl = null" class="p-2 text-slate-500 hover:text-slate-300"><X class="w-4 h-4" /></button>
        </div>
        <p class="text-xs text-slate-500">Link expires in 7 days. Send it via email, Slack, or any messaging app.</p>
      </div>

      <!-- Tabs -->
      <div class="flex gap-1 bg-slate-800/50 rounded-xl p-1 w-fit">
        <button
          v-for="tab in [{ key: 'members', label: 'Members', count: members.length }, { key: 'invitations', label: 'Pending', count: invitations.length }]"
          :key="tab.key" @click="activeTab = tab.key"
          class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          :class="activeTab === tab.key ? 'bg-slate-700 text-white' : 'text-slate-400 hover:text-slate-300'"
        >
          {{ tab.label }}
          <span v-if="tab.count > 0" class="px-1.5 py-0.5 text-xs rounded-full" :class="activeTab === tab.key ? 'bg-slate-600 text-white' : 'bg-slate-700 text-slate-400'">{{ tab.count }}</span>
        </button>
      </div>

      <!-- Members Tab -->
      <div v-if="activeTab === 'members'" class="bg-slate-800/40 rounded-xl border border-slate-700/50 overflow-hidden">
        <div v-if="members.length === 0" class="text-center py-12">
          <Users class="w-10 h-10 text-slate-600 mx-auto mb-3" />
          <p class="text-slate-400 text-sm">No members yet.</p>
        </div>
        <div v-else class="divide-y divide-slate-700/50">
          <div v-for="member in members" :key="member.id" class="flex items-center gap-4 px-5 py-4 hover:bg-slate-700/20 transition-colors">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0" :class="member.role === 'admin' ? 'bg-indigo-600 text-white' : 'bg-slate-600 text-slate-200'">
              {{ member.name.charAt(0).toUpperCase() }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-white font-medium text-sm truncate">{{ member.name }}</span>
                <span v-if="member.is_self" class="text-xs text-slate-500">(you)</span>
                <Crown v-if="member.role === 'admin'" class="w-3.5 h-3.5 text-amber-400" title="Admin" />
              </div>
              <div class="text-slate-400 text-xs truncate">{{ member.email }}</div>
            </div>
            <span class="px-2 py-0.5 text-xs rounded-full font-medium flex-shrink-0" :class="roleBadge[member.role] ?? 'bg-slate-700 text-slate-300'">{{ roleLabel[member.role] ?? member.role }}</span>
            <span class="flex items-center gap-1 px-2 py-0.5 text-xs rounded-full flex-shrink-0" :class="member.is_active ? 'bg-green-900/40 text-green-400' : 'bg-red-900/40 text-red-400'">
              <span class="w-1.5 h-1.5 rounded-full" :class="member.is_active ? 'bg-green-400' : 'bg-red-400'" />
              {{ member.is_active ? 'Active' : 'Inactive' }}
            </span>
            <span class="text-slate-500 text-xs flex-shrink-0 hidden md:block">{{ member.joined_at }}</span>
            <div v-if="canManage && !member.is_self" class="flex items-center gap-1 flex-shrink-0">
              <button @click="openEditRole(member)" class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-600 rounded-lg transition-colors" title="Change role"><Key class="w-3.5 h-3.5" /></button>
              <button @click="toggleStatus(member)" class="p-1.5 rounded-lg transition-colors" :class="member.is_active ? 'text-slate-400 hover:text-amber-400 hover:bg-amber-900/30' : 'text-slate-400 hover:text-green-400 hover:bg-green-900/30'" :title="member.is_active ? 'Deactivate' : 'Activate'">
                <UserX v-if="member.is_active" class="w-3.5 h-3.5" /><UserCheck v-else class="w-3.5 h-3.5" />
              </button>
              <button @click="removeMember(member)" class="p-1.5 text-slate-400 hover:text-red-400 hover:bg-red-900/30 rounded-lg transition-colors" title="Remove from workspace"><Trash2 class="w-3.5 h-3.5" /></button>
            </div>
          </div>
        </div>
      </div>

      <!-- Invitations Tab -->
      <div v-if="activeTab === 'invitations'" class="bg-slate-800/40 rounded-xl border border-slate-700/50 overflow-hidden">
        <div v-if="invitations.length === 0" class="text-center py-12">
          <Mail class="w-10 h-10 text-slate-600 mx-auto mb-3" />
          <p class="text-slate-400 text-sm">No pending invitations.</p>
          <p class="text-slate-500 text-xs mt-1">Invite a new member to get started.</p>
        </div>
        <div v-else class="divide-y divide-slate-700/50">
          <div v-for="inv in invitations" :key="inv.id" class="flex items-center gap-4 px-5 py-4 hover:bg-slate-700/20 transition-colors">
            <div class="w-9 h-9 rounded-full bg-yellow-900/30 flex items-center justify-center flex-shrink-0"><Clock class="w-4 h-4 text-yellow-400" /></div>
            <div class="flex-1 min-w-0">
              <div class="text-white text-sm font-medium truncate">{{ inv.email }}</div>
              <div class="text-slate-400 text-xs">Invited {{ inv.created_at }} by {{ inv.inviter }} · Expires {{ inv.expires_at }}</div>
            </div>
            <span class="px-2 py-0.5 text-xs rounded-full font-medium flex-shrink-0" :class="roleBadge[inv.role]">{{ roleLabel[inv.role] }}</span>
            <span class="flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-yellow-900/40 text-yellow-400 flex-shrink-0"><Clock class="w-3 h-3" /> Pending</span>
            <div v-if="canManage" class="flex items-center gap-1 flex-shrink-0">
              <button @click="copyUrl(inv.invite_url, inv.id)" class="flex items-center gap-1 px-2 py-1.5 rounded-lg text-xs transition-colors" :class="copiedId === inv.id ? 'bg-green-700 text-white' : 'bg-slate-700 hover:bg-slate-600 text-slate-300'" title="Copy invite link">
                <CheckCircle v-if="copiedId === inv.id" class="w-3.5 h-3.5" /><Link v-else class="w-3.5 h-3.5" />
                {{ copiedId === inv.id ? 'Copied' : 'Copy' }}
              </button>
              <button @click="resendInvite(inv)" class="p-1.5 text-slate-400 hover:text-blue-400 hover:bg-blue-900/30 rounded-lg transition-colors" title="Refresh link"><RefreshCw class="w-3.5 h-3.5" /></button>
              <button @click="cancelInvite(inv)" class="p-1.5 text-slate-400 hover:text-red-400 hover:bg-red-900/30 rounded-lg transition-colors" title="Cancel invitation"><X class="w-3.5 h-3.5" /></button>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Invite Modal -->
    <Teleport to="body">
      <div v-if="showInvite" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="showInvite = false">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md shadow-2xl">
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700">
            <div class="flex items-center gap-2"><UserPlus class="w-5 h-5 text-indigo-400" /><h2 class="text-white font-semibold">Invite Team Member</h2></div>
            <button @click="showInvite = false" class="text-slate-400 hover:text-white"><X class="w-5 h-5" /></button>
          </div>
          <div class="px-6 py-5 space-y-4">
            <!-- Seat warning -->
            <div v-if="seats?.is_seat_plan" class="flex items-center gap-2 p-3 rounded-lg text-xs border" :class="seats.available <= 0 ? 'bg-red-900/30 text-red-400 border-red-800' : 'bg-slate-800 text-slate-400 border-slate-700'">
              <AlertCircle class="w-3.5 h-3.5 flex-shrink-0" />
              <span v-if="seats.available <= 0">No seats available. Upgrade your plan to invite more members.</span>
              <span v-else>{{ seats.available }} seat{{ seats.available !== 1 ? 's' : '' }} available ({{ seats.used }}/{{ seats.purchased }} used)</span>
            </div>
            <div>
              <label class="text-slate-300 text-sm font-medium block mb-1.5">Email Address</label>
              <div class="relative">
                <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <input v-model="inviteForm.email" type="email" placeholder="colleague@company.com"
                  class="w-full bg-slate-800 border border-slate-600 rounded-lg pl-10 pr-4 py-2.5 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                  @keyup.enter="submitInvite" />
              </div>
              <p v-if="inviteForm.errors.email" class="text-red-400 text-xs mt-1">{{ inviteForm.errors.email }}</p>
            </div>
            <div>
              <label class="text-slate-300 text-sm font-medium block mb-1.5">Role</label>
              <div class="grid grid-cols-2 gap-2">
                <button v-for="opt in [{ value: 'member', label: 'Member', desc: 'Can use chats & projects', icon: Users }, { value: 'admin', label: 'Admin', desc: 'Can manage team & settings', icon: Shield }]"
                  :key="opt.value" @click="inviteForm.role = opt.value"
                  class="flex flex-col items-start p-3 rounded-xl border text-left transition-colors"
                  :class="inviteForm.role === opt.value ? 'border-indigo-500 bg-indigo-600/20' : 'border-slate-600 bg-slate-800 hover:border-slate-500'">
                  <div class="flex items-center gap-2 mb-1">
                    <component :is="opt.icon" class="w-3.5 h-3.5" :class="inviteForm.role === opt.value ? 'text-indigo-400' : 'text-slate-400'" />
                    <span class="text-sm font-medium" :class="inviteForm.role === opt.value ? 'text-white' : 'text-slate-300'">{{ opt.label }}</span>
                  </div>
                  <span class="text-xs text-slate-500">{{ opt.desc }}</span>
                </button>
              </div>
            </div>
            <p class="text-xs text-slate-500 bg-slate-800/50 rounded-lg p-3 flex gap-2">
              <Link class="w-3.5 h-3.5 flex-shrink-0 mt-0.5 text-slate-400" />
              An invite link will be generated. Share it with your colleague via email or messaging app. Link expires in 7 days.
            </p>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-700">
            <button @click="showInvite = false" class="px-4 py-2 text-slate-400 hover:text-white text-sm transition-colors">Cancel</button>
            <button @click="submitInvite" :disabled="inviteForm.processing || !inviteForm.email || seatLimitReached"
              class="flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm rounded-lg transition-colors font-medium">
              <UserPlus class="w-4 h-4" />
              {{ inviteForm.processing ? 'Creating…' : 'Create Invite Link' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Change Role Modal -->
    <Teleport to="body">
      <div v-if="editRoleUser" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="editRoleUser = null">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-sm shadow-2xl">
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700">
            <div class="flex items-center gap-2"><Key class="w-5 h-5 text-indigo-400" /><h2 class="text-white font-semibold">Change Role</h2></div>
            <button @click="editRoleUser = null" class="text-slate-400 hover:text-white"><X class="w-5 h-5" /></button>
          </div>
          <div class="px-6 py-5 space-y-4">
            <p class="text-slate-400 text-sm">Change role for <span class="text-white font-medium">{{ editRoleUser.name }}</span></p>
            <div class="grid grid-cols-2 gap-2">
              <button v-for="r in ['admin', 'member']" :key="r" @click="roleForm.role = r"
                class="p-3 rounded-xl border text-sm font-medium transition-colors capitalize"
                :class="roleForm.role === r ? 'border-indigo-500 bg-indigo-600/20 text-white' : 'border-slate-600 bg-slate-800 text-slate-300 hover:border-slate-500'">
                {{ r }}
              </button>
            </div>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-700">
            <button @click="editRoleUser = null" class="px-4 py-2 text-slate-400 hover:text-white text-sm">Cancel</button>
            <button @click="saveRole" :disabled="roleForm.processing" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-sm rounded-lg transition-colors font-medium">
              {{ roleForm.processing ? 'Saving…' : 'Save Role' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </AppLayout>
</template>