<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref, onMounted, watch } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Toast from "@/Components/Toast.vue";
import { toggleTheme, useTheme } from "@/Stores/useTheme";

const page = usePage();
const flash = computed(() => page.props.flash || {});
const theme = useTheme();
const sidebarOpen = ref(false);
const usersMenuOpen = ref(false);

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
    }
);
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

                    <span class="text-lg font-semibold">CHCC Inc.</span>
                </div>
                <nav class="p-4 space-y-1">
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route)"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-text-secondary hover:bg-surface-muted hover:text-text-primary dark:hover:bg-surface-dark dark:hover:text-text-inverted"
                        :class="{
                            'bg-surface-muted text-text-primary dark:bg-surface-dark dark:text-text-inverted':
                                route().current(item.route),
                        }"
                    >
                        {{ item.name }}
                    </Link>

                    <!-- Users Accordion -->
                    <div>
                        <button
                            @click="usersMenuOpen = !usersMenuOpen"
                            class="w-full flex items-center justify-between gap-2 rounded-md px-3 py-2 text-sm font-medium text-text-secondary hover:bg-surface-muted hover:text-text-primary dark:hover:bg-surface-dark dark:hover:text-text-inverted"
                            :class="{
                                'bg-surface-muted text-text-primary dark:bg-surface-dark dark:text-text-inverted':
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
                                class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-text-secondary hover:bg-surface-muted hover:text-text-primary dark:hover:bg-surface-dark dark:hover:text-text-inverted"
                                :class="{
                                    'bg-surface-muted text-text-primary dark:bg-surface-dark dark:text-text-inverted':
                                        route().current(subItem.route),
                                }"
                            >
                                {{ subItem.name }}
                            </Link>
                        </div>
                    </div>

                    <!-- Settings Link with Divider -->
                    <div
                        class="pt-2 mt-2 border-t border-border-light dark:border-border-dark"
                    >
                        <Link
                            :href="route(settingsItem.route)"
                            class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-text-secondary hover:bg-surface-muted hover:text-text-primary dark:hover:bg-surface-dark dark:hover:text-text-inverted"
                            :class="{
                                'bg-surface-muted text-text-primary dark:bg-surface-dark dark:text-text-inverted':
                                    route().current(settingsItem.route),
                            }"
                        >
                            <svg
                                class="w-4 h-4"
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
                            {{ settingsItem.name }}
                        </Link>
                    </div>
                </nav>
            </aside>

            <!-- Main -->
            <div class="flex-1 flex flex-col min-h-screen sm:pl-64">
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
                            class="btn-ghost rounded-full px-3 py-2 text-sm"
                            @click="toggleTheme()"
                        >
                            {{
                                theme.theme === "dark" ? "🌙 Dark" : "☀️ Light"
                            }}
                        </button>
                        <button
                            type="button"
                            class="btn-ghost text-red-600 dark:text-red-400"
                            @click.prevent="$inertia.post(route('logout'))"
                        >
                            Logout
                        </button>
                    </div>
                </header>

                <main class="py-6 px-4 sm:px-6 lg:px-8">
                    <div
                        v-if="flash?.value?.success"
                        class="mb-4 rounded border border-green-200 bg-green-50 p-3 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200"
                    >
                        {{ flash.value.success }}
                    </div>
                    <slot />
                </main>
            </div>
        </div>
        <Toast />
    </div>
</template>
