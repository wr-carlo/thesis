<script setup>
import InstructorLayout from "@/Layouts/InstructorLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    stats: Object,
});
</script>

<template>
    <InstructorLayout>
        <Head title="Instructor Dashboard" />

        <div class="mb-6">
            <h1
                class="text-3xl font-bold text-text-primary dark:text-text-inverted"
            >
                Dashboard
            </h1>
            <p class="text-text-secondary mt-1">
                Welcome back! Here's your overview.
            </p>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- My Subjects Card -->
            <Link
                :href="route('instructor.subjects.index')"
                class="card p-6 hover:shadow-xl hover:border-l-4 hover:border-blue-500 transition-all duration-300 cursor-pointer group"
            >
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div
                            class="text-sm font-medium text-text-secondary mb-2"
                        >
                            My Subjects
                        </div>
                        <div
                            class="text-2xl font-bold text-text-primary dark:text-text-inverted mb-1"
                        >
                            {{ stats?.total_subjects || 0 }}
                        </div>
                        <p class="text-xs text-text-secondary">
                            Teaching subjects
                        </p>
                    </div>
                    <div
                        class="h-12 w-12 rounded-lg bg-gradient-to-br from-blue-500/20 to-blue-600/10 flex items-center justify-center group-hover:scale-110 transition-transform"
                    >
                        <svg
                            class="w-5 h-5 text-blue-600 dark:text-blue-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                            />
                        </svg>
                    </div>
                </div>
            </Link>

            <!-- Total Students Card -->
            <div
                class="card p-6 hover:shadow-xl hover:border-l-4 hover:border-green-500 transition-all duration-300"
            >
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div
                            class="text-sm font-medium text-text-secondary mb-2"
                        >
                            Total Students
                        </div>
                        <div
                            class="text-2xl font-bold text-text-primary dark:text-text-inverted mb-1"
                        >
                            {{ stats?.total_students || 0 }}
                        </div>
                        <p class="text-xs text-text-secondary">
                            Enrolled students
                        </p>
                    </div>
                    <div
                        class="h-12 w-12 rounded-lg bg-gradient-to-br from-green-500/20 to-green-600/10 flex items-center justify-center"
                    >
                        <svg
                            class="w-5 h-5 text-green-600 dark:text-green-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- (Removed cards tied to lessons/assessments) -->

            <!-- Pending Requests Card -->
            <Link
                v-if="stats?.pending_requests > 0"
                :href="route('instructor.subjects.index')"
                class="card p-6 hover:shadow-xl hover:border-l-4 hover:border-red-500 transition-all duration-300 cursor-pointer group bg-red-50/50 dark:bg-red-900/10 border-red-200 dark:border-red-800"
            >
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div
                            class="text-sm font-medium text-red-600 dark:text-red-400 mb-2"
                        >
                            Pending Requests
                        </div>
                        <div
                            class="text-2xl font-bold text-red-600 dark:text-red-400 mb-1"
                        >
                            {{ stats?.pending_requests || 0 }}
                        </div>
                        <p class="text-xs text-red-500 dark:text-red-400">
                            Student join requests
                        </p>
                    </div>
                    <div
                        class="h-12 w-12 rounded-lg bg-gradient-to-br from-red-500/20 to-red-600/10 flex items-center justify-center group-hover:scale-110 transition-transform"
                    >
                        <svg
                            class="w-5 h-5 text-red-600 dark:text-red-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                    </div>
                </div>
            </Link>

            <!-- (Removed Total Attempts card tied to assessments) -->
        </div>

        <!-- (Removed Recent Activity sections tied to lessons/assessments) -->
    </InstructorLayout>
</template>
