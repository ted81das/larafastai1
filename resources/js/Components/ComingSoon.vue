<script setup>

import {Head, useForm} from "@inertiajs/vue3";
import InputError from "@/Components/Profile/InputError.vue";
import Banner from "@/Components/Profile/Banner.vue";

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('coming-soon', form), {
        onSuccess: () => form.reset('email'),
    });
};
</script>

<template>
    <Banner />
    <Head title="Coming Soon"/>
    <div class="hero min-h-screen bg-base-200">
        <div class="hero-content flex w-full flex-col sm:flex-row">
            <div class="basis-2/3 text-left mr-8">
                <h1 class="text-5xl sm:text-7xl font-bold leading-12" v-html="$t('pages.coming-soon.title', { name: $page.props.appName })"></h1>
                <h2 class="py-6" v-html="$t('pages.coming-soon.description', { name: $page.props.appName })"></h2>
            </div>
            <div class="basis-1/3">
                <h3 class="text-4xl text-center mb-8">{{ $t('pages.coming-soon.waitlist.title') }}</h3>
                <div class="card shadow-2xl bg-base-100">
                    <form class="card-body" @submit.prevent="submit">
                        <div class="form-control">
                            <label class="label mb-4">
                                <span class="label-text text-xl" v-html="$t('pages.coming-soon.waitlist.description', { name: $page.props.appName })"></span>
                            </label>
                            <input class="rounded-sm mb-2 border-base-300" type="text" v-model="form.email" :placeholder="$t('pages.coming-soon.waitlist.email')">
                            <InputError class="mt-2" :message="form.errors.email"/>
                            <button type="submit" class="btn btn-secondary">{{ $t('pages.coming-soon.waitlist.btn') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
