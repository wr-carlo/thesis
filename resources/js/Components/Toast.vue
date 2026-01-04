<script setup>
import { useToast } from "@/Stores/useToast";

const { toasts, remove } = useToast();

const toastClasses = (type) => {
    const base =
        "flex items-center gap-3 p-4 rounded-lg shadow-lg border min-w-[300px] max-w-md";
    const types = {
        success:
            "bg-green-50 border-green-200 text-green-800 dark:bg-green-950 dark:border-green-900 dark:text-green-200",
        error: "bg-red-50 border-red-200 text-red-800 dark:bg-red-950 dark:border-red-900 dark:text-red-200",
        warning:
            "bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-950 dark:border-yellow-900 dark:text-yellow-200",
        info: "bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-950 dark:border-blue-900 dark:text-blue-200",
    };
    return `${base} ${types[type] || types.info}`;
};

const icon = (type) => {
    const icons = {
        success: "✓",
        error: "✕",
        warning: "⚠",
        info: "ℹ",
    };
    return icons[type] || icons.info;
};
</script>

<template>
    <Teleport to="body">
        <div class="fixed top-4 right-4 z-50 space-y-2">
            <TransitionGroup name="toast" tag="div">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    :class="toastClasses(toast.type)"
                >
                    <span class="text-lg font-semibold">{{
                        icon(toast.type)
                    }}</span>
                    <p class="flex-1 text-sm">{{ toast.message }}</p>
                    <button
                        @click="remove(toast.id)"
                        class="text-current opacity-70 hover:opacity-100 transition-opacity"
                    >
                        ✕
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.toast-enter-to {
    opacity: 1;
    transform: translateX(0);
}

.toast-leave-from {
    opacity: 1;
    transform: translateX(0);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>
