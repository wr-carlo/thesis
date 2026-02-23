<script setup>
import InstructorLayout from "@/Layouts/InstructorLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    assessment: Object,
    student: Object,
    summary: Object,
    attempts: Array,
});

const expandedAttemptIds = ref(new Set());

const toggleAccordion = (attemptId) => {
    const next = new Set(expandedAttemptIds.value);
    if (next.has(attemptId)) {
        next.delete(attemptId);
    } else {
        next.add(attemptId);
    }
    expandedAttemptIds.value = next;
};

const isAccordionOpen = (attemptId) => expandedAttemptIds.value.has(attemptId);

const hasAdaptives = (attempt) => {
    const list = attempt.adaptive_assessments || [];
    return Array.isArray(list) && list.length > 0;
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

const getScoreColor = (score) => {
    if (score >= 75) return "text-green-600 dark:text-green-400";
    if (score >= 50) return "text-yellow-600 dark:text-yellow-400";
    return "text-red-600 dark:text-red-400";
};

const getScoreBgColor = (score) => {
    if (score >= 75)
        return "bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800";
    if (score >= 50)
        return "bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800";
    return "bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800";
};

const isBestAttempt = (attemptId) => {
    return attemptId === props.summary.best_attempt_id;
};

const isLatestAttempt = (index) => {
    return index === props.attempts.length - 1;
};
</script>

<template>
    <InstructorLayout>
        <Head :title="`History - ${student.name} - ${assessment.title}`" />

        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <div class="card p-6">
                    <h1
                        class="text-2xl font-bold text-text-primary dark:text-text-inverted mb-2"
                    >
                        {{ student.name }} - Attempts
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
                    <div class="text-sm text-text-secondary mb-1">
                        Total Attempts
                    </div>
                    <div
                        class="text-2xl font-bold text-text-primary dark:text-text-inverted"
                    >
                        {{ summary.total_attempts }}
                    </div>
                </div>
                <div class="card p-4">
                    <div class="text-sm text-text-secondary mb-1">
                        Best Score
                    </div>
                    <div
                        class="text-2xl font-bold"
                        :class="getScoreColor(summary.best_score)"
                    >
                        {{ summary.best_score }}%
                    </div>
                    <div
                        v-if="summary.best_attempt_no"
                        class="text-xs text-text-secondary mt-1"
                    >
                        Attempt #{{ summary.best_attempt_no }}
                    </div>
                </div>
                <div class="card p-4">
                    <div class="text-sm text-text-secondary mb-1">
                        Latest Attempt
                    </div>
                    <div
                        class="text-sm font-medium text-text-primary dark:text-text-inverted"
                    >
                        {{ formatDate(summary.latest_attempt_date) || "N/A" }}
                    </div>
                </div>
            </div>

            <!-- Attempts List -->
            <div class="space-y-4">
                <h2
                    class="text-xl font-semibold text-text-primary dark:text-text-inverted mb-4"
                >
                    All Attempts
                </h2>

                <!-- Empty State -->
                <div
                    v-if="attempts.length === 0"
                    class="card p-12 text-center text-text-secondary"
                >
                    <h3
                        class="text-lg font-medium text-text-primary dark:text-text-inverted mb-2"
                    >
                        No attempts
                    </h3>
                </div>

                <!-- Attempt Cards -->
                <div
                    v-for="(attempt, index) in attempts"
                    :key="attempt.id"
                    class="card p-6 hover:shadow-lg transition-all duration-200"
                    :class="getScoreBgColor(attempt.score)"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex-shrink-0 w-10 h-10 rounded-full bg-accent-primary text-white flex items-center justify-center font-bold text-sm"
                            >
                                #{{ attempt.attempt_no }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h3
                                        class="text-lg font-semibold text-text-primary dark:text-text-inverted"
                                    >
                                        Attempt {{ attempt.attempt_no }}
                                    </h3>
                                    <span
                                        v-if="isBestAttempt(attempt.id)"
                                        class="px-2 py-1 text-xs font-medium bg-yellow-500 text-white rounded"
                                    >
                                        Best
                                    </span>
                                    <span
                                        v-if="isLatestAttempt(index)"
                                        class="px-2 py-1 text-xs font-medium bg-blue-500 text-white rounded"
                                    >
                                        Latest
                                    </span>
                                </div>
                                <p class="text-sm text-text-secondary">
                                    {{ formatDate(attempt.created_at) }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="text-3xl font-bold"
                            :class="getScoreColor(attempt.score)"
                        >
                            {{ attempt.score }}%
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-4 gap-4 mb-4">
                        <div>
                            <div class="text-xs text-text-secondary mb-1">
                                Total
                            </div>
                            <div
                                class="text-sm font-semibold text-text-primary dark:text-text-inverted"
                            >
                                {{ attempt.total_questions }}
                            </div>
                        </div>
                        <div>
                            <div
                                class="text-xs text-green-600 dark:text-green-400 mb-1"
                            >
                                Correct
                            </div>
                            <div
                                class="text-sm font-semibold text-green-600 dark:text-green-400"
                            >
                                {{ attempt.correct_answers }}
                            </div>
                        </div>
                        <div>
                            <div
                                class="text-xs text-red-600 dark:text-red-400 mb-1"
                            >
                                Incorrect
                            </div>
                            <div
                                class="text-sm font-semibold text-red-600 dark:text-red-400"
                            >
                                {{ attempt.wrong_answers }}
                            </div>
                        </div>
                        <div>
                            <div
                                class="text-xs text-gray-600 dark:text-gray-400 mb-1"
                            >
                                No Answer
                            </div>
                            <div
                                class="text-sm font-semibold text-gray-600 dark:text-gray-400"
                            >
                                {{ attempt.no_answer }}
                            </div>
                        </div>
                    </div>

                    <!-- Accordion: Adaptive assessments from this attempt -->
                    <div
                        v-if="hasAdaptives(attempt)"
                        class="pt-4 border-t border-border-light dark:border-border-dark"
                    >
                        <button
                            type="button"
                            @click="toggleAccordion(attempt.id)"
                            class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg bg-accent-primary/10 dark:bg-accent-primary/20 border border-accent-primary/30 text-left text-sm font-medium text-text-primary dark:text-text-inverted hover:bg-accent-primary/20 dark:hover:bg-accent-primary/30 transition-colors"
                        >
                            <span class="flex items-center gap-2">
                                <svg
                                    class="w-4 h-4 text-accent-primary"
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
                                Adaptive assessments from this attempt ({{
                                    attempt.adaptive_assessments.length
                                }})
                            </span>
                            <svg
                                class="w-5 h-5 text-accent-primary transition-transform"
                                :class="{
                                    'rotate-180': isAccordionOpen(attempt.id),
                                }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>
                        <div
                            v-show="isAccordionOpen(attempt.id)"
                            class="mt-2 pl-4 space-y-2 border-l-2 border-accent-primary/30"
                        >
                            <div
                                v-for="adaptive in attempt.adaptive_assessments"
                                :key="adaptive.id"
                                class="py-2"
                            >
                                <div class="text-sm text-text-secondary mb-1">
                                    {{ adaptive.title }}
                                </div>
                                <div
                                    class="flex items-center gap-2 text-xs text-text-secondary"
                                >
                                    {{ formatDate(adaptive.created_at) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </InstructorLayout>
</template>
