<template>
    <div
        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden mt-6 mb-6"
    >
        <!-- Header -->
        <div
            class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700"
        >
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <svg
                        class="w-5 h-5 text-indigo-600 dark:text-indigo-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                    </svg>
                    <h3
                        class="text-lg font-semibold text-gray-900 dark:text-white"
                    >
                        Assign Sections
                    </h3>
                </div>
                <div
                    v-if="selectedSectionIds.length > 0"
                    class="flex items-center gap-2 px-3 py-1.5 bg-indigo-100 dark:bg-indigo-900/30 rounded-full"
                >
                    <span
                        class="text-sm font-medium text-indigo-700 dark:text-indigo-300"
                    >
                        {{ selectedSectionIds.length }}
                    </span>
                    <span class="text-xs text-indigo-600 dark:text-indigo-400">
                        {{
                            selectedSectionIds.length === 1
                                ? "section"
                                : "sections"
                        }}
                        selected
                    </span>
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <!-- Search Input -->
                <div class="relative">
                    <div
                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                    >
                        <svg
                            class="h-5 w-5 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search sections..."
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition"
                    />
                </div>

                <!-- Department Filter -->
                <div class="relative">
                    <div
                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                    >
                        <svg
                            class="h-5 w-5 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                            />
                        </svg>
                    </div>
                    <select
                        v-model="selectedDepartmentId"
                        class="block w-full pl-10 pr-8 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent appearance-none cursor-pointer transition"
                    >
                        <option :value="null">All Departments</option>
                        <option
                            v-for="department in departments"
                            :key="department.id"
                            :value="department.id"
                        >
                            {{ department.name }}
                        </option>
                    </select>
                    <div
                        class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
                    >
                        <svg
                            class="h-5 w-5 text-gray-400"
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
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div
            class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-3"
        >
            <button
                type="button"
                @click="toggleSelectAllFiltered"
                class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 transition"
            >
                <svg
                    class="w-4 h-4"
                    :class="{
                        'text-indigo-600 dark:text-indigo-400':
                            allFilteredSelected,
                        'text-gray-400': !allFilteredSelected,
                    }"
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
                <span>Select All Filtered</span>
            </button>
            <button
                type="button"
                @click="toggleSelectAll"
                class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 transition"
            >
                <svg
                    class="w-4 h-4"
                    :class="{
                        'text-indigo-600 dark:text-indigo-400': allSelected,
                        'text-gray-400': !allSelected,
                    }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    v-if="!allSelected"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <svg
                    v-else
                    class="w-4 h-4 text-indigo-600 dark:text-indigo-400"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd"
                    />
                </svg>
                <span>Select All</span>
            </button>
            <div class="flex-1"></div>
            <span
                v-if="searchQuery || selectedDepartmentId"
                class="text-xs text-gray-500 dark:text-gray-400"
            >
                Showing {{ filteredSections.length }} section(s)
            </span>
        </div>

        <!-- Selected Sections Tags (if any selected) -->
        <div
            v-if="selectedSectionIds.length > 0"
            class="px-6 py-3 bg-indigo-50 dark:bg-indigo-900/10 border-b border-gray-200 dark:border-gray-700"
        >
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="sectionId in selectedSectionIds"
                    :key="sectionId"
                    class="inline-flex items-center gap-1.5 px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-200 rounded-full text-xs font-medium"
                >
                    {{ getSectionName(sectionId) }}
                    <button
                        @click="toggleSection(sectionId)"
                        class="hover:bg-indigo-200 dark:hover:bg-indigo-800 rounded-full p-0.5 transition"
                    >
                        <svg
                            class="w-3 h-3"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </span>
            </div>
        </div>

        <!-- Sections List -->
        <div class="max-h-96 overflow-y-auto">
            <div v-if="filteredSections.length === 0" class="p-12 text-center">
                <svg
                    class="mx-auto h-12 w-12 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                    />
                </svg>
                <p
                    class="mt-4 text-sm font-medium text-gray-900 dark:text-white"
                >
                    No sections found
                </p>
                <p
                    class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                    v-if="searchQuery || selectedDepartmentId"
                >
                    Try adjusting your search or filter
                </p>
            </div>

            <!-- Grouped by Department -->
            <template v-else>
                <div
                    v-for="(deptSections, departmentId) in groupedSections"
                    :key="departmentId"
                    class="border-b border-gray-200 dark:border-gray-700 last:border-b-0"
                >
                    <!-- Department Header -->
                    <div
                        class="sticky top-0 z-10 px-6 py-3 bg-gray-50 dark:bg-gray-900/80 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700"
                    >
                        <div
                            class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300"
                        >
                            <svg
                                class="w-4 h-4 text-indigo-600 dark:text-indigo-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                                />
                            </svg>
                            {{ getDepartmentName(departmentId) }}
                            <span
                                class="ml-auto text-xs font-normal text-gray-500 dark:text-gray-400"
                            >
                                {{ deptSections.length }}
                                {{
                                    deptSections.length === 1
                                        ? "section"
                                        : "sections"
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Sections in Department -->
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        <label
                            v-for="section in deptSections"
                            :key="section.id"
                            class="flex items-center px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer transition group"
                        >
                            <div class="flex items-center h-5">
                                <input
                                    type="checkbox"
                                    :value="section.id"
                                    v-model="selectedSectionIds"
                                    class="w-4 h-4 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:ring-offset-0 cursor-pointer transition"
                                />
                            </div>
                            <div class="ml-4 flex-1">
                                <div
                                    class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition"
                                >
                                    {{ section.name }}
                                </div>
                                <div
                                    class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                                >
                                    {{
                                        getDepartmentName(
                                            getSectionDepartmentId(section)
                                        )
                                    }}
                                </div>
                            </div>
                            <div
                                v-if="selectedSectionIds.includes(section.id)"
                                class="ml-3"
                            >
                                <svg
                                    class="w-5 h-5 text-indigo-600 dark:text-indigo-400"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                        </label>
                    </div>
                </div>
            </template>
        </div>

        <!-- Error Display -->
        <div
            v-if="errors && errors.length > 0"
            class="px-6 py-3 bg-red-50 dark:bg-red-900/20 border-t border-red-200 dark:border-red-800"
        >
            <InputError :message="errors[0]" />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    departments: {
        type: Array,
        default: () => [],
    },
    sections: {
        type: Array,
        default: () => [],
    },
    selectedSectionIds: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["update:selectedSectionIds"]);

const selectedDepartmentId = ref(null);
const searchQuery = ref("");
const selectedSectionIds = ref([...props.selectedSectionIds]);

// Watch for prop changes and update local state
watch(
    () => props.selectedSectionIds,
    (newVal) => {
        selectedSectionIds.value = [...newVal];
    },
    { deep: true }
);

// Watch for local changes and emit to parent
watch(
    selectedSectionIds,
    (newVal) => {
        emit("update:selectedSectionIds", newVal);
    },
    { deep: true }
);

// Helper function to get department_id from section (handles both direct and nested)
const getSectionDepartmentId = (section) => {
    return section.department_id ?? section.department?.id ?? null;
};

// Get filtered sections based on selected department and search query
const filteredSections = computed(() => {
    let sections = props.sections;

    // Filter by department
    if (selectedDepartmentId.value) {
        sections = sections.filter(
            (section) =>
                getSectionDepartmentId(section) === selectedDepartmentId.value
        );
    }

    // Filter by search query
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase().trim();
        sections = sections.filter((section) => {
            const sectionName = (section.name || "").toLowerCase();
            const deptId = getSectionDepartmentId(section);
            const deptName = getDepartmentName(deptId).toLowerCase();
            return sectionName.includes(query) || deptName.includes(query);
        });
    }

    return sections;
});

// Group sections by department
const groupedSections = computed(() => {
    const groups = {};
    filteredSections.value.forEach((section) => {
        const deptId = getSectionDepartmentId(section);
        if (deptId) {
            if (!groups[deptId]) {
                groups[deptId] = [];
            }
            groups[deptId].push(section);
        }
    });
    return groups;
});

// Get department name by ID
const getDepartmentName = (departmentId) => {
    if (!departmentId && departmentId !== 0) return "Unknown";

    // Normalize IDs to numbers for comparison
    const normalizedDeptId = Number(departmentId);

    // Try to find department - handle both string and number ID comparisons
    const department = props.departments.find((d) => {
        if (!d || d.id === undefined) return false;
        const deptId = Number(d.id);
        return deptId === normalizedDeptId;
    });

    if (department && department.name) {
        return department.name;
    }

    // Fallback: try to get from section's nested department object if available
    if (props.sections && props.sections.length > 0) {
        const section = props.sections.find((s) => {
            const sectionDeptId = getSectionDepartmentId(s);
            return sectionDeptId && Number(sectionDeptId) === normalizedDeptId;
        });

        if (section && section.department && section.department.name) {
            return section.department.name;
        }
    }

    return "Unknown";
};

// Get section name by ID
const getSectionName = (sectionId) => {
    const section = props.sections.find((s) => s.id === sectionId);
    return section ? section.name : "Unknown";
};

// Check if all filtered sections are selected
const allFilteredSelected = computed(() => {
    if (filteredSections.value.length === 0) return false;
    return filteredSections.value.every((section) =>
        selectedSectionIds.value.includes(section.id)
    );
});

// Check if some filtered sections are selected (for indeterminate state)
const someFilteredSelected = computed(() => {
    const selectedCount = filteredSections.value.filter((section) =>
        selectedSectionIds.value.includes(section.id)
    ).length;
    return selectedCount > 0 && selectedCount < filteredSections.value.length;
});

// Check if all sections are selected
const allSelected = computed(() => {
    if (props.sections.length === 0) return false;
    return props.sections.every((section) =>
        selectedSectionIds.value.includes(section.id)
    );
});

// Check if some sections are selected (for indeterminate state)
const someSelected = computed(() => {
    return (
        selectedSectionIds.value.length > 0 &&
        selectedSectionIds.value.length < props.sections.length
    );
});

// Toggle individual section
const toggleSection = (sectionId) => {
    const index = selectedSectionIds.value.indexOf(sectionId);
    if (index > -1) {
        selectedSectionIds.value.splice(index, 1);
    } else {
        selectedSectionIds.value.push(sectionId);
    }
};

// Toggle select all filtered sections
const toggleSelectAllFiltered = () => {
    if (allFilteredSelected.value) {
        // Deselect all filtered sections
        const filteredIds = filteredSections.value.map((s) => s.id);
        selectedSectionIds.value = selectedSectionIds.value.filter(
            (id) => !filteredIds.includes(id)
        );
    } else {
        // Select all filtered sections
        const filteredIds = filteredSections.value.map((s) => s.id);
        const combined = [
            ...new Set([...selectedSectionIds.value, ...filteredIds]),
        ];
        selectedSectionIds.value = combined;
    }
};

// Toggle select all sections
const toggleSelectAll = () => {
    if (allSelected.value) {
        // Deselect all sections
        selectedSectionIds.value = [];
    } else {
        // Select all sections
        selectedSectionIds.value = props.sections.map((s) => s.id);
    }
};
</script>
