<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Bell, ChevronRight, AlertTriangle, ClipboardCheck } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type NotifItem = {
  id: number | string
  href: string
  title: string
  // support format lama & baru
  subtitle?: string | null
  desc?: string | null
  meta?: string | null
  time?: string | null
  // ini penting untuk bedain SLA vs Approval
  kind?: 'APPROVAL' | 'SLA' | string | null
}

const props = defineProps<{
  items: NotifItem[]
}>()

function fmt(value?: string | null) {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return String(value)
  return d.toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  })
}

function initialsFromTitle(t?: string) {
  const s = String(t ?? '').trim()
  if (!s) return 'N'
  const parts = s.split(/\s+/).slice(0, 2)
  return parts.map(p => p[0]?.toUpperCase()).join('') || 'N'
}

function normKind(k?: string | null) {
  const v = String(k ?? '').toUpperCase()
  if (v.includes('SLA')) return 'SLA'
  if (v.includes('APPROVAL')) return 'APPROVAL'
  return v || 'APPROVAL'
}

function getSubtitle(n: NotifItem) {
  // format baru: subtitle
  if (n.subtitle) return n.subtitle
  // format lama: desc/meta
  if (n.desc && n.meta) return `${n.desc} • ${n.meta}`
  if (n.desc) return n.desc
  if (n.meta) return n.meta
  return null
}

const total = computed(() => props.items?.length ?? 0)

const tab = ref<'ALL' | 'APPROVAL' | 'SLA'>('ALL')

const filteredItems = computed(() => {
  const items = props.items ?? []
  if (tab.value === 'ALL') return items
  if (tab.value === 'APPROVAL') return items.filter(i => normKind(i.kind) === 'APPROVAL')
  if (tab.value === 'SLA') return items.filter(i => normKind(i.kind) === 'SLA')
  return items
})

const countApproval = computed(() => (props.items ?? []).filter(i => normKind(i.kind) === 'APPROVAL').length)
const countSla = computed(() => (props.items ?? []).filter(i => normKind(i.kind) === 'SLA').length)

function pillClass(active: boolean) {
  return active
    ? 'bg-blue-600 text-white border-blue-600'
    : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
}

function kindBadgeClass(kind: string) {
  if (kind === 'SLA') return 'bg-rose-50 text-rose-700 border-rose-200'
  return 'bg-blue-50 text-blue-700 border-blue-200'
}

function leftBadgeClass(kind: string) {
  if (kind === 'SLA') return 'border-rose-200 bg-rose-50 text-rose-700'
  return 'border-blue-200 bg-blue-50 text-blue-700'
}

function iconForKind(kind: string) {
  return kind === 'SLA' ? AlertTriangle : ClipboardCheck
}
</script>

<template>
  <Head title="Notifikasi" />

  <AppLayout title="Notifikasi" currentPage="notifications.index">
    <div class="max-w-4xl">
      <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-slate-100">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center border border-blue-100">
                  <Bell class="w-5 h-5" />
                </div>

                <div class="min-w-0">
                  <h1 class="text-lg font-extrabold text-slate-900 leading-tight">Notifikasi</h1>
                  <p class="text-xs text-slate-500 mt-0.5">
                    Gabungan notifikasi Approval & SLA Overdue.
                  </p>
                </div>
              </div>

              <!-- Tabs -->
              <div class="mt-4 flex flex-wrap gap-2">
                <button
                  type="button"
                  class="px-3 py-1.5 rounded-full text-[11px] font-bold border transition"
                  :class="pillClass(tab === 'ALL')"
                  @click="tab = 'ALL'"
                >
                  Semua ({{ total }})
                </button>

                <button
                  type="button"
                  class="px-3 py-1.5 rounded-full text-[11px] font-bold border transition"
                  :class="pillClass(tab === 'APPROVAL')"
                  @click="tab = 'APPROVAL'"
                >
                  Approval ({{ countApproval }})
                </button>

                <button
                  type="button"
                  class="px-3 py-1.5 rounded-full text-[11px] font-bold border transition"
                  :class="pillClass(tab === 'SLA')"
                  @click="tab = 'SLA'"
                >
                  SLA Overdue ({{ countSla }})
                </button>
              </div>
            </div>

            <div class="shrink-0">
              <span
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[11px] font-bold border"
                :class="total > 0 ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-slate-50 text-slate-600 border-slate-200'"
              >
                <span class="w-2 h-2 rounded-full" :class="total > 0 ? 'bg-rose-600' : 'bg-slate-300'" />
                {{ total }} Item
              </span>
            </div>
          </div>
        </div>

        <!-- Empty -->
        <div v-if="!filteredItems.length" class="px-6 py-12 text-center">
          <div class="mx-auto w-12 h-12 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500">
            <Bell class="w-6 h-6" />
          </div>
          <div class="mt-3 text-sm font-semibold text-slate-700">Tidak ada notifikasi</div>
          <div class="mt-1 text-xs text-slate-500">
            Coba ganti tab “Semua”, atau memang belum ada data yang masuk.
          </div>
        </div>

        <!-- List -->
        <div v-else class="divide-y divide-slate-100">
          <Link
            v-for="n in filteredItems"
            :key="n.id"
            :href="n.href"
            class="group block px-6 py-4 hover:bg-slate-50 transition"
          >
            <div class="flex items-start gap-4">
              <!-- Left icon/badge -->
              <div class="shrink-0">
                <div
                  class="w-10 h-10 rounded-xl border flex items-center justify-center transition"
                  :class="leftBadgeClass(normKind(n.kind))"
                >
                  <component :is="iconForKind(normKind(n.kind))" class="w-5 h-5" />
                </div>
              </div>

              <!-- Content -->
              <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <div class="flex items-center gap-2 min-w-0">
                      <div class="text-sm font-extrabold text-slate-900 truncate">
                        {{ n.title }}
                      </div>

                      <span
                        class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold border"
                        :class="kindBadgeClass(normKind(n.kind))"
                      >
                        {{ normKind(n.kind) === 'SLA' ? 'SLA Overdue' : 'Approval' }}
                      </span>
                    </div>

                    <div v-if="getSubtitle(n)" class="text-xs text-slate-600 mt-1 line-clamp-2">
                      {{ getSubtitle(n) }}
                    </div>
                  </div>

                  <div class="shrink-0 text-right">
                    <div class="text-[11px] text-slate-400 whitespace-nowrap">
                      {{ fmt(n.time) }}
                    </div>
                    <div class="mt-1 flex justify-end">
                      <ChevronRight class="w-4 h-4 text-slate-300 group-hover:text-blue-500 transition" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.line-clamp-2{
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
