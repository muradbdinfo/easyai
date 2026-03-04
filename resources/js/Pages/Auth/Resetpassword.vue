<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import { Lock, Bot, KeyRound } from 'lucide-vue-next'

const props = defineProps({
    token: String,
    email: String,
})

const form = useForm({
    token:                 props.token,
    email:                 props.email,
    password:              '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 flex items-center justify-center px-4">
        <div class="w-full max-w-md">

            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-indigo-600 rounded-2xl mb-4">
                    <Bot class="w-8 h-8 text-white" />
                </div>
                <h1 class="text-2xl font-bold text-white">Reset Password</h1>
                <p class="text-slate-400 text-sm mt-1">Enter your new password below.</p>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">

                <div v-if="form.errors.email" class="mb-5 p-3 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm">
                    {{ form.errors.email }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">

                    <!-- Hidden email -->
                    <input type="hidden" v-model="form.email" />

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">New Password</label>
                        <div class="relative">
                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
                            <input
                                v-model="form.password"
                                type="password"
                                required
                                autofocus
                                class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder-slate-500"
                                :class="{ 'border-red-500': form.errors.password }"
                                placeholder="Min 8 characters"
                            />
                        </div>
                        <p v-if="form.errors.password" class="text-red-400 text-xs mt-1">{{ form.errors.password }}</p>
                    </div>

                    <!-- Confirm -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Confirm Password</label>
                        <div class="relative">
                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder-slate-500"
                                placeholder="Repeat password"
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium rounded-lg px-4 py-2.5 text-sm transition-colors"
                    >
                        <KeyRound class="w-4 h-4" />
                        {{ form.processing ? 'Resetting...' : 'Reset Password' }}
                    </button>
                </form>
            </div>

            <p class="text-center text-sm text-slate-500 mt-6">
                <Link :href="route('login')" class="text-indigo-400 hover:text-indigo-300 font-medium">
                    Back to Sign In
                </Link>
            </p>

        </div>
    </div>
</template>