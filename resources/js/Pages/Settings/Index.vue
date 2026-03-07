<script setup>
// FILE: resources/js/Pages/Settings/Index.vue
import { ref, computed } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    User, Lock, Settings, Building2, Image,
    Key, Bell, Save, Eye, EyeOff, Plus, Trash2,
    Copy, CheckCircle, Zap
} from 'lucide-vue-next'

const props = defineProps({
    user:          Object,
    tenant:        Object,
    api_tokens:    { type: Array, default: () => [] },
    ollama_models: { type: Array, default: () => [] },
})

const page  = usePage()
const flash = computed(() => page.props.flash ?? {})

// ── Role gate ─────────────────────────────────────────────────────
const canManage = computed(() => ['admin', 'superadmin'].includes(props.user.role))

// ── Tabs: members only get Profile + Notifications ────────────────
const tabs = computed(() => [
    { key: 'profile',       label: 'Profile',       icon: User      },
    ...(canManage.value ? [
        { key: 'workspace', label: 'Workspace',     icon: Building2 },
        { key: 'api_keys',  label: 'API Keys',      icon: Key       },
    ] : []),
    { key: 'notifications', label: 'Notifications', icon: Bell      },
])
const activeTab = ref('profile')

// ── New token banner ──────────────────────────────────────────────
const newToken    = computed(() => flash.value.new_token ?? null)
const copiedToken = ref(false)
function copyToken() {
    navigator.clipboard.writeText(newToken.value)
    copiedToken.value = true; setTimeout(() => copiedToken.value = false, 2000)
}

// ── Forms ─────────────────────────────────────────────────────────
const profileForm  = useForm({ name: props.user.name, email: props.user.email })
const showCurrent  = ref(false)
const showNew      = ref(false)
const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' })
const workspaceForm = useForm({ name: props.tenant.name, default_model: props.tenant.default_model })

const logoPreview   = ref(props.tenant.logo_url)
const logoFile      = ref(null)
const logoUploading = ref(false)
function onLogoSelect(e) {
    const f = e.target.files[0]; if (!f) return
    logoFile.value = f; logoPreview.value = URL.createObjectURL(f)
}
function uploadLogo() {
    if (!logoFile.value) return; logoUploading.value = true
    const form = useForm({ logo: logoFile.value })
    form.post(route('settings.logo'), { onFinish: () => { logoUploading.value = false; logoFile.value = null } })
}
function deleteLogo() {
    if (!confirm('Remove logo?')) return
    router.delete(route('settings.logo.delete'), { preserveScroll: true, onSuccess: () => { logoPreview.value = null } })
}

const newKeyName = ref('')
const keyForm    = useForm({ name: '' })
function createKey() {
    if (!newKeyName.value.trim()) return
    keyForm.name = newKeyName.value.trim()
    keyForm.post(route('settings.apikey.create'), { preserveScroll: true, onSuccess: () => { newKeyName.value = '' } })
}
function deleteKey(id) {
    if (!confirm('Revoke this API key?')) return
    router.delete(route('settings.apikey.delete', id), { preserveScroll: true })
}

const notifForm = useForm({ ...props.user.notification_preferences })

const inp = 'w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm outline-none focus:border-indigo-500 transition-colors disabled:opacity-50'
</script>

<template>
    <AppLayout title="Settings">
        <div class="max-w-3xl mx-auto px-4 py-8">

            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-indigo-600/20 rounded-lg"><Settings class="w-5 h-5 text-indigo-400"/></div>
                <div>
                    <h1 class="text-xl font-bold text-white">Settings</h1>
                    <p v-if="!canManage" class="text-slate-500 text-xs mt-0.5">
                        Profile &amp; notifications only — contact your admin for workspace changes.
                    </p>
                </div>
            </div>

            <div v-if="flash.success" class="mb-4 flex items-center gap-2 bg-green-900/30 border border-green-700/40 text-green-400 text-sm px-4 py-2.5 rounded-lg">
                <CheckCircle class="w-4 h-4 shrink-0"/> {{ flash.success }}
            </div>

            <div v-if="newToken" class="mb-4 bg-amber-900/30 border border-amber-700/40 rounded-lg p-4">
                <p class="text-amber-400 text-xs font-semibold mb-2">Copy your new API key — it won't be shown again.</p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 bg-slate-900 text-green-400 text-xs px-3 py-2 rounded font-mono break-all">{{ newToken }}</code>
                    <button @click="copyToken" class="shrink-0 p-2 bg-slate-700 hover:bg-slate-600 rounded-lg">
                        <CheckCircle v-if="copiedToken" class="w-4 h-4 text-green-400"/><Copy v-else class="w-4 h-4 text-slate-300"/>
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 mb-6 bg-slate-900 border border-slate-800 rounded-xl p-1">
                <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
                        class="flex-1 flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-sm font-medium transition-colors"
                        :class="activeTab === tab.key ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'">
                    <component :is="tab.icon" class="w-4 h-4"/><span class="hidden sm:inline">{{ tab.label }}</span>
                </button>
            </div>

            <!-- ── Profile ── -->
            <div v-if="activeTab === 'profile'" class="space-y-5">
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
                    <h2 class="text-white font-semibold text-sm mb-4 flex items-center gap-2">
                        <User class="w-4 h-4 text-slate-400"/> Profile Information
                    </h2>
                    <form @submit.prevent="profileForm.put(route('settings.profile'), { preserveScroll: true })" class="space-y-4">
                        <div>
                            <label class="block text-slate-400 text-xs mb-1">Name</label>
                            <input v-model="profileForm.name" type="text" required :class="inp"/>
                            <p v-if="profileForm.errors.name" class="text-red-400 text-xs mt-1">{{ profileForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs mb-1">Email</label>
                            <input v-model="profileForm.email" type="email" required :class="inp"/>
                            <p v-if="profileForm.errors.email" class="text-red-400 text-xs mt-1">{{ profileForm.errors.email }}</p>
                        </div>
                        <div class="flex items-center gap-3 pt-1">
                            <span class="text-xs text-slate-500">Role: <span class="text-slate-400 capitalize">{{ user.role }}</span></span>
                            <button type="submit" :disabled="profileForm.processing"
                                    class="ml-auto flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg disabled:opacity-50 transition-colors">
                                <Save class="w-4 h-4"/> Save
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
                    <h2 class="text-white font-semibold text-sm mb-4 flex items-center gap-2">
                        <Lock class="w-4 h-4 text-slate-400"/> Change Password
                    </h2>
                    <form @submit.prevent="passwordForm.put(route('settings.password'), { preserveScroll: true })" class="space-y-4">
                        <div>
                            <label class="block text-slate-400 text-xs mb-1">Current Password</label>
                            <div class="relative">
                                <input v-model="passwordForm.current_password" :type="showCurrent ? 'text' : 'password'" :class="inp + ' pr-10'"/>
                                <button type="button" @click="showCurrent = !showCurrent" class="absolute right-3 top-2.5 text-slate-400">
                                    <EyeOff v-if="showCurrent" class="w-4 h-4"/><Eye v-else class="w-4 h-4"/>
                                </button>
                            </div>
                            <p v-if="passwordForm.errors.current_password" class="text-red-400 text-xs mt-1">{{ passwordForm.errors.current_password }}</p>
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs mb-1">New Password</label>
                            <div class="relative">
                                <input v-model="passwordForm.password" :type="showNew ? 'text' : 'password'" :class="inp + ' pr-10'"/>
                                <button type="button" @click="showNew = !showNew" class="absolute right-3 top-2.5 text-slate-400">
                                    <EyeOff v-if="showNew" class="w-4 h-4"/><Eye v-else class="w-4 h-4"/>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs mb-1">Confirm New Password</label>
                            <input v-model="passwordForm.password_confirmation" type="password" :class="inp"/>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="passwordForm.processing"
                                    class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg disabled:opacity-50 transition-colors">
                                <Save class="w-4 h-4"/> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── Workspace (admin only — tab hidden for members) ── -->
            <div v-if="activeTab === 'workspace'" class="space-y-5">
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
                    <h2 class="text-white font-semibold text-sm mb-4 flex items-center gap-2">
                        <Image class="w-4 h-4 text-slate-400"/> Workspace Logo
                    </h2>
                    <div class="flex items-center gap-5">
                        <div class="w-20 h-20 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center overflow-hidden shrink-0">
                            <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-contain p-2"/>
                            <Building2 v-else class="w-8 h-8 text-slate-600"/>
                        </div>
                        <div class="space-y-2">
                            <p class="text-slate-400 text-xs">PNG, JPG, SVG or WebP. Max 2MB.</p>
                            <div class="flex gap-2 flex-wrap">
                                <label class="cursor-pointer flex items-center gap-2 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs rounded-lg">
                                    <Image class="w-3.5 h-3.5"/> Choose File
                                    <input type="file" accept="image/*" class="hidden" @change="onLogoSelect"/>
                                </label>
                                <button v-if="logoFile" @click="uploadLogo" :disabled="logoUploading"
                                        class="flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs rounded-lg disabled:opacity-50">
                                    <Save class="w-3.5 h-3.5"/> {{ logoUploading ? 'Uploading...' : 'Upload' }}
                                </button>
                                <button v-if="logoPreview" @click="deleteLogo"
                                        class="flex items-center gap-2 px-3 py-1.5 bg-red-900/40 hover:bg-red-800/50 text-red-400 text-xs rounded-lg">
                                    <Trash2 class="w-3.5 h-3.5"/> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
                    <h2 class="text-white font-semibold text-sm mb-4 flex items-center gap-2">
                        <Building2 class="w-4 h-4 text-slate-400"/> Workspace Settings
                    </h2>
                    <form @submit.prevent="workspaceForm.put(route('settings.workspace'), { preserveScroll: true })" class="space-y-4">
                        <div>
                            <label class="block text-slate-400 text-xs mb-1">Workspace Name</label>
                            <input v-model="workspaceForm.name" type="text" :class="inp"/>
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs mb-1 flex items-center gap-1">
                                <Zap class="w-3 h-3"/> Default AI Model
                            </label>
                            <select v-model="workspaceForm.default_model" :class="inp">
                                <option v-for="m in ollama_models" :key="m" :value="m">{{ m }}</option>
                            </select>
                        </div>
                        <p class="text-slate-600 text-xs">Slug: <span class="font-mono text-slate-500">{{ tenant.slug }}</span></p>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="workspaceForm.processing"
                                    class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg disabled:opacity-50 transition-colors">
                                <Save class="w-4 h-4"/> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── API Keys (admin only — tab hidden for members) ── -->
            <div v-if="activeTab === 'api_keys'">
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
                    <h2 class="text-white font-semibold text-sm mb-1 flex items-center gap-2">
                        <Key class="w-4 h-4 text-slate-400"/> API Keys
                    </h2>
                    <p class="text-slate-500 text-xs mb-5">Use tokens to access the EasyAI API from external apps or mobile.</p>
                    <div class="flex gap-2 mb-5">
                        <input v-model="newKeyName" type="text" placeholder="Key name (e.g. Mobile App)" :class="inp + ' flex-1'"/>
                        <button @click="createKey"
                                class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg transition-colors">
                            <Plus class="w-4 h-4"/> Create
                        </button>
                    </div>
                    <p v-if="api_tokens.length === 0" class="text-slate-500 text-sm text-center py-6">No API keys yet.</p>
                    <div v-else class="space-y-2">
                        <div v-for="token in api_tokens" :key="token.id"
                             class="flex items-center justify-between bg-slate-800 rounded-lg px-4 py-3">
                            <div>
                                <p class="text-white text-sm font-medium">{{ token.name }}</p>
                                <p class="text-slate-500 text-xs">
                                    Created {{ new Date(token.created_at).toLocaleDateString() }} ·
                                    {{ token.last_used_at ? 'Last used ' + new Date(token.last_used_at).toLocaleDateString() : 'Never used' }}
                                </p>
                            </div>
                            <button @click="deleteKey(token.id)" class="text-red-400 hover:text-red-300 p-1.5 transition-colors">
                                <Trash2 class="w-4 h-4"/>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Notifications ── -->
            <div v-if="activeTab === 'notifications'">
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
                    <h2 class="text-white font-semibold text-sm mb-1 flex items-center gap-2">
                        <Bell class="w-4 h-4 text-slate-400"/> Notification Preferences
                    </h2>
                    <p class="text-slate-500 text-xs mb-5">Choose which in-app notifications you receive.</p>
                    <form @submit.prevent="notifForm.put(route('settings.notifications'), { preserveScroll: true })" class="space-y-3">
                        <label v-for="(label, key) in {
                            quota_warning:   'Token quota at 80% warning',
                            quota_exceeded:  'Token quota exceeded alert',
                            payment_confirm: 'Payment confirmation',
                            team_invitation: 'Team invitation received',
                        }" :key="key" class="flex items-center justify-between bg-slate-800 rounded-lg px-4 py-3 cursor-pointer">
                            <span class="text-slate-300 text-sm">{{ label }}</span>
                            <div class="relative">
                                <input type="checkbox" v-model="notifForm[key]" class="sr-only peer"/>
                                <div class="w-9 h-5 bg-slate-600 peer-checked:bg-indigo-600 rounded-full transition-colors peer-focus:ring-2 peer-focus:ring-indigo-500/50"></div>
                                <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4"></div>
                            </div>
                        </label>
                        <div class="flex justify-end pt-2">
                            <button type="submit" :disabled="notifForm.processing"
                                    class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg disabled:opacity-50 transition-colors">
                                <Save class="w-4 h-4"/> Save Preferences
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>