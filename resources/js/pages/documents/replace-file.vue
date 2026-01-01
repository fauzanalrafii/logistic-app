<script setup lang="ts">
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { computed, reactive, ref, onMounted, onBeforeUnmount } from 'vue'

import ConfirmDialog from '@/components/ConfirmDialog.vue'
import Snackbar from '@/components/Snackbar.vue'

const page = usePage<any>()

const props = defineProps<{
  document: {
    id: number
    document_type: string
    file_name: string
  }
}>()

const form = useForm<{
  _method: 'put'
  file: File | null
}>({
  _method: 'put',
  file: null,
})

const fileName = computed(() => form.file?.name ?? '')
const canSubmit = computed(() => !!form.file && !form.processing)

/* ================== SNACKBAR (AUTO CLOSE) ================== */
const snack = reactive({
  show: false,
  message: '',
  type: 'info' as 'success' | 'error' | 'info',
})

let snackTimer: number | null = null

function closeSnack() {
  snack.show = false
  if (snackTimer) {
    window.clearTimeout(snackTimer)
    snackTimer = null
  }
}

function showSnack(message: string, type: 'success' | 'error' | 'info' = 'info', autoMs = 3000) {
  closeSnack()
  snack.show = true
  snack.message = message
  snack.type = type

  snackTimer = window.setTimeout(() => {
    snack.show = false
    snackTimer = null
  }, autoMs)
}

onBeforeUnmount(() => closeSnack())

/* ================== MODAL ================== */
const confirmOpen = ref(false)

function openConfirm() {
  if (form.processing) return

  if (!form.file) {
    showSnack('Pilih file baru dulu sebelum lanjut.', 'error')
    return
  }

  confirmOpen.value = true
}

function closeConfirm() {
  if (form.processing) return
  confirmOpen.value = false
}

function doSubmit() {
  if (!form.file) return

  form.post(`/documents/${props.document.id}/replace-file`, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      showSnack('File dokumen berhasil diganti.', 'success')
      router.visit('/documents')
    },
    onError: (errors) => {
      const msg =
        errors && Object.keys(errors).length
          ? Object.values(errors).flat().join(', ')
          : 'Gagal mengganti file dokumen.'
      showSnack(String(msg), 'error', 4500)
    },
    onFinish: () => {
      confirmOpen.value = false
    },
  })
}

onMounted(() => {
  const msg = page?.props?.flash?.success
  if (msg) showSnack(String(msg), 'success')
})
</script>

<template>
  <AppLayout title="Manajemen Dokumen" currentPage="documents">
    <Head title="Ganti File Dokumen" />

    <div class="max-w-3xl mx-auto space-y-6 px-4 lg:px-0">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Ganti File Dokumen</h1>
          <p class="text-sm text-slate-500">
            Kamu hanya mengganti file. Jenis dokumen dan data lainnya tetap.
          </p>
        </div>

        <Link
          href="/documents"
          class="px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 hover:bg-slate-50"
        >
          Kembali
        </Link>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5">
        <div class="rounded-lg bg-slate-50 border border-slate-200 p-4">
          <div class="text-sm font-semibold text-slate-800">Info Dokumen</div>
          <div class="text-sm text-slate-600 mt-1">
            <div><b>Jenis:</b> {{ props.document.document_type }}</div>
            <div class="mt-1"><b>File saat ini:</b> {{ props.document.file_name }}</div>
          </div>

          <div class="mt-2">
            <a class="text-blue-600 text-sm hover:underline" :href="`/documents/${props.document.id}/download`">
              Download file saat ini
            </a>
          </div>
        </div>

        <div>
          <label class="text-sm font-semibold mb-1 block">
            File baru <span class="text-red-500">*</span>
          </label>

          <input
            type="file"
            class="input w-full h-10"
            @change="(e:any) => (form.file = e.target.files?.[0] ?? null)"
          />

          <div v-if="fileName" class="text-xs text-slate-600 mt-2">
            File dipilih: <b>{{ fileName }}</b>
          </div>

          <div v-if="form.errors.file" class="text-xs text-red-600 mt-1">
            {{ form.errors.file }}
          </div>

          <div class="text-xs text-slate-500 mt-2">
            File lama akan otomatis terhapus setelah diganti.
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link
            href="/documents"
            class="px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 hover:bg-slate-50"
          >
            Batal
          </Link>

          <button
            type="button"
            class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-60"
            :disabled="!canSubmit"
            @click="openConfirm"
          >
            {{ form.processing ? 'Menyimpan...' : 'Ganti File' }}
          </button>
        </div>
      </div>
    </div>

    <ConfirmDialog
      :show="confirmOpen"
      title="Ganti File Dokumen?"
      :message="`File lama '${props.document.file_name}' akan diganti dan kemungkinan terhapus dari storage.`"
      confirmText="Ya, Ganti"
      cancelText="Batal"
      :loading="form.processing"
      @cancel="closeConfirm"
      @confirm="doSubmit"
    />

    <Snackbar
      :show="snack.show"
      :message="snack.message"
      :type="snack.type"
      @close="closeSnack"
    />
  </AppLayout>
</template>

<style scoped>
.input {
  @apply border border-gray-300 rounded-md shadow-sm px-3 py-2
         focus:ring-blue-500 focus:border-blue-500;
}
</style>
