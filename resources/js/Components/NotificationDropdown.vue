<script setup>
import { onMounted, onUnmounted, ref } from "vue";
import axios from "axios";

const isOpen = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const isLoading = ref(false);
const pollInterval = ref(null);

const fetchNotifications = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get(
            route("instructor.notifications.unread")
        );
        notifications.value = response.data.notifications;
        unreadCount.value = response.data.unreadCount;
    } catch (error) {
        console.error("Failed to fetch notifications:", error);
    } finally {
        isLoading.value = false;
    }
};

const markAsRead = async (notificationId) => {
    try {
        await axios.post(
            route("instructor.notifications.read", notificationId)
        );
        // Update local state
        const notification = notifications.value.find(
            (n) => n.id === notificationId
        );
        if (notification) {
            notification.read_at = new Date().toISOString();
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
    } catch (error) {
        console.error("Failed to mark notification as read:", error);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post(route("instructor.notifications.read-all"));
        notifications.value.forEach((n) => {
            n.read_at = new Date().toISOString();
        });
        unreadCount.value = 0;
    } catch (error) {
        console.error("Failed to mark all as read:", error);
    }
};

const handleNotificationClick = (notification) => {
    // Just mark as read, no navigation
    if (!notification.read_at) {
        markAsRead(notification.id);
    }
    isOpen.value = false;
};

onMounted(() => {
    fetchNotifications();

    // Poll every 30 seconds
    pollInterval.value = setInterval(() => {
        fetchNotifications();
    }, 30000);
});

onUnmounted(() => {
    if (pollInterval.value) {
        clearInterval(pollInterval.value);
    }
});
</script>

<template>
    <div class="relative">
        <!-- Bell Icon Button -->
        <button
            @click="isOpen = !isOpen"
            class="relative p-2 rounded-lg hover:bg-surface-muted dark:hover:bg-surface-dark-muted transition-colors"
        >
            <svg
                class="w-6 h-6 text-text-secondary hover:text-text-primary dark:hover:text-text-inverted"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
            </svg>

            <!-- Unread Badge -->
            <span
                v-if="unreadCount > 0"
                class="absolute top-0 right-0 block h-5 w-5 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center"
            >
                {{ unreadCount > 9 ? "9+" : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Overlay -->
        <div
            v-if="isOpen"
            class="fixed inset-0 z-40"
            @click="isOpen = false"
        ></div>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95 translate-y-2"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-2"
        >
            <div
                v-if="isOpen"
                class="absolute -right-30 bottom-full mb-2 w-80 bg-white dark:bg-surface-dark rounded-lg shadow-lg border border-border-light dark:border-border-dark z-50 max-h-96 overflow-hidden flex flex-col"
                @click.stop
            >
                <!-- Header -->
                <div
                    class="p-4 border-b border-border-light dark:border-border-dark flex items-center justify-between"
                >
                    <h3
                        class="font-semibold text-text-primary dark:text-text-inverted"
                    >
                        Notifications
                    </h3>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllAsRead"
                        class="text-xs text-accent-primary hover:underline"
                    >
                        Mark all as read
                    </button>
                </div>

                <!-- Notifications List -->
                <div class="overflow-y-auto flex-1">
                    <div
                        v-if="isLoading"
                        class="p-4 text-center text-text-secondary"
                    >
                        Loading...
                    </div>

                    <div
                        v-else-if="notifications.length === 0"
                        class="p-8 text-center text-text-secondary"
                    >
                        <svg
                            class="w-12 h-12 mx-auto mb-2 opacity-50"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                            />
                        </svg>
                        <p class="text-sm">No new notifications</p>
                    </div>

                    <div
                        v-else
                        class="divide-y divide-border-light dark:divide-border-dark"
                    >
                        <button
                            v-for="notification in notifications"
                            :key="notification.id"
                            @click="handleNotificationClick(notification)"
                            class="w-full text-left p-4 hover:bg-surface-muted dark:hover:bg-surface-dark-muted transition-colors"
                            :class="{
                                'bg-blue-50 dark:bg-blue-900/20':
                                    !notification.read_at,
                            }"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    v-if="!notification.read_at"
                                    class="mt-1.5 h-2 w-2 rounded-full bg-accent-primary flex-shrink-0"
                                ></div>
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="text-sm text-text-primary dark:text-text-inverted"
                                    >
                                        {{ notification.description }}
                                    </p>
                                    <p class="text-xs text-text-secondary mt-1">
                                        {{
                                            new Date(
                                                notification.created_at
                                            ).toLocaleString()
                                        }}
                                    </p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
