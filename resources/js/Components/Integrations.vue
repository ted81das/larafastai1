<script setup>
import {ArrowRightIcon} from "@heroicons/vue/24/outline/index.js";

</script>

<template>
    <section class="py-8 lg:py-20" id="integrations">
        <div class="container">
            <div class="text-center">
                <h2 class="text-4xl font-semibold">{{ $t('Integrations') }}</h2>
                <p class="mt-2 text-lg sm:text-center">
                    {{ $t('Here you can show the tools that you have used to built your SaaS') }}
                </p>
            </div>
            <div class="mt-12 rounded-lg bg-base-200 p-8 text-base-content lg:px-24 lg:py-14">
                <div class="grid items-center gap-8 lg:grid-cols-2">
                    <div class="inline-flex flex-col text-center lg:text-start">
                        <h3 class="text-3xl font-medium leading-snug">
                            {{ $t('Effortless expert Saas integrations, just a snap away.') }}
                        </h3>
                        <p class="mt-4 text-lg leading-normal">
                            {{ $t('Elevate efficiency with our SaaS platforms seamless integration feature. Connect tools effortlessly for a unified digital workspace.') }}
                        </p>

                        <div class="mt-8 flex justify-center lg:justify-start">
                            <button class="btn btn-primary">{{ $t('Quick Connect') }}</button>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-2 gap-14 md:grid-cols-3">
                        <div class="flex justify-center lg:justify-end">
                            <div
                                class="inline-flex h-24 w-24 items-center justify-center rounded-full bg-white shadow-sm"
                            >
                                <img alt="amazon" class="h-12 w-12" src="/images/integrations/amazon-mini.svg" />
                            </div>
                        </div>

                        <div class="flex justify-center lg:justify-end">
                            <div
                                class="inline-flex h-24 w-24 items-center justify-center rounded-full bg-white shadow-sm"
                            >
                                <img alt="slack" class="h-12 w-12" src="/images/integrations/slack.svg" />
                            </div>
                        </div>

                        <div class="flex justify-center lg:justify-end">
                            <div
                                class="inline-flex h-24 w-24 items-center justify-center rounded-full bg-white shadow-sm"
                            >
                                <img alt="openai" class="h-12 w-12" src="/images/integrations/openai.svg" />
                            </div>
                        </div>

                        <div class="flex justify-center lg:justify-end">
                            <div
                                class="inline-flex h-24 w-24 items-center justify-center rounded-full bg-white shadow-sm"
                            >
                                <img alt="meta" class="h-12 w-12" src="/images/integrations/meta-mini.svg" />
                            </div>
                        </div>

                        <div class="flex justify-center lg:justify-end">
                            <div
                                class="inline-flex h-24 w-24 items-center justify-center rounded-full bg-white shadow-sm"
                            >
                                <img alt="whatsapp" class="h-12 w-12" src="/images/integrations/whatsapp.svg" />
                            </div>
                        </div>

                        <div class="flex justify-center lg:justify-end">
                            <div
                                class="inline-flex h-24 w-24 items-center justify-center rounded-full bg-white shadow-sm"
                            >
                                <img alt="x" class="h-12 w-12" src="/images/integrations/x.svg" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-3">
                <div
                    class="card cursor-pointer border border-base-content/10 p-6 transition-all hover:shadow-sm"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-base-200">
                                <img alt="slack" class="h-8 w-8" src="/images/integrations/slack.svg" />
                            </div>
                            <h3 class="text-xl font-semibold">{{ $t('Slack') }}</h3>
                        </div>
                        <button class="btn btn-circle" aria-label="Details">
                            <ArrowRightIcon class="w-8" />
                        </button>
                    </div>
                    <p class="mt-4">
                        {{ $t('Streamline project discussions, share updates, and boost real-time collaboration effortlessly.') }}
                    </p>
                </div>

                <div
                    class="card cursor-pointer border border-base-content/10 p-6 transition-all hover:shadow-sm"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-base-200">
                                <img alt="google drive" class="h-8 w-8" src="/images/integrations/g-drive.svg" />
                            </div>
                            <h3 class="text-xl font-semibold">{{ $t('Google Drive') }}</h3>
                        </div>
                        <button class="btn btn-circle" aria-label="Details">
                            <ArrowRightIcon class="w-8" />
                        </button>
                    </div>

                    <p class="mt-4">
                        {{ $t('File management, Enhance collaboration, and Elevate your productivity with the convenience of this integrated solution') }}
                    </p>
                </div>

                <div
                    class="card cursor-pointer border border-base-content/10 p-6 transition-all hover:shadow-sm"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-base-200">
                                <img alt="bitbucket" class="h-8 w-8" src="/images/integrations/bitbucket-mini.svg" />
                            </div>
                            <h3 class="text-xl font-semibold">{{ $t('Bitbucket') }}</h3>
                        </div>
                        <button class="btn btn-circle" aria-label="Details">
                            <ArrowRightIcon class="w-8" />
                        </button>
                    </div>

                    <p class="mt-4">
                        {{ $t('Streamline version control, collaboration, and project management with ease. Embrace the power of seamless GitHub integration.') }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
