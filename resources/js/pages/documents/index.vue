<script setup lang="ts">
import { computed, ref, reactive, onMounted, onBeforeUnmount } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { ArrowRight, X, Filter, RotateCcw } from 'lucide-vue-next'

import ConfirmDialog from '@/components/ConfirmDialog.vue'
import Snackbar from '@/components/Snackbar.vue'

const page = usePage<any>()

type Paginated<T> = {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  links: { url: string | null; label: string; active: boolean }[]
}

type StatusInfo = { id: number; name: string; description: string | null } | null
type ProjectRef = { id: number; name: string; code?: string } | null

type DocumentItem = {
  id: number
  project_id: number | null
  project?: ProjectRef
  document_type: string
  file_name: string
  file_size: number | null
  status_id: number | null
  status: StatusInfo
  uploaded_at: string | null
  created_at: string
}

const props = defineProps<{
  documents: Paginated<DocumentItem>
  filters: {
    project_id?: string | null
    document_type?: string | null
    date_from?: string | null
    date_to?: string | null
    q?: string | null
    status_id?: string | null
    per_page?: number | null
  }
  docTypes: { code: string; label: string }[]
  statusOptions: { id: number; name: string; description: string | null }[]
  projectOptions: { id: number; name: string; code?: string }[]
}>()

/* ================== SNACKBAR (AUTO CLOSE) ================== */
const snack = reactive({
  show: false,
  message: '',
  type: 'info' as 'success' | 'error' | 'info',
})

let snackTimer: number | null = null

function closeSnack() {
  snack.show = false
  if (snackTimer) {
    window.clearTimeout(snackTimer)
    snackTimer = null
  }
}

function showSnack(message: string, type: 'success' | 'error' | 'info' = 'info', autoMs = 3000) {
  closeSnack()
  snack.show = true
  snack.message = message
  snack.type = type

  snackTimer = window.setTimeout(() => {
    snack.show = false
    snackTimer = null
  }, autoMs)
}

onBeforeUnmount(() => closeSnack())

/* ================== FILTER STATE ================== */
const search = ref(props.filters.q ?? '')
const perPage = ref(props.filters.per_page ?? props.documents.per_page ?? 10)

const filterProject = ref(props.filters.project_id ?? '')
const filterDocType = ref(props.filters.document_type ?? '')
const filterStatus = ref(props.filters.status_id ?? '')
const filterStartDate = ref(props.filters.date_from ?? '')
const filterEndDate = ref(props.filters.date_to ?? '')

const showFilter = ref(false)

function applyFilters() {
  router.get(
    '/documents',
    {
      q: search.value,
      per_page: perPage.value,
      project_id: filterProject.value,
      document_type: filterDocType.value,
      status_id: filterStatus.value,
      date_from: filterStartDate.value,
      date_to: filterEndDate.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    },
  )
}

function resetFilters() {
  search.value = ''
  perPage.value = 10
  filterProject.value = ''
  filterDocType.value = ''
  filterStatus.value = ''
  filterStartDate.value = ''
  filterEndDate.value = ''
  applyFilters()
}

const hasDocs = computed(() => props.documents.data.length > 0)

function formatDate(value: string | null) {
  if (!value) return '-'
  const d = new Date(value)
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatSize(size: number | null) {
  if (!size) return '-'
  const kb = size / 1024
  if (kb < 1024) return `${kb.toFixed(1)} KB`
  return `${(kb / 1024).toFixed(1)} MB`
}

function goToCreate() {
  router.get('/documents/create')
}

function downloadDocument(doc: DocumentItem) {
  window.location.href = `/documents/${doc.id}/download`
}

function goToReplaceFile(doc: DocumentItem) {
  router.get(`/documents/${doc.id}/replace-file`)
}

/* ================== DELETE MODAL ================== */
const deleteModalOpen = ref(false)
const deleting = ref(false)
const selectedDoc = ref<DocumentItem | null>(null)

function openDeleteModal(doc: DocumentItem) {
  selectedDoc.value = doc
  deleteModalOpen.value = true
}

function closeDeleteModal(force = false) {
  if (deleting.value && !force) return
  deleteModalOpen.value = false
  selectedDoc.value = null
}

function deleteConfirmed() {
  if (!selectedDoc.value) return

  deleting.value = true

  router.delete(`/documents/${selectedDoc.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showSnack('Dokumen berhasil dihapus.', 'success')
      closeDeleteModal(true)
    },
    onError: (errors) => {
      const msg =
        errors && Object.keys(errors).length
          ? Object.values(errors).flat().join(', ')
          : 'Gagal menghapus dokumen.'
      showSnack(String(msg), 'error', 4500)
    },
    onFinish: () => {
      deleting.value = false
    },
  })
}

onMounted(() => {
  const msg = page?.props?.flash?.success
  if (msg) showSnack(String(msg), 'success')
})
</script>

<template>
  <AppLayout title="Manajemen Dokumen" currentPage="documents">
    <Head title="Manajemen Dokumen Deployment" />

    <div class="max-w-7xl mx-auto space-y-6 px-4 lg:px-0">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Manajemen Dokumen Deployment</h1>
          <p class="text-sm text-slate-500">Kelola dokumen proyek, status verifikasi, dan filter berdasarkan periode.</p>
        </div>

        <button
          type="button"
          class="inline-flex items-center px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700"
          @click="goToCreate"
        >
          <ArrowRight class="w-4 h-4 mr-1" />
          Upload Dokumen
        </button>
      </div>

      <div class="flex items-center justify-start gap-2 flex-wrap">
        <input
          v-model="search"
          @keyup.enter="applyFilters"
          placeholder="Search nama file / tipe dokumen..."
          class="input w-full md:w-1/3 h-9 text-sm"
        />

        <select v-model="perPage" @change="applyFilters" class="input h-9 w-24 text-sm">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>

        <button
          @click="showFilter = !showFilter"
          class="flex items-center gap-1 px-3 py-1.5 h-9 bg-gray-200 hover:bg-gray-300 rounded-md shadow-sm text-gray-700 text-sm"
        >
          <Filter class="w-4 h-4" />
          <span>Advanced Filter</span>
        </button>

        <button
          @click="resetFilters"
          class="flex items-center gap-1 px-3 py-1.5 h-9 bg-white hover:bg-gray-50 rounded-md border border-gray-200 shadow-sm text-gray-600 text-sm"
        >
          <X class="w-4 h-4" />
          <span>Reset</span>
        </button>
      </div>

      <div v-if="showFilter" class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="text-sm font-semibold mb-1 block">Project</label>
            <select v-model="filterProject" @change="applyFilters" class="input w-full h-10">
              <option value="">All</option>
              <option v-for="p in props.projectOptions" :key="p.id" :value="String(p.id)">
                {{ p.code ? `${p.code} - ${p.name}` : p.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="text-sm font-semibold mb-1 block">Jenis Dokumen</label>
            <select v-model="filterDocType" @change="applyFilters" class="input w-full h-10">
              <option value="">All</option>
              <option v-for="dt in props.docTypes" :key="dt.code" :value="dt.label">
                {{ dt.label }}
              </option>
            </select>
          </div>

          <div>
            <label class="text-sm font-semibold mb-1 block">Status</label>
            <select v-model="filterStatus" @change="applyFilters" class="input w-full h-10">
              <option value="">All</option>
              <option v-for="st in props.statusOptions" :key="st.id" :value="String(st.id)">
                {{ st.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="text-sm font-semibold mb-1 block">Start Date</label>
            <input type="date" v-model="filterStartDate" @change="applyFilters" class="input w-full h-10" />
          </div>

          <div>
            <label class="text-sm font-semibold mb-1 block">End Date</label>
            <input type="date" v-model="filterEndDate" @change="applyFilters" class="input w-full h-10" />
          </div>

          <div class="hidden md:block"></div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b bg-slate-50 text-xs font-semibold text-slate-600 uppercase">
                <th class="px-3 py-2 text-left">Tanggal</th>
                <th class="px-3 py-2 text-left">Project</th>
                <th class="px-3 py-2 text-left">Jenis Dokumen</th>
                <th class="px-3 py-2 text-left">Nama File</th>
                <th class="px-3 py-2 text-left">Ukuran</th>
                <th class="px-3 py-2 text-left">Status</th>
                <th class="px-3 py-2 text-left">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="doc in documents.data"
                :key="doc.id"
                class="border-b last:border-0 hover:bg-slate-50"
              >
                <td class="px-3 py-2 align-top whitespace-nowrap">
                  {{ formatDate(doc.uploaded_at ?? doc.created_at) }}
                </td>

                <td class="px-3 py-2 align-top">
                  {{
                    doc.project
                      ? (doc.project.code ? `${doc.project.code} - ${doc.project.name}` : doc.project.name)
                      : (doc.project_id ?? '-')
                  }}
                </td>

                <td class="px-3 py-2 align-top">{{ doc.document_type }}</td>
                <td class="px-3 py-2 align-top">{{ doc.file_name }}</td>
                <td class="px-3 py-2 align-top">{{ formatSize(doc.file_size) }}</td>

                <td class="px-3 py-2 align-top">
                  <span
                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="{
                      'bg-emerald-100 text-emerald-700': doc.status?.name === 'FINAL',
                      'bg-blue-100 text-blue-700': doc.status?.name === 'VERIFIED',
                      'bg-slate-100 text-slate-700': !['FINAL', 'VERIFIED'].includes(doc.status?.name ?? ''),
                    }"
                  >
                    {{ doc.status?.name ?? '-' }}
                  </span>
                </td>

                <td class="px-3 py-2 align-top whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <button class="text-blue-600 hover:underline text-xs" @click="downloadDocument(doc)">
                      Download
                    </button>

                    <button
                      class="text-slate-700 hover:underline text-xs inline-flex items-center gap-1"
                      @click="goToReplaceFile(doc)"
                      title="Ganti file dokumen"
                    >
                      <RotateCcw class="w-3.5 h-3.5" />
                      Ganti File
                    </button>

                    <button class="text-red-600 hover:underline text-xs" @click="openDeleteModal(doc)">
                      Hapus
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!hasDocs">
                <td colspan="7" class="px-3 py-4 text-center text-slate-500">Belum ada dokumen.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex justify-end mt-4 gap-1">
          <Link
            v-for="(link, i) in props.documents.links"
            :key="i"
            :href="link.url ?? '#'"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
            :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
            v-html="link.label"
          />
        </div>
      </div>
    </div>

    <ConfirmDialog
      :show="deleteModalOpen"
      title="Hapus Dokumen?"
      :message="selectedDoc ? `Dokumen '${selectedDoc.file_name}' akan dihapus permanen.` : 'Dokumen akan dihapus.'"
      confirmText="Ya, Hapus"
      cancelText="Batal"
      :loading="deleting"
      @cancel="closeDeleteModal()"
      @confirm="deleteConfirmed"
    />

    <Snackbar
      :show="snack.show"
      :message="snack.message"
      :type="snack.type"
      @close="closeSnack"
    />
  </AppLayout>
</template>

<style scoped>
.input {
  @apply border border-gray-300 rounded-md shadow-sm px-3 py-2
         focus:ring-blue-500 focus:border-blue-500;
}
</style>
