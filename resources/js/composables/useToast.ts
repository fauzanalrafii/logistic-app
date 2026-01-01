import { reactive, readonly } from "vue"

type ToastType = "success" | "error" | "info"

const state = reactive({
  show: false,
  message: "",
  type: "info" as ToastType,
  duration: 3500,
})

function open(message: string, type: ToastType = "info", duration = 3500) {
  state.message = message
  state.type = type
  state.duration = duration
  state.show = true
}

function close() {
  state.show = false
}

export function useToast() {
  return {
    toast: readonly(state),
    open,
    close,
    success: (msg: string, duration?: number) => open(msg, "success", duration ?? 3500),
    error: (msg: string, duration?: number) => open(msg, "error", duration ?? 4000),
    info: (msg: string, duration?: number) => open(msg, "info", duration ?? 3500),
  }
}
