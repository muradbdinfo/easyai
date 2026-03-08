<script setup>
// FILE: resources/js/Pages/Admin/Settings/Index.vue
import { ref, computed } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    Settings, Globe, Zap, Mail, Save, Image, Building2,
    CheckCircle, AlertCircle, Trash2, RefreshCw, Send,
    Layout, Plus, X, Palette, Sun, Moon,
    Shield, UserPlus, Eye, EyeOff, Users
} from 'lucide-vue-next'

const props = defineProps({
    platform:    Object,
    ollama:      Object,
    mail:        Object,
    theme:       Object,
    landing:     Object,
    superadmins: { type: Array, default: () => [] },
})

const page      = usePage()
const activeTab = ref('theme')
const flash     = computed(() => page.props.flash ?? {})

const tabs = [
    { key:'theme',        label:'Theme',        icon:Palette  },
    { key:'platform',     label:'Platform',     icon:Globe    },
    { key:'ollama',       label:'Ollama',       icon:Zap      },
    { key:'mail',         label:'Mail/SMTP',    icon:Mail     },
    { key:'landing',      label:'Landing Page', icon:Layout   },
    { key:'superadmins',  label:'Superadmins',  icon:Shield   },
]

const inp = 'w-full border border-slate-200 rounded-lg px-3 py-2 text-slate-800 text-sm outline-none focus:border-indigo-400'

// ── Theme ─────────────────────────────────────────────────────────
const themeForm    = useForm({ brand: props.theme?.brand ?? '#6366f1', tenant_mode: props.theme?.tenant_mode ?? 'dark', landing_mode: props.theme?.landing_mode ?? 'dark' })
const previewBrand = ref(props.theme?.brand ?? '#6366f1')

// ── Platform ──────────────────────────────────────────────────────
const platformForm  = useForm({ app_name: props.platform.app_name ?? '', support_email: props.platform.support_email ?? '' })
const logoPreview   = ref(props.platform.logo_url)
const logoFile      = ref(null)
const logoUploading = ref(false)

function onLogoSelect(e) {
    const f = e.target.files[0]; if (!f) return
    logoFile.value = f; logoPreview.value = URL.createObjectURL(f)
}
function uploadLogo() {
    if (!logoFile.value) return; logoUploading.value = true
    const form = useForm({ logo: logoFile.value })
    form.post(route('admin.settings.logo'), { onFinish: () => { logoUploading.value = false; logoFile.value = null } })
}
function deleteLogo() {
    if (!confirm('Remove logo?')) return
    router.delete(route('admin.settings.logo.delete'), { preserveScroll: true, onSuccess: () => { logoPreview.value = null } })
}

// ── Ollama ────────────────────────────────────────────────────────
const ollamaForm   = useForm({ url: props.ollama.url ?? '', model: props.ollama.model ?? 'llama3' })
const ollamaStatus = ref(null)
function testOllama() {
    ollamaStatus.value = 'testing'
    router.post(route('admin.settings.ollama.test'), {}, {
        preserveScroll: true,
        onSuccess: () => { ollamaStatus.value = 'ok'   },
        onError:   () => { ollamaStatus.value = 'fail' },
    })
}

// ── Mail ──────────────────────────────────────────────────────────
const mailForm    = useForm({ host: props.mail.host ?? '', port: props.mail.port ?? 587, username: props.mail.username ?? '', password: '', from_name: props.mail.from_name ?? '', from_email: props.mail.from_email ?? '' })
const testMailTo  = ref('')
const mailSending = ref(false)
function sendTestMail() {
    if (!testMailTo.value) return; mailSending.value = true
    router.post(route('admin.settings.mail.test'), { email: testMailTo.value }, {
        preserveScroll: true, onFinish: () => { mailSending.value = false }
    })
}

// ── Landing ────────────────────────────────────────────────────────
const defaultFeatures = [
    { icon:'🧠', title:'Long-Term Memory',    desc:'Summarizes past chats into project context.' },
    { icon:'📁', title:'RAG Knowledge Base',  desc:'Upload docs and get AI answers from your data.' },
    { icon:'👥', title:'Team Collaboration',  desc:'Invite teammates, assign roles, restrict projects.' },
    { icon:'🔒', title:'100% Private',        desc:'Self-hosted Ollama. Data never leaves your server.' },
]
const defaultFaq = [
    { q:'Is my data private?', a:'Yes. EasyAI is fully self-hosted.' },
    { q:'Which AI models are supported?', a:'Any model supported by Ollama — llama3, mistral, codellama, gemma, and more.' },
]
const landingForm = useForm({
    hero_title:    props.landing.hero_title    ?? 'Your Private AI Workspace',
    hero_subtitle: props.landing.hero_subtitle ?? 'Self-hosted, multi-tenant AI workspace.',
    hero_cta:      props.landing.hero_cta      ?? 'Start Free Trial',
    announcement:  props.landing.announcement  ?? '',
    show_pricing:  props.landing.show_pricing  ?? true,
    show_contact:  props.landing.show_contact  ?? true,
    contact_email: props.landing.contact_email ?? '',
    footer_text:   props.landing.footer_text   ?? 'EasyAI — Self-Hosted AI Workspace',
    features:      props.landing.features?.length ? [...props.landing.features] : defaultFeatures,
    faq:           props.landing.faq?.length ? [...props.landing.faq] : defaultFaq,
})
function addFeature() { landingForm.features.push({ icon:'✨', title:'', desc:'' }) }
function removeFeature(i) { landingForm.features.splice(i, 1) }
function addFaq() { landingForm.faq.push({ q:'', a:'' }) }
function removeFaq(i) { landingForm.faq.splice(i, 1) }

// ── Superadmins ───────────────────────────────────────────────────
const saForm    = useForm({ name: '', email: '', password: '', password_confirmation: '' })
const showSaPw  = ref(false)
const showSaCpw = ref(false)

function deleteSuperAdmin(sa) {
    if (!confirm(`Remove ${sa.name} as superadmin? They will lose all admin access.`)) return
    router.delete(route('admin.settings.superadmin.delete', sa.id), { preserveScroll: true })
}
</script>

<template>
    <AdminLayout title="Settings">
        <div class="max-w-2xl mx-auto">

            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-indigo-50 rounded-lg"><Settings class="w-5 h-5 text-indigo-600"/></div>
                <h1 class="text-xl font-bold text-slate-800">Platform Settings</h1>
            </div>

            <div v-if="flash.success" class="mb-4 flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-2.5 rounded-lg">
                <CheckCircle class="w-4 h-4 shrink-0"/> {{ flash.success }}
            </div>
            <div v-if="flash.error" class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-2.5 rounded-lg">
                <AlertCircle class="w-4 h-4 shrink-0"/> {{ flash.error }}
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 mb-6 bg-slate-100 rounded-xl p-1 overflow-x-auto">
                <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
                        class="flex-1 flex items-center justify-center gap-1.5 py-2 px-2 rounded-lg text-xs font-medium transition-colors whitespace-nowrap min-w-fit"
                        :class="activeTab===tab.key ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                    <component :is="tab.icon" class="w-3.5 h-3.5"/> {{ tab.label }}
                </button>
            </div>

            <!-- ── THEME TAB ── -->
            <div v-if="activeTab==='theme'">
                <form @submit.prevent="themeForm.put(route('admin.settings.theme'), { preserveScroll: true })">
                    <div class="bg-white rounded-xl border border-slate-200 p-6 space-y-6">
                        <h2 class="text-slate-700 font-semibold text-sm flex items-center gap-2"><Palette class="w-4 h-4 text-slate-400"/> Global Theme</h2>
                        <div>
                            <label class="block text-slate-500 text-xs mb-2">Brand / Accent Color</label>
                            <div class="flex items-center gap-3">
                                <input v-model="themeForm.brand" type="color" @input="previewBrand = themeForm.brand"
                                       class="w-12 h-12 rounded-xl border border-slate-200 cursor-pointer p-0.5 bg-white"/>
                                <input v-model="themeForm.brand" type="text" maxlength="7"
                                       class="border border-slate-200 rounded-lg px-3 py-2 text-slate-800 text-sm font-mono w-28 outline-none focus:border-indigo-400"
                                       @input="previewBrand = themeForm.brand"/>
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg border border-slate-200" :style="{background: previewBrand}"/>
                                    <div class="flex gap-1.5">
                                        <span v-for="c in ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#0ea5e9','#ec4899','#f97316']"
                                              :key="c" @click="themeForm.brand=c; previewBrand=c"
                                              class="w-5 h-5 rounded cursor-pointer border-2 transition-all hover:scale-110"
                                              :style="{background:c, borderColor: themeForm.brand===c ? '#1e293b' : 'transparent'}"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-slate-500 text-xs mb-2">Tenant App Mode</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" @click="themeForm.tenant_mode='dark'"
                                        class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl border-2 text-sm font-medium transition-all"
                                        :class="themeForm.tenant_mode==='dark' ? 'border-indigo-500 bg-slate-900 text-white' : 'border-slate-200 text-slate-500 hover:border-slate-300'">
                                    <Moon class="w-4 h-4"/> Dark
                                </button>
                                <button type="button" @click="themeForm.tenant_mode='light'"
                                        class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl border-2 text-sm font-medium transition-all"
                                        :class="themeForm.tenant_mode==='light' ? 'border-indigo-500 bg-slate-50 text-slate-800' : 'border-slate-200 text-slate-500 hover:border-slate-300'">
                                    <Sun class="w-4 h-4"/> Light
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-slate-500 text-xs mb-2">Landing Page Mode</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" @click="themeForm.landing_mode='dark'"
                                        class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl border-2 text-sm font-medium transition-all"
                                        :class="themeForm.landing_mode==='dark' ? 'border-indigo-500 bg-slate-900 text-white' : 'border-slate-200 text-slate-500 hover:border-slate-300'">
                                    <Moon class="w-4 h-4"/> Dark
                                </button>
                                <button type="button" @click="themeForm.landing_mode='light'"
                                        class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl border-2 text-sm font-medium transition-all"
                                        :class="themeForm.landing_mode==='light' ? 'border-indigo-500 bg-slate-50 text-slate-800' : 'border-slate-200 text-slate-500 hover:border-slate-300'">
                                    <Sun class="w-4 h-4"/> Light
                                </button>
                            </div>
                            <p class="text-slate-400 text-xs mt-2">Admin panel always uses dark sidebar + white content.</p>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="themeForm.processing"
                                    class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg disabled:opacity-50 transition-colors">
                                <Save class="w-4 h-4"/> Save Theme
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ── PLATFORM TAB ── -->
            <div v-if="activeTab==='platform'" class="space-y-5">
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-slate-700 font-semibold text-sm mb-4 flex items-center gap-2"><Image class="w-4 h-4 text-slate-400"/> Logo</h2>
                    <div class="flex items-center gap-5">
                        <div class="w-20 h-20 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center overflow-hidden shrink-0">
                            <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-contain p-2"/>
                            <Building2 v-else class="w-8 h-8 text-slate-400"/>
                        </div>
                        <div class="space-y-2">
                            <p class="text-slate-400 text-xs">PNG, JPG, SVG. Max 2MB.</p>
                            <div class="flex gap-2">
                                <label class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs rounded-lg cursor-pointer transition-colors">
                                    <Image class="w-3.5 h-3.5"/> Choose
                                    <input type="file" accept="image/*" class="hidden" @change="onLogoSelect"/>
                                </label>
                                <button v-if="logoFile" type="button" @click="uploadLogo" :disabled="logoUploading"
                                        class="flex items-center gap-1.5 px-3 py-1.5 bg-green-50 hover:bg-green-100 text-green-600 text-xs rounded-lg disabled:opacity-50">
                                    <Save class="w-3.5 h-3.5"/> {{ logoUploading ? 'Uploading…' : 'Upload' }}
                                </button>
                                <button v-if="logoPreview && !logoFile" type="button" @click="deleteLogo"
                                        class="flex items-center gap-1.5 px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-500 text-xs rounded-lg">
                                    <Trash2 class="w-3.5 h-3.5"/> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-slate-700 font-semibold text-sm mb-4 flex items-center gap-2"><Globe class="w-4 h-4 text-slate-400"/> App Info</h2>
                    <form @submit.prevent="platformForm.put(route('admin.settings.platform'), { preserveScroll: true })" class="space-y-4">
                        <div><label class="block text-slate-500 text-xs mb-1">App Name</label><input v-model="platformForm.app_name" type="text" required :class="inp"/></div>
                        <div><label class="block text-slate-500 text-xs mb-1">Support Email</label><input v-model="platformForm.support_email" type="email" required :class="inp"/></div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="platformForm.processing"
                                    class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg disabled:opacity-50">
                                <Save class="w-4 h-4"/> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── OLLAMA TAB ── -->
            <div v-if="activeTab==='ollama'">
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-slate-700 font-semibold text-sm mb-4 flex items-center gap-2"><Zap class="w-4 h-4 text-slate-400"/> Ollama</h2>
                    <form @submit.prevent="ollamaForm.put(route('admin.settings.ollama'), { preserveScroll: true })" class="space-y-4">
                        <div><label class="block text-slate-500 text-xs mb-1">API URL</label><input v-model="ollamaForm.url" type="url" required placeholder="http://127.0.0.1:11434" :class="inp+' font-mono'"/></div>
                        <div><label class="block text-slate-500 text-xs mb-1">Default Model</label><input v-model="ollamaForm.model" type="text" required placeholder="llama3" :class="inp+' font-mono'"/></div>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="testOllama" :disabled="ollamaStatus==='testing'"
                                    class="flex items-center gap-2 px-3 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 text-sm rounded-lg disabled:opacity-50">
                                <RefreshCw class="w-4 h-4" :class="ollamaStatus==='testing'?'animate-spin':''"/> Test Connection
                            </button>
                            <span v-if="ollamaStatus==='ok'"   class="flex items-center gap-1 text-green-600 text-xs"><CheckCircle class="w-4 h-4"/> Connected</span>
                            <span v-if="ollamaStatus==='fail'" class="flex items-center gap-1 text-red-500 text-xs"><AlertCircle class="w-4 h-4"/> Failed</span>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="ollamaForm.processing"
                                    class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg disabled:opacity-50">
                                <Save class="w-4 h-4"/> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── MAIL TAB ── -->
            <div v-if="activeTab==='mail'">
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-slate-700 font-semibold text-sm mb-4 flex items-center gap-2"><Mail class="w-4 h-4 text-slate-400"/> SMTP</h2>
                    <form @submit.prevent="mailForm.put(route('admin.settings.mail'), { preserveScroll: true })" class="space-y-4">
                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-2"><label class="block text-slate-500 text-xs mb-1">Host</label><input v-model="mailForm.host" type="text" required placeholder="smtp.mailtrap.io" :class="inp+' font-mono'"/></div>
                            <div><label class="block text-slate-500 text-xs mb-1">Port</label><input v-model="mailForm.port" type="number" required :class="inp+' font-mono'"/></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="block text-slate-500 text-xs mb-1">Username</label><input v-model="mailForm.username" type="text" :class="inp"/></div>
                            <div><label class="block text-slate-500 text-xs mb-1">Password</label><input v-model="mailForm.password" type="password" placeholder="Leave blank to keep current" :class="inp"/></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="block text-slate-500 text-xs mb-1">From Name</label><input v-model="mailForm.from_name" type="text" required :class="inp"/></div>
                            <div><label class="block text-slate-500 text-xs mb-1">From Email</label><input v-model="mailForm.from_email" type="email" required :class="inp"/></div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="mailForm.processing"
                                    class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg disabled:opacity-50">
                                <Save class="w-4 h-4"/> Save
                            </button>
                        </div>
                    </form>
                    <div class="border-t border-slate-100 mt-5 pt-5">
                        <h3 class="text-slate-600 text-xs font-semibold mb-3">Send Test Email</h3>
                        <div class="flex gap-2">
                            <input v-model="testMailTo" type="email" placeholder="test@example.com" :class="inp"/>
                            <button @click="sendTestMail" :disabled="mailSending"
                                    class="flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white text-sm rounded-lg disabled:opacity-50 shrink-0">
                                <Send class="w-4 h-4"/> {{ mailSending ? 'Sending…' : 'Send' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── LANDING TAB ── -->
            <div v-if="activeTab==='landing'" class="space-y-5">
                <form @submit.prevent="landingForm.put(route('admin.settings.landing'), { preserveScroll: true })">
                    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-5">
                        <h2 class="text-slate-700 font-semibold text-sm mb-5 flex items-center gap-2"><Layout class="w-4 h-4 text-slate-400"/> Hero & Content</h2>
                        <div class="space-y-4">
                            <div><label class="block text-slate-500 text-xs mb-1">Hero Title</label><input v-model="landingForm.hero_title" type="text" required :class="inp"/></div>
                            <div><label class="block text-slate-500 text-xs mb-1">Hero Subtitle</label><textarea v-model="landingForm.hero_subtitle" rows="2" :class="inp+' resize-none'"></textarea></div>
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="block text-slate-500 text-xs mb-1">CTA Text</label><input v-model="landingForm.hero_cta" :class="inp"/></div>
                                <div><label class="block text-slate-500 text-xs mb-1">Footer Text</label><input v-model="landingForm.footer_text" :class="inp"/></div>
                            </div>
                            <div><label class="block text-slate-500 text-xs mb-1">Announcement <span class="text-slate-400">(optional)</span></label><input v-model="landingForm.announcement" :class="inp" placeholder="🎉 New feature!"/></div>
                            <div><label class="block text-slate-500 text-xs mb-1">Contact Email</label><input v-model="landingForm.contact_email" type="email" required :class="inp"/></div>
                            <div class="flex items-center gap-6">
                                <label class="flex items-center gap-2 cursor-pointer text-slate-600 text-sm"><input v-model="landingForm.show_pricing" type="checkbox" class="rounded"/> Show Pricing</label>
                                <label class="flex items-center gap-2 cursor-pointer text-slate-600 text-sm"><input v-model="landingForm.show_contact" type="checkbox" class="rounded"/> Show Contact</label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-slate-700 font-semibold text-sm">Feature Cards</h2>
                            <button type="button" @click="addFeature" class="flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs rounded-lg"><Plus class="w-3.5 h-3.5"/> Add</button>
                        </div>
                        <div class="space-y-2">
                            <div v-for="(f,i) in landingForm.features" :key="i" class="flex gap-2 items-start p-3 bg-slate-50 rounded-lg">
                                <input v-model="f.icon" type="text" placeholder="🧠" maxlength="4" class="w-12 border border-slate-200 rounded-lg px-2 py-2 text-center outline-none focus:border-indigo-400 bg-white"/>
                                <div class="flex-1 space-y-1.5">
                                    <input v-model="f.title" type="text" placeholder="Title" :class="inp"/>
                                    <input v-model="f.desc"  type="text" placeholder="Description" :class="inp"/>
                                </div>
                                <button type="button" @click="removeFeature(i)" class="text-red-400 hover:text-red-600 p-1 mt-1"><X class="w-4 h-4"/></button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-slate-700 font-semibold text-sm">FAQ Items</h2>
                            <button type="button" @click="addFaq" class="flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs rounded-lg"><Plus class="w-3.5 h-3.5"/> Add</button>
                        </div>
                        <div class="space-y-2">
                            <div v-for="(f,i) in landingForm.faq" :key="i" class="p-3 bg-slate-50 rounded-lg space-y-1.5">
                                <div class="flex gap-2">
                                    <input v-model="f.q" type="text" placeholder="Question" :class="inp"/>
                                    <button type="button" @click="removeFaq(i)" class="text-red-400 hover:text-red-600 p-1 shrink-0"><X class="w-4 h-4"/></button>
                                </div>
                                <textarea v-model="f.a" rows="2" placeholder="Answer" :class="inp+' resize-none'"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" :disabled="landingForm.processing"
                                class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg disabled:opacity-50 transition-colors">
                            <Save class="w-4 h-4"/> Save Landing Page
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── SUPERADMINS TAB ── -->
            <div v-if="activeTab==='superadmins'" class="space-y-5">

                <!-- Current list -->
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-2 px-6 py-4 border-b border-slate-100">
                        <Users class="w-4 h-4 text-slate-400"/>
                        <h2 class="text-slate-700 font-semibold text-sm">Current Superadmins</h2>
                        <span class="ml-auto text-xs text-slate-400">{{ superadmins.length }} total</span>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div v-for="sa in superadmins" :key="sa.id"
                             class="flex items-center justify-between px-6 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                                    <Shield class="w-4 h-4 text-indigo-600"/>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-slate-800">
                                        {{ sa.name }}
                                        <span v-if="sa.id === $page.props.auth?.user?.id"
                                              class="ml-1.5 text-xs bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded-full">you</span>
                                    </p>
                                    <p class="text-xs text-slate-400">{{ sa.email }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-400 hidden sm:block">
                                    Added {{ new Date(sa.created_at).toLocaleDateString() }}
                                </span>
                                <button v-if="sa.id !== $page.props.auth?.user?.id"
                                        @click="deleteSuperAdmin(sa)"
                                        class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Remove superadmin">
                                    <Trash2 class="w-3.5 h-3.5"/>
                                </button>
                                <span v-else class="w-7 h-7"/>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add new -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-slate-700 font-semibold text-sm mb-5 flex items-center gap-2">
                        <UserPlus class="w-4 h-4 text-slate-400"/> Add New Superadmin
                    </h2>
                    <form @submit.prevent="saForm.post(route('admin.settings.superadmin.store'), { preserveScroll: true, onSuccess: () => saForm.reset() })"
                          class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-slate-500 text-xs mb-1">Full Name</label>
                                <input v-model="saForm.name" type="text" required placeholder="John Doe" :class="inp"/>
                                <p v-if="saForm.errors.name" class="text-red-500 text-xs mt-1">{{ saForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-slate-500 text-xs mb-1">Email</label>
                                <input v-model="saForm.email" type="email" required placeholder="admin2@easyai.local" :class="inp"/>
                                <p v-if="saForm.errors.email" class="text-red-500 text-xs mt-1">{{ saForm.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-slate-500 text-xs mb-1">Password</label>
                                <div class="relative">
                                    <input v-model="saForm.password" :type="showSaPw ? 'text' : 'password'"
                                           required placeholder="Min 8 characters" :class="inp+' pr-10'"/>
                                    <button type="button" @click="showSaPw = !showSaPw"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                        <EyeOff v-if="showSaPw" class="w-4 h-4"/>
                                        <Eye    v-else           class="w-4 h-4"/>
                                    </button>
                                </div>
                                <p v-if="saForm.errors.password" class="text-red-500 text-xs mt-1">{{ saForm.errors.password }}</p>
                            </div>
                            <div>
                                <label class="block text-slate-500 text-xs mb-1">Confirm Password</label>
                                <div class="relative">
                                    <input v-model="saForm.password_confirmation" :type="showSaCpw ? 'text' : 'password'"
                                           required placeholder="Repeat password" :class="inp+' pr-10'"/>
                                    <button type="button" @click="showSaCpw = !showSaCpw"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                        <EyeOff v-if="showSaCpw" class="w-4 h-4"/>
                                        <Eye    v-else            class="w-4 h-4"/>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start gap-2 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
                            <Shield class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"/>
                            <p class="text-amber-700 text-xs leading-relaxed">
                                Superadmins have <strong>full unrestricted access</strong> to all tenants, plans, payments and settings. Only add people you fully trust.
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" :disabled="saForm.processing"
                                    class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg font-medium transition-colors disabled:opacity-50">
                                <UserPlus class="w-4 h-4"/>
                                {{ saForm.processing ? 'Creating…' : 'Create Superadmin' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Seeder bug fix notice -->
                <div class="bg-red-50 border border-red-200 rounded-xl px-5 py-4 flex items-start gap-3">
                    <AlertCircle class="w-4 h-4 text-red-500 shrink-0 mt-0.5"/>
                    <div>
                        <p class="text-red-700 text-xs font-semibold mb-1">Seeder bug fix</p>
                        <p class="text-red-600 text-xs">
                            If you seeded before fixing the plain-text password bug, run in tinker:
                        </p>
                        <code class="block mt-1.5 text-xs bg-red-100 text-red-800 px-3 py-2 rounded-lg font-mono">
                            User::where('role','superadmin')->each(fn($u) => $u->update(['password' => bcrypt('your_new_password')]));
                        </code>
                    </div>
                </div>

            </div>

        </div>
    </AdminLayout>
</template>