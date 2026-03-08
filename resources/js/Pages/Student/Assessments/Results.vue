<script setup>
import StudentLayout from "@/Layouts/StudentLayout.vue";
import { Head, Link, router, usePage, useForm } from "@inertiajs/vue3";
import { computed, ref, onMounted } from "vue";
import ProcessingModal from "@/Components/ProcessingModal.vue";

const props = defineProps({
    assessment: Object,
    attempt: Object,
    results: Object,
    items: Array,
    show_adaptive_button: Boolean,
    has_wrong_answers: Boolean,
});

const page = usePage();
const adaptiveLoading = ref(false);
const adaptiveError = ref(null);
const showAdaptiveModal = ref(false);

const showProcessingModal = ref(false);
const uploadProgress = ref(0);
const currentStage = ref("");

const adaptiveForm = useForm({
    multiple_choice_count: 0,
    identification_count: 0,
    true_or_false_count: 0,
});

onMounted(() => {
    if (page.props.errors?.error) {
        adaptiveError.value = page.props.errors.error;
    }
});

const showAdaptiveButton = computed(() => props.show_adaptive_button === true);

const openAdaptiveModal = () => {
    adaptiveForm.reset();
    showAdaptiveModal.value = true;
};

const closeAdaptiveModal = () => {
    showAdaptiveModal.value = false;
    adaptiveForm.reset();
};

const totalRequestedCounts = computed(() => {
    return (adaptiveForm.multiple_choice_count || 0) + 
           (adaptiveForm.identification_count || 0) + 
           (adaptiveForm.true_or_false_count || 0);
});

const isValidAdaptiveRequest = computed(() => {
    const total = totalRequestedCounts.value;
    const minRequired = props.results.wrong_answers || 0;
    const maxAllowed = props.results.total_questions || 0;
    return total >= minRequired && total <= maxAllowed;
});

const generateAdaptive = () => {
    if (!isValidAdaptiveRequest.value) return;

    adaptiveError.value = null;
    showAdaptiveModal.value = false;
    
    // Show Processing Modal immediately
    showProcessingModal.value = true;
    uploadProgress.value = 10;
    currentStage.value = "Analyzing mistakes and content...";

    adaptiveForm.post(
        route("student.assessments.adaptive", {
            assessment: props.assessment.id,
            attempt: props.attempt.id,
        }),
        {
            preserveScroll: true,
            onProgress: (progress) => {
                uploadProgress.value = Math.min(90, progress.percentage || 0);
                if (uploadProgress.value > 50) {
                    currentStage.value = "Generating adaptive questions...";
                }
            },
            onSuccess: () => {
                showProcessingModal.value = false;
                uploadProgress.value = 100;
            },
            onError: (errors) => {
                adaptiveError.value = errors.error || "Failed to generate adaptive assessment.";
                uploadProgress.value = 0;
            },
            onFinish: () => {
                adaptiveLoading.value = false;
            },
        }
    );
};

const handleProcessingClose = () => {
    showProcessingModal.value = false;
    if (!uploadProgress.value || adaptiveError.value) {
        adaptiveError.value = null;
    }
};

const cancelAdaptiveUpload = () => {
    adaptiveForm.cancel();
    showProcessingModal.value = false;
    adaptiveError.value = null;
    uploadProgress.value = 0;
    currentStage.value = "";
};

const retryAdaptiveUpload = () => {
    adaptiveError.value = null;
    uploadProgress.value = 0;
    currentStage.value = "";
    generateAdaptive();
};

const currentQuestionIndex = ref(0);

const totalQuestions = computed(() => props.items?.length || 0);

const currentQuestion = computed(() => {
    return props.items?.[currentQuestionIndex.value] || null;
});

const isFirstQuestion = computed(() => currentQuestionIndex.value === 0);

const isLastQuestion = computed(() => currentQuestionIndex.value === totalQuestions.value - 1);

// Helper to get choices as array
const getChoices = (item) => {
    if (!item.choices) return [];
    if (Array.isArray(item.choices)) return item.choices;
    if (typeof item.choices === 'string') {
        try {
            return JSON.parse(item.choices);
        } catch (e) {
            return [];
        }
    }
    return [];
};

const formatDate = (dateString) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const scoreColor = computed(() => {
    const score = props.results.score;
    if (score >= 75) return 'text-green-600 dark:text-green-400';
    if (score >= 50) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
});

const scoreBgColor = computed(() => {
    const score = props.results.score;
    if (score >= 75) return 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
    if (score >= 50) return 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';
    return 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
});

const nextQuestion = () => {
    if (currentQuestionIndex.value < totalQuestions.value - 1) {
        currentQuestionIndex.value++;
    }
};

const previousQuestion = () => {
    if (currentQuestionIndex.value > 0) {
        currentQuestionIndex.value--;
    }
};

const goToQuestion = (index) => {
    if (index >= 0 && index < totalQuestions.value) {
        currentQuestionIndex.value = index;
    }
};
</script>

<template>
    <StudentLayout>
        <Head :title="`Results - ${assessment.title}`" />

        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center justify-end mb-4">
                    <Link
                        :href="route('student.assessments.history', assessment.id)"
                        class="inline-flex items-center justify-center px-4 py-2 bg-blue-500 text-white text-sm font-medium rounded-lg hover:bg-blue-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        View All Attempts
                    </Link>
                </div>

                <div class="card p-6">
                    <h1
                        class="text-2xl font-bold text-text-primary dark:text-text-inverted mb-2"
                    >
                        Assessment Results
                    </h1>
                    <div class="text-sm text-text-secondary space-y-1">
                        <p>
                            <span class="font-medium">Assessment:</span>
                            {{ assessment.title }}
                        </p>
                        <p>
                            <span class="font-medium">Subject:</span>
                            {{ assessment.subject.name }}
                            ({{ assessment.subject.code }})
                        </p>
                        <p>
                            <span class="font-medium">Lesson:</span>
                            {{ assessment.lesson.title }}
                        </p>
                        <p>
                            <span class="font-medium">Attempt:</span>
                            #{{ attempt.attempt_no }} - {{ formatDate(attempt.created_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Score Summary -->
            <div
                class="mb-6 card p-6"
                :class="scoreBgColor"
            >
                <div class="text-center">
                    <div class="text-sm text-text-secondary mb-2">Your Score</div>
                    <div
                        class="text-5xl font-bold mb-2"
                        :class="scoreColor"
                    >
                        {{ results.score }}%
                    </div>
                    <div class="text-sm text-text-secondary">
                        {{ results.correct_answers }} out of
                        {{ results.total_questions }} correct
                    </div>
                    <div class="mt-4 pt-4 border-t border-border-light dark:border-border-dark">
                        <div class="grid grid-cols-4 gap-4 text-sm">
                            <div>
                                <div class="text-text-secondary">Total Questions</div>
                                <div
                                    class="text-lg font-semibold text-text-primary dark:text-text-inverted"
                                >
                                    {{ results.total_questions }}
                                </div>
                            </div>
                            <div>
                                <div class="text-green-600 dark:text-green-400">Correct</div>
                                <div
                                    class="text-lg font-semibold text-green-600 dark:text-green-400"
                                >
                                    {{ results.correct_answers }}
                                </div>
                            </div>
                            <div>
                                <div class="text-red-600 dark:text-red-400">Incorrect</div>
                                <div
                                    class="text-lg font-semibold text-red-600 dark:text-red-400"
                                >
                                    {{ results.wrong_answers }}
                                </div>
                            </div>
                            <div>
                                <div class="text-gray-600 dark:text-gray-400">No Answer</div>
                                <div
                                    class="text-lg font-semibold text-gray-600 dark:text-gray-400"
                                >
                                    {{ results.no_answer }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Adaptive Assessment Button -->
            <div
                v-if="showAdaptiveButton"
                class="mb-6 card p-6 border-2 border-dashed border-accent-primary bg-accent-primary/5 dark:bg-accent-primary/10"
            >
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-full bg-accent-primary/20 dark:bg-accent-primary/30 flex items-center justify-center"
                        >
                            <svg
                                class="w-6 h-6 text-accent-primary"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-text-primary dark:text-text-inverted">
                                Practice Wrong Answers
                            </h3>
                            <p class="text-sm text-text-secondary">
                                Generate a custom assessment focused on your learning gaps.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="openAdaptiveModal"
                        :disabled="adaptiveForm.processing || showProcessingModal"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent-primary text-white font-medium rounded-lg hover:bg-accent-muted transition-colors disabled:opacity-70 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-accent-primary focus:ring-offset-2"
                    >
                        <span>Generate Adaptive Assessment</span>
                    </button>
                </div>
                <p
                    v-if="adaptiveError && !showProcessingModal"
                    class="mt-4 text-sm text-red-600 dark:text-red-400"
                >
                    {{ adaptiveError }}
                </p>
            </div>

            <!-- Processing Modal -->
            <ProcessingModal
                :show="showProcessingModal"
                type="adaptive"
                :progress="uploadProgress"
                :stage="currentStage"
                :error="adaptiveError"
                @close="handleProcessingClose"
                @cancel="cancelAdaptiveUpload"
                @retry="retryAdaptiveUpload"
            />

            <!-- Adaptive Generation Settings Modal -->
            <div v-if="showAdaptiveModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeAdaptiveModal"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-text-primary dark:text-text-inverted" id="modal-title">
                                        Customize Adaptive Practice
                                    </h3>
                                    <div class="mt-2 mb-4">
                                        <p class="text-sm text-text-secondary">
                                            How many questions do you want to practice? You must select at least <span class="font-bold">{{ results.wrong_answers }}</span> (your mistakes) and at most <span class="font-bold">{{ results.total_questions }}</span> (total parent items).
                                        </p>
                                    </div>

                                    <div class="space-y-4">
                                        <div>
                                            <label for="mcq_count" class="block text-sm font-medium text-text-primary dark:text-text-inverted">Multiple Choice</label>
                                            <input type="number" min="0" id="mcq_count" v-model.number="adaptiveForm.multiple_choice_count" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-accent-primary focus:border-accent-primary sm:text-sm">
                                        </div>
                                        <div>
                                            <label for="identification_count" class="block text-sm font-medium text-text-primary dark:text-text-inverted">Identification</label>
                                            <input type="number" min="0" id="identification_count" v-model.number="adaptiveForm.identification_count" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-accent-primary focus:border-accent-primary sm:text-sm">
                                        </div>
                                        <div>
                                            <label for="tf_count" class="block text-sm font-medium text-text-primary dark:text-text-inverted">True/False</label>
                                            <input type="number" min="0" id="tf_count" v-model.number="adaptiveForm.true_or_false_count" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-accent-primary focus:border-accent-primary sm:text-sm">
                                        </div>
                                    </div>

                                    <div class="mt-4 p-3 rounded-md" :class="isValidAdaptiveRequest ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'">
                                        <p class="text-sm font-medium" :class="isValidAdaptiveRequest ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'">
                                            Total Selected: {{ totalRequestedCounts }} 
                                            <span v-if="!isValidAdaptiveRequest && totalRequestedCounts < results.wrong_answers">(Requires {{ results.wrong_answers - totalRequestedCounts }} more)</span>
                                            <span v-if="!isValidAdaptiveRequest && totalRequestedCounts > results.total_questions">(Exceeds by {{ totalRequestedCounts - results.total_questions }})</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" @click="generateAdaptive" :disabled="!isValidAdaptiveRequest || adaptiveForm.processing" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-accent-primary text-base font-medium text-white hover:bg-accent-muted focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent-primary sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                Generate
                            </button>
                            <button type="button" @click="closeAdaptiveModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Question Results -->
            <div>
                <div class="mb-6 card p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-between text-sm mb-3">
                        <span class="text-text-primary dark:text-text-inverted font-medium">
                            Question {{ currentQuestionIndex + 1 }} of {{ totalQuestions }}
                        </span>
                        <span class="text-text-secondary">
                            Reviewing Results
                        </span>
                    </div>
                    <!-- Progress Bar -->
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div
                            class="bg-accent-primary h-2 rounded-full transition-all duration-300"
                            :style="{ width: `${((currentQuestionIndex + 1) / totalQuestions) * 100}%` }"
                        ></div>
                    </div>
                </div>

                <div
                    v-if="currentQuestion"
                    class="card p-6 mb-6"
                    :class="{
                        'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/10':
                            currentQuestion.is_correct,
                        'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/10':
                            !currentQuestion.is_correct && currentQuestion.student_answer !== null,
                    }"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-semibold text-sm"
                            :class="
                                currentQuestion.is_correct
                                    ? 'bg-green-500 text-white'
                                    : 'bg-red-500 text-white'
                            "
                        >
                            {{ currentQuestionIndex + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3
                                class="text-lg font-semibold text-text-primary dark:text-text-inverted mb-4"
                            >
                                {{ currentQuestion.question }}
                            </h3>

                            <!-- Multiple Choice Results -->
                            <div
                                v-if="currentQuestion.type === 'multiple_choice'"
                                class="space-y-2 mb-4"
                            >
                                <div
                                    v-for="(choice, choiceIndex) in getChoices(currentQuestion)"
                                    :key="choiceIndex"
                                    :class="[
                                        'flex items-center p-3 rounded-lg border',
                                        choice === currentQuestion.correct_answer
                                            ? 'border-green-500 bg-green-100 dark:bg-green-900/30'
                                            : choice === currentQuestion.student_answer && !currentQuestion.is_correct
                                            ? 'border-red-500 bg-red-100 dark:bg-red-900/30'
                                            : 'border-border-light dark:border-border-dark',
                                    ]"
                                >
                                    <span
                                        v-if="choice === currentQuestion.correct_answer"
                                        class="mr-2 text-green-600 dark:text-green-400 font-bold"
                                    >
                                        ✓
                                    </span>
                                    <span
                                        v-else-if="
                                            choice === currentQuestion.student_answer && !currentQuestion.is_correct
                                        "
                                        class="mr-2 text-red-600 dark:text-red-400 font-bold"
                                    >
                                        ✗
                                    </span>
                                    <span
                                        class="text-text-primary dark:text-text-inverted flex-1"
                                    >
                                        {{ choice }}
                                    </span>
                                    <span
                                        v-if="choice === currentQuestion.correct_answer"
                                        class="text-xs font-medium text-green-600 dark:text-green-400"
                                    >
                                        Correct Answer
                                    </span>
                                </div>
                            </div>

                            <!-- Identification Results -->
                            <div v-else-if="currentQuestion.type === 'identification'" class="space-y-3 mb-4">
                                <div>
                                    <div
                                        class="text-sm font-medium text-text-secondary mb-1"
                                    >
                                        Your Answer:
                                    </div>
                                    <div
                                        class="p-3 rounded-lg border"
                                        :class="
                                            currentQuestion.is_correct
                                                ? 'border-green-500 bg-green-100 dark:bg-green-900/30 text-green-900 dark:text-green-100'
                                                : 'border-red-500 bg-red-100 dark:bg-red-900/30 text-red-900 dark:text-red-100'
                                        "
                                    >
                                        {{ currentQuestion.student_answer || '(No answer)' }}
                                    </div>
                                </div>
                                <div>
                                    <div
                                        class="text-sm font-medium text-text-secondary mb-1"
                                    >
                                        Correct Answer:
                                    </div>
                                    <div
                                        class="p-3 rounded-lg border border-green-500 bg-green-100 dark:bg-green-900/30 text-green-900 dark:text-green-100"
                                    >
                                        {{ currentQuestion.correct_answer }}
                                    </div>
                                </div>
                            </div>

                            <!-- True/False Results -->
                            <div v-else-if="currentQuestion.type === 'true_or_false'" class="space-y-3 mb-4">
                                <div>
                                    <div
                                        class="text-sm font-medium text-text-secondary mb-1"
                                    >
                                        Your Answer:
                                    </div>
                                    <div
                                        class="p-3 rounded-lg border"
                                        :class="
                                            currentQuestion.is_correct
                                                ? 'border-green-500 bg-green-100 dark:bg-green-900/30 text-green-900 dark:text-green-100'
                                                : 'border-red-500 bg-red-100 dark:bg-red-900/30 text-red-900 dark:text-red-100'
                                        "
                                    >
                                        {{ currentQuestion.student_answer || '(No answer)' }}
                                    </div>
                                </div>
                                <div>
                                    <div
                                        class="text-sm font-medium text-text-secondary mb-1"
                                    >
                                        Correct Answer:
                                    </div>
                                    <div
                                        class="p-3 rounded-lg border border-green-500 bg-green-100 dark:bg-green-900/30 text-green-900 dark:text-green-100"
                                    >
                                        {{ currentQuestion.correct_answer }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex items-center justify-between pt-6 border-t border-border-light dark:border-border-dark">
                    <button
                        type="button"
                        @click="previousQuestion"
                        :disabled="isFirstQuestion"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition-colors',
                            isFirstQuestion
                                ? 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                                : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600',
                        ]"
                    >
                        ← Previous
                    </button>

                    <div class="flex gap-2 flex-wrap justify-center max-w-md">
                        <button
                            v-for="(item, index) in items"
                            :key="item.id"
                            type="button"
                            @click="goToQuestion(index)"
                            :class="[
                                'w-8 h-8 rounded-full text-sm font-medium transition-colors',
                                index === currentQuestionIndex
                                    ? 'bg-accent-primary text-white'
                                    : item.is_correct
                                    ? 'bg-green-500 text-white hover:bg-green-600'
                                    : 'bg-red-500 text-white hover:bg-red-600',
                            ]"
                            :title="`Question ${index + 1}`"
                        >
                            {{ index + 1 }}
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="nextQuestion"
                        :disabled="isLastQuestion"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition-colors',
                            isLastQuestion
                                ? 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                                : 'bg-accent-primary text-white hover:bg-accent-muted',
                        ]"
                    >
                        Next →
                    </button>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
