<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue"
import { Head, useForm, Link } from "@inertiajs/vue3"
import ConfirmDialog from "@/components/ConfirmDialog.vue"
import { ref, computed, watch } from "vue"
import { useToast } from "@/composables/useToast"

const { success, error } = useToast()

const props = defineProps<{
  user: any
  permissions: Array<{ id: number; name: string }>
  assigned: number[]
}>()

const form = useForm<{ permissions: number[] }>({
  permissions: props.assigned ?? [],
})

// SELECT ALL
const selectAllPermissions = ref(false)
const allPermissionIds = computed(() => props.permissions.map((p) => p.id))

watch(selectAllPermissions, (val) => {
  form.permissions = val ? [...allPermissionIds.value] : []
})

watch(
  () => form.permissions,
  (val) => {
    selectAllPermissions.value = val.length === allPermissionIds.value.length
  },
  { deep: true }
)

// ✅ Modal Update
const submitModalOpen = ref(false)
const loading = ref(false)

function openSubmitModal() {
  if (!form.permissions.length) return error("Pilih minimal 1 permission.")
  submitModalOpen.value = true
}

function closeSubmitModal(force = false) {
  if ((form.processing || loading.value) && !force) return
  submitModalOpen.value = false
}

function confirmUpdate() {
  loading.value = true

  // NOTE: di code kamu pakai props.user.ID
  const userId = props.user?.ID ?? props.user?.id

  form.put(`/assign-permissions/${userId}`, {
    preserveScroll: true,
    onSuccess: () => {
      success("Assign permission berhasil diupdate.")
      closeSubmitModal(true)
    },
    onError: (errs: any) => {
      const msg = errs?.error || "Gagal update assign permission."
      error(String(msg))
    },
    onFinish: () => {
      loading.value = false
    },
  })
}
</script>

<template>
  <Head title="Edit Assign Permission" />

  <AppLayout title="Manage Permission" currentPage="assign-permissions.index">
    <div class="space-y-6">
      <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
        <h2 class="text-2xl font-bold text-gray-900">Edit Assign Permission</h2>
      </div>

      <div class="bg-white rounded-lg shadow p-6 space-y-6 border border-gray-200">
        <!-- USER INFO -->
        <div class="bg-gray-50 p-4 border rounded-lg">
          <p class="text-lg font-semibold text-gray-900">
            {{ props.user?.UserID ?? props.user?.name ?? "-" }}
          </p>
          <p class="text-sm text-gray-600 mt-1">
            User ID:
            <span class="font-medium">{{ props.user?.ID ?? props.user?.id ?? "-" }}</span>
          </p>
        </div>

        <!-- PERMISSIONS -->
        <div>
          <div class="flex justify-between items-center mb-4">
            <label class="block text-sm font-semibold text-gray-700">Permissions</label>

            <div class="flex items-center gap-2">
              <input
                type="checkbox"
                id="select-all-permissions"
                v-model="selectAllPermissions"
                class="w-4 h-4 text-blue-600 rounded focus:ring-2"
              />
              <label for="select-all-permissions" class="text-sm text-gray-700 cursor-pointer">
                Select All Permissions
              </label>
            </div>
          </div>

          <div
            v-if="props.permissions.length > 0"
            class="grid grid-flow-col grid-rows-6 gap-4 p-4 border border-gray-300 rounded-lg bg-gray-50"
          >
            <div v-for="permission in props.permissions" :key="permission.id" class="flex items-center">
              <input
                type="checkbox"
                :id="`permission-${permission.id}`"
                :value="permission.id"
                v-model="form.permissions"
                class="w-4 h-4 text-blue-600 rounded focus:ring-2"
              />
              <label :for="`permission-${permission.id}`" class="ml-2 text-sm text-gray-700 cursor-pointer">
                {{ permission.name }}
              </label>
            </div>
          </div>

          <span v-if="form.errors.permissions" class="text-sm text-red-600 mt-2 block">
            {{ form.errors.permissions }}
          </span>
        </div>

        <!-- ACTION BUTTON -->
        <div class="flex gap-3 justify-end">
          <Link
            href="/assign-permissions"
            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
          >
            Cancel
          </Link>

          <button
            type="button"
            @click="openSubmitModal"
            :disabled="form.processing || loading"
            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition disabled:opacity-50"
          >
            {{ loading ? "Saving..." : "Update" }}
          </button>
        </div>
      </div>
    </div>

    <ConfirmDialog
      :show="submitModalOpen"
      title="Update Assign Permission?"
      :message="`Apakah Anda yakin ingin mengupdate permission untuk user &quot;${props.user?.UserID ?? props.user?.name ?? ''}&quot;?`"
      confirmText="Ya, Update"
      cancelText="Batal"
      :loading="form.processing || loading"
      @cancel="closeSubmitModal()"
      @confirm="confirmUpdate"
    />
  </AppLayout>
</template>
