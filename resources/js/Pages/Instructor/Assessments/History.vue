<script setup>
import { ref } from "vue";
import InstructorLayout from "@/Layouts/InstructorLayout.vue";
import Modal from "@/Components/Modal.vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    assessment: Object,
    summary: Object,
    students: Array,
    most_common_mistakes: {
        type: Array,
        default: () => [],
    },
});

const showMistakesModal = ref(false);

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
</script>

<template>
    <InstructorLayout>
        <Head :title="`History - ${assessment.title}`" />

        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <div class="card p-6">
                    <h1
                        class="text-2xl font-bold text-text-primary dark:text-text-inverted mb-2"
                    >
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
                    <div class="text-sm text-text-secondary mb-1">
                        Students Who Took
                    </div>
                    <div
                        class="text-2xl font-bold text-text-primary dark:text-text-inverted"
                    >
                        {{ summary.total_students }}
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
                        v-if="summary.best_student_name"
                        class="text-xs text-text-secondary mt-1"
                    >
                        {{ summary.best_student_name }}
                    </div>
                </div>
                <button
                    type="button"
                    @click="showMistakesModal = true"
                    class="card p-4 text-left cursor-pointer hover:shadow-lg hover:border-accent-primary/30 transition-all duration-200 border-2 border-transparent"
                >
                    <div class="text-sm text-text-secondary mb-1">
                        Most Common Mistakes
                    </div>
                    <div
                        class="text-2xl font-bold text-text-primary dark:text-text-inverted"
                    >
                        {{
                            most_common_mistakes.length > 0
                                ? most_common_mistakes[0].mistake_count + " students"
                                : "None"
                        }}
                    </div>
                    <div class="text-xs text-text-secondary mt-1">
                        Most missed on first take
                    </div>
                </button>
            </div>

            <!-- Students List -->
            <div class="space-y-4">
                <h2
                    class="text-xl font-semibold text-text-primary dark:text-text-inverted mb-4"
                >
                    Students
                </h2>

                <!-- Empty State -->
                <div
                    v-if="students.length === 0"
                    class="card p-12 text-center text-text-secondary"
                >
                    <svg
                        class="mx-auto h-16 w-16 mb-4 opacity-50"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                    </svg>
                    <h3
                        class="text-lg font-medium text-text-primary dark:text-text-inverted mb-2"
                    >
                        No students yet
                    </h3>
                    <p class="text-sm mb-4">
                        No students have taken this assessment yet.
                    </p>
                </div>

                <!-- Student Cards -->
                <div
                    v-for="student in students"
                    :key="student.student_id"
                    class="card p-6 flex items-center justify-between hover:shadow-lg transition-all duration-200"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="flex-shrink-0 w-12 h-12 rounded-full bg-accent-primary/20 dark:bg-accent-primary/30 text-accent-primary flex items-center justify-center font-bold text-lg"
                        >
                            {{ student.student_name?.charAt(0)?.toUpperCase() || "?" }}
                        </div>
                        <div>
                            <h3
                                class="text-lg font-semibold text-text-primary dark:text-text-inverted"
                            >
                                {{ student.student_name }}
                            </h3>
                            <p class="text-sm text-text-secondary">
                                {{ student.attempt_count }}
                                {{ student.attempt_count === 1 ? "attempt" : "attempts" }}
                                · Best: {{ student.best_score }}%
                            </p>
                            <p
                                v-if="student.latest_attempt_date"
                                class="text-xs text-text-secondary mt-0.5"
                            >
                                Latest: {{ formatDate(student.latest_attempt_date) }}
                            </p>
                        </div>
                    </div>
                    <Link
                        :href="
                            route('instructor.assessments.history.student', [
                                assessment.id,
                                student.student_id,
                            ])
                        "
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-accent-primary rounded-lg hover:bg-accent-muted transition-colors duration-150"
                    >
                        View
                    </Link>
                </div>
            </div>
        </div>

        <!-- Most Common Mistakes Modal -->
        <Modal :show="showMistakesModal" @close="showMistakesModal = false" max-width="lg">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-text-primary dark:text-text-inverted">
                            Question Mistakes (First Attempt)
                        </h2>
                        <p class="text-sm text-text-secondary mt-1">
                            Ranked by how many students got it wrong
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="showMistakesModal = false"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div
                    v-if="most_common_mistakes.length === 0"
                    class="py-12 text-center text-text-secondary"
                >
                    <p>No mistakes recorded on first attempts.</p>
                </div>
                <div
                    v-else
                    class="max-h-96 overflow-y-auto space-y-3"
                >
                    <div
                        v-for="item in most_common_mistakes"
                        :key="item.item_id"
                        class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50"
                    >
                        <div class="flex items-start gap-4">
                            <div
                                class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 flex items-center justify-center font-bold text-sm"
                            >
                                {{ item.rank }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-text-primary dark:text-text-inverted">
                                    {{ item.question }}
                                </p>
                                <p class="text-xs text-red-600 dark:text-red-400 font-medium mt-1">
                                    {{ item.mistake_count }} {{ item.mistake_count === 1 ? "student" : "students" }} got this wrong
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </InstructorLayout>
</template>
