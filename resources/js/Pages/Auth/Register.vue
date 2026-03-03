<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import { User, Mail, Lock, UserPlus, Bot } from 'lucide-vue-next'

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
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
                <p class="text-slate-400 text-sm mt-1">Create your workspace</p>
            </div>

            <!-- Card -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">

                <form @submit.prevent="submit" class="space-y-5">

                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Full Name</label>
                        <div class="relative">
                            <User class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg
                                       pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500
                                       focus:ring-1 focus:ring-indigo-500 placeholder-slate-500"
                                :class="{ 'border-red-500': form.errors.name }"
                                placeholder="John Doe"
                            />
                        </div>
                        <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                        <div class="relative">
                            <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg
                                       pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500
                                       focus:ring-1 focus:ring-indigo-500 placeholder-slate-500"
                                :class="{ 'border-red-500': form.errors.email }"
                                placeholder="you@company.com"
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Password</label>
                        <div class="relative">
                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
                            <input
                                v-model="form.password"
                                type="password"
                                required
                                class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg
                                       pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500
                                       focus:ring-1 focus:ring-indigo-500 placeholder-slate-500"
                                :class="{ 'border-red-500': form.errors.password }"
                                placeholder="Min 8 characters"
                            />
                        </div>
                        <p v-if="form.errors.password" class="text-red-400 text-xs mt-1">{{ form.errors.password }}</p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Confirm Password</label>
                        <div class="relative">
                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg
                                       pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500
                                       focus:ring-1 focus:ring-indigo-500 placeholder-slate-500"
                                placeholder="Repeat password"
                            />
                        </div>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500
                               disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium
                               rounded-lg px-4 py-2.5 text-sm transition-colors"
                    >
                        <UserPlus class="w-4 h-4" />
                        {{ form.processing ? 'Creating account...' : 'Create Account' }}
                    </button>

                </form>
            </div>

            <!-- Login link -->
            <p class="text-center text-sm text-slate-500 mt-6">
                Already have an account?
                <Link :href="route('login')" class="text-indigo-400 hover:text-indigo-300 font-medium">
                    Sign in
                </Link>
            </p>

        </div>
    </div>
</template>