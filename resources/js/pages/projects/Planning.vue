<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { computed, ref, onMounted } from 'vue'
import { Plus, Trash2, Send, FileText, BarChart3, Save, Loader2 } from 'lucide-vue-next'
import axios from 'axios'

const props = defineProps<{
  project: any
  boqHeader: any | null
  bepProjection: any | null
  approvalInstance: any | null
  isSubmitted: boolean
  canRevise: boolean
  document: any | null
}>()

type BoqItemRow = {
  id?: number
  material_code: string
  material_description: string
  uom: string
  qty: number
  unit_price_estimate: number
  remarks: string
}

const activeTab = ref<'BOQ' | 'BEP'>('BOQ')
const isSaving = ref(false)
const isSubmitting = ref(false)
const saveMessage = ref('')

/** ===== BOQ HEADER (vl_boq_headers) ===== */
const boqType = ref<'ON_DESK' | 'ON_SITE'>('ON_DESK')
const boqVersion = ref<number>(1)
const boqStatus = ref<string>('DRAFT')

/** ===== BOQ ITEMS (vl_boq_items) ===== */
const boqRows = ref<BoqItemRow[]>([])

/** ===== BEP (vl_bep_projections) ===== */
const bepVersion = ref<number>(1)
const capex = ref<number>(0)
const opexEstimate = ref<number>(0)
const revenueEstimate = ref<number>(0)
const bepStatus = ref<string>('DRAFT')

// Load existing data on mount
onMounted(() => {
  if (props.boqHeader) {
    boqType.value = props.boqHeader.type ?? 'ON_DESK'
    boqVersion.value = props.boqHeader.version ?? 1
    boqStatus.value = props.boqHeader.status ?? 'DRAFT'
    boqRows.value = props.boqHeader.items?.map((item: any) => ({
      id: item.id,
      material_code: item.material_code ?? '',
      material_description: item.material_description ?? '',
      uom: item.uom ?? 'Unit',
      qty: item.qty ?? 0,
      unit_price_estimate: item.unit_price_estimate ?? 0,
      remarks: item.remarks ?? '',
    })) ?? []
  }

  if (props.bepProjection) {
    bepVersion.value = props.bepProjection.version ?? 1
    capex.value = props.bepProjection.capex ?? 0
    opexEstimate.value = props.bepProjection.opex_estimate ?? 0
    revenueEstimate.value = props.bepProjection.revenue_estimate ?? 0
    bepStatus.value = props.bepProjection.status ?? 'DRAFT'
  }

  // Add empty row if no items
  if (boqRows.value.length === 0) {
    addBoqRow()
  }
  
  if (props.document) {
    generatedPdfUrl.value = `/storage/${props.document.file_path}`
    documentGenerated.value = true
  }
})

function addBoqRow() {
  boqRows.value.push({
    material_code: '',
    material_description: '',
    uom: 'Unit',
    qty: 1,
    unit_price_estimate: 0,
    remarks: '',
  })
}

function removeBoqRow(i: number) {
  boqRows.value.splice(i, 1)
}

function safeNumber(n: any) {
  const x = Number(n)
  return Number.isFinite(x) ? x : 0
}

function formatRupiah(value: number) {
  return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(value)
}

const rowSubtotal = (r: BoqItemRow) =>
  safeNumber(r.qty) * safeNumber(r.unit_price_estimate)

const boqTotal = computed(() =>
  boqRows.value.reduce((sum, r) => sum + rowSubtotal(r), 0),
)

const bepMonths = computed(() => {
  const c = safeNumber(capex.value)
  const rev = safeNumber(revenueEstimate.value)
  const opx = safeNumber(opexEstimate.value)
  const net = rev - opx
  // Return immediately - if net <= 0, can't calculate BEP
  if (c === 0) return 0
  if (net <= 0) return Infinity
  return Math.ceil(c / net)
})

const bepMonthsDisplay = computed(() => {
  const months = bepMonths.value
  if (months === Infinity) return '∞ (Revenue ≤ OPEX)'
  return `${months} bulan`
})

const canSubmit = computed(() => {
  const hasBoqItems = boqRows.value.some(r => r.material_description.trim() !== '')
  const hasCapex = capex.value > 0
  // Can submit if not submitted, or if can revise (was rejected)
  return hasBoqItems && hasCapex && (!props.isSubmitted || props.canRevise)
})

const isReadOnly = computed(() => {
  // boleh edit kalau rejected
  if (props.approvalInstance?.status === 'REJECTED') return false

  // kalau sudah submit → read only
  return props.isSubmitted
})

const isApproved = computed(() => {
  return props.approvalInstance?.status === 'APPROVED'
})

const isRevising = ref(false)

const showGenerateModal = ref(false)
const isGenerating = ref(false)

// status dokumen
const documentGenerated = ref(false)
const generatedPdfUrl = ref<string | null>(null)

// form generate
const generateForm = ref({
  mitra: '',
  background: '',
  objective: '',
  benefit: '',
})

const canGenerate = computed(() => {
  // belum submit → tidak boleh
  if (!props.isSubmitted) return false

  // rejected → tidak boleh
  if (props.approvalInstance?.status === 'REJECTED') return false

  // 🔥 SUDAH PERNAH GENERATE → TIDAK BOLEH
  if (documentGenerated.value || generatedPdfUrl.value) return false

  return true
})

async function startRevise() {
  if (!confirm('Mulai revisi? Anda dapat mengedit BOQ dan BEP lalu submit ulang.')) return

  isRevising.value = true
  router.post('/projects/planning/revise', {
    project_id: props.project.id,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      saveMessage.value = 'Mode revisi aktif. Silakan edit dan submit ulang.'
    },
    onError: (errors) => {
      saveMessage.value = Object.values(errors).flat().join(', ')
    },
    onFinish: () => {
      isRevising.value = false
    }
  })
}

async function saveBoqDraft() {
  if (isReadOnly.value) return
  
  isSaving.value = true
  saveMessage.value = ''

  // Filter out empty rows
  const validItems = boqRows.value.filter(r => r.material_description.trim() !== '')
  
  if (validItems.length === 0) {
    saveMessage.value = 'Harap isi minimal 1 item BOQ.'
    isSaving.value = false
    return
  }

  router.post('/projects/planning/boq', {
    project_id: props.project.id,
    type: boqType.value,
    items: validItems,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      saveMessage.value = 'BOQ berhasil disimpan!'
    },
    onError: (errors) => {
      saveMessage.value = Object.values(errors).flat().join(', ')
    },
    onFinish: () => {
      isSaving.value = false
    }
  })
}

async function saveBepDraft() {
  if (isReadOnly.value) return
  
  isSaving.value = true
  saveMessage.value = ''

  router.post('/projects/planning/bep', {
    project_id: props.project.id,
    capex: capex.value,
    opex_estimate: opexEstimate.value,
    revenue_estimate: revenueEstimate.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      saveMessage.value = 'BEP berhasil disimpan!'
    },
    onError: (errors) => {
      saveMessage.value = Object.values(errors).flat().join(', ')
    },
    onFinish: () => {
      isSaving.value = false
    }
  })
}

async function saveDraft() {
  if (activeTab.value === 'BOQ') {
    await saveBoqDraft()
  } else {
    await saveBepDraft()
  }
}

async function submitPlanning() {
  if (!canSubmit.value) {
    saveMessage.value = 'Harap isi BOQ dan BEP sebelum submit.'
    return
  }

  if (!confirm('Apakah Anda yakin ingin submit planning ini? Setelah di-submit, data tidak dapat diubah.')) {
    return
  }

  isSubmitting.value = true
  saveMessage.value = 'Menyimpan data...'

  try {
    // Save BOQ first
    const boqItems = boqRows.value.filter(r => r.material_description.trim() !== '')
    if (boqItems.length > 0) {
      await new Promise<void>((resolve, reject) => {
        router.post('/projects/planning/boq', {
          project_id: props.project.id,
          type: boqType.value,
          items: boqItems,
        }, {
          preserveScroll: true,
          onSuccess: () => resolve(),
          onError: (errors) => reject(new Error(Object.values(errors).flat().join(', '))),
        })
      })
    }

    // Save BEP
    if (capex.value > 0) {
      await new Promise<void>((resolve, reject) => {
        router.post('/projects/planning/bep', {
          project_id: props.project.id,
          capex: capex.value,
          opex_estimate: opexEstimate.value,
          revenue_estimate: revenueEstimate.value,
        }, {
          preserveScroll: true,
          onSuccess: () => resolve(),
          onError: (errors) => reject(new Error(Object.values(errors).flat().join(', '))),
        })
      })
    }

    // Now submit
    saveMessage.value = 'Mengirim untuk approval...'
    router.post('/projects/planning/submit', {
      project_id: props.project.id,
    }, {
      onSuccess: () => {
        saveMessage.value = 'Planning berhasil di-submit!'
      },
      onError: (errors) => {
        saveMessage.value = Object.values(errors).flat().join(', ')
      },
      onFinish: () => {
        isSubmitting.value = false
      }
    })
  } catch (error: any) {
    saveMessage.value = error.message || 'Gagal menyimpan data'
    isSubmitting.value = false
  }
}

async function submitGenerate() {
  if (!generateForm.value.mitra) {
    alert('Mitra wajib diisi')
    return
  }

  isGenerating.value = true

  try {
    const res = await axios.post('/documents/boq-ondesk/generate', {
      project_id: props.project.id,
      ...generateForm.value,
    })

    generatedPdfUrl.value = res.data.pdf_url
    documentGenerated.value = true   // 🔥 INI PENTING
    showGenerateModal.value = false  // 🔥 MODAL TUTUP

    window.open(res.data.pdf_url, '_blank')
  } catch (e: any) {
    alert(e.response?.data?.message ?? 'Gagal generate dokumen')
  } finally {
    isGenerating.value = false
  }
}
</script>

<template>
  <AppLayout title="Perencanaan" currentPage="project.planOnDesk">
    <!-- Header -->
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between mb-5">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Perencanaan</h1>
        <p class="text-sm text-slate-500 mt-1 break-all">
          {{ props.project?.code ?? '-' }} — {{ props.project?.name ?? '-' }}
        </p>
        <div v-if="props.approvalInstance" class="mt-2 text-xs">
          <span class="font-semibold text-slate-600">Status Approval: </span>
          <span 
            class="font-medium" 
            :class="{
              'text-green-600': props.approvalInstance.status === 'APPROVED',
              'text-red-600': props.approvalInstance.status === 'REJECTED',
              'text-blue-600': props.approvalInstance.status === 'PENDING',
            }"
          >{{ props.approvalInstance.progress }}</span>
        </div>

        <!-- Rejection Banner -->
        <div v-if="props.approvalInstance?.is_rejected" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
          <div class="text-sm font-semibold text-red-700">Planning Ditolak</div>
          <div v-if="props.approvalInstance.rejection_reason" class="text-xs text-red-600 mt-1">
            Alasan: {{ props.approvalInstance.rejection_reason }}
          </div>
          <div class="text-xs text-red-600 mt-2">
            Silakan edit dan submit ulang.
          </div>
        </div>
      </div>

      <div class="flex flex-wrap gap-2 sm:justify-end">
        <!-- KEMBALI -->
        <Link
          href="/projects/plan-on-desk"
          class="px-3 py-1.5 text-xs rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50"
        >
          ← Kembali
        </Link>

        <!-- SAVE DRAFT -->
        <button
          v-if="!isReadOnly"
          type="button"
          @click="saveDraft"
          :disabled="isSaving"
          class="px-3 py-1.5 text-xs rounded-lg bg-slate-800 text-white hover:bg-slate-900 inline-flex items-center gap-1 disabled:opacity-50"
        >
          <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
          <Save v-else class="w-4 h-4" />
          Save Draft
        </button>

        <!-- SUBMIT -->
        <button
          v-if="!isReadOnly"
          type="button"
          @click="submitPlanning"
          :disabled="!canSubmit || isSubmitting"
          class="px-3 py-1.5 text-xs rounded-lg bg-blue-600 text-white hover:bg-blue-700 inline-flex items-center gap-1"
        >
          <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
          <Send v-else class="w-4 h-4" />
          Submit untuk Approval
        </button>

        <!-- GENERATE -->
        <button
          v-if="canGenerate"
          @click="showGenerateModal = true"
          class="px-3 py-1.5 text-xs rounded-lg bg-indigo-600 text-white"
        >
          Generate BOQ On Desk
        </button>

        <!-- LIHAT DOKUMEN -->
        <a
          v-if="generatedPdfUrl"
          :href="generatedPdfUrl"
          target="_blank"
          class="px-3 py-1.5 text-xs rounded-lg bg-green-600 text-white hover:bg-green-700"
        >
          Lihat Dokumen BOQ
        </a>
      </div>
    </div>

    <!-- Message -->
    <div v-if="saveMessage" class="mb-4 px-4 py-2 rounded-lg text-sm" :class="saveMessage.includes('berhasil') ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
      {{ saveMessage }}
    </div>

    <!-- Read Only Banner -->
    <div v-if="isReadOnly" class="mb-4 px-4 py-3 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm">
      <strong>Planning sudah di-submit.</strong> Data tidak dapat diubah lagi.
    </div>

    <!-- Tabs -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
      <div class="flex border-b bg-gray-50">
        <button
          class="px-4 py-3 text-sm font-semibold flex items-center gap-2 border-b-2"
          :class="activeTab === 'BOQ' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-600 hover:text-slate-800'"
          @click="activeTab = 'BOQ'"
          type="button"
        >
          <FileText class="w-4 h-4" />
          BOQ
        </button>

        <button
          class="px-4 py-3 text-sm font-semibold flex items-center gap-2 border-b-2"
          :class="activeTab === 'BEP' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-600 hover:text-slate-800'"
          @click="activeTab = 'BEP'"
          type="button"
        >
          <BarChart3 class="w-4 h-4" />
          Proyeksi BEP
        </button>
      </div>

      <!-- =========================
           TAB: BOQ
           ========================= -->
      <div v-if="activeTab === 'BOQ'" class="p-4 sm:p-6">
        <!-- BOQ HEADER (vl_boq_headers) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="text-xs font-semibold text-slate-500 uppercase">BOQ Header</div>

            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="label">Type</label>
                <select v-model="boqType" class="input h-10 w-full text-sm" :disabled="isReadOnly">
                  <option value="ON_DESK">ON_DESK</option>
                  <option value="ON_SITE">ON_SITE</option>
                </select>
              </div>

              <div>
                <label class="label">Status</label>
                <div class="px-3 py-2 bg-slate-100 rounded-md text-sm font-medium">
                  {{ boqStatus }}
                </div>
              </div>
            </div>
          </div>

          <!-- TOTAL -->
          <div class="bg-slate-50 border border-gray-200 rounded-xl p-4 lg:col-span-2">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div>
                <div class="text-xs font-semibold text-slate-500 uppercase">Total Cost Estimate</div>
                <div class="text-2xl font-bold text-slate-800 mt-1">
                  Rp {{ formatRupiah(boqTotal) }}
                </div>
              </div>

              <div v-if="!isReadOnly" class="flex gap-2">
                <button
                  type="button"
                  @click="addBoqRow"
                  class="px-3 h-10 rounded-md bg-slate-800 text-white text-sm hover:bg-slate-900 inline-flex items-center gap-1"
                >
                  <Plus class="w-4 h-4" />
                  Tambah Baris
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- BOQ ITEMS TABLE (vl_boq_items) -->
        <div class="mt-5 overflow-x-auto border border-gray-200 rounded-xl">
          <table class="min-w-[1100px] w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-[11px] font-bold border-b">
              <tr>
                <th class="px-3 py-2 text-left w-44">Material Code</th>
                <th class="px-3 py-2 text-left w-[420px]">Material Description</th>
                <th class="px-3 py-2 text-left w-24">UoM</th>
                <th class="px-3 py-2 text-right w-24">Qty</th>
                <th class="px-3 py-2 text-right w-40">Unit Price Estimate</th>
                <th class="px-3 py-2 text-right w-36">Subtotal</th>
                <th class="px-3 py-2 text-left w-56">Remarks</th>
                <th v-if="!isReadOnly" class="px-3 py-2 text-center w-16"></th>
              </tr>
            </thead>

            <tbody class="divide-y">
              <tr v-for="(row, i) in boqRows" :key="i" class="hover:bg-slate-50 align-top">
                <td class="px-3 py-2">
                  <input 
                    :value="row.material_code || '(Auto-generated)'" 
                    class="input h-9 w-full text-sm bg-slate-50 text-slate-500" 
                    readonly 
                  />
                </td>

                <td class="px-3 py-2">
                  <input v-model="row.material_description" class="input h-9 w-full text-sm" placeholder="Nama material / uraian" :readonly="isReadOnly" />
                </td>

                <td class="px-3 py-2">
                  <input v-model="row.uom" class="input h-9 w-full text-sm" placeholder="Unit/Meter" :readonly="isReadOnly" />
                </td>

                <td class="px-3 py-2 text-right">
                  <input v-model.number="row.qty" type="number" min="0" class="input h-9 w-24 text-sm text-right" :readonly="isReadOnly" />
                </td>

                <td class="px-3 py-2 text-right">
                  <input v-model.number="row.unit_price_estimate" type="number" min="0" class="input h-9 w-40 text-sm text-right" :readonly="isReadOnly" />
                </td>

                <td class="px-3 py-2 text-right font-semibold text-slate-800">
                  Rp {{ formatRupiah(rowSubtotal(row)) }}
                </td>

                <td class="px-3 py-2">
                  <input v-model="row.remarks" class="input h-9 w-full text-sm" placeholder="Catatan (opsional)" :readonly="isReadOnly" />
                </td>

                <td v-if="!isReadOnly" class="px-3 py-2 text-center">
                  <button
                    type="button"
                    class="text-red-600 hover:text-red-800 mt-1"
                    @click="removeBoqRow(i)"
                    title="Hapus baris"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </td>
              </tr>

              <tr v-if="!boqRows.length">
                <td :colspan="isReadOnly ? 7 : 8" class="px-4 py-8 text-center text-sm text-slate-400">
                  Belum ada baris BOQ.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- =========================
           TAB: BEP
           ========================= -->
      <div v-else class="p-4 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="text-base font-semibold text-slate-800">Proyeksi BEP</h2>
            <p class="text-xs text-slate-500 mt-1">
              Kalkulasi Break Even Point berdasarkan CAPEX, OPEX, dan Revenue.
            </p>
          </div>

          <div class="px-3 py-2 bg-slate-100 rounded-md text-sm font-medium">
            {{ bepStatus }}
          </div>
        </div>

        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="label">CAPEX (Investasi)</label>
            <input v-model.number="capex" type="number" min="0" class="input h-10 w-full text-sm" :readonly="isReadOnly" />
          </div>

          <div class="space-y-1">
            <label class="label">OPEX Estimate / bulan</label>
            <input v-model.number="opexEstimate" type="number" min="0" class="input h-10 w-full text-sm" :readonly="isReadOnly" />
          </div>

          <div class="space-y-1">
            <label class="label">Revenue Estimate / bulan</label>
            <input v-model.number="revenueEstimate" type="number" min="0" class="input h-10 w-full text-sm" :readonly="isReadOnly" />
          </div>

          <div class="space-y-1">
            <label class="label">BEP Months (auto)</label>
            <input :value="bepMonthsDisplay" readonly class="input h-10 w-full text-sm bg-slate-50" />
          </div>
        </div>

        <div class="mt-4 p-4 rounded-xl border bg-slate-50">
          <div class="text-[11px] text-slate-500 font-semibold uppercase">Ringkasan</div>
          <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
            <div>
              <div class="text-[11px] text-slate-500">CAPEX</div>
              <div class="font-bold text-slate-800">Rp {{ formatRupiah(safeNumber(capex)) }}</div>
            </div>
            <div>
              <div class="text-[11px] text-slate-500">Net / bulan</div>
              <div class="font-bold text-slate-800">
                Rp {{ formatRupiah(Math.max(0, safeNumber(revenueEstimate) - safeNumber(opexEstimate))) }}
              </div>
            </div>
            <div>
              <div class="text-[11px] text-slate-500">BEP</div>
              <div class="font-bold text-slate-800">{{ bepMonthsDisplay }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div
      v-if="showGenerateModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    >
      <div class="bg-white w-full max-w-lg rounded-xl p-5">
        <h2 class="text-lg font-bold mb-4">Generate BOQ On Desk</h2>

        <div class="space-y-3">
          <div>
            <label class="label">Mitra *</label>
            <input v-model="generateForm.mitra" class="input w-full" />
          </div>

          <div>
            <label class="label">Latar Belakang</label>
            <textarea v-model="generateForm.background" class="input w-full"></textarea>
          </div>

          <div>
            <label class="label">Tujuan</label>
            <textarea v-model="generateForm.objective" class="input w-full"></textarea>
          </div>

          <div>
            <label class="label">Manfaat</label>
            <textarea v-model="generateForm.benefit" class="input w-full"></textarea>
          </div>
        </div>

        <div class="mt-5 flex justify-end gap-2">
          <button
            @click="showGenerateModal = false"
            class="px-3 py-1.5 text-xs border rounded-lg"
          >
            Batal
          </button>

          <button
            @click="submitGenerate"
            :disabled="isGenerating"
            class="px-3 py-1.5 text-xs bg-indigo-600 text-white rounded-lg"
          >
            Generate
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.input {
  @apply border border-gray-300 rounded-md shadow-sm px-3 py-2
         focus:ring-blue-500 focus:border-blue-500;
}
.label {
  @apply text-xs font-semibold text-slate-600;
}
</style>
