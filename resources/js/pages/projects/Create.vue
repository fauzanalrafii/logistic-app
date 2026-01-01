<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import { computed, reactive, ref } from 'vue'
import { UserPlus, CheckCircle, Loader2 } from 'lucide-vue-next'
import SearchSelect from '@/components/SearchSelect.vue'
import CustomDatePicker from '@/components/CustomDatePicker.vue'

import ConfirmDialog from '@/components/ConfirmDialog.vue'
import Snackbar from '@/components/Snackbar.vue'

const props = defineProps({
  planners: Array,
  authUser: Object,
})

const form = reactive({
  name: '',
  planner_id: (props.authUser as any)?.id ?? null,

  // ✅ wajib
  target_completion_date: '',

  // ✅ wajib
  project_type: '',

  // ✅ wajib
  kodepos_id: null as number | null,

  // ✅ wajib
  location: '',

  // ❌ optional
  description: '',
})

const kodeposOptions = ref<Array<{ id: number; label: string }>>([])
const isLoadingKodepos = ref(false)

const isSubmitting = ref(false)

// ===================
// MODAL + SNACKBAR STATE
// ===================
const confirmOpen = ref(false)

const snack = reactive({
  show: false,
  message: '',
  type: 'info' as 'success' | 'error' | 'info',
})

function showSnack(message: string, type: 'success' | 'error' | 'info' = 'info') {
  snack.show = true
  snack.message = message
  snack.type = type
}

function openConfirm() {
  confirmOpen.value = true
}
function closeConfirm() {
  confirmOpen.value = false
}

// ===================
// VALIDATION
// ===================
const errors = reactive<Record<string, string>>({
  name: '',
  planner_id: '',
  target_completion_date: '',
  project_type: '',
  kodepos_id: '',
  location: '',
})

function clearErrors() {
  Object.keys(errors).forEach((k) => (errors[k] = ''))
}

function validateForm() {
  clearErrors()

  const name = String(form.name ?? '').trim()
  const projectType = String(form.project_type ?? '').trim()
  const location = String(form.location ?? '').trim()
  const date = String(form.target_completion_date ?? '').trim()
  const plannerId = form.planner_id
  const kodeposId = form.kodepos_id

  if (!name) errors.name = 'Project Name wajib diisi.'
  if (!plannerId) errors.planner_id = 'Planner wajib dipilih.'
  if (!date) errors.target_completion_date = 'Target Completion Date wajib diisi.'
  if (!projectType) errors.project_type = 'Project Type wajib diisi.'
  if (!kodeposId) errors.kodepos_id = 'Area wajib dipilih.'
  if (!location) errors.location = 'Location wajib diisi.'

  return Object.values(errors).every((v) => !v)
}

const isFormValid = computed(() => {
  // realtime gate supaya tombol Save disable sebelum submit
  return (
    String(form.name ?? '').trim().length > 0 &&
    !!form.planner_id &&
    String(form.target_completion_date ?? '').trim().length > 0 &&
    String(form.project_type ?? '').trim().length > 0 &&
    !!form.kodepos_id &&
    String(form.location ?? '').trim().length > 0
  )
})

function tryOpenConfirm() {
  if (isSubmitting.value) return

  const ok = validateForm()
  if (!ok) {
    showSnack('Lengkapi semua field wajib terlebih dahulu.', 'error')
    return
  }

  openConfirm()
}

// ===================
// SUBMIT REAL (POST)
// ===================
function doSubmit() {
  if (isSubmitting.value) return

  // safety: sebelum submit juga validasi lagi
  const ok = validateForm()
  if (!ok) {
    showSnack('Lengkapi semua field wajib terlebih dahulu.', 'error')
    return
  }

  isSubmitting.value = true

  router.post('/projects', form, {
    preserveScroll: true,
    onSuccess: () => {
      showSnack('Project berhasil dibuat', 'success')
    },
    onError: (err: any) => {
      // kalau backend kirim error validation biasa, tetap fallback ini
      showSnack(err?.error ? String(err.error) : 'Gagal menyimpan project. Cek input / server.', 'error')
    },
    onFinish: () => {
      isSubmitting.value = false
    },
  })
}

// ===================
// REMOTE SEARCH KODEPOS
// ===================
async function searchKodepos(keyword: string) {
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
  } finally {
    isLoadingKodepos.value = false
  }
}
</script>

<template>
  <AppLayout currentPage="project.create">
    <!-- Error feedback dari server -->
    <div v-if="$page.props.errors?.error" class="text-red-600 mb-4 font-semibold">
      {{ $page.props.errors.error }}
    </div>

    <div class="w-full">
      <div class="mx-auto w-full max-w-6xl px-4">
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-8">
          <div class="flex items-center gap-3 mb-8">
            <UserPlus class="w-7 h-7 text-blue-600" />
            <h2 class="text-2xl font-bold text-gray-800">Create Project</h2>
          </div>

          <form @submit.prevent class="flex flex-col gap-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="label">
                  Project Name <span class="req">*</span>
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  class="input w-full"
                  :class="errors.name ? 'input-error' : ''"
                  @blur="validateForm"
                />
                <p v-if="errors.name" class="err">{{ errors.name }}</p>
              </div>

              <div>
                <SearchSelect
                  label="Planner"
                  placeholder="Search planner..."
                  :items="props.planners"
                  itemLabel="Name"
                  itemValue="ID"
                  v-model="form.planner_id"
                  @update:modelValue="validateForm"
                />
                <p v-if="errors.planner_id" class="err">{{ errors.planner_id }}</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="label">
                  Target Completion Date <span class="req">*</span>
                </label>
                <CustomDatePicker
                  v-model="form.target_completion_date"
                  placeholder="Select a date"
                  @update:modelValue="validateForm"
                />
                <p v-if="errors.target_completion_date" class="err">{{ errors.target_completion_date }}</p>
              </div>

              <div>
                <label class="label">
                  Project Type <span class="req">*</span>
                </label>
                <input
                  v-model="form.project_type"
                  type="text"
                  class="input w-full"
                  :class="errors.project_type ? 'input-error' : ''"
                  @blur="validateForm"
                />
                <p v-if="errors.project_type" class="err">{{ errors.project_type }}</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <!-- label Area di SearchSelect bawaanmu -->
                <SearchSelect
                  label="Area"
                  placeholder="Cari kodepos / kelurahan / kota..."
                  :items="kodeposOptions"
                  itemLabel="label"
                  itemValue="id"
                  v-model="form.kodepos_id"
                  remote
                  @search="searchKodepos"
                  @update:modelValue="validateForm"
                />
                <p class="hint" v-if="isLoadingKodepos">Mencari kodepos...</p>
                <p v-if="errors.kodepos_id" class="err">{{ errors.kodepos_id }}</p>

                <!-- kalau kamu mau label Area tampil bintang merah juga,
                     opsi paling aman: ubah label SearchSelect jadi "Area *" dari props.
                     tapi kamu minta bintang merah, jadi kita kasih catatan kecil ini -->
                <p class="text-xs text-slate-500 mt-1">
                  <span class="req">*</span> Area wajib dipilih
                </p>
              </div>

              <div>
                <label class="label">
                  Location <span class="req">*</span>
                </label>
                <input
                  v-model="form.location"
                  type="text"
                  class="input w-full"
                  :class="errors.location ? 'input-error' : ''"
                  @blur="validateForm"
                />
                <p v-if="errors.location" class="err">{{ errors.location }}</p>
              </div>
            </div>

            <div>
              <label class="label">Description</label>
              <textarea v-model="form.description" rows="4" class="input w-full"></textarea>
              <p class="text-xs text-slate-500 mt-1">Opsional</p>
            </div>

            <div class="flex justify-end gap-3 mt-4">
              <!-- ✅ tombol Save: disabled kalau form belum valid -->
              <button
                type="button"
                :disabled="isSubmitting || !isFormValid"
                @click="tryOpenConfirm"
                class="btn-save"
              >
                <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
                <CheckCircle v-else class="w-4 h-4" />
                {{ isSubmitting ? 'Saving...' : 'Save' }}
              </button>

              <button
                type="button"
                @click.prevent="router.visit('/projects')"
                class="btn-cancel"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ✅ Confirm Modal -->
    <ConfirmDialog
      :show="confirmOpen"
      title="Submit Project?"
      message="Pastikan data sudah benar. Setelah submit, project akan dibuat di sistem."
      confirmText="Ya, Submit"
      cancelText="Batal"
      :loading="isSubmitting"
      @cancel="closeConfirm"
      @confirm="
        () => {
          closeConfirm()
          doSubmit()
        }
      "
    />

    <!-- ✅ Snackbar -->
    <Snackbar
      :show="snack.show"
      :message="snack.message"
      :type="snack.type"
      @close="snack.show = false"
    />
  </AppLayout>
</template>

<style scoped>
.input {
  @apply border border-gray-200 rounded-xl shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500;
}

.label {
  @apply block text-sm font-semibold mb-1 text-slate-800;
}

.req {
  @apply text-red-600 font-bold;
}

.err {
  @apply mt-1 text-xs text-red-600;
}

.hint {
  @apply mt-1 text-xs text-slate-500;
}

.input-error {
  @apply border-red-400 focus:ring-red-200 focus:border-red-400;
}

.btn-save {
  @apply flex items-center gap-1 px-5 py-2 bg-blue-600 text-white rounded-lg
         hover:bg-blue-700 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-cancel {
  @apply px-5 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 shadow-sm;
}
</style>
