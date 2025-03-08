<script setup>

</script>

<template>
    <footer class="footer p-10 bg-base-200 text-base-content">
        <nav>
            <h6 class="footer-title">{{ $t('Services') }}</h6>
            <a class="link link-hover">{{ $t('Branding') }}</a>
            <a class="link link-hover">{{ $t('Design') }}</a>
            <a class="link link-hover">{{ $t('Marketing') }}</a>
            <a class="link link-hover">{{ $t('Advertisement') }}</a>
        </nav>
        <nav>
            <h6 class="footer-title">{{ $t('Company') }}</h6>
            <a class="link link-hover">{{ $t('About us') }}</a>
            <a class="link link-hover">{{ $t('Contact') }}</a>
            <a class="link link-hover">{{ $t('Jobs') }}</a>
            <a class="link link-hover">{{ $t('Press kit') }}</a>
        </nav>
        <nav>
            <h6 class="footer-title">{{ $t('Legal') }}</h6>
            <a :href="route('terms.show')" class="link link-hover">{{ $t('Terms of use') }}</a>
            <a :href="route('policy.show')" class="link link-hover">{{ $t('Privacy policy') }}</a>
            <a class="link link-hover">{{ $t('Cookie policy') }}</a>
        </nav>
        <form>
            <h6 class="footer-title">{{ $t('Newsletter') }}</h6>
            <fieldset class="form-control w-80">
                <label class="label">
                    <span class="label-text">{{ $t('Enter your email address') }}</span>
                </label>
                <div class="join">
                    <input type="text" placeholder="username@site.com" class="input input-bordered join-item" />
                    <button class="btn btn-secondary join-item">{{ $t('Subscribe') }}</button>
                </div>
            </fieldset>
        </form>
    </footer>
</template>

<style scoped>

</style>
