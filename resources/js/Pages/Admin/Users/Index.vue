<script setup>
// FILE: resources/js/Pages/Admin/Users/Index.vue
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    Users, Search, Filter, Trash2, CheckCircle, AlertCircle,
    Shield, User, RefreshCw, ChevronLeft, ChevronRight, X, Key
} from 'lucide-vue-next'

const props = defineProps({
    users:   Object,
    tenants: Array,
    filters: Object,
    stats:   Object,
})

// ── Filters ────────────────────────────────────────────────────────────
const search    = ref(props.filters?.search    ?? '')
const role      = ref(props.filters?.role      ?? '')
const tenantId  = ref(props.filters?.tenant_id ?? '')
const status    = ref(props.filters?.status    ?? '')

function applyFilters() {
    router.get(route('admin.users.index'), {
        search:    search.value    || undefined,
        role:      role.value      || undefined,
        tenant_id: tenantId.value  || undefined,
        status:    status.value    || undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value   = ''
    role.value     = ''
    tenantId.value = ''
    status.value   = ''
    applyFilters()
}

const hasFilters = computed(() =>
    search.value || role.value || tenantId.value || status.value
)

// ── Actions ────────────────────────────────────────────────────────────
const editUser = ref(null)
const newRole  = ref('')

function openRole(user) {
    editUser.value = user
    newRole.value  = user.role
}

function saveRole() {
    router.put(route('admin.users.role', editUser.value.id), { role: newRole.value }, {
        preserveScroll: true,
        onSuccess: () => { editUser.value = null },
    })
}

function toggleStatus(user) {
    const action = user.is_active ? 'Deactivate' : 'Activate'
    if (!confirm(`${action} ${user.name}?`)) return
    router.put(route('admin.users.status', user.id), {}, { preserveScroll: true })
}

function deleteUser(user) {
    if (!confirm(`Permanently delete ${user.name}? This cannot be undone.`)) return
    router.delete(route('admin.users.destroy', user.id), { preserveScroll: true })
}

// ── Helpers ────────────────────────────────────────────────────────────
const roleBadge = {
    admin:  'bg-indigo-100 text-indigo-700 border border-indigo-200',
    member: 'bg-slate-100 text-slate-600 border border-slate-200',
}
</script>

<template>
    <AdminLayout title="Users">
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <Users class="w-5 h-5 text-indigo-600" /> User Management
                    </h1>
                    <p class="text-slate-500 text-sm mt-0.5">All workspace members across tenants</p>
                </div>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div v-for="card in [
                    { label: 'Total Users',   value: stats.total,    color: 'text-indigo-600', bg: 'bg-indigo-50'  },
                    { label: 'Admins',        value: stats.admins,   color: 'text-amber-600',  bg: 'bg-amber-50'   },
                    { label: 'Members',       value: stats.members,  color: 'text-blue-600',   bg: 'bg-blue-50'    },
                    { label: 'Inactive',      value: stats.inactive, color: 'text-red-600',    bg: 'bg-red-50'     },
                ]" :key="card.label"
                     class="bg-white rounded-xl border border-slate-200 p-4">
                    <p class="text-slate-500 text-xs font-medium">{{ card.label }}</p>
                    <p class="text-2xl font-bold mt-1" :class="card.color">{{ card.value }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="flex flex-wrap gap-3 items-end">
                    <!-- Search -->
                    <div class="relative flex-1 min-w-48">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input v-model="search" @keydown.enter="applyFilters"
                               placeholder="Name or email…"
                               class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                    </div>

                    <!-- Role -->
                    <select v-model="role"
                            class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                        <option value="">All roles</option>
                        <option value="admin">Admin</option>
                        <option value="member">Member</option>
                    </select>

                    <!-- Tenant -->
                    <select v-model="tenantId"
                            class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white max-w-40">
                        <option value="">All tenants</option>
                        <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>

                    <!-- Status -->
                    <select v-model="status"
                            class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                        <option value="">All status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <button @click="applyFilters"
                            class="flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-500 transition-colors">
                        <Filter class="w-3.5 h-3.5" /> Filter
                    </button>

                    <button v-if="hasFilters" @click="clearFilters"
                            class="flex items-center gap-1 px-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-500 hover:text-slate-700 hover:bg-slate-50 transition-colors">
                        <X class="w-3.5 h-3.5" /> Clear
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">User</th>
                            <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Tenant</th>
                            <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Role</th>
                            <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Status</th>
                            <th class="text-left text-slate-500 font-medium px-5 py-3 text-xs">Joined</th>
                            <th class="text-right text-slate-500 font-medium px-5 py-3 text-xs">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="!users.data.length">
                            <td colspan="6" class="px-5 py-12 text-center">
                                <Users class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                                <p class="text-slate-400 text-sm">No users found</p>
                            </td>
                        </tr>
                        <tr v-for="user in users.data" :key="user.id"
                            class="hover:bg-slate-50 transition-colors">

                            <!-- User -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0"
                                         :class="user.role === 'admin' ? 'bg-indigo-500' : 'bg-slate-400'">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-800">{{ user.name }}</p>
                                        <p class="text-slate-400 text-xs">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Tenant -->
                            <td class="px-5 py-3.5">
                                <span v-if="user.tenant" class="text-slate-700 text-sm">{{ user.tenant.name }}</span>
                                <span v-else class="text-slate-400 text-xs italic">No tenant</span>
                            </td>

                            <!-- Role -->
                            <td class="px-5 py-3.5">
                                <span class="px-2 py-0.5 text-xs rounded-full font-medium capitalize"
                                      :class="roleBadge[user.role] ?? 'bg-slate-100 text-slate-600'">
                                    {{ user.role }}
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="px-5 py-3.5">
                                <span class="flex items-center gap-1.5 text-xs font-medium"
                                      :class="user.is_active ? 'text-green-600' : 'text-red-500'">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                          :class="user.is_active ? 'bg-green-500' : 'bg-red-500'" />
                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <!-- Joined -->
                            <td class="px-5 py-3.5 text-slate-400 text-xs">
                                {{ new Date(user.created_at).toLocaleDateString() }}
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- Change role -->
                                    <button @click="openRole(user)"
                                            class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Change role">
                                        <Key class="w-3.5 h-3.5" />
                                    </button>

                                    <!-- Toggle status -->
                                    <button @click="toggleStatus(user)"
                                            class="p-1.5 rounded-lg transition-colors"
                                            :class="user.is_active
                                                ? 'text-slate-400 hover:text-amber-600 hover:bg-amber-50'
                                                : 'text-slate-400 hover:text-green-600 hover:bg-green-50'"
                                            :title="user.is_active ? 'Deactivate' : 'Activate'">
                                        <CheckCircle v-if="!user.is_active" class="w-3.5 h-3.5" />
                                        <AlertCircle v-else class="w-3.5 h-3.5" />
                                    </button>

                                    <!-- Delete -->
                                    <button @click="deleteUser(user)"
                                            class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete user">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="users.last_page > 1"
                     class="flex items-center justify-between px-5 py-3 border-t border-slate-100 bg-slate-50">
                    <p class="text-xs text-slate-500">
                        Showing {{ users.from }}–{{ users.to }} of {{ users.total }}
                    </p>
                    <div class="flex gap-1">
                        <Link v-if="users.prev_page_url" :href="users.prev_page_url"
                              class="p-1.5 border border-slate-200 rounded-lg text-slate-500 hover:bg-white transition-colors">
                            <ChevronLeft class="w-4 h-4" />
                        </Link>
                        <span v-else class="p-1.5 border border-slate-100 rounded-lg text-slate-300 cursor-not-allowed">
                            <ChevronLeft class="w-4 h-4" />
                        </span>

                        <span class="px-3 py-1.5 text-xs text-slate-600 font-medium">
                            {{ users.current_page }} / {{ users.last_page }}
                        </span>

                        <Link v-if="users.next_page_url" :href="users.next_page_url"
                              class="p-1.5 border border-slate-200 rounded-lg text-slate-500 hover:bg-white transition-colors">
                            <ChevronRight class="w-4 h-4" />
                        </Link>
                        <span v-else class="p-1.5 border border-slate-100 rounded-lg text-slate-300 cursor-not-allowed">
                            <ChevronRight class="w-4 h-4" />
                        </span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Change Role Modal -->
        <Teleport to="body">
            <div v-if="editUser"
                 class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
                 @click.self="editUser = null">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm border border-slate-200">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                        <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                            <Key class="w-4 h-4 text-indigo-600" /> Change Role
                        </h3>
                        <button @click="editUser = null" class="text-slate-400 hover:text-slate-600">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="px-6 py-5 space-y-4">
                        <p class="text-sm text-slate-600">
                            Changing role for <span class="font-medium text-slate-800">{{ editUser.name }}</span>
                        </p>
                        <div class="grid grid-cols-2 gap-3">
                            <button v-for="r in ['admin', 'member']" :key="r"
                                    @click="newRole = r"
                                    class="p-3 rounded-xl border-2 text-sm font-medium capitalize transition-colors"
                                    :class="newRole === r
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                        : 'border-slate-200 text-slate-600 hover:border-slate-300'">
                                <Shield v-if="r === 'admin'" class="w-4 h-4 mx-auto mb-1 text-indigo-500" />
                                <User v-else class="w-4 h-4 mx-auto mb-1 text-slate-400" />
                                {{ r }}
                            </button>
                        </div>
                        <button @click="saveRole"
                                class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-medium transition-colors">
                            Save Role
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AdminLayout>
</template>