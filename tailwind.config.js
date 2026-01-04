import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],

    darkMode: "class",

    theme: {
        extend: {
            colors: {
                surface: {
                    DEFAULT: "#ffffff",
                    muted: "#f5f5f5",
                    dark: "#0f172a",
                    "dark-muted": "#111827",
                },
                text: {
                    primary: "#0f172a",
                    secondary: "#475569",
                    inverted: "#e0e0e0",
                },
                border: {
                    light: "#e5e7eb",
                    dark: "#1f2937",
                },
                accent: {
                    primary: "#4f46e5",
                    muted: "#6366f1",
                },
            },
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                card: "0 10px 25px -15px rgba(0,0,0,0.25)",
            },
        },
    },

    plugins: [forms],
};
