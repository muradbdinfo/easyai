<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Package, Plus, Edit2, Trash2, CheckCircle, X, Users } from 'lucide-vue-next'

const props = defineProps({ plans: Array })

const showCreate  = ref(false)
const editingPlan = ref(null)

const createForm = useForm({
    name: '', billing_type: 'flat',
    monthly_token_limit: 500000, price: 29,
    price_per_seat: 20, min_seats: 5, max_seats: '',
    token_limit_per_seat: 500000, features: [],
})

function submitCreate() {
    createForm.post(route('admin.plans.store'), {
        onSuccess: () => { showCreate.value = false; createForm.reset() },
    })
}

function startEdit(plan) { editingPlan.value = { ...plan } }

function submitEdit() {
    router.put(route('admin.plans.update', editingPlan.value.id), editingPlan.value, {
        onSuccess: () => editingPlan.value = null, preserveScroll: true,
    })
}

function deletePlan(plan) {
    if (!confirm(`Delete plan "${plan.name}"?`)) return
    router.delete(route('admin.plans.destroy', plan.id), { preserveScroll: true })
}

function fmt(n) {
    if (!n) return '—'
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    return Math.round(n / 1000) + 'K'
}
</script>

<template>
    <AdminLayout title="Plans">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-2">
                    <Package class="w-5 h-5 text-indigo-600" />
                    <h1 class="text-lg font-bold text-slate-800">Plans</h1>
                </div>
                <button @click="showCreate = true"
                        class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm px-4 py-2 rounded-lg">
                    <Plus class="w-4 h-4" /> New Plan
                </button>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-5 py-3 text-slate-600 font-semibold">Name</th>
                            <th class="text-left px-5 py-3 text-slate-600 font-semibold">Type</th>
                            <th class="text-left px-5 py-3 text-slate-600 font-semibold">Tokens</th>
                            <th class="text-left px-5 py-3 text-slate-600 font-semibold">Price</th>
                            <th class="text-left px-5 py-3 text-slate-600 font-semibold">Seats</th>
                            <th class="text-left px-5 py-3 text-slate-600 font-semibold">Tenants</th>
                            <th class="text-left px-5 py-3 text-slate-600 font-semibold">Status</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="plan in plans" :key="plan.id" class="hover:bg-slate-50">

                            <!-- Editing row -->
                            <template v-if="editingPlan?.id === plan.id">
                                <td colspan="8" class="px-5 py-4">
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                                        <input v-model="editingPlan.name" placeholder="Name"
                                               class="border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                                        <select v-model="editingPlan.billing_type"
                                                class="border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400">
                                            <option value="flat">Flat</option>
                                            <option value="seat">Per Seat</option>
                                        </select>
                                        <template v-if="editingPlan.billing_type !== 'seat'">
                                            <input v-model="editingPlan.monthly_token_limit" type="number" placeholder="Tokens/mo"
                                                   class="border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                                            <input v-model="editingPlan.price" type="number" step="0.01" placeholder="Price $"
                                                   class="border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                                        </template>
                                        <template v-else>
                                            <input v-model="editingPlan.price_per_seat" type="number" step="0.01" placeholder="$/seat"
                                                   class="border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                                            <input v-model="editingPlan.token_limit_per_seat" type="number" placeholder="Tokens/seat"
                                                   class="border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                                        </template>
                                    </div>
                                    <div class="flex gap-2">
                                        <button @click="submitEdit"
                                                class="text-xs bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1.5 rounded-lg">Save</button>
                                        <button @click="editingPlan = null" class="text-slate-400 hover:text-slate-600">
                                            <X class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </template>

                            <!-- Normal row -->
                            <template v-else>
                                <td class="px-5 py-3 font-medium text-slate-800">{{ plan.name }}</td>
                                <td class="px-5 py-3">
                                    <span v-if="plan.billing_type === 'seat'"
                                          class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 text-xs px-2 py-0.5 rounded-full">
                                        <Users class="w-3 h-3" /> Per Seat
                                    </span>
                                    <span v-else class="text-slate-400 text-xs">Flat</span>
                                </td>
                                <td class="px-5 py-3 text-slate-600 text-xs">
                                    <span v-if="plan.billing_type === 'seat'">{{ fmt(plan.token_limit_per_seat) }}/seat</span>
                                    <span v-else>{{ fmt(plan.monthly_token_limit) }}/mo</span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">
                                    <span v-if="plan.billing_type === 'seat'">${{ Number(plan.price_per_seat).toFixed(0) }}/seat</span>
                                    <span v-else>${{ Number(plan.price).toFixed(2) }}/mo</span>
                                </td>
                                <td class="px-5 py-3 text-slate-500 text-xs">
                                    <span v-if="plan.billing_type === 'seat'">{{ plan.min_seats }}–{{ plan.max_seats ?? '∞' }}</span>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                                <td class="px-5 py-3 text-slate-500">{{ plan.tenants_count }}</td>
                                <td class="px-5 py-3">
                                    <span v-if="plan.is_active"
                                          class="inline-flex items-center gap-1 bg-green-50 text-green-700 text-xs px-2 py-0.5 rounded-full">
                                        <CheckCircle class="w-3 h-3" /> Active
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 bg-slate-100 text-slate-500 text-xs px-2 py-0.5 rounded-full">
                                        <X class="w-3 h-3" /> Inactive
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <button @click="startEdit(plan)" class="text-slate-400 hover:text-indigo-600">
                                            <Edit2 class="w-4 h-4" />
                                        </button>
                                        <button @click="deletePlan(plan)" class="text-slate-400 hover:text-red-500">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </template>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create modal -->
        <Teleport to="body">
            <div v-if="showCreate" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="font-bold text-slate-800">New Plan</h2>
                        <button @click="showCreate = false"><X class="w-5 h-5 text-slate-400" /></button>
                    </div>

                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Plan Name</label>
                            <input v-model="createForm.name"
                                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Billing Type</label>
                            <select v-model="createForm.billing_type"
                                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400">
                                <option value="flat">Flat Monthly</option>
                                <option value="seat">Per Seat</option>
                            </select>
                        </div>

                        <!-- Flat fields -->
                        <template v-if="createForm.billing_type === 'flat'">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Tokens / Month</label>
                                    <input v-model="createForm.monthly_token_limit" type="number"
                                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Price (USD)</label>
                                    <input v-model="createForm.price" type="number" step="0.01"
                                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                                </div>
                            </div>
                        </template>

                        <!-- Seat fields -->
                        <template v-else>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Price / Seat (USD)</label>
                                    <input v-model="createForm.price_per_seat" type="number" step="0.01"
                                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Tokens / Seat / Month</label>
                                    <input v-model="createForm.token_limit_per_seat" type="number"
                                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Min Seats</label>
                                    <input v-model="createForm.min_seats" type="number"
                                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Max Seats (blank = unlimited)</label>
                                    <input v-model="createForm.max_seats" type="number" placeholder="—"
                                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                                </div>
                            </div>
                        </template>

                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="showCreate = false"
                                    class="flex-1 border border-slate-200 text-slate-600 text-sm py-2 rounded-lg hover:bg-slate-50">Cancel</button>
                            <button type="submit" :disabled="createForm.processing"
                                    class="flex-1 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-sm py-2 rounded-lg">
                                Create Plan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
