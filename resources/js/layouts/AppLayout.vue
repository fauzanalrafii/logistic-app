<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from "vue"
import { usePage } from "@inertiajs/vue3"

import Sidebar from "@/components/Sidebar.vue"
import Navbar from "@/components/Navbar.vue"
import CommandPalette from "@/components/CommandPalette.vue"

import Snackbar from "@/components/Snackbar.vue"
import ConfirmDialogProvider from "@/components/ConfirmDialogProvider.vue"

import { useToast } from "@/composables/useToast"
import { useDarkMode } from "@/composables/useDarkMode"

const props = defineProps({
  title: { type: String, default: "Velox Logistics" },
  currentPage: { type: String, default: "" },
})

// Dark Mode
const { isDark } = useDarkMode()

const sidebarOpen = ref(false)
const page = usePage()

// toast global
const { toast, success, error, close } = useToast()

const flashSuccess = computed(() => (page.props.flash as any)?.success ?? null)
const flashError = computed(() => (page.props.flash as any)?.error ?? null)

watch(
  () => [flashSuccess.value, flashError.value],
  ([s, e]) => {
    if (s) success(String(s))
    else if (e) error(String(e))
  },
  { immediate: true }
)

// Command Palette
const isCommandPaletteOpen = ref(false)

function handleGlobalKeydown(e: KeyboardEvent) {
  // Ctrl+K or Cmd+K
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault()
    isCommandPaletteOpen.value = true
  }
  // '/' when not in input
  if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes((e.target as HTMLElement)?.tagName)) {
    e.preventDefault()
    isCommandPaletteOpen.value = true
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleGlobalKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleGlobalKeydown)
})
</script>

<template>
  <ConfirmDialogProvider>
    <div :class="['flex h-screen font-inter overflow-hidden transition-colors duration-300', isDark ? 'bg-gradient-to-b from-[#0f172a] via-[#1e293b] to-[#0f172a]' : 'bg-gradient-to-b from-[#0f172a] via-[#1e293b] to-[#0f172a]']">
      <Sidebar
        :isOpen="sidebarOpen"
        :currentPage="currentPage"
        @toggleSidebar="sidebarOpen = !sidebarOpen"
      />

      <div :class="[
        'flex-1 flex flex-col overflow-hidden relative transition-all duration-300',
        'm-3 ml-0 rounded-3xl shadow-2xl border', // INSET PANEL STYLING
        isDark ? 'bg-slate-900 border-slate-700/50' : 'bg-slate-50 border-white/20'
      ]">
        <Navbar :title="title" :isDark="isDark" @toggleSidebar="sidebarOpen = !sidebarOpen" @openCommandPalette="isCommandPaletteOpen = true" />

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-8 pt-28 lg:pt-32 transition-colors duration-300">
          <slot />
        </main>

        <!-- GLOBAL SNACKBAR -->
        <Snackbar
          :show="toast.show"
          :message="toast.message"
          :type="toast.type"
          :duration="toast.duration"
          @close="close"
        />
      </div>
    </div>

    <!-- Command Palette -->
    <CommandPalette 
      :isOpen="isCommandPaletteOpen" 
      @close="isCommandPaletteOpen = false" 
    />
  </ConfirmDialogProvider>
</template>

<style>
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");
.font-inter {
  font-family: "Inter", sans-serif;
}
</style>
