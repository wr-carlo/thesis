<script setup>
import StudentLayout from "@/Layouts/StudentLayout.vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { useToast } from "@/Stores/useToast";

const page = usePage();
const { success, error } = useToast();

const props = defineProps({
    assessments: Array,
    filters: Object,
});

const search = ref(props.filters?.search || "");

const flash = computed(() => page.props.flash || {});

// Watch for flash messages
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.message) {
            if (flash.type === "success") {
                success(flash.message);
            } else if (flash.type === "error") {
                error(flash.message);
            }
        }
    },
    { immediate: true }
);

const hasAssessments = computed(
    () => props.assessments && props.assessments.length > 0
);

const filteredAssessments = computed(() => {
    if (!search.value) return props.assessments;

    const searchLower = search.value.toLowerCase();
    return props.assessments.filter((assessment) => {
        return (
            assessment.title.toLowerCase().includes(searchLower) ||
            assessment.subject.name.toLowerCase().includes(searchLower) ||
            assessment.subject.code.toLowerCase().includes(searchLower) ||
            assessment.lesson.title.toLowerCase().includes(searchLower)
        );
    });
});

const handleSearch = () => {
    router.get(
        route("student.assessments.index"),
        { search: search.value },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
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
</script>

<template>
    <StudentLayout>

        <Head title="Assessments" />

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary dark:text-text-inverted">
                Assessments
            </h1>
            <p class="text-text-secondary">
                Take assessments assigned to your section or from enrolled
                subjects.
            </p>
        </div>

        <!-- Search Bar -->
        <div class="mb-6">
            <form @submit.prevent="handleSearch" class="flex gap-3">
                <div class="flex-1">
                    <TextInput v-model="search" type="text"
                        placeholder="Search assessments by title, subject, or lesson..." class="w-full" />
                </div>
                <PrimaryButton type="submit">Search</PrimaryButton>
            </form>
        </div>

        <!-- Empty State -->
        <div v-if="!hasAssessments" class="card p-12 text-center text-text-secondary">
            <svg class="mx-auto h-16 w-16 mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="text-lg font-medium text-text-primary dark:text-text-inverted mb-2">
                No assessments available
            </h3>
            <p class="text-sm">
                There are no assessments available at the moment. Please check
                back later.
            </p>
        </div>

        <!-- No Results State -->
        <div v-else-if="filteredAssessments.length === 0" class="card p-12 text-center text-text-secondary">
            <svg class="mx-auto h-16 w-16 mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <h3 class="text-lg font-medium text-text-primary dark:text-text-inverted mb-2">
                No results found
            </h3>
            <p class="text-sm">
                Try adjusting your search terms to find what you're looking for.
            </p>
        </div>

        <!-- Assessments Grid -->
        <div v-else class="grid grid-cols-1 gap-6">
            <div v-for="assessment in filteredAssessments" :key="assessment.id"
                class="card p-6 hover:shadow-lg transition-all duration-200 border border-border-light dark:border-border-dark">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-semibold text-text-primary dark:text-text-inverted mb-1">
                            {{ assessment.title }}
                        </h3>
                        <div class="flex flex-wrap gap-4 text-sm text-text-secondary">
                            <div>
                                <span class="font-medium">Subject:</span>
                                {{ assessment.subject.name }}
                                ({{ assessment.subject.code }})
                            </div>
                            <div>
                                <span class="font-medium">Lesson:</span>
                                {{ assessment.lesson.title }}
                            </div>
                            <div>
                                <span class="font-medium">Questions:</span>
                                {{ assessment.item_count }}
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between pt-4 border-t border-border-light dark:border-border-dark">
                    <div class="text-sm text-text-secondary">
                        <span v-if="assessment.attempt_count > 0">
                            Attempts: {{ assessment.attempt_count }}
                            <span v-if="assessment.last_attempt_at">
                                (Last: {{ formatDate(assessment.last_attempt_at) }})
                            </span>
                        </span>
                        <span v-else>Not yet attempted</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link v-if="assessment.attempt_count > 0"
                            :href="route('student.assessments.history', assessment.id)"
                            class="inline-flex items-center justify-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                            View History
                        </Link>
                        <Link :href="route('student.assessments.show', assessment.id)"
                            class="inline-flex items-center justify-center px-4 py-2 bg-accent-primary text-white text-sm font-medium rounded-lg hover:bg-accent-muted transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-accent-primary focus:ring-offset-2">
                            {{
                                assessment.attempt_count > 0
                                    ? "Retake Assessment"
                                    : "Take Assessment"
                            }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
