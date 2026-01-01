<script setup lang="ts">
import { ref, watch } from "vue"
import Multiselect from "vue-multiselect"
import "vue-multiselect/dist/vue-multiselect.css"

import { Head, Link, useForm } from "@inertiajs/vue3"
import AppLayout from "@/layouts/AppLayout.vue"
import ConfirmDialog from "@/components/ConfirmDialog.vue"
import { useToast } from "@/composables/useToast"

const { success, error } = useToast()

const props = defineProps<{
  users: any[]
  permissions: Array<{ id: number; name: string }>
}>()

const form = useForm<{
  user: any | null
  user_id: string | number
  permissions: number[]
}>({
  user: null,
  user_id: "",
  permissions: [],
})

// Auto-set user_id
watch(
  () => form.user,
  (val) => {
    form.user_id = val ? val.value : ""
  }
)

// ----- SELECT ALL -----
const selectAllPermissions = ref(false)

watch(selectAllPermissions, (checked) => {
  form.permissions = checked ? props.permissions.map((p) => p.id) : []
})

watch(
  () => form.permissions,
  (val) => {
    selectAllPermissions.value = val.length === props.permissions.length
  },
  { deep: true }
)

// ✅ Modal Submit
const submitModalOpen = ref(false)
const loading = ref(false)

function openSubmitModal() {
  if (!form.user_id) return error("Pilih user dulu.")
  if (!form.permissions.length) return error("Pilih minimal 1 permission.")
  submitModalOpen.value = true
}

function closeSubmitModal(force = false) {
  if ((form.processing || loading.value) && !force) return
  submitModalOpen.value = false
}

function confirmSubmit() {
  loading.value = true

  form.post("/assign-permissions", {
    preserveScroll: true,
    onSuccess: () => {
      success("Assign permission berhasil disimpan.")
      closeSubmitModal(true)
    },
    onError: (errs: any) => {
      const msg =
        errs?.error ||
        (form.errors?.permissions ? String(form.errors.permissions) : "Gagal assign permission.")
      error(String(msg))
    },
    onFinish: () => {
      loading.value = false
    },
  })
}
</script>

<template>
  <Head title="Assign Permission" />

  <AppLayout title="Manage Roles" currentPage="assign-permissions.index">
    <div class="space-y-6">
      <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
        <h2 class="text-2xl font-bold text-gray-900">Assign Permission</h2>
        <p class="text-sm text-slate-500 mt-1">Pilih user dan tentukan permission.</p>
      </div>

      <div class="bg-white rounded-lg shadow p-6 space-y-6 border border-gray-200">
        <!-- User Dropdown -->
        <div>
          <label class="font-semibold text-slate-700">User List</label>
          <div class="mt-2">
            <Multiselect
              v-model="form.user"
              :options="props.users"
              label="label"
              track-by="value"
              placeholder="Pilih user"
            />
          </div>

          <span v-if="form.errors.user_id" class="text-sm text-red-600 mt-2 block">
            {{ form.errors.user_id }}
          </span>
        </div>

        <!-- Permissions -->
        <div>
          <div class="flex justify-between items-center mb-4">
            <label class="block text-sm font-semibold text-gray-700">Permissions</label>

            <div class="flex items-center gap-2">
              <input
                type="checkbox"
                id="select-all-permissions"
                v-model="selectAllPermissions"
                class="w-4 h-4 text-[#0BA64A] rounded focus:ring-2"
              />
              <label for="select-all-permissions" class="text-sm text-gray-700 cursor-pointer">
                Select All Permissions
              </label>
            </div>
          </div>

          <div
            v-if="props.permissions.length > 0"
            class="grid grid-flow-col grid-rows-5 gap-4 p-4 border border-gray-300 rounded-lg bg-gray-50"
          >
            <div v-for="permission in props.permissions" :key="permission.id" class="flex items-center">
              <input
                type="checkbox"
                :id="`permission-${permission.id}`"
                :value="permission.id"
                v-model="form.permissions"
                class="w-4 h-4 text-[#0BA64A] rounded focus:ring-2"
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
      </div>

      <!-- Actions -->
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
          {{ loading ? "Assigning..." : "Assign Permission" }}
        </button>
      </div>
    </div>

    <ConfirmDialog
      :show="submitModalOpen"
      title="Simpan Assign Permission?"
      :message="`Apakah Anda yakin ingin menyimpan permission untuk user &quot;${form.user?.label ?? ''}&quot;?`"
      confirmText="Ya, Simpan"
      cancelText="Batal"
      :loading="form.processing || loading"
      @cancel="closeSubmitModal()"
      @confirm="confirmSubmit"
    />
  </AppLayout>
</template>
