<script setup lang="ts">

import AppLayout from "@/layouts/AppLayout.vue"
import ConfirmDialog from "@/components/ConfirmDialog.vue"
import { Head, Link, router, usePage } from "@inertiajs/vue3"
import { computed, ref, onMounted, watch } from "vue"
import {
  Send,
  FileText,
  BarChart3,
  Save,
  Loader2,
  User,
  Briefcase,
  MapPin,
  Calendar,
  FileCode,
  AlignLeft,
  XCircle,
  MessageSquare,
} from "lucide-vue-next"
import BoqTable from "@/components/BoqTable.vue"
import BepForm from "@/components/BepForm.vue"
import DocumentList from "@/components/DocumentList.vue"
import TimelineHistory from "@/components/TimelineHistory.vue"
import { useDarkMode } from "@/composables/useDarkMode"
import { useToast } from "@/composables/useToast"
import axios from "axios"

const showGenerateModal = ref(false)
const isGenerating = ref(false)

const generateForm = ref({
  mitra: '',
  background: '',
  objective: '',
  benefit: '',
})

const documentGenerated = computed(() => {
  return (props.documents ?? []).some(
    (d: any) => d.document_type === 'KELENGKAPAN_SPK_SURVEY'
  )
})
const planningStatus = computed(() => {
  return props.planning?.status?.name ?? props.planning?.status ?? null
})

const canGenerate = computed(() => {
  if (!props.planning) return false
  if (['DRAFT', 'REJECTED'].includes(planningStatus.value)) return false
  if (documentGenerated.value) return false
  return true
})

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

    showGenerateModal.value = false

    // refresh dokumen
    router.reload({ only: ['documents'] })

    window.open(res.data.pdf_url, '_blank')
  } catch (e: any) {
    alert(e.response?.data?.message ?? 'Gagal generate dokumen')
  } finally {
    isGenerating.value = false
  }
}
const page = usePage()
const { success, error, info } = useToast()
const { isDark } = useDarkMode()

const hasPermission = (permission: string) => {
  return (page.props.auth as any)?.permissions?.includes(permission)
}

const props = defineProps<{
  project: any
  statusOptions: any[]
  currentPage?: string
  planning?: any | null
  approvals?: any[] | null
  boqHeader?: any | null
  bepProjection?: any | null
  isSubmitted?: boolean
  canRevise?: boolean
  documents?: any[]
}>()

const STATUS_FLOW = [
  "PLAN ON DESK",
  "SPK SURVEY",
  "SURVEY DRM",
  "IMPLEMENTASI",
  "KONSTRUKSI",
  "GO LIVE",
  "HANDOVER",
  "CLOSED",
]

function normalizeStatusName(name?: string | null) {
  const n = String(name ?? "").toUpperCase().trim()
  if (n === "SPK IMPLEMENTASI") return "IMPLEMENTASI"
  if (n === "SURVEY") return "SURVEY DRM"
  if (n === "GO-LIVE") return "GO LIVE"
  return n
}

function normalizeApprovalStatus(st?: string | null) {
  return String(st ?? "").toUpperCase().trim()
}

function formatDateTime(value?: string | null) {
  if (!value) return "-"
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return value as string
  return d.toLocaleString("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
  })
}

const activeStatusName = computed(() =>
  normalizeStatusName(props.project.status?.name ?? "")
)

const projectAging = computed(() => {
  if (!props.project.created_at) return "-"
  const created = new Date(props.project.created_at)
  const now = new Date()
  const diffTime = Math.abs(now.getTime() - created.getTime())
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

  if (diffDays < 30) return `${diffDays} hari`
  const months = Math.floor(diffDays / 30)
  const days = diffDays % 30
  return days > 0 ? `${months} bulan ${days} hari` : `${months} bulan`
})

const daysUntilTarget = computed(() => {
  if (!props.project.target_completion_date) return null
  const target = new Date(props.project.target_completion_date)
  const now = new Date()
  const diffTime = target.getTime() - now.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

  if (diffDays < 0) return `Terlambat ${Math.abs(diffDays)} hari`
  if (diffDays === 0) return "Hari ini"
  if (diffDays < 30) return `H-${diffDays} hari`

  const months = Math.floor(diffDays / 30)
  const days = diffDays % 30
  if (days === 0) return `H-${months} bulan`
  return `H-${months} bulan ${days} hari`
})

const progressBarColor = computed(() => {
  const statusIndex = STATUS_FLOW.indexOf(activeStatusName.value)

  const singleColorGradients = [
    "from-blue-400 via-blue-500 to-blue-600",
    "from-sky-400 via-sky-500 to-sky-600",
    "from-purple-400 via-purple-500 to-purple-600",
    "from-indigo-400 via-indigo-500 to-indigo-600",
    "from-cyan-400 via-cyan-500 to-cyan-600",
    "from-teal-400 via-teal-500 to-teal-600",
    "from-emerald-400 via-emerald-500 to-emerald-600",
    "from-green-400 via-green-500 to-green-600",
  ]

  const allColors = [
    "blue-500",
    "sky-500",
    "purple-500",
    "indigo-500",
    "cyan-500",
    "teal-500",
    "emerald-500",
    "green-500",
  ]

  const currentColors = allColors.slice(0, statusIndex + 1)

  if (currentColors.length === 1) {
    return singleColorGradients[statusIndex]
  }

  if (currentColors.length === 2) {
    return `from-${currentColors[0]} to-${currentColors[1]}`
  }

  const from = `from-${currentColors[0]}`
  const to = `to-${currentColors[currentColors.length - 1]}`
  const vias = currentColors.slice(1, -1).map((c) => `via-${c}`).join(" ")
  return `${from} ${vias} ${to}`
})

const infoGridItems = computed(() => [
  { icon: Briefcase, label: "Project Type", value: props.project.project_type || "-" },
  { icon: MapPin, label: "Area", value: props.project.area || "-" },
  { icon: MapPin, label: "Location", value: props.project.location || "-" },
  {
    icon: Calendar,
    label: "Target Date",
    value: props.project.target_completion_date || "-",
    extra: daysUntilTarget.value,
  },
])

const sortedHistories = computed(() => {
  const histories = props.project.status_histories ?? []
  const lastByStatus: Record<string, any> = {}

  for (const h of histories) {
    const nameRaw = h.new_status_data?.name
    if (!nameRaw) continue
    const name = normalizeStatusName(nameRaw)
    if (!STATUS_FLOW.includes(name)) continue

    const changedAt = h.changed_at ?? null
    const changer = h.changer?.Name ?? "-"
    const note = h.note ?? null

    const existing = lastByStatus[name]
    if (
      !existing ||
      (changedAt &&
        new Date(changedAt).getTime() >
          new Date(existing.changed_at || 0).getTime())
    ) {
      lastByStatus[name] = { changed_at: changedAt, changer, note }
    }
  }

  const result: any[] = []
  STATUS_FLOW.forEach((statusName, i) => {
    const info = lastByStatus[statusName]
    if (info)
      result.push({
        idx: i + 1,
        name: statusName,
        ...info,
        isActive: statusName === activeStatusName.value,
      })
  })

  if (!result.length && props.project.status?.name) {
    const name = normalizeStatusName(props.project.status.name)
    result.push({
      idx: STATUS_FLOW.indexOf(name) + 1,
      name,
      changed_at: props.project.created_at,
      changer: props.project.planner?.Name ?? "-",
      note: null,
      isActive: true,
    })
  }
  return result
})

const planningOpen = ref(true)

const statusRelatedTypeMap: Record<string, string> = {
  "PLAN ON DESK": "planning",
  "SPK SURVEY": "spk_survey",
  "SURVEY DRM": "survey_drm",
  IMPLEMENTASI: "implementasi",
  KONSTRUKSI: "konstruksi",
  "GO LIVE": "go_live",
  HANDOVER: "handover",
}

const timelineItems = computed(() => {
  const items: any[] = []
  for (const h of sortedHistories.value) {
    items.push({
      type: "status",
      id: `status-${h.idx}`,
      ...h,
      label: h.name,
      subLabel: formatDateTime(h.changed_at),
      extra: `Oleh: ${h.changer}`,
    })

    const relatedType = statusRelatedTypeMap[h.name]
    const approval = props.approvals?.find((a: any) => a.related_type === relatedType)
    if (approval) {
      items.push({
        type: "approval",
        id: `approval-${approval.id}`,
        label: approval.flow?.name || "Approval",
        status: approval.status,
        approvalId: approval.id,
      })

      if (planningOpen.value && approval.flow?.steps) {
        for (const s of approval.flow.steps) {
          const action = approval.actions?.find((a: any) => a.step_id === s.id)
          items.push({
            type: "step",
            id: `step-${s.id}`,
            label: s.name,
            number: s.order,
            stepType:
              action?.action === "APPROVE"
                ? "APPROVE"
                : action?.action === "REJECT"
                  ? "REJECT"
                  : approval.current_step?.id === s.id
                    ? "CURRENT"
                    : "WAITING",
            actedAt: action?.acted_at ? formatDateTime(action.acted_at) : null,
            actedBy: action?.user_name,
            comment: action?.comment,
          })
        }
      }
    }
  }
  return items
})

/**
 * ✅ CARD REJECT (GLOBAL)
 * - tampil kalau ada approval yang status REJECTED
 * - ambil action REJECT paling terakhir
 */
const rejectedCommentCard = computed(() => {
  const approvals = props.approvals ?? []
  if (!approvals.length) return null

  // ambil approval yang memang REJECTED
  const rejectedApprovals = approvals.filter((a: any) => normalizeApprovalStatus(a?.status) === "REJECTED")
  if (!rejectedApprovals.length) return null

  // cari action REJECT paling baru dari semua rejected approvals
  let latest: any = null

  for (const ap of rejectedApprovals) {
    const actions = ap?.actions ?? []
    const rejectActions = actions.filter((x: any) => String(x?.action ?? "").toUpperCase() === "REJECT")
    for (const ra of rejectActions) {
      if (!latest) {
        latest = { approval: ap, action: ra }
        continue
      }
      const t1 = new Date(ra?.acted_at ?? 0).getTime()
      const t2 = new Date(latest.action?.acted_at ?? 0).getTime()
      if (t1 > t2) latest = { approval: ap, action: ra }
    }
  }

  if (!latest) return null

  const approval = latest.approval
  const action = latest.action

  // cari nama step dari flow steps
  const stepName =
    approval?.flow?.steps?.find((s: any) => s.id === action?.step_id)?.name ??
    approval?.current_step?.name ??
    "-"

  return {
    flowName: approval?.flow?.name ?? "Approval",
    stepName,
    comment: action?.comment ?? "",
    actedBy: action?.user_name ?? "-",
    actedAt: action?.acted_at ? formatDateTime(action.acted_at) : "-",
  }
})

type BoqItemRow = {
  id?: number
  material_code: string
  material_description: string
  uom: string
  qty: number
  unit_price_estimate: number
  remarks: string
}

const activeTab = ref<"planning" | "documents" | "history">("planning")
const activePlanningTab = ref<"BOQ" | "BEP">("BOQ")

const isSaving = ref(false)
const isSubmitting = ref(false)

// masih boleh dipakai kalau kamu mau banner
const saveMessage = ref("")

const boqType = ref<"ON_DESK" | "ON_SITE">("ON_DESK")
const boqVersion = ref<number>(1)
const boqRows = ref<BoqItemRow[]>([])
const capex = ref<number>(0)
const opexEstimate = ref<number>(0)
const revenueEstimate = ref<number>(0)

function initPlanningData() {
  if (props.boqHeader) {
    boqType.value = props.boqHeader.type ?? "ON_DESK"
    boqVersion.value = props.boqHeader.version ?? 1
    boqRows.value =
      props.boqHeader.items?.map((item: any) => ({
        id: item.id,
        material_code: item.material_code ?? "",
        material_description: item.material_description ?? "",
        uom: item.uom ?? "Unit",
        qty: item.qty ?? 0,
        unit_price_estimate: item.unit_price_estimate ?? 0,
        remarks: item.remarks ?? "",
      })) ?? []
  }

  if (props.bepProjection) {
    capex.value = props.bepProjection.capex ?? 0
    opexEstimate.value = props.bepProjection.opex_estimate ?? 0
    revenueEstimate.value = props.bepProjection.revenue_estimate ?? 0
  }

  if (boqRows.value.length === 0) addBoqRow()
}

watch(() => props.project.id, initPlanningData)
onMounted(initPlanningData)

function addBoqRow() {
  boqRows.value.push({
    material_code: "",
    material_description: "",
    uom: "Unit",
    qty: 1,
    unit_price_estimate: 0,
    remarks: "",
  })
}

function removeBoqRow(i: number) {
  boqRows.value.splice(i, 1)
}

const isReadOnly = computed(() => props.isSubmitted && !props.canRevise)
const canEditProject = computed(() => !props.isSubmitted)

// ===============================
// ✅ MODAL (Draft & Submit)
// ===============================
const draftModalOpen = ref(false)
const submitModalOpen = ref(false)

function openDraftModal() {
  if (isReadOnly.value) return
  draftModalOpen.value = true
}

function closeDraftModal(force = false) {
  if (isSaving.value && !force) return
  draftModalOpen.value = false
}

function openSubmitModal() {
  if (isReadOnly.value) return
  submitModalOpen.value = true
}

function closeSubmitModal(force = false) {
  if (isSubmitting.value && !force) return
  submitModalOpen.value = false
}

// ===============================
// ✅ ACTIONS: Save Draft
// ===============================
function validateDraft() {
  if (activePlanningTab.value === "BOQ") {
    const validItems = boqRows.value.filter((r) => r.material_description.trim() !== "")
    if (validItems.length === 0) return "Harap isi minimal 1 item BOQ."
  } else {
    // BEP draft boleh 0
  }
  return null
}

function saveDraftConfirmed() {
  const errMsg = validateDraft()
  if (errMsg) {
    error(errMsg)
    return
  }

  isSaving.value = true
  saveMessage.value = ""

  if (activePlanningTab.value === "BOQ") {
    const validItems = boqRows.value.filter((r) => r.material_description.trim() !== "")
    router.post(
      "/projects/planning/boq",
      { project_id: props.project.id, type: boqType.value, items: validItems },
      {
        preserveScroll: true,
        onSuccess: () => {
          success("BOQ draft berhasil disimpan.")
          closeDraftModal(true)
        },
        onError: (errs: any) => {
          const msg = Object.values(errs).flat().join(", ") || "Gagal simpan draft BOQ."
          error(String(msg))
        },
        onFinish: () => {
          isSaving.value = false
        },
      }
    )
  } else {
    router.post(
      "/projects/planning/bep",
      {
        project_id: props.project.id,
        capex: capex.value,
        opex_estimate: opexEstimate.value,
        revenue_estimate: revenueEstimate.value,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          success("BEP draft berhasil disimpan.")
          closeDraftModal(true)
        },
        onError: (errs: any) => {
          const msg = Object.values(errs).flat().join(", ") || "Gagal simpan draft BEP."
          error(String(msg))
        },
        onFinish: () => {
          isSaving.value = false
        },
      }
    )
  }
}

// ===============================
// ✅ ACTIONS: Submit Planning
// ===============================
function validateSubmit() {
  const hasBoqItems = boqRows.value.some((r) => r.material_description.trim() !== "")
  const hasCapex = capex.value > 0
  if (!hasBoqItems || !hasCapex) return "Harap isi BOQ dan BEP sebelum submit."
  return null
}

async function submitPlanningConfirmed() {
  const errMsg = validateSubmit()
  if (errMsg) {
    error(errMsg)
    return
  }

  isSubmitting.value = true
  saveMessage.value = ""
  info("Memproses submit planning...")

  try {
    const boqItems = boqRows.value.filter((r) => r.material_description.trim() !== "")

    if (boqItems.length > 0) {
      await new Promise<void>((resolve, reject) => {
        router.post(
          "/projects/planning/boq",
          { project_id: props.project.id, type: boqType.value, items: boqItems },
          {
            preserveScroll: true,
            onSuccess: () => resolve(),
            onError: (e: any) => reject(new Error(Object.values(e).flat().join(", "))),
          }
        )
      })
    }

    if (capex.value > 0) {
      await new Promise<void>((resolve, reject) => {
        router.post(
          "/projects/planning/bep",
          {
            project_id: props.project.id,
            capex: capex.value,
            opex_estimate: opexEstimate.value,
            revenue_estimate: revenueEstimate.value,
          },
          {
            preserveScroll: true,
            onSuccess: () => resolve(),
            onError: (e: any) => reject(new Error(Object.values(e).flat().join(", "))),
          }
        )
      })
    }

    router.post(
      "/projects/planning/submit",
      { project_id: props.project.id },
      {
        preserveScroll: true,
        onSuccess: () => {
          success("Planning berhasil di-submit!")
          closeSubmitModal(true)
        },
        onError: (e: any) => {
          const msg = Object.values(e).flat().join(", ") || "Submit gagal."
          error(String(msg))
        },
        onFinish: () => {
          isSubmitting.value = false
        },
      }
    )
  } catch (e: any) {
    error(e?.message || "Gagal menyimpan data")
    isSubmitting.value = false
  }
}

// Called by TimelineHistory
function findApprovalByProcessType(relatedType: string) {
  return props.approvals?.find((a: any) => a.related_type === relatedType)
}
</script>

<template>
  <Head :title="`Detail Proyek - ${props.project?.name ?? ''}`" />

  <AppLayout title="Detail Proyek" :currentPage="currentPage || 'project.list'">
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-6">
      <!-- Header Card with Gradient -->
      <div :class="[
        'rounded-3xl overflow-hidden shadow-lg border transition-colors duration-300',
        isDark ? 'border-slate-700 bg-slate-800' : 'border-slate-200/50 bg-white'
      ]">
        <!-- Top Gradient Banner -->
        <div class="relative h-32 bg-gradient-to-r from-rose-500 via-pink-500 to-orange-400 p-6">
          <!-- Progress Overlay -->
          <div class="absolute inset-0 bg-black/10"></div>
          
          <!-- Project Name & Code on Banner -->
          <div class="relative z-10">
            <div class="flex items-center gap-2 text-white/80 text-sm mb-2">
              <FileCode class="w-4 h-4" />
              <span class="font-mono font-medium">{{ project.code }}</span>
            </div>
            <h1 class="text-2xl lg:text-3xl font-bold text-white drop-shadow-sm">{{ project.name }}</h1>
          </div>

          <!-- Progress Badge -->
          <div class="absolute top-6 right-6 text-right">
            <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm rounded-full px-4 py-2">
              <span class="text-2xl font-bold text-white">
                {{ Math.round(((STATUS_FLOW.indexOf(activeStatusName) + 1) / STATUS_FLOW.length) * 100) }}%
              </span>
            </div>
            <div class="mt-2">
              <span class="px-3 py-1 bg-white/25 backdrop-blur-sm text-white text-xs font-bold uppercase rounded-full">
                {{ activeStatusName }}
              </span>
            </div>
          </div>
        </div>

        <!-- Info Section Below Banner -->
        <div class="p-6">
          <!-- Meta Info -->
          <div class="flex flex-wrap items-center gap-4 mb-6">
            <div :class="['flex items-center gap-2 text-sm', isDark ? 'text-slate-300' : 'text-slate-600']">
              <div :class="['w-8 h-8 rounded-full flex items-center justify-center', isDark ? 'bg-slate-700' : 'bg-slate-100']">
                <User class="w-4 h-4 text-blue-500" />
              </div>
              <span>{{ project.planner?.Name || '-' }}</span>
            </div>
            <div :class="['flex items-center gap-2 text-sm', isDark ? 'text-slate-300' : 'text-slate-600']">
              <div :class="['w-8 h-8 rounded-full flex items-center justify-center', isDark ? 'bg-slate-700' : 'bg-slate-100']">
                <Calendar class="w-4 h-4 text-emerald-500" />
              </div>
              <span>{{ projectAging }}</span>
            </div>

            <Link
              v-if="canEditProject && hasPermission('project.edit')"
              :href="`/projects/${project.id}/edit`"
              class="ml-auto px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-full hover:bg-blue-700 transition-colors"
            >
              Edit Proyek →
            </Link>
          </div>

          <!-- Info Grid Cards -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div 
              v-for="(item, idx) in infoGridItems" 
              :key="idx"
              :class="[
                'rounded-2xl p-4 border transition-colors duration-300',
                isDark ? 'bg-slate-700/50 border-slate-600' : 'bg-slate-50 border-slate-100'
              ]"
            >
              <div class="flex items-center gap-2 mb-2">
                <div :class="[
                  'w-8 h-8 rounded-xl flex items-center justify-center',
                  idx === 0 ? 'bg-blue-100 text-blue-600' : 
                  idx === 1 ? 'bg-emerald-100 text-emerald-600' : 
                  idx === 2 ? 'bg-purple-100 text-purple-600' : 
                  'bg-orange-100 text-orange-600'
                ]">
                  <component :is="item.icon" class="w-4 h-4" />
                </div>
                <span :class="['text-xs font-semibold uppercase tracking-wide', isDark ? 'text-slate-400' : 'text-slate-500']">
                  {{ item.label }}
                </span>
              </div>
              <div :class="['text-sm font-semibold', isDark ? 'text-white' : 'text-slate-800']">{{ item.value }}</div>
              <div
                v-if="item.extra"
                class="text-xs font-semibold mt-1"
                :class="item.extra.includes('Terlambat') ? 'text-red-500' : (isDark ? 'text-slate-400' : 'text-slate-500')"
              >
                {{ item.extra }}
              </div>
            </div>
          </div>

          <!-- Description -->
          <div v-if="project.description" :class="['mt-6 pt-6 border-t', isDark ? 'border-slate-700' : 'border-slate-100']">
            <div :class="['flex items-center gap-2 text-xs font-semibold uppercase tracking-wide mb-2', isDark ? 'text-slate-400' : 'text-slate-500']">
              <AlignLeft class="w-3.5 h-3.5" />
              Description
            </div>
            <div :class="['text-sm leading-relaxed rounded-xl p-4', isDark ? 'bg-slate-700/50 text-slate-300' : 'bg-slate-50 text-slate-700']">
              {{ project.description }}
            </div>
          </div>
        </div>
      </div>

      <!-- ✅ GLOBAL: Card Reject (muncul di semua tab) -->
      <div
        v-if="rejectedCommentCard"
        :class="[
          'rounded-2xl p-4 border transition-colors duration-300',
          isDark ? 'bg-red-900/30 border-red-800' : 'bg-red-50 border-red-200'
        ]"
      >
        <div class="flex items-start gap-3">
          <div class="mt-0.5">
            <XCircle class="w-5 h-5 text-red-600" />
          </div>

          <div class="flex-1">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-sm font-bold text-red-700">
                  Planning Ditolak
                </div>
                <div class="text-xs text-red-600 mt-0.5">
                  {{ rejectedCommentCard.flowName }} • Step: {{ rejectedCommentCard.stepName }}
                </div>
              </div>

              <span class="px-2 py-1 rounded-lg text-[11px] font-bold bg-red-100 text-red-700">
                REJECTED
              </span>
            </div>

            <div class="mt-3 bg-white border border-red-100 rounded-lg p-3">
              <div class="flex items-center gap-2 text-xs font-semibold text-slate-600 mb-2">
                <MessageSquare class="w-4 h-4 text-red-600" />
                Komentar Reject
              </div>

              <div class="text-sm text-slate-800 leading-relaxed whitespace-pre-line">
                {{ rejectedCommentCard.comment || "-" }}
              </div>

              <div class="mt-2 text-xs text-slate-500">
                Oleh:
                <span class="font-medium text-slate-700">{{ rejectedCommentCard.actedBy }}</span>
                •
                <span class="font-medium text-slate-700">{{ rejectedCommentCard.actedAt }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ✅ Unified Content Card with Tabs -->
      <div :class="['rounded-3xl border shadow-sm transition-colors duration-300', isDark ? 'bg-slate-800 border-slate-700' : 'bg-white border-slate-200']">
        <!-- Tabs Header -->
        <div :class="['border-b px-6', isDark ? 'border-slate-700' : 'border-slate-100']">
          <div class="flex gap-6">
            <button
              @click="activeTab = 'planning'"
              :class="[
                'py-4 text-sm font-semibold border-b-2 transition-all',
                activeTab === 'planning' 
                  ? 'text-blue-500 border-blue-500' 
                  : (isDark ? 'text-slate-400 border-transparent hover:text-slate-200' : 'text-slate-500 border-transparent hover:text-slate-800')
              ]"
            >
              Planning BOQ & BEP
            </button>
            <button
              @click="activeTab = 'documents'"
              :class="[
                'py-4 text-sm font-semibold border-b-2 transition-all',
                activeTab === 'documents' 
                  ? 'text-blue-500 border-blue-500' 
                  : (isDark ? 'text-slate-400 border-transparent hover:text-slate-200' : 'text-slate-500 border-transparent hover:text-slate-800')
              ]"
            >
              Dokumen Terkait
            </button>
            <button
              @click="activeTab = 'history'"
              :class="[
                'py-4 text-sm font-semibold border-b-2 transition-all',
                activeTab === 'history' 
                  ? 'text-blue-500 border-blue-500' 
                  : (isDark ? 'text-slate-400 border-transparent hover:text-slate-200' : 'text-slate-500 border-transparent hover:text-slate-800')
              ]"
            >
              Riwayat & Timeline
            </button>
          </div>
        </div>

        <!-- Content Area -->
        <div class="p-6">
          <!-- Planning Tab -->
          <div v-show="activeTab === 'planning'">
            <div v-if="!isReadOnly" class="flex items-center justify-between mb-6">
              <div class="flex flex-col">
                <h2 :class="['text-lg font-bold', isDark ? 'text-white' : 'text-slate-900']">
                  {{ activePlanningTab === 'BOQ' ? 'Bill of Quantities' : 'Break Even Point' }}
                </h2>
                <p :class="['text-xs', isDark ? 'text-slate-400' : 'text-slate-500']">
                  Kelola estimasi biaya dan perencanaan proyek
                </p>
              </div>

              <div class="flex gap-2">
            <button
              type="button"
              @click="openDraftModal"
              :disabled="isSaving || isSubmitting"
              :class="[
                'px-4 py-2 text-sm rounded-xl disabled:opacity-50 flex items-center gap-1.5 transition-colors font-medium',
                isDark ? 'bg-slate-700 text-slate-200 hover:bg-slate-600' : 'bg-slate-100 hover:bg-slate-200'
              ]"
            >
              <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
              <Save v-else class="w-4 h-4" />
              Draft
            </button>

            <button
              type="button"
              @click="openSubmitModal"
              :disabled="isSubmitting || isSaving"
              class="px-4 py-2 text-sm bg-blue-600 text-white rounded-xl hover:bg-blue-700 disabled:opacity-50 flex items-center gap-1.5 font-medium"
            >
              <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
              <Send v-else class="w-4 h-4" />
              Submit
            </button>
          </div>
        </div>

        <div :class="[
          'rounded-2xl border overflow-hidden transition-colors duration-300',
          isDark ? 'bg-slate-700/50 border-slate-600' : 'bg-white border-slate-200'
        ]">
          <div v-if="isReadOnly" :class="['m-4 p-3 rounded-xl text-sm', isDark ? 'bg-amber-900/30 text-amber-200 border border-amber-800' : 'bg-amber-50 text-amber-900 border border-amber-200']">
            <strong>Planning Terkunci:</strong> Data sudah di-submit tidak dapat diubah.
          </div>

          <div :class="['border-b px-4', isDark ? 'border-slate-600' : 'border-slate-200']">
            <div class="flex items-center justify-between">
              <div class="flex">
                <button @click="activePlanningTab = 'BOQ'" 
                  :class="[
                    'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                    activePlanningTab === 'BOQ' 
                      ? 'text-blue-500 border-blue-500' 
                      : (isDark ? 'text-slate-400 border-transparent' : 'text-slate-600 border-transparent')
                  ]"
                >
                  <FileText class="w-4 h-4 inline mr-1.5" /> BOQ
                </button>

                <button @click="activePlanningTab = 'BEP'" 
                  :class="[
                    'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                    activePlanningTab === 'BEP' 
                      ? 'text-blue-500 border-blue-500' 
                      : (isDark ? 'text-slate-400 border-transparent' : 'text-slate-600 border-transparent')
                  ]"
                >
                  <BarChart3 class="w-4 h-4 inline mr-1.5" /> BEP
                </button>
              </div>

              <div v-if="canGenerate">
                <button
                  @click="showGenerateModal = true"
                  class="px-4 py-2 text-xs font-semibold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition-colors"
                >
                  Generate BOQ On Desk
                </button>
              </div>
            </div>
          </div>

          <div class="p-4">
            <BoqTable
              v-show="activePlanningTab === 'BOQ'"
              v-model:items="boqRows"
              v-model:type="boqType"
              :version="boqVersion"
              :readonly="isReadOnly"
              @add-item="addBoqRow"
              @remove-item="removeBoqRow"
            />

            <BepForm
              v-show="activePlanningTab === 'BEP'"
              v-model:capex="capex"
              v-model:opexEstimate="opexEstimate"
              v-model:revenueEstimate="revenueEstimate"
              :readonly="isReadOnly"
            />
          </div>
        </div>
      </div>

      <!-- Dokumen Tab -->
      <DocumentList v-show="activeTab === 'documents'" :documents="props.documents" />

      <!-- History Tab -->
      <TimelineHistory
        v-show="activeTab === 'history'"
        :sortedHistories="sortedHistories"
        :statusApprovalMap="statusRelatedTypeMap"
        :findApprovalByProcessType="findApprovalByProcessType"
      />
      </div> <!-- End Content Area -->
      </div> <!-- End Unified Card -->

      <!-- ✅ Modal Draft -->
      <ConfirmDialog
        :show="draftModalOpen"
        :title="activePlanningTab === 'BOQ' ? 'Simpan Draft BOQ?' : 'Simpan Draft BEP?'"
        :message="activePlanningTab === 'BOQ'
          ? 'Pastikan item BOQ sudah benar. Draft akan disimpan tanpa submit approval.'
          : 'Pastikan nilai BEP sudah benar. Draft akan disimpan tanpa submit approval.'"
        confirmText="Ya, Simpan"
        cancelText="Batal"
        :loading="isSaving"
        @cancel="closeDraftModal()"
        @confirm="saveDraftConfirmed"
      />

      <!-- ✅ Modal Submit -->
      <ConfirmDialog
        :show="submitModalOpen"
        title="Submit Planning?"
        message="Pastikan data BOQ & BEP sudah benar. Setelah submit, planning akan masuk proses approval."
        confirmText="Ya, Submit"
        cancelText="Batal"
        :loading="isSubmitting"
        @cancel="closeSubmitModal()"
        @confirm="submitPlanningConfirmed"
      />
    </div>
    <div
      v-if="showGenerateModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    >
      <div class="bg-white w-full max-w-lg rounded-xl p-6 shadow-lg">
        <h2 class="text-lg font-bold mb-5">Generate BOQ On Desk</h2>

        <div class="space-y-4">
          <!-- Mitra -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              Mitra <span class="text-red-500">*</span>
            </label>
            <input
              v-model="generateForm.mitra"
              type="text"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
              placeholder="Nama mitra"
            />
          </div>

          <!-- Latar Belakang -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              Latar Belakang
            </label>
            <textarea
              v-model="generateForm.background"
              rows="3"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
              placeholder="Latar belakang proyek"
            ></textarea>
          </div>

          <!-- Tujuan -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              Tujuan
            </label>
            <textarea
              v-model="generateForm.objective"
              rows="3"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
              placeholder="Tujuan proyek"
            ></textarea>
          </div>

          <!-- Manfaat -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              Manfaat
            </label>
            <textarea
              v-model="generateForm.benefit"
              rows="3"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
              placeholder="Manfaat proyek"
            ></textarea>
          </div>
        </div>

        <!-- Action -->
        <div class="mt-6 flex justify-end gap-3">
          <button
            @click="showGenerateModal = false"
            class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100"
          >
            Batal
          </button>

          <button
            @click="submitGenerate"
            :disabled="isGenerating"
            class="px-4 py-2 text-sm rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50"
          >
            Generate
          </button>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<style scoped>
/* optional kecil biar card rapih di layar kecil */
</style>
