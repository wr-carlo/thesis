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
</script>

<template>
    <nav class="mb-4" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li
                v-for="(item, index) in items"
                :key="index"
                class="flex items-center"
            >
                <Link
                    v-if="!item.current && (item.route || item.href)"
                    :href="item.route ? route(item.route) : item.href"
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
                <span
                    v-if="index < items.length - 1"
                    class="mx-2 text-text-secondary"
                >
                    /
                </span>
            </li>
        </ol>
    </nav>
</template>

