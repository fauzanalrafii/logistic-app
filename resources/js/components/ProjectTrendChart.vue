<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  data?: { month: string; created: number; completed: number }[]
}>()

// Dummy Data Default (jika tidak ada props)
const chartData = computed(() => props.data || [
  { month: 'Jul', created: 12, completed: 8 },
  { month: 'Aug', created: 19, completed: 15 },
  { month: 'Sep', created: 15, completed: 10 },
  { month: 'Oct', created: 25, completed: 22 },
  { month: 'Nov', created: 30, completed: 18 },
  { month: 'Dec', created: 22, completed: 28 },
])

const maxVal = computed(() => Math.max(...chartData.value.map(d => Math.max(d.created, d.completed))) * 1.2)

const getHeight = (val: number) => {
  return `${(val / maxVal.value) * 100}%`
}
</script>

<template>
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
    <div class="flex items-center justify-between mb-8">
      <div>
         <h3 class="font-bold text-slate-800 text-lg">Tren Proyek 6 Bulan Terakhir</h3>
         <p class="text-sm text-slate-400">Perbandingan proyek masuk vs selesai</p>
      </div>
      <div class="flex items-center gap-4 text-xs font-semibold">
         <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
            <span class="text-slate-600">Proyek Masuk</span>
         </div>
         <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
            <span class="text-slate-600">Selesai</span>
         </div>
      </div>
    </div>

    <!-- Chart Container -->
    <div class="h-64 flex items-end justify-between gap-2 sm:gap-4 relative">
      <!-- Grid Lines -->
      <div class="absolute inset-0 flex flex-col justify-between pointer-events-none opacity-50">
        <div class="w-full h-px bg-slate-100 dashed"></div>
        <div class="w-full h-px bg-slate-100 dashed"></div>
        <div class="w-full h-px bg-slate-100 dashed"></div>
        <div class="w-full h-px bg-slate-100 dashed"></div>
        <div class="w-full h-px bg-slate-100 bg-slate-200"></div> <!-- Base line -->
      </div>

      <!-- Bars -->
      <div v-for="(item, idx) in chartData" :key="idx" class="relative flex-1 flex flex-col justify-end items-center h-full group">
         <!-- Tooltip (Simple) -->
         <div class="absolute -top-12 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-800 text-white text-[10px] rounded px-2 py-1 pointer-events-none z-10 whitespace-nowrap mb-1">
            {{ item.month }}: {{ item.created }} Masuk / {{ item.completed }} Selesai
         </div>

         <div class="w-full h-full flex items-end justify-center gap-1 sm:gap-3 px-1">
            <!-- Bar 1: Created -->
            <div 
              class="w-full max-w-[16px] sm:max-w-[24px] bg-blue-500 rounded-t-lg transition-all duration-500 hover:bg-blue-600 relative group/bar"
              :style="{ height: getHeight(item.created) }"
            ></div>
            
            <!-- Bar 2: Completed -->
            <div 
              class="w-full max-w-[16px] sm:max-w-[24px] bg-emerald-400 rounded-t-lg transition-all duration-500 hover:bg-emerald-500 relative group/bar"
              :style="{ height: getHeight(item.completed) }"
            ></div>
         </div>
         
         <!-- X Axis Label -->
         <div class="mt-3 text-xs font-bold text-slate-400 uppercase tracking-widest">{{ item.month }}</div>
      </div>
    </div>
  </div>
</template>
