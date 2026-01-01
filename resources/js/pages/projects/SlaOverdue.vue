<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { Filter, Eye } from 'lucide-vue-next'
import { ref } from 'vue'

const props = defineProps<{
  projects: any
  filters?: any
  currentPage?: string
}>()

const search = ref(props.filters?.search ?? '')
const perPage = ref(props.filters?.per_page ?? 10)
const filterArea = ref(props.filters?.area ?? '')
const filterType = ref(props.filters?.project_type ?? '')
const filterSource = ref(props.filters?.source ?? '')
const showFilter = ref(false)

function applyFilters() {
  router.get(
    '/projects/sla-overdue',
    {
      search: search.value,
      per_page: perPage.value,
      area: filterArea.value,
      project_type: filterType.value,
      source: filterSource.value,
    },
    { preserveState: true, replace: true },
  )
}

function formatDate(value?: string | null) {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return value as string
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function sourceBadgeClass(source?: string | null) {
  const s = (source ?? '').toUpperCase()
  if (s === 'OSS') return 'bg-red-50 text-red-700 border border-red-200 font-semibold'
  return 'bg-blue-50 text-blue-700 border border-blue-200 font-semibold'
}

function overdueBadgeClass(days?: number | null) {
  const d = Number(days ?? 0)
  return 'bg-red-50 text-red-700 border border-red-200 font-semibold'
}
</script>

<template>
  <AppLayout title="SLA Overdue" :currentPage="currentPage || 'projects.slaOverdue'">
    <div class="mb-4">
      <h1 class="text-2xl font-bold text-gray-800">SLA Overdue</h1>
      <p class="text-sm text-slate-500">
        Daftar project yang melewati Target Completion Date.
      </p>
    </div>

    <!-- TOP BAR -->
    <div class="flex items-center justify-start gap-2 mb-4">
      <input
        v-model="search"
        @input="applyFilters"
        placeholder="Search projects..."
        class="input w-full md:w-1/3 h-9 text-sm"
      />

      <select
        v-model="perPage"
        @change="applyFilters"
        class="input h-9 w-24 text-sm"
      >
        <option value="5">5</option>
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>

      <button
        @click="showFilter = !showFilter"
        class="flex items-center gap-1 px-3 py-1.5 h-9 bg-gray-200 hover:bg-gray-300 rounded-md shadow-sm text-gray-700 text-sm"
      >
        <Filter class="w-4 h-4" />
        <span>Advanced Filter</span>
      </button>
    </div>

    <!-- FILTER PANEL -->
    <div
      v-if="showFilter"
      class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 mb-6"
    >
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="text-sm font-semibold mb-1 block">Area</label>
          <input v-model="filterArea" @input="applyFilters" class="input w-full h-10" />
        </div>

        <div>
          <label class="text-sm font-semibold mb-1 block">Project Type</label>
          <input v-model="filterType" @input="applyFilters" class="input w-full h-10" />
        </div>

        <div>
          <label class="text-sm font-semibold mb-1 block">Source</label>
          <select v-model="filterSource" @change="applyFilters" class="input w-full h-10">
            <option value="">All</option>
            <option value="MANUAL">MANUAL</option>
            <option value="OSS">OSS</option>
          </select>
        </div>
      </div>
    </div>

    <!-- TABLE -->
    <div class="overflow-x-auto bg-white rounded-xl shadow-md border border-gray-200">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-semibold border-b border-gray-100">
          <tr>
            <th class="px-4 py-3 text-left w-36">Code</th>
            <th class="px-4 py-3 text-left w-64">Name</th>
            <th class="px-4 py-3 text-left w-44">Planner</th>
            <th class="px-4 py-3 text-left w-40">Target Completion</th>
            <th class="px-4 py-3 text-left w-28">Overdue</th>
            <th class="px-4 py-3 text-left w-28">Source</th>
            <th class="px-4 py-3 text-left w-32">Area</th>
            <th class="px-4 py-3 text-left w-24">Action</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-100">
          <tr v-if="!props.projects?.data?.length">
            <td colspan="8" class="px-6 py-8 text-center text-sm text-slate-400">
              Tidak ada project yang overdue.
            </td>
          </tr>

          <tr
            v-for="project in props.projects.data"
            :key="project.id"
            class="hover:bg-gray-50 transition"
          >
            <td class="px-4 py-3">{{ project.code }}</td>
            <td class="px-4 py-3 font-semibold">{{ project.name }}</td>
            <td class="px-4 py-3">{{ project.planner?.Name ?? '-' }}</td>

            <td class="px-4 py-3">
              {{ formatDate(project.target_completion_date) }}
            </td>

            <td class="px-4 py-3">
              <span
                :class="`px-2 py-0.5 rounded-full text-[11px] ${overdueBadgeClass(project.overdue_days)}`"
              >
                {{ project.overdue_days ?? 0 }} hari
              </span>
            </td>

            <td class="px-4 py-3">
              <span
                :class="`px-2 py-0.5 rounded-full text-[11px] ${sourceBadgeClass(project.source)}`"
              >
                {{ project.source ?? 'MANUAL' }}
              </span>
            </td>

            <td class="px-4 py-3">{{ project.area ?? '-' }}</td>

            <td class="px-4 py-3">
              <Link
                :href="`/projects/project-detail?id=${project.id}`"
                class="text-blue-600 flex items-center gap-1"
              >
                <Eye class="w-4 h-4" /> Detail
              </Link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <div class="flex justify-end mt-4 gap-1" v-if="props.projects?.links?.length">
      <Link
        v-for="(link, i) in props.projects.links"
        :key="i"
        :href="link.url ?? '#'"
        class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
        :class="
          link.active
            ? 'bg-blue-600 text-white'
            : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'
        "
        v-html="link.label"
      />
    </div>
  </AppLayout>
</template>

<style scoped>
.input {
  @apply border border-gray-300 rounded-md shadow-sm px-3 py-2
         focus:ring-blue-500 focus:border-blue-500;
}
</style>
