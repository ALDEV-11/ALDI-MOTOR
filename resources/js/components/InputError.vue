<script setup>
defineProps({
    message: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div v-show="message">
        <p class="text-xs text-red-600 mt-1 font-medium">
            {{ message }}
        </p>
    </div>
</template>
