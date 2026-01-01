<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

interface StatusOption {
  id: number
  name: string
}

interface Project {
  id: number
  code: string
  name: string
  source?: string | null
  planner_name?: string | null
  target_completion_date: string | null
}

interface Props {
  groupedProjects: Record<string, Project[]>
  statusOptions: StatusOption[]
  filters: Record<string, any>
}

const props = defineProps<Props>()

const search = ref(props.filters?.search ?? '')
function applySearch() {
  router.get('/projects', { search: search.value }, { preserveState: true, replace: true })
}

function statusColor(name: string) {
  switch (name) {
    case 'PLAN ON DESK': return 'bg-slate-200 text-slate-700'
    case 'SPK SURVEY': return 'bg-yellow-100 text-yellow-700'
    case 'SURVEY DRM': return 'bg-orange-100 text-orange-700'
    case 'IMPLEMENTASI': return 'bg-blue-100 text-blue-700'
    case 'KONSTRUKSI': return 'bg-purple-100 text-purple-700'
    case 'GO LIVE': return 'bg-green-100 text-green-700'
    case 'HANDOVER': return 'bg-teal-100 text-teal-700'
    case 'CLOSED': return 'bg-red-100 text-red-700'
    default: return 'bg-gray-200 text-gray-600'
  }
}

function boardHeaderColor(name: string) {
  switch (name) {
    case 'PLAN ON DESK': return 'bg-slate-300'
     case 'SPK SURVEY': return 'bg-yellow-300'
    case 'SURVEY DRM': return 'bg-orange-300'
    case 'IMPLEMENTASI': return 'bg-blue-300'
    case 'KONSTRUKSI': return 'bg-purple-300'
    case 'GO LIVE': return 'bg-green-300'
    case 'HANDOVER': return 'bg-teal-300'
    case 'CLOSED': return 'bg-red-300'
    default: return 'bg-gray-300'
  }
}

function sourceBadgeClass(source?: string | null) {
  const s = (source ?? '').toUpperCase()
  if (s === 'OSS') return 'bg-red-100 text-red-700 border-red-200'
  return 'bg-blue-100 text-blue-700 border-blue-200'
}
function sourceLabel(source?: string | null) {
  const s = (source ?? '').toUpperCase()
  return s || 'INISIASI'
}
</script>

<template>
  <AppLayout title="Project" currentPage="project.list">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Project Board</h1>

    <!-- SEARCH -->
    <div class="mb-4">
      <input
        v-model="search"
        @input="applySearch"
        placeholder="Search projects..."
        class="border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 w-full md:w-1/3 h-10"
      />
    </div>

    <!-- BOARD WRAPPER -->
    <div class="flex gap-4 pb-6 overflow-x-auto hide-scrollbar">
      <div
        v-for="status in props.statusOptions"
        :key="status.id"
        class="w-[288px] min-w-[288px] max-w-[288px]
               bg-gray-100 rounded-xl border border-gray-300 shadow-sm
               overflow-hidden flex flex-col flex-shrink-0"
      >
        <!-- HEADER -->
        <div
          :class="`
            flex items-center justify-between w-full
            px-4 py-3
            ${boardHeaderColor(status.name)}
          `"
        >
          <h2 class="font-bold text-lg truncate text-gray-800">
            {{ status.name }}
          </h2>

          <span :class="'px-2 py-1 text-xs rounded-full font-bold ' + statusColor(status.name)">
            {{ props.groupedProjects[status.name]?.length ?? 0 }}
          </span>
        </div>

        <!-- CONTENT -->
        <div class="p-4 flex flex-col flex-1">
          <div class="space-y-3 overflow-y-auto pr-1 hide-scrollbar w-full h-auto sm:h-[60vh] lg:h-[55vh] xl:h-[52vh]">
            <!-- CARD -->
            <Link
              v-for="project in props.groupedProjects[status.name]"
              :key="project.id"
              :href="`/projects/project-detail?id=${project.id}`"
              class="block w-full bg-white border border-gray-200 rounded-lg p-3 shadow hover:bg-gray-50 transition cursor-pointer"
            >
              <!-- nama + badge (kode dihilangkan) -->
              <div class="flex items-start justify-between gap-2">
                <div class="text-base font-extrabold text-gray-800 leading-snug line-clamp-2">
                  {{ project.name ?? '-' }}
                </div>

                <span
                  :class="`shrink-0 px-2 py-0.5 text-[10px] font-bold rounded-full border ${sourceBadgeClass(project.source)}`"
                >
                  {{ sourceLabel(project.source) }}
                </span>
              </div>

              <!-- PIC -->
              <div class="text-xs mt-3 text-gray-500">
                PIC:
                <span class="font-semibold text-gray-700">
                  {{ project.planner_name ?? '-' }}
                </span>
              </div>

              <!-- target -->
              <div class="text-xs mt-1 text-gray-500">
                Target:
                <span class="font-semibold text-gray-700">
                  {{ project.target_completion_date ?? '-' }}
                </span>
              </div>
            </Link>

            <!-- NO DATA -->
            <div
              v-if="!props.groupedProjects[status.name] || props.groupedProjects[status.name].length === 0"
              class="text-sm text-gray-400 italic"
            >
              No projects
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.line-clamp-2{
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
  overflow:hidden;
}
</style>
