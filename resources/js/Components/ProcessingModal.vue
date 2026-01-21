<template>
    <Modal :show="show" @close="handleClose" :closeable="!processing && !error">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{
                    error
                        ? "Processing Failed"
                        : processing
                        ? "Processing Lesson"
                        : "Complete"
                }}
            </h2>

            <div class="mt-4">
                <!-- Progress Bar -->
                <div
                    v-if="!error"
                    class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700"
                >
                    <div
                        class="bg-blue-600 h-4 rounded-full transition-all duration-300"
                        :style="{ width: progress + '%' }"
                    ></div>
                </div>

                <!-- Progress Percentage -->
                <p
                    v-if="!error"
                    class="text-sm text-gray-600 dark:text-gray-400 mt-2 text-center"
                >
                    {{ progress }}%
                </p>

                <!-- Current Stage -->
                <p
                    v-if="stage && !error"
                    class="text-sm text-gray-700 dark:text-gray-300 mt-4 text-center"
                >
                    {{ stage }}
                </p>

                <!-- Error Message -->
                <div
                    v-if="error"
                    class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md"
                >
                    <p class="text-sm text-red-600 dark:text-red-400">
                        {{ error }}
                    </p>
                </div>

                <!-- Information Text -->
                <div
                    v-if="!error && processing"
                    class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md"
                >
                    <p class="text-sm text-blue-600 dark:text-blue-400">
                        Please wait while we process your lesson. This may take
                        a few moments.
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex justify-end space-x-3">
                <SecondaryButton
                    v-if="!processing && !error"
                    @click="handleClose"
                >
                    Close
                </SecondaryButton>

                <SecondaryButton
                    v-if="processing && !error"
                    @click="handleCancel"
                >
                    Cancel
                </SecondaryButton>

                <DangerButton v-if="error" @click="handleClose">
                    Close
                </DangerButton>

                <PrimaryButton v-if="error" @click="handleRetry">
                    Retry
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { computed } from "vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";

const props = defineProps({
    show: Boolean,
    progress: {
        type: Number,
        default: 0,
    },
    stage: {
        type: String,
        default: "",
    },
    error: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["close", "cancel", "retry"]);

const processing = computed(() => {
    return props.progress > 0 && props.progress < 100 && !props.error;
});

const handleClose = () => {
    emit("close");
};

const handleCancel = () => {
    emit("cancel");
};

const handleRetry = () => {
    emit("retry");
};
</script>
