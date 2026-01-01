<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue"
import { Head, Link, router, usePage } from "@inertiajs/vue3"
import { computed, ref, watch } from "vue"
import { Search, X, Eye, ClipboardList, History, Loader2 } from "lucide-vue-next"

type Paginated = {
  data: any[]
  total: number
  per_page: number
  current_page: number
  last_page: number
}

const props = defineProps<{
  items: Paginated
  filters?: any
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

const canApprove = computed(() => page.props.auth?.permissions?.includes("approval.approve") ?? false)
const canReject = computed(() => page.props.auth?.permissions?.includes("approval.reject") ?? false)

const tab = ref<string>(props.filters?.tab ?? "Pending Approval") // Pending Approval | history
const search = ref(props.filters?.search ?? "")
const perPage = ref<number>(Number(props.filters?.per_page ?? 10))

const isLoading = ref(false)

let t: any = null
watch(search, () => {
  clearTimeout(t)
  t = setTimeout(() => applyFilters(1), 350)
})

function startLoading() {
  isLoading.value = true
}
function stopLoading() {
  isLoading.value = false
}

function goTab(next: "Pending Approval" | "history") {
  if (isLoading.value) return
  tab.value = next

  router.get(
    "/approval",
    { tab: tab.value, search: search.value, per_page: perPage.value, page: 1 },
    {
      preserveState: true,
      replace: true,
      preserveScroll: true,
      onStart: startLoading,
      onFinish: stopLoading,
    }
  )
}

function applyFilters(p = props.items.current_page) {
  if (isLoading.value) return

  router.get(
    "/approval",
    { tab: tab.value, search: search.value, per_page: perPage.value, page: p },
    {
      preserveState: true,
      replace: true,
      preserveScroll: true,
      onStart: startLoading,
      onFinish: stopLoading,
    }
  )
}

function clearSearch() {
  if (isLoading.value) return
  search.value = ""
  applyFilters(1)
}

const isHistory = computed(() => tab.value === "history")

function badgeClass(stepOrder?: number) {
  if (!stepOrder) return "badge badge-slate"
  if (stepOrder === 1) return "badge badge-blue"
  if (stepOrder === 2) return "badge badge-yellow"
  return "badge badge-slate"
}

function statusBadge(status: any) {
  const s = String(status ?? "").toUpperCase()
  if (s === "APPROVED") return "badge badge-green"
  if (s === "REJECTED") return "badge badge-red"
  if (s === "PENDING" || s === "IN_REVIEW" || s === "SUBMITTED") return "badge badge-yellow"
  return "badge badge-slate"
}

function fmtDateTime(v: any) {
  if (!v) return "-"
  const d = new Date(v)
  if (isNaN(d.getTime())) return String(v)
  return new Intl.DateTimeFormat("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(d)
}

function fmtTimeRemaining(hoursDecimal: number) {
  const totalMinutes = Math.floor(hoursDecimal * 60)
  const hours = Math.floor(totalMinutes / 60)
  const minutes = totalMinutes % 60
  if (hours === 0) return `${minutes} menit`
  if (minutes === 0) return `${hours} jam`
  return `${hours} jam ${minutes} menit`
}
</script>

<template>
  <Head>
    <title>Approvals</title>
  </Head>

  <AppLayout title="Approvals" currentPage="approval">
    <!-- HEADER -->
    <div class="mb-4">
      <h1 class="text-2xl font-bold text-slate-800">Approval</h1>
      <p class="text-sm text-slate-500 mt-1">
        <span v-if="!isHistory">Daftar project yang butuh approval sesuai role kamu.</span>
        <span v-else>Riwayat approval yang sudah selesai (Approved / Rejected).</span>
      </p>
    </div>

    <!-- TABS -->
    <div class="flex items-center gap-2 mb-4">
      <button
        type="button"
        class="tab-btn"
        :class="!isHistory ? 'tab-active' : ''"
        @click="goTab('Pending Approval')"
        :disabled="isLoading"
      >
        <ClipboardList class="w-4 h-4" />
        Pending Approval
      </button>

      <button
        type="button"
        class="tab-btn"
        :class="isHistory ? 'tab-active' : ''"
        @click="goTab('history')"
        :disabled="isLoading"
      >
        <History class="w-4 h-4" />
        History
      </button>
    </div>

    <!-- FILTER -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-4 mb-4">
      <div class="flex flex-col md:flex-row md:items-center gap-3">
        <div class="relative w-full md:w-[420px]">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            v-model="search"
            placeholder="Cari code / nama / area..."
            class="input-icon w-full h-10 text-sm"
            :disabled="isLoading"
          />
          <button
            v-if="search"
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded hover:bg-slate-100"
            @click="clearSearch"
            :disabled="isLoading"
          >
            <X class="w-4 h-4 text-slate-500" />
          </button>
        </div>

        <div class="flex items-center gap-2">
          <span class="text-sm text-slate-600">Show</span>
          <select
            v-model="perPage"
            @change="applyFilters(1)"
            class="input h-10 w-28 text-sm"
            :disabled="isLoading"
          >
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
        </div>

        <div class="md:ml-auto text-xs text-slate-500">
          Total: <span class="font-semibold text-slate-700">{{ props.items.total }}</span>
        </div>
      </div>
    </div>

    <!-- TABLE -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-md overflow-hidden relative">
      <div
        v-if="isLoading"
        class="absolute inset-0 z-10 bg-white/70 backdrop-blur-[1px] flex items-center justify-center"
      >
        <div class="inline-flex items-center gap-2 text-slate-700 text-sm font-semibold">
          <Loader2 class="w-4 h-4 animate-spin" />
          Loading data...
        </div>
      </div>

      <div class="px-4 py-3 border-b flex items-center justify-between">
        <div class="text-sm font-semibold text-slate-700">
          <span v-if="!isHistory">Approval Tasks</span>
          <span v-else>Approval History</span>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead
            v-if="!isHistory"
            class="bg-slate-50 text-slate-500 uppercase text-xs font-semibold border-b border-slate-100"
          >
            <tr>
              <th scope="col" class="px-4 py-3 text-left w-52">Project</th>
              <th scope="col" class="px-4 py-3 text-left w-40">Planner</th>
              <th scope="col" class="px-4 py-3 text-left w-40">Area</th>
              <th scope="col" class="px-4 py-3 text-left w-56">Step Aktif</th>
              <th scope="col" class="px-4 py-3 text-left w-40">SLA / Deadline</th>
              <th scope="col" class="px-4 py-3 text-left w-32">Aksi</th>
            </tr>
          </thead>

          <thead
            v-else
            class="bg-slate-50 text-slate-500 uppercase text-xs font-semibold border-b border-slate-100"
          >
            <tr>
              <th scope="col" class="px-4 py-3 text-left w-52">Project</th>
              <th scope="col" class="px-4 py-3 text-left w-40">Planner</th>
              <th scope="col" class="px-4 py-3 text-left w-40">Area</th>
              <th scope="col" class="px-4 py-3 text-left w-40">Status</th>
              <th scope="col" class="px-4 py-3 text-left w-44">Selesai</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100">
            <template v-if="isLoading">
              <tr v-for="i in 6" :key="i">
                <td class="px-4 py-4"><div class="h-3 w-6 skeleton"></div></td>
                <td class="px-4 py-4"><div class="h-3 w-40 skeleton"></div><div class="h-3 w-20 skeleton mt-2"></div></td>
                <td class="px-4 py-4"><div class="h-3 w-56 skeleton"></div><div class="h-3 w-40 skeleton mt-2"></div></td>
                <td class="px-4 py-4"><div class="h-3 w-28 skeleton"></div></td>
                <td class="px-4 py-4"><div class="h-3 w-20 skeleton"></div></td>
                <td class="px-4 py-4"><div class="h-3 w-36 skeleton"></div></td>
                <td v-if="!isHistory" class="px-4 py-4"><div class="h-3 w-16 skeleton"></div></td>
              </tr>
            </template>

            <template v-else>
              <tr v-for="(row, idx) in props.items.data" :key="row.id" class="hover:bg-slate-50">
                <!-- PROJECT -->
                <td class="px-4 py-3">
                  <div class="font-semibold text-slate-800">{{ row.project?.name }}</div>

                  <div class="flex items-center gap-2 mt-0.5">
                    <div class="font-mono text-slate-800">{{ row.project?.code }}</div>

                    <!-- ✅ STATUS GENERAL PROJECT -->
                    <span class="badge badge-slate">
                      {{ row.project?.status ?? "-" }}
                    </span>
                  </div>

                  <div class="text-xs text-slate-500">ID: {{ row.project?.id }}</div>
                </td>

                <td class="px-4 py-3">{{ row.project?.planner?.Name ?? "-" }}</td>
                <td class="px-4 py-3">{{ row.project?.area ?? "-" }}</td>

                <!-- inbox -->
                <td v-if="!isHistory" class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <span :class="badgeClass(row.step?.step_order)">
                      Step {{ row.step?.step_order ?? "-" }}
                    </span>
                    <div class="leading-tight">
                      <div class="font-semibold text-slate-800">{{ row.step?.name ?? "-" }}</div>
                      <div class="text-xs text-slate-500">Role: {{ row.step?.role_name ?? "-" }}</div>
                    </div>
                  </div>
                </td>

                <!-- SLA / Deadline -->
                <td v-if="!isHistory" class="px-4 py-3">
                  <div v-if="row.sla?.hours" class="space-y-1">
                    <!-- SLA Hours -->
                    <div class="text-xs text-slate-500">SLA: {{ row.sla.hours }} jam</div>
                    <!-- Deadline -->
                    <div v-if="row.sla?.deadline" class="text-xs">
                      <span v-if="row.sla.is_overdue" class="badge badge-red">
                        ⚠️ Overdue
                      </span>
                      <span v-else-if="row.sla.hours_remaining !== null && row.sla.hours_remaining <= 4" class="badge badge-yellow">
                        {{ fmtTimeRemaining(row.sla.hours_remaining) }} lagi
                      </span>
                      <span v-else class="text-slate-600">
                        {{ fmtDateTime(row.sla.deadline) }}
                      </span>
                    </div>
                  </div>
                  <span v-else class="text-xs text-slate-400">-</span>
                </td>

                <td v-if="!isHistory" class="px-4 py-3">
                  <Link :href="`/approval/${row.id}`" class="action-link">
                    <Eye class="w-4 h-4" />
                    <span>Detail</span>
                  </Link>
                </td>

                <!-- history -->
                <td v-if="isHistory" class="px-4 py-3">
                  <span :class="statusBadge(row.status)">{{ row.status }}</span>
                </td>

                <td v-if="isHistory" class="px-4 py-3">
                  {{ fmtDateTime(row.completed_at) }}
                </td>
              </tr>

              <tr v-if="!props.items.data?.length">
                <td :colspan="isHistory ? 7 : 7" class="px-6 py-10 text-center text-slate-400">
                  <span v-if="!isHistory">Tidak ada data approval.</span>
                  <span v-else>Belum ada history approval.</span>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-between px-4 py-3 border-t bg-white">
        <div class="text-xs text-slate-500">
          Page {{ props.items.current_page }} / {{ props.items.last_page }}
        </div>

        <div class="flex gap-2">
          <button
            class="btn-ghost"
            :disabled="isLoading || props.items.current_page <= 1"
            @click="applyFilters(props.items.current_page - 1)"
          >
            Prev
          </button>
          <button
            class="btn-ghost"
            :disabled="isLoading || props.items.current_page >= props.items.last_page"
            @click="applyFilters(props.items.current_page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.input { @apply border border-slate-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500; }
.input-icon { @apply border border-slate-300 rounded-md shadow-sm px-3 pr-9 pl-10 focus:ring-blue-500 focus:border-blue-500; }

.badge { @apply inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border; }
.badge-blue { @apply bg-blue-50 text-blue-700 border-blue-200; }
.badge-yellow { @apply bg-amber-50 text-amber-700 border-amber-200; }
.badge-slate { @apply bg-slate-50 text-slate-700 border-slate-200; }
.badge-green { @apply bg-emerald-50 text-emerald-700 border-emerald-200; }
.badge-red { @apply bg-rose-50 text-rose-700 border-rose-200; }

.action-link { @apply inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 font-semibold; }
.btn-ghost { @apply px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs disabled:opacity-40 disabled:cursor-not-allowed; }

.tab-btn { @apply inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed; }
.tab-active { @apply border-blue-300 bg-blue-50 text-blue-700; }

/* skeleton */
.skeleton { @apply animate-pulse rounded bg-slate-200; }
</style>
