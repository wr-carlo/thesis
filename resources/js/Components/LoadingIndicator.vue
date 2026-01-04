<script setup>
import { useLoading } from "@/Stores/useLoading";
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const { isLoading } = useLoading();
const page = usePage();

// Check if current route is login page
const isLoginPage = computed(() => {
    return page.url === "/login" || route().current("login");
});

// Only show loading if not on login page
const shouldShowLoading = computed(() => {
    return isLoading.value && !isLoginPage.value;
});
</script>

<template>
    <Teleport to="body">
        <Transition name="loading">
            <div v-if="shouldShowLoading" class="loading-overlay">
                <!-- Modern Spinner Container -->
                <div class="loading-container">
                    <!-- SVG Spinner with smooth stroke animation -->
                    <div class="spinner-wrapper">
                        <svg class="spinner-svg" viewBox="0 0 50 50">
                            <defs>
                                <linearGradient
                                    id="spinner-gradient"
                                    x1="0%"
                                    y1="0%"
                                    x2="100%"
                                    y2="100%"
                                >
                                    <stop
                                        offset="0%"
                                        style="
                                            stop-color: #3b82f6;
                                            stop-opacity: 1;
                                        "
                                    />
                                    <stop
                                        offset="50%"
                                        style="
                                            stop-color: #8b5cf6;
                                            stop-opacity: 1;
                                        "
                                    />
                                    <stop
                                        offset="100%"
                                        style="
                                            stop-color: #ec4899;
                                            stop-opacity: 1;
                                        "
                                    />
                                </linearGradient>
                            </defs>
                            <circle
                                class="spinner-path"
                                cx="25"
                                cy="25"
                                r="20"
                                fill="none"
                                stroke-width="4"
                            ></circle>
                        </svg>
                    </div>

                    <!-- Loading Text with pulse animation -->
                    <p class="loading-text">
                        Loading<span class="loading-dots">
                            <span>.</span><span>.</span><span>.</span>
                        </span>
                    </p>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.loading-overlay {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.dark .loading-overlay {
    background: rgba(0, 0, 0, 0.5);
}

.loading-container {
    position: absolute;
    top: 50%;
    left: 55%;
    transform: translate(-50%, -50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    padding: 2.5rem;
    border-radius: 1.5rem;
}

.spinner-wrapper {
    width: 64px;
    height: 64px;
    position: relative;
}

.spinner-svg {
    width: 100%;
    height: 100%;
    animation: rotate 2s linear infinite;
    transform-origin: center;
}

.spinner-path {
    stroke: #086fff;
    stroke-linecap: round;
    stroke-dasharray: 1, 150;
    stroke-dashoffset: 0;
    animation: dash 1.5s ease-in-out infinite;
}

.dark .spinner-path {
    stroke: url(#spinner-gradient);
}

.loading-text {
    font-size: 0.875rem;
    font-weight: 500;
    color: #ffffff;
    letter-spacing: 0.025em;
    margin: 0;
}

.dark .loading-text {
    color: #e5e7eb;
}

.loading-dots {
    display: inline-block;
    margin-left: 0.25rem;
}

.loading-dots span {
    display: inline-block;
    animation: dotPulse 1.4s ease-in-out infinite;
    animation-delay: calc(var(--dot-index) * 0.2s);
}

.loading-dots span:nth-child(1) {
    --dot-index: 0;
}

.loading-dots span:nth-child(2) {
    --dot-index: 1;
}

.loading-dots span:nth-child(3) {
    --dot-index: 2;
}

/* Transitions - Instant appearance/disappearance based on data fetching */
.loading-enter-active {
    transition: opacity 0.05s ease;
}

.loading-leave-active {
    transition: opacity 0.05s ease;
}

.loading-enter-from {
    opacity: 0;
}

.loading-leave-to {
    opacity: 0;
}

/* Animations */
@keyframes rotate {
    100% {
        transform: rotate(360deg);
    }
}

@keyframes dash {
    0% {
        stroke-dasharray: 1, 150;
        stroke-dashoffset: 0;
    }
    50% {
        stroke-dasharray: 90, 150;
        stroke-dashoffset: -35;
    }
    100% {
        stroke-dasharray: 90, 150;
        stroke-dashoffset: -124;
    }
}

@keyframes dotPulse {
    0%,
    80%,
    100% {
        opacity: 0.3;
        transform: translateY(0);
    }
    40% {
        opacity: 1;
        transform: translateY(-4px);
    }
}

/* Add gradient definition for dark mode spinner */
.spinner-svg::before {
    content: "";
}

.dark .spinner-svg {
    filter: drop-shadow(0 0 8px rgba(59, 130, 246, 0.5));
}
</style>
