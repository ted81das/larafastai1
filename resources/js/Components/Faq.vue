<script setup>
import {ref} from "vue";

const selectedFaq = ref(null);

// Numbers represent index of each array item in php transalation file for FAQ content
const faqs = [
    {
        title: 'pages.home.faq.content.0.title',
        content: 'pages.home.faq.content.0.description'
    },
    {
        title: 'pages.home.faq.content.1.title',
        content: 'pages.home.faq.content.1.description'
    },
    {
        title: 'pages.home.faq.content.2.title',
        content: 'pages.home.faq.content.2.description'
    },
    {
        title: 'pages.home.faq.content.3.title',
        content: 'pages.home.faq.content.3.description'
    },
]

const toggleFaq = (index) => {
    selectedFaq.value = selectedFaq.value === index ? null : index;
}
</script>

<template>
    <div class="py-8 sm:py-16 px-8">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl lg:text-center">
                <p class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{{ $t('pages.home.faq.title') }}</p>
                <p class="mt-6 text-lg leading-8">{{ $t('pages.home.faq.description') }}</p>
            </div>
        </div>
        <div class="mx-auto max-w-3xl mt-8">
            <div class="join join-vertical w-full">
                <div v-for="(faq, index) in faqs" :key="index"
                     class="collapse collapse-arrow join-item border border-base-300">
                    <input type="radio" :id="'faq-' + index" name="my-accordion" :checked="selectedFaq === index"/>
                    <div class="collapse-title text-xl font-medium" @click="toggleFaq(index)">
                        {{ $t(faq.title) }}
                    </div>
                    <div class="collapse-content" v-show="selectedFaq === index">
                        <p>{{ $t(faq.content) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
