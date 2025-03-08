<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/Profile/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/Profile/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Profile/Checkbox.vue';
import InputError from '@/Components/Profile/InputError.vue';
import InputLabel from '@/Components/Profile/InputLabel.vue';
import PrimaryButton from '@/Components/Profile/PrimaryButton.vue';
import TextInput from '@/Components/Profile/TextInput.vue';
import SocialButtons from "@/Components/Social/SocialButtons.vue";
import HomeLayout from "@/Layouts/HomeLayout.vue";

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <HomeLayout>
        <AuthenticationCard>
            <template #logo>
                <AuthenticationCardLogo />
            </template>
            <SocialButtons/>
            <div class="divider">{{ $t('or') }}</div>
            <form @submit.prevent="submit">
                <div>
                    <InputLabel for="name" :value="$t('Name')" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                        autocomplete="name"
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div class="mt-4">
                    <InputLabel for="email" :value="$t('Email')" />
                    <TextInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="mt-1 block w-full"
                        required
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="mt-4">
                    <InputLabel for="password" :value="$t('Password')" />
                    <TextInput
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        required
                        autocomplete="new-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="mt-4">
                    <InputLabel for="password_confirmation" :value="$t('Confirm Password')" />
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        required
                        autocomplete="new-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>

                <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature" class="mt-4">
                    <InputLabel for="terms">
                        <div class="flex items-center">
                            <Checkbox id="terms" v-model:checked="form.terms" name="terms" required />

                            <div class="ms-2" v-html="$t('checkbox.terms', { terms: route('terms.show'), policy: route('policy.show') })"></div>
                        </div>
                        <InputError class="mt-2" :message="form.errors.terms" />
                    </InputLabel>
                </div>

                <div class="flex items-center justify-end mt-4">
                    <Link :href="route('login')" class="link">
                        {{ $t('Already registered?') }}
                    </Link>

                    <PrimaryButton class="ms-4" :class="{ 'opacity-75': form.processing }" :disabled="form.processing">
                        {{ $t('Register') }}
                    </PrimaryButton>
                </div>
            </form>
        </AuthenticationCard>
    </HomeLayout>
</template>
