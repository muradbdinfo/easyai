<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Bot, Zap, CheckCircle, AlertCircle, RefreshCw, Package } from 'lucide-vue-next'

const page        = usePage()
const hasOpenclaw = computed(() => page.props.active_addons?.includes('openclaw') ?? false)
const clawUrl   = computed(() => page.props.openclaw_url   ?? '')
const clawModel = computed(() => page.props.openclaw_model ?? '')

const checking = ref(false)
const health   = ref(null)

async function checkHealth() {
    checking.value = true
    health.value   = null
    try {
        const res  = await fetch(route('openclaw.health'))
        const data = await res.json()
        health.value = data.success
    } catch {
        health.value = false
    } finally {
        checking.value = false
    }
}
</script>

<template>
    <AppLayout title="OpenClaw Agent">
        <div class="max-w-2xl mx-auto py-8 px-4 space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-3">
                <Bot class="w-6 h-6 text-indigo-400" />
                <div>
                    <h1 class="text-xl font-bold text-white">OpenClaw Agent</h1>
                    <p class="text-slate-400 text-sm">Self-hosted AI agent with MCP tool support.</p>
                </div>
            </div>

            <!-- Not purchased -->
            <div v-if="!hasOpenclaw"
                class="bg-slate-800 border border-slate-700 rounded-xl p-6 text-center space-y-3">
                <Package class="w-10 h-10 mx-auto text-slate-500" />
                <p class="text-slate-300 font-medium">OpenClaw Agent add-on is not active.</p>
                <p class="text-slate-500 text-sm">Purchase it from the Add-ons page to unlock agent-level AI.</p>
                <a :href="route('addons.index')"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                    <Package class="w-4 h-4" /> Go to Add-ons
                </a>
            </div>

            <template v-else>

                <!-- Status card -->
                <div class="bg-slate-800 border border-slate-700 rounded-xl p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <CheckCircle class="w-4 h-4 text-green-400" />
                            <span class="text-white font-medium text-sm">Add-on Active</span>
                        </div>
                        <span class="text-xs text-slate-400 bg-slate-700 px-2 py-1 rounded">openclaw</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-slate-500 text-xs mb-1">Gateway URL</p>
                            <p class="text-slate-300 font-mono text-xs truncate">{{ clawUrl }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 text-xs mb-1">Default Model</p>
                            <p class="text-slate-300 font-mono text-xs">{{ clawModel }}</p>
                        </div>
                    </div>

                    <!-- Health check -->
                    <div class="flex items-center gap-3 pt-1">
                        <button @click="checkHealth" :disabled="checking"
                            class="flex items-center gap-2 bg-slate-700 hover:bg-slate-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition disabled:opacity-50">
                            <RefreshCw class="w-3 h-3" :class="checking ? 'animate-spin' : ''" />
                            Check Connection
                        </button>
                        <span v-if="health === true"  class="flex items-center gap-1 text-green-400 text-xs"><CheckCircle class="w-3 h-3" /> Connected</span>
                        <span v-if="health === false" class="flex items-center gap-1 text-red-400 text-xs"><AlertCircle class="w-3 h-3" /> Not reachable</span>
                    </div>
                </div>

                <!-- How to use -->
                <div class="bg-slate-800 border border-slate-700 rounded-xl p-5 space-y-3">
                    <div class="flex items-center gap-2">
                        <Zap class="w-4 h-4 text-yellow-400" />
                        <p class="text-white font-medium text-sm">How to Use</p>
                    </div>
                    <ol class="text-slate-400 text-sm space-y-2 list-decimal list-inside">
                        <li>Go to any <strong class="text-slate-300">Project → Settings</strong></li>
                        <li>Select <strong class="text-slate-300">{{ clawModel }}</strong> as the model</li>
                        <li>Start chatting — OpenClaw handles web search and tool use automatically</li>
                    </ol>
                </div>

            </template>
        </div>
    </AppLayout>
</template>