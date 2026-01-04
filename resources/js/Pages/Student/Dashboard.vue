<script setup>
import StudentLayout from "@/Layouts/StudentLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
    <StudentLayout>
        <Head title="Student Dashboard" />

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary dark:text-text-inverted">Dashboard</h1>
            <p class="text-text-secondary">Welcome back! Here's your overview.</p>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="card p-6">
                <div class="text-sm text-text-secondary mb-2">My Subjects</div>
                <div class="text-3xl font-bold text-text-primary dark:text-text-inverted">0</div>
                <p class="text-xs text-text-secondary mt-2">Enrolled subjects</p>
            </div>

            <div class="card p-6">
                <div class="text-sm text-text-secondary mb-2">Pending Assessments</div>
                <div class="text-3xl font-bold text-text-primary dark:text-text-inverted">0</div>
                <p class="text-xs text-text-secondary mt-2">To be completed</p>
            </div>

            <div class="card p-6">
                <div class="text-sm text-text-secondary mb-2">Completed Assessments</div>
                <div class="text-3xl font-bold text-text-primary dark:text-text-inverted">0</div>
                <p class="text-xs text-text-secondary mt-2">Finished assessments</p>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-text-primary dark:text-text-inverted mb-4">
                Recent Activity
            </h2>
            <div class="text-center py-12 text-text-secondary">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p>No recent activity</p>
            </div>
        </div>
    </StudentLayout>
</template>
