<script setup>
// FILE: resources/js/Pages/Admin/Settings/Index.vue

import { ref, computed } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    Settings, Globe, Zap, Mail, Save, Image,
    Building2, CheckCircle, AlertCircle, Trash2,
    RefreshCw, Send
} from 'lucide-vue-next'

const props = defineProps({
    platform: Object,
    ollama:   Object,
    mail:     Object,
})

const page      = usePage()
const activeTab = ref('platform')
const flash     = computed(() => page.props.flash ?? {})

const tabs = [
    { key: 'platform', label: 'Platform', icon: Globe },
    { key: 'ollama',   label: 'Ollama',   icon: Zap  },
    { key: 'mail',     label: 'Mail/SMTP', icon: Mail },
]

// ── Platform form ─────────────────────────────────────────────────
const platformForm = useForm({
    app_name:      props.platform.app_name ?? '',
    support_email: props.platform.support_email ?? '',
})

// ── Logo ──────────────────────────────────────────────────────────
const logoPreview   = ref(props.platform.logo_url)
const logoFile      = ref(null)
const logoUploading = ref(false)

function onLogoSelect(e) {
    const file = e.target.files[0]
    if (!file) return
    logoFile.value    = file
    logoPreview.value = URL.createObjectURL(file)
}

function uploadLogo() {
    if (!logoFile.value) return
    logoUploading.value = true
    const form = useForm({ logo: logoFile.value })
    form.post(route('admin.settings.logo'), {
        onFinish: () => { logoUploading.value = false; logoFile.value = null },
    })
}

function deleteLogo() {
    if (!confirm('Remove logo?')) return
    router.delete(route('admin.settings.logo.delete'), { preserveScroll: true,
        onSuccess: () => { logoPreview.value = null }
    })
}

// ── Ollama form ───────────────────────────────────────────────────
const ollamaForm    = useForm({
    url:   props.ollama.url   ?? '',
    model: props.ollama.model ?? 'llama3',
})
const ollamaStatus  = ref(null) // null | 'testing' | 'ok' | 'fail'

function testOllama() {
    ollamaStatus.value = 'testing'
    router.post(route('admin.settings.ollama.test'), {}, {
        preserveScroll: true,
        onSuccess: () => { ollamaStatus.value = 'ok'   },
        onError:   () => { ollamaStatus.value = 'fail' },
    })
}

// ── Mail form ─────────────────────────────────────────────────────
const mailForm    = useForm({
    host:       props.mail.host       ?? '',
    port:       props.mail.port       ?? 587,
    username:   props.mail.username   ?? '',
    password:   '',
    from_name:  props.mail.from_name  ?? '',
    from_email: props.mail.from_email ?? '',
})
const testEmail   = ref('')
const mailTesting = ref(false)

function sendTestMail() {
    if (!testEmail.value) return
    mailTesting.value = true
    router.post(route('admin.settings.mail.test'), { email: testEmail.value }, {
        preserveScroll: true,
        onFinish: () => { mailTesting.value = false },
    })
}
</script>

<template>
    <AdminLayout title="Settings">
        <div class="max-w-2xl mx-auto">

            <!-- Header -->
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-indigo-50 rounded-lg">
                    <Settings class="w-5 h-5 text-indigo-600" />
                </div>
                <h1 class="text-xl font-bold text-slate-800">Platform Settings</h1>
            </div>

            <!-- Flash -->
            <div v-if="flash.success"
                 class="mb-4 flex items-center gap-2 bg-green-50 border border-green-200
                        text-green-700 text-sm px-4 py-2.5 rounded-lg">
                <CheckCircle class="w-4 h-4 shrink-0" />
                {{ flash.success }}
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 mb-6 bg-slate-100 rounded-xl p-1">
                <button
                    v-for="tab in tabs" :key="tab.key"
                    @click="activeTab = tab.key"
                    class="flex-1 flex items-center justify-center gap-2 py-2 px-3 rounded-lg
                           text-sm font-medium transition-colors"
                    :class="activeTab === tab.key
                        ? 'bg-white text-indigo-600 shadow-sm'
                        : 'text-slate-500 hover:text-slate-700'"
                >
                    <component :is="tab.icon" class="w-4 h-4" />
                    {{ tab.label }}
                </button>
            </div>

            <!-- ── Platform Tab ──────────────────────────────────── -->
            <div v-if="activeTab === 'platform'" class="space-y-5">

                <!-- Logo -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-slate-700 font-semibold text-sm mb-4 flex items-center gap-2">
                        <Image class="w-4 h-4 text-slate-400" /> Platform Logo
                    </h2>
                    <div class="flex items-center gap-5">
                        <div class="w-20 h-20 rounded-xl bg-slate-100 border border-slate-200
                                    flex items-center justify-center overflow-hidden shrink-0">
                            <img v-if="logoPreview" :src="logoPreview"
                                 class="w-full h-full object-contain p-2" />
                            <Building2 v-else class="w-8 h-8 text-slate-400" />
                        </div>
                        <div class="space-y-2">
                            <p class="text-slate-400 text-xs">PNG, JPG, SVG. Max 2MB.</p>
                            <div class="flex gap-2 flex-wrap">
                                <label class="cursor-pointer flex items-center gap-2 px-3 py-1.5
                                              bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs
                                              rounded-lg transition-colors">
                                    <Image class="w-3.5 h-3.5" /> Choose
                                    <input type="file" accept="image/*" class="hidden" @change="onLogoSelect" />
                                </label>
                                <button v-if="logoFile" @click="uploadLogo" :disabled="logoUploading"
                                        class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600
                                               hover:bg-indigo-500 text-white text-xs rounded-lg
                                               disabled:opacity-50 transition-colors">
                                    <Save class="w-3.5 h-3.5" />
                                    {{ logoUploading ? 'Uploading...' : 'Upload' }}
                                </button>
                                <button v-if="logoPreview" @click="deleteLogo"
                                        class="flex items-center gap-1.5 px-3 py-1.5 bg-red-50
                                               hover:bg-red-100 text-red-600 text-xs rounded-lg transition-colors">
                                    <Trash2 class="w-3.5 h-3.5" /> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Platform info -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-slate-700 font-semibold text-sm mb-4 flex items-center gap-2">
                        <Globe class="w-4 h-4 text-slate-400" /> General
                    </h2>
                    <form @submit.prevent="platformForm.put(route('admin.settings.platform'), { preserveScroll: true })"
                          class="space-y-4">
                        <div>
                            <label class="block text-slate-500 text-xs mb-1">Platform Name</label>
                            <input v-model="platformForm.app_name" type="text" required
                                   class="w-full border border-slate-200 rounded-lg px-3 py-2
                                          text-slate-800 text-sm outline-none focus:border-indigo-400" />
                            <p v-if="platformForm.errors.app_name" class="text-red-500 text-xs mt-1">
                                {{ platformForm.errors.app_name }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-slate-500 text-xs mb-1">Support Email</label>
                            <input v-model="platformForm.support_email" type="email" required
                                   class="w-full border border-slate-200 rounded-lg px-3 py-2
                                          text-slate-800 text-sm outline-none focus:border-indigo-400" />
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="platformForm.processing"
                                    class="flex items-center gap-2 px-4 py-2 bg-indigo-600
                                           hover:bg-indigo-500 text-white text-sm rounded-lg
                                           disabled:opacity-50 transition-colors">
                                <Save class="w-4 h-4" /> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── Ollama Tab ────────────────────────────────────── -->
            <div v-if="activeTab === 'ollama'">
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-slate-700 font-semibold text-sm mb-4 flex items-center gap-2">
                        <Zap class="w-4 h-4 text-slate-400" /> Ollama Configuration
                    </h2>
                    <form @submit.prevent="ollamaForm.put(route('admin.settings.ollama'), { preserveScroll: true })"
                          class="space-y-4">
                        <div>
                            <label class="block text-slate-500 text-xs mb-1">Ollama API URL</label>
                            <input v-model="ollamaForm.url" type="url" required
                                   placeholder="http://127.0.0.1:11434"
                                   class="w-full border border-slate-200 rounded-lg px-3 py-2
                                          text-slate-800 text-sm outline-none focus:border-indigo-400 font-mono" />
                        </div>
                        <div>
                            <label class="block text-slate-500 text-xs mb-1">Default Model</label>
                            <input v-model="ollamaForm.model" type="text" required
                                   placeholder="llama3"
                                   class="w-full border border-slate-200 rounded-lg px-3 py-2
                                          text-slate-800 text-sm outline-none focus:border-indigo-400 font-mono" />
                        </div>

                        <!-- Connection test -->
                        <div class="flex items-center gap-3 pt-1">
                            <button type="button" @click="testOllama"
                                    :disabled="ollamaStatus === 'testing'"
                                    class="flex items-center gap-2 px-3 py-2 border border-slate-200
                                           hover:bg-slate-50 text-slate-600 text-sm rounded-lg
                                           disabled:opacity-50 transition-colors">
                                <RefreshCw class="w-4 h-4"
                                    :class="ollamaStatus === 'testing' ? 'animate-spin' : ''" />
                                Test Connection
                            </button>
                            <span v-if="ollamaStatus === 'ok'"
                                  class="flex items-center gap-1 text-green-600 text-xs">
                                <CheckCircle class="w-4 h-4" /> Connected
                            </span>
                            <span v-if="ollamaStatus === 'fail'"
                                  class="flex items-center gap-1 text-red-500 text-xs">
                                <AlertCircle class="w-4 h-4" /> Failed
                            </span>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" :disabled="ollamaForm.processing"
                                    class="flex items-center gap-2 px-4 py-2 bg-indigo-600
                                           hover:bg-indigo-500 text-white text-sm rounded-lg
                                           disabled:opacity-50 transition-colors">
                                <Save class="w-4 h-4" /> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── Mail Tab ──────────────────────────────────────── -->
            <div v-if="activeTab === 'mail'">
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-slate-700 font-semibold text-sm mb-4 flex items-center gap-2">
                        <Mail class="w-4 h-4 text-slate-400" /> SMTP Configuration
                    </h2>
                    <form @submit.prevent="mailForm.put(route('admin.settings.mail'), { preserveScroll: true })"
                          class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2 sm:col-span-1">
                                <label class="block text-slate-500 text-xs mb-1">SMTP Host</label>
                                <input v-model="mailForm.host" type="text"
                                       placeholder="smtp.gmail.com"
                                       class="w-full border border-slate-200 rounded-lg px-3 py-2
                                              text-slate-800 text-sm outline-none focus:border-indigo-400" />
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label class="block text-slate-500 text-xs mb-1">Port</label>
                                <input v-model="mailForm.port" type="number"
                                       placeholder="587"
                                       class="w-full border border-slate-200 rounded-lg px-3 py-2
                                              text-slate-800 text-sm outline-none focus:border-indigo-400" />
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label class="block text-slate-500 text-xs mb-1">Username</label>
                                <input v-model="mailForm.username" type="text"
                                       class="w-full border border-slate-200 rounded-lg px-3 py-2
                                              text-slate-800 text-sm outline-none focus:border-indigo-400" />
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label class="block text-slate-500 text-xs mb-1">Password</label>
                                <input v-model="mailForm.password" type="password"
                                       placeholder="Leave blank to keep current"
                                       class="w-full border border-slate-200 rounded-lg px-3 py-2
                                              text-slate-800 text-sm outline-none focus:border-indigo-400" />
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label class="block text-slate-500 text-xs mb-1">From Name</label>
                                <input v-model="mailForm.from_name" type="text"
                                       placeholder="EasyAI"
                                       class="w-full border border-slate-200 rounded-lg px-3 py-2
                                              text-slate-800 text-sm outline-none focus:border-indigo-400" />
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label class="block text-slate-500 text-xs mb-1">From Email</label>
                                <input v-model="mailForm.from_email" type="email"
                                       class="w-full border border-slate-200 rounded-lg px-3 py-2
                                              text-slate-800 text-sm outline-none focus:border-indigo-400" />
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" :disabled="mailForm.processing"
                                    class="flex items-center gap-2 px-4 py-2 bg-indigo-600
                                           hover:bg-indigo-500 text-white text-sm rounded-lg
                                           disabled:opacity-50 transition-colors">
                                <Save class="w-4 h-4" /> Save
                            </button>
                        </div>
                    </form>

                    <!-- Test mail -->
                    <div class="mt-5 pt-5 border-t border-slate-100">
                        <p class="text-slate-600 text-sm font-medium mb-3 flex items-center gap-2">
                            <Send class="w-4 h-4 text-slate-400" /> Send Test Email
                        </p>
                        <div class="flex gap-2">
                            <input v-model="testEmail" type="email" placeholder="you@example.com"
                                   class="flex-1 border border-slate-200 rounded-lg px-3 py-2
                                          text-slate-800 text-sm outline-none focus:border-indigo-400" />
                            <button @click="sendTestMail" :disabled="mailTesting || !testEmail"
                                    class="flex items-center gap-2 px-4 py-2 bg-slate-700
                                           hover:bg-slate-600 text-white text-sm rounded-lg
                                           disabled:opacity-50 transition-colors">
                                <Send class="w-4 h-4" />
                                {{ mailTesting ? 'Sending...' : 'Send' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>