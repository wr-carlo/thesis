<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref, onMounted, onUnmounted } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import NotificationDropdown from "@/Components/NotificationDropdown.vue";
import Toast from "@/Components/Toast.vue";
import { getBreadcrumbItems } from "@/Stores/useBreadcrumbs";
import { toggleTheme, useTheme } from "@/Stores/useTheme";

const page = usePage();
const flash = computed(() => page.props.flash || {});
const breadcrumbItems = computed(() =>
    getBreadcrumbItems(route().current(), page.props)
);
const theme = useTheme();
const sidebarOpen = ref(false);
const profileOpen = ref(false);
const profileDropdownRef = ref(null);

const navItems = [
    { name: "Dashboard", route: "student.dashboard" },
    { name: "Join Subjects", route: "student.subjects.index" },
    { name: "Assessments", route: "student.assessments.index" },
];

const isActive = (routeName) => {
    return route().current(routeName);
};

const userName = computed(() => page.props.auth?.user?.name || "Student");
const userInitials = computed(() => {
    const name = userName.value;
    const parts = name.split(" ").filter(Boolean);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
});

// Close profile dropdown when clicking outside
const handleClickOutside = (e) => {
    if (profileDropdownRef.value && !profileDropdownRef.value.contains(e.target)) {
        profileOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<template>
    <div class="min-h-screen bg-surface dark:bg-surface-dark transition-colors">
        <Toast />

        <!-- Mobile Sidebar Toggle -->
        <div
            class="lg:hidden fixed top-0 left-0 right-0 z-40 bg-white dark:bg-surface-dark border-b border-border-light dark:border-border-dark p-4 flex items-center justify-between"
        >
            <div class="flex items-center gap-3">
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
                <ApplicationLogo class="h-8 w-8" />
            </div>

            <!-- Mobile right section -->
            <div class="flex items-center gap-2">
                <NotificationDropdown role="student" />
                <button
                    @click="toggleTheme"
                    class="p-2 rounded-lg hover:bg-surface-muted dark:hover:bg-surface-dark-muted"
                >
                    <svg
                        v-if="theme.theme === 'light'"
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
                        :href="route('student.dashboard')"
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
                                Student Portal
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
                </nav>
            </div>
        </aside>

        <!-- Mobile Overlay -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 bg-black/50 z-40 lg:hidden"
        ></div>

        <!-- Main Content Area -->
        <div class="lg:ml-64 min-h-screen pt-16 lg:pt-0">
            <!-- Top Header Bar (desktop) -->
            <header
                class="hidden lg:flex sticky top-0 z-30 bg-white dark:bg-surface-dark border-b border-border-light dark:border-border-dark h-16 items-center justify-end px-6 gap-3"
            >
                <!-- Theme Toggle -->
                <button
                    type="button"
                    class="theme-toggle relative inline-flex h-8 w-16 items-center rounded-full border border-border-light dark:border-border-dark transition-colors duration-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-primary/40"
                    :class="theme.theme === 'dark' ? 'bg-indigo-950 border-indigo-800' : 'bg-amber-100 border-amber-200'"
                    :aria-label="theme.theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                    @click="toggleTheme()"
                >
                    <span
                        class="absolute left-2 text-[11px] transition-all duration-500"
                        :class="theme.theme === 'dark' ? 'opacity-100 scale-110' : 'opacity-30 scale-75'"
                        aria-hidden="true"
                    >🌙</span>
                    <span
                        class="absolute right-2 text-[11px] transition-all duration-500"
                        :class="theme.theme === 'dark' ? 'opacity-30 scale-75' : 'opacity-100 scale-110'"
                        aria-hidden="true"
                    >☀️</span>
                    <span
                        class="theme-knob inline-block h-6 w-6 rounded-full shadow-md transition-all duration-500 ease-[cubic-bezier(0.68,-0.55,0.265,1.55)]"
                        :class="[
                            theme.theme === 'dark'
                                ? 'translate-x-1 bg-indigo-400 rotate-[360deg]'
                                : 'translate-x-9 bg-amber-400 rotate-0',
                        ]"
                    />
                </button>

                <!-- Notifications -->
                <NotificationDropdown role="student" />

                <!-- Profile Dropdown -->
                <div class="relative" ref="profileDropdownRef">
                    <button
                        @click="profileOpen = !profileOpen"
                        class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-surface-muted dark:hover:bg-surface-dark-muted transition-colors"
                    >
                        <div
                            class="w-8 h-8 rounded-full bg-accent-primary text-white flex items-center justify-center text-sm font-bold"
                        >
                            {{ userInitials }}
                        </div>
                        <svg
                            class="w-4 h-4 text-text-secondary transition-transform"
                            :class="{ 'rotate-180': profileOpen }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </button>

                    <!-- Profile Dropdown Menu -->
                    <Transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="profileOpen"
                            class="absolute right-0 mt-2 w-56 bg-white dark:bg-surface-dark rounded-lg shadow-lg border border-border-light dark:border-border-dark z-50 overflow-hidden"
                        >
                            <!-- User Info -->
                            <div class="px-4 py-3 border-b border-border-light dark:border-border-dark">
                                <p class="text-sm font-semibold text-text-primary dark:text-text-inverted">
                                    {{ $page.props.auth.user.name }}
                                </p>
                                <p class="text-xs text-text-secondary capitalize mt-0.5">
                                    {{ $page.props.auth.user.role }}
                                </p>
                            </div>

                            <!-- Menu Items -->
                            <div class="py-1">
                                <Link
                                    :href="route('student.settings')"
                                    @click="profileOpen = false"
                                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-text-primary dark:text-text-inverted hover:bg-surface-muted dark:hover:bg-surface-dark-muted transition-colors"
                                >
                                    <svg class="w-4 h-4 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Settings
                                </Link>

                                <div class="border-t border-border-light dark:border-border-dark my-1"></div>

                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    @click="profileOpen = false"
                                    class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Logout
                                </Link>
                            </div>
                        </div>
                    </Transition>
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <div class="p-6">
                    <Breadcrumb v-if="breadcrumbItems.length" :items="breadcrumbItems" />
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
