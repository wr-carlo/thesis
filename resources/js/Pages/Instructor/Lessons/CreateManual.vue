<template>
    <InstructorLayout>
        <Head title="Create Manual Assessment" />
        <div class="max-w-7xl mx-auto py-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1
                            class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight"
                        >
                            Create Manual Assessment
                        </h1>
                        <p
                            class="mt-1.5 text-sm text-gray-500 dark:text-gray-400"
                        >
                            Manually create assessment questions
                        </p>
                    </div>
                    <Link
                        :href="route('instructor.lessons.index')"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
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
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                        Back to Lessons
                    </Link>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Basic Information Card -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6"
                >
                    <h2
                        class="text-lg font-semibold text-gray-900 dark:text-white mb-4"
                    >
                        Basic Information
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Subject Selection -->
                        <div>
                            <InputLabel for="subject_id" value="Subject" />
                            <select
                                id="subject_id"
                                v-model="form.subject_id"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-lg shadow-sm"
                                required
                            >
                                <option value="">Select a subject</option>
                                <option
                                    v-for="subject in subjects"
                                    :key="subject.id"
                                    :value="subject.id"
                                >
                                    {{ subject.name }} ({{ subject.code }})
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.subject_id"
                            />
                        </div>

                        <!-- Lesson Title -->
                        <div>
                            <InputLabel for="title" value="Lesson Title" />
                            <TextInput
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                placeholder="Enter lesson title"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.title"
                            />
                        </div>
                    </div>
                </div>

                <!-- Section Assignment -->
                <SectionAssignment
                    :departments="departments || []"
                    :sections="sections || []"
                    v-model:selectedSectionIds="form.section_ids"
                    :errors="[]"
                />

                <!-- Assessment Status -->
                <div class="mb-6">
                    <div
                        class="flex items-center justify-between bg-gray-50 dark:bg-gray-900 p-4 rounded-lg"
                    >
                        <div>
                            <span
                                class="text-sm text-gray-600 dark:text-gray-400"
                            >
                                Status:
                            </span>
                            <span
                                :class="{
                                    'ml-2 px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full': true,
                                    'bg-yellow-100 text-yellow-800':
                                        form.status === 'draft',
                                    'bg-green-100 text-green-800':
                                        form.status === 'published',
                                }"
                            >
                                {{ form.status }}
                            </span>
                        </div>
                        <div class="flex space-x-2">
                            <button
                                type="button"
                                v-if="form.status === 'published'"
                                @click="form.status = 'draft'"
                                class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Set to Draft
                            </button>
                            <button
                                type="button"
                                v-if="form.status === 'draft'"
                                @click="form.status = 'published'"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Publish
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Questions Builder Card -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6"
                >
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2
                                class="text-lg font-semibold text-gray-900 dark:text-white"
                            >
                                Questions
                            </h2>
                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                            >
                                Add questions to your assessment
                            </p>
                        </div>
                        <div
                            v-if="questions.length > 0"
                            class="flex items-center gap-3"
                        >
                            <span
                                class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800"
                            >
                                {{ questions.length }}
                                {{
                                    questions.length === 1
                                        ? "question"
                                        : "questions"
                                }}
                            </span>
                            <button
                                type="button"
                                @click="showAddQuestionForm = true"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
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
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="questions.length === 0 && !showAddQuestionForm"
                        class="text-center py-12"
                    >
                        <div
                            class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center"
                        >
                            <svg
                                class="w-8 h-8 text-gray-400 dark:text-gray-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <h3
                            class="text-lg font-medium text-gray-900 dark:text-white mb-2"
                        >
                            No questions yet
                        </h3>
                        <p
                            class="text-sm text-gray-500 dark:text-gray-400 mb-6"
                        >
                            Get started by adding your first question
                        </p>
                        <button
                            type="button"
                            @click="showAddQuestionForm = true"
                            class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
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
                            Add First Question
                        </button>
                    </div>

                    <!-- Add Question Form -->
                    <div
                        v-if="showAddQuestionForm"
                        class="mb-6 p-5 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <h3
                                class="text-base font-semibold text-gray-900 dark:text-white"
                            >
                                Add New Question
                            </h3>
                            <button
                                type="button"
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
                                <InputLabel
                                    for="new_question_type"
                                    value="Question Type"
                                />
                                <select
                                    id="new_question_type"
                                    v-model="newQuestion.type"
                                    @change="handleTypeChange"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-lg shadow-sm"
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
                                <InputLabel
                                    for="new_question_text"
                                    value="Question"
                                />
                                <textarea
                                    id="new_question_text"
                                    v-model="newQuestion.question"
                                    @blur="validateQuestion"
                                    @input="errors.question = ''"
                                    rows="3"
                                    :class="[
                                        'mt-1 block w-full dark:bg-gray-900 dark:text-gray-300 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-lg shadow-sm',
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
                            <div v-if="newQuestion.type === 'multiple_choice'">
                                <div
                                    class="flex items-center justify-between mb-2"
                                >
                                    <InputLabel value="Choices" />
                                    <button
                                        type="button"
                                        v-if="newQuestion.choices.length < 8"
                                        @click="addChoice"
                                        class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                    >
                                        + Add Choice
                                    </button>
                                </div>
                                <div
                                    v-for="(
                                        choice, index
                                    ) in newQuestion.choices"
                                    :key="index"
                                    class="flex items-center gap-2 mb-2"
                                >
                                    <input
                                        v-model="newQuestion.choices[index]"
                                        @input="
                                            validateChoices();
                                            errors.choices = '';
                                        "
                                        type="text"
                                        :class="[
                                            'flex-1 dark:bg-gray-900 dark:text-gray-300 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-lg shadow-sm',
                                            errors.choices
                                                ? 'border-red-500 dark:border-red-500 focus:border-red-500 dark:focus:border-red-500'
                                                : 'border-gray-300 dark:border-gray-700 focus:border-indigo-500 dark:focus:border-indigo-600',
                                        ]"
                                        :placeholder="`Choice ${index + 1}`"
                                    />
                                    <button
                                        type="button"
                                        v-if="newQuestion.choices.length > 4"
                                        @click="removeChoice(index)"
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
                                <InputLabel
                                    for="new_question_answer"
                                    value="Correct Answer"
                                />
                                <!-- Dropdown for Multiple Choice -->
                                <select
                                    v-if="
                                        newQuestion.type === 'multiple_choice'
                                    "
                                    id="new_question_answer"
                                    v-model="newQuestion.correct_answer"
                                    @change="validateCorrectAnswer"
                                    @focus="errors.correct_answer = ''"
                                    :class="[
                                        'mt-1 block w-full dark:bg-gray-900 dark:text-gray-300 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-lg shadow-sm',
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
                                            choice, index
                                        ) in newQuestion.choices.filter(
                                            (c) => c.trim() !== ''
                                        )"
                                        :key="index"
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
                                    id="new_question_answer"
                                    v-model="newQuestion.correct_answer"
                                    @change="validateCorrectAnswer"
                                    @focus="errors.correct_answer = ''"
                                    :class="[
                                        'mt-1 block w-full dark:bg-gray-900 dark:text-gray-300 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-lg shadow-sm',
                                        errors.correct_answer
                                            ? 'border-red-500 dark:border-red-500 focus:border-red-500 dark:focus:border-red-500'
                                            : 'border-gray-300 dark:border-gray-700 focus:border-indigo-500 dark:focus:border-indigo-600',
                                    ]"
                                >
                                    <option value="">Select answer</option>
                                    <option value="True">True</option>
                                    <option value="False">False</option>
                                </select>
                                <!-- Text Input for Identification -->
                                <textarea
                                    v-else
                                    id="new_question_answer"
                                    v-model="newQuestion.correct_answer"
                                    @blur="validateCorrectAnswer"
                                    @input="errors.correct_answer = ''"
                                    rows="1"
                                    :class="[
                                        'mt-1 block w-full dark:bg-gray-900 dark:text-gray-300 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-lg shadow-sm',
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

                            <!-- Form Actions -->
                            <div
                                class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700"
                            >
                                <button
                                    type="button"
                                    @click="cancelAddQuestion"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    @click="addQuestion"
                                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors"
                                >
                                    Add Question
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Questions List -->
                    <div v-if="questions.length > 0" class="space-y-4">
                        <div
                            v-for="(question, index) in questions"
                            :key="index"
                            class="border border-gray-200 dark:border-gray-700 rounded-lg p-5 bg-gray-50 dark:bg-gray-900/50 hover:border-gray-300 dark:hover:border-gray-600 transition-colors"
                        >
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                    >
                                        {{ index + 1 }}
                                    </span>
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium',
                                            question.type === 'multiple_choice'
                                                ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                                                : question.type ===
                                                  'identification'
                                                ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300'
                                                : 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
                                        ]"
                                    >
                                        {{ formatType(question.type) }}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    @click="removeQuestion(index)"
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

                            <div class="space-y-4">
                                <!-- Question Text -->
                                <div>
                                    <p
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        Question
                                    </p>
                                    <p
                                        class="text-base text-gray-900 dark:text-white"
                                    >
                                        {{ question.question }}
                                    </p>
                                </div>

                                <!-- Choices (Multiple Choice Only) -->
                                <div
                                    v-if="
                                        question.type === 'multiple_choice' &&
                                        question.choices
                                    "
                                >
                                    <p
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                                    >
                                        Choices
                                    </p>
                                    <ul class="space-y-2">
                                        <li
                                            v-for="(
                                                choice, choiceIndex
                                            ) in question.choices"
                                            :key="choiceIndex"
                                            :class="[
                                                'px-3 py-2 rounded-lg border',
                                                choice ===
                                                question.correct_answer
                                                    ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-900 dark:text-green-100'
                                                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white',
                                            ]"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    :class="[
                                                        'w-5 h-5 rounded-full flex items-center justify-center text-xs font-medium',
                                                        choice ===
                                                        question.correct_answer
                                                            ? 'bg-green-500 text-white'
                                                            : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
                                                    ]"
                                                >
                                                    {{
                                                        String.fromCharCode(
                                                            65 + choiceIndex
                                                        )
                                                    }}
                                                </span>
                                                <span>{{ choice }}</span>
                                                <span
                                                    v-if="
                                                        choice ===
                                                        question.correct_answer
                                                    "
                                                    class="ml-auto text-xs font-medium text-green-700 dark:text-green-300"
                                                >
                                                    Correct
                                                </span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Correct Answer -->
                                <div>
                                    <p
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        Correct Answer
                                    </p>
                                    <p
                                        class="text-base font-medium text-green-700 dark:text-green-400"
                                    >
                                        {{ question.correct_answer }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div
                    class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700"
                >
                    <Link
                        :href="route('instructor.lessons.index')"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing || questions.length === 0"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="form.processing">Saving...</span>
                        <span v-else>Save Assessment</span>
                    </button>
                </div>
            </form>
        </div>
    </InstructorLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { useForm, Link, router } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import InstructorLayout from "@/Layouts/InstructorLayout.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import SectionAssignment from "@/Components/SectionAssignment.vue";

const props = defineProps({
    subjects: Array,
    departments: Array,
    sections: Array,
});

const form = useForm({
    subject_id: "",
    title: "",
    status: "draft",
    section_ids: [],
    questions: [],
});

const questions = ref([]);
const showAddQuestionForm = ref(false);
const newQuestion = ref({
    type: "multiple_choice",
    question: "",
    choices: ["", "", "", ""],
    correct_answer: "",
});
const errors = ref({
    question: "",
    choices: "",
    correct_answer: "",
});

const formatType = (type) => {
    const types = {
        multiple_choice: "Multiple Choice",
        identification: "Identification",
        true_or_false: "True/False",
    };
    return types[type] || type;
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
    // Allow removal only if there are more than 4 choices
    if (newQuestion.value.choices.length > 4) {
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

const resetForm = () => {
    newQuestion.value = {
        type: "multiple_choice",
        question: "",
        choices: ["", "", "", ""],
        correct_answer: "",
    };
    clearErrors();
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

        questions.value.push({
            type: "multiple_choice",
            question: newQuestion.value.question.trim(),
            choices: filledChoices,
            correct_answer: newQuestion.value.correct_answer,
        });
    } else if (newQuestion.value.type === "true_or_false") {
        questions.value.push({
            type: "true_or_false",
            question: newQuestion.value.question.trim(),
            choices: null,
            correct_answer: newQuestion.value.correct_answer,
        });
    } else {
        // Identification
        questions.value.push({
            type: "identification",
            question: newQuestion.value.question.trim(),
            choices: null,
            correct_answer: newQuestion.value.correct_answer.trim(),
        });
    }

    // Reset form and close
    resetForm();
    clearErrors();
    showAddQuestionForm.value = false;
};

const cancelAddQuestion = () => {
    resetForm();
    showAddQuestionForm.value = false;
};

const removeQuestion = (index) => {
    if (confirm("Are you sure you want to delete this question?")) {
        questions.value.splice(index, 1);
        form.questions = questions.value;
    }
};

const submitForm = () => {
    if (questions.value.length === 0) {
        alert("Please add at least one question before saving.");
        return;
    }

    form.questions = questions.value;

    form.post(route("instructor.lessons.storeManual"), {
        onSuccess: () => {
            // Success handled by redirect
        },
        onError: (errors) => {
            // Errors are displayed via form.errors
            console.error("Validation errors:", errors);
        },
    });
};
</script>
