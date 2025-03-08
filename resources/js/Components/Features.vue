<script setup>

import {ref} from "vue";
import {CreditCardIcon} from "@heroicons/vue/24/outline/index.js";
import {UsersIcon} from "@heroicons/vue/24/outline/index.js";
import {UserIcon} from "@heroicons/vue/24/outline/index.js";
import {DocumentMagnifyingGlassIcon} from "@heroicons/vue/24/outline/index.js";
import {PaintBrushIcon} from "@heroicons/vue/24/outline/index.js";
import {DocumentTextIcon} from "@heroicons/vue/24/outline/index.js";
import {EllipsisHorizontalIcon} from "@heroicons/vue/24/outline/index.js";
import FeatureItems from "@/Components/FeatureItems.vue";
import Heading from "@/Components/Data/Heading.vue";

const activeTab = ref({title: 'Payments', slug: 'payments'});

const tabs = [
    {title: 'Payments', slug: 'payments'},
    {title: 'Auth', slug: 'auth'},
    {title: 'Admin', slug: 'admin'},
    {title: 'SEO', slug: 'seo'},
    {title: 'Blog', slug: 'blog'},
    {title: 'Themes (30+)', slug: 'themes'},
    {title: '... and more', slug: 'more'}
];

const switchTab = (tab) => {
    activeTab.value = tab;
}
</script>

<template>
    <div class="max-w-4xl mx-auto sm:py-8 sm:pt-0">
        <div class="mb-12 max-w-2xl text-center mx-auto px-4 sm:px-0">
            <Heading>{{ $t('pages.home.features.title') }}</Heading>
            <p class="mt-2 text-md leading-6">{{ $t('pages.home.features.title') }}</p>
        </div>
        <!-- The tabs section-->
        <div role="tablist" class="tabs tabs-bordered overflow-y-scroll">
            <div class="tab h-24 w-32" v-for="(tab) in tabs" role="tab" @click="switchTab(tab)" :class="{'tab-active': tab.slug === activeTab.slug}">
                <div class="flex flex-col justify-start items-center">
                    <CreditCardIcon v-if="tab.slug === 'payments'" :class="{'text-secondary': activeTab.slug === 'payments'}" class="w-8 h-8"/>
                    <UsersIcon v-if="tab.slug === 'auth'" :class="{'text-secondary': activeTab.slug === 'auth'}" class="w-8 h-8"/>
                    <UserIcon v-if="tab.slug === 'admin'" :class="{'text-secondary': activeTab.slug === 'admin'}" class="w-8 h-8"/>
                    <DocumentMagnifyingGlassIcon v-if="tab.slug === 'seo'" :class="{'text-secondary': activeTab.slug === 'seo'}" class="w-8 h-8"/>
                    <PaintBrushIcon v-if="tab.slug === 'themes'" :class="{'text-secondary': activeTab.slug === 'themes'}" class="w-8 h-8"/>
                    <DocumentTextIcon v-if="tab.slug === 'blog'" :class="{'text-secondary': activeTab.slug === 'blog'}" class="w-8 h-8"/>
                    <EllipsisHorizontalIcon v-if="tab.slug === 'more'" :class="{'text-secondary': activeTab.slug === 'more'}" class="w-8 h-8"/>
                    <span class="block font-bold" :class="{'text-secondary': tab.slug === activeTab.slug}">{{ $t(tab.title) }}</span>
                </div>
            </div>
        </div>
        <!-- The feature items section-->
        <FeatureItems :activeTab="activeTab"/>
    </div>
</template>

<style scoped>

</style>
