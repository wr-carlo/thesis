<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    items: {
        type: Array,
        required: true,
        validator: (items) => {
            return items.every(
                (item) =>
                    typeof item === "object" &&
                    "label" in item &&
                    ("route" in item || "href" in item || item.current === true)
            );
        },
    },
});

const getItemHref = (item) => {
    if (item.href) return item.href;
    if (item.route) {
        const params = item.params ?? undefined;
        return params !== undefined ? route(item.route, params) : route(item.route);
    }
    return null;
};
</script>

<template>
    <nav class="mb-4" aria-label="Breadcrumb">
        <ol class="flex items-center gap-2 flex-wrap text-sm">
            <li
                v-for="(item, index) in items"
                :key="index"
                class="flex items-center gap-2"
            >
                <template v-if="index > 0">
                    <svg
                        class="w-4 h-4 text-text-secondary flex-shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </template>
                <Link
                    v-if="!item.current && (item.route || item.href)"
                    :href="getItemHref(item)"
                    class="text-text-secondary hover:text-text-primary dark:hover:text-text-inverted transition-colors"
                >
                    {{ item.label }}
                </Link>
                <span
                    v-else
                    class="text-text-primary dark:text-text-inverted font-medium"
                    aria-current="page"
                >
                    {{ item.label }}
                </span>
            </li>
        </ol>
    </nav>
</template>

