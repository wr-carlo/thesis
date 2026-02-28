<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from "vue";

const props = defineProps({
    modelValue: {
        type: [String, Number, null],
        default: null,
    },
    options: {
        type: Array,
        default: () => [],
        // Each option: { value: ..., label: '...', sublabel?: '...' }
    },
    placeholder: {
        type: String,
        default: "Search...",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue"]);

const search = ref("");
const isOpen = ref(false);
const containerRef = ref(null);
const inputRef = ref(null);

const selectedOption = computed(() =>
    props.options.find((o) => o.value === props.modelValue) || null
);

const filteredOptions = computed(() => {
    if (!search.value) return props.options;
    const q = search.value.toLowerCase();
    return props.options.filter(
        (o) =>
            o.label.toLowerCase().includes(q) ||
            (o.sublabel && o.sublabel.toLowerCase().includes(q))
    );
});

const selectOption = (option) => {
    emit("update:modelValue", option.value);
    search.value = "";
    isOpen.value = false;
};

const clearSelection = () => {
    emit("update:modelValue", null);
    search.value = "";
    nextTick(() => {
        inputRef.value?.focus();
        isOpen.value = true;
    });
};

const openDropdown = () => {
    if (!props.disabled) {
        isOpen.value = true;
    }
};

// Close on outside click
const handleClickOutside = (e) => {
    if (containerRef.value && !containerRef.value.contains(e.target)) {
        isOpen.value = false;
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
    <div ref="containerRef" class="relative">
        <!-- Selected display -->
        <div
            v-if="selectedOption"
            class="flex items-center gap-2.5 px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 cursor-pointer"
            @click="clearSelection"
        >
            <div
                class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-bold flex-shrink-0"
            >
                {{ selectedOption.label.charAt(0).toUpperCase() }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ selectedOption.label }}
                </p>
                <p v-if="selectedOption.sublabel" class="text-xs text-gray-400 truncate">
                    {{ selectedOption.sublabel }}
                </p>
            </div>
            <button
                type="button"
                @click.stop="clearSelection"
                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Search input -->
        <div v-else class="relative">
            <svg
                class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
                ref="inputRef"
                v-model="search"
                type="text"
                :placeholder="placeholder"
                :disabled="disabled"
                class="w-full pl-10 pr-8 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                @focus="openDropdown"
                @input="openDropdown"
            />
            <svg
                class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none transition-transform"
                :class="{ 'rotate-180': isOpen }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <!-- Dropdown list -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isOpen && !selectedOption"
                class="absolute z-20 mt-1 w-full max-h-52 overflow-y-auto bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg"
            >
                <button
                    v-for="option in filteredOptions"
                    :key="option.value"
                    type="button"
                    @click="selectOption(option)"
                    class="w-full flex items-center gap-3 px-3 py-2.5 text-left hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                >
                    <div
                        class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-bold flex-shrink-0"
                    >
                        {{ option.label.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ option.label }}
                        </p>
                        <p v-if="option.sublabel" class="text-xs text-gray-400 dark:text-gray-500 truncate">
                            {{ option.sublabel }}
                        </p>
                    </div>
                </button>
                <div
                    v-if="filteredOptions.length === 0"
                    class="px-4 py-3 text-sm text-gray-400 text-center"
                >
                    No results found
                </div>
            </div>
        </Transition>
    </div>
</template>
