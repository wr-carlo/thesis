<script setup>
import StudentLayout from "@/Layouts/StudentLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    assessment: Object,
    summary: Object,
    attempts: Array,
});

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

const getScoreColor = (score) => {
    if (score >= 75) return 'text-green-600 dark:text-green-400';
    if (score >= 50) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const getScoreBgColor = (score) => {
    if (score >= 75) return 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
    if (score >= 50) return 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';
    return 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
};

const isBestAttempt = (attemptNo) => {
    return attemptNo === props.summary.best_attempt_no;
};

const isLatestAttempt = (index) => {
    return index === 0;
};
</script>

<template>
    <StudentLayout>

        <Head :title="`History - ${assessment.title}`" />

        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <Link :href="route('student.assessments.index')"
                    class="inline-flex items-center justify-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 mb-4">
                    ← Back to Assessments
                </Link>

                <div class="card p-6">
                    <h1 class="text-2xl font-bold text-text-primary dark:text-text-inverted mb-2">
                        Assessment History
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
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="card p-4">
                    <div class="text-sm text-text-secondary mb-1">Total Attempts</div>
                    <div class="text-2xl font-bold text-text-primary dark:text-text-inverted">
                        {{ summary.total_attempts }}
                    </div>
                </div>
                <div class="card p-4">
                    <div class="text-sm text-text-secondary mb-1">Best Score</div>
                    <div class="text-2xl font-bold" :class="getScoreColor(summary.best_score)">
                        {{ summary.best_score }}%
                    </div>
                    <div v-if="summary.best_attempt_no" class="text-xs text-text-secondary mt-1">
                        Attempt #{{ summary.best_attempt_no }}
                    </div>
                </div>
                <div class="card p-4">
                    <div class="text-sm text-text-secondary mb-1">Latest Attempt</div>
                    <div class="text-sm font-medium text-text-primary dark:text-text-inverted">
                        {{ formatDate(summary.latest_attempt_date) || 'N/A' }}
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="mb-6">
                <Link :href="route('student.assessments.show', assessment.id)"
                    class="inline-flex items-center justify-center px-4 py-2 bg-accent-primary text-white text-sm font-medium rounded-lg hover:bg-accent-muted transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-accent-primary focus:ring-offset-2">
                    Take Assessment
                </Link>
            </div>

            <!-- Attempts List -->
            <div class="space-y-4">
                <h2 class="text-xl font-semibold text-text-primary dark:text-text-inverted mb-4">
                    All Attempts
                </h2>

                <!-- Empty State -->
                <div v-if="attempts.length === 0" class="card p-12 text-center text-text-secondary">
                    <svg class="mx-auto h-16 w-16 mb-4 opacity-50" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-medium text-text-primary dark:text-text-inverted mb-2">
                        No attempts yet
                    </h3>
                    <p class="text-sm mb-4">
                        You haven't taken this assessment yet.
                    </p>
                    <Link :href="route('student.assessments.show', assessment.id)"
                        class="inline-flex items-center justify-center px-4 py-2 bg-accent-primary text-white text-sm font-medium rounded-lg hover:bg-accent-muted transition-colors duration-200">
                        Take Assessment Now
                    </Link>
                </div>

                <!-- Attempt Cards -->
                <div v-for="(attempt, index) in attempts" :key="attempt.id"
                    class="card p-6 hover:shadow-lg transition-all duration-200"
                    :class="getScoreBgColor(attempt.score)">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex-shrink-0 w-10 h-10 rounded-full bg-accent-primary text-white flex items-center justify-center font-bold text-sm">
                                #{{ attempt.attempt_no }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="text-lg font-semibold text-text-primary dark:text-text-inverted">
                                        Attempt {{ attempt.attempt_no }}
                                    </h3>
                                    <span v-if="isBestAttempt(attempt.attempt_no)"
                                        class="px-2 py-1 text-xs font-medium bg-yellow-500 text-white rounded">
                                        Best
                                    </span>
                                    <span v-if="isLatestAttempt(index)"
                                        class="px-2 py-1 text-xs font-medium bg-blue-500 text-white rounded">
                                        Latest
                                    </span>
                                </div>
                                <p class="text-sm text-text-secondary">
                                    {{ formatDate(attempt.created_at) }}
                                </p>
                            </div>
                        </div>
                        <div class="text-3xl font-bold" :class="getScoreColor(attempt.score)">
                            {{ attempt.score }}%
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-4 gap-4 mb-4">
                        <div>
                            <div class="text-xs text-text-secondary mb-1">Total</div>
                            <div class="text-sm font-semibold text-text-primary dark:text-text-inverted">
                                {{ attempt.total_questions }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-green-600 dark:text-green-400 mb-1">Correct</div>
                            <div class="text-sm font-semibold text-green-600 dark:text-green-400">
                                {{ attempt.correct_answers }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-red-600 dark:text-red-400 mb-1">Incorrect</div>
                            <div class="text-sm font-semibold text-red-600 dark:text-red-400">
                                {{ attempt.wrong_answers }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">No Answer</div>
                            <div class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                                {{ attempt.no_answer }}
                            </div>
                        </div>
                    </div>

                    <!-- View Results Button -->
                    <div class="pt-4 border-t border-border-light dark:border-border-dark">
                        <Link :href="route('student.assessments.results', {
                            assessment: assessment.id,
                            attempt: attempt.id
                        })"
                            class="inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 text-accent-primary border border-accent-primary text-sm font-medium rounded-lg hover:bg-accent-primary hover:text-white transition-colors duration-200">
                            View Results
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
