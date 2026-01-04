<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DataTable from "@/Components/DataTable.vue";
import Modal from "@/Components/Modal.vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { watch, ref } from "vue";
import { useToast } from "@/Stores/useToast";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";

const props = defineProps({
    assignments: Object,
    professors: Array,
    subjects: Array,
});

const form = useForm({
    professor_id: "",
    subject_id: "",
});

const page = usePage();
const { success, error } = useToast();
const showCreateModal = ref(false);
const showDeleteModal = ref(false);
const assignmentToDelete = ref(null);

// Watch for flash messages
watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) {
            success(message);
        }
    }
);

const openDeleteModal = (id) => {
    assignmentToDelete.value = id;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    assignmentToDelete.value = null;
};

const confirmDelete = () => {
    router.delete(
        route("admin.assignments.destroy", assignmentToDelete.value),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
                success("Assignment removed successfully");
            },
            onError: () => {
                error("Failed to remove assignment");
            },
        }
    );
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
    form.post(route("admin.assignments.store"), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            success("Assignment created successfully");
        },
        onError: () => {
            error("Failed to create assignment");
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Assignments" />
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Assignments</h1>
            <button @click="openCreateModal" class="btn-primary">
                Assign Instructor
            </button>
        </div>

        <div class="card p-4">
            <DataTable
                :headers="['Professor', 'Subject', 'Actions']"
                :rows="assignments.data"
                :links="assignments.links"
                empty-text="No assignments."
                class="h-[497px]"
            >
                <template #row="{ row }">
                    <td class="px-3 py-2">{{ row.professor?.user?.name }}</td>
                    <td class="px-3 py-2">
                        {{ row.subject?.code }} - {{ row.subject?.name }}
                    </td>
                    <td class="px-3 py-2 text-right">
                        <button
                            type="button"
                            class="text-red-600 hover:underline"
                            @click.prevent="openDeleteModal(row.id)"
                        >
                            Remove
                        </button>
                    </td>
                </template>
            </DataTable>
        </div>

        <!-- Create Assignment Modal -->
        <Modal :show="showCreateModal" @close="closeCreateModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">
                    Assign Instructor to Subject
                </h2>
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div>
                        <InputLabel
                            for="create_professor_id"
                            value="Instructor"
                        />
                        <select
                            id="create_professor_id"
                            v-model="form.professor_id"
                            class="input mt-1"
                        >
                            <option value="" disabled>Select instructor</option>
                            <option
                                v-for="prof in props.professors"
                                :key="prof.id"
                                :value="prof.id"
                            >
                                {{ prof.user?.name }} ({{
                                    prof.department?.name
                                }})
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.professor_id"
                        />
                    </div>
                    <div>
                        <InputLabel for="create_subject_id" value="Subject" />
                        <select
                            id="create_subject_id"
                            v-model="form.subject_id"
                            class="input mt-1"
                        >
                            <option value="" disabled>Select subject</option>
                            <option
                                v-for="sub in props.subjects"
                                :key="sub.id"
                                :value="sub.id"
                            >
                                {{ sub.code }} - {{ sub.name }}
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.subject_id"
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
                            Assign
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :show="showDeleteModal"
            title="Remove Assignment"
            message="Are you sure you want to remove this assignment? This action cannot be undone."
            confirm-text="Remove"
            cancel-text="Cancel"
            variant="danger"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
