<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import SearchableSelect from "@/Components/SearchableSelect.vue";
import Modal from "@/Components/Modal.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useToast } from "@/Stores/useToast";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";

const props = defineProps({
    subjects: Array,
    professors: Array,
});

const { success, error } = useToast();

// Search
const searchQuery = ref("");

// Subject filter (dropdown to filter by specific subject)
const subjectFilterId = ref("");

const subjectFilterOptions = computed(() => [
    { value: "", label: "All subjects" },
    ...props.subjects.map((s) => ({
        value: String(s.id),
        label: `${s.code} — ${s.name}`,
    })),
]);

const filteredSubjects = computed(() => {
    let list = props.subjects;

    // Filter by selected subject (null/empty = all)
    const subjectId = subjectFilterId.value;
    if (subjectId != null && subjectId !== "") {
        list = list.filter((s) => String(s.id) === subjectId);
    }

    // Filter by search query
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(
            (s) =>
                s.name.toLowerCase().includes(q) ||
                s.code.toLowerCase().includes(q) ||
                s.assignments.some((a) => a.professor_name.toLowerCase().includes(q))
        );
    }

    return list;
});

// Assign modal
const showAssignModal = ref(false);
const assignSubjectName = ref("");
const assignForm = useForm({
    professor_id: "",
    subject_id: "",
});

// Options for the SearchableSelect
const professorOptions = computed(() =>
    props.professors.map((p) => ({
        value: p.id,
        label: p.user?.name || "Unknown",
        sublabel: p.department?.name || "No Department",
    }))
);

const openAssignModal = (subject) => {
    assignSubjectName.value = `${subject.code} — ${subject.name}`;
    assignForm.subject_id = subject.id;
    assignForm.professor_id = "";
    showAssignModal.value = true;
};

const closeAssignModal = () => {
    showAssignModal.value = false;
    assignForm.reset();
    assignForm.clearErrors();
};

const submitAssign = () => {
    assignForm.post(route("admin.assignments.store"), {
        preserveScroll: true,
        onSuccess: () => {
            closeAssignModal();
            success("Instructor assigned successfully");
        },
        onError: () => {
            error("Failed to assign instructor");
        },
    });
};

// Remove
const showDeleteModal = ref(false);
const assignmentToDelete = ref(null);

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
                success("Instructor removed successfully");
            },
            onError: () => {
                error("Failed to remove instructor");
            },
        }
    );
};

// Available professors for a subject (exclude already assigned)
const availableProfessors = (subject) => {
    const assignedIds = subject.assignments.map((a) => a.professor_id);
    return props.professors.filter((p) => !assignedIds.includes(p.id));
};

const formatDate = (dateString) => {
    if (!dateString) return "";
    return new Date(dateString).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Assignments" />

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-1">
                Assignments
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Manage instructor assignments per subject
            </p>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-col sm:flex-row gap-4">
            <div class="flex-1 min-w-0">
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
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search subjects or instructors..."
                        class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all"
                    />
                </div>
            </div>
            <div class="sm:w-64">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                    Filter by subject
                </label>
                <SearchableSelect
                    v-model="subjectFilterId"
                    :options="subjectFilterOptions"
                    placeholder="Filter by subject..."
                />
            </div>
        </div>

        <!-- Subject Cards Grid -->
        <div
            v-if="filteredSubjects.length > 0"
            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"
        >
            <div
                v-for="subject in filteredSubjects"
                :key="subject.id"
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col"
            >
                <!-- Card Header -->
                <div class="px-5 pt-5 pb-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <span class="text-xs font-mono font-medium text-indigo-600 dark:text-indigo-400">
                                {{ subject.code }}
                            </span>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mt-0.5 leading-snug">
                                {{ subject.name }}
                            </h3>
                        </div>
                        <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            {{ subject.assignments.length }} instructor{{ subject.assignments.length !== 1 ? 's' : '' }}
                        </span>
                    </div>
                </div>

                <!-- Assigned Instructors -->
                <div class="flex-1 px-5 pb-3">
                    <!-- Empty state -->
                    <div
                        v-if="subject.assignments.length === 0"
                        class="py-6 text-center"
                    >
                        <svg
                            class="mx-auto w-8 h-8 text-gray-300 dark:text-gray-600 mb-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            No instructor assigned
                        </p>
                    </div>

                    <!-- Instructor list -->
                    <div v-else class="space-y-2">
                        <div
                            v-for="assignment in subject.assignments"
                            :key="assignment.id"
                            class="group flex items-center justify-between gap-2 py-2 px-3 -mx-1 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    {{ assignment.professor_name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ assignment.professor_name }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate">
                                        {{ assignment.department_name }}
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="openDeleteModal(assignment.id)"
                                class="opacity-0 group-hover:opacity-100 p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-all"
                                title="Remove"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Card Footer — Add Button -->
                <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700/50">
                    <button
                        @click="openAssignModal(subject)"
                        class="w-full flex items-center justify-center gap-1.5 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Assign Instructor
                    </button>
                </div>
            </div>
        </div>

        <!-- No Results -->
        <div
            v-else
            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center"
        >
            <svg
                class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-3"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
            </svg>
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                No subjects found
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ subjectFilterId || searchQuery ? "Try adjusting your filters or search query." : "No subjects in database yet." }}
            </p>
        </div>

        <!-- Assign Instructor Modal -->
        <Modal :show="showAssignModal" @close="closeAssignModal">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Assign Instructor
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ assignSubjectName }}
                        </p>
                    </div>
                    <button
                        @click="closeAssignModal"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitAssign" class="space-y-5">
                    <div>
                        <InputLabel value="Instructor" class="mb-2" />
                        <SearchableSelect
                            v-model="assignForm.professor_id"
                            :options="professorOptions"
                            placeholder="Search instructor by name or department..."
                        />
                        <InputError class="mt-2" :message="assignForm.errors.professor_id" />
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <SecondaryButton type="button" @click="closeAssignModal" class="px-4 py-2">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton :disabled="assignForm.processing" class="px-4 py-2">
                            Assign
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :show="showDeleteModal"
            title="Remove Instructor"
            message="Are you sure you want to remove this instructor from the subject? This action cannot be undone."
            confirm-text="Remove"
            cancel-text="Cancel"
            variant="danger"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
