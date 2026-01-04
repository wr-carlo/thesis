import "./bootstrap";
import "../css/app.css";

import { createApp, h } from "vue";
import { createInertiaApp, router } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import { initTheme } from "./Stores/useTheme";
import { useLoading } from "./Stores/useLoading";
import LoadingIndicator from "./Components/LoadingIndicator.vue";

const appName = import.meta.env.VITE_APP_NAME || "SkillSight";

// Initialize loading state management
const { start, stop } = useLoading();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        initTheme();

        // router.on("start", () => {
        //     start();
        // });

        // router.on("finish", () => {
        //     stop();
        // });

        // router.on("success", () => {
        //     stop();
        // });

        // router.on("error", () => {
        //     stop();
        // });

        const app = createApp({
            render: () => h("div", [h(App, props), h(LoadingIndicator)]),
        });

        return app.use(plugin).use(ZiggyVue).mount(el);
    },
    progress: {
        color: "#086fff",
        includeCSS: false,
        showSpinner: true,
    },
});
