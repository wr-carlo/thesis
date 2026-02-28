import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

const withOpacity = (variableName) => {
    return `rgb(var(${variableName}) / <alpha-value>)`;
};

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
                    DEFAULT: withOpacity("--color-surface"),
                    muted: withOpacity("--color-surface-muted"),
                    dark: withOpacity("--color-surface-dark"),
                    "dark-muted": withOpacity("--color-surface-dark-muted"),
                },
                text: {
                    primary: withOpacity("--color-text-primary"),
                    secondary: withOpacity("--color-text-secondary"),
                    inverted: withOpacity("--color-text-inverted"),
                },
                border: {
                    light: withOpacity("--color-border-light"),
                    dark: withOpacity("--color-border-dark"),
                },
                accent: {
                    primary: withOpacity("--color-accent-primary"),
                    muted: withOpacity("--color-accent-muted"),
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
