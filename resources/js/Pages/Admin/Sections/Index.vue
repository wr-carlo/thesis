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
</script>

<template>
    <AdminLayout>
        <Head title="Sections" />
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Sections</h1>
            <button @click="openCreateModal" class="btn-primary">
                Create Section
            </button>
        </div>

        <div class="card p-4">
            <DataTable
                :headers="['Section', 'Department', 'Actions']"
                :rows="sections.data"
                :links="sections.links"
                empty-text="No sections."
                class="h-[497px]"
            >
                <template #row="{ row }">
                    <td class="px-3 py-2">
                        <input
                            v-model="
                                (updateForms[row.id] ||= useForm({
                                    name: row.name,
                                    department_id: row.department_id,
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
                    <td class="px-3 py-2">
                        <select
                            v-model="
                                (updateForms[row.id] ||= useForm({
                                    name: row.name,
                                    department_id: row.department_id,
                                })).department_id
                            "
                            class="input"
                        >
                            <option
                                v-for="dept in props.departments"
                                :key="dept.id"
                                :value="dept.id"
                            >
                                {{ dept.name }}
                            </option>
                        </select>
                        <InputError
                            class="mt-1"
                            :message="
                                updateForms[row.id]?.errors?.department_id
                            "
                        />
                    </td>
                    <td class="px-3 py-2 text-right space-x-2">
                        <Link
                            as="button"
                            method="put"
                            :href="route('admin.sections.update', row.id)"
                            class="text-indigo-600 hover:underline"
                            preserve-scroll
                            @click.prevent="
                                updateForms[row.id].put(
                                    route('admin.sections.update', row.id),
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            success(
                                                'Section updated successfully'
                                            );
                                        },
                                        onError: () => {
                                            error('Failed to update section');
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

        <!-- Create Section Modal -->
        <Modal :show="showCreateModal" @close="closeCreateModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Create Section</h2>
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div>
                        <InputLabel
                            for="create_department_id"
                            value="Department"
                        />
                        <select
                            id="create_department_id"
                            v-model="form.department_id"
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
                            :message="form.errors.department_id"
                        />
                    </div>
                    <div>
                        <InputLabel for="create_name" value="Section Name" />
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
            title="Delete Section"
            message="Are you sure you want to delete this section? This action cannot be undone."
            confirm-text="Delete"
            cancel-text="Cancel"
            variant="danger"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
