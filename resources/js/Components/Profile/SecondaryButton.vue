<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="text-white btn btn-secondary btn-sm inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest shadow-xs disabled:opacity-25 transition ease-in-out duration-150">
        <slot />
    </button>
</template>
