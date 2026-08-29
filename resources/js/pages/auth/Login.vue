<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input, PasswordInput } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { CheckCircle2, LoaderCircle, Lock, Mail } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const shake = ref(false);

const submit = () => {
    form.post(route('login'), {
        onError: () => {
            shake.value = true;
            window.setTimeout(() => (shake.value = false), 450);
        },
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthBase title="Log in to your account" description="Enter your email and password below to log in">
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-4 flex animate-fade-slide-in items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700"
        >
            <CheckCircle2 class="size-4 shrink-0" />
            {{ status }}
        </div>

        <form :class="['flex flex-col gap-6', shake && 'animate-shake']" @submit.prevent="submit">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <div class="relative">
                        <Mail class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="email"
                            type="email"
                            required
                            autofocus
                            tabindex="1"
                            autocomplete="email"
                            v-model="form.email"
                            placeholder="email@example.com"
                            class="pl-10 focus-visible:ring-emerald-500/40"
                        />
                    </div>
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Password</Label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm" :tabindex="5"> Forgot password? </TextLink>
                    </div>
                    <div class="relative">
                        <Lock class="pointer-events-none absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-muted-foreground" />
                        <PasswordInput
                            id="password"
                            required
                            tabindex="2"
                            autocomplete="current-password"
                            v-model="form.password"
                            placeholder="Password"
                            class="pl-10 focus-visible:ring-emerald-500/40"
                        />
                    </div>
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between" tabindex="3">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" v-model:checked="form.remember" tabindex="4" />
                        <span>Remember me</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full bg-gradient-to-r from-emerald-600 to-green-600 shadow-md shadow-emerald-900/10 transition-all duration-200 hover:-translate-y-0.5 hover:from-emerald-500 hover:to-green-500 hover:shadow-lg hover:shadow-emerald-900/20 active:translate-y-0 active:shadow-sm"
                    tabindex="4"
                    :disabled="form.processing"
                >
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Log in
                </Button>
            </div>
        </form>
    </AuthBase>
</template>
