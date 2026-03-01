<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DataTable from "@/Components/DataTable.vue";
import { Head, Link } from "@inertiajs/vue3";
import { formatTimeAgo } from "@/utils/formatTime";

defineProps({
    stats: Object,
    logs: Array,
});
</script>

<template>
    <AdminLayout>
        <Head title="Dashboard" />

        <!-- Stats Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
            <!-- Professors Card -->
            <Link
                :href="route('admin.instructors.index')"
                class="card p-6 rounded-xl shadow-sm border border-transparent hover:shadow-md transition-shadow flex flex-col justify-between h-40 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-primary/40"
            >
                <div class="flex justify-between items-start">
                    <h3 class="text-sm font-medium text-text-secondary">
                        Professors
                    </h3>
                    <div
                        class="p-2 bg-accent-primary/10 rounded-lg"
                    >
                        <svg
                            class="w-5 h-5 text-accent-primary"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 14l9-5-9-5-9 5 9 5z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 14l6.16-3.422A12.083 12.083 0 0121 12.058V17a2 2 0 01-2 2H5a2 2 0 01-2-2v-4.942a12.083 12.083 0 012.84-1.48L12 14z"
                            />
                        </svg>
                    </div>
                </div>
                <div>
                    <div
                        class="text-3xl font-bold text-text-primary dark:text-text-inverted mb-1"
                    >
                        {{ stats.professors }}
                    </div>
                    <p class="text-xs text-text-secondary">
                        Manage professors
                    </p>
                </div>
            </Link>

            <!-- Students Card (Highlighted) -->
            <Link
                :href="route('admin.students.index')"
                class="card p-6 rounded-xl shadow-sm border border-accent-primary relative overflow-hidden group h-40 flex flex-col justify-between cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-primary/40"
            >
                <!-- Decorative corner blob -->
                <div
                    class="absolute top-0 right-0 w-24 h-24 bg-accent-primary/5 dark:bg-accent-primary/10 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"
                ></div>
                <div
                    class="relative z-10 flex flex-col justify-between h-full"
                >
                    <div class="flex justify-between items-start">
                        <h3
                            class="text-sm font-medium text-text-secondary"
                        >
                            Students
                        </h3>
                        <div
                            class="p-2 bg-accent-primary/15 rounded-lg"
                        >
                            <svg
                                class="w-5 h-5 text-accent-primary"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5V4H2v16h5m10 0v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6m10 0H7"
                                />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <div
                            class="text-3xl font-bold text-text-primary dark:text-text-inverted mb-1"
                        >
                            {{ stats.students }}
                        </div>
                        <p class="text-xs text-text-secondary">
                            Manage students
                        </p>
                    </div>
                </div>
            </Link>

            <!-- Departments Card -->
            <Link
                :href="route('admin.departments.index')"
                class="card p-6 rounded-xl shadow-sm border border-transparent hover:shadow-md transition-shadow flex flex-col justify-between h-40 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-primary/40"
            >
                <div class="flex justify-between items-start">
                    <h3 class="text-sm font-medium text-text-secondary">
                        Departments
                    </h3>
                    <div
                        class="p-2 bg-accent-primary/10 rounded-lg"
                    >
                        <svg
                            class="w-5 h-5 text-accent-primary"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3m4-10h2m4 0h2m-6 4h2m4 0h2"
                            />
                        </svg>
                    </div>
                </div>
                <div>
                    <div
                        class="text-3xl font-bold text-text-primary dark:text-text-inverted mb-1"
                    >
                        {{ stats.departments }}
                    </div>
                    <p class="text-xs text-text-secondary">
                        Manage departments
                    </p>
                </div>
            </Link>

            <!-- Sections Card -->
            <Link
                :href="route('admin.sections.index')"
                class="card p-6 rounded-xl shadow-sm border border-transparent hover:shadow-md transition-shadow flex flex-col justify-between h-40 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-primary/40"
            >
                <div class="flex justify-between items-start">
                    <h3 class="text-sm font-medium text-text-secondary">
                        Sections
                    </h3>
                    <div
                        class="p-2 bg-accent-primary/10 rounded-lg"
                    >
                        <svg
                            class="w-5 h-5 text-accent-primary"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"
                            />
                        </svg>
                    </div>
                </div>
                <div>
                    <div
                        class="text-3xl font-bold text-text-primary dark:text-text-inverted mb-1"
                    >
                        {{ stats.sections }}
                    </div>
                    <p class="text-xs text-text-secondary">
                        Manage sections
                    </p>
                </div>
            </Link>

            <!-- Subjects Card -->
            <Link
                :href="route('admin.subjects.index')"
                class="card p-6 rounded-xl shadow-sm border border-transparent hover:shadow-md transition-shadow flex flex-col justify-between h-40 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-primary/40"
            >
                <div class="flex justify-between items-start">
                    <h3 class="text-sm font-medium text-text-secondary">
                        Subjects
                    </h3>
                    <div
                        class="p-2 bg-accent-primary/10 rounded-lg"
                    >
                        <svg
                            class="w-5 h-5 text-accent-primary"
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
                <div>
                    <div
                        class="text-3xl font-bold text-text-primary dark:text-text-inverted mb-1"
                    >
                        {{ stats.subjects }}
                    </div>
                    <p class="text-xs text-text-secondary">
                        Manage subjects
                    </p>
                </div>
            </Link>
        </div>

        <!-- Recent Activity Table -->
        <div
            class="card rounded-xl shadow-sm border border-border-light dark:border-border-dark overflow-hidden"
        >
            <!-- Table Header -->
            <div
                class="p-6 border-b border-border-light dark:border-border-dark flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <h2
                    class="text-lg font-bold text-text-primary dark:text-text-inverted"
                >
                    Recent Activity
                </h2>
                <div class="flex items-center gap-4 text-sm">
                    <span class="text-text-secondary"
                        >Latest 7 entries</span
                    >
                    <Link
                        :href="route('admin.logs.index')"
                        class="text-accent-primary font-medium hover:text-accent-muted transition-colors"
                    >
                        Show All
                    </Link>
                </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-auto">
                <table
                    class="w-full text-left text-sm"
                >
                    <thead
                        class="bg-surface-muted dark:bg-surface-dark-muted text-xs uppercase font-semibold text-text-secondary"
                    >
                        <tr>
                            <th class="px-6 py-4">Time</th>
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4">Role</th>
                            <th class="px-6 py-4">Description</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-border-light dark:divide-border-dark"
                    >
                        <tr v-if="!logs.length">
                            <td
                                colspan="4"
                                class="px-6 py-8 text-center text-text-secondary"
                            >
                                No recent logs.
                            </td>
                        </tr>
                        <tr
                            v-for="log in logs"
                            :key="log.id"
                            class="hover:bg-surface-muted dark:hover:bg-surface-dark-muted transition-colors"
                        >
                            <td
                                class="px-6 py-4 font-medium whitespace-nowrap"
                                :title="log.created_at"
                            >
                                {{ formatTimeAgo(log.created_at) }}
                            </td>
                            <td class="px-6 py-4">
                                {{ log.user?.name || "N/A" }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent-primary/10 text-accent-primary"
                                >
                                    {{ log.role }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                {{ log.description }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
