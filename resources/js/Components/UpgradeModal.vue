<script setup>
import { AlertCircle, CreditCard, X, Zap } from 'lucide-vue-next'

defineProps({ show: Boolean })
defineEmits(['close'])
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 px-4"
            @click.self="$emit('close')"
        >
            <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md p-6">

                <!-- Header -->
                <div class="flex items-start justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-amber-500/20 rounded-xl flex items-center justify-center">
                            <AlertCircle class="w-5 h-5 text-amber-400" />
                        </div>
                        <div>
                            <h2 class="text-white font-semibold">Token Quota Exceeded</h2>
                            <p class="text-slate-400 text-sm">Upgrade your plan to continue</p>
                        </div>
                    </div>
                    <button @click="$emit('close')" class="text-slate-500 hover:text-slate-300">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Body -->
                <div class="bg-slate-800 rounded-xl p-4 mb-5">
                    <div class="flex items-center gap-2 mb-2">
                        <Zap class="w-4 h-4 text-amber-400" />
                        <span class="text-slate-300 text-sm font-medium">
                            Monthly token quota used up
                        </span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Upgrade to a higher plan to get more tokens and keep chatting.
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button
                        @click="$emit('close')"
                        class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    >
                        Maybe Later
                    </button>
                    <a
                        href="/billing/plans"
                        class="flex-1 flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    >
                        <CreditCard class="w-4 h-4" />
                        Upgrade Plan
                    </a>
                </div>

            </div>
        </div>
    </Teleport>
</template>