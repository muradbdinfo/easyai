<script setup>
import { useForm } from '@inertiajs/vue3'
import { Shield, Mail, Lock, LogIn } from 'lucide-vue-next'

const form = useForm({
    email:    '',
    password: '',
})

function submit() {
    form.post('/login')
}
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
        <div class="w-full max-w-md">

            <!-- Header -->
            <div class="text-center mb-8">
                <div class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <Shield class="w-7 h-7 text-white" />
                </div>
                <h1 class="text-2xl font-bold text-slate-800">EasyAI Admin</h1>
                <p class="text-slate-500 text-sm mt-1">Super Admin Access Only</p>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">

                <form @submit.prevent="submit" class="space-y-5">

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">
                            Email
                        </label>
                        <div class="relative">
                            <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                placeholder="admin@easyai.local"
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-xl text-sm
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                                       bg-white text-slate-800 placeholder-slate-400"
                                :class="{ 'border-red-400': form.errors.email }"
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">
                            Password
                        </label>
                        <div class="relative">
                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input
                                v-model="form.password"
                                type="password"
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-xl text-sm
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                                       bg-white text-slate-800 placeholder-slate-400"
                                :class="{ 'border-red-400': form.errors.password }"
                            />
                        </div>
                        <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-indigo-600
                               hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed
                               text-white font-medium rounded-xl text-sm transition-colors"
                    >
                        <LogIn class="w-4 h-4" />
                        {{ form.processing ? 'Signing in…' : 'Sign In to Admin' }}
                    </button>

                </form>
            </div>

            <p class="text-center text-xs text-slate-400 mt-6">
                EasyAI Admin Panel — Restricted Access
            </p>
        </div>
    </div>
</template>