<script setup>

</script>

<template>
    <div class="mt-8 flex items-center">
        <div class="avatar-group -space-x-6 rtl:space-x-reverse">
            <div class="avatar">
                <div class="w-12">
                    <img src="https://placehold.co/100" />
                </div>
            </div>
            <div class="avatar">
                <div class="w-12">
                    <img src="https://placehold.co/100" />
                </div>
            </div>
            <div class="avatar">
                <div class="w-12">
                    <img src="https://placehold.co/100" />
                </div>
            </div>
            <div class="avatar">
                <div class="w-12">
                    <img src="https://placehold.co/100" />
                </div>
            </div>
            <div class="avatar">
                <div class="w-12">
                    <img src="https://placehold.co/100" />
                </div>
            </div>
        </div>
        <div class="flex flex-col items-start ml-2">
            <div class="rating">
                <input type="radio" name="rating-2" class="mask mask-star-2 bg-yellow-400" />
                <input type="radio" name="rating-2" class="mask mask-star-2 bg-yellow-400" />
                <input type="radio" name="rating-2" class="mask mask-star-2 bg-yellow-400" />
                <input type="radio" name="rating-2" class="mask mask-star-2 bg-yellow-400" />
                <input type="radio" name="rating-2" class="mask mask-star-2 bg-yellow-400" />
            </div>
            <div class="mt-1">
                <p v-html="$t('pages.home.hero.trusted-by', { value: 987 })"></p>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
