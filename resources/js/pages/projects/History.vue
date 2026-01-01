<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const props = defineProps<{
  currentPage: string
  filters: { search?: string; per_page?: number }
  statusHistories: any
  completedProjects: any
}>()

const tab = ref<'changes' | 'completed'>('changes')

const search = ref(props.filters?.search ?? '')
const perPage = ref(props.filters?.per_page ?? 10)

const histories = computed(() => props.statusHistories?.data ?? [])
const completed = computed(() => props.completedProjects?.data ?? [])

// =====================
// Fetch + Debounce
// =====================
let searchTimer: number | null = null

const fetchHistory = () => {
  router.get(
    '/projects/history',
    { search: search.value, per_page: perPage.value },
    {
      preserveState: true,
      replace: true,
      preserveScroll: true,
      // ✅ bikin payload lebih ringan & lebih cepat
      only: ['statusHistories', 'completedProjects', 'filters'],
    }
  )
}

// ✅ debounce khusus search
watch(search, () => {
  if (searchTimer) window.clearTimeout(searchTimer)
  searchTimer = window.setTimeout(() => {
    fetchHistory()
  }, 350)
})

// ✅ perPage langsung refresh
watch(perPage, () => {
  fetchHistory()
})

// =====================
// Helpers
// =====================
const formatDate = (value: string | null) => {
  if (!value) return '-'
  const d = new Date(value)
  if (isNaN(d.getTime())) return value

  return d.toLocaleString('id-ID', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

const statusClass = (name?: string | null) => {
  const s = (name ?? '').toUpperCase()

  // Dihapus
  if (s === 'DELETED') {
    return 'bg-gray-100 text-gray-600 border border-gray-300'
  }

  // Final
  if (s === 'CLOSED' || s === 'SELESAI') {
    return 'bg-red-50 text-red-700 border border-red-200'
  }

  // Awal
  if (s.includes('PLAN ON DESK')) {
    return 'bg-slate-50 text-slate-700 border border-slate-200'
  }

  // Survey / DRM
  if (s.includes('SURVEY') && s.includes('DRM')) {
    return 'bg-violet-50 text-violet-700 border border-violet-200'
  }
  if (s.includes('SURVEY')) {
    return 'bg-indigo-50 text-indigo-700 border border-indigo-200'
  }

  // Implementasi
  if (s.includes('IMPLEMENTASI')) {
    return 'bg-amber-50 text-amber-800 border border-amber-200'
  }

  // Procurement
  if (s.includes('PROCUREMENT') || s.includes('(PP)')) {
    return 'bg-cyan-50 text-cyan-800 border border-cyan-200'
  }

  // Material
  if (s.includes('MATERIAL') || s.includes('(MOS)')) {
    return 'bg-emerald-50 text-emerald-800 border border-emerald-200'
  }

  // Konstruksi
  if (s.includes('KONSTRUKSI')) {
    return 'bg-orange-50 text-orange-800 border border-orange-200'
  }

  // Go Live
  if (s.includes('GO LIVE')) {
    return 'bg-green-50 text-green-700 border border-green-200'
  }

  // Inventory/Asset
  if (s.includes('INVENTORY') || s.includes('ASSET')) {
    return 'bg-teal-50 text-teal-800 border border-teal-200'
  }

  // Default
  return 'bg-slate-50 text-slate-700 border border-slate-200'
}
</script>

<template>
  <AppLayout :currentPage="currentPage">
    <div class="p-6 space-y-5">
      <!-- Header -->
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">History Project</h1>
          <p class="text-sm text-slate-500">
            Riwayat perubahan status dan daftar project yang sudah selesai.
          </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
          <input
            v-model="search"
            type="text"
            autocomplete="off"
            placeholder="Cari nama project / status..."
            class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-sm w-full sm:w-80
                   focus:outline-none focus:ring-2 focus:ring-blue-200"
          />

          <select
            v-model="perPage"
            class="px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-sm w-24
                   focus:outline-none focus:ring-2 focus:ring-blue-200"
          >
            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="50">50</option>
          </select>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex gap-2">
        <button
          class="px-4 py-2 rounded-xl text-sm font-semibold transition border"
          :class="tab === 'changes'
            ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
            : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
          @click="tab = 'changes'"
        >
          Perubahan Status
        </button>

        <button
          class="px-4 py-2 rounded-xl text-sm font-semibold transition border"
          :class="tab === 'completed'
            ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
            : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
          @click="tab = 'completed'"
        >
          Project Selesai
        </button>
      </div>

      <!-- ========================= -->
      <!-- TAB 1: Perubahan Status   -->
      <!-- ========================= -->
      <div v-if="tab === 'changes'" class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50">
              <tr class="text-slate-600 border-b border-slate-200">
                <th class="text-left py-3 px-2 font-semibold">Waktu</th>
                <th class="text-left py-3 px-2 font-semibold">Project</th>
                <th class="text-left py-3 px-2 font-semibold">Old Status</th>
                <th class="text-left py-3 px-2 font-semibold">New Status</th>
                <th class="text-left py-3 px-2 font-semibold">Diubah Oleh</th>
                <th class="text-left py-3 px-2 font-semibold">Catatan</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="h in histories"
                :key="h.id"
                class="border-b border-slate-100 text-slate-800"
              >
                <!-- Waktu -->
                <td class="py-3 px-2 whitespace-nowrap">
                  {{ formatDate(h.changed_at) }}
                </td>

                <!-- Project -->
                <td class="py-3 px-2">
                  <div class="font-semibold text-slate-900">
                    {{ h.project?.name ?? '-' }}
                  </div>
                  <div class="text-xs text-slate-400">
                    #{{ h.project_id }}
                  </div>
                </td>

                <!-- Old Status -->
                <td class="py-3 px-2">
                  <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                    :class="statusClass(h.old_status_label)"
                  >
                    {{ h.old_status_label ?? '-' }}
                  </span>
                </td>

                <!-- New Status -->
                <td class="py-3 px-2">
                  <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                    :class="statusClass(h.note === 'Dihapus' ? 'DIHAPUS' : h.new_status_label)"
                  >
                    {{ h.note === 'Dihapus' ? 'DIHAPUS' : (h.new_status_label ?? '-') }}
                  </span>
                </td>

                <!-- Diubah Oleh -->
                <td class="py-3 px-2">
                  {{ h.changer?.Name ?? '-' }}
                </td>

                <!-- Catatan -->
                <td class="py-3 px-2">
                  {{ h.note ?? '-' }}
                </td>
              </tr>

              <tr v-if="histories.length === 0">
                <td colspan="6" class="py-8 text-center text-slate-400">
                  Tidak ada history perubahan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="flex justify-end mt-4 gap-1">
          <Link
            v-for="(l, i) in props.statusHistories?.links ?? []"
            :key="i"
            :href="l.url ?? '#'"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
            :class="l.active
              ? 'bg-blue-600 text-white'
              : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
            v-html="l.label"
          />
        </div>
      </div>

      <!-- ========================= -->
      <!-- TAB 2: Project Selesai    -->
      <!-- ========================= -->
      <div v-else class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50">
              <tr class="text-slate-600 border-b border-slate-200">
                <th class="text-left py-3 px-2 font-semibold">Project</th>
                <th class="text-left py-3 px-2 font-semibold">Status</th>
                <th class="text-left py-3 px-2 font-semibold">Planner</th>
                <th class="text-left py-3 px-2 font-semibold">Updated</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="p in completed"
                :key="p.id"
                class="border-b border-slate-100 text-slate-800"
              >
                <td class="py-3 px-2">
                  <div class="font-semibold text-slate-900">
                    {{ p.name ?? '-' }}
                  </div>
                  <div class="text-xs text-slate-400">
                    {{ p.code ?? '' }}
                  </div>
                </td>

                <td class="py-3 px-2">
                  <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                    :class="statusClass(p.status?.name ?? 'SELESAI')"
                  >
                    {{ p.status?.name ?? 'SELESAI' }}
                  </span>
                </td>

                <td class="py-3 px-2">
                  {{ p.planner?.Name ?? '-' }}
                </td>

                <td class="py-3 px-2 whitespace-nowrap">
                  {{ formatDate(p.updated_at ?? null) }}
                </td>
              </tr>

              <tr v-if="completed.length === 0">
                <td colspan="5" class="py-8 text-center text-slate-400">
                  Belum ada project selesai.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="flex justify-end mt-4 gap-1">
          <Link
            v-for="(l, i) in props.completedProjects?.links ?? []"
            :key="i"
            :href="l.url ?? '#'"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
            :class="l.active
              ? 'bg-blue-600 text-white'
              : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
            v-html="l.label"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
