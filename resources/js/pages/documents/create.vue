<script setup lang="ts">
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { ArrowLeft, X, Search, ChevronDown, FileUp } from 'lucide-vue-next'
import { computed, reactive, ref, onMounted, onBeforeUnmount } from 'vue'

import ConfirmDialog from '@/components/ConfirmDialog.vue'
import Snackbar from '@/components/Snackbar.vue'

const page = usePage<any>()

const props = defineProps<{
  docTypeOptions: { code: string; label: string }[]
  statusOptions: { id: number; name: string; description: string | null }[]
  projectOptions: { id: number; name: string; code?: string }[]
}>()

const form = useForm<{
  project_id: string | number | null
  related_type: string | null
  related_id: string | number | null
  doc_code: string | null
  document_type: string | null
  status_id: number | null
  file: File | null
}>({
  project_id: null,
  related_type: '',
  related_id: '',
  doc_code: '',
  document_type: '',
  status_id: null,
  file: null,
})

/* ============ PROJECT SEARCH DROPDOWN ============ */
const projectSearch = ref('')
const projectDropdownOpen = ref(false)

const selectedProjectLabel = computed(() => {
  const id = form.project_id ? Number(form.project_id) : null
  if (!id) return ''
  const p = props.projectOptions.find((x) => x.id === id)
  if (!p) return ''
  return p.code ? `${p.code} - ${p.name}` : p.name
})

const filteredProjects = computed(() => {
  if (!projectSearch.value) return props.projectOptions
  const kw = projectSearch.value.toLowerCase()
  return props.projectOptions.filter((p) => {
    const text = `${p.code ?? ''} ${p.name}`.toLowerCase()
    return text.includes(kw)
  })
})

function openProjectDropdown() {
  projectDropdownOpen.value = true
  if (!projectSearch.value && selectedProjectLabel.value) {
    projectSearch.value = selectedProjectLabel.value
  }
}

function closeProjectDropdown() {
  setTimeout(() => {
    projectDropdownOpen.value = false
    if (!projectSearch.value && selectedProjectLabel.value) {
      projectSearch.value = selectedProjectLabel.value
    }
  }, 150)
}

function selectProject(p: { id: number; name: string; code?: string }) {
  form.project_id = p.id
  projectSearch.value = p.code ? `${p.code} - ${p.name}` : p.name
  projectDropdownOpen.value = false
}

function clearProject() {
  form.project_id = null
  projectSearch.value = ''
}

/* ============ FILE ============ */
const fileName = computed(() => form.file?.name ?? '')

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  const file = target.files?.[0] ?? null
  form.file = file
}

/* ============ SNACKBAR (AUTO CLOSE) ============ */
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

/* ============ MODAL ============ */
const confirmOpen = ref(false)

const canSubmit = computed(() => !!form.project_id && !!form.file && !form.processing)

function openConfirm() {
  if (form.processing) return

  if (!form.project_id || !form.file) {
    showSnack('Project dan file wajib diisi sebelum submit.', 'error')
    return
  }

  confirmOpen.value = true
}

function closeConfirm() {
  if (form.processing) return
  confirmOpen.value = false
}

/* ============ SUBMIT ============ */
function doSubmit() {
  if (!form.project_id || !form.file) return

  form.post('/documents', {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      showSnack('Dokumen berhasil diupload.', 'success')
      router.visit('/documents')
    },
    onError: (errors) => {
      const msg =
        errors && Object.keys(errors).length
          ? Object.values(errors).flat().join(', ')
          : 'Gagal upload dokumen. Cek input atau server.'
      showSnack(String(msg), 'error', 4500)
    },
    onFinish: () => {
      confirmOpen.value = false
    },
  })
}

onMounted(() => {
  const msg = page?.props?.flash?.success
  if (msg) showSnack(String(msg), 'success')
})
</script>

<template>
  <AppLayout title="Upload Dokumen" currentPage="documents">
    <Head title="Upload Dokumen Deployment" />

    <div class="max-w-4xl mx-auto px-4 lg:px-0 py-2">
      <!-- Top bar -->
      <div class="mb-5 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <Link
            href="/documents"
            class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900"
          >
            <ArrowLeft class="w-4 h-4" />
            <span>Kembali</span>
          </Link>

          <span class="hidden sm:inline text-slate-300">•</span>

          <div class="hidden sm:flex items-center gap-2 text-sm text-slate-500">
            <span>Documents</span>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-medium">Upload</span>
          </div>
        </div>

        <div class="text-xs text-slate-500">
          <span class="text-red-500">*</span> wajib diisi
        </div>
      </div>

      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Card header -->
        <div class="p-6 bg-gradient-to-r from-slate-50 to-white border-b border-slate-200">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
              <h1 class="text-xl font-semibold text-slate-900">Upload Dokumen</h1>
              <p class="text-sm text-slate-500 mt-1">
                Upload dokumen project dengan mapping folder otomatis (opsional).
              </p>
            </div>
          </div>
        </div>

        <!-- Error global -->
        <div
          v-if="form.hasErrors"
          class="m-6 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800"
        >
          <div class="font-semibold mb-1">Ada data yang belum valid</div>
          <div class="text-xs text-red-700">Cek kembali input yang berwarna merah.</div>
        </div>

        <!-- Form body -->
        <form class="p-6 space-y-6" @submit.prevent="openConfirm">
          <!-- SECTION: Project -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-semibold text-slate-900">
                  Project <span class="text-red-500">*</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Wajib pilih project untuk upload dokumen.</p>
              </div>

              <button
                v-if="form.project_id"
                type="button"
                class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-700"
                @click="clearProject"
              >
                <X class="w-3.5 h-3.5" />
                Hapus pilihan
              </button>
            </div>

            <div class="relative">
              <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                <input
                  v-model="projectSearch"
                  type="text"
                  placeholder="Cari kode / nama project..."
                  class="input input-icon"
                  @focus="openProjectDropdown"
                  @blur="closeProjectDropdown"
                />
                <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
              </div>

              <div
                v-if="projectDropdownOpen"
                class="absolute z-20 mt-2 w-full max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg"
              >
                <template v-if="filteredProjects.length">
                  <button
                    v-for="p in filteredProjects"
                    :key="p.id"
                    type="button"
                    class="w-full text-left px-4 py-3 hover:bg-slate-50 flex flex-col border-b last:border-b-0 border-slate-100"
                    @mousedown.prevent="selectProject(p)"
                  >
                    <span class="font-medium text-slate-800">
                      {{ p.code ? `${p.code} - ${p.name}` : p.name }}
                    </span>
                    <span v-if="p.code" class="text-xs text-slate-500 mt-0.5">
                      Kode: {{ p.code }}
                    </span>
                  </button>
                </template>

                <div v-else class="px-4 py-3 text-sm text-slate-500">Project tidak ditemukan.</div>
              </div>

              <div
                v-if="!form.project_id"
                class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2"
              >
                Pilih Project dulu sebelum menyimpan dokumen.
              </div>

              <div v-if="form.errors.project_id" class="error">{{ form.errors.project_id }}</div>
            </div>
          </div>

          <div class="h-px bg-slate-200"></div>

          <!-- SECTION: Relasi -->
          <div class="space-y-3">
            <div>
              <h3 class="text-sm font-semibold text-slate-900">Relasi Data (Opsional)</h3>
              <p class="text-xs text-slate-500 mt-0.5">Isi jika dokumen terkait tabel/record tertentu.</p>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="label">Related Type</label>
                <input v-model="form.related_type" type="text" placeholder="contoh: vl_spk / vl_bast" class="input" />
                <div v-if="form.errors.related_type" class="error">{{ form.errors.related_type }}</div>
              </div>

              <div>
                <label class="label">Related ID</label>
                <input v-model="form.related_id" type="number" placeholder="contoh: 123" class="input" />
                <div v-if="form.errors.related_id" class="error">{{ form.errors.related_id }}</div>
              </div>
            </div>
          </div>

          <div class="h-px bg-slate-200"></div>

          <!-- SECTION: Jenis + Status -->
          <div class="space-y-3">
            <div>
              <h3 class="text-sm font-semibold text-slate-900">Jenis & Status (Opsional)</h3>
              <p class="text-xs text-slate-500 mt-0.5">Pakai mapping kalau ingin folder otomatis sesuai master.</p>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="label">Jenis Dokumen (Mapping)</label>
                <select v-model="form.doc_code" class="input">
                  <option value="">-- Pilih dari daftar --</option>
                  <option v-for="opt in props.docTypeOptions" :key="opt.code" :value="opt.code">
                    {{ opt.label }}
                  </option>
                </select>
                <p class="hint">Kalau diisi, sistem otomatis pakai label & folder sesuai pilihan.</p>
                <div v-if="form.errors.doc_code" class="error">{{ form.errors.doc_code }}</div>
              </div>

              <div>
                <label class="label">Jenis Dokumen (Custom)</label>
                <input v-model="form.document_type" type="text" placeholder="contoh: BA Hasil Test" class="input" />
                <p class="hint">Isi kalau tidak pakai mapping (doc_code dikosongkan).</p>
                <div v-if="form.errors.document_type" class="error">{{ form.errors.document_type }}</div>
              </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="label">Status</label>
                <select v-model="form.status_id" class="input">
                  <option :value="null">Pilih status</option>
                  <option v-for="st in props.statusOptions" :key="st.id" :value="st.id">
                    {{ st.name }}
                  </option>
                </select>
                <p class="hint">Contoh: DRAFT / VERIFIED / FINAL.</p>
                <div v-if="form.errors.status_id" class="error">{{ form.errors.status_id }}</div>
              </div>

              <div class="hidden md:block"></div>
            </div>
          </div>

          <div class="h-px bg-slate-200"></div>

          <!-- SECTION: File -->
          <div class="space-y-3">
            <div class="flex items-center justify-between gap-2">
              <div>
                <h3 class="text-sm font-semibold text-slate-900">
                  File Dokumen <span class="text-red-500">*</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Upload file yang akan disimpan ke sistem.</p>
              </div>

              <div
                v-if="fileName"
                class="text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-full px-3 py-1.5"
              >
                {{ fileName }}
              </div>
            </div>

            <label
              class="block rounded-2xl border border-dashed border-slate-300 bg-slate-50 hover:bg-slate-100 transition p-6 cursor-pointer"
            >
              <div class="flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-white border border-slate-200 flex items-center justify-center">
                  <FileUp class="w-5 h-5 text-slate-600" />
                </div>
                <div class="flex-1">
                  <div class="text-sm font-medium text-slate-800">Klik untuk pilih file</div>
                  <div class="text-xs text-slate-500 mt-1">
                    PDF / Image / Excel / Word / Text (sesuai aturan sistem)
                  </div>
                </div>
                <div class="text-xs text-slate-500">Browse</div>
              </div>

              <input type="file" class="hidden" @change="onFileChange" />
            </label>

            <div
              v-if="!form.file"
              class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2"
            >
              Pilih file dokumen dulu sebelum menyimpan.
            </div>

            <div v-if="form.errors.file" class="error">{{ form.errors.file }}</div>
          </div>

          <!-- Footer actions -->
          <div class="pt-2 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="text-xs text-slate-500">
              Dengan klik <b>Simpan Dokumen</b>, file akan diupload dan dicatat di sistem.
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto">
              <Link href="/documents" class="btn-secondary w-full sm:w-auto text-center">
                Batal
              </Link>

              <button type="submit" class="btn-primary w-full sm:w-auto" :disabled="!canSubmit">
                <span v-if="form.processing">Menyimpan...</span>
                <span v-else>Simpan Dokumen</span>
              </button>
            </div>
          </div>
        </form>
      </div>

      <div class="h-6"></div>
    </div>

    <!-- ✅ Confirm Modal -->
    <ConfirmDialog
      :show="confirmOpen"
      title="Simpan Dokumen?"
      message="Pastikan project & file sudah benar. Setelah disimpan, dokumen akan masuk ke daftar."
      confirmText="Ya, Simpan"
      cancelText="Batal"
      :loading="form.processing"
      @cancel="closeConfirm"
      @confirm="doSubmit"
    />

    <!-- ✅ Snackbar -->
    <Snackbar
      :show="snack.show"
      :message="snack.message"
      :type="snack.type"
      @close="closeSnack"
    />
  </AppLayout>
</template>

<style scoped>
.label {
  @apply block text-sm font-medium text-slate-700 mb-1;
}

.hint {
  @apply mt-1 text-xs text-slate-500;
}

.input {
  @apply w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm
    focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 outline-none transition;
}

.error {
  @apply mt-1 text-xs text-red-600;
}

.btn-primary {
  @apply inline-flex items-center justify-center px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold
    hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed transition;
}

.btn-secondary {
  @apply inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm font-medium
    hover:bg-slate-50 transition;
}

.input-icon {
  padding-left: 2.5rem;
  padding-right: 2.5rem;
}
</style>
