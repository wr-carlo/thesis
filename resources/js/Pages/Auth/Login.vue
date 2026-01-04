<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted } from "vue";

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    id_number: "",
    password: "",
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};

// Animation icons
const icons = [
    "/images/login/login-icon1.png",
    "/images/login/login-icon2.png",
    "/images/login/login-icon3.png",
    "/images/login/login-icon4.png",
    "/images/login/login-icon5.png",
    "/images/login/login-icon6.png",
    "/images/login/login-icon7.png",
];

const floatingIcons = ref([]);
let animationTimeout = null;

// Grid background animation
const gridSize = 50; // Size of each grid box
const aoeRange = 150; // Area of effect range in pixels
const mousePosition = ref({ x: 0, y: 0 });
const gridContainer = ref(null);
const formContainer = ref(null);

const createIcon = () => {
    const iconIndex = Math.floor(Math.random() * icons.length);
    const icon = {
        id: Date.now() + Math.random(),
        src: icons[iconIndex],
        left: Math.random() * 80 + 10 + "%", // 10% to 90% to avoid edges
        top: Math.random() * 80 + 10 + "%", // 10% to 90% to avoid edges
        size: Math.random() * 80 + 150 + "px", // 60px to 140px
        delay: Math.random() * 0.3,
        duration: Math.random() * 2 + 2.5, // 2.5-4.5 seconds
        rotation: Math.random() * 360 + "deg", // Random rotation
    };
    floatingIcons.value.push(icon);

    // Remove icon after animation
    setTimeout(() => {
        const index = floatingIcons.value.findIndex((i) => i.id === icon.id);
        if (index > -1) {
            floatingIcons.value.splice(index, 1);
        }
    }, (icon.duration + icon.delay) * 1000);
};

const createGrid = () => {
    if (!gridContainer.value) return;

    const container = gridContainer.value;
    const rect = container.getBoundingClientRect();
    const cols = Math.ceil(rect.width / gridSize);
    const rows = Math.ceil(rect.height / gridSize);

    // Check if dark mode is active
    const isDark = document.documentElement.classList.contains("dark");
    const lineColor = isDark
        ? "rgba(139, 92, 246, 0.2)"
        : "rgba(99, 102, 241, 0.15)";
    const boxBorderColor = isDark
        ? "rgba(139, 92, 246, 0.2)"
        : "rgba(99, 102, 241, 0.15)";
    const svgStrokeColor = isDark
        ? "rgba(139, 92, 246, 0.25)"
        : "rgba(99, 102, 241, 0.2)";

    // Clear existing grid
    container.innerHTML = "";

    // Create grid lines and boxes
    for (let row = 0; row <= rows; row++) {
        const line = document.createElement("div");
        line.className = "grid-line-h";
        line.style.cssText = `
            position: absolute;
            top: ${row * gridSize}px;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(to right, transparent 0%, ${lineColor} 20%, ${lineColor} 80%, transparent 100%);
            pointer-events: none;
        `;
        container.appendChild(line);
    }

    for (let col = 0; col <= cols; col++) {
        const line = document.createElement("div");
        line.className = "grid-line-v";
        line.style.cssText = `
            position: absolute;
            left: ${col * gridSize}px;
            top: 0;
            width: 1px;
            height: 100%;
            background: linear-gradient(to bottom, transparent 0%, ${lineColor} 20%, ${lineColor} 80%, transparent 100%);
            pointer-events: none;
        `;
        container.appendChild(line);
    }

    // Create grid boxes with S-shape pattern
    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
            const box = document.createElement("div");
            box.className = "grid-box";
            box.style.cssText = `
                position: absolute;
                left: ${col * gridSize}px;
                top: ${row * gridSize}px;
                width: ${gridSize}px;
                height: ${gridSize}px;
                border: 1px solid ${boxBorderColor};
                opacity: 0.15;
                transform: scale(1);
                transition: all 0.3s ease-out;
                pointer-events: none;
            `;

            // Add S-shape SVG pattern
            box.innerHTML = `
                <svg class="w-full h-full" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M 10 10 Q 25 10 25 25 Q 25 40 40 40" stroke="${svgStrokeColor}" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                    <path d="M 40 10 Q 25 10 25 25 Q 25 40 10 40" stroke="${svgStrokeColor}" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                </svg>
            `;

            container.appendChild(box);
        }
    }
};

const handleMouseMove = (e) => {
    if (!gridContainer.value || !formContainer.value) return;

    const rect = formContainer.value.getBoundingClientRect();
    mousePosition.value = {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top,
    };

    // Update grid boxes based on mouse position
    const gridBoxes = gridContainer.value.querySelectorAll(".grid-box");
    gridBoxes.forEach((box) => {
        const boxLeft = parseFloat(box.style.left);
        const boxTop = parseFloat(box.style.top);
        const boxCenterX = boxLeft + gridSize / 2;
        const boxCenterY = boxTop + gridSize / 2;

        const distance = Math.sqrt(
            Math.pow(mousePosition.value.x - boxCenterX, 2) +
                Math.pow(mousePosition.value.y - boxCenterY, 2)
        );

        if (distance < aoeRange) {
            const scale = 1 + (1 - distance / aoeRange) * 0.5; // Scale from 1 to 1.5
            const opacity = 0.3 + (1 - distance / aoeRange) * 0.4; // Opacity from 0.3 to 0.7
            box.style.transform = `scale(${scale})`;
            box.style.opacity = opacity;
        } else {
            box.style.transform = "scale(1)";
            box.style.opacity = "0.15";
        }
    });
};

onMounted(() => {
    // Create initial icons
    for (let i = 0; i < 10; i++) {
        setTimeout(() => createIcon(), i * 400);
    }

    // Continuously spawn new icons randomly
    const spawnIcon = () => {
        if (floatingIcons.value.length < 15) {
            createIcon();
        }
        // Random interval between 0.5- 1 seconds for faster spawning
        const nextDelay = Math.random() * 500 + 500;
        animationTimeout = setTimeout(spawnIcon, nextDelay);
    };

    // Start spawning after initial icons
    animationTimeout = setTimeout(spawnIcon, 1000);

    // Create grid
    setTimeout(() => {
        createGrid();

        // Recreate grid on window resize
        const handleResize = () => {
            createGrid();
        };
        window.addEventListener("resize", handleResize);

        // Watch for dark mode changes
        const darkModeObserver = new MutationObserver(() => {
            createGrid();
        });
        darkModeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ["class"],
        });
    }, 100);
});

onUnmounted(() => {
    if (animationTimeout) {
        clearTimeout(animationTimeout);
    }

    // Remove resize listener
    window.removeEventListener("resize", createGrid);
});
</script>

<template>
    <Head title="Log in" />

    <div class="min-h-screen flex">
        <!-- Left Side - Background Image -->
        <div
            class="hidden lg:flex lg:w-1/2 bg-cover bg-center bg-no-repeat relative"
            style="background-image: url('/images/front-campus.png')"
        >
            <!-- Overlay for better text readability -->
            <div
                class="absolute inset-0 bg-gradient-to-br from-blue-900/80 to-indigo-900/70"
            ></div>

            <!-- Content on image -->
            <div
                class="relative z-10 flex flex-col justify-center items-center w-full text-white"
            >
                <div>
                    <img
                        src="/images/white-logo.svg"
                        alt="Logo"
                        class="w-24 h-24 mx-auto mb-4"
                    />
                </div>
                <h1 class="text-4xl font-bold mb-2 text-center">
                    Concepcion Holy Cross College Inc.
                </h1>
                <p class="text-lg text-center text-blue-100 max-w-md">
                    Welcome to the Intelligent Assessment Tool
                </p>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div
            ref="formContainer"
            class="w-full lg:w-1/2 flex flex-col justify-center items-center bg-white dark:bg-surface-dark p-8 sm:p-12 relative overflow-hidden"
            @mousemove="handleMouseMove"
        >
            <!-- Interactive Grid Background -->
            <div
                ref="gridContainer"
                class="absolute inset-0 grid-background pointer-events-none"
            ></div>

            <!-- Floating Animated Icons -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden">
                <img
                    v-for="icon in floatingIcons"
                    :key="icon.id"
                    :src="icon.src"
                    :alt="`Icon ${icon.id}`"
                    class="absolute opacity-0 floating-icon"
                    :style="{
                        left: icon.left,
                        top: icon.top,
                        width: icon.size,
                        height: icon.size,
                        '--initial-rotation': icon.rotation,
                        animationDelay: icon.delay + 's',
                        animationDuration: icon.duration + 's',
                    }"
                />
            </div>

            <!-- Mobile Logo -->
            <div class="lg:hidden mb-8 relative z-10">
                <ApplicationLogo class="w-16 h-16 mx-auto" />
                <h2
                    class="text-2xl font-bold text-center mt-4 text-text-primary dark:text-text-inverted"
                >
                    Concepcion Holy Cross College Inc.
                </h2>
            </div>

            <div class="w-full max-w-md relative z-10">
                <div class="mb-8">
                    <h2
                        class="text-3xl font-bold text-text-primary dark:text-text-inverted mb-2"
                    >
                        Welcome Back
                    </h2>
                    <p class="text-text-secondary dark:text-text-inverted/70">
                        Sign in to your account to continue
                    </p>
                </div>

                <div
                    v-if="status"
                    class="mb-6 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 text-sm"
                >
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-3">
                        <div>
                            <InputLabel
                                for="id_number"
                                value="ID Number"
                                class="text-text-primary dark:text-text-inverted"
                            />

                            <TextInput
                                id="id_number"
                                type="text"
                                inputmode="numeric"
                                class="mt-1 block w-full"
                                v-model="form.id_number"
                                required
                                autofocus
                                autocomplete="username"
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.id_number"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="password"
                                value="Password"
                                class="text-text-primary dark:text-text-inverted"
                            />

                            <TextInput
                                id="password"
                                type="password"
                                class="mt-1 block w-full"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.password"
                            />
                        </div>
                    </div>

                    <PrimaryButton
                        class="w-full"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        <span v-if="!form.processing">Log in</span>
                        <span v-else>Logging in...</span>
                    </PrimaryButton>
                </form>

                <div class="mt-8 text-center">
                    <p
                        class="text-sm text-text-secondary dark:text-text-inverted/70"
                    >
                        © {{ new Date().getFullYear() }} Concepcion Holy Cross
                        College Inc. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes floatFade {
    0% {
        opacity: 0;
        transform: translateY(20px) scale(0.8)
            rotate(var(--initial-rotation, 0deg));
    }
    15% {
        opacity: 0.5;
        transform: translateY(0) scale(1)
            rotate(calc(var(--initial-rotation, 0deg) + 5deg));
    }
    50% {
        opacity: 0.6;
        transform: translateY(-5px) scale(1.05)
            rotate(calc(var(--initial-rotation, 0deg) - 5deg));
    }
    85% {
        opacity: 0.4;
        transform: translateY(-10px) scale(1)
            rotate(calc(var(--initial-rotation, 0deg) + 3deg));
    }
    100% {
        opacity: 0;
        transform: translateY(-30px) scale(0.8)
            rotate(var(--initial-rotation, 0deg));
    }
}

.floating-icon {
    animation: floatFade ease-in-out forwards;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
    object-fit: contain;
    will-change: transform, opacity;
}

.dark .floating-icon {
    filter: drop-shadow(0 4px 8px rgba(255, 255, 255, 0.1));
}
</style>
