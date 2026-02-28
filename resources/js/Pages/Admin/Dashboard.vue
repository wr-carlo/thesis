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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <Link
                :href="route('admin.instructors.index')"
                class="card p-3 sm:p-4 block border border-transparent transition-all duration-200 cursor-pointer hover:border-accent-primary hover:bg-accent-primary/5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-primary/40"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm text-text-secondary">Professors</div>
                        <div class="text-3xl font-semibold mt-1">
                            {{ stats.professors }}
                        </div>
                        <div class="text-xs text-text-secondary mt-1">
                            Manage professors
                        </div>
                    </div>
                    <div
                        class="w-10 h-10 rounded-xl bg-accent-primary/10 text-accent-primary flex items-center justify-center"
                    >
                        <svg
                            class="w-5 h-5"
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
            </Link>
            <Link
                :href="route('admin.students.index')"
                class="card p-3 sm:p-4 block border border-transparent transition-all duration-200 cursor-pointer hover:border-accent-primary hover:bg-accent-primary/5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-primary/40"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm text-text-secondary">Students</div>
                        <div class="text-3xl font-semibold mt-1">
                            {{ stats.students }}
                        </div>
                        <div class="text-xs text-text-secondary mt-1">
                            Manage students
                        </div>
                    </div>
                    <div
                        class="w-10 h-10 rounded-xl bg-accent-primary/10 text-accent-primary flex items-center justify-center"
                    >
                        <svg
                            class="w-5 h-5"
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
            </Link>
            <Link
                :href="route('admin.departments.index')"
                class="card p-3 sm:p-4 block border border-transparent transition-all duration-200 cursor-pointer hover:border-accent-primary hover:bg-accent-primary/5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-primary/40"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm text-text-secondary">Departments</div>
                        <div class="text-3xl font-semibold mt-1">
                            {{ stats.departments }}
                        </div>
                        <div class="text-xs text-text-secondary mt-1">
                            Manage departments
                        </div>
                    </div>
                    <div
                        class="w-10 h-10 rounded-xl bg-accent-primary/10 text-accent-primary flex items-center justify-center"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 7h16M4 12h16M4 17h10"
                            />
                        </svg>
                    </div>
                </div>
            </Link>
            <Link
                :href="route('admin.sections.index')"
                class="card p-3 sm:p-4 block border border-transparent transition-all duration-200 cursor-pointer hover:border-accent-primary hover:bg-accent-primary/5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-primary/40"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm text-text-secondary">Sections</div>
                        <div class="text-3xl font-semibold mt-1">
                            {{ stats.sections }}
                        </div>
                        <div class="text-xs text-text-secondary mt-1">
                            Manage sections
                        </div>
                    </div>
                    <div
                        class="w-10 h-10 rounded-xl bg-accent-primary/10 text-accent-primary flex items-center justify-center"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 8h10M7 12h10M7 16h6"
                            />
                        </svg>
                    </div>
                </div>
            </Link>
            <Link
                :href="route('admin.subjects.index')"
                class="card p-3 sm:p-4 block border border-transparent transition-all duration-200 cursor-pointer hover:border-accent-primary hover:bg-accent-primary/5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-primary/40"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm text-text-secondary">Subjects</div>
                        <div class="text-3xl font-semibold mt-1">
                            {{ stats.subjects }}
                        </div>
                        <div class="text-xs text-text-secondary mt-1">
                            Manage subjects
                        </div>
                    </div>
                    <div
                        class="w-10 h-10 rounded-xl bg-accent-primary/10 text-accent-primary flex items-center justify-center"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 6v12m6-6H6"
                            />
                        </svg>
                    </div>
                </div>
            </Link>
        </div>
        <!--logs table-->
        <div class="card p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold">Recent Activity</h2>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-text-secondary"
                        >Latest 7 entries</span
                    >
                    <Link
                        :href="route('admin.logs.index')"
                        class="btn-ghost text-sm"
                    >
                        Show All
                    </Link>
                </div>
            </div>
            <DataTable
                :headers="['Time', 'User', 'Role', 'Description']"
                :rows="logs"
                :links="[]"
                empty-text="No recent logs."
            >
                <template #row="{ row }">
                    <td
                        class="px-3 py-2 whitespace-nowrap"
                        :title="row.created_at"
                    >
                        {{ formatTimeAgo(row.created_at) }}
                    </td>
                    <td class="px-3 py-2">{{ row.user?.name || "N/A" }}</td>
                    <td class="px-3 py-2 capitalize">{{ row.role }}</td>
                    <td class="px-3 py-2">{{ row.description }}</td>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>
