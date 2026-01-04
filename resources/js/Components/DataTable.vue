<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    headers: Array,
    rows: Array,
    links: Array,
    emptyText: {
        type: String,
        default: "No records found.",
    },
});
</script>

<template>
    <div class="card p-4 h-full flex flex-col">
        <div class="overflow-x-auto flex-1">
            <table class="min-w-full text-sm">
                <thead class="bg-surface-muted dark:bg-surface-dark-muted">
                    <tr>
                        <th
                            v-for="(head, idx) in headers"
                            :key="idx"
                            class="px-3 py-2 text-left text-text-secondary"
                        >
                            {{ head }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!rows.length">
                        <td
                            :colspan="headers.length"
                            class="px-3 py-6 text-center text-text-secondary"
                        >
                            {{ emptyText }}
                        </td>
                    </tr>
                    <tr
                        v-for="row in rows"
                        :key="row.id"
                        class="border-b border-border-light dark:border-border-dark"
                    >
                        <slot name="row" :row="row" />
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex gap-2 flex-wrap">
            <Link
                v-for="link in links"
                :key="link.url || link.label"
                :href="link.url || '#'"
                v-html="link.label"
                class="px-3 py-1 rounded border text-sm transition"
                :class="[
                    link.active
                        ? 'bg-accent-primary text-white border-transparent'
                        : 'bg-surface text-text-secondary border-border-light dark:border-border-dark dark:bg-surface-dark dark:text-text-inverted hover:bg-surface-muted dark:hover:bg-surface-dark-muted',
                    !link.url || link.url === '#' || link.url === null
                        ? 'opacity-50 cursor-not-allowed pointer-events-none'
                        : 'cursor-pointer',
                ]"
            />
        </div>
    </div>
</template>
