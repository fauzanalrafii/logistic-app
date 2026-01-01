import { ref, watch, onMounted } from 'vue'

const STORAGE_KEY = 'dashboard-dark-mode'

// Global state (shared across components)
const isDark = ref(false)

export function useDarkMode() {
    onMounted(() => {
        // Load from localStorage on first mount
        const saved = localStorage.getItem(STORAGE_KEY)
        if (saved !== null) {
            isDark.value = saved === 'true'
        }
    })

    // Persist to localStorage whenever it changes
    watch(isDark, (val) => {
        localStorage.setItem(STORAGE_KEY, String(val))
    })

    function toggle() {
        isDark.value = !isDark.value
    }

    return {
        isDark,
        toggle,
    }
}
