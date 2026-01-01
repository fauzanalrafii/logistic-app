<script setup lang="ts">
import { provide, reactive } from "vue"
import ConfirmDialog from "@/components/ConfirmDialog.vue"

type ConfirmOptions = {
  title?: string
  message?: string
  confirmText?: string
  cancelText?: string
}

type State = ConfirmOptions & {
  show: boolean
  loading: boolean
  resolve?: (val: boolean) => void
}

const state = reactive<State>({
  show: false,
  loading: false,
  title: "",
  message: "",
  confirmText: "",
  cancelText: "",
  resolve: undefined,
})

function confirm(opts: ConfirmOptions): Promise<boolean> {
  state.title = opts.title
  state.message = opts.message
  state.confirmText = opts.confirmText
  state.cancelText = opts.cancelText
  state.show = true
  state.loading = false

  return new Promise((resolve) => {
    state.resolve = resolve
  })
}

function cancel() {
  if (state.loading) return
  state.show = false
  state.resolve?.(false)
  state.resolve = undefined
}

function ok() {
  state.loading = true
  // biar aman dari double click
  setTimeout(() => {
    state.show = false
    state.loading = false
    state.resolve?.(true)
    state.resolve = undefined
  }, 0)
}

provide("confirmDialog", { confirm })
</script>

<template>
  <slot />

  <ConfirmDialog
    :show="state.show"
    :title="state.title"
    :message="state.message"
    :confirmText="state.confirmText"
    :cancelText="state.cancelText"
    :loading="state.loading"
    @cancel="cancel"
    @confirm="ok"
  />
</template>
