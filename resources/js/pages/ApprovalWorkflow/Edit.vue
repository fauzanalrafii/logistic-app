<script setup>
import { ref } from "vue"
import { router, Head } from "@inertiajs/vue3"
import { ChevronRight, Loader2 } from "lucide-vue-next"
import AppLayout from "@/layouts/AppLayout.vue"
import WorkflowEditor from "@/components/WorkflowEditor.vue"
import ConfirmDialog from "@/components/ConfirmDialog.vue"
import { useToast } from "@/composables/useToast"

const props = defineProps({
  workflow: Object,
  steps: Array,
  roles: Array,
  currentPage: String,
})

const { success, error } = useToast()

const form = ref({
  name: props.workflow?.name ?? "",
  steps: (props.steps ?? []).map((step) => ({
    id: step.id,
    name: step.name,
    step_order: step.step_order,
    required_role_id: step.required_role_id,
    sla_hours: step.sla_hours,
  })),
})

const errors = ref({})
const processing = ref(false)
const workflowEditorRef = ref(null)

// ===============================
// ✅ Modal Update state
// ===============================
const updateModalOpen = ref(false)

function openUpdateModal() {
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

  updateModalOpen.value = true
}

function closeUpdateModal() {
  if (processing.value) return
  updateModalOpen.value = false
}

function confirmUpdate() {
  if (processing.value) return

  processing.value = true
  errors.value = {}

  router.put(`/approval/workflows/${props.workflow.id}`, form.value, {
    preserveScroll: true,

    onSuccess: () => {
      success("Workflow berhasil diupdate.")
      updateModalOpen.value = false
    },

    onError: (err) => {
      errors.value = err || {}

      const msg =
        err?.error ||
        err?.message ||
        err?.name ||
        "Gagal update workflow. Coba cek lagi inputnya."
      error(String(msg))

      updateModalOpen.value = false
    },

    onFinish: () => {
      processing.value = false
    },
  })
}
</script>

<template>
  <Head>
    <title>Edit Workflow - {{ workflow?.name }}</title>
  </Head>

  <AppLayout :currentPage="currentPage">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
        <a href="/approval/workflows" class="hover:text-slate-700 transition-colors">
          Workflow
        </a>
        <ChevronRight class="w-4 h-4" />
        <span class="text-slate-700">Edit</span>
      </div>

      <h1 class="text-xl font-bold text-gray-800">Edit Workflow Approval</h1>
      <p class="text-sm text-slate-500 mt-0.5">{{ workflow?.name }}</p>
    </div>

    <!-- ✅ tombol Update hanya buka modal -->
    <form @submit.prevent="openUpdateModal">
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
          </div>
        </div>

        <!-- Info -->
        <div class="mt-4 p-3 bg-slate-50 border border-slate-100 rounded-lg">
          <p class="text-sm text-slate-600">
            <strong>Process Type:</strong>
            <code class="bg-slate-200 px-1.5 py-0.5 rounded text-xs">
              {{ workflow?.process_type }}
            </code>
          </p>

          <p class="text-sm text-blue-600 mt-2">
            Status akan otomatis menjadi "Aktif" saat ada project yang sedang menjalani approval di flow ini.
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

        <WorkflowEditor v-model="form.steps" :roles="roles" ref="workflowEditorRef" />

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

        <button
          type="submit"
          :disabled="processing || form.steps.length === 0"
          class="btn-primary"
        >
          <Loader2 v-if="processing" class="animate-spin w-4 h-4" />
          {{ processing ? "Menyimpan..." : "Update Workflow" }}
        </button>
      </div>
    </form>

    <!-- ✅ Modal Konfirmasi Update -->
    <ConfirmDialog
      :show="updateModalOpen"
      title="Update Workflow?"
      message="Pastikan perubahan step sudah benar. Setelah diupdate, alur approval akan mengikuti workflow terbaru."
      confirmText="Ya, Update"
      cancelText="Batal"
      :loading="processing"
      @cancel="closeUpdateModal"
      @confirm="confirmUpdate"
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
