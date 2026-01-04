<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import DataTable from "@/Components/DataTable.vue";
import Modal from "@/Components/Modal.vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { watch, ref } from "vue";
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
</script>

<template>
    <AdminLayout>
        <Head title="Departments" />
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Departments</h1>
            <button @click="openCreateModal" class="btn-primary">
                Create Department
            </button>
        </div>

        <div class="card p-4">
            <DataTable
                :headers="['Name', 'Actions']"
                :rows="departments.data"
                :links="departments.links"
                empty-text="No departments."
                class="h-[497px]"
            >
                <template #row="{ row }">
                    <td class="px-3 py-2">
                        <input
                            v-model="
                                (updateForms[row.id] ||= useForm({
                                    name: row.name,
                                })).name
                            "
                            type="text"
                            class="input"
                        />
                        <InputError
                            class="mt-1"
                            :message="updateForms[row.id]?.errors?.name"
                        />
                    </td>
                    <td class="px-3 py-2 space-x-2">
                        <Link
                            as="button"
                            method="put"
                            :href="route('admin.departments.update', row.id)"
                            class="text-indigo-600 hover:underline"
                            preserve-scroll
                            @click.prevent="
                                updateForms[row.id].put(
                                    route('admin.departments.update', row.id),
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            success(
                                                'Department updated successfully'
                                            );
                                        },
                                        onError: () => {
                                            error(
                                                'Failed to update department'
                                            );
                                        },
                                    }
                                )
                            "
                        >
                            Update
                        </Link>
                        <button
                            type="button"
                            class="text-red-600 hover:underline"
                            @click.prevent="openDeleteModal(row.id)"
                        >
                            Delete
                        </button>
                    </td>
                </template>
            </DataTable>
        </div>

        <!-- Create Department Modal -->
        <Modal :show="showCreateModal" @close="closeCreateModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Create Department</h2>
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div>
                        <InputLabel for="create_name" value="Name" />
                        <TextInput
                            id="create_name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <SecondaryButton
                            type="button"
                            @click="closeCreateModal"
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton :disabled="form.processing">
                            Save
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :show="showDeleteModal"
            title="Delete Department"
            message="Are you sure you want to delete this department? This action cannot be undone."
            confirm-text="Delete"
            cancel-text="Cancel"
            variant="danger"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
