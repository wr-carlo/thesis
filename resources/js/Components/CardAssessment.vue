<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    assessment: {
        type: Object,
        required: true,
    },
});

const hasAttempts = computed(() => props.assessment.attempt_count > 0);

const actionLabel = computed(() =>
    hasAttempts.value ? "Retake Assessment" : "Take Assessment"
);

const formattedLastAttempt = computed(() => {
    if (!props.assessment.last_attempt_at) return null;

    const date = new Date(props.assessment.last_attempt_at);
    return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
});

const cardHeaderImages = [
    "/images/images/card-images/card-picture-1.png",
    "/images/images/card-images/card-picture-2.png",
    "/images/images/card-images/card-picture-3.png",
    "/images/images/card-images/card-picture-4.png",
    "/images/images/card-images/card-picture-5.png",
];

const headerImageStyle = computed(() => {
    const id = Number(props.assessment.id);
    const imageIndex = Number.isFinite(id)
        ? Math.abs(id) % cardHeaderImages.length
        : 0;
    const imageUrl = cardHeaderImages[imageIndex];

    return {
        backgroundImage: `linear-gradient(to right, rgba(255, 255, 255, 0.18), rgba(255, 255, 255, 0.08)), url(${imageUrl})`,
        backgroundSize: "cover",
        backgroundPosition: "center",
    };
});
</script>

<template>
    <article
        class="card overflow-hidden rounded-2xl border border-border-light dark:border-border-dark hover:shadow-lg transition-all duration-200"
    >
        <div
            class="h-28 p-4 flex items-start"
            :style="headerImageStyle"
        >
            <span
                class="inline-flex items-center rounded-full bg-white/80 dark:bg-black/20 px-3 py-1 text-xs font-medium text-text-primary dark:text-text-inverted"
            >
                {{ assessment.item_count }} Questions
            </span>
        </div>

        <div class="p-5">
            <h3
                class="text-xl font-semibold text-text-primary dark:text-text-inverted leading-tight mb-2"
            >
                {{ assessment.title }}
            </h3>

            <p class="text-sm text-text-secondary mb-1">
                {{ assessment.subject.name }} ({{ assessment.subject.code }})
            </p>
            <p class="text-sm text-text-secondary mb-4">
                {{ assessment.lesson.title }}
            </p>

            <div
                class="text-sm text-text-secondary border-t border-border-light dark:border-border-dark pt-3"
            >
                <span v-if="hasAttempts">
                    Attempts: {{ assessment.attempt_count }}
                    <span v-if="formattedLastAttempt">
                        - Last: {{ formattedLastAttempt }}
                    </span>
                </span>
                <span v-else>No attempts yet</span>
            </div>

            <div class="mt-4 flex items-center justify-end gap-2">
                <Link
                    v-if="hasAttempts"
                    :href="route('student.assessments.history', assessment.id)"
                    class="inline-flex items-center justify-center px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                >
                    View History
                </Link>
                <Link
                    :href="route('student.assessments.show', assessment.id)"
                    class="inline-flex items-center justify-center px-3 py-2 bg-accent-primary text-white text-sm font-medium rounded-lg hover:bg-accent-muted transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-accent-primary focus:ring-offset-2"
                >
                    {{ actionLabel }}
                </Link>
            </div>
        </div>
    </article>
</template>
