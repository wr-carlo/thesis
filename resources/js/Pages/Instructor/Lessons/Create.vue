<template>
    <InstructorLayout>
        <Head title="Create Assessment" />
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg"
                >
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2
                                class="text-2xl font-semibold text-gray-900 dark:text-white"
                            >
                                Upload New Lesson
                            </h2>
                        </div>

                        <form @submit.prevent="submitForm" class="space-y-6">
                            <!-- Subject Selection -->
                            <div>
                                <InputLabel for="subject_id" value="Subject" />
                                <select
                                    id="subject_id"
                                    v-model="form.subject_id"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    required
                                >
                                    <option value="">Select a subject</option>
                                    <option
                                        v-for="subject in subjects"
                                        :key="subject.id"
                                        :value="subject.id"
                                    >
                                        {{ subject.name }} ({{ subject.code }})
                                    </option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.subject_id"
                                />
                            </div>

                            <!-- Lesson Title -->
                            <div>
                                <InputLabel for="title" value="Lesson Title" />
                                <TextInput
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    placeholder="Enter lesson title"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.title"
                                />
                            </div>

                            <!-- File Upload -->
                            <div>
                                <InputLabel for="file" value="Lesson File" />
                                <div
                                    class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md hover:border-indigo-500 transition"
                                    @dragover.prevent="isDragging = true"
                                    @dragleave.prevent="isDragging = false"
                                    @drop.prevent="handleDrop"
                                    :class="{
                                        'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20':
                                            isDragging,
                                    }"
                                >
                                    <div class="space-y-1 text-center">
                                        <svg
                                            class="mx-auto h-12 w-12 text-gray-400"
                                            stroke="currentColor"
                                            fill="none"
                                            viewBox="0 0 48 48"
                                        >
                                            <path
                                                d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                        <div
                                            class="flex text-sm text-gray-600 dark:text-gray-400"
                                        >
                                            <label
                                                for="file"
                                                class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
                                            >
                                                <span>Upload a file</span>
                                                <input
                                                    id="file"
                                                    type="file"
                                                    class="sr-only"
                                                    accept=".docx,.pdf,.pptx,.txt"
                                                    @change="handleFileSelect"
                                                    required
                                                />
                                            </label>
                                            <p class="pl-1">or drag and drop</p>
                                        </div>
                                        <p
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            DOCX, PDF, PPTX, TXT up to 10MB
                                        </p>
                                        <p
                                            v-if="fileName"
                                            class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-2"
                                        >
                                            Selected: {{ fileName }}
                                        </p>
                                    </div>
                                </div>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.file"
                                />
                            </div>

                            <!-- Assessment Configuration -->
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                        Assessment Configuration
                                    </h3>
                                    
                                    <!-- HOTS Toggle -->
                                    <label class="flex items-center cursor-pointer">
                                        <div class="mr-3 text-sm font-medium" :class="hotsEnabled ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'">
                                            HOTS (Higher-Order)
                                        </div>
                                        <div class="relative">
                                            <input type="checkbox" v-model="hotsEnabled" class="sr-only" @change="handleHotsToggle" />
                                            <div class="block w-10 h-6 bg-gray-300 dark:bg-gray-700 rounded-full transition-colors" :class="{'bg-indigo-600 dark:bg-indigo-500': hotsEnabled}"></div>
                                            <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform" :class="{'transform translate-x-4 flex items-center justify-center': hotsEnabled}"></div>
                                        </div>
                                    </label>
                                </div>

                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                                    Define the number of questions per question type for each Bloom's cognitive level.
                                    <span v-if="hotsEnabled" class="font-medium text-indigo-600 dark:text-indigo-400">Currently configuring HOTS (Advance Question Types).</span>
                                    <span v-else class="font-medium text-emerald-600 dark:text-emerald-400">Currently configuring LOTS (Basic Question Types).</span>
                                </p>

                                <!-- LOTS Group -->
                                <div v-show="!hotsEnabled" class="space-y-6 animate-fade-in">
                                    <div v-for="levelParams in bloomLevels.slice(0, 3)" :key="levelParams.value" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800 shadow-sm relative overflow-hidden transition-all duration-300">
                                        <div class="absolute left-0 top-0 bottom-0 w-1" :class="levelParams.bgActiveClass.split(' ')[0]"></div>
                                        
                                        <div class="flex items-center gap-2 mb-3">
                                            <span :class="levelParams.badgeClass" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold">
                                                {{ levelParams.label }}
                                            </span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500">Level {{ levelParams.order }}</span>
                                        </div>

                                        <div class="grid grid-cols-3 gap-4">
                                            <div>
                                                <InputLabel :for="'mcq_' + levelParams.value" value="Multiple Choice" class="text-xs text-gray-500 dark:text-gray-400" />
                                                <TextInput :id="'mcq_' + levelParams.value" v-model.number="form.question_distribution[levelParams.value].mcq" type="number" min="0" class="mt-1 block w-full text-sm py-1.5" />
                                            </div>
                                            <div>
                                                <InputLabel :for="'id_' + levelParams.value" value="Identification" class="text-xs text-gray-500 dark:text-gray-400" />
                                                <TextInput :id="'id_' + levelParams.value" v-model.number="form.question_distribution[levelParams.value].identification" type="number" min="0" class="mt-1 block w-full text-sm py-1.5" />
                                            </div>
                                            <div>
                                                <InputLabel :for="'tf_' + levelParams.value" value="True/False" class="text-xs text-gray-500 dark:text-gray-400" />
                                                <TextInput :id="'tf_' + levelParams.value" v-model.number="form.question_distribution[levelParams.value].tf" type="number" min="0" class="mt-1 block w-full text-sm py-1.5" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- HOTS Group -->
                                <div v-show="hotsEnabled" class="space-y-6 animate-fade-in relative">
                                    <!-- "Advance question type" label -->
                                    <div class="absolute -top-3 left-4 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300 text-xs font-bold px-2 py-0.5 rounded shadow-sm z-10 border border-indigo-200 dark:border-indigo-800">
                                        Advance question type
                                    </div>

                                    <div v-for="levelParams in bloomLevels.slice(3, 6)" :key="levelParams.value" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800 shadow-sm relative overflow-hidden transition-all duration-300">
                                        <div class="absolute left-0 top-0 bottom-0 w-1" :class="levelParams.bgActiveClass.split(' ')[0]"></div>
                                        
                                        <div class="flex items-center gap-2 mb-3 mt-2">
                                            <span :class="levelParams.badgeClass" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold">
                                                {{ levelParams.label }}
                                            </span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500">Level {{ levelParams.order }}</span>
                                        </div>

                                        <div class="grid grid-cols-3 gap-4">
                                            <div>
                                                <InputLabel :for="'mcq_' + levelParams.value" value="Multiple Choice" class="text-xs text-gray-500 dark:text-gray-400" />
                                                <TextInput :id="'mcq_' + levelParams.value" v-model.number="form.question_distribution[levelParams.value].mcq" type="number" min="0" class="mt-1 block w-full text-sm py-1.5" />
                                            </div>
                                            <div>
                                                <InputLabel :for="'id_' + levelParams.value" value="Identification" class="text-xs text-gray-500 dark:text-gray-400" />
                                                <TextInput :id="'id_' + levelParams.value" v-model.number="form.question_distribution[levelParams.value].identification" type="number" min="0" class="mt-1 block w-full text-sm py-1.5" />
                                            </div>
                                            <div>
                                                <InputLabel :for="'tf_' + levelParams.value" value="True/False" class="text-xs text-gray-500 dark:text-gray-400" />
                                                <TextInput :id="'tf_' + levelParams.value" v-model.number="form.question_distribution[levelParams.value].tf" type="number" min="0" class="mt-1 block w-full text-sm py-1.5" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <InputError class="mt-4" :message="uploadError" v-if="uploadError.includes('configure at least one question')" />
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center justify-end">
                                <PrimaryButton
                                    type="submit"
                                    :disabled="form.processing || form.bloom_levels.length === 0"
                                    class="ml-4"
                                >
                                    <span v-if="form.processing"
                                        >Processing...</span
                                    >
                                    <span v-else>Generate Assessment</span>
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Processing Modal will be shown during upload -->
        <ProcessingModal
            :show="showProcessingModal"
            :progress="uploadProgress"
            :stage="currentStage"
            :error="uploadError"
            @close="handleClose"
            @cancel="cancelUpload"
            @retry="retryUpload"
        />

        <!-- Confirmation Modal -->
        <div v-if="showConfirmModal"  class="fixed  inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen  p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900/40 dark:bg-gray-900/80 backdrop-blur-sm transition-opacity" aria-hidden="true" @click="showConfirmModal = false"></div>

                <div class="relative inline-block bg-white dark:bg-gray-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-2xl sm:w-full border border-gray-100 dark:border-gray-800">
                    <div class="p-5 sm:p-6">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white tracking-tight" id="modal-title">
                                    Assessment Summary
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Review your question distribution before generating
                                </p>
                            </div>
                            <div class="flex items-center gap-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 px-4 py-2 rounded-full text-sm font-medium border border-indigo-100 dark:border-indigo-800/50">
                                <span>Total Questions</span>
                                <span class="bg-indigo-600 text-white dark:bg-indigo-500 px-2.5 py-0.5 rounded-full text-xs">{{ totalQuestions }}</span>
                            </div>
                        </div>
                        
                        <!-- Content -->
                        <div class="space-y-6 max-h-[45vh] overflow-y-auto pr-2">
                            <div v-if="Object.keys(summarizedDistribution).length > 0">
                                <div v-for="(types, level) in summarizedDistribution" :key="level" class="pb-6 mb-6 border-b border-gray-100 dark:border-gray-800/60 last:border-0 last:pb-0 last:mb-0">
                                    <div class="flex items-center gap-3 mb-4">
                                        <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200 capitalize">{{ level }}</h4>
                                        <div class="h-px flex-1 bg-gray-100 dark:bg-gray-800"></div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div v-if="types.mcq" class="flex flex-col bg-gray-50 dark:bg-gray-800/40 p-3 rounded-xl border border-gray-200/60 dark:border-gray-700/50 shadow-sm">
                                            <span class="text-[0.65rem] font-semibold text-gray-500 dark:text-gray-400 mb-0.5 uppercase tracking-wider">Multiple Choice</span>
                                            <span class="text-xl font-bold text-gray-900 dark:text-white">{{ types.mcq }}</span>
                                        </div>
                                        <div v-if="types.identification" class="flex flex-col bg-gray-50 dark:bg-gray-800/40 p-3 rounded-xl border border-gray-200/60 dark:border-gray-700/50 shadow-sm">
                                            <span class="text-[0.65rem] font-semibold text-gray-500 dark:text-gray-400 mb-0.5 uppercase tracking-wider">Identification</span>
                                            <span class="text-xl font-bold text-gray-900 dark:text-white">{{ types.identification }}</span>
                                        </div>
                                        <div v-if="types.tf" class="flex flex-col bg-gray-50 dark:bg-gray-800/40 p-3 rounded-xl border border-gray-200/60 dark:border-gray-700/50 shadow-sm">
                                            <span class="text-[0.65rem] font-semibold text-gray-500 dark:text-gray-400 mb-0.5 uppercase tracking-wider">True / False</span>
                                            <span class="text-xl font-bold text-gray-900 dark:text-white">{{ types.tf }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex justify-end gap-2 mt-8">
                            <button type="button" @click="showConfirmModal = false" class="px-5 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-transparent border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm">
                                Cancel
                            </button>
                            <button type="button" @click="confirmGenerate" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-all shadow-sm shadow-indigo-200 dark:shadow-none">
                                Confirm & Generate
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </InstructorLayout>
</template>

<script setup>
import { ref, watch, onUnmounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import InstructorLayout from "@/Layouts/InstructorLayout.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ProcessingModal from "@/Components/ProcessingModal.vue";
import { Head } from "@inertiajs/vue3";
const props = defineProps({
    subjects: Array,
});

// Bloom's Taxonomy levels configuration
const bloomLevels = [
    {
        value: "remember",
        label: "Remember",
        order: 1,
        description: "Recall facts, terms, definitions, and basic concepts",
        badgeClass: "bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300",
        borderActiveClass: "border-emerald-400 dark:border-emerald-600",
        bgActiveClass: "bg-emerald-600 dark:bg-emerald-900/10",
    },
    {
        value: "understand",
        label: "Understand",
        order: 2,
        description: "Explain, summarize, paraphrase, and interpret ideas",
        badgeClass: "bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300",
        borderActiveClass: "border-blue-400 dark:border-blue-600",
        bgActiveClass: "bg-blue-600 dark:bg-blue-900/10",
    },
    {
        value: "apply",
        label: "Apply",
        order: 3,
        description: "Use knowledge to solve problems in new situations",
        badgeClass: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300",
        borderActiveClass: "border-yellow-400 dark:border-yellow-600",
        bgActiveClass: "bg-yellow-600 dark:bg-yellow-900/10",
    },
    {
        value: "analyze",
        label: "Analyze",
        order: 4,
        description: "Compare, contrast, and examine relationships",
        badgeClass: "bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300",
        borderActiveClass: "border-orange-400 dark:border-orange-600",
        bgActiveClass: "bg-orange-600 dark:bg-orange-900/10",
    },
    {
        value: "evaluate",
        label: "Evaluate",
        order: 5,
        description: "Judge, justify, critique, and defend decisions",
        badgeClass: "bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300",
        borderActiveClass: "border-red-400 dark:border-red-600",
        bgActiveClass: "bg-red-600 dark:bg-red-900/10",
    },
    {
        value: "create",
        label: "Create",
        order: 6,
        description: "Design, propose, construct, and formulate new ideas",
        badgeClass: "bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300",
        borderActiveClass: "border-purple-400 dark:border-purple-600",
        bgActiveClass: "bg-purple-600 dark:bg-purple-900/10",
    },
];

const isBloomSelected = (value) => {
    return form.bloom_levels.includes(value);
};

// Enable/Disable HOTS mode
const hotsEnabled = ref(false);

const form = useForm({
    subject_id: "",
    title: "",
    file: null,
    question_distribution: {
        remember: { mcq: 0, identification: 0, tf: 0 },
        understand: { mcq: 0, identification: 0, tf: 0 },
        apply: { mcq: 0, identification: 0, tf: 0 },
        analyze: { mcq: 0, identification: 0, tf: 0 },
        evaluate: { mcq: 0, identification: 0, tf: 0 },
        create: { mcq: 0, identification: 0, tf: 0 },
    },
    bloom_levels: ["remember", "understand", "apply"],
});

const fileName = ref("");
const isDragging = ref(false);
const showProcessingModal = ref(false);
const showConfirmModal = ref(false);
const uploadProgress = ref(0);
const currentStage = ref("");
const uploadError = ref("");
const totalQuestions = ref(0);
const summarizedDistribution = ref({});

// Sound effects
const playSound = (type) => {
    const soundFile = type === 'success' ? '/sound-effect/success.mp3' : '/sound-effect/failed.mp3';
    const audio = new Audio(soundFile);
    audio.volume = 0.5;
    audio.play().catch(() => {
        // Silently ignore if browser blocks autoplay
    });
};

watch(showConfirmModal, (newValue) => {
    if (newValue) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = 'auto';
    }
});

onUnmounted(() => {
    document.body.style.overflow = 'auto'; // ensure it gets re-enabled when component is destroyed
});

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.file = file;
        fileName.value = file.name;
    }
};

const handleDrop = (event) => {
    isDragging.value = false;
    const file = event.dataTransfer.files[0];
    if (file) {
        form.file = file;
        fileName.value = file.name;
    }
};

const submitForm = () => {
    // Only send the levels that are actually selected based on the toggle.
    form.bloom_levels = hotsEnabled.value 
        ? ["analyze", "evaluate", "create"] 
        : ["remember", "understand", "apply"];

    // Ensure we only process selected distribution numbers, and clear the rest
    const activeLevels = form.bloom_levels;
    const finalDistribution = {};
    
    let hasQuestions = false;
    let total = 0;
    
    for (const level of activeLevels) {
        const counts = form.question_distribution[level];
        if (counts && (counts.mcq > 0 || counts.identification > 0 || counts.tf > 0)) {
            finalDistribution[level] = {};
            if (counts.mcq > 0) finalDistribution[level].mcq = counts.mcq;
            if (counts.identification > 0) finalDistribution[level].identification = counts.identification;
            if (counts.tf > 0) finalDistribution[level].tf = counts.tf;
            
            total += (counts.mcq || 0) + (counts.identification || 0) + (counts.tf || 0);
            hasQuestions = true;
        }
    }

    if (!hasQuestions) {
        uploadError.value = "Please configure at least one question for the selected levels.";
        return;
    }

    // Set confirmation details and show modal
    totalQuestions.value = total;
    summarizedDistribution.value = finalDistribution;
    showConfirmModal.value = true;
};

const confirmGenerate = () => {
    showConfirmModal.value = false;
    showProcessingModal.value = true;
    uploadProgress.value = 10;
    currentStage.value = "Uploading file...";

    // Use transform to only send active distribution and levels
    form.transform((data) => ({
        ...data,
        question_distribution: summarizedDistribution.value
    })).post(route("instructor.lessons.store"), {
        onSuccess: () => {
            playSound('success');
            showProcessingModal.value = false;
        },
        onError: (errors) => {
            playSound('failed');
            uploadError.value =
                errors.error || "An error occurred during upload";
            uploadProgress.value = 0;
        },
        onProgress: (progress) => {
            uploadProgress.value = Math.min(90, progress.percentage || 0);
        },
    });
};

const handleHotsToggle = () => {
    // We already use the state of hotsEnabled to drive the view visually.
    // The active levels are filtered during submission.
};

const handleClose = () => {
    showProcessingModal.value = false;
    // Only reset form if not processing and no error
    // This allows user to close modal without losing form data during processing
    if (!uploadProgress.value || uploadError.value) {
        uploadError.value = "";
    }
};

const cancelUpload = () => {
    form.cancel(); // Actually abort the in-progress HTTP request
    showProcessingModal.value = false;
    form.reset();
    fileName.value = "";
    uploadError.value = "";
    uploadProgress.value = 0;
    currentStage.value = "";
};

const retryUpload = () => {
    uploadError.value = "";
    uploadProgress.value = 0;
    currentStage.value = "";
    submitForm();
};
</script>
