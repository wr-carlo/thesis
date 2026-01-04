import { reactive } from "vue";

const state = reactive({
    theme: "light",
});

const storageKey = "admin-theme";

export function initTheme() {
    const stored = localStorage.getItem(storageKey);
    const prefersDark =
        window.matchMedia &&
        window.matchMedia("(prefers-color-scheme: dark)").matches;
    state.theme = stored || (prefersDark ? "dark" : "light");
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
