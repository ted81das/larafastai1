<script setup>

</script>

<template>
    <div class="relative isolate overflow-hidden px-6 py-24 sm:py-24 lg:overflow-visible lg:px-0">
        <div class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 lg:mx-0 lg:max-w-none lg:grid-cols-2 lg:items-start lg:gap-y-10">
            <div class="lg:col-span-2 lg:col-start-1 lg:row-start-1 lg:mx-auto lg:grid lg:w-full lg:max-w-7xl lg:grid-cols-2 lg:gap-x-8 lg:px-8">
                <div class="lg:pr-4">
                    <div class="lg:max-w-lg">
                        <p class="text-base font-semibold leading-7">{{ $t('pages.home.content-with-image.subtitle') }}</p>
                        <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{{ $t('pages.home.content-with-image.title') }}</h1>
                        <div class="flex flex-col gap-y-8 mt-8" v-html="$t('pages.home.content-with-image.description')"></div>
                    </div>
                </div>
            </div>
            <div class="-ml-12 -mt-12 p-12 lg:sticky lg:top-4 lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:overflow-hidden">
                <img class="w-full max-w-none rounded-xl"
                     src="https://placehold.co/400" alt="">
            </div>
        </div>
    </div>

</template>

<style scoped>

</style>
