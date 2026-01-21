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
const editingId = ref(null);
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

const hasSubjects = computed(() => props.subjects.data?.length > 0);

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

const startEdit = (row) => {
    editingId.value = row.id;
    if (!updateForms[row.id]) {
        updateForms[row.id] = useForm({
            name: row.name,
            code: row.code,
            description: row.description || "",
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

    updateForms[id].put(route("admin.subjects.update", id), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            success("Subject updated successfully");
        },
        onError: () => {
            error("Failed to update subject");
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

const formatDate = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const truncateText = (text, maxLength = 80) => {
    if (!text) return "";
    return text.length > maxLength
        ? text.substring(0, maxLength) + "..."
        : text;
};
</script>

<template>
    <AdminLayout>
        <Head title="Subjects" />

        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-2xl font-semibold text-gray-900 dark:text-white mb-1"
                    >
                        Subjects
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Manage academic subjects
                    </p>
                </div>
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
                    Add Subject
                </button>
            </div>
        </div>

        <!-- Subjects List -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
            <!-- Empty State -->
            <div v-if="!hasSubjects" class="p-12 text-center">
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
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                    />
                </svg>
                <h3
                    class="text-sm font-medium text-gray-900 dark:text-white mb-1"
                >
                    No subjects
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Get started by creating a new subject.
                </p>
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
                    Add Subject
                </button>
            </div>

            <!-- Subjects Table -->
            <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                <div
                    v-for="subject in props.subjects.data"
                    :key="subject.id"
                    class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150"
                >
                    <div class="flex items-center justify-between gap-4">
                        <!-- Subject Info -->
                        <div class="flex-1 min-w-0">
                            <div
                                v-if="editingId !== subject.id"
                                class="flex items-center gap-4"
                            >
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-12 h-12 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center"
                                    >
                                        <svg
                                            class="w-6 h-6 text-emerald-600 dark:text-emerald-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                            />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0 space-y-2">
                                    <div
                                        class="flex items-center gap-3 flex-wrap"
                                    >
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300"
                                        >
                                            {{ subject.code }}
                                        </span>
                                        <p
                                            class="text-base font-medium text-gray-900 dark:text-white"
                                        >
                                            {{ subject.name }}
                                        </p>
                                    </div>
                                    <p
                                        v-if="subject.description"
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        {{ truncateText(subject.description) }}
                                    </p>
                                    <p
                                        v-if="subject.created_at"
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        Created
                                        {{ formatDate(subject.created_at) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Edit Mode -->
                            <div v-else class="space-y-3">
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        Code
                                    </label>
                                    <input
                                        v-model="
                                            (updateForms[subject.id] ||=
                                                useForm({
                                                    name: subject.name,
                                                    code: subject.code,
                                                    description:
                                                        subject.description ||
                                                        '',
                                                })).code
                                        "
                                        type="text"
                                        class="w-full px-3 py-2 text-base border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                        placeholder="Subject code (e.g., CS201)"
                                        @keyup.enter="
                                            saveEdit(subject.id, subject)
                                        "
                                        @keyup.esc="cancelEdit(subject.id)"
                                        autofocus
                                    />
                                    <InputError
                                        class="text-xs mt-1"
                                        :message="
                                            updateForms[subject.id]?.errors
                                                ?.code
                                        "
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        Name
                                    </label>
                                    <input
                                        v-model="
                                            (updateForms[subject.id] ||=
                                                useForm({
                                                    name: subject.name,
                                                    code: subject.code,
                                                    description:
                                                        subject.description ||
                                                        '',
                                                })).name
                                        "
                                        type="text"
                                        class="w-full px-3 py-2 text-base border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                        placeholder="Subject name"
                                        @keyup.enter="
                                            saveEdit(subject.id, subject)
                                        "
                                        @keyup.esc="cancelEdit(subject.id)"
                                    />
                                    <InputError
                                        class="text-xs mt-1"
                                        :message="
                                            updateForms[subject.id]?.errors
                                                ?.name
                                        "
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        Description
                                    </label>
                                    <textarea
                                        v-model="
                                            (updateForms[subject.id] ||=
                                                useForm({
                                                    name: subject.name,
                                                    code: subject.code,
                                                    description:
                                                        subject.description ||
                                                        '',
                                                })).description
                                        "
                                        class="w-full px-3 py-2 text-base border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all min-h-[100px]"
                                        placeholder="Subject description (optional)"
                                        @keyup.esc="cancelEdit(subject.id)"
                                    ></textarea>
                                    <InputError
                                        class="text-xs mt-1"
                                        :message="
                                            updateForms[subject.id]?.errors
                                                ?.description
                                        "
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                            <template v-if="editingId !== subject.id">
                                <button
                                    @click="startEdit(subject)"
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
                                    @click="openDeleteModal(subject.id)"
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
                            </template>
                            <template v-else>
                                <button
                                    @click="saveEdit(subject.id, subject)"
                                    :disabled="
                                        updateForms[subject.id]?.processing
                                    "
                                    class="p-2 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                                    title="Save"
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
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                </button>
                                <button
                                    @click="cancelEdit(subject.id)"
                                    class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors duration-150"
                                    title="Cancel"
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
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="props.subjects.links && props.subjects.links.length > 3"
                class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between"
            >
                <div class="flex-1 flex justify-between sm:hidden">
                    <Link
                        v-if="props.subjects.links[0].url"
                        :href="props.subjects.links[0].url"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
                        preserve-scroll
                    >
                        Previous
                    </Link>
                    <Link
                        v-if="
                            props.subjects.links[
                                props.subjects.links.length - 1
                            ].url
                        "
                        :href="
                            props.subjects.links[
                                props.subjects.links.length - 1
                            ].url
                        "
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
                                props.subjects.from || 0
                            }}</span>
                            to
                            <span class="font-medium">{{
                                props.subjects.to || 0
                            }}</span>
                            of
                            <span class="font-medium">{{
                                props.subjects.total || 0
                            }}</span>
                            results
                        </p>
                    </div>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, index) in props.subjects.links"
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

        <!-- Create Subject Modal -->
        <Modal :show="showCreateModal" @close="closeCreateModal">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-white"
                    >
                        Create Subject
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
                            for="create_code"
                            value="Subject Code"
                            class="mb-2"
                        />
                        <TextInput
                            id="create_code"
                            v-model="form.code"
                            type="text"
                            class="block w-full"
                            placeholder="Enter subject code (e.g., CS201)"
                            required
                            autofocus
                        />
                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>
                    <div>
                        <InputLabel
                            for="create_name"
                            value="Subject Name"
                            class="mb-2"
                        />
                        <TextInput
                            id="create_name"
                            v-model="form.name"
                            type="text"
                            class="block w-full"
                            placeholder="Enter subject name"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel
                            for="create_description"
                            value="Description"
                            class="mb-2"
                        />
                        <textarea
                            id="create_description"
                            v-model="form.description"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all min-h-[120px]"
                            placeholder="Enter subject description (optional)"
                            rows="4"
                        ></textarea>
                        <InputError
                            class="mt-2"
                            :message="form.errors.description"
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
                            :disabled="form.processing"
                            class="px-4 py-2"
                        >
                            Create Subject
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :show="showDeleteModal"
            title="Delete Subject"
            message="Are you sure you want to delete this subject? This action cannot be undone and may affect associated lessons, assessments, and assignments."
            confirm-text="Delete"
            cancel-text="Cancel"
            variant="danger"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
