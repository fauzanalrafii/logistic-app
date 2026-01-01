<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue"
import { Head, Link, useForm, usePage } from "@inertiajs/vue3"
import { computed, ref } from "vue"
import { ArrowLeft, CheckCircle, XCircle, Loader2 } from "lucide-vue-next"
import { useToast } from "@/composables/useToast"
import ConfirmDialog from "@/components/ConfirmDialog.vue"
import Snackbar from '@/components/Snackbar.vue'

const props = defineProps<{
  instance: any
  boq: any | null
  bep: any | null
  currentPage: string
}>()

interface PageProps {
  auth: {
    user: { name: string; role: string } | null
    permissions: string[]
  }
  [key: string]: unknown
}

const page = usePage<PageProps>()
const { success, error } = useToast()

// permission
const canApprove = computed(() => page.props.auth?.permissions?.includes("approval.approve") ?? false)
const canReject  = computed(() => page.props.auth?.permissions?.includes("approval.reject") ?? false)

// status string dari backend kamu: instance.status = 'PENDING/IN_REVIEW/APPROVED/REJECTED'
const statusText = computed(() => String(props.instance?.status ?? "").toUpperCase())
const isFinal = computed(() => statusText.value === "APPROVED" || statusText.value === "REJECTED")

// ✅ INI YANG PENTING: pakai flag dari backend
const canAct = computed(() => {
  // kalau backend tidak kirim, anggap false biar aman
  const can = Boolean(props.instance?.can_act)
  const already = Boolean(props.instance?.already_acted)
  return can && !already
})

// inertia forms
const approveForm = useForm({})
const rejectForm = useForm<{ comment: string }>({ comment: "" })
const rejectComment = ref("")

// modal state
const approveModalOpen = ref(false)
const rejectModalOpen = ref(false)

// lock state
const actionLocked = ref(false) // lock saat request jalan
const localLocked  = ref(false) // lock permanen setelah sukses (tanpa nunggu reload)

const isBusy = computed(() => approveForm.processing || rejectForm.processing)

// ✅ DISABLE yang benar: kalau tidak canAct -> tombol mati
const isDisabled = computed(() => {
  return isFinal.value || localLocked.value || !canAct.value || isBusy.value || actionLocked.value
})

const disableReason = computed(() => {
  if (isFinal.value) return "Approval sudah selesai."
  if (!canAct.value) return "Anda tidak bisa melakukan aksi pada step ini."
  if (isBusy.value) return "Sedang memproses..."
  return ""
})

function money(v: any) {
  const n = Number(v ?? 0)
  return new Intl.NumberFormat("id-ID").format(isNaN(n) ? 0 : n)
}

function fmtTimeRemaining(hoursDecimal: number) {
  const totalMinutes = Math.floor(hoursDecimal * 60)
  const hours = Math.floor(totalMinutes / 60)
  const minutes = totalMinutes % 60
  if (hours === 0) return `${minutes} menit`
  if (minutes === 0) return `${hours} jam`
  return `${hours} jam ${minutes} menit`
}

function approve() {
  if (isDisabled.value) return

  actionLocked.value = true

  approveForm.post(`/approval/${props.instance.id}/approve`, {
    preserveScroll: true,
    onSuccess: () => {
      // ✅ begitu sukses, langsung lock permanen di UI
      localLocked.value = true
      approveModalOpen.value = false
      success("Approve berhasil.")
    },
    onError: (errs: any) => {
      const msg = errs?.error || "Gagal approve."
      error(String(msg))
      actionLocked.value = false
    },
    onCancel: () => {
      actionLocked.value = false
    },
    onFinish: () => {
      if (!localLocked.value) actionLocked.value = false
    },
  })
}

function doReject() {
  if (isDisabled.value) return

  const c = String(rejectComment.value ?? "").trim()
  if (!c) {
    error("Komentar reject wajib diisi.")
    return
  }

  rejectForm.comment = c
  actionLocked.value = true

  rejectForm.post(`/approval/${props.instance.id}/reject`, {
    preserveScroll: true,
    onSuccess: () => {
      localLocked.value = true
      rejectModalOpen.value = false
      success("Reject berhasil.")
    },
    onError: (errs: any) => {
      const msg = errs?.error || "Gagal reject."
      error(String(msg))
      actionLocked.value = false
    },
    onCancel: () => {
      actionLocked.value = false
    },
    onFinish: () => {
      if (!localLocked.value) actionLocked.value = false
    },
  })
}

function openApproveModal() {
  if (isDisabled.value) return
  approveModalOpen.value = true
}

function closeApproveModal() {
  approveModalOpen.value = false
}

function openRejectModal() {
  if (isDisabled.value) return
  rejectModalOpen.value = true
}

function closeRejectModal() {
  rejectModalOpen.value = false
}

</script>

<template>
  <Head :title="`Approval Detail - ${props.instance.project?.name ?? ''}`" />

  <AppLayout title="Approval Detail" :currentPage="currentPage">
    <!-- HEADER -->
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3 mb-4">
      <div>
        <Link
          href="/Approval/index"
          class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900"
        >
          <ArrowLeft class="w-4 h-4" />
          Kembali
        </Link>

        <h1 class="text-2xl font-bold text-slate-800 mt-2">
          Approval: {{ props.instance.project?.code }} — {{ props.instance.project?.name }}
        </h1>

        <p class="text-sm text-slate-500 mt-1">
          Status:
          <span class="font-semibold text-slate-700">{{ statusText }}</span>
          <span v-if="props.instance.progress_label"> • {{ props.instance.progress_label }}</span>
        </p>

        <!-- SLA Info -->
        <div v-if="props.instance.sla?.hours" class="mt-2 flex items-center gap-3">
          <span class="text-sm text-slate-600">
            SLA: <strong>{{ props.instance.sla.hours }} jam</strong>
          </span>
          <span v-if="props.instance.sla.deadline" class="text-sm">
            • Deadline: <strong>{{ props.instance.sla.deadline }}</strong>
          </span>
          <span v-if="props.instance.sla.is_overdue" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700 border border-rose-200">
            ⚠️ Overdue
          </span>
          <span v-else-if="props.instance.sla.hours_remaining !== null && props.instance.sla.hours_remaining <= 4" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 border border-amber-200">
            {{ fmtTimeRemaining(props.instance.sla.hours_remaining) }} lagi
          </span>
        </div>
      </div>
    </div>

    <!-- INFO FINAL -->
    <div
      v-if="isFinal"
      class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"
    >
      Approval sudah selesai ({{ statusText }}). Tombol aksi dinonaktifkan.
    </div>

    <div
      v-else-if="localLocked"
      class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"
    >
      Aksi sudah dikirim. Tombol dinonaktifkan.
    </div>

    <!-- DETAIL PROJECT -->
    <div class="card mb-4">
      <div class="card-title">Detail Project</div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
        <div>
          <div class="label">Code</div>
          <div class="value font-mono">{{ props.instance.project?.code ?? "-" }}</div>
        </div>

        <div>
          <div class="label">Area</div>
          <div class="value">{{ props.instance.project?.area ?? "-" }}</div>
        </div>

        <div>
          <div class="label">Planner</div>
          <div class="value">{{ props.instance.project?.planner?.Name ?? "-" }}</div>
        </div>

        <div>
          <div class="label">Location</div>
          <div class="value">{{ props.instance.project?.location ?? "-" }}</div>
        </div>

        <div>
          <div class="label">Project Type</div>
          <div class="value">{{ props.instance.project?.project_type ?? "-" }}</div>
        </div>

        <div>
          <div class="label">Source</div>
          <div class="value">{{ props.instance.project?.source ?? "-" }}</div>
        </div>

        <div>
          <div class="label">Target Completion</div>
          <div class="value">{{ props.instance.project?.target_completion_date ?? "-" }}</div>
        </div>

        <div>
          <div class="label">Status Project</div>
          <div class="value">{{ props.instance.project?.status ?? "-" }}</div>
        </div>
      </div>
    </div>

    <!-- BOQ + BEP -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
      <!-- BOQ -->
      <div class="card">
        <div class="card-title flex items-center justify-between">
          <span>BOQ On Desk</span>
          <span v-if="props.boq" class="badge">v{{ props.boq.version }}</span>
        </div>

        <div v-if="!props.boq" class="empty">Belum ada data BOQ.</div>

        <div v-else>
          <div class="text-sm text-slate-600 mb-2">
            Total Estimasi:
            <span class="font-semibold text-slate-800">Rp {{ money(props.boq.total_cost_estimate) }}</span>
          </div>

          <div class="overflow-x-auto border rounded-lg">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-semibold">
                <tr>
                  <th class="px-3 py-2 text-left">Material Item</th>
                  <th class="px-3 py-2 text-left">UOM</th>
                  <th class="px-3 py-2 text-right">Qty</th>
                  <th class="px-3 py-2 text-right">Unit Price</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="(it, i) in props.boq.items" :key="i">
                  <td class="px-3 py-2">
                    <div class="font-semibold text-slate-800">{{ it.item }}</div>
                    <div class="text-xs text-slate-500">{{ it.spec }}</div>
                  </td>
                  <td class="px-3 py-2">{{ it.uom }}</td>
                  <td class="px-3 py-2 text-right">{{ it.qty }}</td>
                  <td class="px-3 py-2 text-right">Rp {{ money(it.unit_price) }}</td>
                </tr>

                <tr v-if="!props.boq.items?.length">
                  <td colspan="5" class="px-3 py-6 text-center text-slate-400">
                    Item BOQ kosong.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- BEP -->
      <div class="card">
        <div class="card-title flex items-center justify-between">
          <span>Proyeksi BEP</span>
          <span v-if="props.bep" class="badge">v{{ props.bep.version }}</span>
        </div>

        <div v-if="!props.bep" class="empty">Belum ada data BEP.</div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
          <div>
            <div class="label">CAPEX</div>
            <div class="value font-semibold">Rp {{ money(props.bep.capex) }}</div>
          </div>

          <div>
            <div class="label">OPEX Estimate</div>
            <div class="value font-semibold">Rp {{ money(props.bep.opex_estimate) }}</div>
          </div>

          <div>
            <div class="label">Revenue Estimate</div>
            <div class="value font-semibold">Rp {{ money(props.bep.revenue_estimate) }}</div>
          </div>

          <div>
            <div class="label">BEP (Months)</div>
            <div class="value font-semibold">{{ props.bep.bep_months }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- HISTORY -->
    <div class="card mb-4">
      <div class="card-title">History Approval</div>

      <div v-if="!props.instance.actions?.length" class="empty">
        Belum ada history tindakan.
      </div>

      <div v-else class="space-y-2">
        <div v-for="(a, i) in props.instance.actions" :key="i" class="border rounded-lg p-3 bg-white">
          <div class="flex items-center justify-between">
            <div class="text-sm font-semibold text-slate-800">
              {{ a.action }} • Step {{ a.step_order ?? "-" }} • {{ a.step_name ?? "-" }}
            </div>
            <div class="text-xs text-slate-500">{{ a.acted_at }}</div>
          </div>

          <div class="text-xs text-slate-500 mt-1">
            Oleh: <span class="font-semibold text-slate-700">{{ a.user_name }}</span>
          </div>

          <div v-if="a.comment" class="text-sm text-slate-700 mt-2 whitespace-pre-line">
            {{ a.comment }}
          </div>
        </div>
      </div>
    </div>

    <!-- KOMENTAR REJECT -->
    <div v-if="canReject" class="card mb-4">
      <div class="card-title">Komentar Reject (wajib jika Reject)</div>

      <textarea
        v-model="rejectComment"
        rows="3"
        class="input w-full"
        placeholder="Tulis alasan reject..."
        :disabled="isDisabled"
      />

      <div v-if="rejectForm.errors?.comment" class="text-xs text-rose-600 mt-2">
        {{ rejectForm.errors.comment }}
      </div>
    </div>

    <!-- ACTIONS -->
    <div class="flex gap-2">
      <button
        v-if="canApprove"
        class="btn-approve"
        type="button"
        @click="openApproveModal"
        :disabled="isDisabled"
        :title="disableReason"
        :class="isDisabled ? 'opacity-50 cursor-not-allowed' : ''"
      >
        <Loader2 v-if="approveForm.processing" class="w-4 h-4 animate-spin" />
        <CheckCircle v-else class="w-4 h-4" />
        <span>{{ approveForm.processing ? "Processing..." : "Approve" }}</span>
      </button>

      <button
        v-if="canReject"
        class="btn-reject"
        type="button"
        @click="openRejectModal"
        :disabled="isDisabled"
        :title="disableReason"
        :class="isDisabled ? 'opacity-50 cursor-not-allowed' : ''"
      >
        <Loader2 v-if="rejectForm.processing" class="w-4 h-4 animate-spin" />
        <XCircle v-else class="w-4 h-4" />
        <span>{{ rejectForm.processing ? "Processing..." : "Reject" }}</span>
      </button>
    </div>

    <!-- MODAL APPROVE -->
    <ConfirmDialog
      :show="approveModalOpen"
      title="Approve?"
      message="Yakin ingin approve project ini?"
      confirmText="Ya, Approve"
      cancelText="Batal"
      :loading="approveForm.processing"
      @cancel="closeApproveModal"
      @confirm="approve"
    />

    <!-- MODAL REJECT -->
    <ConfirmDialog
      :show="rejectModalOpen"
      title="Reject?"
      :message="`Yakin ingin reject project ini? `"
      confirmText="Ya, Reject"
      cancelText="Batal"
      :loading="rejectForm.processing"
      @cancel="closeRejectModal"
      @confirm="doReject"
    />
  </AppLayout>
</template>

<style scoped>
.card { @apply bg-white border border-slate-200 rounded-xl shadow-sm p-4; }
.card-title { @apply text-sm font-bold text-slate-800 mb-3; }
.label { @apply text-xs text-slate-500; }
.value { @apply text-slate-800; }
.empty { @apply text-sm text-slate-400 py-6; }
.badge { @apply inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border bg-slate-50 text-slate-700 border-slate-200; }
.input { @apply border border-slate-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500; }
.btn-approve { @apply inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold; }
.btn-reject  { @apply inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold; }
</style>
