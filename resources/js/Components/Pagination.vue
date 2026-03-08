<script setup>
import { Link, router } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    /** Laravel paginator links array */
    links: {
        type: Array,
        default: () => [],
    },
    /** Current page number */
    currentPage: {
        type: Number,
        default: 1,
    },
    /** Last page number */
    lastPage: {
        type: Number,
        default: 1,
    },
    /** Items per page */
    perPage: {
        type: Number,
        default: 10,
    },
    /** Total items */
    total: {
        type: Number,
        default: 0,
    },
    /** From index (e.g. 1) */
    from: {
        type: Number,
        default: 0,
    },
    /** To index (e.g. 10) */
    to: {
        type: Number,
        default: 0,
    },
    /** Route name for building URLs when per_page changes (e.g. 'admin.students.index') */
    routeName: {
        type: String,
        required: true,
    },
    /** Current query/filter params to preserve (e.g. { search: '', department_id: '' }) */
    filters: {
        type: Object,
        default: () => ({}),
    },
    /** Per-page options [10, 25, 50, 100] */
    perPageOptions: {
        type: Array,
        default: () => [10, 25, 50, 100],
    },
});

const prevLink = computed(() => props.links[0] || {});
const nextLink = computed(() => props.links[props.links.length - 1] || {});

/** Compute which page numbers to display (1,2,3,4,5,...,last) */
const displayPages = computed(() => {
    const last = props.lastPage;
    const current = props.currentPage;

    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => ({ type: "page", number: i + 1 }));
    }

    const result = [{ type: "page", number: 1 }];

    if (current <= 4) {
        for (let i = 2; i <= Math.min(5, last - 1); i++) {
            result.push({ type: "page", number: i });
        }
        result.push({ type: "ellipsis" });
    } else if (current >= last - 3) {
        result.push({ type: "ellipsis" });
        for (let i = Math.max(2, last - 4); i < last; i++) {
            result.push({ type: "page", number: i });
        }
    } else {
        result.push({ type: "ellipsis" });
        for (let i = current - 1; i <= current + 1; i++) {
            result.push({ type: "page", number: i });
        }
        result.push({ type: "ellipsis" });
    }

    if (last > 1) {
        result.push({ type: "page", number: last });
    }

    return result;
});

const pageLink = (pageNum) => {
    const link = props.links.find((l) => {
        const label = typeof l.label === "string" ? l.label.replace(/<[^>]*>/g, "").trim() : "";
        return label === String(pageNum);
    });
    return link?.url || route(props.routeName, { ...props.filters, per_page: props.perPage, page: pageNum });
};

const changePerPage = (value) => {
    const perPage = Number(value);
    if (perPage === props.perPage) return;
    router.get(route(props.routeName), {
        ...props.filters,
        per_page: perPage,
        page: 1,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const showPagination = computed(() => props.links && props.links.length >= 1 && props.total > 0);

const perPageOptionsSorted = computed(() => {
    const opts = new Set(props.perPageOptions);
    if (!opts.has(props.perPage)) {
        opts.add(props.perPage);
    }
    return Array.from(opts).sort((a, b) => a - b);
});
</script>

<template>
    <div
        v-if="showPagination"
        class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4"
    >
        <!-- Mobile: Prev/Next only -->
        <div class="flex-1 flex justify-between sm:hidden w-full">
            <Link
                v-if="prevLink.url"
                :href="prevLink.url"
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                preserve-scroll
            >
                Previous
            </Link>
            <span v-else class="px-4 py-2 text-sm text-gray-400 dark:text-gray-500">Previous</span>
            <Link
                v-if="nextLink.url"
                :href="nextLink.url"
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                preserve-scroll
            >
                Next
            </Link>
            <span v-else class="px-4 py-2 text-sm text-gray-400 dark:text-gray-500">Next</span>
        </div>

        <!-- Desktop: Full pagination -->
        <div class="hidden sm:flex flex-1 items-center justify-between w-full">
            <div class="text-sm text-gray-700 dark:text-gray-300">
                Showing
                <span class="font-medium">{{ from || 0 }}</span>
                to
                <span class="font-medium">{{ to || 0 }}</span>
                of
                <span class="font-medium">{{ total || 0 }}</span>
                results
            </div>

            <div class="flex items-center gap-3">
                <!-- Previous -->
                <Link
                    v-if="prevLink.url"
                    :href="prevLink.url"
                    class="px-3 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors"
                    preserve-scroll
                >
                    Previous
                </Link>
                <span
                    v-else
                    class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500"
                >
                    Previous
                </span>

                <!-- Page numbers -->
                <div class="flex items-center gap-1">
                    <template v-for="(item, idx) in displayPages" :key="idx">
                        <Link
                            v-if="item.type === 'page'"
                            :href="pageLink(item.number)"
                            :class="[
                                'min-w-[2.25rem] px-3 py-2 text-sm font-medium rounded-lg text-center transition-colors',
                                item.number === currentPage
                                    ? 'bg-indigo-600 text-white'
                                    : 'text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700',
                            ]"
                            preserve-scroll
                        >
                            {{ item.number }}
                        </Link>
                        <span
                            v-else
                            class="px-2 py-2 text-sm text-gray-400 dark:text-gray-500"
                        >
                            ...
                        </span>
                    </template>
                </div>

                <!-- Next -->
                <Link
                    v-if="nextLink.url"
                    :href="nextLink.url"
                    class="px-3 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors"
                    preserve-scroll
                >
                    Next
                </Link>
                <span
                    v-else
                    class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500"
                >
                    Next
                </span>

                <!-- Per page selector -->
                <div class="flex items-center gap-2 ml-2 pl-2 border-l border-gray-200 dark:border-gray-600">
                    <select
                        :value="perPage"
                        @change="changePerPage(($event.target).value)"
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                    >
                        <option
                            v-for="opt in perPageOptionsSorted"
                            :key="opt"
                            :value="opt"
                        >
                            {{ opt }} / page
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>
