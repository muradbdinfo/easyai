<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Package, CheckCircle, AlertCircle, Zap,
    CreditCard, ArrowRight, X, Clock
} from 'lucide-vue-next'

const props = defineProps({
    addons:          { type: Array, default: () => [] },
    addon_required:  { type: String, default: null },
})

const selectedAddon  = ref(null)
const selectedMethod = ref('cod')
const purchasing     = ref(false)

function openPurchase(addon) {
    if (addon.purchased) return
    selectedAddon.value  = addon
    selectedMethod.value = 'cod'
}

function confirmPurchase() {
    if (!selectedAddon.value) return
    purchasing.value = true
    router.post(route('addons.purchase', selectedAddon.value.id), {
        method: selectedMethod.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            purchasing.value    = false
            selectedAddon.value = null
        },
    })
}

function cancelAddon(addon) {
    if (!confirm(`Cancel "${addon.name}" add-on? You will lose access immediately.`)) return
    router.delete(route('addons.cancel', addon.id), { preserveScroll: true })
}
</script>

<template>
    <AppLayout title="Add-ons">
        <div class="max-w-4xl mx-auto py-8 px-4 space-y-6">

            <!-- Header -->
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <Package class="w-5 h-5 text-indigo-400" />
                    <h1 class="text-xl font-bold text-white">Add-ons</h1>
                </div>
                <p class="text-slate-400 text-sm">Extend your workspace with powerful add-on modules.</p>
            </div>

            <!-- Required notice -->
            <div v-if="addon_required"
                class="bg-amber-900/30 border border-amber-700 text-amber-300 px-4 py-3 rounded-xl flex items-center gap-2 text-sm">
                <AlertCircle class="w-4 h-4 flex-shrink-0" />
                You need to purchase the <strong class="mx-1">{{ addon_required }}</strong> add-on to access this feature.
            </div>

            <!-- Flash -->
            <div v-if="$page.props.flash?.success"
                class="bg-green-900/30 border border-green-700 text-green-300 px-4 py-3 rounded-xl flex items-center gap-2 text-sm">
                <CheckCircle class="w-4 h-4 flex-shrink-0" /> {{ $page.props.flash.success }}
            </div>

            <!-- Empty state -->
            <div v-if="!addons.length" class="text-center py-16 text-slate-500">
                <Package class="w-12 h-12 mx-auto mb-3 opacity-30" />
                <p>No add-ons available yet.</p>
            </div>

            <!-- Add-on cards -->
            <div class="grid gap-5 sm:grid-cols-2">
                <div v-for="addon in addons" :key="addon.id"
                    class="bg-slate-800 border rounded-xl p-6 flex flex-col gap-4"
                    :class="addon.purchased ? 'border-indigo-600' : 'border-slate-700'">

                    <!-- Top -->
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-indigo-600/20 flex items-center justify-center flex-shrink-0">
                                <Zap class="w-5 h-5 text-indigo-400" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-white">{{ addon.name }}</h3>
                                <p class="text-xs text-slate-400 font-mono">{{ addon.slug }}</p>
                            </div>
                        </div>

                        <!-- Status badge -->
                        <span v-if="addon.purchased"
                            class="flex items-center gap-1 bg-indigo-600/20 text-indigo-300 text-xs font-medium px-2.5 py-1 rounded-full">
                            <CheckCircle class="w-3 h-3" /> Active
                        </span>
                        <span v-else-if="addon.status === 'pending'"
                            class="flex items-center gap-1 bg-amber-600/20 text-amber-300 text-xs font-medium px-2.5 py-1 rounded-full">
                            <Clock class="w-3 h-3" /> Pending
                        </span>
                    </div>

                    <!-- Description -->
                    <p class="text-slate-400 text-sm leading-relaxed">{{ addon.description }}</p>

                    <!-- Features -->
                    <ul v-if="addon.features?.length" class="space-y-1.5">
                        <li v-for="(f, i) in addon.features" :key="i"
                            class="flex items-center gap-2 text-sm text-slate-300">
                            <CheckCircle class="w-3.5 h-3.5 text-indigo-400 flex-shrink-0" />
                            {{ f }}
                        </li>
                    </ul>

                    <!-- Footer -->
                    <div class="flex items-center justify-between pt-2 border-t border-slate-700 mt-auto">
                        <div>
                            <span class="text-2xl font-bold text-white">{{ addon.currency }} {{ addon.price }}</span>
                            <span class="text-slate-400 text-sm">/{{ addon.billing_cycle }}</span>
                        </div>

                        <div class="flex gap-2">
                            <!-- Cancel button (active) -->
                            <button v-if="addon.purchased" @click="cancelAddon(addon)"
                                class="text-xs text-slate-500 hover:text-red-400 transition-colors px-2 py-1">
                                Cancel
                            </button>

                            <!-- Purchase button -->
                            <button v-if="!addon.purchased && addon.status !== 'pending'"
                                @click="openPurchase(addon)"
                                class="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                Purchase <ArrowRight class="w-3.5 h-3.5" />
                            </button>

                            <!-- Pending state -->
                            <span v-if="addon.status === 'pending'"
                                class="text-xs text-amber-400 px-2 py-1">
                                Awaiting approval
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Purchase modal -->
        <div v-if="selectedAddon" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4">
            <div class="bg-slate-800 border border-slate-700 rounded-xl w-full max-w-md p-6 space-y-5">

                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-white text-lg">Purchase Add-on</h2>
                    <button @click="selectedAddon = null">
                        <X class="w-5 h-5 text-slate-400 hover:text-white" />
                    </button>
                </div>

                <!-- Summary -->
                <div class="bg-slate-700/50 rounded-lg p-4">
                    <p class="text-slate-300 font-medium">{{ selectedAddon.name }}</p>
                    <p class="text-2xl font-bold text-white mt-1">
                        {{ selectedAddon.currency }} {{ selectedAddon.price }}
                        <span class="text-sm font-normal text-slate-400">/{{ selectedAddon.billing_cycle }}</span>
                    </p>
                </div>

                <!-- Payment method -->
                <div>
                    <p class="text-xs font-medium text-slate-400 mb-3">Select payment method</p>
                    <div class="space-y-2">

                        <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
                            :class="selectedMethod === 'cod' ? 'border-indigo-500 bg-indigo-600/10' : 'border-slate-600 hover:border-slate-500'">
                            <input type="radio" v-model="selectedMethod" value="cod" class="accent-indigo-500" />
                            <div>
                                <p class="text-white text-sm font-medium">Manual / COD</p>
                                <p class="text-slate-400 text-xs">Pay manually — admin will approve</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
                            :class="selectedMethod === 'sslcommerz' ? 'border-indigo-500 bg-indigo-600/10' : 'border-slate-600 hover:border-slate-500'">
                            <input type="radio" v-model="selectedMethod" value="sslcommerz" class="accent-indigo-500" />
                            <div>
                                <p class="text-white text-sm font-medium">SSLCommerz</p>
                                <p class="text-slate-400 text-xs">bKash / Nagad / Card (Bangladesh)</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
                            :class="selectedMethod === 'stripe' ? 'border-indigo-500 bg-indigo-600/10' : 'border-slate-600 hover:border-slate-500'">
                            <input type="radio" v-model="selectedMethod" value="stripe" class="accent-indigo-500" />
                            <div>
                                <p class="text-white text-sm font-medium">Stripe</p>
                                <p class="text-slate-400 text-xs">International card</p>
                            </div>
                        </label>

                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button @click="selectedAddon = null"
                        class="flex-1 px-4 py-2.5 border border-slate-600 text-slate-300 rounded-lg text-sm hover:border-slate-500 transition-colors">
                        Cancel
                    </button>
                    <button @click="confirmPurchase" :disabled="purchasing"
                        class="flex-1 flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors">
                        <CreditCard class="w-4 h-4" />
                        {{ purchasing ? 'Processing...' : 'Confirm Purchase' }}
                    </button>
                </div>

            </div>
        </div>

    </AppLayout>
</template>