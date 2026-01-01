<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import QuickStats from '@/components/QuickStats.vue'
import DashboardAnalytics from '@/components/DashboardAnalytics.vue'
import supercorridor from '@/assets/images/supercorridor.png'
import { useDarkMode } from '@/composables/useDarkMode'
import { 
  ArrowRight, 
  Map,
  ClipboardList,
  Search,
  HardHat,
  Radio,
  Building2,
  CheckCircle2,
  AlertOctagon,
  Clock,
  Layers
} from 'lucide-vue-next'
import { computed } from 'vue'

// Dark Mode
const { isDark } = useDarkMode()

const props = defineProps<{
  userName: string
  userRole: string
  statCounts: Record<string, number>
  recentProjects: any[]
  approvalList: any[]
  slaOverdueCount: number
  quickStats: {
    activeProjects: number
    monthlyTarget: number
    approvalWaiting: number
  }
  analytics: {
    trendData: { month: string; created: number; completed: number }[]
    regionData: { name: string; count: number }[]
    cityData: { name: string; count: number; province?: string; projects?: any[] }[]
  }
}>()

// ==================
// Status Mapping (Simplified for Bento)
// ==================
const statusItems = computed(() => [
  { key: 'planning', label: 'Planning', count: props.statCounts['PLANNING'] ?? props.statCounts['PLAN ON DESK'] ?? 0, icon: Map, color: 'text-blue-600', barColor: 'bg-blue-600' },
  { key: 'spk_survey', label: 'SPK Survey', count: props.statCounts['SPK SURVEY'] ?? 0, icon: ClipboardList, color: 'text-indigo-600', barColor: 'bg-indigo-600' },
  { key: 'survey', label: 'Survey DRM', count: (props.statCounts['SURVEY DRM'] ?? 0) + (props.statCounts['SURVEY'] ?? 0), icon: Search, color: 'text-violet-600', barColor: 'bg-violet-600' },
  { key: 'impl', label: 'Implementasi', count: (props.statCounts['IMPLEMENTASI'] ?? 0) + (props.statCounts['SPK_IMPLEMENTASI'] ?? 0), icon: HardHat, color: 'text-orange-600', barColor: 'bg-orange-600' },
  { key: 'konstruksi', label: 'Konstruksi', count: props.statCounts['KONSTRUKSI'] ?? 0, icon: HardHat, color: 'text-amber-600', barColor: 'bg-amber-600' },
  { key: 'live', label: 'Go-Live', count: props.statCounts['GO LIVE'] ?? props.statCounts['GO-LIVE'] ?? 0, icon: Radio, color: 'text-emerald-600', barColor: 'bg-emerald-600' },
  { key: 'close', label: 'Handover', count: props.statCounts['HANDOVER'] ?? 0, icon: Building2, color: 'text-slate-600', barColor: 'bg-slate-600' },
])

// ================== 
// Helpers
// ==================
function formatProjectDate(value?: string | null) {
  if (!value) return '-'
  const d = new Date(value)
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })
}

function fmtTimeRemaining(hoursDecimal: number) {
  const totalMinutes = Math.floor(hoursDecimal * 60)
  const hours = Math.floor(totalMinutes / 60)
  const minutes = totalMinutes % 60
  if (hours === 0) return `${minutes}m`
  return `${hours}h ${minutes}m`
}

// ==================
// Dynamic Greeting Based on Time
// ==================
const timeOfDay = computed(() => {
  const hour = new Date().getHours()
  if (hour >= 5 && hour < 11) return 'morning'      // 05:00 - 10:59
  if (hour >= 11 && hour < 15) return 'afternoon'   // 11:00 - 14:59
  if (hour >= 15 && hour < 19) return 'evening'     // 15:00 - 18:59
  return 'night'                                     // 19:00 - 04:59
})

const greeting = computed(() => {
  const greetings = {
    morning: 'Selamat Pagi',
    afternoon: 'Selamat Siang',
    evening: 'Selamat Sore',
    night: 'Selamat Malam'
  }
  return greetings[timeOfDay.value]
})

const greetingColors = computed(() => {
  const colors = {
    morning: {
      // Fresh, calm, clean start
      gradient: 'from-sky-400 via-blue-500 to-blue-600',
      border: 'border-sky-400/30',
      shadow: 'shadow-sky-300/50',
      circle: 'bg-white/20'
    },
    afternoon: {
      gradient: 'from-amber-300 via-yellow-400 to-orange-400',
      border: 'border-amber-400/40',
      shadow: 'shadow-amber-300/60',
      circle: 'bg-yellow-200/30',
      text: 'text-slate-800',
      subtext: 'text-slate-600'
   },
    evening: {
      // Warm, emotional, transition
      gradient: 'from-orange-500 via-rose-500 to-pink-600',
      border: 'border-rose-400/30',
      shadow: 'shadow-rose-300/50',
      circle: 'bg-orange-300/20'
    },
    night: {
      // Calm, focus, rest
      gradient: 'from-indigo-800 via-slate-900 to-black',
      border: 'border-indigo-500/20',
      shadow: 'shadow-indigo-400/40',
      circle: 'bg-indigo-300/10'
    }
  }
  return colors[timeOfDay.value]
})
</script> 

<template>
  <Head>
    <title>Dashboard</title>
    <link rel="icon" type="image/png" :href="supercorridor" />
  </Head>

  <AppLayout title="Dashboard" currentPage="dashboard">
    <div class="max-w-[1400px] mx-auto">
      
      <!-- Bento Grid Container -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 auto-rows-[minmax(100px,auto)]">
        
        <!-- BLOCK 1: Welcome & SLA (Top Left, 4 cols) -->
        <div class="lg:col-span-4 flex flex-col gap-6">
           <div :class="['bg-gradient-to-br rounded-3xl p-8 flex flex-col justify-between h-full shadow-md border relative overflow-hidden group text-white transform-gpu will-change-transform', greetingColors.gradient, greetingColors.border]">
              <!-- Decorative Circle -->
              <div :class="['absolute -right-10 -top-10 w-40 h-40 rounded-full transition-transform duration-500', greetingColors.circle]"></div>
              
              <div>
                <h1 class="text-2xl font-bold tracking-tight">{{ greeting }}, {{ props.userName }}!</h1>
                <p class="text-white/80 mt-2 text-sm leading-relaxed capitalize">{{ props.userRole }}</p>
              </div>

              <div v-if="props.slaOverdueCount > 0" class="mt-6 flex items-center gap-3 bg-white/95 p-4 rounded-2xl border border-rose-100 text-slate-800">
                 <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                    <AlertOctagon class="w-5 h-5" />
                 </div>
                 <div>
                    <div class="text-lg font-bold text-rose-700 leading-none">{{ props.slaOverdueCount }}</div>
                    <div class="text-xs text-rose-500 font-medium">Overdue Projects</div>
                 </div>
                 <Link href="/projects/sla-overdue" class="ml-auto w-8 h-8 flex items-center justify-center rounded-full bg-rose-500 text-white hover:bg-rose-600 transition-colors">
                    <ArrowRight class="w-4 h-4" />
                 </Link>
              </div>
              <div v-else class="mt-6 flex items-center gap-3 bg-white/95 p-3 rounded-2xl text-slate-800">
                 <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <CheckCircle2 class="w-5 h-5" />
                 </div>
                 <div class="text-sm font-medium">All systems normal.</div>
              </div>
           </div>
        </div>
        <!-- Quick Stats Mini Cards (BLOCK 1.5) -->
         <QuickStats
           class="lg:col-span-8"
           :activeProjects="props.quickStats.activeProjects"
           :monthlyTarget="props.quickStats.monthlyTarget"
           :approvalWaiting="props.quickStats.approvalWaiting"
           :isDark="isDark"
         />

        <!-- BLOCK 2: Status Pulse (Full Width) -->
        <div :class="[
           'lg:col-span-12 rounded-3xl p-3 shadow-sm border transition-colors duration-300',
           isDark ? 'bg-gradient-to-br from-slate-800 to-slate-900 border-slate-700' : 'bg-gradient-to-br from-slate-50 to-slate-100 border-slate-200/50'
        ]">
           <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 h-full gap-2">
              <div 
                v-for="(stat, index) in statusItems" 
                :key="stat.key" 
                :class="[
                   'rounded-2xl hover:shadow-md transition-all p-4 flex flex-col justify-between items-center text-center group cursor-default border',
                   isDark ? 'bg-slate-800/80 border-slate-700 hover:bg-slate-700/80' : 'bg-white border-slate-100'
                ]"
              >
                 <component :is="stat.icon" :class="['w-6 h-6 mb-2 transition-transform group-hover:scale-110', stat.color]" />
                 <div class="w-full">
                    <div :class="['text-2xl font-bold', isDark ? 'text-white' : 'text-slate-800']">{{ stat.count }}</div>
                    <div :class="['text-[10px] font-bold uppercase tracking-widest mt-1 mb-2', isDark ? 'text-slate-400' : 'text-slate-400']">{{ stat.label }}</div>
                    
                    <!-- Mini Progress Bar -->
                    <div :class="['w-full h-1 rounded-full overflow-hidden', isDark ? 'bg-slate-700' : 'bg-slate-100']">
                       <div 
                         :class="['h-full rounded-full transition-all duration-1000 ease-out', stat.barColor]"
                         :style="{ width: stat.count > 0 ? '100%' : '0%' }"
                       ></div>
                    </div>
                 </div>
              </div>
           </div>
        </div>

        <!-- BLOCK 2.5: Project Stats Chart (Full Width) -->
        <div class="lg:col-span-12">
            <DashboardAnalytics 
              :trendData="props.analytics.trendData"
              :regionData="props.analytics.regionData"
              :cityData="props.analytics.cityData"
              :isDark="isDark"
            />
        </div>

        <!-- BLOCK 3: Focus Area / Inbox (Middle Left, 8 cols) -->
        <div :class="[
           'lg:col-span-8 lg:row-span-1 rounded-3xl p-6 shadow-sm border flex flex-col transition-colors duration-300',
           isDark ? 'bg-slate-800 border-slate-700' : 'bg-white border-slate-100/50'
        ]" style="min-height: 520px;">
           <div class="flex justify-between items-center mb-5">
              <div class="flex items-center gap-3">
                 <div :class="['w-1.5 h-6 rounded-full', isDark ? 'bg-blue-400' : 'bg-slate-800']"></div>
                 <h2 :class="['text-lg font-bold tracking-tight', isDark ? 'text-white' : 'text-slate-800']">Menunggu Approval</h2>
                 <span :class="['px-2 py-0.5 rounded-lg text-xs font-bold', isDark ? 'bg-slate-700 text-slate-300' : 'bg-slate-100 text-slate-600']">{{ props.approvalList.length }} dari {{ props.quickStats.approvalWaiting }}</span>
              </div>
              <Link href="/approval" :class="['text-xs font-bold uppercase tracking-wider transition-colors', isDark ? 'text-slate-400 hover:text-white' : 'text-slate-400 hover:text-slate-800']">View All</Link>
           </div>

           <div :class="['flex-1 overflow-y-auto pr-2 space-y-3', isDark ? 'custom-scrollbar-dark' : 'custom-scrollbar']">
              <div v-if="!props.approvalList.length" :class="['h-full flex flex-col items-center justify-center', isDark ? 'text-slate-500' : 'text-slate-300']">
                 <Layers class="w-12 h-12 opacity-20 mb-2" />
                 <p class="text-sm">No pending items.</p>
              </div>

              <Link 
                v-for="(item, idx) in props.approvalList" 
                :key="item.id || idx" 
                :href="item.href"
                :class="[
                   'group rounded-2xl p-4 flex items-center gap-5 hover:shadow-md transition-all duration-300 cursor-pointer h-20 border',
                   isDark ? 'bg-slate-700/50 border-slate-600 hover:border-blue-500/50' : 'bg-white border-slate-100 hover:border-blue-200'
                ]"
              >
                 <!-- Priority Dot -->
                 <div :class="[
                    'w-3 h-3 rounded-full shrink-0 ring-4 ring-opacity-20',
                    item.sla?.is_overdue ? 'bg-rose-500 ring-rose-500' : (item.sla?.hours_remaining <= 4 ? 'bg-amber-400 ring-amber-400' : 'bg-emerald-400 ring-emerald-400')
                 ]"></div>

                 <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                       <span :class="['text-[10px] font-bold px-1.5 py-0.5 rounded uppercase', isDark ? 'bg-slate-600 text-slate-300' : 'bg-slate-100 text-slate-500']">{{ item.type }}</span>
                       <span :class="['text-[10px] font-mono', isDark ? 'text-slate-400' : 'text-slate-400']">{{ item.code }}</span>
                    </div>
                    <h4 :class="['font-bold text-sm truncate', isDark ? 'text-white' : 'text-slate-800']">{{ item.name }}</h4>
                 </div>

                 <!-- Meta & Action -->
                 <div class="hidden sm:flex flex-col items-end gap-1 text-right">
                    <span :class="['text-[10px] font-bold uppercase tracking-wider', isDark ? 'text-slate-500' : 'text-slate-400']">Step</span>
                    <span :class="['text-xs font-medium', isDark ? 'text-slate-300' : 'text-slate-600']">{{ item.step }}</span>
                 </div>

                 <div class="text-right min-w-[80px]">
                    <div v-if="item.sla?.is_overdue" class="text-xs font-bold text-rose-500">Overdue</div>
                    <div v-else-if="item.sla?.hours_remaining" class="text-xs font-bold text-amber-500">{{ fmtTimeRemaining(item.sla.hours_remaining) }}</div>
                    <div v-else :class="['text-xs font-bold', isDark ? 'text-slate-400' : 'text-slate-400']">{{ formatProjectDate(item.date) }}</div>
                    <div :class="['text-[10px] uppercase tracking-wider mt-0.5', isDark ? 'text-slate-500' : 'text-slate-400']">Deadline</div>
                 </div>

                 <div :class="['w-10 h-10 rounded-xl flex items-center justify-center transition-all group-hover:bg-blue-600 group-hover:text-white', isDark ? 'bg-slate-600 text-slate-400' : 'bg-slate-50 text-slate-400']">
                    <ArrowRight class="w-5 h-5" />
                 </div>
              </Link>
           </div>
        </div>

        <!-- BLOCK 4: Proyek Terbaru (Middle Right, 4 cols) -->
        <div :class="[
           'lg:col-span-4 lg:row-span-1 rounded-3xl p-6 shadow-sm border flex flex-col relative overflow-hidden transition-colors duration-300',
           isDark ? 'bg-slate-800 border-slate-700' : 'bg-white border-slate-100/50'
        ]" style="min-height: 520px;">
           
           <div class="flex justify-between items-center mb-5">
              <div class="flex items-center gap-3">
                 <div :class="['w-1.5 h-6 rounded-full', isDark ? 'bg-blue-400' : 'bg-slate-800']"></div>
                 <h2 :class="['text-lg font-bold tracking-tight', isDark ? 'text-white' : 'text-slate-800']">Proyek Terbaru</h2>
              </div>
              <Link href="/projects" :class="['text-xs font-bold uppercase tracking-wider transition-colors', isDark ? 'text-slate-400 hover:text-white' : 'text-slate-400 hover:text-slate-800']">
                 View All
              </Link>
           </div>

           <div :class="['flex-1 overflow-y-auto pr-2 space-y-3', isDark ? 'custom-scrollbar-dark' : 'custom-scrollbar']">
              <div v-if="!props.recentProjects.length" :class="['h-full flex flex-col items-center justify-center', isDark ? 'text-slate-500' : 'text-slate-300']">
                 <Layers class="w-12 h-12 opacity-20 mb-2" />
                 <p class="text-sm">No recent projects.</p>
              </div>

              <Link 
                v-for="(project, i) in props.recentProjects" 
                :key="project.id"
                :href="`/projects/project-detail?id=${project.id}`"
                :class="[
                   'group rounded-2xl p-4 flex items-center gap-4 hover:shadow-md transition-all duration-300 cursor-pointer h-20 border',
                   isDark ? 'bg-slate-700/50 border-slate-600 hover:border-blue-500/50' : 'bg-white border-slate-100 hover:border-blue-200'
                ]"
              >
                 <!-- Calendar Icon -->
                 <div :class="['w-12 h-12 rounded-xl overflow-hidden shrink-0 shadow-sm border flex flex-col', isDark ? 'border-slate-600' : 'border-slate-200']">
                    <!-- Month Header (Red like classic calendar) -->
                    <div class="bg-gradient-to-r from-rose-500 to-rose-600 text-white text-[7px] font-bold uppercase text-center py-0.5 tracking-wider">
                       {{ new Date(project.created_at).toLocaleString('id-ID', { month: 'short' }) }}
                    </div>
                    <!-- Date Body -->
                    <div :class="['flex-1 flex flex-col items-center justify-center', isDark ? 'bg-slate-700' : 'bg-white']">
                       <div :class="['text-base font-bold leading-none -mt-0.5', isDark ? 'text-white' : 'text-slate-800']">{{ new Date(project.created_at).getDate() }}</div>
                       <div :class="['text-[8px] font-bold uppercase leading-none mt-0.5', isDark ? 'text-slate-400' : 'text-slate-400']">{{ new Date(project.created_at).toLocaleString('id-ID', { weekday: 'short' }) }}</div>
                    </div>
                 </div>

                 <div class="flex-1 min-w-0">
                    <h4 :class="['font-bold text-sm truncate group-hover:text-blue-500 transition-colors', isDark ? 'text-white' : 'text-slate-800']">{{ project.name }}</h4>
                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                       <span :class="['text-[10px] font-mono', isDark ? 'text-slate-400' : 'text-slate-400']">{{ project.code }}</span>
                       <span :class="['text-[10px] px-1.5 py-0.5 rounded font-semibold border', isDark ? 'bg-slate-600 text-slate-300 border-slate-500' : 'bg-slate-100 text-slate-600 border-slate-200']">{{ project.status }}</span>
                    </div>
                 </div>

                 <div :class="['w-10 h-10 rounded-xl flex items-center justify-center transition-all group-hover:bg-blue-600 group-hover:text-white', isDark ? 'bg-slate-600 text-slate-400' : 'bg-slate-50 text-slate-400']">
                    <ArrowRight class="w-5 h-5" />
                 </div>
              </Link>
           </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

.custom-scrollbar-dark::-webkit-scrollbar { width: 4px; }
.custom-scrollbar-dark::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar-dark::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
.custom-scrollbar-dark::-webkit-scrollbar-thumb:hover { background: #475569; }
</style>
