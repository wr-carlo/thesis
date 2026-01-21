<script setup>
import { computed } from "vue";
import { formatTimeAgo } from "@/utils/formatTime";

const props = defineProps({
    log: {
        type: Object,
        required: true,
    },
});

// Determine activity type and extract information
const activityInfo = computed(() => {
    const description = props.log.description || "";
    
    // Check for assessment creation
    if (description.includes("Created manual assessment")) {
        const match = description.match(/Created manual assessment for (.+)/);
        return {
            type: "assessment",
            subtype: "manual",
            icon: "document",
            title: "Created Assessment",
            description: match ? match[1] : "Manual assessment",
            badge: {
                label: "Manual",
                color: "blue",
            },
        };
    }
    
    if (description.includes("Created generated assessment")) {
        const match = description.match(/Created generated assessment for (.+)/);
        return {
            type: "assessment",
            subtype: "generated",
            icon: "document",
            title: "Created Assessment",
            description: match ? match[1] : "Generated assessment",
            badge: {
                label: "Generated",
                color: "purple",
            },
        };
    }
    
    // Check for approval
    if (description.includes("Approved join request")) {
        const match = description.match(/Approved join request for (.+) to (.+)/);
        return {
            type: "approve",
            icon: "check",
            title: "Approved Student",
            description: match ? `${match[1]} - ${match[2]}` : description,
            badge: null,
        };
    }
    
    // Check for decline
    if (description.includes("Declined join request")) {
        const match = description.match(/Declined join request for (.+) to (.+)/);
        return {
            type: "decline",
            icon: "x",
            title: "Declined Student",
            description: match ? `${match[1]} - ${match[2]}` : description,
            badge: null,
        };
    }
    
    // Default fallback
    return {
        type: "default",
        icon: "info",
        title: "Activity",
        description: description,
        badge: null,
    };
});

const iconClasses = computed(() => {
    const info = activityInfo.value;
    
    const baseClasses = "w-5 h-5";
    
    switch (info.icon) {
        case "check":
            return `${baseClasses} text-green-600 dark:text-green-400`;
        case "x":
            return `${baseClasses} text-red-600 dark:text-red-400`;
        case "document":
            return `${baseClasses} text-blue-600 dark:text-blue-400`;
        default:
            return `${baseClasses} text-text-secondary`;
    }
});

const badgeClasses = computed(() => {
    if (!activityInfo.value.badge) return "";
    
    const color = activityInfo.value.badge.color;
    
    if (color === "blue") {
        return "bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300";
    }
    if (color === "purple") {
        return "bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300";
    }
    
    return "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300";
});
</script>

<template>
    <div
        class="group flex items-start gap-4 p-4 rounded-lg border border-border-light dark:border-border-dark bg-white dark:bg-surface-dark hover:shadow-md hover:border-accent-primary/20 transition-all duration-200"
    >
        <!-- Icon -->
        <div
            class="flex-shrink-0 w-10 h-10 rounded-lg bg-surface-muted dark:bg-surface-dark-muted flex items-center justify-center group-hover:scale-110 transition-transform"
        >
            <!-- Check Icon (Approve) -->
            <svg
                v-if="activityInfo.icon === 'check'"
                :class="iconClasses"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </svg>

            <!-- X Icon (Decline) -->
            <svg
                v-else-if="activityInfo.icon === 'x'"
                :class="iconClasses"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </svg>

            <!-- Document Icon (Assessment) -->
            <svg
                v-else-if="activityInfo.icon === 'document'"
                :class="iconClasses"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
            </svg>

            <!-- Default Info Icon -->
            <svg
                v-else
                :class="iconClasses"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </svg>
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <h3
                        class="text-sm font-semibold text-text-primary dark:text-text-inverted mb-1"
                    >
                        {{ activityInfo.title }}
                    </h3>
                    <p
                        class="text-sm text-text-secondary line-clamp-2 break-words"
                    >
                        {{ activityInfo.description }}
                    </p>
                </div>

                <!-- Badge -->
                <span
                    v-if="activityInfo.badge"
                    :class="[
                        'flex-shrink-0 px-2 py-1 text-xs font-medium rounded-full',
                        badgeClasses,
                    ]"
                >
                    {{ activityInfo.badge.label }}
                </span>
            </div>

            <!-- Time -->
            <div class="mt-2">
                <span
                    class="text-xs text-text-secondary"
                    :title="log.created_at"
                >
                    {{ formatTimeAgo(log.created_at) }}
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
