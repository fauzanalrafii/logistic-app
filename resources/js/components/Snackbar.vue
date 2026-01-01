<script setup lang="ts">
import { watch, ref, computed } from "vue"
import { CheckCircle2, AlertTriangle, Info, X } from "lucide-vue-next"

const props = defineProps<{
  show: boolean
  message: string
  type?: "success" | "error" | "info"
  duration?: number
}>()

const emit = defineEmits(["close"])
const timer = ref<number | null>(null)

const kind = computed(() => props.type ?? "info")

watch(
  () => props.show,
  (val) => {
    if (!val) return
    if (timer.value) window.clearTimeout(timer.value)
    timer.value = window.setTimeout(() => emit("close"), props.duration ?? 3500)
  }
)

function containerClass() {
  // background + border yang lebih soft (biar kayak notif beneran)
  if (kind.value === "success") return "bg-emerald-50 border-emerald-200"
  if (kind.value === "error") return "bg-rose-50 border-rose-200"
  return "bg-slate-50 border-slate-200"
}

function iconWrapClass() {
  if (kind.value === "success") return "text-emerald-700 border-emerald-200"
  if (kind.value === "error") return "text-rose-700 border-rose-200"
  return "text-slate-700 border-slate-200"
}

function titleText() {
  if (kind.value === "success") return "Berhasil"
  if (kind.value === "error") return "Gagal"
  return "Info"
}
</script>

<template>
  <!-- ✅ PINDAH KE KANAN ATAS -->
  <div class="fixed top-5 right-5 z-[9999] pointer-events-none">
    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 -translate-y-2 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 -translate-y-2 scale-95"
    >
      <div v-if="show && message" class="pointer-events-auto w-[360px] sm:w-[420px]">
        <div
          class="rounded-2xl shadow-2xl border p-4 flex items-start gap-3"
          :class="containerClass()"
          role="status"
        >
          <!-- icon -->
          <div
            class="w-10 h-10 rounded-xl bg-white border flex items-center justify-center shrink-0"
            :class="iconWrapClass()"
          >
            <CheckCircle2 v-if="kind === 'success'" class="w-5 h-5" />
            <AlertTriangle v-else-if="kind === 'error'" class="w-5 h-5" />
            <Info v-else class="w-5 h-5" />
          </div>

          <!-- content -->
          <div class="min-w-0 flex-1">
            <div class="text-sm font-extrabold text-slate-900">
              {{ titleText() }}
            </div>
            <div class="text-sm text-slate-700 mt-0.5 break-words">
              {{ message }}
            </div>
          </div>

          <!-- close -->
          <button
            type="button"
            class="shrink-0 p-1 rounded-lg hover:bg-black/5 text-slate-600"
            @click="$emit('close')"
            aria-label="Close"
          >
            <X class="w-4 h-4" />
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>
