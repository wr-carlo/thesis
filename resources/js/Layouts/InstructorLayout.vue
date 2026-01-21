<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import NotificationDropdown from "@/Components/NotificationDropdown.vue";
import Toast from "@/Components/Toast.vue";
import { toggleTheme, useTheme } from "@/Stores/useTheme";

const page = usePage();
const flash = computed(() => page.props.flash || {});
const theme = useTheme();
const sidebarOpen = ref(false);

const navItems = [
    { name: "Dashboard", route: "instructor.dashboard" },
    { name: "My Subjects", route: "instructor.subjects.index" },
    { name: "Lessons", route: "instructor.lessons.index" },
];

const isActive = (routeName) => {
    return route().current(routeName);
};
</script>

<template>
    <div class="min-h-screen bg-surface dark:bg-surface-dark transition-colors">
        <Toast />

        <!-- Mobile Sidebar Toggle -->
        <div
            class="lg:hidden fixed top-0 left-0 right-0 z-40 bg-white dark:bg-surface-dark border-b border-border-light dark:border-border-dark p-4 flex items-center justify-between"
        >
            <ApplicationLogo class="h-8 w-8" />
            <div class="flex items-center gap-2">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2">
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
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed top-0 left-0 z-50 h-screen w-64 transition-transform',
                'bg-white dark:bg-surface-dark',
                'border-r border-border-light dark:border-border-dark',
                sidebarOpen
                    ? 'translate-x-0'
                    : '-translate-x-full lg:translate-x-0',
            ]"
        >
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div
                    class="p-6 border-b border-border-light dark:border-border-dark"
                >
                    <Link
                        :href="route('instructor.dashboard')"
                        class="flex items-center gap-3"
                    >
                        <ApplicationLogo class="h-10 w-10" />
                        <div>
                            <div
                                class="font-bold text-text-primary dark:text-text-inverted"
                            >
                                CHCC
                            </div>
                            <div class="text-xs text-text-secondary">
                                Instructor Portal
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 p-4 space-y-1">
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route)"
                        :class="[
                            'flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors',
                            isActive(item.route)
                                ? 'bg-accent-primary text-white'
                                : 'text-text-secondary hover:bg-surface-muted dark:hover:bg-surface-dark-muted hover:text-text-primary dark:hover:text-text-inverted',
                        ]"
                    >
                        {{ item.name }}
                    </Link>

                    <!-- Settings Link -->
                    <Link
                        :href="route('instructor.settings')"
                        :class="[
                            'flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors',
                            isActive('instructor.settings')
                                ? 'bg-accent-primary text-white'
                                : 'text-text-secondary hover:bg-surface-muted dark:hover:bg-surface-dark-muted hover:text-text-primary dark:hover:text-text-inverted',
                        ]"
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
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        Settings
                    </Link>
                </nav>

                <!-- User Section -->
                <div
                    class="p-4 border-t border-border-light dark:border-border-dark"
                >
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm">
                            <div
                                class="font-medium text-text-primary dark:text-text-inverted"
                            >
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-xs text-text-secondary capitalize">
                                {{ $page.props.auth.user.role }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <NotificationDropdown role="instructor" />
                            <button
                                @click="toggleTheme"
                                class="p-2 rounded-lg hover:bg-surface-muted dark:hover:bg-surface-dark-muted"
                            >
                                <svg
                                    v-if="theme.current === 'light'"
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="btn-ghost w-full text-sm"
                    >
                        Logout
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Mobile Overlay -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 bg-black/50 z-40 lg:hidden"
        ></div>

        <!-- Main Content -->
        <main class="lg:ml-64 min-h-screen pt-20 lg:pt-0">
            <div class="p-6">
                <slot />
            </div>
        </main>
    </div>
</template>
