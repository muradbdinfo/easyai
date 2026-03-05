<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { CheckCircle, AlertCircle, UserPlus, Shield, Users, Eye, EyeOff, X } from 'lucide-vue-next'

// ─── Props ────────────────────────────────────────────────────────────
const props = defineProps({
  invitation:     { type: Object,  default: null },
  tenant:         { type: Object,  default: null },
  inviter:        { type: Object,  default: null },
  existingUser:   { type: Boolean, default: false },
  isLoggedIn:     { type: Boolean, default: false },
  loggedInEmail:  { type: String,  default: null },
  error:          { type: String,  default: null },
})

// ─── State ────────────────────────────────────────────────────────────
const showPassword = ref(false)
const page         = usePage()

// ─── Accept form (for new users) ──────────────────────────────────────
const acceptForm = useForm({
  name:                  '',
  password:              '',
  password_confirmation: '',
})

function submitAccept() {
  acceptForm.post(route('invitation.accept', props.invitation?.token), {
    preserveScroll: true,
  })
}

function submitDecline() {
  acceptForm.post(route('invitation.decline', props.invitation?.token))
}

// For logged-in user accepting directly
function loggedInAccept() {
  acceptForm.post(route('invitation.accept', props.invitation?.token), {
    preserveScroll: true,
  })
}

const roleColor = {
  admin:  'text-indigo-400',
  member: 'text-slate-300',
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 flex items-center justify-center p-4">
    <div class="w-full max-w-md">

      <!-- ── Logo ─────────────────────────────────────────────── -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 bg-indigo-600 rounded-xl mb-3">
          <span class="text-white font-bold text-xl">E</span>
        </div>
        <div class="text-white font-semibold text-lg">EasyAI</div>
      </div>

      <!-- ── Error State ──────────────────────────────────────── -->
      <div v-if="error" class="bg-slate-900 border border-red-700/50 rounded-2xl p-8 text-center">
        <AlertCircle class="w-12 h-12 text-red-400 mx-auto mb-4" />
        <h1 class="text-white font-bold text-xl mb-2">Invitation Issue</h1>
        <p class="text-slate-400 text-sm">{{ error }}</p>
        <a
          href="/login"
          class="inline-flex items-center gap-2 mt-6 px-5 py-2 bg-slate-700 hover:bg-slate-600
                 text-white text-sm rounded-lg transition-colors"
        >
          Go to Login
        </a>
      </div>

      <!-- ── Valid Invitation ──────────────────────────────────── -->
      <div v-else-if="invitation" class="bg-slate-900 border border-slate-700 rounded-2xl overflow-hidden shadow-2xl">

        <!-- Invitation Banner -->
        <div class="bg-gradient-to-r from-indigo-600/20 to-purple-600/20 border-b border-slate-700 px-6 py-5 text-center">
          <div
            class="inline-flex items-center justify-center w-10 h-10 rounded-full mb-3"
            :class="invitation.role === 'admin' ? 'bg-indigo-600/40' : 'bg-slate-700'"
          >
            <Shield v-if="invitation.role === 'admin'" class="w-5 h-5 text-indigo-400" />
            <Users v-else class="w-5 h-5 text-slate-300" />
          </div>
          <h1 class="text-white font-bold text-xl mb-1">
            You're invited!
          </h1>
          <p class="text-slate-400 text-sm">
            <span class="text-white font-medium">{{ inviter?.name }}</span>
            invited you to join
            <span class="text-indigo-400 font-medium">{{ tenant?.name }}</span>
            as a
            <span class="font-medium" :class="roleColor[invitation.role] ?? 'text-white'">
              {{ invitation.role }}
            </span>.
          </p>
        </div>

        <div class="px-6 py-6 space-y-5">

          <!-- ── Case: Already logged in with correct email ────── -->
          <template v-if="isLoggedIn && loggedInEmail === invitation.email">
            <div class="text-center space-y-4">
              <div class="flex items-center justify-center gap-2 text-green-400 text-sm">
                <CheckCircle class="w-4 h-4" />
                Logged in as <strong>{{ loggedInEmail }}</strong>
              </div>
              <p class="text-slate-400 text-sm">
                Click below to join <strong class="text-white">{{ tenant?.name }}</strong>.
              </p>
              <button
                @click="loggedInAccept"
                :disabled="acceptForm.processing"
                class="w-full flex items-center justify-center gap-2 py-3 bg-indigo-600
                       hover:bg-indigo-500 disabled:opacity-60 text-white rounded-xl
                       font-medium transition-colors"
              >
                <CheckCircle class="w-4 h-4" />
                {{ acceptForm.processing ? 'Joining…' : 'Accept & Join Workspace' }}
              </button>
            </div>
          </template>

          <!-- ── Case: Logged in but wrong email ───────────────── -->
          <template v-else-if="isLoggedIn && loggedInEmail !== invitation.email">
            <div class="bg-amber-900/20 border border-amber-700/50 rounded-xl p-4 text-sm">
              <div class="flex items-start gap-2 text-amber-400">
                <AlertCircle class="w-4 h-4 flex-shrink-0 mt-0.5" />
                <div>
                  You're logged in as <strong>{{ loggedInEmail }}</strong>,
                  but this invitation was sent to <strong>{{ invitation.email }}</strong>.
                  Please log out and log in with the correct account.
                </div>
              </div>
            </div>
            <a
              href="/logout"
              class="block text-center w-full py-2.5 border border-slate-600 hover:border-slate-500
                     text-slate-300 text-sm rounded-xl transition-colors"
            >
              Log Out & Switch Account
            </a>
          </template>

          <!-- ── Case: Existing user, not logged in ────────────── -->
          <template v-else-if="existingUser">
            <div class="bg-blue-900/20 border border-blue-700/50 rounded-xl p-4 text-sm text-blue-300">
              <div class="flex items-start gap-2">
                <CheckCircle class="w-4 h-4 flex-shrink-0 mt-0.5" />
                An account exists for <strong>{{ invitation.email }}</strong>.
                Log in to accept this invitation.
              </div>
            </div>
            <a
              :href="`/login`"
              class="block text-center w-full py-3 bg-indigo-600 hover:bg-indigo-500
                     text-white font-medium rounded-xl transition-colors"
            >
              Log In to Accept
            </a>
          </template>

          <!-- ── Case: New user — register ─────────────────────── -->
          <template v-else>
            <p class="text-slate-400 text-sm text-center">
              Create your account for <strong class="text-white">{{ invitation.email }}</strong>
            </p>

            <!-- Name -->
            <div>
              <label class="text-slate-300 text-sm font-medium block mb-1.5">Full Name</label>
              <input
                v-model="acceptForm.name"
                type="text"
                placeholder="Your name"
                class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-2.5
                       text-white placeholder-slate-500 text-sm focus:outline-none
                       focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              />
              <p v-if="acceptForm.errors.name" class="text-red-400 text-xs mt-1">
                {{ acceptForm.errors.name }}
              </p>
            </div>

            <!-- Password -->
            <div>
              <label class="text-slate-300 text-sm font-medium block mb-1.5">Password</label>
              <div class="relative">
                <input
                  v-model="acceptForm.password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="At least 8 characters"
                  class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-2.5
                         text-white placeholder-slate-500 text-sm focus:outline-none
                         focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 pr-10"
                />
                <button
                  @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-300"
                >
                  <EyeOff v-if="showPassword" class="w-4 h-4" />
                  <Eye v-else class="w-4 h-4" />
                </button>
              </div>
              <p v-if="acceptForm.errors.password" class="text-red-400 text-xs mt-1">
                {{ acceptForm.errors.password }}
              </p>
            </div>

            <!-- Confirm password -->
            <div>
              <label class="text-slate-300 text-sm font-medium block mb-1.5">Confirm Password</label>
              <input
                v-model="acceptForm.password_confirmation"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Repeat password"
                class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-2.5
                       text-white placeholder-slate-500 text-sm focus:outline-none
                       focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              />
            </div>

            <!-- Submit -->
            <button
              @click="submitAccept"
              :disabled="acceptForm.processing || !acceptForm.name || !acceptForm.password"
              class="w-full flex items-center justify-center gap-2 py-3 bg-indigo-600
                     hover:bg-indigo-500 disabled:opacity-60 text-white rounded-xl
                     font-medium transition-colors"
            >
              <UserPlus class="w-4 h-4" />
              {{ acceptForm.processing ? 'Creating Account…' : 'Create Account & Join' }}
            </button>
          </template>

          <!-- ── Decline Link ────────────────────────────────────── -->
          <div class="text-center pt-2">
            <button
              @click="submitDecline"
              class="text-slate-500 hover:text-slate-400 text-xs transition-colors"
            >
              Decline this invitation
            </button>
          </div>

        </div>
      </div>

    </div>
  </div>
</template>
