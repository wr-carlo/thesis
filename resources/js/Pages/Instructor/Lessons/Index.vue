<template>
    <InstructorLayout>
        <Head title="My Lessons" />

        <!-- Header Section -->
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                >
                    <div>
                        <h1
                            class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight"
                        >
                            My Assessments
                        </h1>
                        <p
                            class="mt-1.5 text-sm text-gray-500 dark:text-gray-400"
                        >
                            View and manage your lesson assessments
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('instructor.lessons.createManual')"
                            class="inline-flex items-center justify-center px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 shadow-sm"
                        >
                            <svg
                                class="w-4 h-4 mr-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                            Create Manual Assessment
                        </Link>
                        <Link
                            :href="route('instructor.lessons.create')"
                            class="inline-flex items-center justify-center px-5 py-2.5 bg-accent-primary dark:bg-gray-700 text-white dark:text-white text-sm font-medium rounded-lg hover:bg-accent-muted dark:hover:bg-gray-600 transition-colors duration-200 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-white focus:ring-offset-1 dark:ring-offset-gray-900 shadow-sm"
                        >
                            <svg
                                class="w-4 h-4 mr-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            Upload New Lesson
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="mb-6 flex flex-col sm:flex-row gap-4">
                <!-- Search Bar -->
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
                            placeholder="Search by title or subject..."
                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent text-sm"
                        />
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="sm:w-48">
                    <select
                        v-model="statusFilter"
                        @change="handleStatusFilter"
                        class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent text-sm"
                    >
                        <option value="all">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </div>
            </div>

            <!-- Lessons Grid -->
            <div
                v-if="lessons.data.length > 0"
                class="flex flex-col min-h-[440px] justify-between"
            >
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-4"
                >
                    <div
                        v-for="lesson in lessons.data"
                        :key="lesson.id"
                        class="group bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-md transition-all duration-200"
                    >
                        <!-- Card Header -->
                        <div class="mb-4">
                            <h3
                                class="text-lg font-semibold text-gray-900 dark:text-white mb-1.5 line-clamp-2 group-hover:text-gray-700 dark:group-hover:text-gray-200 transition-colors"
                            >
                                {{ lesson.title }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ lesson.subject?.name || "No subject" }}
                            </p>
                        </div>

                        <!-- Status Badge and Question Count -->
                        <div class="mb-4 flex flex-wrap items-center gap-2">
                            <span
                                v-if="
                                    lesson.assessments &&
                                    lesson.assessments.length > 0
                                "
                                :class="{
                                    'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium': true,
                                    'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800':
                                        lesson.assessments[0].status ===
                                        'draft',
                                    'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800':
                                        lesson.assessments[0].status ===
                                        'published',
                                }"
                            >
                                <span
                                    :class="{
                                        'w-1.5 h-1.5 rounded-full mr-2': true,
                                        'bg-amber-500':
                                            lesson.assessments[0].status ===
                                            'draft',
                                        'bg-emerald-500':
                                            lesson.assessments[0].status ===
                                            'published',
                                    }"
                                ></span>
                                {{ lesson.assessments[0].status }}
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-600"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full mr-2 bg-gray-400"
                                ></span>
                                No Assessment
                            </span>
                            <span
                                v-if="
                                    lesson.assessments &&
                                    lesson.assessments.length > 0 &&
                                    lesson.assessments[0].items
                                "
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800"
                            >
                                <svg
                                    class="w-3.5 h-3.5 mr-1.5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                {{ lesson.assessments[0].items?.length || 0 }}
                                {{
                                    (lesson.assessments[0].items?.length ||
                                        0) === 1
                                        ? "question"
                                        : "questions"
                                }}
                            </span>
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700/50"
                        >
                            <span
                                class="text-xs text-gray-500 dark:text-gray-400"
                            >
                                {{ formatDate(lesson.created_at) }}
                            </span>
                            <div class="flex items-center gap-2">
                                <Link
                                    :href="
                                        route(
                                            'instructor.lessons.edit',
                                            lesson.id
                                        )
                                    "
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                >
                                    <svg
                                        class="w-3.5 h-3.5 mr-1.5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                    Edit
                                </Link>
                                <button
                                    @click="deleteLesson(lesson.id)"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors duration-150"
                                >
                                    <svg
                                        class="w-3.5 h-3.5 mr-1.5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="lessons.links.length > 3"
                    class="flex justify-center items-center gap-1"
                >
                    <Link
                        v-for="(link, index) in lessons.links"
                        :key="index"
                        :href="link.url"
                        :class="{
                            'inline-flex items-center justify-center min-w-[2.5rem] px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-150': true,
                            'bg-gray-900 dark:bg-white text-white dark:text-gray-900':
                                link.active,
                            'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700':
                                !link.active && link.url,
                            'bg-gray-50 dark:bg-gray-800/50 text-gray-400 dark:text-gray-600 cursor-not-allowed border border-gray-200 dark:border-gray-700':
                                !link.url,
                        }"
                        v-html="link.label"
                    />
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="flex flex-col items-center justify-center py-16 px-4"
            >
                <div
                    class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4"
                >
                    <svg
                        class="w-8 h-8 text-gray-400 dark:text-gray-500"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                </div>
                <h3
                    class="text-lg font-semibold text-gray-900 dark:text-white mb-1.5"
                >
                    No assessments yet
                </h3>
                <p
                    class="text-sm text-gray-500 dark:text-gray-400 text-center max-w-sm mb-6"
                >
                    Get started by uploading your first lesson and creating an
                    assessment.
                </p>
                <Link
                    :href="route('instructor.lessons.create')"
                    class="inline-flex items-center justify-center px-5 py-2.5 bg-accent-primary dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white focus:ring-offset-2 shadow-sm"
                >
                    <svg
                        class="w-4 h-4 mr-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    Upload New Lesson
                </Link>
            </div>
        </div>

        <!-- Confirmation Modal for Delete -->
        <ConfirmationModal
            :show="showDeleteModal"
            title="Delete Lesson"
            message="Are you sure you want to delete this lesson? This will also
                delete the associated assessment. This action cannot be undone."
            confirm-text="Delete"
            cancel-text="Cancel"
            variant="danger"
            @close="showDeleteModal = false"
            @confirm="confirmDelete"
        />
    </InstructorLayout>
</template>

<script setup>
import { ref } from "vue";
import { Link, router, Head } from "@inertiajs/vue3";
import InstructorLayout from "@/Layouts/InstructorLayout.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";

const props = defineProps({
    lessons: Object,
    filters: {
        type: Object,
        default: () => ({
            search: "",
            status: "all",
        }),
    },
});

const showDeleteModal = ref(false);
const lessonToDelete = ref(null);
const searchQuery = ref(props.filters?.search || "");
const statusFilter = ref(props.filters?.status || "all");
let searchTimeout = null;

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const deleteLesson = (id) => {
    lessonToDelete.value = id;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    router.delete(route("instructor.lessons.destroy", lessonToDelete.value), {
        onSuccess: () => {
            showDeleteModal.value = false;
            lessonToDelete.value = null;
        },
    });
};

const applyFilters = () => {
    router.get(
        route("instructor.lessons.index"),
        {
            search: searchQuery.value || null,
            status: statusFilter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const handleSearch = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
};

const handleStatusFilter = () => {
    applyFilters();
};
</script>
