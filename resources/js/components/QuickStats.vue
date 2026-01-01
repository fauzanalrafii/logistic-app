<script setup lang="ts">
import { Clock, Layers, ClipboardList } from 'lucide-vue-next'

defineProps<{
  activeProjects: number
  monthlyTarget: number
  approvalWaiting: number
  isDark?: boolean
}>()
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 h-full">
    <!-- Active Projects -->
    <div :class="[
       'rounded-3xl p-5 shadow-sm border hover:shadow-md transition-all group relative overflow-hidden flex flex-col justify-between h-full',
       isDark ? 'bg-slate-800 border-slate-700' : 'bg-white border-slate-100'
    ]">
      <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
        <Layers class="w-24 h-24 text-blue-600 -mr-4 -mt-4 transform rotate-12" />
      </div>
      
      <div class="flex justify-between items-start relative z-10">
        <div :class="['text-xs font-bold uppercase tracking-wider', isDark ? 'text-slate-400' : 'text-slate-400']">Proyek Aktif</div>
        <div :class="['w-10 h-10 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform', isDark ? 'bg-blue-900/50 text-blue-400' : 'bg-blue-50 text-blue-600']">
          <Layers class="w-5 h-5" />
        </div>
      </div>
      
      <div class="relative z-10 mt-2">
        <div :class="['text-3xl font-bold tracking-tight', isDark ? 'text-white' : 'text-slate-800']">{{ activeProjects }}</div>
        <div :class="['text-xs font-medium mt-1', isDark ? 'text-slate-500' : 'text-slate-400']">Sedang berjalan</div>
      </div>
    </div>

    <!-- Monthly Target -->
    <div :class="[
       'rounded-3xl p-5 shadow-sm border hover:shadow-md transition-all group relative overflow-hidden flex flex-col justify-between h-full',
       isDark ? 'bg-slate-800 border-slate-700' : 'bg-white border-slate-100'
    ]">
      <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
        <Clock class="w-24 h-24 text-amber-600 -mr-4 -mt-4 transform rotate-12" />
      </div>

      <div class="flex justify-between items-start relative z-10">
        <div :class="['text-xs font-bold uppercase tracking-wider', isDark ? 'text-slate-400' : 'text-slate-400']">Target Bulan Ini</div>
        <div :class="['w-10 h-10 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform', isDark ? 'bg-amber-900/50 text-amber-400' : 'bg-amber-50 text-amber-600']">
          <Clock class="w-5 h-5" />
        </div>
      </div>
      
      <div class="relative z-10 mt-2">
        <div :class="['text-3xl font-bold tracking-tight', isDark ? 'text-white' : 'text-slate-800']">{{ monthlyTarget }}</div>
        <div :class="['text-xs font-medium mt-1', isDark ? 'text-slate-500' : 'text-slate-400']">Due date bulan ini</div>
      </div>
    </div>

    <!-- Approval Waiting -->
    <div :class="[
       'rounded-3xl p-5 shadow-sm border hover:shadow-md transition-all group relative overflow-hidden flex flex-col justify-between h-full',
       isDark ? 'bg-slate-800 border-slate-700' : 'bg-white border-slate-100'
    ]">
       <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
         <ClipboardList :class="[
            'w-24 h-24 -mr-4 -mt-4 transform rotate-12',
            approvalWaiting > 0 ? 'text-rose-600' : 'text-emerald-600'
        ]" />
      </div>

      <div class="flex justify-between items-start relative z-10">
        <div :class="['text-xs font-bold uppercase tracking-wider', isDark ? 'text-slate-400' : 'text-slate-400']">Menunggu Approval</div>
        <div :class="[
          'w-10 h-10 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform',
          approvalWaiting > 0 
            ? (isDark ? 'bg-rose-900/50 text-rose-400' : 'bg-rose-50 text-rose-600')
            : (isDark ? 'bg-emerald-900/50 text-emerald-400' : 'bg-emerald-50 text-emerald-600')
        ]">
          <ClipboardList class="w-5 h-5" />
        </div>
      </div>
      
      <div class="relative z-10 mt-2">
        <div class="flex items-baseline gap-1">
          <div :class="['text-3xl font-bold tracking-tight', isDark ? 'text-white' : 'text-slate-800']">{{ approvalWaiting }}</div>
          <div :class="['text-sm font-semibold', isDark ? 'text-slate-400' : 'text-slate-500']">Item</div>
        </div>
        <div :class="['text-xs font-medium mt-1', isDark ? 'text-slate-500' : 'text-slate-400']">
            {{ approvalWaiting > 0 ? 'Perlu tindakan segera' : 'Semua aman' }}
        </div>
      </div>
    </div>
  </div>
</template>

