<script setup>
import StudentLayout from "@/Layouts/StudentLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    assessment: Object,
    items: Array,
});

const form = useForm({
    answers: {},
});

const currentQuestionIndex = ref(0);

const totalQuestions = computed(() => props.items?.length || 0);

const currentQuestion = computed(() => {
    return props.items?.[currentQuestionIndex.value] || null;
});

const isFirstQuestion = computed(() => currentQuestionIndex.value === 0);

const isLastQuestion = computed(() => currentQuestionIndex.value === totalQuestions.value - 1);

const answeredQuestions = computed(() => {
    return Object.keys(form.answers).filter(
        (key) => form.answers[key]?.answer !== null && form.answers[key]?.answer !== ""
    ).length;
});

const unansweredQuestions = computed(() => {
    return totalQuestions.value - answeredQuestions.value;
});

// Initialize form answers and ensure choices is always an array
props.items?.forEach((item) => {
    form.answers[item.id] = { answer: "" };

    // Ensure choices is an array for multiple choice questions
    if (item.type === 'multiple_choice' && item.choices) {
        if (typeof item.choices === 'string') {
            try {
                item.choices = JSON.parse(item.choices);
            } catch (e) {
                item.choices = [];
            }
        }
        if (!Array.isArray(item.choices)) {
            item.choices = [];
        }
    }
});

const updateAnswer = (itemId, answer) => {
    form.answers[itemId] = { answer };
};

// Helper to get choices as array
const getChoices = (item) => {
    if (!item.choices) return [];
    if (Array.isArray(item.choices)) return item.choices;
    if (typeof item.choices === 'string') {
        try {
            return JSON.parse(item.choices);
        } catch (e) {
            return [];
        }
    }
    return [];
};

const nextQuestion = () => {
    if (currentQuestionIndex.value < totalQuestions.value - 1) {
        currentQuestionIndex.value++;
    }
};

const previousQuestion = () => {
    if (currentQuestionIndex.value > 0) {
        currentQuestionIndex.value--;
    }
};

const goToQuestion = (index) => {
    if (index >= 0 && index < totalQuestions.value) {
        currentQuestionIndex.value = index;
    }
};

const submitForm = () => {
    if (
        unansweredQuestions.value > 0 &&
        !confirm(
            `You have ${unansweredQuestions.value} unanswered question(s). Do you want to submit anyway?`
        )
    ) {
        return;
    }

    form.post(route("student.assessments.store", props.assessment.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <StudentLayout>

        <Head :title="assessment.title" />

        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <Link :href="route('student.assessments.index')"
                    class="inline-flex items-center justify-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 mb-4">
                    ← Back to Assessments
                </Link>

                <div class="card p-6">
                    <h1 class="text-2xl font-bold text-text-primary dark:text-text-inverted mb-2">
                        {{ assessment.title }}
                    </h1>
                    <div class="text-sm text-text-secondary space-y-1">
                        <p>
                            <span class="font-medium">Subject:</span>
                            {{ assessment.subject.name }}
                            ({{ assessment.subject.code }})
                        </p>
                        <p>
                            <span class="font-medium">Lesson:</span>
                            {{ assessment.lesson.title }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Progress Info -->
            <div class="mb-6 card p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between text-sm mb-3">
                    <span class="text-text-primary dark:text-text-inverted font-medium">
                        Question {{ currentQuestionIndex + 1 }} of {{ totalQuestions }}
                    </span>
                    <span class="text-text-secondary">
                        {{ answeredQuestions }} / {{ totalQuestions }} answered
                    </span>
                </div>
                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-accent-primary h-2 rounded-full transition-all duration-300"
                        :style="{ width: `${((currentQuestionIndex + 1) / totalQuestions) * 100}%` }"></div>
                </div>
            </div>

            <!-- Questions -->
            <form @submit.prevent="submitForm">
                <div v-if="currentQuestion" class="card p-6 mb-6">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex-shrink-0 w-8 h-8 rounded-full bg-accent-primary text-white flex items-center justify-center font-semibold text-sm">
                            {{ currentQuestionIndex + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-text-primary dark:text-text-inverted mb-4">
                                {{ currentQuestion.question }}
                            </h3>

                            <!-- Multiple Choice -->
                            <div v-if="currentQuestion.type === 'multiple_choice'" class="space-y-2">
                                <label v-for="(choice, choiceIndex) in getChoices(currentQuestion)" :key="choiceIndex"
                                    :class="[
                                        'flex items-center p-3 rounded-lg border cursor-pointer transition-colors',
                                        form.answers[currentQuestion.id]?.answer === choice
                                            ? 'border-accent-primary bg-accent-primary/10 dark:bg-accent-primary/20'
                                            : 'border-border-light dark:border-border-dark hover:border-accent-primary/50',
                                    ]">
                                    <input type="radio" :name="`answer_${currentQuestion.id}`" :value="choice"
                                        :checked="form.answers[currentQuestion.id]?.answer === choice"
                                        @change="updateAnswer(currentQuestion.id, choice)"
                                        class="mr-3 text-accent-primary focus:ring-accent-primary" />
                                    <span class="text-text-primary dark:text-text-inverted">
                                        {{ choice }}
                                    </span>
                                </label>
                            </div>

                            <!-- Identification -->
                            <div v-else-if="currentQuestion.type === 'identification'">
                                <input :value="form.answers[currentQuestion.id]?.answer || ''"
                                    @input="updateAnswer(currentQuestion.id, $event.target.value)" type="text"
                                    placeholder="Enter your answer" class="input w-full" />
                            </div>

                            <!-- True/False -->
                            <div v-else-if="currentQuestion.type === 'true_or_false'" class="flex gap-4">
                                <label :class="[
                                    'flex items-center p-3 rounded-lg border cursor-pointer transition-colors flex-1',
                                    form.answers[currentQuestion.id]?.answer === 'True'
                                        ? 'border-accent-primary bg-accent-primary/10 dark:bg-accent-primary/20'
                                        : 'border-border-light dark:border-border-dark hover:border-accent-primary/50',
                                ]">
                                    <input type="radio" :name="`answer_${currentQuestion.id}`" value="True"
                                        :checked="form.answers[currentQuestion.id]?.answer === 'True'"
                                        @change="updateAnswer(currentQuestion.id, 'True')"
                                        class="mr-3 text-accent-primary focus:ring-accent-primary" />
                                    <span class="text-text-primary dark:text-text-inverted font-medium">
                                        True
                                    </span>
                                </label>
                                <label :class="[
                                    'flex items-center p-3 rounded-lg border cursor-pointer transition-colors flex-1',
                                    form.answers[currentQuestion.id]?.answer === 'False'
                                        ? 'border-accent-primary bg-accent-primary/10 dark:bg-accent-primary/20'
                                        : 'border-border-light dark:border-border-dark hover:border-accent-primary/50',
                                ]">
                                    <input type="radio" :name="`answer_${currentQuestion.id}`" value="False"
                                        :checked="form.answers[currentQuestion.id]?.answer === 'False'"
                                        @change="updateAnswer(currentQuestion.id, 'False')"
                                        class="mr-3 text-accent-primary focus:ring-accent-primary" />
                                    <span class="text-text-primary dark:text-text-inverted font-medium">
                                        False
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div
                    class="flex items-center justify-between pt-6 border-t border-border-light dark:border-border-dark">
                    <button type="button" @click="previousQuestion" :disabled="isFirstQuestion" :class="[
                        'px-4 py-2 rounded-lg font-medium transition-colors',
                        isFirstQuestion
                            ? 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                            : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600',
                    ]">
                        ← Previous
                    </button>

                    <div class="flex gap-2">
                        <button v-for="(item, index) in items" :key="item.id" type="button" @click="goToQuestion(index)"
                            :class="[
                                'w-8 h-8 rounded-full text-sm font-medium transition-colors',
                                index === currentQuestionIndex
                                    ? 'bg-accent-primary text-white'
                                    : form.answers[item.id]?.answer
                                        ? 'bg-green-500 text-white hover:bg-green-600'
                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600',
                            ]" :title="`Question ${index + 1}`">
                            {{ index + 1 }}
                        </button>
                    </div>

                    <div class="flex gap-3">
                        <PrimaryButton v-if="isLastQuestion" type="submit"
                            :class="{ 'opacity-50 cursor-not-allowed': form.processing }" :disabled="form.processing">
                            <span v-if="form.processing">Submitting...</span>
                            <span v-else>Submit Assessment</span>
                        </PrimaryButton>
                        <button v-else type="button" @click="nextQuestion"
                            class="px-4 py-2 bg-accent-primary text-white rounded-lg font-medium hover:bg-accent-muted transition-colors">
                            Next →
                        </button>
                    </div>
                </div>

                <InputError :message="form.errors.error" class="mt-4" />
            </form>
        </div>
    </StudentLayout>
</template>
