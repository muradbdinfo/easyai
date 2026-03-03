<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Zap, AlertCircle } from 'lucide-vue-next'

const emit = defineEmits(['upgrade'])

const quota = computed(() => usePage().props.quota)

const barColor = computed(() => {
    if (!quota.value) return 'bg-slate-600'
    const p = quota.value.percent
    if (p >= 90) return 'bg-red-500'
    if (p >= 70) return 'bg-amber-500'
    return 'bg-indigo-500'
})

const textColor = computed(() => {
    if (!quota.value) return 'text-slate-500'
    const p = quota.value.percent
    if (p >= 90) return 'text-red-400'
    if (p >= 70) return 'text-amber-400'
    return 'text-slate-400'
})

function fmt(n) {
    if (!n && n !== 0) return '0'
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
    return String(n)
}
</script>

<template>
    <div v-if="quota" class="px-4 py-3">

        <!-- Label -->
        <div class="flex items-center justify-between mb-1.5">
            <div class="flex items-center gap-1.5" :class="textColor">
                <Zap class="w-3.5 h-3.5" />
                <span class="text-xs font-medium">
                    {{ fmt(quota.used) }} / {{ fmt(quota.total) }}
                </span>
            </div>
            <span class="text-xs" :class="textColor">{{ quota.percent }}%</span>
        </div>

        <!-- Bar -->
        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
            <div
                :class="barColor"
                class="h-1.5 rounded-full transition-all duration-500"
                :style="{ width: Math.min(quota.percent, 100) + '%' }"
            />
        </div>

        <!-- Exceeded -->
        <div v-if="quota.exceeded" class="mt-2">
            <button
                @click="$emit('upgrade')"
                class="w-full flex items-center justify-center gap-1.5 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-400 text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
            >
                <AlertCircle class="w-3.5 h-3.5" />
                Quota exceeded — Upgrade
            </button>
        </div>
        <p v-else class="text-slate-600 text-xs mt-1.5">
            Resets {{ quota.reset_date }}
        </p>
    </div>
</template>