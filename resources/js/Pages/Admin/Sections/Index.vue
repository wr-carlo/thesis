<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import Modal from "@/Components/Modal.vue";
import SearchableSelect from "@/Components/SearchableSelect.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import { useToast } from "@/Stores/useToast";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import Pagination from "@/Components/Pagination.vue";

const props = defineProps({
    sections: Object,
    departments: Array,
    filters: Object,
});

const form = useForm({
    department_id: "",
    name: "",
});

const { success, error, warning } = useToast();
const updateForms = {};
const editingId = ref(null);
const showCreateModal = ref(false);
const showImportModal = ref(false);
const showDeleteModal = ref(false);
const sectionToDelete = ref(null);
const importErrors = ref([]);
const importErrorMessage = ref("");
const isImportDragging = ref(false);
const importFileName = ref("");

const importForm = useForm({
    department_id: "",
    file: null,
});

const departmentOptions = computed(() =>
    props.departments.map((d) => ({
        value: d.id,
        label: d.name,
    }))
);

const searchQuery = ref(props.filters?.search || "");
const departmentFilterId = ref(
    props.filters?.department_id != null && props.filters.department_id !== ""
        ? String(props.filters.department_id)
        : ""
);

const departmentFilterOptions = computed(() => [
    { value: "", label: "All departments" },
    ...departmentOptions.value.map((o) => ({ value: String(o.value), label: o.label })),
]);

const applyFilters = () => {
    router.get(route("admin.sections.index"), {
        search: searchQuery.value,
        department_id: departmentFilterId.value || undefined,
        per_page: props.filters?.per_page || 10,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

let searchTimeout = null;
watch(searchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch(departmentFilterId, applyFilters);

const hasSections = computed(() => props.sections.data?.length > 0);
const hasActiveFilters = computed(() => searchQuery.value || departmentFilterId.value);

const getDepartmentName = (departmentId) => {
    const dept = props.departments.find((d) => d.id === departmentId);
    return dept ? dept.name : "Unknown";
};

const openCreateModal = () => {
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    form.reset();
    form.clearErrors();
};

const openImportModal = () => {
    showImportModal.value = true;
    importErrors.value = [];
};

const closeImportModal = () => {
    showImportModal.value = false;
    importForm.reset();
    importForm.clearErrors();
    importErrors.value = [];
    importErrorMessage.value = "";
    importFileName.value = "";
};

const handleImportFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        importForm.file = file;
        importFileName.value = file.name;
    }
};

const handleImportDrop = (event) => {
    isImportDragging.value = false;
    event.preventDefault();
    const file = event.dataTransfer?.files?.[0];
    if (file && /\.(xlsx|xls|csv)$/i.test(file.name)) {
        importForm.file = file;
        importFileName.value = file.name;
    }
};

const downloadTemplate = () => {
    window.location.href = route("admin.sections.template");
};

const getImportErrorMessage = (errors) => {
    if (!errors) return null;
    const first = (field) => {
        const v = errors[field];
        return Array.isArray(v) ? v[0] : typeof v === "string" ? v : null;
    };
    return first("department_id") || first("file") || first("error") || "Failed to import. Please check your file and department selection.";
};

const submitImport = () => {
    importErrorMessage.value = "";
    importForm.post(route("admin.sections.import"), {
        preserveScroll: true,
        onSuccess: (response) => {
            const flash = response.props.flash;
            if (flash?.type === "error") {
                importErrorMessage.value = flash.message || "Import failed. Please try again.";
                if (flash.errors) importErrors.value = flash.errors;
                error(flash.message);
                return;
            }
            if (flash?.errors?.length > 0) {
                importErrors.value = flash.errors;
            }
            closeImportModal();
            if (flash?.type === "success") success(flash.message);
            else if (flash?.type === "warning") warning(flash.message);
            else success("Sections imported successfully");
        },
        onError: (errors) => {
            const msg = getImportErrorMessage(errors);
            importErrorMessage.value = msg;
            error(msg);
        },
    });
};

const submitCreate = () => {
    form.post(route("admin.sections.store"), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            success("Section created successfully");
        },
        onError: () => {
            error("Failed to create section");
        },
    });
};

const startEdit = (row) => {
    editingId.value = row.id;
    if (!updateForms[row.id]) {
        updateForms[row.id] = useForm({
            name: row.name,
            department_id: row.department_id,
        });
    }
};

const cancelEdit = (id) => {
    editingId.value = null;
    if (updateForms[id]) {
        updateForms[id].reset();
        updateForms[id].clearErrors();
    }
};

const saveEdit = (id, row) => {
    if (!updateForms[id]) return;
    
    updateForms[id].put(route("admin.sections.update", id), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            success("Section updated successfully");
        },
        onError: () => {
            error("Failed to update section");
        },
    });
};

const openDeleteModal = (id) => {
    sectionToDelete.value = id;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    sectionToDelete.value = null;
};

const confirmDelete = () => {
    router.delete(route("admin.sections.destroy", sectionToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
            success("Section deleted successfully");
        },
        onError: () => {
            error("Failed to delete section");
        },
    });
};

const formatDate = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Sections" />
        
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-1">
                        Sections
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Manage class sections and their departments
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="openImportModal"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Import
                    </button>
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Section
                    </button>
                </div>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="mb-6 flex flex-col sm:flex-row gap-4">
            <div class="flex-1 min-w-0 w-full">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                    Search
                </label>
                <div class="relative">
                    <svg
                        class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
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
                    <input
                        id="search"
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search sections by name..."
                        class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all"
                    />
                </div>
            </div>
            <div class="sm:w-64">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                    Filter by department
                </label>
                <SearchableSelect
                    v-model="departmentFilterId"
                    :options="departmentFilterOptions"
                    placeholder="Filter by department..."
                />
            </div>
        </div>

        <!-- Sections List -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Empty State -->
            <div v-if="!hasSections" class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                    {{ hasActiveFilters ? "No sections found" : "No sections" }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    {{ hasActiveFilters ? "Try adjusting your search or filters." : "Get started by creating a new section." }}
                </p>
                <button
                    v-if="!hasActiveFilters"
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Section
                </button>
            </div>

            <!-- Sections Table -->
            <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                <div
                    v-for="section in props.sections.data"
                    :key="section.id"
                    class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150"
                >
                    <div class="flex items-center justify-between gap-4">
                        <!-- Section Info -->
                        <div class="flex-1 min-w-0">
                            <div v-if="editingId !== section.id" class="flex items-center gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3">
                                        <p class="text-base font-medium text-gray-900 dark:text-white truncate">
                                            {{ section.name }}
                                        </p>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                                            {{ getDepartmentName(section.department_id) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Created {{ formatDate(section.created_at) }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Edit Mode -->
                            <div v-else class="space-y-3">
                                <div>
                                    <input
                                        v-model="(updateForms[section.id] ||= useForm({ name: section.name, department_id: section.department_id })).name"
                                        type="text"
                                        class="w-full px-3 py-2 text-base border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                        placeholder="Section name"
                                        @keyup.enter="saveEdit(section.id, section)"
                                        @keyup.esc="cancelEdit(section.id)"
                                        autofocus
                                    />
                                    <InputError
                                        class="text-xs mt-1"
                                        :message="updateForms[section.id]?.errors?.name"
                                    />
                                </div>
                                <div>
                                    <SearchableSelect
                                        v-model="(updateForms[section.id] ||= useForm({ name: section.name, department_id: section.department_id })).department_id"
                                        :options="departmentOptions"
                                        placeholder="Search and select department..."
                                    />
                                    <InputError
                                        class="text-xs mt-1"
                                        :message="updateForms[section.id]?.errors?.department_id"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                            <template v-if="editingId !== section.id">
                                <button
                                    @click="startEdit(section)"
                                    class="p-2 text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors duration-150"
                                    title="Edit"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button
                                    @click="openDeleteModal(section.id)"
                                    class="p-2 text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors duration-150"
                                    title="Delete"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </template>
                            <template v-else>
                                <button
                                    @click="saveEdit(section.id, section)"
                                    :disabled="updateForms[section.id]?.processing"
                                    class="p-2 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                                    title="Save"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                                <button
                                    @click="cancelEdit(section.id)"
                                    class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors duration-150"
                                    title="Cancel"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <Pagination
                :links="props.sections.links || []"
                :current-page="props.sections.current_page || 1"
                :last-page="props.sections.last_page || 1"
                :per-page="props.filters?.per_page || 10"
                :total="props.sections.total || 0"
                :from="props.sections.from || 0"
                :to="props.sections.to || 0"
                route-name="admin.sections.index"
                :filters="{
                    search: props.filters?.search || '',
                    department_id: departmentFilterId.value || props.filters?.department_id || '',
                }"
            />
        </div>

        <!-- Create Section Modal -->
        <Modal :show="showCreateModal" @close="closeCreateModal">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Create Section
                    </h2>
                    <button
                        @click="closeCreateModal"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitCreate" class="space-y-6">
                    <div>
                        <InputLabel value="Department" class="mb-2" />
                        <SearchableSelect
                            v-model="form.department_id"
                            :options="departmentOptions"
                            placeholder="Search and select department..."
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.department_id"
                        />
                    </div>
                    <div>
                        <InputLabel for="create_name" value="Section Name" class="mb-2" />
                        <TextInput
                            id="create_name"
                            v-model="form.name"
                            type="text"
                            class="block w-full"
                            placeholder="Enter section name (e.g., BSCS-4A)"
                            required
                            autofocus
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <SecondaryButton
                            type="button"
                            @click="closeCreateModal"
                            class="px-4 py-2"
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            :disabled="form.processing"
                            class="px-4 py-2"
                        >
                            Create Section
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Import Sections Modal -->
        <Modal :show="showImportModal" @close="closeImportModal">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Import Sections
                    </h2>
                    <button
                        @click="closeImportModal"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitImport" class="space-y-6">
                    <!-- Error message banner -->
                    <div
                        v-if="importErrorMessage"
                        class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg flex items-start gap-3"
                    >
                        <svg class="w-5 h-5 text-red-500 dark:text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                {{ importErrorMessage }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="importErrorMessage = ''"
                            class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 shrink-0"
                            aria-label="Dismiss"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            Upload an Excel or CSV file with section names only (a <strong>name</strong> or <strong>section</strong> column). Duplicates will be skipped.
                        </p>
                        <div>
                        <InputLabel value="Department" class="mb-2" />
                            <SearchableSelect
                                v-model="importForm.department_id"
                                :options="departmentOptions"
                                placeholder="Select department to import sections into..."
                            />
                            <InputError
                                class="mt-2"
                                :message="importForm.errors.department_id"
                            />
                        </div>
                        <InputLabel for="import_file" value="Select File" class="mb-2" />
                        <div
                            class="mt-1 relative overflow-hidden rounded-lg p-[2px]"
                            @dragover.prevent="isImportDragging = true"
                            @dragleave.prevent="isImportDragging = false"
                            @drop.prevent="handleImportDrop"
                        >
                            <div
                                class="drop-zone-beam"
                                :class="{ 'drop-zone-beam--full': importForm.file }"
                            />
                            <div
                                class="relative flex justify-center px-6 pt-5 pb-6 rounded-[calc(0.5rem-2px)] transition-colors"
                                :class="isImportDragging
                                    ? 'bg-indigo-50 dark:bg-indigo-900/20'
                                    : 'bg-white dark:bg-gray-800'"
                            >
                            <div class="space-y-1 text-center">
                                <svg
                                    class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 48 48"
                                >
                                    <path
                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                                <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                                    <label
                                        for="import_file"
                                        class="relative cursor-pointer rounded-md font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
                                    >
                                        <span>Upload a file</span>
                                        <input
                                            id="import_file"
                                            type="file"
                                            class="sr-only"
                                            accept=".xlsx,.xls,.csv"
                                            @change="handleImportFileChange"
                                        />
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    XLSX, XLS, CSV up to 2MB
                                </p>
                                <p
                                    v-if="importFileName"
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-2"
                                >
                                    Selected: {{ importFileName }}
                                </p>
                            </div>
                            </div>
                        </div>
                        <InputError class="mt-2" :message="importForm.errors.file" />
                        <button
                            type="button"
                            @click="downloadTemplate"
                            class="mt-2 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium"
                        >
                            Download template
                        </button>
                    </div>
                    <div v-if="importErrors.length > 0" class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg max-h-32 overflow-y-auto">
                        <p class="text-xs font-medium text-amber-800 dark:text-amber-300 mb-2">Skipped rows:</p>
                        <ul class="text-xs text-amber-700 dark:text-amber-400 space-y-1">
                            <li v-for="(err, idx) in importErrors.slice(0, 10)" :key="idx">{{ err }}</li>
                            <li v-if="importErrors.length > 10" class="text-amber-600 dark:text-amber-500">... and {{ importErrors.length - 10 }} more</li>
                        </ul>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <SecondaryButton type="button" @click="closeImportModal" class="px-4 py-2">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            :disabled="!importForm.file || importForm.processing"
                            class="px-4 py-2"
                        >
                            {{ importForm.processing ? "Importing..." : "Import" }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :show="showDeleteModal"
            title="Delete Section"
            message="Are you sure you want to delete this section? This action cannot be undone and may affect associated students."
            confirm-text="Delete"
            cancel-text="Cancel"
            variant="danger"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>

<style scoped>
.drop-zone-beam {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 200vmax;
    height: 200vmax;
    margin-left: -100vmax;
    margin-top: -100vmax;
    background: conic-gradient(
        from 0deg,
        transparent 0deg,
        #3238a8 20deg,
        #00c8ff 40deg,
        transparent 60deg,
        transparent 60deg 150deg,
        transparent 150deg,
        #3238a8 170deg,
        #00c8ff 190deg,
        transparent 210deg,
        transparent 210deg 360deg
    );
    animation: border-beam-rotate 3s linear infinite;
    will-change: transform;
    border-radius: 50%;
}

.drop-zone-beam--full {
    background: conic-gradient(
        from 0deg,
        #3238a8,
        #00c8ff,
        #3238a8,
        #00c8ff,
        #3238a8
    );
}

@keyframes border-beam-rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
