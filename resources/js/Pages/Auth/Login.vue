<script setup>
import { ref, computed } from 'vue'
import { useForm, Link, usePage } from '@inertiajs/vue3'
import { Mail, Lock, LogIn, Bot } from 'lucide-vue-next'

const page    = usePage()
const success = computed(() => page.props.flash?.success)

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 flex items-center justify-center px-4">
        <div class="w-full max-w-md">

            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-indigo-600 rounded-2xl mb-4">
                    <Bot class="w-8 h-8 text-white" />
                </div>
                <h1 class="text-2xl font-bold text-white">EasyAI</h1>
                <p class="text-slate-400 text-sm mt-1">Sign in to your workspace</p>
            </div>

            <!-- Card -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">

                <!-- Flash Success -->
                <div v-if="success"
                     class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-lg text-green-400 text-sm">
                    {{ success }}
                </div>

                <!-- Flash Error -->
                <div v-if="form.errors.email"
                     class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm">
                    {{ form.errors.email }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Email Address
                        </label>
                        <div class="relative">
                            <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
                            <input
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                required
                                class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg
                                       pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500
                                       focus:ring-1 focus:ring-indigo-500 placeholder-slate-500"
                                placeholder="you@company.com"
                            />
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
                            <input
                                v-model="form.password"
                                type="password"
                                autocomplete="current-password"
                                required
                                class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg
                                       pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500
                                       focus:ring-1 focus:ring-indigo-500 placeholder-slate-500"
                                placeholder="••••••••"
                            />
                        </div>
                    </div>

                    <!-- Forgot password link -->
                    <div class="flex justify-end">
                        <Link
                            :href="route('password.request')"
                            class="text-xs text-slate-500 hover:text-indigo-400 transition-colors"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <!-- Remember -->
                    <div class="flex items-center">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            id="remember"
                            class="rounded border-slate-600 bg-slate-800 text-indigo-600
                                   focus:ring-indigo-500 focus:ring-offset-slate-900"
                        />
                        <label for="remember" class="ml-2 text-sm text-slate-400">
                            Remember me
                        </label>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500
                               disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium
                               rounded-lg px-4 py-2.5 text-sm transition-colors"
                    >
                        <LogIn class="w-4 h-4" />
                        {{ form.processing ? 'Signing in...' : 'Sign In' }}
                    </button>

                </form>
            </div>

            <!-- Register link -->
            <p class="text-center text-sm text-slate-500 mt-6">
                Don't have an account?
                <Link :href="route('register')" class="text-indigo-400 hover:text-indigo-300 font-medium">
                    Create one
                </Link>
            </p>

        </div>
    </div>
</template>