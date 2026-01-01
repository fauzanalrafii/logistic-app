import { inject } from "vue"

type ConfirmOptions = {
  title?: string
  message?: string
  confirmText?: string
  cancelText?: string
}

export function useConfirmDialog() {
  const api = inject<{ confirm: (opts: ConfirmOptions) => Promise<boolean> }>(
    "confirmDialog",
    null as any
  )

  // ✅ kalau provider belum ada, fallback ke native confirm (biar gak blank)
  const confirm = (opts: ConfirmOptions) => {
    if (api?.confirm) return api.confirm(opts)

    const title = opts.title ?? "Konfirmasi"
    const message = opts.message ?? "Yakin ingin melanjutkan?"
    return Promise.resolve(window.confirm(`${title}\n\n${message}`))
  }

  return { confirm }
}
