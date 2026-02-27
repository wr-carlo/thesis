<script setup>
import { ref } from "vue";
import InstructorLayout from "@/Layouts/InstructorLayout.vue";
import Modal from "@/Components/Modal.vue";
import { Head, Link, router } from "@inertiajs/vue3";

const props = defineProps({
    assessment: Object,
    summary: Object,
    students: Array,
    most_common_mistakes: {
        type: Array,
        default: () => [],
    },
    sections: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: null, section: null }),
    },
});

const showMistakesModal = ref(false);
const searchQuery = ref(props.filters?.search || "");
const sectionFilter = ref(props.filters?.section || "all");
let searchTimeout = null;

const applyFilters = () => {
    router.get(
        route("instructor.assessments.history", props.assessment.id),
        {
            search: searchQuery.value || null,
            section: sectionFilter.value === "all" ? null : sectionFilter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const handleSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
};

const handleSectionFilter = () => {
    applyFilters();
};

const activeSectionName = () => {
    if (!props.filters?.section) return null;
    const s = props.sections?.find(
        (sec) => String(sec.id) === String(props.filters.section)
    );
    return s?.name ?? null;
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

const getMasteryPercent = (student) => {
    const value = Number(student?.mastery_percent ?? 0);
    if (Number.isNaN(value)) return 0;
    return Math.max(0, Math.min(100, value));
};

const getMasteryColor = (student) => {
    const percent = getMasteryPercent(student);
    if (percent > 75) return "#05ff00"; // high
    if (percent > 50) return "#d79f00"; // middle
    return "#ff0000"; // low
};

const getMasteryRingStyle = (student) => {
    const percent = getMasteryPercent(student);
    const color = getMasteryColor(student);
    return {
        background: `conic-gradient(${color} ${percent}%, #e5e7eb ${percent}% 100%)`,
    };
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
                        <span v-if="activeSectionName()">
                            ({{ activeSectionName() }})
                        </span>
                    </div>
                </button>
            </div>

            <!-- Search and Filter Section -->
            <div class="mb-6 flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <div class="relative">
                        <div
                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                        >
                            <svg
                                class="h-5 w-5 text-gray-400 dark:text-gray-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                        <input
                            v-model="searchQuery"
                            @input="handleSearch"
                            type="text"
                            placeholder="Search by student name..."
                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent text-sm"
                        />
                    </div>
                </div>
                <div class="sm:w-48">
                    <select
                        v-model="sectionFilter"
                        @change="handleSectionFilter"
                        class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent text-sm"
                    >
                        <option value="all">All Sections</option>
                        <option
                            v-for="section in sections"
                            :key="section.id"
                            :value="section.id"
                        >
                            {{ section.name }}
                        </option>
                    </select>
                </div>
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
                                <span v-if="student.section_name" class="ml-1">
                                    · {{ student.section_name }}
                                </span>
                            </p>
                            <p
                                v-if="student.latest_attempt_date"
                                class="text-xs text-text-secondary mt-0.5"
                            >
                                Latest: {{ formatDate(student.latest_attempt_date) }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <div
                                class="relative w-14 h-14 rounded-full p-[4px]"
                                :style="getMasteryRingStyle(student)"
                            >
                                <div
                                    class="w-full h-full rounded-full bg-white dark:bg-gray-900 flex items-center justify-center"
                                >
                                    <span
                                        class="text-[11px] font-bold text-text-primary dark:text-text-inverted"
                                    >
                                        {{ Math.round(getMasteryPercent(student)) }}%
                                    </span>
                                </div>
                            </div>
                            <div class="text-xs leading-tight">
                                <div
                                    class="font-semibold text-text-primary dark:text-text-inverted"
                                >
                                    Mastery
                                </div>
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
                            <span
                                v-if="activeSectionName()"
                                class="font-medium text-accent-primary"
                            >
                                · Filtered by: {{ activeSectionName() }}
                            </span>
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
                    <p>
                        {{
                            activeSectionName()
                                ? `No mistakes recorded on first attempts for ${activeSectionName()}.`
                                : "No mistakes recorded on first attempts."
                        }}
                    </p>
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
