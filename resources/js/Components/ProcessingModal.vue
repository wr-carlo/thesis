<template>
    <Modal :show="show" @close="handleClose" :closeable="!processing && !error">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{
                    error
                        ? "Processing Failed"
                        : processing
                        ? (type === 'adaptive' ? "Generating Assessment" : "Processing Lesson")
                        : "Complete"
                }}
            </h2>

            <div class="mt-4">
                <!-- Lottie Animation + Progress -->
                <div v-if="!error" class="flex flex-col items-center">
                    <!-- Lottie Animation -->
                    <div class="w-48 h-48 flex items-center justify-center">
                        <iframe
                            v-if="processing"
                            :src="type === 'adaptive' ? 'https://lottie.host/embed/15e622ed-4892-4591-983e-c832bbb09e76/oD908eaZLZ.lottie' : 'https://lottie.host/embed/8f34be24-f75a-42c9-968e-9bb15a9559bc/Z7FOgMJKo9.lottie'"
                            class="w-full h-full border-0"
                            style="border: none; background: transparent;"
                        ></iframe>
                        <!-- Success checkmark when done -->
                        <div v-else class="flex items-center justify-center w-20 h-20 rounded-full bg-green-100 dark:bg-green-900/30">
                            <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700 overflow-hidden">
                            <div
                                class="h-3 rounded-full transition-all duration-500 ease-out"
                                :class="progress >= 100 ? 'bg-green-500' : 'bg-blue-600'"
                                :style="{ width: progress + '%' }"
                            ></div>
                        </div>
                    </div>

                    <!-- Progress Percentage -->
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 text-center font-medium">
                        {{ progress }}%
                    </p>
                </div>

                <!-- Current Stage -->
                <p
                    v-if="stage && !error"
                    class="text-sm text-gray-700 dark:text-gray-300 mt-4 text-center"
                >
                    {{ stage }}
                </p>

                <!-- Error Animation + Message -->
                <div
                    v-if="error"
                    class="flex flex-col items-center"
                >
                    <!-- Failed Lottie Animation -->
                    <div class="w-48 h-48 flex items-center justify-center">
                        <iframe
                            src="https://lottie.host/embed/bb90344a-1ea3-497d-9954-2da7d6c3a269/m84jWyOHdb.lottie"
                            class="w-full h-full border-0"
                            style="border: none; background: transparent;"
                        ></iframe>
                    </div>

                    <div class="w-full mt-2 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md">
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ error }}
                        </p>
                    </div>
                </div>

                <!-- Information Text -->
                <div
                    v-if="!error && processing"
                    class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md"
                >
                    <p class="text-sm text-blue-600 dark:text-blue-400">
                        {{ type === 'adaptive' ? "Please wait while we generate your custom adaptive questions. This may take a few moments." : "Please wait while we process your lesson. This may take a few moments." }}
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
    type: {
        type: String,
        default: "lesson", // "lesson" or "adaptive"
    }
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
