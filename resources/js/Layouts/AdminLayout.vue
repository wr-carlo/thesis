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

const settingsItem = { name: "Settings", route: "admin.settings" };
const profileName = computed(() => page.props.auth?.user?.name || "Admin");

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
            <div class="flex-1 flex flex-col  sm:pl-64 main-content">
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
                        <button
                            type="button"
                            class="relative inline-flex h-8 w-16 items-center rounded-full border border-border-light dark:border-border-dark bg-surface-muted dark:bg-surface-dark transition-colors duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-primary/40"
                            :aria-label="
                                theme.theme === 'dark'
                                    ? 'Switch to light mode'
                                    : 'Switch to dark mode'
                            "
                            @click="toggleTheme()"
                        >
                            <span
                                class="absolute left-2 text-[11px] transition-opacity duration-300"
                                :class="
                                    theme.theme === 'dark'
                                        ? 'opacity-100'
                                        : 'opacity-40'
                                "
                                aria-hidden="true"
                            >
                                🌙
                            </span>
                            <span
                                class="absolute right-2 text-[11px] transition-opacity duration-300"
                                :class="
                                    theme.theme === 'dark'
                                        ? 'opacity-40'
                                        : 'opacity-100'
                                "
                                aria-hidden="true"
                            >
                                ☀️
                            </span>
                            <span
                                class="inline-block h-6 w-6 rounded-full bg-white dark:bg-surface-dark-muted shadow-sm transform transition-transform duration-300"
                                :class="
                                    theme.theme === 'dark'
                                        ? 'translate-x-1'
                                        : 'translate-x-9'
                                "
                            />
                        </button>
                        <div class="relative" ref="profileMenuRef">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-surface-muted dark:hover:bg-surface-dark transition-colors"
                                @click.stop="profileMenuOpen = !profileMenuOpen"
                            >
                                <span
                                    class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white font-semibold text-sm"
                                >
                                    {{ profileName.charAt(0).toUpperCase() }}
                                </span>
                                <svg
                                    class="h-4 w-4 text-text-secondary transition-transform"
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

                            <div
                                v-if="profileMenuOpen"
                                class="absolute right-0 mt-2 w-44 rounded-md border border-border-light dark:border-border-dark bg-white dark:bg-surface-dark-muted shadow-lg py-1 z-50"
                            >
                                <Link
                                    :href="route(settingsItem.route)"
                                    class="block px-4 py-2 text-sm text-text-secondary hover:bg-surface-muted hover:text-text-primary dark:hover:bg-surface-dark dark:hover:text-text-inverted"
                                >
                                    Settings
                                </Link>
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-surface-muted dark:hover:bg-surface-dark"
                                >
                                    Logout
                                </Link>
                            </div>
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