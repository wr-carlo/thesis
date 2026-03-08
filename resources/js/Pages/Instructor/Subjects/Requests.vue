<script setup>
import InstructorLayout from '@/Layouts/InstructorLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useToast } from '@/Stores/useToast';

const page = usePage();
const { success } = useToast();

const props = defineProps({
    subject: Object,
    requests: Array,
    approvedStudents: Array,
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

const dropStudent = (student) => {
    if (confirm(`Are you sure you want to drop ${student.student_name} from ${props.subject.name}?`)) {
        processingRequest.value = student.id;
        router.delete(
            route('instructor.subjects.requests.drop', {
                subject: props.subject.id,
                studentSubject: student.id
            }),
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

// Modal specific logic
const showRequestsModal = ref(false);

watch(showRequestsModal, (newValue) => {
    if (newValue) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = 'auto';
    }
});
</script>

<template>
    <InstructorLayout>
        <Head :title="`Join Requests - ${subject.name}`" />

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary dark:text-text-inverted">
                Join Requests - {{ subject.name }}
            </h1>
            <p class="text-text-secondary">Manage student requests to join this subject.</p>
        </div>
        
        <div class="card p-6 mb-6 flex items-center justify-between flex-wrap gap-4">
            <div>
                <h3 class="text-lg font-semibold text-text-primary dark:text-text-inverted">{{ subject.name }}</h3>
                <p class="text-sm text-text-secondary mt-1">{{ subject.code }}</p>
                <p v-if="subject.description" class="text-sm text-text-secondary mt-2">
                    {{ subject.description }}
                </p>
            </div>
            <div>
                <button
                    @click="showRequestsModal = true"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition-colors shadow-sm"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    View Pending Requests
                    <span v-if="hasRequests" class="ml-2 bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                        {{ requests.length }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Approved Students List -->
        <div class="card p-6 mt-6">
            <h3 class="text-lg font-semibold text-text-primary dark:text-text-inverted mb-4">
                Enrolled Students ({{ approvedStudents?.length || 0 }})
            </h3>
            
            <div v-if="approvedStudents && approvedStudents.length > 0" class="space-y-4">
                <div
                    v-for="student in approvedStudents"
                    :key="student.id"
                    class="border border-border-light dark:border-border-dark rounded-lg p-4 bg-gray-50 dark:bg-gray-800/50"
                >
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-accent-primary flex items-center justify-center">
                                        <span class="text-white font-semibold text-sm">
                                            {{ student.student_name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-sm font-semibold text-text-primary dark:text-text-inverted truncate">
                                        {{ student.student_name }}
                                    </h4>
                                    <p class="text-sm text-text-secondary truncate">
                                        ID: {{ student.student_id_number }} • Section: {{ student.section_name }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                Approved
                            </span>
                            
                            <!-- Drop Button -->
                            <button
                                @click="dropStudent(student)"
                                :disabled="isProcessing(student.id)"
                                class="inline-flex items-center p-1.5 bg-red-100 hover:bg-red-200 text-red-700 dark:bg-red-900/30 dark:hover:bg-red-900/50 dark:text-red-400 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                title="Drop Student"
                            >
                                <svg v-if="isProcessing(student.id)" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div v-else class="text-center text-text-secondary py-8 border border-dashed border-border-light dark:border-border-dark rounded-lg">
                <p class="text-sm">There are currently no approved students for this subject.</p>
            </div>
        </div>

        <!-- Pending Requests Modal -->
        <div v-if="showRequestsModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900/40 dark:bg-gray-900/80 backdrop-blur-sm transition-opacity" aria-hidden="true" @click="showRequestsModal = false"></div>

                <div class="relative inline-block bg-white dark:bg-gray-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-3xl sm:w-full border border-gray-100 dark:border-gray-800 flex flex-col max-h-[90vh]">
                    <div class="p-5 sm:p-6 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white tracking-tight" id="modal-title">
                                Pending Join Requests
                            </h3>
                            <button @click="showRequestsModal = false" class="text-gray-400 hover:text-gray-500 bg-gray-100 dark:bg-gray-800 p-1.5 rounded-full transition-colors">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="p-5 sm:p-6 overflow-y-auto flex-1">
                        <!-- Empty State -->
                        <div v-if="!hasRequests" class="text-center text-text-secondary py-12">
                            <svg class="mx-auto h-12 w-12 mb-4 opacity-50 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">No pending requests</h3>
                            <p class="mt-1 text-sm text-gray-500">There are no pending join requests for this subject.</p>
                        </div>

                        <!-- Requests List -->
                        <div v-else class="space-y-4">
                            <div
                                v-for="request in requests"
                                :key="request.id"
                                class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-indigo-400 dark:hover:border-indigo-500 transition-colors duration-200 bg-white dark:bg-gray-800 shadow-sm"
                                :class="{ 'opacity-50': isProcessing(request.id) }"
                            >
                                <div class="flex items-center justify-between flex-wrap gap-4">
                                    <!-- Student Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center border border-indigo-200 dark:border-indigo-800">
                                                    <span class="text-indigo-700 dark:text-indigo-400 font-bold text-sm">
                                                        {{ request.student_name.charAt(0).toUpperCase() }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="min-w-0">
                                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                                    {{ request.student_name }}
                                                </h4>
                                                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                    ID: {{ request.student_id_number }} • Section: {{ request.section_name }}
                                                </p>
                                                <p class="text-xs text-gray-400 mt-0.5">
                                                    Requested: {{ new Date(request.requested_at).toLocaleDateString() }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex gap-2 flex-shrink-0">
                                        <button
                                            @click="approveRequest(request)"
                                            :disabled="isProcessing(request.id)"
                                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
                                        >
                                            <svg v-if="isProcessing(request.id)" class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <svg v-else class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Approve
                                        </button>
                                        
                                        <button
                                            @click="declineRequest(request)"
                                            :disabled="isProcessing(request.id)"
                                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-red-200 dark:border-red-900/50 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg font-medium text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
                                        >
                                            <svg v-if="isProcessing(request.id)" class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <svg v-else class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Decline
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-5 py-4 sm:px-6 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50 flex justify-end">
                        <button type="button" @click="showRequestsModal = false" class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-all shadow-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </InstructorLayout>
</template>

