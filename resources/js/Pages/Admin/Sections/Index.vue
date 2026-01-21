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
    sections: Object,
    departments: Array,
    filters: Object,
});

const form = useForm({
    department_id: "",
    name: "",
});

const page = usePage();
const { success, error } = useToast();
const updateForms = {};
const editingId = ref(null);
const showCreateModal = ref(false);
const showDeleteModal = ref(false);
const sectionToDelete = ref(null);

// Watch for flash messages
watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) {
            success(message);
        }
    }
);

const hasSections = computed(() => props.sections.data?.length > 0);

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

        <!-- Sections List -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Empty State -->
            <div v-if="!hasSections" class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">No sections</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Get started by creating a new section.</p>
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
                                    <select
                                        v-model="(updateForms[section.id] ||= useForm({ name: section.name, department_id: section.department_id })).department_id"
                                        class="w-full px-3 py-2 text-base border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
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
            <div v-if="props.sections.links && props.sections.links.length > 3" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <Link
                        v-if="props.sections.links[0].url"
                        :href="props.sections.links[0].url"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
                        preserve-scroll
                    >
                        Previous
                    </Link>
                    <Link
                        v-if="props.sections.links[props.sections.links.length - 1].url"
                        :href="props.sections.links[props.sections.links.length - 1].url"
                        class="ml-3 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
                        preserve-scroll
                    >
                        Next
                    </Link>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            Showing <span class="font-medium">{{ props.sections.from || 0 }}</span> to
                            <span class="font-medium">{{ props.sections.to || 0 }}</span> of
                            <span class="font-medium">{{ props.sections.total || 0 }}</span> results
                        </p>
                    </div>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, index) in props.sections.links"
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
                        <InputLabel for="create_department_id" value="Department" class="mb-2" />
                        <select
                            id="create_department_id"
                            v-model="form.department_id"
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
