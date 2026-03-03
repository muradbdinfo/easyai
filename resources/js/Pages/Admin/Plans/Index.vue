<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    Package, Plus, Edit2, Trash2, CheckCircle, X
} from 'lucide-vue-next'

const props = defineProps({
    plans: Array,
})

const showCreate = ref(false)
const editingPlan = ref(null)

const createForm = useForm({
    name:                '',
    monthly_token_limit: 500000,
    price:               29,
    features:            [],
})

function submitCreate() {
    createForm.post(route('admin.plans.store'), {
        onSuccess: () => {
            showCreate.value = false
            createForm.reset()
        },
    })
}

function startEdit(plan) {
    editingPlan.value = { ...plan }
}

function submitEdit() {
    router.put(route('admin.plans.update', editingPlan.value.id), {
        name:                editingPlan.value.name,
        monthly_token_limit: editingPlan.value.monthly_token_limit,
        price:               editingPlan.value.price,
        is_active:           editingPlan.value.is_active,
    }, {
        onSuccess: () => editingPlan.value = null,
        preserveScroll: true,
    })
}

function deletePlan(plan) {
    if (!confirm(`Delete plan "${plan.name}"? This cannot be undone.`)) return
    router.delete(route('admin.plans.destroy', plan.id))
}

function fmt(n) {
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
    return String(n)
}
</script>

<template>
    <AdminLayout title="Plans">

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-2">
                <Package class="w-5 h-5 text-slate-500" />
                <h1 class="text-xl font-bold text-slate-800">Plans</h1>
            </div>
            <button
                @click="showCreate = true"
                class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm px-4 py-2 rounded-lg transition-colors"
            >
                <Plus class="w-4 h-4" />
                New Plan
            </button>
        </div>

        <!-- Plans table -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50">
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Name</th>
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Tokens/Month</th>
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Price</th>
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Tenants</th>
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Status</th>
                        <th class="text-right text-slate-500 font-medium px-5 py-3 text-xs">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="plan in plans" :key="plan.id" class="hover:bg-slate-50">

                        <!-- Edit inline row -->
                        <template v-if="editingPlan?.id === plan.id">
                            <td class="px-5 py-2">
                                <input v-model="editingPlan.name"
                                    class="border border-slate-300 rounded px-2 py-1 text-sm w-full" />
                            </td>
                            <td class="px-5 py-2">
                                <input v-model="editingPlan.monthly_token_limit" type="number"
                                    class="border border-slate-300 rounded px-2 py-1 text-sm w-28" />
                            </td>
                            <td class="px-5 py-2">
                                <input v-model="editingPlan.price" type="number" step="0.01"
                                    class="border border-slate-300 rounded px-2 py-1 text-sm w-20" />
                            </td>
                            <td class="px-5 py-2 text-slate-500">{{ plan.tenants_count }}</td>
                            <td class="px-5 py-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="editingPlan.is_active" class="rounded" />
                                    <span class="text-xs text-slate-600">Active</span>
                                </label>
                            </td>
                            <td class="px-5 py-2 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="submitEdit"
                                        class="text-xs bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1 rounded-lg">
                                        Save
                                    </button>
                                    <button @click="editingPlan = null"
                                        class="text-slate-400 hover:text-slate-600">
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </template>

                        <!-- Normal row -->
                        <template v-else>
                            <td class="px-5 py-3 font-medium text-slate-800">{{ plan.name }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ fmt(plan.monthly_token_limit) }}</td>
                            <td class="px-5 py-3 text-slate-600">${{ Number(plan.price).toFixed(2) }}/mo</td>
                            <td class="px-5 py-3 text-slate-500">{{ plan.tenants_count }}</td>
                            <td class="px-5 py-3">
                                <span v-if="plan.is_active"
                                    class="inline-flex items-center gap-1 bg-green-50 text-green-700 text-xs px-2 py-0.5 rounded-full">
                                    <CheckCircle class="w-3 h-3" /> Active
                                </span>
                                <span v-else
                                    class="inline-flex items-center gap-1 bg-slate-100 text-slate-500 text-xs px-2 py-0.5 rounded-full">
                                    <X class="w-3 h-3" /> Inactive
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <button @click="startEdit(plan)"
                                        class="text-indigo-600 hover:text-indigo-500">
                                        <Edit2 class="w-4 h-4" />
                                    </button>
                                    <button @click="deletePlan(plan)"
                                        class="text-slate-400 hover:text-red-500">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </template>

                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Create modal -->
        <Teleport to="body">
            <div v-if="showCreate"
                class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4"
                @click.self="showCreate = false">
                <div class="bg-white rounded-2xl w-full max-w-md p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-slate-800 font-semibold">New Plan</h2>
                        <button @click="showCreate = false" class="text-slate-400 hover:text-slate-600">
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Name</label>
                            <input v-model="createForm.name" type="text" placeholder="e.g. Pro"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        </div>
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
                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="showCreate = false"
                                class="flex-1 border border-slate-200 text-slate-600 text-sm py-2 rounded-lg hover:bg-slate-50">
                                Cancel
                            </button>
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