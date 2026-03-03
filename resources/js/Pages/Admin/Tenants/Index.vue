<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    Users, Search, Eye, Edit2,
    CheckCircle, AlertCircle, Clock, ChevronRight, Filter
} from 'lucide-vue-next'

const props = defineProps({
    tenants: Object,
    filters: Object,
})

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')

function applyFilters() {
    router.get(route('admin.tenants.index'), {
        search: search.value,
        status: status.value,
    }, { preserveState: true, replace: true })
}

function statusBg(s) {
    return s === 'active'    ? 'bg-green-50 text-green-700'
         : s === 'suspended' ? 'bg-red-50 text-red-700'
         : 'bg-amber-50 text-amber-700'
}

function statusIcon(s) {
    return s === 'active' ? CheckCircle : s === 'suspended' ? AlertCircle : Clock
}

function usagePercent(t) {
    if (!t.token_quota || t.token_quota === 0) return 0
    return Math.min(100, Math.round((t.tokens_used / t.token_quota) * 100))
}
</script>

<template>
    <AdminLayout title="Tenants">

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-2">
                <Users class="w-5 h-5 text-slate-500" />
                <h1 class="text-xl font-bold text-slate-800">Tenants</h1>
                <span class="bg-slate-100 text-slate-600 text-xs px-2 py-0.5 rounded-full font-medium">
                    {{ tenants.total }}
                </span>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl border border-slate-200 p-4 mb-5 flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 flex-1 min-w-48">
                <Search class="w-4 h-4 text-slate-400 shrink-0" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by name..."
                    class="flex-1 text-sm text-slate-700 outline-none placeholder-slate-400"
                    @keyup.enter="applyFilters"
                />
            </div>
            <div class="flex items-center gap-2">
                <Filter class="w-4 h-4 text-slate-400" />
                <select
                    v-model="status"
                    class="text-sm text-slate-700 border border-slate-200 rounded-lg px-3 py-1.5 outline-none focus:border-indigo-400"
                    @change="applyFilters"
                >
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="trial">Trial</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
            <button
                @click="applyFilters"
                class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm px-4 py-1.5 rounded-lg transition-colors"
            >
                Search
            </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div v-if="tenants.data.length === 0" class="text-center py-12 text-slate-400">
                No tenants found.
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50">
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Tenant</th>
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Plan</th>
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Usage</th>
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Status</th>
                        <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Users</th>
                        <th class="text-right text-slate-500 font-medium px-5 py-3 text-xs">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="tenant in tenants.data" :key="tenant.id" class="hover:bg-slate-50">
                        <td class="px-5 py-3">
                            <p class="font-medium text-slate-800">{{ tenant.name }}</p>
                            <p class="text-slate-400 text-xs">{{ tenant.slug }}</p>
                        </td>
                        <td class="px-5 py-3 text-slate-600 text-xs">
                            {{ tenant.plan?.name ?? '—' }}
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-20 bg-slate-100 rounded-full h-1.5">
                                    <div
                                        class="h-1.5 rounded-full transition-all"
                                        :class="usagePercent(tenant) >= 90 ? 'bg-red-500' : usagePercent(tenant) >= 70 ? 'bg-amber-500' : 'bg-indigo-500'"
                                        :style="{ width: usagePercent(tenant) + '%' }"
                                    />
                                </div>
                                <span class="text-xs text-slate-400">{{ usagePercent(tenant) }}%</span>
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            <span
                                class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full capitalize"
                                :class="statusBg(tenant.status)"
                            >
                                <component :is="statusIcon(tenant.status)" class="w-3 h-3" />
                                {{ tenant.status }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-500 text-xs">
                            {{ tenant.users_count }}
                        </td>
                        <td class="px-5 py-3 text-right">
                            <Link
                                :href="route('admin.tenants.show', tenant.id)"
                                class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-500 text-xs font-medium"
                            >
                                <Eye class="w-3.5 h-3.5" />
                                View
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="tenants.last_page > 1" class="flex items-center justify-between px-5 py-3 border-t border-slate-100">
                <span class="text-xs text-slate-400">
                    Showing {{ tenants.from }}–{{ tenants.to }} of {{ tenants.total }}
                </span>
                <div class="flex gap-1">
                    <Link
                        v-if="tenants.prev_page_url"
                        :href="tenants.prev_page_url"
                        class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50"
                    >
                        Prev
                    </Link>
                    <Link
                        v-if="tenants.next_page_url"
                        :href="tenants.next_page_url"
                        class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50"
                    >
                        Next
                    </Link>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>