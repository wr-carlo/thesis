<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Modal from "@/Components/Modal.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import { useToast } from "@/Stores/useToast";

const props = defineProps({
    instructors: Object,
    departments: Array,
    filters: Object,
});

const page = usePage();
const { success, error, warning } = useToast();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showImportModal = ref(false);
const showResetPasswordModal = ref(false);
const showDeleteModal = ref(false);
const editingInstructor = ref(null);
const instructorToDelete = ref(null);
const searchQuery = ref(props.filters?.search || "");
const importErrors = ref([]);
let searchTimeout = null;

const hasInstructors = computed(() => props.instructors.data?.length > 0);

// Watch for flash messages
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.message) {
            if (flash.type === "success") {
                success(flash.message);
                if (flash.errors && flash.errors.length > 0) {
                    importErrors.value = flash.errors;
                }
            } else if (flash.type === "warning") {
                warning(flash.message);
                if (flash.errors) {
                    importErrors.value = flash.errors;
                }
            } else if (flash.type === "error") {
                error(flash.message);
                if (flash.errors) {
                    importErrors.value = flash.errors;
                }
            }
        } else if (flash?.success) {
            success(flash.success);
        }
    },
    { deep: true }
);

// Reactive search with debouncing
watch(searchQuery, (newValue) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            route("admin.instructors.index"),
            { search: newValue },
            {
                preserveState: true,
                preserveScroll: false,
                replace: true,
            }
        );
    }, 500);
});

const createForm = useForm({
    id_number: "",
    name: "",
    department_id: "",
});

const editForm = useForm({
    id_number: "",
    name: "",
    department_id: "",
});

const importForm = useForm({
    file: null,
    department_id: "",
});

const openCreateModal = () => {
    showCreateModal.value = true;
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
};

const handleFileChange = (event) => {
    importForm.file = event.target.files[0];
};

const downloadTemplate = () => {
    window.location.href = route("admin.instructors.template");
};

const submitImport = () => {
    importForm.post(route("admin.instructors.import"), {
        preserveScroll: true,
        onSuccess: (response) => {
            const flash = response.props.flash;

            if (flash?.type === "error") {
                error(flash.message);
                if (flash.errors) {
                    importErrors.value = flash.errors;
                }
                return;
            }

            if (flash?.errors && flash.errors.length > 0) {
                importErrors.value = flash.errors;
                if (flash.type === "success") {
                    success(flash.message);
                } else if (flash.type === "warning") {
                    warning(flash.message);
                }
                return;
            }

            closeImportModal();
            if (flash?.type === "success") {
                success(flash.message);
            } else {
                success("Instructors imported successfully");
            }
        },
        onError: () => {
            error("Failed to import instructors. Please check your file format.");
        },
    });
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const openEditModal = (instructor) => {
    editingInstructor.value = instructor;
    editForm.id_number = instructor.id_number;
    editForm.name = instructor.name;
    editForm.department_id = instructor.professor?.department_id || "";
    editForm.clearErrors();
    showEditModal.value = true;
};

const openResetPasswordModal = () => {
    showResetPasswordModal.value = true;
};

const closeResetPasswordModal = () => {
    showResetPasswordModal.value = false;
};

const confirmResetPassword = () => {
    router.post(
        route("admin.instructors.reset-password", editingInstructor.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                closeResetPasswordModal();
                success("Password reset successfully to default (chcc@2025)");
            },
            onError: () => {
                error("Failed to reset password");
            },
        }
    );
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingInstructor.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const submitCreate = () => {
    createForm.post(route("admin.instructors.store"), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            success("Instructor created successfully");
        },
        onError: () => {
            error("Failed to create instructor");
        },
    });
};

const submitEdit = () => {
    editForm.put(
        route("admin.instructors.update", editingInstructor.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeEditModal();
                success("Instructor updated successfully");
            },
            onError: () => {
                error("Failed to update instructor");
            },
        }
    );
};

const openDeleteModal = (id) => {
    instructorToDelete.value = id;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    instructorToDelete.value = null;
};

const confirmDelete = () => {
    router.delete(
        route("admin.instructors.destroy", instructorToDelete.value),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
                success("Instructor deleted successfully");
            },
            onError: () => {
                error("Failed to delete instructor");
            },
        }
    );
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
        <Head title="Instructors" />

        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-2xl font-semibold text-gray-900 dark:text-white mb-1"
                    >
                        Instructors
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Manage instructor accounts and departments
                    </p>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="openImportModal"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200"
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
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                            />
                        </svg>
                        Import Instructors
                    </button>
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200"
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
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        Add Instructor
                    </button>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="mb-6">
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
                    id="search"
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search by ID number or name..."
                    class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                />
            </div>
        </div>

        <!-- Instructors List -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
            <!-- Empty State -->
            <div v-if="!hasInstructors" class="p-12 text-center">
                <svg
                    class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                    />
                </svg>
                <h3
                    class="text-sm font-medium text-gray-900 dark:text-white mb-1"
                >
                    No instructors found
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Get started by creating a new instructor or importing from a
                    file.
                </p>
                <div class="flex gap-2 justify-center">
                    <button
                        @click="openImportModal"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200"
                    >
                        Import Instructors
                    </button>
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200"
                    >
                        Add Instructor
                    </button>
                </div>
            </div>

            <!-- Instructors Table -->
            <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                <div
                    v-for="instructor in props.instructors.data"
                    :key="instructor.id"
                    class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150"
                >
                    <div class="flex items-center justify-between gap-4">
                        <!-- Instructor Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-4">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-400 to-pink-500 flex items-center justify-center text-white font-semibold text-lg"
                                    >
                                        {{ instructor.name.charAt(0).toUpperCase() }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0 space-y-1">
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <p
                                            class="text-base font-medium text-gray-900 dark:text-white truncate"
                                        >
                                            {{ instructor.name }}
                                        </p>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300"
                                        >
                                            {{ instructor.id_number }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-xs font-medium text-gray-500 dark:text-gray-400"
                                        >
                                            Department:
                                        </span>
                                        <span
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {{
                                                instructor.professor?.department
                                                    ?.name || "No Department"
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                            <button
                                @click="openEditModal(instructor)"
                                class="p-2 text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors duration-150"
                                title="Edit"
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
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    />
                                </svg>
                            </button>
                            <button
                                @click="openDeleteModal(instructor.id)"
                                class="p-2 text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors duration-150"
                                title="Delete"
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
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="props.instructors.links && props.instructors.links.length > 3"
                class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between"
            >
                <div class="flex-1 flex justify-between sm:hidden">
                    <Link
                        v-if="props.instructors.links[0].url"
                        :href="props.instructors.links[0].url"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
                        preserve-scroll
                    >
                        Previous
                    </Link>
                    <Link
                        v-if="props.instructors.links[props.instructors.links.length - 1].url"
                        :href="props.instructors.links[props.instructors.links.length - 1].url"
                        class="ml-3 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
                        preserve-scroll
                    >
                        Next
                    </Link>
                </div>
                <div
                    class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between"
                >
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            Showing
                            <span class="font-medium">{{
                                props.instructors.from || 0
                            }}</span>
                            to
                            <span class="font-medium">{{
                                props.instructors.to || 0
                            }}</span>
                            of
                            <span class="font-medium">{{
                                props.instructors.total || 0
                            }}</span>
                            results
                        </p>
                    </div>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, index) in props.instructors.links"
                            :key="index"
                            :href="link.url || '#'"
                            v-html="link.label"
                            :class="[
                                'px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-150',
                                link.active
                                    ? 'bg-indigo-600 text-white'
                                    : 'text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700',
                                !link.url ||
                                link.url === '#' ||
                                link.url === null
                                    ? 'opacity-50 cursor-not-allowed pointer-events-none'
                                    : 'cursor-pointer',
                            ]"
                            preserve-scroll
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Instructors Modal -->
        <Modal :show="showImportModal" @close="closeImportModal" max-width="lg">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-white"
                    >
                        Import Instructors
                    </h2>
                    <button
                        @click="closeImportModal"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Instructions -->
                <div
                    class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg"
                >
                    <h3
                        class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2"
                    >
                        Instructions:
                    </h3>
                    <ul
                        class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside"
                    >
                        <li>
                            Download the template file and fill it with
                            instructor data
                        </li>
                        <li>
                            Required columns (in order):
                            <strong>id_number</strong> (1st column),
                            <strong>name</strong> (2nd column)
                        </li>
                        <li>
                            All fields are required - no empty cells allowed
                        </li>
                        <li>
                            Select the department for all instructors in the
                            file
                        </li>
                        <li>Maximum file size: 2MB (~500-1000 instructors)</li>
                        <li>
                            Supported formats: Excel (.xlsx, .xls) or CSV (.csv)
                        </li>
                        <li>
                            Default password for all imported instructors:
                            <strong>chcc@2025</strong>
                        </li>
                    </ul>
                </div>

                <!-- Download Template Button -->
                <div class="mb-6">
                    <button
                        type="button"
                        @click="downloadTemplate"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200"
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
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        Download Template
                    </button>
                </div>

                <form @submit.prevent="submitImport" class="space-y-6">
                    <!-- Department Selection -->
                    <div>
                        <InputLabel
                            for="import_department_id"
                            value="Department *"
                            class="mb-2"
                        />
                        <select
                            id="import_department_id"
                            v-model="importForm.department_id"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            required
                        >
                            <option value="" disabled>
                                Select department for all instructors
                            </option>
                            <option
                                v-for="dept in props.departments"
                                :key="dept.id"
                                :value="dept.id"
                            >
                                {{ dept.name }}
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="importForm.errors.department_id"
                        />
                        <p
                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            All instructors in the file will be assigned to this
                            department
                        </p>
                    </div>

                    <!-- File Upload -->
                    <div>
                        <InputLabel
                            for="import_file"
                            value="Excel File *"
                            class="mb-2"
                        />
                        <input
                            id="import_file"
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            @change="handleFileChange"
                            class="block w-full text-sm text-gray-900 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:rounded-r-none file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-300 file:cursor-pointer"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="importForm.errors.file"
                        />
                        <p
                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            Maximum file size: 2MB
                        </p>
                    </div>

                    <!-- Import Errors/Warnings Display -->
                    <div
                        v-if="importErrors.length > 0"
                        class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg"
                    >
                        <h4
                            class="text-sm font-semibold text-yellow-900 dark:text-yellow-100 mb-2"
                        >
                            Import Warnings ({{ importErrors.length }}):
                        </h4>
                        <div class="max-h-40 overflow-y-auto">
                            <ul
                                class="text-xs text-yellow-800 dark:text-yellow-200 space-y-1"
                            >
                                <li
                                    v-for="(err, idx) in importErrors"
                                    :key="idx"
                                >
                                    • {{ err }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div
                        class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700"
                    >
                        <SecondaryButton
                            type="button"
                            @click="closeImportModal"
                            class="px-4 py-2"
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            :disabled="importForm.processing"
                            class="px-4 py-2"
                        >
                            <span v-if="importForm.processing"
                                >Importing...</span
                            >
                            <span v-else>Import Instructors</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Create Instructor Modal -->
        <Modal :show="showCreateModal" @close="closeCreateModal">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-white"
                    >
                        Create Instructor
                    </h2>
                    <button
                        @click="closeCreateModal"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitCreate" class="space-y-6">
                    <div>
                        <InputLabel
                            for="create_id_number"
                            value="ID Number"
                            class="mb-2"
                        />
                        <TextInput
                            id="create_id_number"
                            v-model="createForm.id_number"
                            type="text"
                            class="block w-full"
                            placeholder="Enter instructor ID number"
                            required
                            autofocus
                        />
                        <InputError
                            class="mt-2"
                            :message="createForm.errors.id_number"
                        />
                    </div>
                    <div>
                        <InputLabel
                            for="create_name"
                            value="Full Name"
                            class="mb-2"
                        />
                        <TextInput
                            id="create_name"
                            v-model="createForm.name"
                            type="text"
                            class="block w-full"
                            placeholder="Enter instructor full name"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="createForm.errors.name"
                        />
                    </div>
                    <div>
                        <InputLabel
                            for="create_department_id"
                            value="Department"
                            class="mb-2"
                        />
                        <select
                            id="create_department_id"
                            v-model="createForm.department_id"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            required
                        >
                            <option value="" disabled>Select department</option>
                            <option
                                v-for="dept in props.departments"
                                :key="dept.id"
                                :value="dept.id"
                            >
                                {{ dept.name }}
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="createForm.errors.department_id"
                        />
                    </div>
                    <div
                        class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700"
                    >
                        <SecondaryButton
                            type="button"
                            @click="closeCreateModal"
                            class="px-4 py-2"
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            :disabled="createForm.processing"
                            class="px-4 py-2"
                        >
                            Create Instructor
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Instructor Modal -->
        <Modal :show="showEditModal" @close="closeEditModal">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-white"
                    >
                        Edit Instructor
                    </h2>
                    <button
                        @click="closeEditModal"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitEdit" class="space-y-6">
                    <div>
                        <InputLabel
                            for="edit_id_number"
                            value="ID Number"
                            class="mb-2"
                        />
                        <TextInput
                            id="edit_id_number"
                            v-model="editForm.id_number"
                            type="text"
                            class="block w-full"
                            required
                            autofocus
                        />
                        <InputError
                            class="mt-2"
                            :message="editForm.errors.id_number"
                        />
                    </div>
                    <div>
                        <InputLabel
                            for="edit_name"
                            value="Full Name"
                            class="mb-2"
                        />
                        <TextInput
                            id="edit_name"
                            v-model="editForm.name"
                            type="text"
                            class="block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="editForm.errors.name"
                        />
                    </div>
                    <div>
                        <InputLabel
                            for="edit_department_id"
                            value="Department"
                            class="mb-2"
                        />
                        <select
                            id="edit_department_id"
                            v-model="editForm.department_id"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        >
                            <option value="" disabled>Select department</option>
                            <option
                                v-for="dept in props.departments"
                                :key="dept.id"
                                :value="dept.id"
                            >
                                {{ dept.name }}
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="editForm.errors.department_id"
                        />
                    </div>
                    <div
                        class="pt-4 border-t border-gray-200 dark:border-gray-700"
                    >
                        <div
                            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                        >
                            <div>
                                <p
                                    class="text-sm font-medium text-gray-900 dark:text-white"
                                >
                                    Password
                                </p>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                                >
                                    Default: chcc@2025
                                </p>
                            </div>
                            <SecondaryButton
                                type="button"
                                @click="openResetPasswordModal"
                                class="px-4 py-2"
                            >
                                Reset Password
                            </SecondaryButton>
                        </div>
                    </div>
                    <div
                        class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700"
                    >
                        <SecondaryButton
                            type="button"
                            @click="closeEditModal"
                            class="px-4 py-2"
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            :disabled="editForm.processing"
                            class="px-4 py-2"
                        >
                            Save Changes
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Reset Password Confirmation Modal -->
        <ConfirmationModal
            :show="showResetPasswordModal"
            title="Reset Password"
            message="Are you sure you want to reset the password to default (chcc@2025)?"
            confirm-text="Reset Password"
            cancel-text="Cancel"
            variant="warning"
            @close="closeResetPasswordModal"
            @confirm="confirmResetPassword"
        />

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :show="showDeleteModal"
            title="Delete Instructor"
            message="Are you sure you want to delete this instructor? This action cannot be undone and may affect associated lessons, assessments, and assignments."
            confirm-text="Delete"
            cancel-text="Cancel"
            variant="danger"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
