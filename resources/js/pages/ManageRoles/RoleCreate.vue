<script setup lang="ts">
import { Head, useForm, router } from "@inertiajs/vue3"
import AppLayout from "@/layouts/AppLayout.vue"
import ConfirmDialog from "@/components/ConfirmDialog.vue"
import { ref, watch } from "vue"
import { useToast } from "@/composables/useToast"

const props = defineProps<{
  permissions: any[]
}>()

const { success, error } = useToast()

const form = useForm({
  name: "",
  permissions: [] as string[],
})

const allPermissions = ref(props.permissions || [])
const loading = ref(false)

// select all
const selectAllToggle = ref(false)
const selectAll = () => {
  form.permissions = allPermissions.value.map((p: any) => p.name)
}
const deselectAll = () => {
  form.permissions = []
}
watch(selectAllToggle, (value) => {
  value ? selectAll() : deselectAll()
})

function togglePermission(permissionName: string) {
  const idx = form.permissions.indexOf(permissionName)
  if (idx > -1) form.permissions.splice(idx, 1)
  else form.permissions.push(permissionName)
}

// ===============================
// ✅ Modal Submit state
// ===============================
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
  form.post("/roles", {
    preserveScroll: true,
    onSuccess: () => {
      success(`Role "${form.name}" berhasil dibuat.`)
      closeSubmitModal(true)
    },
    onError: (errs: any) => {
      // tampilkan error singkat (validasi tetap muncul di form)
      const msg = errs?.error || "Gagal membuat role. Cek input."
      error(String(msg))
    },
    onFinish: () => {
      loading.value = false
    },
  })
}
</script>

<template>
  <Head title="Create Role" />

  <AppLayout title="Manage Roles" currentPage="roles.index">
    <div class="space-y-6">
      <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Create New Role</h2>

        <form @submit.prevent="openSubmitModal" class="space-y-6">
          <!-- Role Name -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              Role Name
            </label>
            <input
              v-model="form.name"
              type="text"
              placeholder="e.g., Administrator"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
              required
            />
            <span v-if="form.errors.name" class="text-sm text-red-600 mt-1">
              {{ form.errors.name }}
            </span>
          </div>

          <!-- Permissions Section -->
          <div>
            <div class="flex justify-between items-center mb-4">
              <label class="block text-sm font-semibold text-gray-700">
                Permissions
              </label>
              <div class="flex items-center gap-2">
                <input
                  type="checkbox"
                  id="select-all-toggle"
                  v-model="selectAllToggle"
                  class="w-4 h-4 text-blue-600 rounded focus:ring-2"
                />
                <label for="select-all-toggle" class="text-sm text-gray-700 cursor-pointer">
                  Select All Permissions
                </label>
              </div>
            </div>

            <div
              v-if="allPermissions.length > 0"
              class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4 border border-gray-200 rounded-lg bg-gray-50"
            >
              <div v-for="permission in allPermissions" :key="permission.id" class="flex items-center">
                <input
                  type="checkbox"
                  :id="`perm-${permission.id}`"
                  :checked="form.permissions.includes(permission.name)"
                  @change="togglePermission(permission.name)"
                  class="w-4 h-4 text-blue-600 rounded focus:ring-2"
                />
                <label :for="`perm-${permission.id}`" class="ml-2 text-sm text-gray-700 cursor-pointer">
                  {{ permission.name }}
                </label>
              </div>
            </div>

            <span v-if="form.errors.permissions" class="text-sm text-red-600 mt-2">
              {{ form.errors.permissions }}
            </span>
          </div>

          <!-- Actions -->
          <div class="flex gap-3 justify-end">
            <a href="/roles" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
              Cancel
            </a>
            <button
              type="submit"
              :disabled="form.processing || loading"
              class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition disabled:opacity-50"
            >
              {{ loading ? "Creating..." : "Create Role" }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ✅ Modal Konfirmasi Submit -->
    <ConfirmDialog
      :show="submitModalOpen"
      title="Simpan Role?"
      message="Pastikan data role dan permission sudah benar. Setelah disimpan, role akan tersedia di sistem."
      confirmText="Ya, Simpan"
      cancelText="Batal"
      :loading="form.processing || loading"
      @cancel="closeSubmitModal()"
      @confirm="confirmSubmit"
    />
  </AppLayout>
</template>
