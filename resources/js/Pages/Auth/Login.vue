<script setup>
import {Link, useForm} from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/Profile/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/Profile/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Profile/Checkbox.vue';
import InputError from '@/Components/Profile/InputError.vue';
import InputLabel from '@/Components/Profile/InputLabel.vue';
import PrimaryButton from '@/Components/Profile/PrimaryButton.vue';
import TextInput from '@/Components/Profile/TextInput.vue';
import SocialButtons from "@/Components/Social/SocialButtons.vue";
import HomeLayout from "@/Layouts/HomeLayout.vue";

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <HomeLayout>
        <AuthenticationCard>
            <template #logo>
                <AuthenticationCardLogo/>
            </template>

            <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                {{ status }}
            </div>
            <SocialButtons/>
            <div class="divider">{{ $t('or') }}</div>
            <form @submit.prevent="submit">
                <div>
                    <InputLabel for="email" :value="$t('Email')"/>
                    <TextInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="mt-1 block w-full"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="form.errors.email"/>
                </div>

                <div class="mt-4">
                    <InputLabel for="password" :value="$t('Password')"/>
                    <TextInput
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        required
                        autocomplete="current-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password"/>
                </div>

                <div class="block mt-4">
                    <label class="flex items-center">
                        <Checkbox v-model:checked="form.remember" name="remember"/>
                        <span class="ms-2 text-sm">{{ $t('Remember me') }}</span>
                    </label>
                </div>

                <div class="flex items-center justify-end mt-4">
                    <Link v-if="canResetPassword" :href="route('password.request')"
                          class="link">
                        {{ $t('Forgot your password?') }}
                    </Link>

                    <PrimaryButton class="ms-4" :class="{ 'opacity-75': form.processing }" :disabled="form.processing">
                        {{ $t('Log in') }}
                    </PrimaryButton>
                </div>
                <div class="flex flex-col items-center justify-end mt-4">
                    <p class="text-base">{{ $t('Do not have an account?') }}</p>
                    <a :href="route('register')" class="ms-4 flex btn btn-ghost text-info">
                        {{ $t('Register') }}
                    </a>
                </div>
            </form>
        </AuthenticationCard>
    </HomeLayout>
</template>
