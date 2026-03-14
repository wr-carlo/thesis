import { defineStore } from "pinia";

const STORAGE_KEY_PREFIX = "lesson_review_";

export const useLessonReviewStore = defineStore("lessonReview", {
    state: () => ({
        reviews: {},
    }),

    actions: {
        save(token, data) {
            this.reviews[token] = data;
            try {
                localStorage.setItem(
                    STORAGE_KEY_PREFIX + token,
                    JSON.stringify(data)
                );
            } catch (e) {
                console.warn("Failed to persist lesson review to localStorage:", e);
            }
        },

        load(token) {
            if (this.reviews[token]) {
                return this.reviews[token];
            }
            try {
                const stored = localStorage.getItem(STORAGE_KEY_PREFIX + token);
                if (stored) {
                    const data = JSON.parse(stored);
                    this.reviews[token] = data;
                    return data;
                }
            } catch (e) {
                console.warn("Failed to load lesson review from localStorage:", e);
            }
            return null;
        },

        remove(token) {
            delete this.reviews[token];
            try {
                localStorage.removeItem(STORAGE_KEY_PREFIX + token);
            } catch (e) {
                console.warn("Failed to remove lesson review from localStorage:", e);
            }
        },
    },
});
