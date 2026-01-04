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
    subjects: Object,
    filters: Object,
});

const form = useForm({
    name: "",
    code: "",
    description: "",
});

const page = usePage();
const { success, error } = useToast();
const updateForms = {};
const showCreateModal = ref(false);
const showDeleteModal = ref(false);
const subjectToDelete = ref(null);

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
    form.post(route("admin.subjects.store"), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            success("Subject created successfully");
        },
        onError: () => {
            error("Failed to create subject");
        },
    });
};

const openDeleteModal = (id) => {
    subjectToDelete.value = id;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    subjectToDelete.value = null;
};

const confirmDelete = () => {
    router.delete(route("admin.subjects.destroy", subjectToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
            success("Subject deleted successfully");
        },
        onError: () => {
            error("Failed to delete subject");
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Subjects" />
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Subjects</h1>
            <button @click="openCreateModal" class="btn-primary">
                Create Subject
            </button>
        </div>

        <div class="card p-4">
            <DataTable
                :headers="['Code', 'Name', 'Actions']"
                :rows="subjects.data"
                :links="subjects.links"
                empty-text="No subjects."
                class="h-[497px]"
            >
                <template #row="{ row }">
                    <td class="px-3 py-2">
                        <input
                            v-model="
                                (updateForms[row.id] ||= useForm({
                                    name: row.name,
                                    code: row.code,
                                    description: row.description,
                                })).code
                            "
                            type="text"
                            class="input"
                        />
                        <InputError
                            class="mt-1"
                            :message="updateForms[row.id]?.errors?.code"
                        />
                    </td>
                    <td class="px-3 py-2">
                        <input
                            v-model="
                                (updateForms[row.id] ||= useForm({
                                    name: row.name,
                                    code: row.code,
                                    description: row.description,
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
                    <td class="px-3 py-2 text-left space-x-2">
                        <Link
                            as="button"
                            method="put"
                            :href="route('admin.subjects.update', row.id)"
                            class="text-indigo-600 hover:underline"
                            preserve-scroll
                            @click.prevent="
                                updateForms[row.id].put(
                                    route('admin.subjects.update', row.id),
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            success(
                                                'Subject updated successfully'
                                            );
                                        },
                                        onError: () => {
                                            error('Failed to update subject');
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

        <!-- Create Subject Modal -->
        <Modal :show="showCreateModal" @close="closeCreateModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Create Subject</h2>
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
                    <div>
                        <InputLabel for="create_code" value="Code" />
                        <TextInput
                            id="create_code"
                            v-model="form.code"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>
                    <div>
                        <InputLabel
                            for="create_description"
                            value="Description"
                        />
                        <textarea
                            id="create_description"
                            v-model="form.description"
                            class="input mt-1 min-h-[120px]"
                            rows="3"
                        ></textarea>
                        <InputError
                            class="mt-2"
                            :message="form.errors.description"
                        />
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
            title="Delete Subject"
            message="Are you sure you want to delete this subject? This action cannot be undone."
            confirm-text="Delete"
            cancel-text="Cancel"
            variant="danger"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
