<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3"
import AppLayout from "@/layouts/AppLayout.vue"
import ConfirmDialog from "@/components/ConfirmDialog.vue"
import { ref, computed, watch } from "vue"
import { useToast } from "@/composables/useToast"

const { success, error } = useToast()

const props = defineProps<{
  user: any
  roles: any[]
  assigned: number[]
}>()

const form = useForm<{ roles: number[] }>({
  roles: props.assigned,
})

const loading = ref(false)

// Checkbox "Select All"
const selectAllRoles = ref(false)
const allRoleIds = computed(() => props.roles.map((r) => r.id))

watch(selectAllRoles, (val) => {
  form.roles = val ? [...allRoleIds.value] : []
})

watch(
  () => form.roles,
  (val) => {
    selectAllRoles.value = val.length === allRoleIds.value.length
  },
  { deep: true }
)

// ✅ modal update
const submitModalOpen = ref(false)

function openSubmitModal() {
  if (!form.roles.length) {
    error("Pilih minimal 1 role.")
    return
  }
  submitModalOpen.value = true
}

function closeSubmitModal(force = false) {
  if ((form.processing || loading.value) && !force) return
  submitModalOpen.value = false
}

function confirmUpdate() {
  loading.value = true

  form.put(`/assign-roles/${props.user.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      success("Assign role berhasil diupdate.")
      closeSubmitModal(true)
    },
    onError: (errs: any) => {
      const msg = errs?.error || "Gagal update assign role."
      error(String(msg))
    },
    onFinish: () => {
      loading.value = false
    },
  })
}
</script>

<template>
  <Head title="Edit Assign Role" />

  <AppLayout title="Manage Role" currentPage="assign-roles.index">
    <div class="space-y-6">
      <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
        <h2 class="text-2xl font-bold text-gray-900">Edit Assign Role</h2>
      </div>

      <div class="bg-white rounded-lg shadow p-6 space-y-6 border border-gray-200">
        <!-- User Information -->
        <div class="bg-gray-50 p-4 border rounded-lg">
          <p class="text-lg font-semibold text-gray-900">
            {{ props.user.name }}
          </p>

          <p class="text-sm text-gray-600 mt-1">
            User ID: <span class="font-medium">{{ props.user.id }}</span>
          </p>
        </div>

        <!-- Roles Section -->
        <div>
          <div class="flex justify-between items-center mb-4">
            <label class="block text-sm font-semibold text-gray-700">
              Roles
            </label>

            <div class="flex items-center gap-2">
              <input
                type="checkbox"
                id="select-all-roles"
                v-model="selectAllRoles"
                class="w-4 h-4 text-[#0BA64A] rounded focus:ring-2"
              />
              <label for="select-all-roles" class="text-sm text-gray-700 cursor-pointer">
                Select All Roles
              </label>
            </div>
          </div>

          <div
            v-if="props.roles.length > 0"
            class="grid grid-flow-col grid-rows-5 gap-4 p-4 border border-gray-300 rounded-lg bg-gray-50"
          >
            <div v-for="role in props.roles" :key="role.id" class="flex items-center">
              <input
                type="checkbox"
                :id="`role-${role.id}`"
                :value="role.id"
                v-model="form.roles"
                class="w-4 h-4 text-[#0BA64A] rounded focus:ring-2"
              />
              <label :for="`role-${role.id}`" class="ml-2 text-sm text-gray-700 cursor-pointer">
                {{ role.name }}
              </label>
            </div>
          </div>

          <span v-if="form.errors.roles" class="text-sm text-red-600 mt-2">
            {{ form.errors.roles }}
          </span>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 justify-end">
          <a
            href="/assign-roles"
            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
          >
            Cancel
          </a>

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

    <!-- ✅ Modal Konfirmasi Update -->
    <ConfirmDialog
      :show="submitModalOpen"
      title="Update Assign Role?"
      :message="`Apakah Anda yakin ingin mengupdate role untuk user &quot;${props.user?.name ?? ''}&quot;?`"
      confirmText="Ya, Update"
      cancelText="Batal"
      :loading="form.processing || loading"
      @cancel="closeSubmitModal()"
      @confirm="confirmUpdate"
    />
  </AppLayout>
</template>
