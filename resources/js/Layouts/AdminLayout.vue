<script setup>
// FILE: resources/js/Layouts/AdminLayout.vue
import { ref, computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import { LayoutDashboard, Users, CreditCard, BarChart2, Settings, Shield, LogOut, Menu, X, Activity, Package, PuzzleIcon } from 'lucide-vue-next'
import AdminNotificationBell from '@/Components/AdminNotificationBell.vue'

defineProps({ title: String })

const page        = usePage()
const auth        = computed(() => page.props.auth)
const brand       = computed(() => page.props.theme?.brand ?? '#6366f1')
const sidebarOpen = ref(false)

const navItems = [
    { label:'Dashboard', icon:LayoutDashboard, name:'admin.dashboard'      },
    { label:'Tenants',   icon:Users,           name:'admin.tenants.index'  },
    { label:'Plans',     icon:Package,         name:'admin.plans.index'    },
    { label:'Add-ons',   icon:PuzzleIcon,      name:'admin.addons.index'   },
    { label:'Payments',  icon:CreditCard,      name:'admin.payments.index' },
    { label:'Usage',     icon:BarChart2,       name:'admin.usage.index'    },
    { label:'Settings',  icon:Settings,        name:'admin.settings.index' },
    { label:'Users',     icon:Users,           name:'admin.users.index'    },
]

function active(name) {
    try { return page.url.startsWith(new URL(route(name)).pathname) } catch { return false }
}
function logout() { router.post(route('admin.logout')) }
</script>

<template>
    <div class="min-h-screen flex bg-slate-50" :style="{ '--brand': brand }">

        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-40 w-56 bg-slate-800 flex flex-col transition-transform duration-200 md:translate-x-0 md:static md:flex shrink-0">

            <div class="flex items-center gap-3 px-4 py-4 border-b border-slate-700 shrink-0">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:var(--brand)">
                    <Shield class="w-4 h-4 text-white"/>
                </div>
                <div>
                    <p class="text-white text-sm font-bold">EasyAI</p>
                    <p class="text-slate-400 text-xs">Admin Panel</p>
                </div>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                <Link v-for="item in navItems" :key="item.name"
                      :href="route(item.name)"
                      class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                      :style="active(item.name) ? 'background:var(--brand);color:#fff' : ''"
                      :class="!active(item.name) ? 'text-slate-400 hover:text-white hover:bg-slate-700' : ''"
                      @click="sidebarOpen=false">
                    <component :is="item.icon" class="w-4 h-4 shrink-0"/> {{ item.label }}
                </Link>
            </nav>

            <div class="px-3 py-4 border-t border-slate-700 shrink-0">
                <div class="flex items-center gap-3 px-3 py-2 mb-2">
                    <div class="w-7 h-7 bg-slate-600 rounded-full flex items-center justify-center shrink-0">
                        <Shield class="w-4 h-4 text-slate-300"/>
                    </div>
                    <div class="min-w-0">
                        <p class="text-white text-xs font-medium truncate">{{ auth?.user?.name }}</p>
                        <p class="text-slate-400 text-xs">Super Admin</p>
                    </div>
                </div>
                <button @click="logout"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-400/10 transition-colors text-sm font-medium">
                    <LogOut class="w-4 h-4 shrink-0"/> Log Out
                </button>
            </div>
        </aside>

        <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-black/50 md:hidden" @click="sidebarOpen=false"/>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="flex items-center justify-between px-6 py-4 bg-white border-b border-slate-200 shrink-0">
                <div class="flex items-center gap-3">
                    <button class="md:hidden text-slate-500" @click="sidebarOpen=!sidebarOpen">
                        <Menu v-if="!sidebarOpen" class="w-5 h-5"/><X v-else class="w-5 h-5"/>
                    </button>
                    <div class="flex items-center gap-2 text-sm">
                        <Shield class="w-4 h-4" :style="{color:brand}"/>
                        <span class="font-semibold text-slate-700">{{ title ?? 'Admin Panel' }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <!-- ✅ Real notification bell (replaces static Bell icon) -->
                    <AdminNotificationBell />

                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <Activity class="w-4 h-4 text-green-500"/><span>System OK</span>
                    </div>
                </div>
            </header>
            <main class="flex-1 overflow-auto p-6"><slot/></main>
        </div>
    </div>
</template>