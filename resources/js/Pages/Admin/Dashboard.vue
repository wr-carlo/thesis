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
            <div class="card p-4">
                <div class="text-sm text-text-secondary">Professors</div>
                <div class="text-3xl font-semibold">{{ stats.professors }}</div>
            </div>
            <div class="card p-4">
                <div class="text-sm text-text-secondary">Students</div>
                <div class="text-3xl font-semibold">{{ stats.students }}</div>
            </div>
            <div class="card p-4">
                <div class="text-sm text-text-secondary">Departments</div>
                <div class="text-3xl font-semibold">
                    {{ stats.departments }}
                </div>
            </div>
            <div class="card p-4">
                <div class="text-sm text-text-secondary">Sections</div>
                <div class="text-3xl font-semibold">{{ stats.sections }}</div>
            </div>
            <div class="card p-4">
                <div class="text-sm text-text-secondary">Subjects</div>
                <div class="text-3xl font-semibold">{{ stats.subjects }}</div>
            </div>
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
