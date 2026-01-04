<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DataTable from "@/Components/DataTable.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { formatTimeAgo } from "@/utils/formatTime";

const props = defineProps({
    logs: Object,
    filters: Object,
});

const breadcrumbItems = [
    { label: "Dashboard", route: "admin.dashboard" },
    { label: "Logs", current: true },
];

const searchQuery = ref(props.filters?.search || "");
let searchTimeout = null;

watch(searchQuery, (newValue) => {
    // Clear existing timeout
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    // Debounce search - wait 500ms after user stops typing
    searchTimeout = setTimeout(() => {
        router.get(
            route("admin.logs.index"),
            { search: newValue },
            {
                preserveState: true,
                preserveScroll: false,
                replace: true,
            }
        );
    }, 500);
});
</script>

<template>
    <AdminLayout>
        <Head title="Logs" />
        <Breadcrumb :items="breadcrumbItems" />
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Activity Logs</h1>
        </div>

        <div class="card p-4 space-y-4">
            <div>
                <InputLabel for="search" value="Search" />
                <input
                    id="search"
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search by description or user name..."
                    class="input mt-1"
                />
            </div>
            <DataTable
                :headers="['Time', 'User', 'Role', 'Description']"
                :rows="logs.data"
                :links="logs.links"
                empty-text="No logs found."
                class="h-[390px]"
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
