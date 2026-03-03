<script setup>
import { LayoutDashboard, Bot } from 'lucide-vue-next'
import { usePage } from '@inertiajs/vue3'

const { auth } = usePage().props
</script>

<template>
    <div class="min-h-screen bg-slate-950 flex items-center justify-center">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-600 rounded-2xl mb-6">
                <Bot class="w-9 h-9 text-white" />
            </div>
            <div class="flex items-center justify-center gap-2 mb-2">
                <LayoutDashboard class="w-5 h-5 text-indigo-400" />
                <h1 class="text-2xl font-bold text-white">Welcome to EasyAI</h1>
            </div>
            <p class="text-slate-400">Hello, {{ auth.user.name }}! Your workspace is ready.</p>
        </div>
    </div>
</template>