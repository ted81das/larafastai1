<script setup>

defineProps({currentSubscription: String})

const plans = [
    {
        name: 'payment.plans.free',
        slug: 'free',
        description: 'payment.plans.free.description',
        price: '0',
        interval: 'month',
        features: [
            'Unlimited public projects',
            'Up to 5 private projects',
            'Basic analytics dashboard',
            'Community support',
            'Standard security features',
        ],
        // productId: 1,
        // variantId: 1,
    },
    {
        name: 'payment.plans.starter',
        slug: 'starter', // used by stripe, should be your stripe price id
        description: 'payment.plans.starter.description',
        price: '9.99',
        interval: 'month',
        features: [
            'Everything in "Free"',
            'Unlimited private projects',
            'Advanced analytics and reporting',
            'Priority email support',
            'Enhanced security features',
        ],
        bestseller: true,
        // productId: 193449, // for lemonsqueezy only
        // variantId: 255829, // for lemonsqueezy only
    },
    {
        name: 'payment.plans.pro',
        slug: 'pro', // used by stripe, should be your stripe price id
        description: 'payment.plans.pro.description',
        price: '19.99',
        interval: 'month',
        features: [
            'Everything in "Starter"',
            'Dedicated account manager',
            'Custom integrations',
            '24/7 phone and email support',
            'Advanced collaboration tools',
        ],
        // productId: 193449, // for lemonsqueezy only
        // variantId: 255829, // for lemonsqueezy only
    },
];
</script>

<template>
    <div id="pricing" class="py-8 sm:py-16 px-8">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl lg:text-center">
                <p class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{{ $t('payment.title') }}</p>
                <p class="mt-6 text-lg leading-8" v-html="$t('payment.description', { value: 7 })"></p>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 mx-auto max-w-6xl my-8">
            <div v-for="plan in plans" class="relative px-8 py-12 border border-base-200 rounded-3xl shadow-xl hover:shadow-2xl cursor-pointer" :class="{'border-secondary border-2': plan.bestseller}">
                <span v-if="plan.bestseller" class="absolute top-5 right-5 bg-secondary text-white rounded-full px-4 font-semibold sm:font-medium text-sm sm:text-base">bestseller</span>
                <p class="text-3xl font-extrabold mb-2">{{ $t(plan.name) }}</p>
                <p class="mb-6 h-16">
                    <span>{{ $t('Best For') }}: </span> <span>{{ $t(plan.description) }}</span></p>
                <p class="mb-6">
                    <span class="text-4xl font-extrabold">${{ plan.price }}</span>
                    <span v-if="plan.price !== '0'" class="text-base font-medium">/{{ plan.interval }}</span>
                </p>
                <a v-if="plan.price !== '0'" :href="$page.props.auth.user ? route('stripe.subscription.checkout', {price: plan.slug}) : route('register')"
                   class="mb-6 btn btn-secondary btn-wide text-center mx-auto flex">
                    {{ $t('Choose Plan') }}
                </a>
                <a v-else :href="route('register')"
                   class="mb-6 btn btn-secondary btn-wide text-center mx-auto flex">
                   {{ $t('Choose Plan') }}
                </a>
                <p class="text-sm mb-4">*{{ $t('payment.free-trial', { value: 7 }) }}</p>
                <ul>
                    <li v-for="feature in plan.features" class="flex">
                        - {{ $t(feature) }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
