<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue"
import { Head, router } from "@inertiajs/vue3"
import { reactive, ref } from "vue"
import { CheckCircle, Edit as EditIcon, Loader2 } from "lucide-vue-next"
import SearchSelect from "@/components/SearchSelect.vue"
import CustomDatePicker from "@/components/CustomDatePicker.vue"
import ConfirmDialog from "@/components/ConfirmDialog.vue"
import { useToast } from "@/composables/useToast"

const props = defineProps<{
  planners: any[]
  statusOptions: any[]
  project: any
}>()

const { success, error } = useToast()

const kodeposOptions = ref<Array<{ id: number; label: string }>>([])
const isLoadingKodepos = ref(false)
const isSubmitting = ref(false)

// ✅ modal state
const confirmOpen = ref(false)

const form = reactive({
  name: props.project?.name || "",
  code: props.project?.code || "",
  status_id: props.project?.status_id || null,
  planner_id: props.project?.planner_id || null,
  target_completion_date: props.project?.target_completion_date || "",
  project_type: props.project?.project_type || "",
  kodepos_id: props.project?.kodepos_id || null,
  location: props.project?.location || "",
  description: props.project?.description || "",
})

function openConfirm() {
  confirmOpen.value = true
}

function closeConfirm() {
  if (isSubmitting.value) return
  confirmOpen.value = false
}

function doSubmit() {
  if (isSubmitting.value) return
  isSubmitting.value = true

  const payload: any = { ...form }

  // kalau planner kosong, jangan kirim biar aman
  if (!payload.planner_id) delete payload.planner_id

  // kalau planning sudah submit, area (kodepos) gak boleh ikut terkirim
  if (props.project?.isSubmitted) {
    delete payload.kodepos_id
  }

  router.put(`/projects/${props.project.id}`, payload, {
    preserveScroll: true,
    onSuccess: () => {
      success("Project berhasil diupdate.")
      confirmOpen.value = false
    },
    onError: (errs: any) => {
      const msg =
        errs?.error ||
        errs?.message ||
        "Update gagal. Coba cek lagi inputnya."
      error(String(msg))
    },
    onFinish: () => {
      isSubmitting.value = false
    },
  })
}

function cancel() {
  router.visit("/projects")
}

async function searchKodepos(keyword: string) {
  if (props.project?.isSubmitted) return

  if (!keyword || keyword.length < 3) {
    kodeposOptions.value = []
    return
  }

  isLoadingKodepos.value = true
  try {
    const res = await fetch(`/api/kodepos/search?search=${encodeURIComponent(keyword)}`)
    kodeposOptions.value = await res.json()
  } catch (e) {
    console.error(e)
    kodeposOptions.value = []
    error("Gagal ambil data kodepos.")
  } finally {
    isLoadingKodepos.value = false
  }
}
</script>

<template>
  <Head title="Edit Project" />

  <AppLayout title="Edit Project" currentPage="project.edit">
    <div class="w-full">
      <div class="mx-auto w-full max-w-6xl px-4">
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-8">
          <div class="flex items-center gap-3 mb-8">
            <EditIcon class="w-6 h-6 text-green-600" />
            <h2 class="text-2xl font-bold text-gray-800">Edit Project</h2>
          </div>

          <!-- ✅ FORM: submit sekarang buka modal (bukan langsung put) -->
          <form @submit.prevent="openConfirm" class="flex flex-col gap-6">
            <!-- SECTION 1 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-semibold mb-1">
                  Project Name <span class="text-red-500">*</span>
                </label>
                <input v-model="form.name" type="text" class="input w-full" required />
              </div>

              <div>
                <label class="block text-sm font-semibold mb-1">Code</label>
                <input v-model="form.code" type="text" class="input w-full bg-gray-100" disabled />
              </div>

              <SearchSelect
                label="Planner"
                placeholder="Search planner..."
                :items="props.planners"
                itemLabel="Name"
                itemValue="ID"
                v-model="form.planner_id"
                :selectedText="props.planners?.find((p:any) => p.ID === form.planner_id)?.Name"
              />

              <div>
                <label class="block text-sm font-semibold mb-1">Status</label>
                <input
                  type="text"
                  :value="props.statusOptions?.find((s:any) => s.id === form.status_id)?.name ?? '-'"
                  class="input w-full bg-gray-100 cursor-not-allowed"
                  disabled
                />
              </div>
            </div>

            <!-- SECTION 2 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-semibold mb-1">Target Completion Date</label>
                <CustomDatePicker v-model="form.target_completion_date" placeholder="Select a date" />
              </div>

              <div>
                <label class="block text-sm font-semibold mb-1">Project Type</label>
                <input v-model="form.project_type" type="text" class="input w-full" />
              </div>
            </div>

            <!-- SECTION 3 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <SearchSelect
                  label="Area"
                  placeholder="Cari kodepos / kelurahan / kota..."
                  :items="kodeposOptions"
                  itemLabel="label"
                  itemValue="id"
                  v-model="form.kodepos_id"
                  @search="searchKodepos"
                  remote
                  :selectedText="props.project?.area"
                  :disabled="props.project?.isSubmitted"
                />

                <p class="text-xs text-gray-500 mt-1">
                  Area saat ini: <strong>{{ props.project?.area }}</strong>
                </p>

                <p v-if="props.project?.isSubmitted" class="text-xs text-red-500 mt-1">
                  Area tidak dapat diubah karena planning sudah disubmit.
                </p>
              </div>

              <div>
                <label class="block text-sm font-semibold mb-1">Location</label>
                <input v-model="form.location" type="text" class="input w-full" />
              </div>
            </div>

            <!-- SECTION 4 -->
            <div>
              <label class="block text-sm font-semibold mb-1">Description</label>
              <textarea v-model="form.description" rows="4" class="input w-full"></textarea>
            </div>

            <div class="flex justify-end gap-3 mt-4">
              <button
                type="submit"
                :disabled="isSubmitting || isLoadingKodepos"
                class="flex items-center gap-2 px-5 py-2 bg-green-600 text-white rounded-lg
                       hover:bg-green-700 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
                <CheckCircle v-else class="w-4 h-4" />
                {{ isSubmitting ? "Updating..." : "Update" }}
              </button>

              <button
                type="button"
                @click="cancel"
                class="px-5 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 shadow-sm"
              >
                Cancel
              </button>
            </div>
          </form>

          <!-- STATUS HISTORY -->
          <div v-if="props.project?.statusHistories?.length > 0" class="mt-10">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Status History</h3>
            <div class="overflow-x-auto bg-gray-50 p-4 rounded-xl border border-gray-200">
              <table class="min-w-full text-sm">
                <thead class="bg-gray-100 text-gray-500 uppercase text-xs font-semibold border-b border-gray-200">
                  <tr>
                    <th class="px-4 py-2 text-left">Old Status</th>
                    <th class="px-4 py-2 text-left">New Status</th>
                    <th class="px-4 py-2 text-left">Changed By</th>
                    <th class="px-4 py-2 text-left">Changed At</th>
                    <th class="px-4 py-2 text-left">Note</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="history in props.project.statusHistories" :key="history.uuid">
                    <td class="px-4 py-2">{{ history.oldStatusData?.name ?? "-" }}</td>
                    <td class="px-4 py-2">{{ history.newStatusData?.name ?? "-" }}</td>
                    <td class="px-4 py-2">{{ history.changer?.Name || "-" }}</td>
                    <td class="px-4 py-2">{{ history.changed_at }}</td>
                    <td class="px-4 py-2">{{ history.note || "-" }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- ✅ CONFIRM MODAL (konsisten, bukan native browser confirm) -->
    <ConfirmDialog
      :show="confirmOpen"
      title="Update Project?"
      message="Yakin ingin menyimpan perubahan project ini?"
      confirmText="Ya, Submit"
      cancelText="Batal"
      :loading="isSubmitting"
      @cancel="closeConfirm"
      @confirm="doSubmit"
    />
  </AppLayout>
</template>

<style scoped>
.input {
  @apply border border-gray-200 rounded-xl shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500;
}
</style>
