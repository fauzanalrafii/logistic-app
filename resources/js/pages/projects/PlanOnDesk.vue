<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue"
import ConfirmDialog from "@/components/ConfirmDialog.vue"
import { Head, Link, router } from "@inertiajs/vue3"
import { Filter, Eye, Search, X, Trash2 } from "lucide-vue-next"
import { ref, watch } from "vue"
import { useToast } from "@/composables/useToast"

const props = defineProps<{
  projects: any
  filters?: any
}>()

const { success, error } = useToast()

const search = ref(props.filters?.search ?? "")
const perPage = ref(props.filters?.per_page ?? 10)
const filterArea = ref(props.filters?.area ?? "")
const filterType = ref(props.filters?.project_type ?? "")
const filterSource = ref(props.filters?.source ?? "")
const filterStartDate = ref(props.filters?.start_date ?? "")
const filterEndDate = ref(props.filters?.end_date ?? "")

const showFilter = ref(false)
const isLoading = ref(false)

// ===============================
// ✅ Modal Delete state
// ===============================
const deleteModalOpen = ref(false)
const deleting = ref(false)
const selectedProject = ref<any>(null)

function projectId(p: any) {
  return p?.id ?? p?.ID
}

function openDeleteModal(p: any) {
  if (p?.is_submitted) return
  selectedProject.value = p
  deleteModalOpen.value = true
}

// ✅ FIX: kasih force biar bisa nutup walau deleting masih true
function closeDeleteModal(force = false) {
  if (deleting.value && !force) return
  deleteModalOpen.value = false
  selectedProject.value = null
}

function confirmDelete() {
  if (!selectedProject.value) return
  if (deleting.value) return

  deleting.value = true

  router.delete(`/projects/plan-on-desk/${projectId(selectedProject.value)}`, {
    preserveScroll: true,
    onSuccess: () => {
      success(`Project "${selectedProject.value?.name ?? ""}" berhasil dihapus.`)

      // ✅ paksa close saat sukses (ini inti fix kamu)
      closeDeleteModal(true)
    },
    onError: (errs: any) => {
      const msg = errs?.error || "Gagal menghapus project."
      error(String(msg))
    },
    onFinish: () => {
      deleting.value = false
    },
  })
}

// ===============================
// debounce search
// ===============================
let t: any = null
watch(search, () => {
  clearTimeout(t)
  t = setTimeout(() => applyFilters(), 350)
})

function applyFilters() {
  isLoading.value = true
  router.get(
    "/projects/plan-on-desk",
    {
      search: search.value,
      per_page: perPage.value,
      area: filterArea.value,
      project_type: filterType.value,
      source: filterSource.value,
      start_date: filterStartDate.value,
      end_date: filterEndDate.value,
    },
    {
      preserveState: true,
      replace: true,
      onFinish: () => (isLoading.value = false),
    }
  )
}

function resetFilters() {
  search.value = ""
  perPage.value = 10
  filterArea.value = ""
  filterType.value = ""
  filterSource.value = ""
  filterStartDate.value = ""
  filterEndDate.value = ""
  applyFilters()
}

function sourceBadgeClass(source?: string | null) {
  const s = (source ?? "").toUpperCase()
  if (s === "OSS") return "badge badge-red"
  return "badge badge-blue"
}

function approvalBadgeClass(status?: string | null) {
  const s = (status ?? "").toLowerCase()
  if (s.includes("approved")) return "badge badge-green"
  if (s.includes("rejected")) return "badge badge-red"
  if (s.includes("pending") || s.includes("step")) return "badge badge-yellow"
  return "badge badge-gray"
}
</script>

<template>
  <Head title="Plan On Desk Projects" />

  <AppLayout title="Plan On Desk" currentPage="project.planOnDesk">
    <!-- Header -->
    <div class="mb-4">
      <h1 class="text-2xl font-bold text-gray-800">Plan On Desk Projects</h1>
      <p class="text-sm text-slate-500 mt-1">Daftar project status Plan On Desk.</p>
    </div>

    <!-- Top Bar -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 mb-4">
      <div class="flex flex-col md:flex-row md:items-center gap-3">
        <!-- Search -->
        <div class="relative w-full md:w-[420px]">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input v-model="search" placeholder="Search projects..." class="input-icon w-full h-10 text-sm" />
          <button
            v-if="search"
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded hover:bg-slate-100"
            @click="search = ''"
            title="Clear"
          >
            <X class="w-4 h-4 text-slate-500" />
          </button>
        </div>

        <!-- Per page -->
        <div class="flex items-center gap-2">
          <span class="text-sm text-slate-600">Show</span>
          <select v-model="perPage" @change="applyFilters" class="input h-10 w-28 text-sm">
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>

        <div class="flex items-center gap-2 md:ml-auto">
          <button type="button" @click="showFilter = !showFilter" class="btn-secondary h-10">
            <Filter class="w-4 h-4" />
            <span>Advanced Filter</span>
          </button>

          <button type="button" @click="resetFilters" class="btn-ghost h-10">
            Reset
          </button>
        </div>
      </div>

      <!-- Filter Panel -->
      <div v-if="showFilter" class="mt-4 border-t pt-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="label" for="filterArea">Area</label>
            <input id="filterArea" v-model="filterArea" @input="applyFilters" class="input w-full h-10 text-sm" />
          </div>

          <div>
            <label class="label" for="filterType">Project Type</label>
            <input id="filterType" v-model="filterType" @input="applyFilters" class="input w-full h-10 text-sm" />
          </div>

          <div>
            <label class="label" for="filterSource">Source</label>
            <select id="filterSource" v-model="filterSource" @change="applyFilters" class="input w-full h-10 text-sm">
              <option value="">All</option>
              <option value="MANUAL">MANUAL</option>
              <option value="OSS">OSS</option>
            </select>
          </div>

          <div>
            <label class="label" for="filterStartDate">Start Date</label>
            <input
              id="filterStartDate"
              type="date"
              v-model="filterStartDate"
              @change="applyFilters"
              class="input w-full h-10 text-sm"
            />
          </div>

          <div>
            <label class="label" for="filterEndDate">End Date</label>
            <input
              id="filterEndDate"
              type="date"
              v-model="filterEndDate"
              @change="applyFilters"
              class="input w-full h-10 text-sm"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden">
      <div class="px-4 py-3 border-b flex items-center justify-between">
        <div class="text-sm font-semibold text-slate-700">Data Projects</div>
        <div v-if="isLoading" class="text-xs text-slate-500">Loading...</div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-semibold border-b border-gray-100">
            <tr>
              <th class="px-4 py-3 text-left">Project</th>
              <th class="px-4 py-3 text-left">Planner</th>
              <th class="px-4 py-3 text-left">Source</th>
              <th class="px-4 py-3 text-left">Area</th>
              <th class="px-4 py-3 text-left">Status Approval</th>
              <th class="px-4 py-3 text-center w-1">Actions</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100">
            <tr v-for="p in props.projects.data" :key="projectId(p)" class="hover:bg-gray-50 transition">
              <td class="px-4 py-3">
                <div class="flex flex-col">
                  <div class="font-semibold text-slate-800 text-sm">
                    {{ p.name }}
                  </div>
                  <div class="font-mono text-xs text-slate-500 mt-0.5">
                    {{ p.code }}
                  </div>
                </div>
              </td>

              <td class="px-4 py-3 whitespace-nowrap">
                <div class="text-slate-700 text-sm">
                  {{ p.planner?.Name ?? "-" }}
                </div>
              </td>

              <td class="px-4 py-3 whitespace-nowrap">
                <span :class="sourceBadgeClass(p.source)">{{ p.source ?? "MANUAL" }}</span>
              </td>

              <td class="px-4 py-3 whitespace-nowrap text-slate-700 text-sm">
                {{ p.area ?? "-" }}
              </td>

              <td class="px-4 py-3 whitespace-nowrap">
                <span :class="approvalBadgeClass(p.approval_status)">
                  {{ p.approval_status ?? "Belum Submit" }}
                </span>

                <div v-if="p.approval_last_action" class="text-xs text-slate-500 mt-1">
                  {{ p.approval_last_action.action }} oleh {{ p.approval_last_action.user_name }}
                </div>
              </td>

              <td class="px-4 py-3 whitespace-nowrap text-center">
                <div class="flex items-center justify-center gap-2">
                  <Link
                    :href="`/projects/project-detail?id=${projectId(p)}`"
                    class="btn-action btn-action-slate"
                    title="Detail Project"
                  >
                    <Eye class="w-4 h-4" />
                    <span>Detail</span>
                  </Link>

                  <button
                    type="button"
                    class="btn-action"
                    :class="p.is_submitted ? 'btn-action-disabled' : 'btn-action-red'"
                    :title="p.is_submitted ? 'Projek sudah berjalan, tidak bisa dihapus' : 'Hapus Project'"
                    :disabled="p.is_submitted"
                    @click="openDeleteModal(p)"
                  >
                    <Trash2 class="w-4 h-4" />
                    <span>Hapus</span>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!props.projects.data?.length">
              <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                Data tidak ditemukan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-end mt-4 gap-1">
      <Link
        v-for="(link, i) in props.projects.links"
        :key="i"
        :href="link.url ?? '#'"
        class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
        :class="
          link.active
            ? 'bg-blue-600 text-white'
            : link.url
              ? 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'
              : 'bg-slate-100 text-slate-400 cursor-not-allowed'
        "
        v-html="link.label"
      />
    </div>

    <!-- ✅ Modal Konfirmasi Hapus -->
    <ConfirmDialog
      :show="deleteModalOpen"
      title="Hapus Project?"
      :message="`Apakah Anda yakin ingin menghapus project &quot;${selectedProject?.name ?? ''}&quot; (${selectedProject?.code ?? ''})?`"
      confirmText="Ya, Hapus"
      cancelText="Batal"
      :loading="deleting"
      @cancel="closeDeleteModal()"
      @confirm="confirmDelete"
    />
  </AppLayout>
</template>

<style scoped>
.input {
  @apply border border-gray-300 rounded-md shadow-sm px-3 py-2
  focus:ring-blue-500 focus:border-blue-500;
}

.label {
  @apply text-sm font-semibold mb-1 block text-slate-700;
}

.btn-secondary {
  @apply flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm;
}
.btn-ghost {
  @apply px-3 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm;
}

.badge {
  @apply inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border;
}
.badge-blue {
  @apply bg-blue-50 text-blue-700 border-blue-200;
}
.badge-red {
  @apply bg-red-50 text-red-700 border-red-200;
}
.badge-green {
  @apply bg-green-50 text-green-700 border-green-200;
}
.badge-yellow {
  @apply bg-yellow-50 text-yellow-700 border-yellow-200;
}
.badge-gray {
  @apply bg-gray-50 text-gray-600 border-gray-200;
}

.btn-action {
  @apply px-3 py-1.5 rounded-lg text-xs font-medium inline-flex items-center gap-1 transition border;
}
.btn-action-red {
  @apply bg-red-50 text-red-700 border-red-200 hover:bg-red-100;
}
.btn-action-slate {
  @apply bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200;
}
.btn-action-disabled {
  @apply bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed opacity-60;
}

/* input search icon */
.input-icon {
  @apply border border-gray-300 rounded-md shadow-sm pl-10 pr-10 py-2
  focus:ring-blue-500 focus:border-blue-500;
}
</style>
