<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DataTable from "@/Components/DataTable.vue";
import Badge from "@/Components/Badge.vue";
import Modal from "@/Components/Modal.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { ref, watch } from "vue";
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

// Watch for flash messages
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.message) {
            if (flash.type === "success") {
                success(flash.message);
                // Check if there are warnings/errors to display
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
            // Fallback for old flash.success format
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

            // Check flash type first
            if (flash?.type === "error") {
                error(flash.message);
                if (flash.errors) {
                    importErrors.value = flash.errors;
                }
                return; // Don't close modal
            }

            // If there are errors/warnings (skipped rows), don't close modal
            if (flash?.errors && flash.errors.length > 0) {
                importErrors.value = flash.errors;

                // Show success message but keep modal open to display warnings
                if (flash.type === "success") {
                    success(flash.message);
                } else if (flash.type === "warning") {
                    warning(flash.message);
                }
                return; // Don't close modal so user can see the warnings
            }

            // All instructors imported successfully - close modal
            closeImportModal();
            if (flash?.type === "success") {
                success(flash.message);
            } else {
                success("Instructors imported successfully");
            }
        },
        onError: () => {
            error(
                "Failed to import instructors. Please check your file format."
            );
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
</script>

<template>
    <AdminLayout>
        <Head title="Instructors" />
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Instructors</h1>
            <div class="flex gap-2">
                <button @click="openImportModal" class="btn-secondary">
                    Import Instructors
                </button>
                <button @click="openCreateModal" class="btn-primary">
                    Create Instructor
                </button>
            </div>
        </div>

        <div class="card p-4 space-y-4">
            <div>
                <InputLabel for="search" value="Search" />
                <input
                    id="search"
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search by ID number or name..."
                    class="input mt-1"
                />
            </div>
            <DataTable
                :headers="['ID #', 'Name', 'Department', 'Actions']"
                :rows="instructors.data"
                :links="instructors.links"
                empty-text="No instructors found."
                class="h-[444px]"
            >
                <template #row="{ row }">
                    <td class="px-3 py-2">{{ row.id_number }}</td>
                    <td class="px-3 py-2">{{ row.name }}</td>
                    <td class="px-3 py-2">
                        {{ row.professor?.department?.name || "—" }}
                    </td>
                    <td class="px-3 py-2 text-right space-x-2">
                        <button
                            @click="openEditModal(row)"
                            class="text-indigo-600 hover:underline text-sm"
                        >
                            Edit
                        </button>
                        <button
                            class="text-red-600 hover:underline text-sm"
                            @click.prevent="openDeleteModal(row.id)"
                        >
                            Delete
                        </button>
                    </td>
                </template>
            </DataTable>
        </div>

        <!-- Import Instructors Modal -->
        <Modal :show="showImportModal" @close="closeImportModal" max-width="lg">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Import Instructors</h2>

                <!-- Instructions -->
                <div
                    class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg"
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
                <div class="mb-4">
                    <button
                        type="button"
                        @click="downloadTemplate"
                        class="btn-secondary w-full"
                    >
                        <svg
                            class="w-4 h-4 inline-block mr-2"
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
                        Download Excel Template
                    </button>
                </div>

                <!-- Import Form -->
                <form @submit.prevent="submitImport" class="space-y-4">
                    <!-- Department Selection -->
                    <div>
                        <InputLabel
                            for="import_department_id"
                            value="Department"
                        />
                        <select
                            id="import_department_id"
                            v-model="importForm.department_id"
                            class="input mt-1"
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
                    </div>

                    <!-- File Upload -->
                    <div>
                        <InputLabel for="import_file" value="Excel File" />
                        <input
                            id="import_file"
                            type="file"
                            @change="handleFileChange"
                            accept=".xlsx,.xls,.csv"
                            class="mt-1 block w-full text-sm text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="importForm.errors.file"
                        />
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
                    <div class="flex justify-end gap-3 mt-6">
                        <SecondaryButton
                            type="button"
                            @click="closeImportModal"
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton :disabled="importForm.processing">
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
                <h2 class="text-lg font-semibold mb-4">Create Instructor</h2>
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div>
                        <InputLabel for="create_id_number" value="ID Number" />
                        <TextInput
                            id="create_id_number"
                            v-model="createForm.id_number"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="createForm.errors.id_number"
                        />
                    </div>
                    <div>
                        <InputLabel for="create_name" value="Name" />
                        <TextInput
                            id="create_name"
                            v-model="createForm.name"
                            type="text"
                            class="mt-1 block w-full"
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
                        />
                        <select
                            id="create_department_id"
                            v-model="createForm.department_id"
                            class="input mt-1"
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
                    <div class="flex justify-end gap-3 mt-6">
                        <SecondaryButton type="button" @click="closeCreateModal"
                            >Cancel</SecondaryButton
                        >
                        <PrimaryButton :disabled="createForm.processing"
                            >Save</PrimaryButton
                        >
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Instructor Modal -->
        <Modal :show="showEditModal" @close="closeEditModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Edit Instructor</h2>
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div>
                        <InputLabel for="edit_id_number" value="ID Number" />
                        <TextInput
                            id="edit_id_number"
                            v-model="editForm.id_number"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="editForm.errors.id_number"
                        />
                    </div>
                    <div>
                        <InputLabel for="edit_name" value="Name" />
                        <TextInput
                            id="edit_name"
                            v-model="editForm.name"
                            type="text"
                            class="mt-1 block w-full"
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
                        />
                        <select
                            id="edit_department_id"
                            v-model="editForm.department_id"
                            class="input mt-1"
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
                        class="flex items-center justify-between pt-2 border-t border-border-light dark:border-border-dark"
                    >
                        <div>
                            <p
                                class="text-sm text-text-secondary dark:text-text-secondary-dark"
                            >
                                Password
                            </p>
                            <p
                                class="text-xs text-text-secondary dark:text-text-secondary-dark mt-1"
                            >
                                Default: chcc@2025
                            </p>
                        </div>
                        <SecondaryButton
                            type="button"
                            @click="openResetPasswordModal"
                        >
                            Reset Password
                        </SecondaryButton>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <SecondaryButton type="button" @click="closeEditModal"
                            >Cancel</SecondaryButton
                        >
                        <PrimaryButton :disabled="editForm.processing"
                            >Save</PrimaryButton
                        >
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
            message="Are you sure you want to delete this instructor? This action cannot be undone."
            confirm-text="Delete"
            cancel-text="Cancel"
            variant="danger"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
