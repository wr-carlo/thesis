<script setup>
import StudentLayout from "@/Layouts/StudentLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    user: Object,
});

const showPasswordForm = ref(false);

// Profile Information Form
const profileForm = useForm({
    id_number: props.user.id_number,
});

const updateProfile = () => {
    profileForm.put(route("student.settings.update"), {
        preserveScroll: true,
        onSuccess: () => {
            profileForm.reset();
        },
    });
};

// Password Form
const passwordForm = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const updatePassword = () => {
    passwordForm.put(route("student.settings.password"), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            showPasswordForm.value = false;
        },
    });
};
</script>

<template>
    <StudentLayout>
        <Head title="Settings" />

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary dark:text-text-inverted">
                Settings
            </h1>
            <p class="text-text-secondary">
                Manage your account settings and preferences
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <!-- Profile Information -->
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-text-primary dark:text-text-inverted mb-4">
                    Profile Information
                </h2>
                <form @submit.prevent="updateProfile" class="space-y-4">
                    <div>
                        <label
                            for="name"
                            class="block text-sm font-medium text-text-primary dark:text-text-inverted mb-2"
                        >
                            Name
                        </label>
                        <input
                            id="name"
                            :value="user.name"
                            type="text"
                            class="input w-full bg-gray-100 dark:bg-gray-800 cursor-not-allowed"
                            disabled
                            readonly
                        />
                        <p class="mt-1 text-xs text-text-secondary">
                            Your name cannot be changed. Please contact the administrator.
                        </p>
                    </div>

                    <div>
                        <label
                            for="id_number"
                            class="block text-sm font-medium text-text-primary dark:text-text-inverted mb-2"
                        >
                            ID Number
                        </label>
                        <input
                            id="id_number"
                            v-model="profileForm.id_number"
                            type="number"
                            class="input w-full"
                            required
                        />
                        <div
                            v-if="profileForm.errors.id_number"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ profileForm.errors.id_number }}
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="btn-primary"
                            :disabled="profileForm.processing"
                        >
                            {{ profileForm.processing ? "Saving..." : "Save Changes" }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-text-primary dark:text-text-inverted mb-4">
                    Change Password
                </h2>

                <button
                    v-if="!showPasswordForm"
                    @click="showPasswordForm = true"
                    class="btn-primary"
                >
                    Change Password
                </button>

                <form
                    v-else
                    @submit.prevent="updatePassword"
                    class="space-y-4"
                >
                    <div>
                        <label
                            for="current_password"
                            class="block text-sm font-medium text-text-primary dark:text-text-inverted mb-2"
                        >
                            Current Password
                        </label>
                        <input
                            id="current_password"
                            v-model="passwordForm.current_password"
                            type="password"
                            class="input w-full"
                            required
                        />
                        <div
                            v-if="passwordForm.errors.current_password"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ passwordForm.errors.current_password }}
                        </div>
                    </div>

                    <div>
                        <label
                            for="password"
                            class="block text-sm font-medium text-text-primary dark:text-text-inverted mb-2"
                        >
                            New Password
                        </label>
                        <input
                            id="password"
                            v-model="passwordForm.password"
                            type="password"
                            class="input w-full"
                            required
                        />
                        <div
                            v-if="passwordForm.errors.password"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ passwordForm.errors.password }}
                        </div>
                    </div>

                    <div>
                        <label
                            for="password_confirmation"
                            class="block text-sm font-medium text-text-primary dark:text-text-inverted mb-2"
                        >
                            Confirm New Password
                        </label>
                        <input
                            id="password_confirmation"
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            class="input w-full"
                            required
                        />
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="
                                showPasswordForm = false;
                                passwordForm.reset();
                            "
                            class="btn-secondary"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="btn-primary"
                            :disabled="passwordForm.processing"
                        >
                            {{
                                passwordForm.processing
                                    ? "Updating..."
                                    : "Update Password"
                            }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Academic Information -->
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-text-primary dark:text-text-inverted mb-4">
                    Academic Information
                </h2>
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-text-secondary">Section:</span>
                        <span
                            class="ml-2 text-sm font-medium text-text-primary dark:text-text-inverted"
                        >
                            {{ user.student?.section?.name || "N/A" }}
                        </span>
                    </div>
                    <div>
                        <span class="text-sm text-text-secondary">Department:</span>
                        <span
                            class="ml-2 text-sm font-medium text-text-primary dark:text-text-inverted"
                        >
                            {{ user.student?.section?.department?.name || "N/A" }}
                        </span>
                    </div>
                    <div>
                        <span class="text-sm text-text-secondary">Role:</span>
                        <span
                            class="ml-2 text-sm font-medium text-text-primary dark:text-text-inverted capitalize"
                        >
                            {{ user.role }}
                        </span>
                    </div>
                    <div>
                        <span class="text-sm text-text-secondary">Account Created:</span>
                        <span
                            class="ml-2 text-sm font-medium text-text-primary dark:text-text-inverted"
                        >
                            {{ new Date(user.created_at).toLocaleDateString() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

