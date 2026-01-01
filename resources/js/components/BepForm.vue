<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  capex: number
  opexEstimate: number
  revenueEstimate: number
  readonly?: boolean
}>()

const emit = defineEmits<{
  'update:capex': [value: number]
  'update:opexEstimate': [value: number]
  'update:revenueEstimate': [value: number]
}>()

function formatRupiah(value: number) {
  return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(value)
}

function safeNumber(n: any) {
  const x = Number(n)
  return Number.isFinite(x) ? x : 0
}

const netProfit = computed(() => 
  Math.max(0, safeNumber(props.revenueEstimate) - safeNumber(props.opexEstimate))
)

const bepMonths = computed(() => {
  const c = safeNumber(props.capex)
  const net = netProfit.value
  if (c === 0) return 0
  if (net <= 0) return Infinity
  return Math.ceil(c / net)
})

const bepDisplay = computed(() => {
  const months = bepMonths.value
  if (months === Infinity) return '∞ bulan'
  return `${months} bulan`
})
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    <!-- Input Parameters -->
    <div class="space-y-4">
      <div>
        <label for="capex-input" class="block text-xs font-medium text-slate-700 mb-1.5">
          CAPEX (Investasi Awal)
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
          <input 
            id="capex-input"
            :value="capex"
            @input="emit('update:capex', Number(($event.target as HTMLInputElement).value))"
            type="number"
            min="0"
            class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            :class="readonly ? 'bg-slate-50 text-slate-600' : 'bg-white'"
            :readonly="readonly"
          />
        </div>
      </div>
      <div>
        <label for="opex-input" class="block text-xs font-medium text-slate-700 mb-1.5">
          OPEX Estimasi (Per Bulan)
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
          <input 
            id="opex-input"
            :value="opexEstimate"
            @input="emit('update:opexEstimate', Number(($event.target as HTMLInputElement).value))"
            type="number"
            min="0"
            class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            :class="readonly ? 'bg-slate-50 text-slate-600' : 'bg-white'"
            :readonly="readonly"
          />
        </div>
      </div>
      <div>
        <label for="revenue-input" class="block text-xs font-medium text-slate-700 mb-1.5">
          Pendapatan Estimasi (Per Bulan)
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
          <input 
            id="revenue-input"
            :value="revenueEstimate"
            @input="emit('update:revenueEstimate', Number(($event.target as HTMLInputElement).value))"
            type="number"
            min="0"
            class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            :class="readonly ? 'bg-slate-50 text-slate-600' : 'bg-white'"
            :readonly="readonly"
          />
        </div>
      </div>
    </div>

    <!-- Results - Compact -->
    <div class="space-y-4">
      <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
        <div class="text-xs font-medium text-green-700 uppercase tracking-wide mb-1">
          Profit Bersih / Bulan
        </div>
        <div class="text-2xl font-bold text-green-900">
          Rp {{ formatRupiah(netProfit) }}
        </div>
      </div>
      <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="text-xs font-medium text-blue-700 uppercase tracking-wide mb-1">
          Break Even Point
        </div>
        <div class="text-2xl font-bold text-blue-900">
          {{ bepDisplay }}
        </div>
      </div>
    </div>

  </div>
</template>
