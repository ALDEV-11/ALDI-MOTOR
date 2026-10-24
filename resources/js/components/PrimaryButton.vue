<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
    >
        <slot />
    </button>
</template>
