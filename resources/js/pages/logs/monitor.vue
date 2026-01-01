<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '../../layouts/AppLayout.vue'

type LogRow = {
  datetime: string
  level: string
  message: string
  context: Record<string, unknown>
}

const props = defineProps<{
  logs: LogRow[]
  date: string
  fileExists: boolean
  fileName: string
  contextKeys: string[]
}>()

// form tanggal (GET)
const formDate = ref(props.date)

// search keyword (client-side filter)
const search = ref('')

// context keys yang mau ditampilkan (buang "time")
const visibleContextKeys = computed(() =>
  props.contextKeys.filter((k) => k !== 'time')
)

// submit form (load log tanggal lain)
function applyFilter() {
  router.get(
    '/logs/monitor',
    { date: formDate.value },
    {
      preserveState: true,
      preserveScroll: true,
    },
  )
}

// format datetime biar enak dibaca
function formatDateTime(value: string) {
  const d = new Date(value)
  if (isNaN(d.getTime())) {
    // kalau gagal parse, balikin apa adanya
    return value
  }

  return d.toLocaleString('id-ID', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

// data log yg sudah difilter keyword
const filteredLogs = computed(() => {
  if (!search.value) return props.logs

  const kw = search.value.toLowerCase()

  return props.logs.filter((log) => {
    const base = `${log.datetime} ${log.level} ${log.message}`.toLowerCase()

    const ctx = log.context ?? {}
    const ctxText = Object.values(ctx)
      .map((v) => {
        if (v === null || v === undefined) return ''
        if (typeof v === 'object') {
          try {
            return JSON.stringify(v)
          } catch {
            return ''
          }
        }
        return String(v)
      })
      .join(' ')
      .toLowerCase()

    return base.includes(kw) || ctxText.includes(kw)
  })
})

// helper untuk tampilkan value context
function formatCtxValue(value: unknown): string {
  if (value === null || value === undefined) return ''

  if (typeof value === 'object') {
    try {
      return JSON.stringify(value)
    } catch {
      return ''
    }
  }

  return String(value)
}
</script>

<template>
  <AppLayout title="Log Monitoring" currentPage="logs">
    <Head title="Log Monitoring" />

    <div class="max-w-7xl mx-auto px-4 py-6 space-y-4">
      <h2 class="text-xl font-semibold text-slate-800 mb-2">
        Log Monitoring
      </h2>

      <!-- Form filter -->
      <form
        class="flex flex-wrap gap-3 items-end bg-white rounded-xl shadow-sm border border-slate-200 p-4"
        @submit.prevent="applyFilter"
      >
        <div class="flex flex-col">
          <label class="text-sm font-medium text-slate-700 mb-1">
            Tanggal
          </label>
          <input
            v-model="formDate"
            type="date"
            class="rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm px-3 py-2"
          />
        </div>

        <div class="flex flex-col flex-1 min-w-[200px]">
          <label class="text-sm font-medium text-slate-700 mb-1">
            Search
          </label>
          <input
            v-model="search"
            type="text"
            placeholder="Cari level, pesan, context, dll..."
            class="rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm px-3 py-2 w-full"
          />
        </div>

        <div class="flex flex-col">
          <button
            type="submit"
            class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700"
          >
            Load
          </button>
        </div>
      </form>

      <!-- Info file -->
      <p class="text-xs text-slate-600">
        File: <span class="font-mono">{{ props.fileName }}</span>
        <span v-if="!props.fileExists" class="text-red-600">
          — file tidak ditemukan / belum ada log untuk tanggal ini.
        </span>
      </p>

      <!-- Tabel log -->
      <div
        v-if="props.fileExists && filteredLogs.length"
        class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 overflow-x-auto"
      >
        <table class="min-w-full border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50 border-b">
              <th class="border border-slate-200 px-2 py-2 text-left">Waktu</th>
              <th class="border border-slate-200 px-2 py-2 text-left">Level</th>
              <th class="border border-slate-200 px-2 py-2 text-left">Message</th>

              <!-- context keys (kecuali 'time') -->
              <th
                v-for="key in visibleContextKeys"
                :key="key"
                class="border border-slate-200 px-2 py-2 text-left"
              >
                {{ key }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(log, idx) in filteredLogs"
              :key="idx"
              class="border-b hover:bg-slate-50"
              :class="{ 'bg-slate-50/70': idx % 2 === 1 }"
            >
              <td class="border border-slate-200 px-2 py-1 align-top whitespace-nowrap">
                {{ formatDateTime(log.datetime) }}
              </td>
              <td class="border border-slate-200 px-2 py-1 align-top whitespace-nowrap">
                {{ log.level }}
              </td>
              <td class="border border-slate-200 px-2 py-1 align-top">
                {{ log.message }}
              </td>

              <td
                v-for="key in visibleContextKeys"
                :key="key"
                class="border border-slate-200 px-2 py-1 align-top"
              >
                {{ formatCtxValue(log.context?.[key]) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-else-if="props.fileExists" class="text-sm text-slate-600">
        Tidak ada baris log terbaca di file ini.
      </p>
    </div>
  </AppLayout>
</template>
