<script setup>

import Avatar from "@/Components/Data/Avatar.vue";
import Paragraph from "@/Components/Data/Paragraph.vue";
</script>

<template>
<div id="about" class="max-w-2xl mx-auto my-16 px-8">
    <div class="flex items-start">
        <Avatar class="shrink-0" src="https://placehold.co/100"/>
        <div class="shrink ml-4">
            <p class="font-bold text-lg">{{ $t('pages.home.about.title') }}</p>
            <Paragraph>{{ $t('pages.home.about.intro') }}</Paragraph>
        </div>
    </div>
    <div class="mt-4">
        <Paragraph>{{ $t('pages.home.about.description') }}</Paragraph>
    </div>
</div>
</template>

<style scoped>

</style>
