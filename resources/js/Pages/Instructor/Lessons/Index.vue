<template>
    <InstructorLayout>
        <Head title="My Lessons" />
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
                                My Lessons
                            </h2>
                            <Link
                                :href="route('instructor.lessons.create')"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Upload New Lesson
                            </Link>
                        </div>

                        <!-- Lessons Table -->
                        <div
                            v-if="lessons.data.length > 0"
                            class="overflow-x-auto"
                        >
                            <table
                                class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                            >
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                        >
                                            Title
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                        >
                                            Subject
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                        >
                                            Assessment Status
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                        >
                                            Created
                                        </th>
                                        <th
                                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                                >
                                    <tr
                                        v-for="lesson in lessons.data"
                                        :key="lesson.id"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div
                                                class="text-sm font-medium text-gray-900 dark:text-white"
                                            >
                                                {{ lesson.title }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div
                                                class="text-sm text-gray-900 dark:text-gray-300"
                                            >
                                                {{ lesson.subject?.name }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                v-if="
                                                    lesson.assessments &&
                                                    lesson.assessments.length >
                                                        0
                                                "
                                                :class="{
                                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                                                    'bg-yellow-100 text-yellow-800':
                                                        lesson.assessments[0]
                                                            .status === 'draft',
                                                    'bg-green-100 text-green-800':
                                                        lesson.assessments[0]
                                                            .status ===
                                                        'published',
                                                }"
                                            >
                                                {{
                                                    lesson.assessments[0].status
                                                }}
                                            </span>
                                            <span
                                                v-else
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800"
                                            >
                                                No Assessment
                                            </span>
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            {{ formatDate(lesson.created_at) }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                                        >
                                            <Link
                                                :href="
                                                    route(
                                                        'instructor.lessons.edit',
                                                        lesson.id
                                                    )
                                                "
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                @click="deleteLesson(lesson.id)"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <div
                                v-if="lessons.links.length > 3"
                                class="mt-4 flex justify-center"
                            >
                                <nav
                                    class="inline-flex rounded-md shadow-sm -space-x-px"
                                >
                                    <Link
                                        v-for="(link, index) in lessons.links"
                                        :key="index"
                                        :href="link.url"
                                        :class="{
                                            'relative inline-flex items-center px-4 py-2 text-sm font-medium': true,
                                            'bg-blue-600 text-white':
                                                link.active,
                                            'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700':
                                                !link.active,
                                            'cursor-not-allowed opacity-50':
                                                !link.url,
                                        }"
                                        v-html="link.label"
                                    />
                                </nav>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <svg
                                class="mx-auto h-12 w-12 text-gray-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <h3
                                class="mt-2 text-sm font-medium text-gray-900 dark:text-white"
                            >
                                No lessons
                            </h3>
                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                            >
                                Get started by uploading your first lesson.
                            </p>
                            <div class="mt-6">
                                <Link
                                    :href="route('instructor.lessons.create')"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Upload New Lesson
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal for Delete -->
        <ConfirmationModal
            :show="showDeleteModal"
            @close="showDeleteModal = false"
        >
            <template #title> Delete Lesson </template>

            <template #content>
                Are you sure you want to delete this lesson? This will also
                delete the associated assessment. This action cannot be undone.
            </template>

            <template #footer>
                <SecondaryButton @click="showDeleteModal = false">
                    Cancel
                </SecondaryButton>

                <DangerButton @click="confirmDelete" class="ml-3">
                    Delete
                </DangerButton>
            </template>
        </ConfirmationModal>
    </InstructorLayout>
</template>

<script setup>
import { ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import InstructorLayout from "@/Layouts/InstructorLayout.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import { Head } from "@inertiajs/vue3";

const props = defineProps({
    lessons: Object,
});

const showDeleteModal = ref(false);
const lessonToDelete = ref(null);

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
</script>
