<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import Modal from "@/Components/Modal.vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { watch, ref, computed } from "vue";
import { useToast } from "@/Stores/useToast";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";

const props = defineProps({
    departments: Object,
    filters: Object,
});

const form = useForm({
    name: "",
});

const page = usePage();
const { success, error } = useToast();
const updateForms = {};
const editingId = ref(null);
const showCreateModal = ref(false);
const showDeleteModal = ref(false);
const departmentToDelete = ref(null);

// Watch for flash messages
watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) {
            success(message);
        }
    }
);

const hasDepartments = computed(() => props.departments.data?.length > 0);

const openCreateModal = () => {
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    form.reset();
    form.clearErrors();
};

const submitCreate = () => {
    form.post(route("admin.departments.store"), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            success("Department created successfully");
        },
        onError: () => {
            error("Failed to create department");
        },
    });
};

const startEdit = (row) => {
    editingId.value = row.id;
    if (!updateForms[row.id]) {
        updateForms[row.id] = useForm({
            name: row.name,
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
    
    updateForms[id].put(route("admin.departments.update", id), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            success("Department updated successfully");
        },
        onError: () => {
            error("Failed to update department");
        },
    });
};

const openDeleteModal = (id) => {
    departmentToDelete.value = id;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    departmentToDelete.value = null;
};

const confirmDelete = () => {
    router.delete(
        route("admin.departments.destroy", departmentToDelete.value),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
                success("Department deleted successfully");
            },
            onError: () => {
                error("Failed to delete department");
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
        <Head title="Departments" />
        
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-1">
                        Departments
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Manage academic departments
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Department
                </button>
            </div>
        </div>

        <!-- Departments List -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Empty State -->
            <div v-if="!hasDepartments" class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">No departments</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Get started by creating a new department.</p>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Department
                </button>
            </div>

            <!-- Departments Table -->
            <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                <div
                    v-for="department in props.departments.data"
                    :key="department.id"
                    class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150"
                >
                    <div class="flex items-center justify-between gap-4">
                        <!-- Department Name -->
                        <div class="flex-1 min-w-0">
                            <div v-if="editingId !== department.id" class="flex items-center gap-3">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-base font-medium text-gray-900 dark:text-white truncate">
                                        {{ department.name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        Created {{ formatDate(department.created_at) }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Edit Mode -->
                            <div v-else class="space-y-2">
                                <input
                                    v-model="(updateForms[department.id] ||= useForm({ name: department.name })).name"
                                    type="text"
                                    class="w-full px-3 py-2 text-base border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                    placeholder="Department name"
                                    @keyup.enter="saveEdit(department.id, department)"
                                    @keyup.esc="cancelEdit(department.id)"
                                    autofocus
                                />
                                <InputError
                                    class="text-xs"
                                    :message="updateForms[department.id]?.errors?.name"
                                />
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                            <template v-if="editingId !== department.id">
                                <button
                                    @click="startEdit(department)"
                                    class="p-2 text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors duration-150"
                                    title="Edit"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button
                                    @click="openDeleteModal(department.id)"
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
                                    @click="saveEdit(department.id, department)"
                                    :disabled="updateForms[department.id]?.processing"
                                    class="p-2 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                                    title="Save"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                                <button
                                    @click="cancelEdit(department.id)"
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
            <div v-if="props.departments.links && props.departments.links.length > 3" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <Link
                        v-if="props.departments.links[0].url"
                        :href="props.departments.links[0].url"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
                        preserve-scroll
                    >
                        Previous
                    </Link>
                    <Link
                        v-if="props.departments.links[props.departments.links.length - 1].url"
                        :href="props.departments.links[props.departments.links.length - 1].url"
                        class="ml-3 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
                        preserve-scroll
                    >
                        Next
                    </Link>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            Showing <span class="font-medium">{{ props.departments.from || 0 }}</span> to
                            <span class="font-medium">{{ props.departments.to || 0 }}</span> of
                            <span class="font-medium">{{ props.departments.total || 0 }}</span> results
                        </p>
                    </div>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, index) in props.departments.links"
                            :key="index"
                            :href="link.url || '#'"
                            v-html="link.label"
                            :class="[
                                'px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-150',
                                link.active
                                    ? 'bg-indigo-600 text-white'
                                    : 'text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700',
                                !link.url || link.url === '#' || link.url === null
                                    ? 'opacity-50 cursor-not-allowed pointer-events-none'
                                    : 'cursor-pointer'
                            ]"
                            preserve-scroll
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Department Modal -->
        <Modal :show="showCreateModal" @close="closeCreateModal">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Create Department
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
                        <InputLabel for="create_name" value="Department Name" class="mb-2" />
                        <TextInput
                            id="create_name"
                            v-model="form.name"
                            type="text"
                            class="block w-full"
                            placeholder="Enter department name"
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
                            Create Department
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :show="showDeleteModal"
            title="Delete Department"
            message="Are you sure you want to delete this department? This action cannot be undone and may affect associated sections and subjects."
            confirm-text="Delete"
            cancel-text="Cancel"
            variant="danger"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
