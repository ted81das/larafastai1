<script setup>

defineProps({activeTab: {title: String, slug: String}})

import ListItem from "@/Components/Data/ListItem.vue";

</script>

<template>
    <div class="p-8 sm:p-16">
        <div v-if="activeTab.slug === 'payments'">
            <div class="max-w-6xl mx-auto flex flex-col sm:flex-row justify-between items-start">
                <div class="basis-1/2 w-full">
                    <div class="mb-4">
                        <p class="font-bold text-lg">{{ $t('Payments') }}</p>
                    </div>
                    <ul>
                        <ListItem :title="$t('Subscriptions')"/>
                        <ListItem :title="$t('Product Checkouts')"/>
                        <ListItem :title="$t('Customer Portal')"/>
                        <ListItem :title="$t('Webhooks')"/>
                        <ListItem :title="$t('Order Statistics')"/>
                        <ListItem :title="$t('Subscription Statistics')"/>
                    </ul>
                </div>
                <div class="basis-1/2 w-full flex flex-col items-center justify-center  mt-8 sm:mt-0">
                    <img src="https://placehold.co/400" class="shadow-xl rounded-xl border" />
                </div>
            </div>
        </div>
        <div v-if="activeTab.slug === 'auth'">
            <div class="max-w-6xl mx-auto flex flex-col sm:flex-row justify-between items-start">
                <div class="basis-1/2 w-full">
                    <div class="mb-4">
                        <p class="font-bold text-lg">{{ $t('Auth') }}</p>
                    </div>
                    <ul>
                        <ListItem :title="$t('Login/Registration')"/>
                        <ListItem :title="$t('User Dashboard')"/>
                        <ListItem :title="$t('Social Auth')"/>
                        <li class="ml-8">
                            <ul>
                                <ListItem :title="$t('Google')"/>
                                <ListItem :title="$t('GitHub')"/>
                                <ListItem :title="$t('Twitter')"/>
                            </ul>
                        </li>
                        <ListItem :title="$t('Magic link login')"/>
                        <ListItem :title="$t('Protected routes')"/>
                    </ul>
                </div>
                <div class="basis-1/2 w-full flex flex-col items-center justify-center  mt-8 sm:mt-0">
                    <img src="https://placehold.co/400" class="shadow-xl rounded-xl border" />
                </div>
            </div>
            <div class="max-w-6xl mx-auto mt-4">
                <p class="text-sm">
                    *Social Auth can be easily extended by adding other providers from <a class="link" target="_blank" href="https://socialiteproviders.com/about/">Socialite Providers</a>
                </p>
            </div>
        </div>
        <div v-if="activeTab.slug === 'admin'">
            <div class="max-w-6xl mx-auto flex flex-col sm:flex-row justify-between items-start">
                <div class="basis-1/2 w-full">
                    <div class="mb-4">
                        <p class="font-bold text-lg">{{ $t('Admin') }}</p>
                    </div>
                    <ul>
                        <ListItem :title="$t('Users Management')"/>
                        <ListItem :title="$t('Blog')"/>
                        <ListItem :title="$t('Articles')"/>
                        <ListItem :title="$t('Orders')"/>
                        <ListItem :title="$t('Subscriptions')"/>
                        <ListItem :title="$t('Statistics')"/>
                    </ul>
                </div>
                <div class="basis-1/2 w-full flex flex-col items-center justify-center  mt-8 sm:mt-0">
                    <img src="https://placehold.co/400" class="shadow-xl rounded-xl border" />
                </div>
            </div>
            <div class="max-w-6xl mx-auto mt-4">
                <p class="text-sm">
                    *Admin sections can be extended using <a class="link" target="_blank" href="https://filamentphp.com/docs/3.x/panels/getting-started">Filament Docs</a>
                </p>
            </div>
        </div>
        <div v-if="activeTab.slug === 'seo'">
            <div class="max-w-6xl mx-auto flex flex-col sm:flex-row justify-between items-start">
                <div class="basis-1/2 w-full">
                    <div class="mb-4">
                        <p class="font-bold text-lg">{{ $t('SEO') }}</p>
                    </div>
                    <ul>
                        <ListItem :title="$t('SEO Meta Tags')"/>
                        <ListItem :title="$t('Twitter Cards')"/>
                        <ListItem :title="$t('Dynamic Open Graph Images')"/>
                        <ListItem :title="$t('Open Graph tags for social media')"/>
                        <ListItem :title="$t('Automatic sitemap generation')"/>
                        <ListItem :title="$t('Schema.org markup')"/>
                        <ListItem :title="$t('Blog and Articles with SEO')"/>
                    </ul>
                </div>
                <div class="basis-1/2 w-full flex flex-col items-center justify-center  mt-8 sm:mt-0">
                    <img src="https://placehold.co/400" class="shadow-xl rounded-xl border" />
                </div>
            </div>
        </div>
        <div v-if="activeTab.slug === 'blog'">
            <div class="max-w-6xl mx-auto flex flex-col sm:flex-row justify-between items-start">
                <div class="basis-1/2 w-full">
                    <div class="mb-4">
                        <p class="font-bold text-lg">{{ $t('Blog') }}</p>
                    </div>
                    <ul>
                        <ListItem :title="$t('Blog Page')"/>
                        <ListItem :title="$t('Articles Page')"/>
                        <ListItem :title="$t('Articles management through admin')"/>
                        <ListItem :title="$t('SEO Optimized')"/>
                    </ul>
                </div>
                <div class="basis-1/2 w-full flex flex-col items-center justify-center  mt-8 sm:mt-0">
                    <img src="https://placehold.co/400" class="shadow-xl rounded-xl border" />
                </div>
            </div>
            <div class="max-w-6xl mx-auto mt-4">
                <p class="text-sm">
                    *See Example - <a class="link" target="_blank" href="https://larafast.com/blog">Blog</a>
                </p>
            </div>
        </div>
        <div v-if="activeTab.slug === 'themes'">
            <div class="max-w-6xl mx-auto flex flex-col sm:flex-row justify-between items-start">
                <div class="basis-1/2 w-full">
                    <div class="mb-4">
                        <p class="font-bold text-lg">{{ $t('Themes') }}</p>
                    </div>
                    <ul>
                        <ListItem :title="$t('Powered by DaisyUI')"/>
                        <ListItem :title="$t('30+ Themes out of the box')"/>
                        <ListItem :title="$t('Easy switching between themes')"/>
                        <ListItem :title="$t('Extendable and customizable')"/>
                    </ul>
                </div>
                <div class="basis-1/2 w-full flex flex-col items-center justify-center  mt-8 sm:mt-0">
                    <img src="https://placehold.co/400" class="shadow-xl rounded-xl border" />
                </div>
            </div>
            <div class="max-w-6xl mx-auto mt-4">
                <p class="text-sm">
                    *Check all themes here <a class="link" target="_blank" href="https://daisyui.com/docs/themes">Themes</a>
                </p>
            </div>
        </div>
        <div v-if="activeTab.slug === 'more'">
            <div class="max-w-6xl mx-auto flex flex-col sm:flex-row justify-between items-start">
                <div class="basis-1/2 w-full">
                    <div class="mb-4">
                        <p class="font-bold text-lg">{{ $t('More') }}</p>
                    </div>
                    <ul>
                        <ListItem :title="$t('Vue and Livewire Components')"/>
                        <ListItem :title="$t('Open AI Service')"/>
                        <ListItem :title="$t('LemonSqueezy Service')"/>
                        <ListItem :title="$t('Coming Soon Page')"/>
                        <ListItem :title="$t('Terms and Privacy Page')"/>
                        <ListItem :title="$t('Detailed Documentation')"/>
                    </ul>
                </div>
                <div class="basis-1/2 w-full flex flex-col items-center justify-center  mt-8 sm:mt-0">
                    <img src="https://placehold.co/400" class="shadow-xl rounded-xl border" />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
