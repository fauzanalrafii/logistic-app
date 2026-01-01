<script setup lang="ts">
import { computed, ref } from 'vue'
import { MapPin } from 'lucide-vue-next'
import DashboardMap from '@/components/DashboardMap.vue'

const props = defineProps<{
  trendData: { month: string; created: number; completed: number }[]
  regionData: { name: string; count: number }[]
  cityData: { name: string; count: number; province?: string; projects?: any[] }[]
  isDark?: boolean
}>()

const selectedRange = ref(6) // Default 6 months
const ranges = [3, 6, 12]

// Filter data based on selection (take last N items)
// Filter data based on selection (take last N items)
const filteredTrendData = computed(() => {
   if (!props.trendData) return []
   // Ensure we handle case where data < range gracefully
   const len = props.trendData.length
   const range = selectedRange.value
   const start = Math.max(0, len - range)
   return props.trendData.slice(start)
})

const maxTrendVal = computed(() => {
   if (!filteredTrendData.value.length) return 10
   const max = Math.max(...filteredTrendData.value.map(d => Math.max(d.created, d.completed)))
   // Round up to nearest 5 or 10 for cleaner axis
   return Math.ceil(max / 5) * 5
})

const yAxisTicks = computed(() => {
  const max = maxTrendVal.value
  return [
    max,
    max * 0.75,
    max * 0.5,
    max * 0.25,
    0
    ].map(val => Math.round(val))
  })

const getHeight = (val: number) => `${(val / maxTrendVal.value) * 100}%`

// --- REGION CHART LOGIC (Restored) ---
const top5Regions = computed(() => {
   if (!props.regionData) return []
   return [...props.regionData]
      .sort((a, b) => b.count - a.count)
      .slice(0, 5)
})

const totalProjects = computed(() => {
   if (!top5Regions.value) return 0
   return top5Regions.value.reduce((acc, curr) => acc + curr.count, 0)
})

const getWidth = (val: number) => {
   if (totalProjects.value === 0) return '0%'
   if (val === 0) return '0%'
   const calculatedWidth = (val / totalProjects.value) * 100
   return `${Math.max(calculatedWidth, 10)}%`
}

const regionColors = [
  'bg-blue-500',
  'bg-indigo-600',
  'bg-emerald-400', 
  'bg-amber-400',
  'bg-rose-500'
]

const getRegionColor = (index: number) => {
  return regionColors[index % regionColors.length]
}
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- CHART 1: Monthly Trend (Line/Bar Hybrid) -->
    <div :class="['lg:col-span-2 rounded-3xl p-6 shadow-sm border flex flex-col transition-colors duration-300', isDark ? 'bg-slate-800 border-slate-700' : 'bg-white border-slate-100']">
      <div class="flex items-center justify-between mb-6">
        <div>
           <div class="flex items-center gap-3">
              <div :class="['w-1.5 h-6 rounded-full', isDark ? 'bg-blue-400' : 'bg-slate-800']"></div>
              <h3 :class="['font-bold text-lg', isDark ? 'text-white' : 'text-slate-800']">Tren Proyek</h3>
           </div>
           
           <div class="flex items-center gap-2 mt-3 ml-4.5">
             <button 
                v-for="range in ranges" 
                :key="range"
                @click="selectedRange = range"
                :class="[
                    'text-xs px-2 py-1 rounded-lg transition-colors font-medium border',
                    selectedRange === range 
                        ? (isDark ? 'bg-blue-900/50 text-blue-400 border-blue-700' : 'bg-blue-50 text-blue-600 border-blue-100')
                        : (isDark ? 'bg-transparent text-slate-400 border-transparent hover:text-white hover:bg-slate-700' : 'bg-white text-slate-400 border-transparent hover:text-slate-600 hover:bg-slate-50')
                ]"
             >
                {{ range }} Bln
             </button>
           </div>
        </div>
        <div class="flex items-center gap-4 text-xs font-semibold">
           <!-- Legend -->
           <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-blue-500"></div>
              <span :class="isDark ? 'text-slate-300' : 'text-slate-600'">Masuk</span>
           </div>
           <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
              <span :class="isDark ? 'text-slate-300' : 'text-slate-600'">Selesai</span>
           </div>
        </div>
      </div>

      <div class="flex-1 min-h-[200px] flex gap-4 relative pt-4">
         <!-- Y-Axis Labels -->
         <div class="flex flex-col justify-between text-xs font-bold text-slate-300 pb-6 text-right w-6 shrink-0">
            <div v-for="tick in yAxisTicks" :key="tick">{{ tick }}</div>
         </div>

         <!-- Chart Area -->
         <div class="flex-1 relative flex items-end justify-between gap-2 sm:gap-4">
             <!-- Grid Lines -->
             <div class="absolute inset-0 flex flex-col justify-between pointer-events-none opacity-50 pb-6">
                <div class="w-full h-px bg-slate-50 border-t border-slate-100 dashed"></div>
                <div class="w-full h-px bg-slate-50 border-t border-slate-100 dashed"></div>
                <div class="w-full h-px bg-slate-50 border-t border-slate-100 dashed"></div>
                <div class="w-full h-px bg-slate-50 border-t border-slate-100 dashed"></div>
                <div class="w-full h-px bg-slate-200"></div>
             </div>

             <!-- Bars -->
             <div v-for="(item, idx) in filteredTrendData" :key="item.month" class="relative flex-1 flex flex-col justify-end items-center h-full group z-10">
                <div class="w-full flex items-end justify-center gap-1 sm:gap-2 px-1 h-full">
                    <!-- Bar Created -->
                    <div class="w-full max-w-[12px] sm:max-w-[16px] bg-blue-500 rounded-t-sm transition-all duration-500 hover:bg-blue-600 relative group/bar" :style="{ height: getHeight(item.created) }">
                       <div class="absolute -top-6 left-1/2 -translate-x-1/2 opacity-0 group-hover/bar:opacity-100 bg-slate-800 text-white text-[9px] px-1.5 py-0.5 rounded transition-opacity">
                          {{ item.created }}
                       </div>
                    </div>
                    <!-- Bar Completed -->
                    <div class="w-full max-w-[12px] sm:max-w-[16px] bg-emerald-400 rounded-t-sm transition-all duration-500 hover:bg-emerald-500 relative group/bar" :style="{ height: getHeight(item.completed) }">
                       <div class="absolute -top-6 left-1/2 -translate-x-1/2 opacity-0 group-hover/bar:opacity-100 bg-slate-800 text-white text-[9px] px-1.5 py-0.5 rounded transition-opacity">
                          {{ item.completed }}
                       </div>
                    </div>
                </div>
                <div class="mt-3 text-xs font-bold text-slate-400">{{ item.month }}</div>
             </div>
         </div>
      </div>
    </div>

     <!-- CHART 2: Region Distribution (List) - RESTORED -->
    <div :class="['rounded-3xl p-6 shadow-sm border flex flex-col transition-colors duration-300', isDark ? 'bg-slate-800 border-slate-700' : 'bg-white border-slate-100']">
       <div class="flex items-center justify-between mb-6">
          <div>
             <div class="flex items-center gap-3">
               <div :class="['w-1.5 h-6 rounded-full', isDark ? 'bg-blue-400' : 'bg-slate-800']"></div>
               <h3 :class="['font-bold text-lg', isDark ? 'text-white' : 'text-slate-800']">Sebaran Wilayah</h3>
             </div>
             <p :class="['text-sm ml-4.5 pl-0.5', isDark ? 'text-slate-400' : 'text-slate-400']">5 Wilayah Proyek Terbanyak</p>
          </div>
          <div :class="['w-8 h-8 rounded-full flex items-center justify-center', isDark ? 'bg-slate-700' : 'bg-slate-50']">
             <MapPin :class="['w-4 h-4', isDark ? 'text-slate-400' : 'text-slate-400']" />
          </div>
       </div>

       <div class="flex-1 flex flex-col justify-center gap-5">
          <div v-for="(region, index) in top5Regions" :key="region.name" class="space-y-1">
             <div class="flex justify-between text-sm">
                <span :class="['font-medium', isDark ? 'text-slate-300' : 'text-slate-700']">{{ region.name }}</span>
                <span :class="['font-bold', isDark ? 'text-white' : 'text-slate-800']">{{ region.count }}</span>
             </div>
             <div :class="['w-full h-1.5 rounded-full overflow-hidden', isDark ? 'bg-slate-700' : 'bg-slate-50']">
                <div :class="['h-full rounded-full', getRegionColor(index)]" :style="{ width: getWidth(region.count) }"></div>
             </div>
          </div>
       </div>
    </div>
  </div>

  <!-- ROW 2: Map (Full Width) -->
  <div class="grid grid-cols-1 mt-6">
     <div :class="['rounded-3xl shadow-sm border flex flex-col h-[400px] lg:h-[650px] overflow-hidden transition-colors duration-300', isDark ? 'bg-slate-800 border-slate-700' : 'bg-white border-slate-100']">
        <div class="flex items-center justify-between mb-2 px-6 pt-6">
           <div>
              <div class="flex items-center gap-3">
                <div :class="['w-1.5 h-6 rounded-full', isDark ? 'bg-blue-400' : 'bg-slate-800']"></div>
                <h3 :class="['font-bold text-lg', isDark ? 'text-white' : 'text-slate-800']">Peta Sebaran Proyek</h3>
              </div>
              <p :class="['text-sm ml-4.5 pl-0.5', isDark ? 'text-slate-400' : 'text-slate-400']">Distribusi berdasarkan Kota</p>
           </div>
        </div>

        <div class="flex-1 w-full relative z-0">
             <DashboardMap :regionData="props.cityData" :isDark="isDark" />
        </div>
     </div>
  </div>
</template>
