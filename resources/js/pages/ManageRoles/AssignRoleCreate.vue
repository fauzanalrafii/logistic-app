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
  roles: any[]
}>()

const form = useForm<{
  user: any | null
  user_id: string | number
  roles: number[]
}>({
  user: null,
  user_id: "",
  roles: [],
})

// Auto-set user_id
watch(
  () => form.user,
  (val) => {
    form.user_id = val ? val.value : ""
  }
)

// ----- SELECT ALL LOGIC -----
const selectAllRoles = ref(false)

watch(selectAllRoles, (checked) => {
  form.roles = checked ? props.roles.map((r) => r.id) : []
})

watch(
  () => form.roles,
  (val) => {
    selectAllRoles.value = val.length === props.roles.length
  },
  { deep: true }
)

// ✅ modal submit
const submitModalOpen = ref(false)
const loading = ref(false)

function openSubmitModal() {
  // validasi ringan sebelum modal
  if (!form.user_id) {
    error("Pilih user dulu.")
    return
  }
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

function confirmSubmit() {
  loading.value = true

  form.post("/assign-roles/store", {
    preserveScroll: true,
    onSuccess: () => {
      success("Assign role berhasil disimpan.")
      closeSubmitModal(true)
    },
    onError: (errs: any) => {
      const msg =
        errs?.error ||
        (form.errors?.roles ? String(form.errors.roles) : "Gagal assign role.")
      error(String(msg))
    },
    onFinish: () => {
      loading.value = false
    },
  })
}
</script>

<template>
  <Head title="Create Assign Role" />

  <AppLayout title="Manage Roles" currentPage="assign-roles.index">
    <div class="space-y-6">
      <!-- Form Card -->
      <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
        <h2 class="text-2xl font-bold text-gray-900">Assign Role</h2>
        <p class="text-sm text-slate-500 mt-1">Pilih user dan tentukan role.</p>
      </div>

      <!-- Card -->
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
          <p v-if="form.errors.user_id" class="text-sm text-red-600 mt-2">
            {{ form.errors.user_id }}
          </p>
        </div>

        <!-- Roles Section -->
        <div>
          <div class="flex justify-between items-center mb-4">
            <label class="block text-sm font-semibold text-gray-700">
              Roles
            </label>

            <!-- Select All -->
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
          {{ loading ? "Creating..." : "Assign Role" }}
        </button>
      </div>
    </div>

    <!-- ✅ Modal Konfirmasi Submit -->
    <ConfirmDialog
      :show="submitModalOpen"
      title="Simpan Assign Role?"
      :message="`Apakah Anda yakin ingin menyimpan role untuk user &quot;${form.user?.label ?? ''}&quot;?`"
      confirmText="Ya, Simpan"
      cancelText="Batal"
      :loading="form.processing || loading"
      @cancel="closeSubmitModal()"
      @confirm="confirmSubmit"
    />
  </AppLayout>
</template>
