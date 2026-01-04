<script setup>
import InstructorLayout from '@/Layouts/InstructorLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useToast } from '@/Stores/useToast';

const page = usePage();
const { success } = useToast();

const props = defineProps({
    subject: Object,
    requests: Array,
});

const flash = computed(() => page.props.flash || {});
const hasRequests = computed(() => props.requests && props.requests.length > 0);

const processingRequest = ref(null);

// Watch for flash messages and show toast notifications
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.message) {
            if (flash.type === 'success') {
                success(flash.message);
            }
        }
    }
);

const approveRequest = (request) => {
    if (confirm(`Approve ${request.student_name}'s request to join ${props.subject.name}?`)) {
        processingRequest.value = request.id;
        router.post(
            route('instructor.subjects.requests.approve', {
                subject: props.subject.id,
                studentSubject: request.id
            }),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    processingRequest.value = null;
                }
            }
        );
    }
};

const declineRequest = (request) => {
    if (confirm(`Decline ${request.student_name}'s request to join ${props.subject.name}?`)) {
        processingRequest.value = request.id;
        router.post(
            route('instructor.subjects.requests.decline', {
                subject: props.subject.id,
                studentSubject: request.id
            }),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    processingRequest.value = null;
                }
            }
        );
    }
};

const isProcessing = (requestId) => processingRequest.value === requestId;
</script>

<template>
    <InstructorLayout>
        <Head :title="`Join Requests - ${subject.name}`" />

        <div class="mb-6">
            <Link
                :href="route('instructor.subjects.index')"
                class="text-sm text-text-secondary hover:text-text-primary dark:hover:text-text-inverted mb-2 inline-flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Subjects
            </Link>
            <h1 class="text-2xl font-bold text-text-primary dark:text-text-inverted mt-2">
                Join Requests - {{ subject.name }}
            </h1>
            <p class="text-text-secondary">Manage student requests to join this subject.</p>
        </div>
        <!-- Subject Info Card -->
        <div class="card p-6 mb-6">
            <h3 class="text-lg font-semibold text-text-primary dark:text-text-inverted">{{ subject.name }}</h3>
            <p class="text-sm text-text-secondary mt-1">{{ subject.code }}</p>
            <p v-if="subject.description" class="text-sm text-text-secondary mt-2">
                {{ subject.description }}
            </p>
        </div>

        <!-- Empty State -->
        <div v-if="!hasRequests" class="card p-6 text-center text-text-secondary">
            <svg class="mx-auto h-12 w-12 mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="text-sm font-medium text-text-primary dark:text-text-inverted">No pending requests</h3>
            <p class="mt-1 text-sm">
                There are no pending join requests for this subject.
            </p>
        </div>

        <!-- Requests List -->
        <div v-else class="card p-6">
            <h3 class="text-lg font-semibold text-text-primary dark:text-text-inverted mb-4">
                Pending Requests ({{ requests.length }})
            </h3>
            
            <div class="space-y-4">
                <div
                    v-for="request in requests"
                    :key="request.id"
                    class="border border-border-light dark:border-border-dark rounded-lg p-4 hover:border-accent-primary transition-colors duration-200"
                    :class="{ 'opacity-50': isProcessing(request.id) }"
                >
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <!-- Student Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-accent-primary/10 flex items-center justify-center">
                                        <span class="text-accent-primary font-semibold text-sm">
                                            {{ request.student_name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-sm font-semibold text-text-primary dark:text-text-inverted truncate">
                                        {{ request.student_name }}
                                    </h4>
                                    <p class="text-sm text-text-secondary truncate">
                                        ID: {{ request.student_id_number }} • Section: {{ request.section_name }}
                                    </p>
                                    <p class="text-xs text-text-secondary mt-1">
                                        Requested: {{ request.requested_at }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 flex-shrink-0">
                            <button
                                @click="approveRequest(request)"
                                :disabled="isProcessing(request.id)"
                                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="isProcessing(request.id)" class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Approve
                            </button>
                            
                            <button
                                @click="declineRequest(request)"
                                :disabled="isProcessing(request.id)"
                                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="isProcessing(request.id)" class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Decline
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </InstructorLayout>
</template>

