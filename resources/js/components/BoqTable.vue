<script setup lang="ts">
import { computed } from 'vue'
import { Plus, Trash2 } from 'lucide-vue-next'

interface BoqItem {
  id?: number
  material_code: string
  material_description: string
  uom: string
  qty: number
  unit_price_estimate: number
  remarks: string
}

const props = defineProps<{
  items: BoqItem[]
  type: 'ON_DESK' | 'ON_SITE'
  version: number
  readonly?: boolean
}>()

const emit = defineEmits<{
  'update:items': [items: BoqItem[]]
  'update:type': [type: 'ON_DESK' | 'ON_SITE']
  'add-item': []
  'remove-item': [index: number]
}>()

function formatRupiah(value: number) {
  return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(value)
}

function safeNumber(n: any) {
  const x = Number(n)
  return Number.isFinite(x) ? x : 0
}

const rowSubtotal = (r: BoqItem) => safeNumber(r.qty) * safeNumber(r.unit_price_estimate)

const total = computed(() =>
  props.items.reduce((sum, r) => sum + rowSubtotal(r), 0)
)

function updateItem(index: number, field: keyof BoqItem, value: any) {
  const newItems = [...props.items]
  newItems[index] = { ...newItems[index], [field]: value }
  emit('update:items', newItems)
}
</script>

<template>
  <div>
    <!-- Header Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg">
        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-3">Pengaturan</div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-medium text-slate-700 mb-1.5 block">Tipe</label>
            <select 
              :value="type"
              @input="emit('update:type', ($event.target as HTMLSelectElement).value as any)"
              class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
              :disabled="readonly"
            >
              <option value="ON_DESK">ON_DESK</option>
              <option value="ON_SITE">ON_SITE</option>
            </select>
          </div>
          <div>
            <label class="text-xs font-medium text-slate-700 mb-1.5 block">Versi</label>
            <div class="px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg text-slate-700 font-medium">
              v{{ version }}
            </div>
          </div>
        </div>
      </div>

      <div class="p-4 bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg text-white shadow-lg">
        <div class="text-xs font-medium opacity-90 uppercase tracking-wide mb-2">Total Estimasi</div>
        <div class="text-2xl font-bold">Rp {{ formatRupiah(total) }}</div>
      </div>
    </div>

    <!-- Table -->
    <div class="border border-slate-200 rounded-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider w-12">#</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Deskripsi</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider w-24">Kode</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider w-20">Satuan</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider w-24">Qty</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider w-32">Harga Satuan</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider w-36">Subtotal</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider w-32">Keterangan</th>
              <th v-if="!readonly" class="px-4 py-3 w-10"></th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-100">
            <tr v-for="(row, i) in items" :key="i" class="hover:bg-slate-50 transition">
              <td class="px-4 py-3 text-slate-500 font-medium">{{ i + 1 }}</td>
              <td class="px-4 py-3">
                <input 
                  :value="row.material_description"
                  @input="updateItem(i, 'material_description', ($event.target as HTMLInputElement).value)"
                  type="text"
                  class="w-full px-2 py-1.5 text-sm border border-slate-200 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="readonly ? 'bg-slate-50 text-slate-600' : 'bg-white'"
                  placeholder="Deskripsi item..."
                  :readonly="readonly"
                />
              </td>
              <td class="px-4 py-3">
                <input 
                  :value="row.material_code || '-'"
                  type="text"
                  class="w-full px-2 py-1.5 text-sm bg-slate-50 border border-slate-200 rounded text-slate-500 font-mono"
                  readonly
                />
              </td>
              <td class="px-4 py-3">
                <input 
                  :value="row.uom"
                  @input="updateItem(i, 'uom', ($event.target as HTMLInputElement).value)"
                  type="text"
                  class="w-full px-2 py-1.5 text-sm border border-slate-200 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center"
                  :class="readonly ? 'bg-slate-50 text-slate-600' : 'bg-white'"
                  :readonly="readonly"
                />
              </td>
              <td class="px-4 py-3">
                <input 
                  :value="row.qty"
                  @input="updateItem(i, 'qty', Number(($event.target as HTMLInputElement).value))"
                  type="number"
                  min="0"
                  class="w-full px-2 py-1.5 text-sm border border-slate-200 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-right font-medium"
                  :class="readonly ? 'bg-slate-50 text-slate-600' : 'bg-white'"
                  :readonly="readonly"
                />
              </td>
              <td class="px-4 py-3">
                <input 
                  :value="row.unit_price_estimate"
                  @input="updateItem(i, 'unit_price_estimate', Number(($event.target as HTMLInputElement).value))"
                  type="number"
                  min="0"
                  class="w-full px-2 py-1.5 text-sm border border-slate-200 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-right"
                  :class="readonly ? 'bg-slate-50 text-slate-600' : 'bg-white'"
                  :readonly="readonly"
                />
              </td>
              <td class="px-4 py-3 text-right font-semibold text-slate-900">
                {{ formatRupiah(rowSubtotal(row)) }}
              </td>
              <td class="px-4 py-3">
                <input 
                  :value="row.remarks"
                  @input="updateItem(i, 'remarks', ($event.target as HTMLInputElement).value)"
                  type="text"
                  class="w-full px-2 py-1.5 text-sm border border-slate-200 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="readonly ? 'bg-slate-50 text-slate-600' : 'bg-white'"
                  placeholder="Opsional"
                  :readonly="readonly"
                />
              </td>
              <td v-if="!readonly" class="px-4 py-3 text-center">
                <button 
                  @click="emit('remove-item', i)"
                  class="text-slate-400 hover:text-red-600 transition p-1"
                  type="button"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </td>
            </tr>
            <tr v-if="!items.length">
              <td :colspan="readonly ? 8 : 9" class="px-4 py-8 text-center text-slate-400">
                Belum ada item.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="!readonly" class="px-4 py-3 bg-slate-50 border-t border-slate-200">
        <button 
          @click="emit('add-item')"
          class="text-sm font-medium text-blue-600 hover:text-blue-700 inline-flex items-center gap-1.5"
          type="button"
        >
          <Plus class="w-4 h-4" />
          Tambah Item
        </button>
      </div>
    </div>
  </div>
</template>
