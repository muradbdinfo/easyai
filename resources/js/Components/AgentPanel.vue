<script setup>
import { ref, onUnmounted } from 'vue'
import {
    Bot, Zap, Square, ChevronDown, ChevronRight,
    Calculator, Globe, FileText, BookOpen,
    CheckCircle, AlertCircle, RefreshCw, Send
} from 'lucide-vue-next'

const props = defineProps({
    project:    { type: Object, required: true },
    chat:       { type: Object, required: true },
    chatStatus: { type: String, default: 'open' },
})

const emit = defineEmits(['answer-received', 'goal-submitted'])

// ── State ──────────────────────────────────────────────────────
const goal      = ref('')
const maxSteps  = ref(8)
const running   = ref(false)
const runId     = ref(null)
const runStatus = ref(null)
const steps     = ref([])
const expanded  = ref({})
const error     = ref(null)

let pollTimer = null

// ── Tool icon map ───────────────────────────────────────────────
const toolIcon = {
    calculator:            Calculator,
    web_search:            Globe,
    read_url:              FileText,
    search_knowledge_base: BookOpen,
}

function getToolIcon(name) {
    return toolIcon[name] ?? Bot
}

// ── Run agent ───────────────────────────────────────────────────
async function runAgent() {
    if (!goal.value.trim() || running.value) return

    running.value   = true
    runId.value     = null
    runStatus.value = 'running'
    steps.value     = []
    expanded.value  = {}
    error.value     = null

    // Push goal as user bubble in chat immediately
    emit('goal-submitted', goal.value.trim())

    try {
        const res = await axios.post(
            route('agent.run', [props.project.id, props.chat.id]),
            { goal: goal.value.trim(), max_steps: maxSteps.value },
            { headers: { 'X-XSRF-TOKEN': getCsrfToken() } }
        )

        if (res.data.data?.agent_run_id) {
            runId.value = res.data.data.agent_run_id
            startPolling()
        } else {
            throw new Error('No agent_run_id returned.')
        }
    } catch (e) {
        running.value   = false
        runStatus.value = 'failed'
        error.value     = e.response?.data?.message ?? e.message ?? 'Failed to start agent.'
        emit('answer-received') // remove thinking bubble even on error
    }
}

// ── Stop agent ──────────────────────────────────────────────────
async function stopAgent() {
    if (!runId.value) return

    try {
        await axios.post(
            route('agent.stop', [props.project.id, props.chat.id, runId.value]),
            {},
            { headers: { 'X-XSRF-TOKEN': getCsrfToken() } }
        )
    } catch {}

    runStatus.value = 'stopped'
    stopPolling()
    running.value = false
    emit('answer-received') // remove thinking bubble
}

// ── Polling ─────────────────────────────────────────────────────
function startPolling() {
    pollTimer = setInterval(fetchSteps, 2000)
}

function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer)
        pollTimer = null
    }
}

async function fetchSteps() {
    if (!runId.value) return

    try {
        const res = await axios.get(
            route('agent.steps', [props.project.id, props.chat.id, runId.value])
        )

        const data      = res.data.data
        steps.value     = data.steps ?? []
        runStatus.value = data.run?.status ?? 'running'

        // Auto-expand latest step
        if (steps.value.length) {
            const last = steps.value[steps.value.length - 1]
            expanded.value[last.step_number] = true
        }

        if (['completed', 'failed', 'stopped'].includes(runStatus.value)) {
            stopPolling()
            running.value = false

            if (runStatus.value === 'failed') {
                error.value = data.run?.error_message ?? 'Agent failed.'
            }

            // Tell Show.vue to refresh messages from DB
            emit('answer-received')

            if (runStatus.value === 'completed') {
                goal.value = ''
            }
        }

    } catch {}
}

// ── Helpers ─────────────────────────────────────────────────────
function toggleStep(num) {
    expanded.value[num] = !expanded.value[num]
}

function getCsrfToken() {
    return decodeURIComponent(
        document.cookie
            .split(';')
            .find(c => c.trim().startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? ''
    )
}

function truncate(str, n = 120) {
    if (!str) return ''
    const s = typeof str === 'object' ? JSON.stringify(str) : String(str)
    return s.length > n ? s.slice(0, n) + '…' : s
}

onUnmounted(stopPolling)
</script>

<template>
    <div class="flex flex-col h-full bg-slate-950">

        <!-- ── Header ─────────────────────────────────────────── -->
        <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-800 shrink-0">
            <div class="w-6 h-6 bg-indigo-600/20 rounded-lg flex items-center justify-center">
                <Zap class="w-3.5 h-3.5 text-indigo-400" />
            </div>
            <span class="text-white text-sm font-semibold">Agent Mode</span>
            <span class="ml-auto text-xs text-slate-500">ReAct · up to {{ maxSteps }} steps</span>
        </div>

        <!-- ── Goal input ──────────────────────────────────────── -->
        <div class="px-4 py-3 border-b border-slate-800 shrink-0">
            <textarea
                v-model="goal"
                :disabled="running || chatStatus === 'closed'"
                placeholder="Describe the goal for the agent… e.g. 'Search for the latest Laravel release notes and summarise the key changes'"
                rows="3"
                class="w-full bg-slate-800 text-white text-sm placeholder-slate-500 rounded-xl
                       px-4 py-3 resize-none outline-none focus:ring-2 focus:ring-indigo-500
                       border border-slate-700 disabled:opacity-50"
                @keydown.ctrl.enter.prevent="runAgent"
            />

            <div class="flex items-center gap-3 mt-2">

                <!-- Max steps selector -->
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span>Max steps:</span>
                    <select v-model="maxSteps" :disabled="running"
                        class="bg-slate-800 text-slate-300 border border-slate-700 rounded-lg
                               px-2 py-1 text-xs outline-none focus:ring-1 focus:ring-indigo-500">
                        <option v-for="n in [3,5,8,10,15]" :key="n" :value="n">{{ n }}</option>
                    </select>
                </div>

                <div class="flex-1" />

                <!-- Stop button (while running) -->
                <button v-if="running" @click="stopAgent"
                    class="flex items-center gap-1.5 px-3 py-1.5 bg-red-600/20 hover:bg-red-600/30
                           text-red-400 border border-red-600/30 rounded-lg text-xs font-medium
                           transition-colors">
                    <Square class="w-3 h-3" /> Stop
                </button>

                <!-- Run button -->
                <button v-else @click="runAgent"
                    :disabled="!goal.trim() || chatStatus === 'closed'"
                    class="flex items-center gap-1.5 px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500
                           disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-lg
                           text-xs font-semibold transition-colors">
                    <Send class="w-3 h-3" /> Run Agent
                </button>
            </div>
        </div>

        <!-- ── Steps area ──────────────────────────────────────── -->
        <div class="flex-1 overflow-y-auto px-4 py-3 space-y-2">

            <!-- Empty state -->
            <div v-if="!running && !steps.length && !error"
                class="flex flex-col items-center justify-center h-full text-center py-12">
                <div class="w-12 h-12 bg-indigo-600/10 rounded-2xl flex items-center justify-center mb-3">
                    <Bot class="w-6 h-6 text-indigo-500" />
                </div>
                <p class="text-slate-400 text-sm font-medium">Ready to run</p>
                <p class="text-slate-600 text-xs mt-1 max-w-xs">
                    Enter a goal above and click Run Agent. The AI will reason
                    step-by-step and use tools to complete it.
                </p>
            </div>

            <!-- Running spinner (no steps yet) -->
            <div v-if="running && !steps.length"
                class="flex items-center gap-3 px-4 py-3 bg-slate-800/50 rounded-xl border border-slate-700/50">
                <RefreshCw class="w-4 h-4 text-indigo-400 animate-spin shrink-0" />
                <span class="text-slate-400 text-sm">Agent thinking…</span>
            </div>

            <!-- Error -->
            <div v-if="error"
                class="flex items-start gap-2 px-4 py-3 bg-red-900/20 border border-red-700/30 rounded-xl">
                <AlertCircle class="w-4 h-4 text-red-400 shrink-0 mt-0.5" />
                <p class="text-red-300 text-sm">{{ error }}</p>
            </div>

            <!-- Step list -->
            <div v-for="step in steps" :key="step.id"
                class="rounded-xl border transition-colors"
                :class="step.tool_name
                    ? 'border-indigo-700/40 bg-indigo-900/10'
                    : 'border-slate-700/40 bg-slate-800/30'">

                <!-- Step header (clickable) -->
                <button @click="toggleStep(step.step_number)"
                    class="w-full flex items-center gap-2 px-3 py-2.5 text-left">

                    <!-- Step number -->
                    <span class="w-5 h-5 rounded-full bg-slate-700 flex items-center justify-center
                                 text-slate-400 text-xs font-bold shrink-0">
                        {{ step.step_number }}
                    </span>

                    <!-- Tool / reasoning icon -->
                    <div class="w-5 h-5 flex items-center justify-center shrink-0">
                        <component
                            :is="step.tool_name ? getToolIcon(step.tool_name) : Bot"
                            class="w-3.5 h-3.5"
                            :class="step.tool_name ? 'text-indigo-400' : 'text-slate-500'"
                        />
                    </div>

                    <!-- Label + thought preview -->
                    <div class="flex-1 min-w-0">
                        <span v-if="step.tool_name"
                            class="text-indigo-300 text-xs font-semibold uppercase tracking-wide">
                            {{ step.tool_name.replace(/_/g,' ') }}
                        </span>
                        <span v-else class="text-slate-400 text-xs">Reasoning</span>

                        <p v-if="step.thought && !expanded[step.step_number]"
                            class="text-slate-500 text-xs mt-0.5 truncate">
                            {{ truncate(step.thought, 80) }}
                        </p>
                    </div>

                    <!-- Expand toggle -->
                    <component
                        :is="expanded[step.step_number] ? ChevronDown : ChevronRight"
                        class="w-3.5 h-3.5 text-slate-600 shrink-0"
                    />
                </button>

                <!-- Expanded content -->
                <div v-if="expanded[step.step_number]"
                    class="px-3 pb-3 space-y-2 border-t border-slate-700/30">

                    <!-- Thought -->
                    <div v-if="step.thought" class="mt-2">
                        <p class="text-slate-500 text-xs font-semibold uppercase mb-1">Thought</p>
                        <p class="text-slate-300 text-xs leading-relaxed bg-slate-800/50
                                   rounded-lg px-3 py-2 whitespace-pre-wrap">{{ step.thought }}</p>
                    </div>

                    <!-- Tool input -->
                    <div v-if="step.tool_input">
                        <p class="text-slate-500 text-xs font-semibold uppercase mb-1">Input</p>
                        <pre class="text-indigo-300 text-xs bg-slate-800/80 rounded-lg px-3 py-2
                                   overflow-x-auto whitespace-pre-wrap break-all">{{ typeof step.tool_input === 'object'
                            ? JSON.stringify(step.tool_input, null, 2)
                            : step.tool_input }}</pre>
                    </div>

                    <!-- Tool output -->
                    <div v-if="step.tool_output">
                        <p class="text-slate-500 text-xs font-semibold uppercase mb-1">Result</p>
                        <pre class="text-green-300 text-xs bg-slate-800/80 rounded-lg px-3 py-2
                                   overflow-x-auto whitespace-pre-wrap max-h-48 break-all">{{ truncate(step.tool_output, 800) }}</pre>
                    </div>
                </div>
            </div>

            <!-- Completed badge -->
            <div v-if="runStatus === 'completed'"
                class="flex items-center gap-2 px-3 py-2 bg-green-900/20 border border-green-700/30 rounded-xl">
                <CheckCircle class="w-4 h-4 text-green-400 shrink-0" />
                <span class="text-green-300 text-sm">Agent completed — answer posted in chat</span>
            </div>

            <!-- Stopped badge -->
            <div v-if="runStatus === 'stopped'"
                class="flex items-center gap-2 px-3 py-2 bg-amber-900/20 border border-amber-700/30 rounded-xl">
                <Square class="w-4 h-4 text-amber-400 shrink-0" />
                <span class="text-amber-300 text-sm">Agent stopped</span>
            </div>

            <!-- Failed badge -->
            <div v-if="runStatus === 'failed' && !error"
                class="flex items-center gap-2 px-3 py-2 bg-red-900/20 border border-red-700/30 rounded-xl">
                <AlertCircle class="w-4 h-4 text-red-400 shrink-0" />
                <span class="text-red-300 text-sm">Agent failed</span>
            </div>

        </div>
    </div>
</template>