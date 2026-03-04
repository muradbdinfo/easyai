<script setup>
import { useForm, Link, usePage, router } from '@inertiajs/vue3'
import { Mail, ArrowLeft, Bot, SendHorizonal } from 'lucide-vue-next'
import { computed } from 'vue'

const form    = useForm({ email: '' })
const page    = usePage()
const success = computed(() => page.props.flash?.success)

const submit = () => form.post(route('password.email'))
</script>

<template>
    <div class="min-h-screen bg-slate-950 flex items-center justify-center px-4">
        <div class="w-full max-w-md">

            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-indigo-600 rounded-2xl mb-4">
                    <Bot class="w-8 h-8 text-white" />
                </div>
                <h1 class="text-2xl font-bold text-white">Forgot Password?</h1>
                <p class="text-slate-400 text-sm mt-1">We'll email you a reset link.</p>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">

                <div v-if="success" class="mb-5 p-3 bg-green-500/10 border border-green-500/30 rounded-lg text-green-400 text-sm">
                    {{ success }}
                </div>

                <div v-if="form.errors.email" class="mb-5 p-3 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm">
                    {{ form.errors.email }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                        <div class="relative">
                            <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                autofocus
                                class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder-slate-500"
                                :class="{ 'border-red-500': form.errors.email }"
                                placeholder="you@company.com"
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium rounded-lg px-4 py-2.5 text-sm transition-colors"
                    >
                        <SendHorizonal class="w-4 h-4" />
                        {{ form.processing ? 'Sending...' : 'Send Reset Link' }}
                    </button>
                </form>
            </div>

            <p class="text-center text-sm text-slate-500 mt-6">
                <Link :href="route('login')" class="text-indigo-400 hover:text-indigo-300 font-medium inline-flex items-center gap-1">
                    <ArrowLeft class="w-3.5 h-3.5" /> Back to Sign In
                </Link>
            </p>

        </div>
    </div>
</template>