<script setup>
import Modal from './Modal.vue';
import PrimaryButton from './PrimaryButton.vue';
import SecondaryButton from './SecondaryButton.vue';
import DangerButton from './DangerButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirm Action',
    },
    message: {
        type: String,
        required: true,
    },
    confirmText: {
        type: String,
        default: 'Confirm',
    },
    cancelText: {
        type: String,
        default: 'Cancel',
    },
    variant: {
        type: String,
        default: 'danger', // 'danger', 'warning', 'info'
        validator: (value) => ['danger', 'warning', 'info'].includes(value),
    },
});

const emit = defineEmits(['close', 'confirm', 'cancel']);

const handleConfirm = () => {
    emit('confirm');
    emit('close');
};

const handleCancel = () => {
    emit('cancel');
    emit('close');
};

const variantClasses = {
    danger: 'text-red-600 dark:text-red-400',
    warning: 'text-yellow-600 dark:text-yellow-400',
    info: 'text-blue-600 dark:text-blue-400',
};
</script>

<template>
    <Modal :show="show" @close="handleCancel" max-width="md">
        <div class="p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg
                        v-if="variant === 'danger'"
                        class="h-6 w-6 text-red-600 dark:text-red-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                    <svg
                        v-else-if="variant === 'warning'"
                        class="h-6 w-6 text-yellow-600 dark:text-yellow-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                    <svg
                        v-else
                        class="h-6 w-6 text-blue-600 dark:text-blue-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-semibold text-text-primary dark:text-text-inverted">
                        {{ title }}
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm text-text-secondary dark:text-text-secondary-dark">
                            {{ message }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton type="button" @click="handleCancel">
                    {{ cancelText }}
                </SecondaryButton>
                <DangerButton
                    v-if="variant === 'danger'"
                    type="button"
                    @click="handleConfirm"
                >
                    {{ confirmText }}
                </DangerButton>
                <PrimaryButton
                    v-else
                    type="button"
                    @click="handleConfirm"
                >
                    {{ confirmText }}
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

