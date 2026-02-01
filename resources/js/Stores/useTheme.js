import { reactive } from "vue";

const state = reactive({
    theme: "light",
});

const storageKey = "admin-theme";

export function initTheme() {
    const stored = localStorage.getItem(storageKey);
    // Default to light mode, only use dark if explicitly saved in localStorage
    state.theme = stored || "light";
    applyTheme();
}

export function toggleTheme() {
    state.theme = state.theme === "dark" ? "light" : "dark";
    localStorage.setItem(storageKey, state.theme);
    applyTheme();
}

export function useTheme() {
    return state;
}

function applyTheme() {
    const root = document.documentElement;
    if (state.theme === "dark") {
        root.classList.add("dark");
    } else {
        root.classList.remove("dark");
    }
}
