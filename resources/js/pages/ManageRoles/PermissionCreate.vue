<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3"
import AppLayout from "@/layouts/AppLayout.vue"
import ConfirmDialog from "@/components/ConfirmDialog.vue"
import { ref } from "vue"
import { useToast } from "@/composables/useToast"

const { success, error } = useToast()

const form = useForm({
  name: "",
})

const loading = ref(false)

// ✅ modal submit
const submitModalOpen = ref(false)

function openSubmitModal() {
  submitModalOpen.value = true
}

function closeSubmitModal(force = false) {
  if ((form.processing || loading.value) && !force) return
  submitModalOpen.value = false
}

function confirmSubmit() {
  loading.value = true

  form.post("/permissions", {
    preserveScroll: true,
    onSuccess: () => {
      success(`Permission "${form.name}" berhasil dibuat.`)
      closeSubmitModal(true)
    },
    onError: (errs: any) => {
      const msg = errs?.error || "Gagal membuat permission. Cek input."
      error(String(msg))
    },
    onFinish: () => {
      loading.value = false
    },
  })
}
</script>

<template>
  <Head title="Create Permission" />

  <AppLayout title="Manage Roles" currentPage="permissions.index">
    <div class="space-y-6">
      <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">
          Create New Permission
        </h2>

        <form @submit.prevent="openSubmitModal" class="space-y-6">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              Permission Name
            </label>
            <input
              v-model="form.name"
              type="text"
              placeholder="e.g., user-create"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
              required
            />
            <span v-if="form.errors.name" class="text-sm text-red-600 mt-1">
              {{ form.errors.name }}
            </span>
          </div>

          <div class="flex gap-3 justify-end">
            <a
              href="/permissions"
              class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
            >
              Cancel
            </a>
            <button
              type="submit"
              :disabled="form.processing || loading"
              class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition disabled:opacity-50"
            >
              {{ loading ? "Creating..." : "Create Permission" }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ✅ Modal Konfirmasi Submit -->
    <ConfirmDialog
      :show="submitModalOpen"
      title="Simpan Permission?"
      :message="`Apakah Anda yakin ingin menyimpan permission &quot;${form.name}&quot;?`"
      confirmText="Ya, Simpan"
      cancelText="Batal"
      :loading="form.processing || loading"
      @cancel="closeSubmitModal()"
      @confirm="confirmSubmit"
    />
  </AppLayout>
</template>
