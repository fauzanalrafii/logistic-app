<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue"
import ConfirmDialog from "@/components/ConfirmDialog.vue"
import { Link, router } from "@inertiajs/vue3"
import { Plus, FileText, ClipboardList, Eye, Pencil, Trash2 } from "lucide-vue-next"
import { computed, ref } from "vue"
import { useToast } from "@/composables/useToast"

const props = defineProps<{
  workflows: any[]
  currentPage: string
}>()

const { success, error } = useToast()

// ===============================
// ✅ Modal Delete state
// ===============================
const deleteModalOpen = ref(false)
const deleting = ref(false)
const selectedWorkflow = ref<any>(null)

const canManage = computed(() => {
  // tetap pakai yang sebelumnya kamu pakai di template: $page.props.auth.permissions.includes(...)
  // tapi di script ini kita biarin template yang cek langsung juga boleh
  return true
})

function openDeleteModal(w: any) {
  selectedWorkflow.value = w
  deleteModalOpen.value = true
}

// ✅ FIX: kasih opsi force biar bisa nutup walau deleting masih true
function closeDeleteModal(force = false) {
  if (deleting.value && !force) return
  deleteModalOpen.value = false
  selectedWorkflow.value = null
}

function confirmDelete() {
  if (!selectedWorkflow.value) return
  if (deleting.value) return

  deleting.value = true

  router.delete(`/approval/workflows/${selectedWorkflow.value.id}`, {
    preserveScroll: true,

    onSuccess: () => {
      // ✅ penting: force close (karena onSuccess terjadi sebelum onFinish)
      closeDeleteModal(true)

      // ✅ optional toast lokal (biar tetap ada notif walau backend gak kirim flash)
      success(`Workflow "${selectedWorkflow.value?.name ?? ""}" berhasil dihapus.`)
    },

    onError: (errs: any) => {
      const msg = errs?.error || "Gagal menghapus workflow."
      error(String(msg))

      // biar modal gak nyangkut juga
      closeDeleteModal(true)
    },

    onFinish: () => {
      deleting.value = false
    },
  })
}
</script>

<template>
  <Head>
    <title>Workflow Approval</title>
  </Head>

  <AppLayout :currentPage="currentPage">
    <!-- Header -->
    <div class="mb-4">
      <h1 class="text-2xl font-bold text-gray-800">Manajemen Workflow Approval</h1>
      <p class="text-sm text-slate-500 mt-1">Kelola alur approval untuk berbagai proses.</p>
    </div>

    <!-- Top Bar -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 mb-4">
      <div class="flex flex-col md:flex-row md:items-center gap-3 justify-between">
        <div class="text-sm font-semibold text-slate-700">
          Total {{ workflows.length }} workflow
        </div>

        <a
          v-if="$page.props.auth.permissions.includes('manage_roles')"
          href="/approval/workflows/create"
          class="btn-primary"
        >
          <Plus class="w-4 h-4" />
          <span>Buat Workflow Baru</span>
        </a>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden">
      <div class="px-4 py-3 border-b flex items-center justify-between bg-gray-50">
        <div class="text-sm font-semibold text-slate-700">Data Workflow</div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-semibold border-b border-gray-100">
            <tr>
              <th class="px-4 py-3 text-left">Workflow</th>
              <th class="px-4 py-3 text-left">Untuk Status</th>
              <th class="px-4 py-3 text-center">Jumlah Step</th>
              <th class="px-4 py-3 text-center">Project Aktif</th>
              <th class="px-4 py-3 text-center">Status</th>
              <th class="px-4 py-3 text-center w-1">Actions</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="workflow in workflows"
              :key="workflow.id"
              class="hover:bg-gray-50 transition"
            >
              <!-- Workflow Name -->
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div
                    class="flex-shrink-0 h-10 w-10 flex items-center justify-center bg-blue-50 rounded-lg border border-blue-100"
                  >
                    <ClipboardList class="h-5 w-5 text-blue-600" />
                  </div>
                  <div>
                    <div class="font-semibold text-slate-800 text-sm">
                      {{ workflow.name }}
                    </div>
                    <div class="font-mono text-xs text-slate-400 mt-0.5">
                      {{ workflow.process_type }}
                    </div>
                  </div>
                </div>
              </td>

              <!-- Trigger Status -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="badge badge-purple">
                  {{ workflow.trigger_status }}
                </span>
              </td>

              <!-- Steps Count -->
              <td class="px-4 py-3 whitespace-nowrap text-center">
                <span
                  class="inline-flex items-center justify-center w-8 h-8 bg-slate-100 text-slate-700 rounded-full font-semibold text-sm"
                >
                  {{ workflow.steps_count }}
                </span>
              </td>

              <!-- Project Count -->
              <td class="px-4 py-3 whitespace-nowrap text-center">
                <span
                  :class="[
                    'inline-flex items-center justify-center w-8 h-8 rounded-full font-semibold text-sm',
                    workflow.project_count > 0 ? 'bg-blue-100 text-blue-700' : 'bg-slate-50 text-slate-400',
                  ]"
                >
                  {{ workflow.project_count }}
                </span>
              </td>

              <!-- Active Status -->
              <td class="px-4 py-3 whitespace-nowrap text-center">
                <span
                  :class="workflow.is_active ? 'badge badge-green' : 'badge badge-gray'"
                  :title="workflow.is_active ? 'Ada project yang menggunakan flow ini' : 'Tidak ada project aktif'"
                >
                  {{ workflow.is_active ? "Aktif" : "Non-aktif" }}
                </span>
              </td>

              <!-- Actions -->
              <td class="px-4 py-3 whitespace-nowrap text-center">
                <div class="flex items-center justify-center gap-2">
                  <Link
                    :href="`/approval/workflows/${workflow.id}`"
                    class="btn-action btn-action-slate"
                    title="Lihat Diagram"
                  >
                    <Eye class="w-4 h-4" />
                    <span>Lihat</span>
                  </Link>

                  <Link
                    :href="`/approval/workflows/${workflow.id}/edit`"
                    class="btn-action btn-action-blue"
                    title="Edit"
                  >
                    <Pencil class="w-4 h-4" />
                    <span>Edit</span>
                  </Link>

                  <button
                    type="button"
                    class="btn-action btn-action-red"
                    title="Hapus"
                    @click="openDeleteModal(workflow)"
                    :disabled="deleting && selectedWorkflow?.id === workflow.id"
                    :class="(deleting && selectedWorkflow?.id === workflow.id) ? 'opacity-60 cursor-not-allowed' : ''"
                  >
                    <Trash2 class="w-4 h-4" />
                    <span>Hapus</span>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!workflows.length">
              <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                <FileText class="mx-auto h-12 w-12 text-gray-300 mb-3" />
                <p>Tidak ada workflow ditemukan.</p>
                <p class="text-sm mt-1">Mulai dengan membuat workflow baru.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ✅ Modal Konfirmasi Delete -->
    <ConfirmDialog
      :show="deleteModalOpen"
      title="Hapus Workflow?"
      :message="`Apakah Anda yakin ingin menghapus workflow &quot;${selectedWorkflow?.name ?? ''}&quot;? Aksi ini tidak dapat dibatalkan.`"
      confirmText="Ya, Hapus"
      cancelText="Batal"
      :loading="deleting"
      @cancel="closeDeleteModal()"
      @confirm="confirmDelete"
    />
  </AppLayout>
</template>

<style scoped>
.btn-primary {
  @apply flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition;
}

.badge {
  @apply inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border cursor-pointer transition;
}
.badge-green {
  @apply bg-green-50 text-green-700 border-green-200 hover:bg-green-100;
}
.badge-gray {
  @apply bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100;
}
.badge-purple {
  @apply bg-purple-50 text-purple-700 border-purple-200;
}

.btn-action {
  @apply px-3 py-1.5 rounded-lg text-xs font-medium inline-flex items-center gap-1 transition border;
}
.btn-action-blue {
  @apply bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100;
}
.btn-action-red {
  @apply bg-red-50 text-red-700 border-red-200 hover:bg-red-100;
}
.btn-action-slate {
  @apply bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200;
}
</style>
