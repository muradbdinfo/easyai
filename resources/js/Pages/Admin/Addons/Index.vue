<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    Package, Plus, Edit2, Trash2,
    CheckCircle, AlertCircle, Clock, X
} from 'lucide-vue-next'

const props = defineProps({
    addons:           { type: Array, default: () => [] },
    pending_payments: { type: Array, default: () => [] },
})

// ── Create modal ──────────────────────────────────────────────────
const showCreate  = ref(false)
const showEdit    = ref(false)
const editingAddon = ref(null)
const featureInput = ref('')

const blank = () => ({
    name: '', slug: '', description: '',
    price: '', currency: 'USD',
    billing_cycle: 'monthly', features: [],
    is_active: true, sort_order: 0,
})

const createForm = ref(blank())
const editForm   = ref(blank())

// ── Features helpers ──────────────────────────────────────────────
function addFeature(form) {
    if (featureInput.value.trim()) {
        form.features.push(featureInput.value.trim())
        featureInput.value = ''
    }
}
function removeFeature(form, i) { form.features.splice(i, 1) }

// ── CRUD ──────────────────────────────────────────────────────────
function submitCreate() {
    router.post(route('admin.addons.store'), createForm.value, {
        preserveScroll: true,
        onSuccess: () => { showCreate.value = false; createForm.value = blank() },
    })
}

function openEdit(addon) {
    editingAddon.value = addon
    editForm.value = {
        name:          addon.name,
        slug:          addon.slug,
        description:   addon.description ?? '',
        price:         addon.price,
        currency:      addon.currency,
        billing_cycle: addon.billing_cycle,
        features:      [...(addon.features ?? [])],
        is_active:     addon.is_active,
        sort_order:    addon.sort_order,
    }
    showEdit.value = true
}

function submitEdit() {
    router.put(route('admin.addons.update', editingAddon.value.id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => { showEdit.value = false; editingAddon.value = null },
    })
}

function deleteAddon(addon) {
    if (!confirm(`Delete "${addon.name}"?`)) return
    router.delete(route('admin.addons.destroy', addon.id), { preserveScroll: true })
}

function approve(paymentId) {
    if (!confirm('Approve this add-on purchase?')) return
    router.put(route('admin.addons.approve', paymentId), {}, { preserveScroll: true })
}
</script>

<template>
    <AdminLayout title="Add-ons">

        <div class="space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Package class="w-5 h-5 text-slate-500" />
                    <h1 class="text-xl font-bold text-slate-800">Add-ons</h1>
                </div>
                <button @click="showCreate = true"
                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    <Plus class="w-4 h-4" /> New Add-on
                </button>
            </div>

            <!-- Flash messages -->
            <div v-if="$page.props.flash?.success"
                class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
                <CheckCircle class="w-4 h-4 flex-shrink-0" /> {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.errors?.error"
                class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
                <AlertCircle class="w-4 h-4 flex-shrink-0" /> {{ $page.props.errors.error }}
            </div>

            <!-- Add-ons table -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                    <Package class="w-4 h-4 text-slate-400" />
                    <span class="font-medium text-slate-700 text-sm">All Add-ons ({{ addons.length }})</span>
                </div>

                <!-- Empty state -->
                <div v-if="!addons.length" class="py-12 text-center text-slate-400">
                    <Package class="w-10 h-10 mx-auto mb-2 opacity-30" />
                    <p class="text-sm">No add-ons yet. Create one above.</p>
                </div>

                <table v-else class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="px-5 py-3 text-left">Add-on</th>
                            <th class="px-5 py-3 text-left">Price</th>
                            <th class="px-5 py-3 text-left">Active Subs</th>
                            <th class="px-5 py-3 text-left">Total Sales</th>
                            <th class="px-5 py-3 text-left">Status</th>
                            <th class="px-5 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="addon in addons" :key="addon.id" class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ addon.name }}</p>
                                <p class="text-xs text-slate-400 font-mono mt-0.5">{{ addon.slug }}</p>
                            </td>
                            <td class="px-5 py-3 text-slate-700">
                                {{ addon.currency }} {{ addon.price }}
                                <span class="text-slate-400 text-xs">/{{ addon.billing_cycle }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="font-semibold text-indigo-600">{{ addon.active_count ?? 0 }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ addon.total_purchases ?? 0 }}</td>
                            <td class="px-5 py-3">
                                <span :class="addon.is_active
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-slate-100 text-slate-500'"
                                    class="px-2 py-0.5 rounded-full text-xs font-medium">
                                    {{ addon.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <button @click="openEdit(addon)"
                                        class="text-slate-400 hover:text-indigo-600 transition-colors">
                                        <Edit2 class="w-4 h-4" />
                                    </button>
                                    <button @click="deleteAddon(addon)"
                                        class="text-slate-400 hover:text-red-500 transition-colors">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pending approvals -->
            <div v-if="pending_payments.length"
                class="bg-white rounded-xl border border-amber-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-amber-100 bg-amber-50 flex items-center gap-2">
                    <Clock class="w-4 h-4 text-amber-500" />
                    <span class="font-medium text-amber-700 text-sm">
                        Pending Add-on Purchases ({{ pending_payments.length }})
                    </span>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="px-5 py-3 text-left">Tenant</th>
                            <th class="px-5 py-3 text-left">Add-on</th>
                            <th class="px-5 py-3 text-left">Amount</th>
                            <th class="px-5 py-3 text-left">Method</th>
                            <th class="px-5 py-3 text-left">Date</th>
                            <th class="px-5 py-3 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in pending_payments" :key="p.id" class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ p.tenant?.name }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ p.addon?.name }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ p.currency }} {{ p.amount }}</td>
                            <td class="px-5 py-3">
                                <span class="uppercase text-xs font-medium text-slate-500">{{ p.method }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-400 text-xs">
                                {{ new Date(p.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-5 py-3">
                                <button @click="approve(p.id)"
                                    class="flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                                    <CheckCircle class="w-3.5 h-3.5" /> Approve
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- Create Modal -->
        <div v-if="showCreate" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl w-full max-w-lg p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="font-semibold text-slate-800 flex items-center gap-2">
                        <Plus class="w-4 h-4" /> New Add-on
                    </h2>
                    <button @click="showCreate = false">
                        <X class="w-5 h-5 text-slate-400 hover:text-slate-600" />
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-slate-600 block mb-1">Name</label>
                            <input v-model="createForm.name" type="text" placeholder="Agent AI"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-600 block mb-1">Slug</label>
                            <input v-model="createForm.slug" type="text" placeholder="agent-ai"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-slate-600 block mb-1">Description</label>
                        <textarea v-model="createForm.description" rows="2"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400 resize-none" />
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs font-medium text-slate-600 block mb-1">Price</label>
                            <input v-model="createForm.price" type="number" step="0.01" min="0"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-600 block mb-1">Currency</label>
                            <select v-model="createForm.currency"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400">
                                <option>USD</option>
                                <option>BDT</option>
                                <option>EUR</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-600 block mb-1">Billing</label>
                            <select v-model="createForm.billing_cycle"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="one_time">One Time</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-slate-600 block mb-1">Features</label>
                        <div class="flex gap-2 mb-2">
                            <input v-model="featureInput" type="text" placeholder="Add feature, press Enter"
                                @keydown.enter.prevent="addFeature(createForm)"
                                class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                            <button type="button" @click="addFeature(createForm)"
                                class="px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg text-sm font-medium">+</button>
                        </div>
                        <div v-if="createForm.features.length" class="flex flex-wrap gap-1">
                            <span v-for="(f, i) in createForm.features" :key="i"
                                class="flex items-center gap-1 bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded text-xs">
                                {{ f }}
                                <button @click="removeFeature(createForm, i)" class="hover:text-indigo-900">×</button>
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="showCreate = false"
                            class="px-4 py-2 text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                        <button @click="submitCreate"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg font-medium">
                            Create Add-on
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEdit" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl w-full max-w-lg p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="font-semibold text-slate-800 flex items-center gap-2">
                        <Edit2 class="w-4 h-4" /> Edit Add-on
                    </h2>
                    <button @click="showEdit = false">
                        <X class="w-5 h-5 text-slate-400 hover:text-slate-600" />
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-medium text-slate-600 block mb-1">Name</label>
                        <input v-model="editForm.name" type="text"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                    </div>

                    <div>
                        <label class="text-xs font-medium text-slate-600 block mb-1">Description</label>
                        <textarea v-model="editForm.description" rows="2"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400 resize-none" />
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs font-medium text-slate-600 block mb-1">Price</label>
                            <input v-model="editForm.price" type="number" step="0.01" min="0"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-600 block mb-1">Currency</label>
                            <select v-model="editForm.currency"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400">
                                <option>USD</option>
                                <option>BDT</option>
                                <option>EUR</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-600 block mb-1">Billing</label>
                            <select v-model="editForm.billing_cycle"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="one_time">One Time</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-slate-600 block mb-1">Features</label>
                        <div class="flex gap-2 mb-2">
                            <input v-model="featureInput" type="text" placeholder="Add feature, press Enter"
                                @keydown.enter.prevent="addFeature(editForm)"
                                class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                            <button type="button" @click="addFeature(editForm)"
                                class="px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg text-sm font-medium">+</button>
                        </div>
                        <div v-if="editForm.features.length" class="flex flex-wrap gap-1">
                            <span v-for="(f, i) in editForm.features" :key="i"
                                class="flex items-center gap-1 bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded text-xs">
                                {{ f }}
                                <button @click="removeFeature(editForm, i)" class="hover:text-indigo-900">×</button>
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" v-model="editForm.is_active" class="rounded" />
                            <span class="text-slate-700">Active (visible to tenants)</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="showEdit = false"
                            class="px-4 py-2 text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                        <button @click="submitEdit"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg font-medium">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>