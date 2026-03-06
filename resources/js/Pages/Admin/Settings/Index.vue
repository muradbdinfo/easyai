<script setup>
// FILE: resources/js/Pages/Admin/Settings/Index.vue
import { ref, computed } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Settings, Globe, Zap, Mail, Save, Image, Building2, CheckCircle, AlertCircle, Trash2, RefreshCw, Send, Layout, Plus, X, Palette, Sun, Moon } from 'lucide-vue-next'

const props = defineProps({ platform: Object, ollama: Object, mail: Object, theme: Object, landing: Object })

const page      = usePage()
const activeTab = ref('theme')
const flash     = computed(() => page.props.flash ?? {})

const tabs = [
    { key:'theme',    label:'Theme',        icon:Palette },
    { key:'platform', label:'Platform',     icon:Globe   },
    { key:'ollama',   label:'Ollama',       icon:Zap     },
    { key:'mail',     label:'Mail/SMTP',    icon:Mail    },
    { key:'landing',  label:'Landing Page', icon:Layout  },
]

const inp = 'w-full border border-slate-200 rounded-lg px-3 py-2 text-slate-800 text-sm outline-none focus:border-indigo-400'

// ── Theme ─────────────────────────────────────────────────────────
const themeForm = useForm({
    brand:        props.theme?.brand        ?? '#6366f1',
    tenant_mode:  props.theme?.tenant_mode  ?? 'dark',
    landing_mode: props.theme?.landing_mode ?? 'dark',
})
// Live preview while picking color
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
const mailForm  = useForm({ host: props.mail.host ?? '', port: props.mail.port ?? 587, username: props.mail.username ?? '', password: '', from_name: props.mail.from_name ?? '', from_email: props.mail.from_email ?? '' })
const testEmail = ref('')
const mailTesting = ref(false)
function sendTestMail() {
    if (!testEmail.value) return; mailTesting.value = true
    router.post(route('admin.settings.mail.test'), { email: testEmail.value }, { preserveScroll: true, onFinish: () => { mailTesting.value = false } })
}

// ── Landing ───────────────────────────────────────────────────────
const defaultFeatures = [
    { icon:'🧠', title:'AI Memory',          desc:'Projects remember context across conversations.' },
    { icon:'📁', title:'Projects & Chats',   desc:'Organize work into projects with full history.' },
    { icon:'📚', title:'Knowledge Base',     desc:'Upload docs. AI answers from your own data.'    },
    { icon:'👥', title:'Team Collaboration', desc:'Invite teammates, assign roles.'                },
    { icon:'🔒', title:'100% Private',       desc:'Self-hosted Ollama. Your data stays private.'   },
    { icon:'💳', title:'Flexible Billing',   desc:'COD, bKash/Nagad via SSLCommerz, or Stripe.'    },
]
const defaultFaq = [
    { q:'Is my data private?',            a:'Yes. All AI processing via your own Ollama instance.' },
    { q:'Which AI models are supported?', a:'Any Ollama model — llama3, mistral, codellama, gemma.' },
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
                        <h2 class="text-slate-700 font-semibold text-sm flex items-center gap-2">
                            <Palette class="w-4 h-4 text-slate-400"/> Global Theme
                        </h2>

                        <!-- Brand color -->
                        <div>
                            <label class="block text-slate-500 text-xs mb-2">Brand / Accent Color</label>
                            <div class="flex items-center gap-3">
                                <input v-model="themeForm.brand" type="color" @input="previewBrand = themeForm.brand"
                                       class="w-12 h-12 rounded-xl border border-slate-200 cursor-pointer p-0.5 bg-white"/>
                                <input v-model="themeForm.brand" type="text" maxlength="7"
                                       class="border border-slate-200 rounded-lg px-3 py-2 text-slate-800 text-sm font-mono w-28 outline-none focus:border-indigo-400"
                                       @input="previewBrand = themeForm.brand"/>
                                <!-- Live preview -->
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg border border-slate-200" :style="{background: previewBrand}"/>
                                    <div class="flex gap-1.5">
                                        <span v-for="c in ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#0ea5e9','#ec4899','#f97316']"
                                              :key="c" class="w-5 h-5 rounded cursor-pointer border-2 transition-all hover:scale-110"
                                              :style="{background:c, borderColor: themeForm.brand===c ? '#000':'transparent'}"
                                              @click="themeForm.brand = c; previewBrand = c"/>
                                    </div>
                                </div>
                            </div>
                            <p class="text-slate-400 text-xs mt-2">Applied to: sidebar active items, buttons, badges — on all panels and landing page.</p>
                        </div>

                        <!-- Tenant mode -->
                        <div>
                            <label class="block text-slate-500 text-xs mb-2">Tenant App Theme</label>
                            <div class="flex gap-3">
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

                        <!-- Landing mode -->
                        <div>
                            <label class="block text-slate-500 text-xs mb-2">Landing Page Theme</label>
                            <div class="flex gap-3">
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

                        <div class="flex justify-end pt-2">
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
                            <div class="flex gap-2 flex-wrap">
                                <label class="cursor-pointer flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs rounded-lg">
                                    <Image class="w-3.5 h-3.5"/> Choose
                                    <input type="file" accept="image/*" class="hidden" @change="onLogoSelect"/>
                                </label>
                                <button v-if="logoFile" @click="uploadLogo" :disabled="logoUploading"
                                        class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs rounded-lg disabled:opacity-50">
                                    <Save class="w-3.5 h-3.5"/> {{ logoUploading ? 'Uploading...' : 'Upload' }}
                                </button>
                                <button v-if="logoPreview" @click="deleteLogo"
                                        class="flex items-center gap-1.5 px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs rounded-lg">
                                    <Trash2 class="w-3.5 h-3.5"/> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-slate-700 font-semibold text-sm mb-4 flex items-center gap-2"><Globe class="w-4 h-4 text-slate-400"/> General</h2>
                    <form @submit.prevent="platformForm.put(route('admin.settings.platform'), { preserveScroll: true })" class="space-y-4">
                        <div><label class="block text-slate-500 text-xs mb-1">Platform Name</label><input v-model="platformForm.app_name" type="text" required :class="inp"/></div>
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
                                <RefreshCw class="w-4 h-4" :class="ollamaStatus==='testing'?'animate-spin':''"/> Test
                            </button>
                            <span v-if="ollamaStatus==='ok'" class="flex items-center gap-1 text-green-600 text-xs"><CheckCircle class="w-4 h-4"/> Connected</span>
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
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2 sm:col-span-1"><label class="block text-slate-500 text-xs mb-1">Host</label><input v-model="mailForm.host" :class="inp"/></div>
                            <div class="col-span-2 sm:col-span-1"><label class="block text-slate-500 text-xs mb-1">Port</label><input v-model="mailForm.port" type="number" :class="inp"/></div>
                            <div class="col-span-2 sm:col-span-1"><label class="block text-slate-500 text-xs mb-1">Username</label><input v-model="mailForm.username" :class="inp"/></div>
                            <div class="col-span-2 sm:col-span-1"><label class="block text-slate-500 text-xs mb-1">Password</label><input v-model="mailForm.password" type="password" placeholder="Keep blank to retain" :class="inp"/></div>
                            <div class="col-span-2 sm:col-span-1"><label class="block text-slate-500 text-xs mb-1">From Name</label><input v-model="mailForm.from_name" :class="inp"/></div>
                            <div class="col-span-2 sm:col-span-1"><label class="block text-slate-500 text-xs mb-1">From Email</label><input v-model="mailForm.from_email" type="email" :class="inp"/></div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="mailForm.processing"
                                    class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg disabled:opacity-50">
                                <Save class="w-4 h-4"/> Save
                            </button>
                        </div>
                    </form>
                    <div class="mt-5 pt-5 border-t border-slate-100">
                        <p class="text-slate-600 text-sm font-medium mb-3 flex items-center gap-2"><Send class="w-4 h-4 text-slate-400"/> Test Email</p>
                        <div class="flex gap-2">
                            <input v-model="testEmail" type="email" placeholder="you@example.com" :class="inp+' flex-1'"/>
                            <button @click="sendTestMail" :disabled="mailTesting||!testEmail"
                                    class="flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white text-sm rounded-lg disabled:opacity-50">
                                <Send class="w-4 h-4"/> {{ mailTesting?'Sending...':'Send' }}
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
                    <!-- Features -->
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
                    <!-- FAQ -->
                    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-slate-700 font-semibold text-sm">FAQ Items</h2>
                            <button type="button" @click="addFaq" class="flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs rounded-lg"><Plus class="w-3.5 h-3.5"/> Add</button>
                        </div>
                        <div class="space-y-2">
                            <div v-for="(item,i) in landingForm.faq" :key="i" class="flex gap-2 items-start p-3 bg-slate-50 rounded-lg">
                                <div class="flex-1 space-y-1.5">
                                    <input v-model="item.q" type="text" placeholder="Question" :class="inp"/>
                                    <textarea v-model="item.a" rows="2" placeholder="Answer" :class="inp+' resize-none'"></textarea>
                                </div>
                                <button type="button" @click="removeFaq(i)" class="text-red-400 hover:text-red-600 p-1 mt-1"><X class="w-4 h-4"/></button>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="landingForm.processing"
                                class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg disabled:opacity-50">
                            <Save class="w-4 h-4"/> Save Landing
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </AdminLayout>
</template>