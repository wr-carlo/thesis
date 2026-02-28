<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref, onMounted, onBeforeUnmount, watch } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
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
const usersMenuOpen = ref(false);
const profileMenuOpen = ref(false);
const profileMenuRef = ref(null);

const navItems = [
    { name: "Dashboard", route: "admin.dashboard" },
    { name: "Departments", route: "admin.departments.index" },
    { name: "Sections", route: "admin.sections.index" },
    { name: "Subjects", route: "admin.subjects.index" },
    { name: "Assignments", route: "admin.assignments.index" },
];

const userSubItems = [
    { name: "Students", route: "admin.students.index" },
    { name: "Instructors", route: "admin.instructors.index" },
];

const profileName = computed(() => page.props.auth?.user?.name || "Admin");
const profileInitials = computed(() => {
    const name = profileName.value;
    const parts = name.split(" ").filter(Boolean);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
});

// Check if current route is a users sub-route
const isUsersRoute = computed(() => {
    const currentRoute = route().current();
    return (
        currentRoute?.startsWith("admin.students.") ||
        currentRoute?.startsWith("admin.instructors.")
    );
});

// Auto-expand users menu if on users route
onMounted(() => {
    if (isUsersRoute.value) {
        usersMenuOpen.value = true;
    }
});

// Watch for route changes
watch(
    () => route().current(),
    () => {
        if (isUsersRoute.value) {
            usersMenuOpen.value = true;
        }
        profileMenuOpen.value = false;
    }
);

const handleClickOutside = (event) => {
    if (
        profileMenuOpen.value &&
        profileMenuRef.value &&
        !profileMenuRef.value.contains(event.target)
    ) {
        profileMenuOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<template>
    <div
        class="min-h-screen bg-surface-muted text-text-primary dark:bg-surface-dark dark:text-text-inverted"
    >
        <div class="flex min-h-screen">
            <!-- Sidebar -->
            <aside
                :class="[
                    'w-64 bg-white dark:bg-surface-dark-muted border-r border-border-light dark:border-border-dark fixed inset-y-0 left-0 transform transition-transform duration-200 ease-in-out z-30',
                    sidebarOpen
                        ? 'translate-x-0'
                        : '-translate-x-full sm:translate-x-0',
                ]"
            >
                <div
                    class="flex items-center h-16 px-4 border-b border-border-light dark:border-border-dark gap-3"
                >
                    <img
                        v-if="theme.theme === 'dark'"
                        src="/images/white-logo.svg"
                        alt="Logo"
                        class="h-10 w-10"
                    />
                    <img
                        v-else
                        src="/images/logo.png"
                        alt="Logo"
                        class="h-10 w-10"
                    />

                    <span class="text-lg text-text-primary dark:text-text-inverted font-semibold">CHCC Inc.</span>
                </div>
                <nav class="p-4 space-y-1">
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route)"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-text-secondary hover:bg-accent-primary/10 hover:text-accent-primary dark:hover:bg-accent-primary/20 dark:hover:text-accent-primary"
                        :class="{
                            '!bg-accent-primary/10 !text-accent-primary dark:!bg-accent-primary/20 dark:!text-accent-primary':
                                route().current(`${item.route}*`),
                        }"
                    >
                        {{ item.name }}
                    </Link>

                    <!-- Users Accordion -->
                    <div>
                        <button
                            @click="usersMenuOpen = !usersMenuOpen"
                            class="w-full flex items-center justify-between gap-2 rounded-md px-3 py-2 text-sm font-medium text-text-secondary hover:bg-accent-primary/10 hover:text-accent-primary dark:hover:bg-accent-primary/20 dark:hover:text-accent-primary"
                            :class="{
                                'bg-accent-primary/10 text-accent-primary dark:bg-accent-primary/20 dark:text-accent-primary':
                                    isUsersRoute,
                            }"
                        >
                            <span>Users</span>
                            <span
                                class="transform transition-transform"
                                :class="{ 'rotate-90': usersMenuOpen }"
                            >
                                ›
                            </span>
                        </button>
                        <div
                            v-show="usersMenuOpen"
                            class="ml-4 mt-1 space-y-1 border-l border-border-light dark:border-border-dark pl-2"
                        >
                            <Link
                                v-for="subItem in userSubItems"
                                :key="subItem.route"
                                :href="route(subItem.route)"
                                class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-text-secondary hover:bg-accent-primary/10 hover:text-accent-primary dark:hover:bg-accent-primary/20 dark:hover:text-accent-primary"
                                :class="{
                                    '!bg-accent-primary/10 !text-accent-primary dark:!bg-accent-primary/20 dark:!text-accent-primary':
                                        route().current(`${subItem.route}*`),
                                }"
                            >
                                {{ subItem.name }}
                            </Link>
                        </div>
                    </div>

                </nav>
            </aside>

            <!-- Main -->
            <div class="flex-1 flex flex-col sm:pl-64 main-content">
                <header
                    class="h-16 bg-white dark:bg-surface-dark-muted border-b border-border-light dark:border-border-dark flex items-center px-4 sm:px-6 lg:px-8 justify-between"
                >
                    <div class="flex items-center gap-3">
                        <button
                            class="sm:hidden p-2 rounded hover:bg-surface-muted dark:hover:bg-surface-dark"
                            @click="sidebarOpen = !sidebarOpen"
                        >
                            <span class="sr-only">Toggle menu</span>
                            ☰
                        </button>
                        <h1 class="text-base font-semibold">Admin Panel</h1>
                    </div>
                    <div class="flex items-center gap-3">
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

                        <!-- Profile Dropdown -->
                        <div class="relative" ref="profileMenuRef">
                            <button
                                type="button"
                                class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-surface-muted dark:hover:bg-surface-dark transition-colors"
                                @click.stop="profileMenuOpen = !profileMenuOpen"
                            >
                                <div
                                    class="w-8 h-8 rounded-full bg-accent-primary text-white flex items-center justify-center text-sm font-bold"
                                >
                                    {{ profileInitials }}
                                </div>
                                <svg
                                    class="w-4 h-4 text-text-secondary transition-transform"
                                    :class="{ 'rotate-180': profileMenuOpen }"
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
                                    v-if="profileMenuOpen"
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
                                            :href="route('admin.settings')"
                                            @click="profileMenuOpen = false"
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
                                            @click="profileMenuOpen = false"
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
                    </div>
                </header>

                <main class="py-6 px-4 sm:px-6 lg:px-8">
                    <div
                        v-if="flash?.value?.success"
                        class="mb-4 rounded border border-green-200 bg-green-50 p-3 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200"
                    >
                        {{ flash.value.success }}
                    </div>
                    <Breadcrumb v-if="breadcrumbItems.length" :items="breadcrumbItems" />
                    <slot />
                </main>
            </div>
        </div>
        <Toast />
    </div>
</template>
<style>



</style>