<template>
    <InstructorLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg"
                >
                    <div class="p-6">
                        <!-- Header -->
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h2
                                    class="text-2xl font-semibold text-gray-900 dark:text-white"
                                >
                                    Edit Assessment
                                </h2>
                                <p
                                    class="text-sm text-gray-600 dark:text-gray-400 mt-1"
                                >
                                    {{ lesson.title }} -
                                    {{ lesson.subject?.name }}
                                </p>
                            </div>
                            <div class="flex space-x-3">
                                <Link
                                    :href="route('instructor.lessons.index')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Back
                                </Link>
                            </div>
                        </div>

                        <!-- Assessment Status -->
                        <div v-if="assessment" class="mb-6">
                            <div
                                class="flex items-center justify-between bg-gray-50 dark:bg-gray-900 p-4 rounded-lg"
                            >
                                <div>
                                    <span
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                        >Status:</span
                                    >
                                    <span
                                        :class="{
                                            'ml-2 px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full': true,
                                            'bg-yellow-100 text-yellow-800':
                                                assessment.status === 'draft',
                                            'bg-green-100 text-green-800':
                                                assessment.status ===
                                                'published',
                                        }"
                                    >
                                        {{ assessment.status }}
                                    </span>
                                </div>
                                <div class="flex space-x-2">
                                    <button
                                        v-if="assessment.status === 'published'"
                                        @click="unpublishAssessment"
                                        class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Set to Draft
                                    </button>
                                    <button
                                        v-if="assessment.status === 'draft'"
                                        @click="publishAssessment"
                                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Publish
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Questions List -->
                        <div v-if="items.length > 0" class="space-y-4">
                            <div
                                v-for="(item, index) in items"
                                :key="index"
                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900"
                            >
                                <div
                                    class="flex justify-between items-start mb-3"
                                >
                                    <div class="flex-1">
                                        <span
                                            class="text-sm font-medium text-gray-500 dark:text-gray-400"
                                        >
                                            Question {{ index + 1 }} -
                                            {{ formatType(item.type) }}
                                        </span>
                                    </div>
                                    <button
                                        @click="deleteItem(index)"
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
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
                                </div>

                                <!-- Question Text -->
                                <div class="mb-3">
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        Question
                                    </label>
                                    <textarea
                                        v-model="item.question"
                                        rows="2"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    ></textarea>
                                </div>

                                <!-- Choices (Multiple Choice Only) -->
                                <div
                                    v-if="item.type === 'multiple_choice'"
                                    class="mb-3"
                                >
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        Choices
                                    </label>
                                    <div
                                        v-for="(
                                            choice, choiceIndex
                                        ) in item.choices"
                                        :key="choiceIndex"
                                        class="flex mb-2"
                                    >
                                        <input
                                            v-model="item.choices[choiceIndex]"
                                            type="text"
                                            class="flex-1 border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        />
                                    </div>
                                </div>

                                <!-- Correct Answer -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        Correct Answer
                                    </label>
                                    <input
                                        v-if="item.type === 'true_or_false'"
                                        v-model="item.correct_answer"
                                        type="text"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        placeholder="True or False"
                                    />
                                    <textarea
                                        v-else
                                        v-model="item.correct_answer"
                                        rows="1"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="mt-6 flex justify-end">
                            <button
                                @click="saveChanges"
                                :disabled="saving"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                <span v-if="saving">Saving...</span>
                                <span v-else>Save Changes</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </InstructorLayout>
</template>

<script setup>
import { ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import InstructorLayout from "@/Layouts/InstructorLayout.vue";

const props = defineProps({
    lesson: Object,
});

const assessment = ref(props.lesson.assessments?.[0] || null);
const items = ref(assessment.value?.items || []);
const saving = ref(false);

const formatType = (type) => {
    const types = {
        multiple_choice: "Multiple Choice",
        identification: "Identification",
        true_or_false: "True/False",
    };
    return types[type] || type;
};

const deleteItem = (index) => {
    if (confirm("Are you sure you want to delete this question?")) {
        items.value.splice(index, 1);
    }
};

const saveChanges = () => {
    saving.value = true;

    router.put(
        route("instructor.lessons.update", props.lesson.id),
        { items: items.value },
        {
            onSuccess: () => {
                saving.value = false;
            },
            onError: () => {
                saving.value = false;
            },
        }
    );
};

const publishAssessment = () => {
    router.post(
        route("instructor.lessons.publish", props.lesson.id),
        {},
        {
            onSuccess: () => {
                assessment.value.status = "published";
            },
        }
    );
};

const unpublishAssessment = () => {
    router.post(
        route("instructor.lessons.unpublish", props.lesson.id),
        {},
        {
            onSuccess: () => {
                assessment.value.status = "draft";
            },
        }
    );
};
</script>
