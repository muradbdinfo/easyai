<script setup>
// FILE: resources/js/Components/ChatbotWidget.vue
import { ref, nextTick, onMounted, watch } from 'vue'
import { Bot, X, Send, RefreshCw, Minimize2, MessageSquare } from 'lucide-vue-next'

defineProps({
    botName:  { type: String, default: 'EasyAI Assistant' },
    greeting: { type: String, default: 'Hi there! 👋 Ask me anything about EasyAI.' },
})

const isOpen     = ref(false)
const messages   = ref([])
const input      = ref('')
const loading    = ref(false)
const sessionId  = ref('')
const messagesEl = ref(null)
const inputEl    = ref(null)
const hasNewMsg  = ref(false)
const dots       = ref(0)
let dotTimer     = null

function ts()  { return new Date().toLocaleTimeString('en', { hour: '2-digit', minute: '2-digit' }) }
function uid() { return Math.random().toString(36).slice(2) }
function uuid() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
        const r = Math.random() * 16 | 0
        return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16)
    })
}

onMounted(() => {
    sessionId.value = localStorage.getItem('easyai_session') || uuid()
    localStorage.setItem('easyai_session', sessionId.value)
    messages.value.push({ role: 'bot', content: 'Hi there! 👋 Ask me anything about EasyAI.', time: ts(), id: uid() })
})

watch(loading, v => {
    if (v) { dots.value = 0; dotTimer = setInterval(() => { dots.value = (dots.value + 1) % 4 }, 400) }
    else   { clearInterval(dotTimer) }
})

async function scrollBottom() {
    await nextTick()
    if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight
}

function toggle() {
    isOpen.value = !isOpen.value
    if (isOpen.value) { hasNewMsg.value = false; scrollBottom(); nextTick(() => inputEl.value?.focus()) }
}

async function send() {
    const text = input.value.trim()
    if (!text || loading.value) return
    messages.value.push({ role: 'user', content: text, time: ts(), id: uid() })
    input.value   = ''
    loading.value = true
    await scrollBottom()
    try {
        const res = await fetch('/chatbot/relay', {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ message: text, session_id: sessionId.value }),
        })
        if (!res.ok) throw new Error(`HTTP ${res.status}`)
        const data  = await res.json()
        const reply = data.reply || data.message || data.output || data.text ||
                      (Array.isArray(data) && (data[0]?.reply || data[0]?.output)) ||
                      'Sorry, no response. Please try again.'
        messages.value.push({ role: 'bot', content: String(reply), time: ts(), id: uid() })
        if (!isOpen.value) hasNewMsg.value = true
    } catch {
        messages.value.push({ role: 'bot', content: 'Connection error. Please try again.', time: ts(), id: uid(), error: true })
    } finally {
        loading.value = false
        scrollBottom()
    }
}

function onKeydown(e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send() } }

function clearChat() {
    sessionId.value = uuid()
    localStorage.setItem('easyai_session', sessionId.value)
    messages.value = [{ role: 'bot', content: 'Hi there! 👋 Ask me anything about EasyAI.', time: ts(), id: uid() }]
}
</script>

<template>
    <div class="fixed bottom-6 right-6 z-[9999] flex flex-col items-end gap-3">
        <Transition name="pop">
            <div v-if="hasNewMsg && !isOpen"
                 class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center shadow-lg">1</div>
        </Transition>
        <Transition name="slide-up">
            <div v-if="!isOpen && messages.length === 1"
                 class="widget-hint mb-1 px-4 py-2.5 rounded-2xl rounded-br-sm text-sm font-medium text-white shadow-xl cursor-pointer max-w-[220px] text-right leading-snug"
                 style="background:var(--brand)" @click="toggle">
                Hi there! 👋 Ask me anything…
            </div>
        </Transition>
        <button @click="toggle"
                class="w-14 h-14 rounded-full shadow-2xl flex items-center justify-center transition-all duration-300 hover:scale-110 active:scale-95 focus:outline-none"
                style="background:var(--brand)">
            <Transition name="spin" mode="out-in">
                <X             v-if="isOpen" key="x"   class="w-5 h-5 text-white"/>
                <MessageSquare v-else         key="bot" class="w-5 h-5 text-white"/>
            </Transition>
        </button>
    </div>

    <Transition name="chat-panel">
        <div v-if="isOpen"
             class="fixed bottom-24 right-6 z-[9998] w-[360px] max-w-[calc(100vw-2rem)] rounded-2xl overflow-hidden shadow-2xl flex flex-col"
             style="background:#0f172a;border:1px solid #1e293b;max-height:min(520px,calc(100vh - 8rem))">

            <div class="flex items-center gap-3 px-4 py-3 shrink-0" style="background:#020617;border-bottom:1px solid #1e293b">
                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" style="background:var(--brand)">
                    <Bot class="w-4 h-4 text-white"/>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ botName }}</p>
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-[11px] text-slate-500">Online · Powered by Ollama</span>
                    </div>
                </div>
                <button @click="clearChat" title="Clear chat"
                        class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-600 hover:text-slate-300 hover:bg-slate-800 transition-colors">
                    <RefreshCw class="w-3.5 h-3.5"/>
                </button>
                <button @click="toggle"
                        class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-600 hover:text-slate-300 hover:bg-slate-800 transition-colors">
                    <Minimize2 class="w-3.5 h-3.5"/>
                </button>
            </div>

            <div ref="messagesEl" class="flex-1 overflow-y-auto px-4 py-4 space-y-4 scrollbar-thin"
                 style="scrollbar-color:#334155 transparent">
                <TransitionGroup name="msg">
                    <div v-for="msg in messages" :key="msg.id"
                         :class="['flex gap-2.5', msg.role === 'user' ? 'flex-row-reverse' : 'flex-row']">
                        <div class="w-7 h-7 rounded-full shrink-0 flex items-center justify-center mt-0.5"
                             :style="msg.role === 'user' ? 'background:#1e293b' : 'background:var(--brand)'">
                            <Bot v-if="msg.role !== 'user'" class="w-3.5 h-3.5 text-white"/>
                            <span v-else class="text-[10px] text-slate-300 font-bold">You</span>
                        </div>
                        <div :class="['max-w-[78%] flex flex-col gap-1', msg.role === 'user' ? 'items-end' : 'items-start']">
                            <div :class="['px-3.5 py-2.5 rounded-2xl text-sm leading-relaxed break-words whitespace-pre-wrap',
                                          msg.role === 'user' ? 'text-white rounded-tr-sm' :
                                          msg.error ? 'bg-red-950 text-red-300 border border-red-800 rounded-tl-sm' :
                                          'bg-slate-800 text-slate-200 rounded-tl-sm']"
                                 :style="msg.role === 'user' ? 'background:var(--brand)' : ''">{{ msg.content }}</div>
                            <span class="text-[10px] text-slate-600 px-1">{{ msg.time }}</span>
                        </div>
                    </div>
                </TransitionGroup>
                <Transition name="msg">
                    <div v-if="loading" class="flex gap-2.5">
                        <div class="w-7 h-7 rounded-full shrink-0 flex items-center justify-center" style="background:var(--brand)">
                            <Bot class="w-3.5 h-3.5 text-white"/>
                        </div>
                        <div class="px-3.5 py-2.5 rounded-2xl rounded-tl-sm bg-slate-800">
                            <span class="flex gap-1.5 items-center h-4">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-500 animate-bounce" style="animation-delay:0ms"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-500 animate-bounce" style="animation-delay:150ms"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-500 animate-bounce" style="animation-delay:300ms"></span>
                            </span>
                        </div>
                    </div>
                </Transition>
            </div>

            <div class="px-3 py-3 shrink-0" style="border-top:1px solid #1e293b;background:#020617">
                <div class="flex gap-2 items-end">
                    <textarea ref="inputEl" v-model="input" @keydown="onKeydown"
                              placeholder="Ask me anything…" rows="1" :disabled="loading"
                              class="flex-1 resize-none rounded-xl px-3.5 py-2.5 text-sm text-slate-200 placeholder-slate-600 outline-none transition-all"
                              style="background:#0f172a;border:1px solid #1e293b;max-height:120px;field-sizing:content"
                              :style="{ 'border-color': input.length ? 'var(--brand)' : '' }"/>
                    <button @click="send" :disabled="!input.trim() || loading"
                            class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition-all disabled:opacity-30 hover:opacity-90 active:scale-95"
                            style="background:var(--brand)">
                        <Send class="w-4 h-4 text-white"/>
                    </button>
                </div>
                <p class="text-center text-[10px] text-slate-700 mt-2">
                    Powered by <span class="text-slate-500 font-medium">EasyAI</span> · Enter to send
                </p>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.chat-panel-enter-active { transition: opacity .2s ease, transform .25s cubic-bezier(.34,1.56,.64,1); }
.chat-panel-leave-active { transition: opacity .15s ease, transform .15s ease; }
.chat-panel-enter-from   { opacity:0; transform:translateY(16px) scale(.97); }
.chat-panel-leave-to     { opacity:0; transform:translateY(8px) scale(.98); }
.msg-enter-active        { transition: opacity .2s ease, transform .25s ease; }
.msg-enter-from          { opacity:0; transform:translateY(6px); }
.spin-enter-active,.spin-leave-active { transition: opacity .15s, transform .15s; }
.spin-enter-from { opacity:0; transform:rotate(-90deg) scale(.5); }
.spin-leave-to   { opacity:0; transform:rotate(90deg) scale(.5); }
.slide-up-enter-active { transition: opacity .3s ease, transform .3s cubic-bezier(.34,1.56,.64,1); }
.slide-up-leave-active { transition: opacity .2s ease, transform .2s ease; }
.slide-up-enter-from   { opacity:0; transform:translateY(10px); }
.slide-up-leave-to     { opacity:0; transform:translateY(4px); }
.pop-enter-active { transition: transform .3s cubic-bezier(.34,1.56,.64,1), opacity .2s; }
.pop-enter-from   { transform:scale(0); opacity:0; }
.scrollbar-thin::-webkit-scrollbar       { width:4px; }
.scrollbar-thin::-webkit-scrollbar-track { background:transparent; }
.scrollbar-thin::-webkit-scrollbar-thumb { background:#334155; border-radius:2px; }
.widget-hint { position:relative; }
</style>