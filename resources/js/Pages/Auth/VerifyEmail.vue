<script setup>
// FILE: resources/js/Pages/Auth/VerifyEmail.vue
import { useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { MailCheck, RefreshCw } from 'lucide-vue-next'

const page   = usePage()
const sent   = computed(() => page.props.flash?.status === 'verification-link-sent')
const form   = useForm({})
const resend = () => form.post('/email/resend')
</script>

<template>
    <div class="min-h-screen bg-slate-950 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 max-w-md w-full text-center">

            <div class="w-14 h-14 bg-indigo-600/20 rounded-full flex items-center justify-center mx-auto mb-5">
                <MailCheck class="w-7 h-7 text-indigo-400" />
            </div>

            <h1 class="text-white text-xl font-bold mb-2">Verify your email</h1>
            <p class="text-slate-400 text-sm mb-6">
                We sent a verification link to your email address.<br>
                Click it to activate your account before continuing.
            </p>

            <div v-if="sent" class="bg-green-900/30 border border-green-700 text-green-400 text-sm rounded-xl px-4 py-2.5 mb-5">
                A new verification link has been sent!
            </div>

            <button
                @click="resend"
                :disabled="form.processing"
                class="flex items-center gap-2 mx-auto bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-6 py-2.5 rounded-xl text-sm font-medium transition-colors"
            >
                <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': form.processing }" />
                Resend verification email
            </button>

            <form method="POST" action="/logout" class="mt-6">
                <input type="hidden" name="_token" :value="$page.props.csrf_token">
                <button type="submit" class="text-slate-500 hover:text-slate-400 text-xs transition-colors">
                    Sign out and use a different account
                </button>
            </form>
        </div>
    </div>
</template>