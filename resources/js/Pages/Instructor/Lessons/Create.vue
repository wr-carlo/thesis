<template>
    <InstructorLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                                Upload New Lesson
                            </h2>
                            <Link
                                :href="route('instructor.lessons.index')"
                                class="text-blue-600 hover:text-blue-800"
                            >
                                Back to Lessons
                            </Link>
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
                                <InputError class="mt-2" :message="form.errors.subject_id" />
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
                                <InputError class="mt-2" :message="form.errors.title" />
                            </div>

                            <!-- File Upload -->
                            <div>
                                <InputLabel for="file" value="Lesson File" />
                                <div
                                    class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md hover:border-indigo-500 transition"
                                    @dragover.prevent="isDragging = true"
                                    @dragleave.prevent="isDragging = false"
                                    @drop.prevent="handleDrop"
                                    :class="{ 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20': isDragging }"
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
                                        <div class="flex text-sm text-gray-600 dark:text-gray-400">
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
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            DOCX, PDF, PPTX, TXT up to 10MB
                                        </p>
                                        <p v-if="fileName" class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-2">
                                            Selected: {{ fileName }}
                                        </p>
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="form.errors.file" />
                            </div>

                            <!-- Question Configuration -->
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                    Assessment Configuration
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <!-- Multiple Choice Count -->
                                    <div>
                                        <InputLabel for="multiple_choice_count" value="Multiple Choice Questions" />
                                        <TextInput
                                            id="multiple_choice_count"
                                            v-model.number="form.multiple_choice_count"
                                            type="number"
                                            min="0"
                                            class="mt-1 block w-full"
                                            required
                                        />
                                        <InputError class="mt-2" :message="form.errors.multiple_choice_count" />
                                    </div>

                                    <!-- Identification Count -->
                                    <div>
                                        <InputLabel for="identification_count" value="Identification Questions" />
                                        <TextInput
                                            id="identification_count"
                                            v-model.number="form.identification_count"
                                            type="number"
                                            min="0"
                                            class="mt-1 block w-full"
                                            required
                                        />
                                        <InputError class="mt-2" :message="form.errors.identification_count" />
                                    </div>

                                    <!-- True/False Count -->
                                    <div>
                                        <InputLabel for="true_or_false_count" value="True/False Questions" />
                                        <TextInput
                                            id="true_or_false_count"
                                            v-model.number="form.true_or_false_count"
                                            type="number"
                                            min="0"
                                            class="mt-1 block w-full"
                                            required
                                        />
                                        <InputError class="mt-2" :message="form.errors.true_or_false_count" />
                                    </div>
                                </div>

                                <!-- Difficulty Level -->
                                <div class="mt-4">
                                    <InputLabel for="difficulty" value="Difficulty Level" />
                                    <select
                                        id="difficulty"
                                        v-model="form.difficulty"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        required
                                    >
                                        <option value="easy">Easy</option>
                                        <option value="medium">Medium</option>
                                        <option value="hard">Hard</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.difficulty" />
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center justify-end">
                                <PrimaryButton
                                    type="submit"
                                    :disabled="form.processing"
                                    class="ml-4"
                                >
                                    <span v-if="form.processing">Processing...</span>
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
            @cancel="cancelUpload"
            @retry="retryUpload"
        />
    </InstructorLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import InstructorLayout from '@/Layouts/InstructorLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProcessingModal from '@/Components/ProcessingModal.vue';

const props = defineProps({
    subjects: Array,
});

const form = useForm({
    subject_id: '',
    title: '',
    file: null,
    multiple_choice_count: 5,
    identification_count: 3,
    true_or_false_count: 2,
    difficulty: 'medium',
});

const fileName = ref('');
const isDragging = ref(false);
const showProcessingModal = ref(false);
const uploadProgress = ref(0);
const currentStage = ref('');
const uploadError = ref('');

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
    showProcessingModal.value = true;
    uploadProgress.value = 10;
    currentStage.value = 'Uploading file...';

    form.post(route('instructor.lessons.store'), {
        onSuccess: () => {
            showProcessingModal.value = false;
        },
        onError: (errors) => {
            uploadError.value = errors.error || 'An error occurred during upload';
            uploadProgress.value = 0;
        },
        onProgress: (progress) => {
            uploadProgress.value = Math.min(90, progress.percentage || 0);
        },
    });
};

const cancelUpload = () => {
    showProcessingModal.value = false;
    form.reset();
    fileName.value = '';
};

const retryUpload = () => {
    uploadError.value = '';
    submitForm();
};
</script>

