<script setup>

import moment from "moment/moment.js";
defineProps({
    article: Object
})
</script>

<template>
    <article class="cursor-pointer flex max-w-xl flex-col items-start justify-between shadow-md overflow-hidden rounded-xl">
        <div class="h-48 w-full overflow-hidden border-b">
            <a :href="route('blog.article', {'article': article.slug})">
                <img class="object-contain object-center" :src="article.icon" :alt="article.title">
            </a>
        </div>
        <div class="p-4">
            <div class="flex items-center gap-x-4 text-xs">
                <time datetime="2020-03-16" class="text-gray-500">{{ moment(article.created_at).format('MMM Do, YYYY, HH:mm')}}</time>
            </div>
            <div class="group relative">
                <h3 class="mt-2 text-lg font-semibold leading-6 text-base-900 group-hover:text-base-600">
                    <a :href="route('blog.article', {'article': article.slug})">
                        <span class="absolute inset-0"></span>
                        {{ article.title }}
                    </a>
                </h3>
                <p class="mt-4 line-clamp-3 text-sm leading-6 text-base-600">
                    {{ article.seo_description }}
                </p>
            </div>
            <div class="relative mt-8 flex items-center gap-x-4">
                <img :src="article.user.profile_photo_url" alt="Author Icon" class="h-10 w-10 rounded-full bg-gray-50">
                <div class="text-sm leading-6">
                    <p class="font-semibold text-base-900">
                        <a href="#">
                            <span class="absolute inset-0"></span>
                            {{ article.user.name }}
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </article>
</template>

<style scoped>

</style>
