<script setup lang="ts">
const props = defineProps<{
  show: boolean
  title?: string
  message?: string
  confirmText?: string
  cancelText?: string
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: "confirm"): void
  (e: "cancel"): void
}>()
</script>

<template>
  <transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95"
  >
    <div v-if="show" class="fixed inset-0 z-[9999]">
      <!-- overlay -->
      <div
        class="absolute inset-0 bg-black/40"
        @click="!loading && emit('cancel')"
      />

      <!-- modal -->
      <div class="absolute inset-0 flex items-center justify-center p-4">
        <div
          class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden"
          role="dialog"
          aria-modal="true"
        >
          <!-- header -->
          <div class="p-7">
            <div class="text-xl font-bold text-slate-900">
              {{ title ?? "Submit Project?" }}
            </div>
            <div class="mt-2 text-[15px] leading-relaxed text-slate-600">
              {{ message ?? "Pastikan data sudah benar. Setelah submit, project akan dibuat di sistem." }}
            </div>
          </div>

          <!-- divider -->
          <div class="h-px bg-slate-100" />

          <!-- footer -->
          <div class="p-6 flex items-center justify-end gap-3">
            <button
              type="button"
              class="px-6 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-semibold
                     hover:bg-slate-200 disabled:opacity-60 disabled:cursor-not-allowed"
              :disabled="loading"
              @click="emit('cancel')"
            >
              {{ cancelText ?? "Batal" }}
            </button>

            <button
              type="button"
              class="px-6 py-2.5 rounded-xl bg-blue-600 text-white font-semibold
                     hover:bg-blue-700 shadow-sm disabled:opacity-60 disabled:cursor-not-allowed"
              :disabled="loading"
              @click="emit('confirm')"
            >
              {{ loading ? "Processing..." : (confirmText ?? "Ya, Submit") }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>
