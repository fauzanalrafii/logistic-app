<script setup>
import { ref } from "vue"
import { router, Head } from "@inertiajs/vue3"
import { ChevronRight, Loader2 } from "lucide-vue-next"
import AppLayout from "@/layouts/AppLayout.vue"
import WorkflowEditor from "@/components/WorkflowEditor.vue"
import ConfirmDialog from "@/components/ConfirmDialog.vue"
import { useToast } from "@/composables/useToast"

const props = defineProps({
  roles: Array,
  currentPage: String,
})

const { success, error } = useToast()

const form = ref({
  name: "",
  steps: [],
})

const errors = ref({})
const processing = ref(false)

// ===============================
// ✅ Modal Submit state
// ===============================
const submitModalOpen = ref(false)

function openSubmitModal() {
  if (processing.value) return

  // validasi ringan sebelum modal muncul
  if (!String(form.value.name || "").trim()) {
    error("Nama workflow wajib diisi.")
    return
  }
  if (!Array.isArray(form.value.steps) || form.value.steps.length === 0) {
    error("Minimal harus ada 1 step approval.")
    return
  }

  submitModalOpen.value = true
}

function closeSubmitModal() {
  if (processing.value) return
  submitModalOpen.value = false
}

function confirmSubmit() {
  if (processing.value) return
  processing.value = true
  errors.value = {}

  router.post("/approval/workflows", form.value, {
    preserveScroll: true,

    onSuccess: () => {
      // ✅ tampilkan snackbar sukses (cuma saat submit)
      success("Workflow berhasil disimpan.")
      submitModalOpen.value = false
    },

    onError: (err) => {
      errors.value = err || {}

      // ambil pesan error yang paling “human”
      const msg =
        err?.error ||
        err?.message ||
        err?.name ||
        "Gagal menyimpan workflow. Coba cek lagi inputnya."
      error(String(msg))

      // modal ditutup biar gak nyangkut
      submitModalOpen.value = false
    },

    onFinish: () => {
      processing.value = false
    },
  })
}
</script>

<template>
  <Head>
    <title>Buat Workflow Baru</title>
  </Head>

  <AppLayout :currentPage="currentPage">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
        <a href="/approval/workflows" class="hover:text-slate-700 transition-colors">
          Workflow
        </a>
        <ChevronRight class="w-4 h-4" />
        <span class="text-slate-700">Buat Baru</span>
      </div>

      <h1 class="text-xl font-bold text-gray-800">Buat Workflow Approval Baru</h1>
      <p class="text-sm text-slate-500 mt-0.5">Definisikan alur approval untuk proses bisnis</p>
    </div>

    <form @submit.prevent="openSubmitModal">
      <!-- Workflow Basic Info -->
      <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
        <h2 class="text-sm font-semibold text-slate-700 mb-4">Informasi Workflow</h2>

        <div class="grid grid-cols-1 gap-4">
          <div>
            <label class="label">
              Nama Workflow <span class="text-red-500">*</span>
            </label>

            <input
              v-model="form.name"
              type="text"
              required
              placeholder="Contoh: Plan On Desk Planning"
              class="input w-full"
              :class="{ 'border-red-500': errors?.name }"
            />

            <p v-if="errors?.name" class="mt-1 text-xs text-red-600">
              {{ errors.name }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
              Process type akan di-generate otomatis dari nama
            </p>
          </div>
        </div>

        <!-- Info -->
        <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-lg">
          <p class="text-sm text-blue-700">
            <strong>Info:</strong> Status akan otomatis menjadi "Aktif" saat ada project yang sedang menjalani approval di flow ini.
          </p>
        </div>
      </div>

      <!-- Workflow Steps - Graph Editor -->
      <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
        <div class="mb-4">
          <h2 class="text-sm font-semibold text-slate-700">Step Approval</h2>
          <p class="text-xs text-slate-500 mt-0.5">
            Drag node untuk mengubah urutan, klik node untuk edit
          </p>
        </div>

        <WorkflowEditor v-model="form.steps" :roles="roles" />

        <p v-if="form.steps.length === 0" class="text-center text-slate-500 text-sm mt-4">
          Klik tombol "Tambah Step" di atas untuk memulai
        </p>
      </div>

      <!-- Form Actions -->
      <div class="flex items-center justify-end gap-3">
        <a
          href="/approval/workflows"
          class="px-4 py-2 border border-gray-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors"
        >
          Batal
        </a>

        <!-- ✅ tombol submit hanya buka modal -->
        <button
          type="submit"
          :disabled="processing || form.steps.length === 0"
          class="btn-primary"
        >
          <Loader2 v-if="processing" class="animate-spin w-4 h-4" />
          {{ processing ? "Menyimpan..." : "Simpan Workflow" }}
        </button>
      </div>
    </form>

    <!-- ✅ Modal Konfirmasi Submit -->
    <ConfirmDialog
      :show="submitModalOpen"
      title="Simpan Workflow?"
      message="Pastikan alur approval sudah benar. Setelah disimpan, workflow akan tersedia untuk digunakan."
      confirmText="Ya, Simpan"
      cancelText="Batal"
      :loading="processing"
      @cancel="closeSubmitModal"
      @confirm="confirmSubmit"
    />
  </AppLayout>
</template>

<style scoped>
.label {
  @apply text-sm font-medium text-slate-700 mb-1 block;
}
.input {
  @apply px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}
.btn-primary {
  @apply flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-lg transition-colors;
}
</style>
