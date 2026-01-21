<script setup>
import StudentLayout from "@/Layouts/StudentLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    assessment: Object,
    attempt: Object,
    results: Object,
    items: Array,
});

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
                <div class="flex items-center gap-3 mb-4">
                    <Link
                        :href="route('student.assessments.index')"
                        class="inline-flex items-center justify-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                    >
                        ← Back to Assessments
                    </Link>
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
