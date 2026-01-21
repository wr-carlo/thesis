<template>
    <InstructorLayout>
        <Head title="Review Assessment" />
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
                                    Review Assessment Questions
                                </h2>
                                <p
                                    class="text-sm text-gray-600 dark:text-gray-400 mt-1"
                                >
                                    {{ lesson.title }} -
                                    {{ lesson.subject?.name }}
                                </p>
                                <p
                                    class="text-xs text-yellow-600 dark:text-yellow-400 mt-2 font-medium"
                                >
                                    ⚠️ Please review the questions below before
                                    saving. You can edit them or cancel to start
                                    over.
                                </p>
                            </div>
                            <div class="flex space-x-3">
                                <Link
                                    :href="route('instructor.lessons.create')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Back to Create
                                </Link>
                            </div>
                        </div>

                        <!-- Question Type Counts -->
                        <div
                            class="mb-6 flex flex-wrap gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700"
                        >
                            <div
                                class="flex items-center gap-2 px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 rounded-full"
                            >
                                <span
                                    class="text-sm font-medium text-blue-800 dark:text-blue-300"
                                >
                                    Multiple Choice:
                                </span>
                                <span
                                    class="text-sm font-bold text-blue-900 dark:text-blue-200"
                                >
                                    {{ questionCounts.multiple_choice }}
                                </span>
                            </div>
                            <div
                                class="flex items-center gap-2 px-3 py-1.5 bg-green-100 dark:bg-green-900/30 rounded-full"
                            >
                                <span
                                    class="text-sm font-medium text-green-800 dark:text-green-300"
                                >
                                    Identification:
                                </span>
                                <span
                                    class="text-sm font-bold text-green-900 dark:text-green-200"
                                >
                                    {{ questionCounts.identification }}
                                </span>
                            </div>
                            <div
                                class="flex items-center gap-2 px-3 py-1.5 bg-purple-100 dark:bg-purple-900/30 rounded-full"
                            >
                                <span
                                    class="text-sm font-medium text-purple-800 dark:text-purple-300"
                                >
                                    True/False:
                                </span>
                                <span
                                    class="text-sm font-bold text-purple-900 dark:text-purple-200"
                                >
                                    {{ questionCounts.true_or_false }}
                                </span>
                            </div>
                            <div
                                class="flex items-center gap-2 px-3 py-1.5 bg-indigo-100 dark:bg-indigo-900/30 rounded-full ml-auto"
                            >
                                <span
                                    class="text-sm font-medium text-indigo-800 dark:text-indigo-300"
                                >
                                    Total:
                                </span>
                                <span
                                    class="text-sm font-bold text-indigo-900 dark:text-indigo-200"
                                >
                                    {{ questionCounts.total }}
                                </span>
                            </div>
                        </div>

                        <!-- Section Assignment -->
                        <SectionAssignment
                            :departments="departments || []"
                            :sections="sections || []"
                            v-model:selectedSectionIds="selectedSectionIds"
                            :errors="[]"
                        />

                        <!-- Add Question Button -->
                        <div class="mb-4">
                            <button
                                v-if="!showAddForm"
                                @click="toggleAddForm"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                <svg
                                    class="w-4 h-4 mr-2"
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
                                Add Question
                            </button>
                        </div>

                        <!-- Add Question Form -->
                        <div
                            v-if="showAddForm"
                            class="mb-6 border border-indigo-300 dark:border-indigo-700 rounded-lg p-4 bg-indigo-50 dark:bg-indigo-900/20 transition-all duration-300"
                        >
                            <div class="flex justify-between items-center mb-4">
                                <h3
                                    class="text-lg font-semibold text-gray-900 dark:text-white"
                                >
                                    Add New Question
                                </h3>
                                <button
                                    @click="cancelAddQuestion"
                                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
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
                            </div>

                            <div class="space-y-4">
                                <!-- Question Type -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        Question Type
                                    </label>
                                    <select
                                        v-model="newQuestion.type"
                                        @change="handleTypeChange"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    >
                                        <option value="multiple_choice">
                                            Multiple Choice
                                        </option>
                                        <option value="identification">
                                            Identification
                                        </option>
                                        <option value="true_or_false">
                                            True/False
                                        </option>
                                    </select>
                                </div>

                                <!-- Question Text -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        Question
                                    </label>
                                    <textarea
                                        v-model="newQuestion.question"
                                        @blur="validateQuestion"
                                        @input="errors.question = ''"
                                        rows="2"
                                        :class="[
                                            'mt-1 block w-full dark:bg-gray-800 dark:text-gray-300 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm',
                                            errors.question
                                                ? 'border-red-500 dark:border-red-500 focus:border-red-500 dark:focus:border-red-500'
                                                : 'border-gray-300 dark:border-gray-700 focus:border-indigo-500 dark:focus:border-indigo-600',
                                        ]"
                                        placeholder="Enter your question here..."
                                    ></textarea>
                                    <InputError
                                        class="mt-1"
                                        :message="errors.question"
                                    />
                                </div>

                                <!-- Choices (Multiple Choice Only) -->
                                <div
                                    v-if="
                                        newQuestion.type === 'multiple_choice'
                                    "
                                >
                                    <div
                                        class="flex justify-between items-center mb-2"
                                    >
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >
                                            Choices
                                        </label>
                                        <button
                                            v-if="
                                                newQuestion.choices.length < 8
                                            "
                                            @click="addChoice"
                                            type="button"
                                            class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            + Add Choice
                                        </button>
                                    </div>
                                    <div
                                        v-for="(
                                            choice, choiceIndex
                                        ) in newQuestion.choices"
                                        :key="choiceIndex"
                                        class="flex items-center gap-2 mb-2"
                                    >
                                        <input
                                            v-model="
                                                newQuestion.choices[choiceIndex]
                                            "
                                            @input="
                                                validateChoices();
                                                errors.choices = '';
                                            "
                                            type="text"
                                            :class="[
                                                'flex-1 dark:bg-gray-800 dark:text-gray-300 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm',
                                                errors.choices
                                                    ? 'border-red-500 dark:border-red-500 focus:border-red-500 dark:focus:border-red-500'
                                                    : 'border-gray-300 dark:border-gray-700 focus:border-indigo-500 dark:focus:border-indigo-600',
                                            ]"
                                            :placeholder="`Choice ${
                                                choiceIndex + 1
                                            }`"
                                        />
                                        <button
                                            v-if="
                                                newQuestion.choices.filter(
                                                    (c) => c.trim() !== ''
                                                ).length > 4
                                            "
                                            @click="removeChoice(choiceIndex)"
                                            type="button"
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
                                                    d="M6 18L18 6M6 6l12 12"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                    <InputError
                                        class="mt-1"
                                        :message="errors.choices"
                                    />
                                </div>

                                <!-- Correct Answer -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        Correct Answer
                                    </label>
                                    <!-- Dropdown for Multiple Choice -->
                                    <select
                                        v-if="
                                            newQuestion.type ===
                                            'multiple_choice'
                                        "
                                        v-model="newQuestion.correct_answer"
                                        @change="validateCorrectAnswer"
                                        @focus="errors.correct_answer = ''"
                                        :class="[
                                            'mt-1 block w-full dark:bg-gray-800 dark:text-gray-300 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm',
                                            errors.correct_answer
                                                ? 'border-red-500 dark:border-red-500 focus:border-red-500 dark:focus:border-red-500'
                                                : 'border-gray-300 dark:border-gray-700 focus:border-indigo-500 dark:focus:border-indigo-600',
                                        ]"
                                    >
                                        <option value="">
                                            Select correct answer
                                        </option>
                                        <option
                                            v-for="(
                                                choice, choiceIdx
                                            ) in newQuestion.choices.filter(
                                                (c) => c.trim() !== ''
                                            )"
                                            :key="choiceIdx"
                                            :value="choice"
                                        >
                                            {{ choice }}
                                        </option>
                                    </select>
                                    <!-- Dropdown for True/False -->
                                    <select
                                        v-else-if="
                                            newQuestion.type === 'true_or_false'
                                        "
                                        v-model="newQuestion.correct_answer"
                                        @change="validateCorrectAnswer"
                                        @focus="errors.correct_answer = ''"
                                        :class="[
                                            'mt-1 block w-full dark:bg-gray-800 dark:text-gray-300 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm',
                                            errors.correct_answer
                                                ? 'border-red-500 dark:border-red-500 focus:border-red-500 dark:focus:border-red-500'
                                                : 'border-gray-300 dark:border-gray-700 focus:border-indigo-500 dark:focus:border-indigo-600',
                                        ]"
                                    >
                                        <option value="">Select answer</option>
                                        <option value="True">True</option>
                                        <option value="False">False</option>
                                    </select>
                                    <!-- Textarea for Identification -->
                                    <textarea
                                        v-else
                                        v-model="newQuestion.correct_answer"
                                        @blur="validateCorrectAnswer"
                                        @input="errors.correct_answer = ''"
                                        rows="1"
                                        :class="[
                                            'mt-1 block w-full dark:bg-gray-800 dark:text-gray-300 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm',
                                            errors.correct_answer
                                                ? 'border-red-500 dark:border-red-500 focus:border-red-500 dark:focus:border-red-500'
                                                : 'border-gray-300 dark:border-gray-700 focus:border-indigo-500 dark:focus:border-indigo-600',
                                        ]"
                                        placeholder="Enter the correct answer..."
                                    ></textarea>
                                    <InputError
                                        class="mt-1"
                                        :message="errors.correct_answer"
                                    />
                                </div>

                                <!-- Form Action Buttons -->
                                <div
                                    class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700"
                                >
                                    <button
                                        @click="cancelAddQuestion"
                                        type="button"
                                        class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        @click="addQuestion"
                                        type="button"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Add Question
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
                                    <!-- Dropdown for Multiple Choice -->
                                    <select
                                        v-if="item.type === 'multiple_choice'"
                                        v-model="item.correct_answer"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    >
                                        <option value="">
                                            Select correct answer
                                        </option>
                                        <option
                                            v-for="(
                                                choice, choiceIdx
                                            ) in item.choices"
                                            :key="choiceIdx"
                                            :value="choice"
                                        >
                                            {{ choice }}
                                        </option>
                                    </select>
                                    <!-- Dropdown for True/False -->
                                    <select
                                        v-else-if="
                                            item.type === 'true_or_false'
                                        "
                                        v-model="item.correct_answer"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    >
                                        <option value="">Select answer</option>
                                        <option value="True">True</option>
                                        <option value="False">False</option>
                                    </select>
                                    <!-- Textarea for Identification -->
                                    <textarea
                                        v-else
                                        v-model="item.correct_answer"
                                        rows="1"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="!showAddForm" class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400 mb-4">
                                No questions yet. Click "Add Question" to create
                                one.
                            </p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-6 flex justify-end space-x-3">
                            <button
                                @click="cancelReview"
                                :disabled="saving || cancelling"
                                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                            >
                                <span v-if="cancelling">Cancelling...</span>
                                <span v-else>Cancel</span>
                            </button>
                            <button
                                @click="saveAssessment"
                                :disabled="
                                    saving || cancelling || items.length === 0
                                "
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                            >
                                <span v-if="saving">Saving...</span>
                                <span v-else>Save Assessment</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </InstructorLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { Link, router } from "@inertiajs/vue3";
import InstructorLayout from "@/Layouts/InstructorLayout.vue";
import SectionAssignment from "@/Components/SectionAssignment.vue";
import InputError from "@/Components/InputError.vue";
import { Head } from "@inertiajs/vue3";

const props = defineProps({
    lesson: Object,
    items: Array,
    departments: Array,
    sections: Array,
    selectedSectionIds: Array,
});

// Ensure items have proper structure, especially choices as arrays
const items = ref(
    (props.items || []).map((item) => {
        if (item.type === "multiple_choice") {
            // Ensure choices is always an array
            if (!item.choices || !Array.isArray(item.choices)) {
                // If choices is a string, try to parse it
                if (typeof item.choices === "string") {
                    try {
                        item.choices = JSON.parse(item.choices);
                    } catch (e) {
                        item.choices = [];
                    }
                } else {
                    item.choices = [];
                }
            }
        }
        return item;
    })
);

// Question type counts
const questionCounts = computed(() => {
    return {
        multiple_choice: items.value.filter(
            (item) => item.type === "multiple_choice"
        ).length,
        identification: items.value.filter(
            (item) => item.type === "identification"
        ).length,
        true_or_false: items.value.filter(
            (item) => item.type === "true_or_false"
        ).length,
        total: items.value.length,
    };
});
const saving = ref(false);
const cancelling = ref(false);
const selectedSectionIds = ref([...(props.selectedSectionIds || [])]);

// Add Question Form State
const showAddForm = ref(false);
const newQuestion = ref({
    type: "multiple_choice",
    question: "",
    choices: ["", "", "", ""],
    correct_answer: "",
});

// Validation errors
const errors = ref({
    question: "",
    choices: "",
    correct_answer: "",
});

// Validation functions
const validateQuestion = () => {
    if (!newQuestion.value.question.trim()) {
        errors.value.question = "Question is required";
        return false;
    }
    errors.value.question = "";
    return true;
};

const validateChoices = () => {
    if (newQuestion.value.type === "multiple_choice") {
        const filledChoices = newQuestion.value.choices.filter(
            (c) => c.trim() !== ""
        );
        if (filledChoices.length < 4) {
            errors.value.choices =
                "Multiple choice questions must have at least 4 choices";
            return false;
        }
    }
    errors.value.choices = "";
    return true;
};

const validateCorrectAnswer = () => {
    if (newQuestion.value.type === "multiple_choice") {
        if (!newQuestion.value.correct_answer) {
            errors.value.correct_answer = "Please select the correct answer";
            return false;
        }
        const filledChoices = newQuestion.value.choices.filter(
            (c) => c.trim() !== ""
        );
        if (!filledChoices.includes(newQuestion.value.correct_answer)) {
            errors.value.correct_answer =
                "Correct answer must match one of the choices";
            return false;
        }
    } else if (newQuestion.value.type === "true_or_false") {
        if (!newQuestion.value.correct_answer) {
            errors.value.correct_answer = "Please select True or False";
            return false;
        }
    } else {
        // Identification
        if (!newQuestion.value.correct_answer.trim()) {
            errors.value.correct_answer = "Please enter the correct answer";
            return false;
        }
    }
    errors.value.correct_answer = "";
    return true;
};

const clearErrors = () => {
    errors.value = {
        question: "",
        choices: "",
        correct_answer: "",
    };
};

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

const saveAssessment = () => {
    if (items.value.length === 0) {
        alert("Please add at least one question before saving.");
        return;
    }

    saving.value = true;

    router.post(
        route("instructor.lessons.review.save"),
        {
            items: items.value,
            section_ids: selectedSectionIds.value,
        },
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

const cancelReview = () => {
    if (
        !confirm(
            "Are you sure you want to cancel? All uploaded data will be discarded."
        )
    ) {
        return;
    }

    cancelling.value = true;

    router.post(
        route("instructor.lessons.review.cancel"),
        {},
        {
            onSuccess: () => {
                cancelling.value = false;
            },
            onError: () => {
                cancelling.value = false;
            },
        }
    );
};

// Add Question Methods
const toggleAddForm = () => {
    showAddForm.value = !showAddForm.value;
    if (!showAddForm.value) {
        resetForm();
    }
};

const resetForm = () => {
    newQuestion.value = {
        type: "multiple_choice",
        question: "",
        choices: ["", "", "", ""],
        correct_answer: "",
    };
    clearErrors();
};

const handleTypeChange = () => {
    if (newQuestion.value.type === "multiple_choice") {
        if (newQuestion.value.choices.length === 0) {
            newQuestion.value.choices = ["", "", "", ""];
        }
    } else {
        newQuestion.value.choices = [];
    }
    newQuestion.value.correct_answer = "";
    clearErrors();
};

const addChoice = () => {
    if (newQuestion.value.choices.length < 8) {
        newQuestion.value.choices.push("");
    }
};

const removeChoice = (index) => {
    const filledChoices = newQuestion.value.choices.filter(
        (c) => c.trim() !== ""
    );
    // Allow removal only if there will be at least 4 filled choices remaining
    if (filledChoices.length > 4) {
        // Store the choice being removed before removing it
        const removedChoice = newQuestion.value.choices[index];
        newQuestion.value.choices.splice(index, 1);
        // Reset correct answer if it was the removed choice
        if (newQuestion.value.correct_answer === removedChoice) {
            newQuestion.value.correct_answer = "";
        }
        // Clear choices error when valid
        if (validateChoices()) {
            errors.value.choices = "";
        }
    }
};

const addQuestion = () => {
    // Run all validations
    const isQuestionValid = validateQuestion();
    const isChoicesValid = validateChoices();
    const isCorrectAnswerValid = validateCorrectAnswer();

    // If any validation fails, stop here (errors are already set)
    if (!isQuestionValid || !isChoicesValid || !isCorrectAnswerValid) {
        return;
    }

    // All validations passed, add the question
    if (newQuestion.value.type === "multiple_choice") {
        const filledChoices = newQuestion.value.choices.filter(
            (c) => c.trim() !== ""
        );

        items.value.push({
            type: "multiple_choice",
            question: newQuestion.value.question.trim(),
            choices: filledChoices,
            correct_answer: newQuestion.value.correct_answer,
        });
    } else if (newQuestion.value.type === "true_or_false") {
        items.value.push({
            type: "true_or_false",
            question: newQuestion.value.question.trim(),
            choices: null,
            correct_answer: newQuestion.value.correct_answer,
        });
    } else {
        // Identification
        items.value.push({
            type: "identification",
            question: newQuestion.value.question.trim(),
            choices: null,
            correct_answer: newQuestion.value.correct_answer.trim(),
        });
    }

    // Reset form and close
    resetForm();
    clearErrors();
    showAddForm.value = false;
};

const cancelAddQuestion = () => {
    resetForm();
    showAddForm.value = false;
};
</script>
