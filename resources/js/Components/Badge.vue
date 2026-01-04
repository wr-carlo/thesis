<script setup>
const props = defineProps({
    variant: {
        type: String,
        default: "neutral", // primary, success, danger
    },
});

const variants = {
    neutral: "bg-surface-muted text-text-secondary dark:bg-surface-dark-muted dark:text-text-inverted",
    primary: "bg-accent-primary/10 text-accent-primary",
    success: "bg-green-100 text-green-700 dark:bg-green-900/60 dark:text-green-200",
    danger: "bg-red-100 text-red-700 dark:bg-red-900/60 dark:text-red-200",
};
</script>

<template>
    <span class="px-2 py-1 rounded text-xs font-medium" :class="variants[props.variant]">
        <slot />
    </span>
</template>

