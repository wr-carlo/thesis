import { ref } from "vue";

const isLoading = ref(false);

export function useLoading() {
    const setLoading = (value) => {
        isLoading.value = value;
    };

    const start = () => {
        isLoading.value = true;
    };

    const stop = () => {
        isLoading.value = false;
    };

    return {
        isLoading,
        setLoading,
        start,
        stop,
    };
}
