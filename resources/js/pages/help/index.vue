<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import {
  Search,
  LifeBuoy,
  ChevronDown,
  Mail,
  Phone,
  MessageCircle,
  CheckCircle2,
  AlertTriangle,
} from 'lucide-vue-next'

type FaqItem = {
  id: string
  q: string
  a: string
  tag?: 'Dashboard' | 'Project' | 'Planning' | 'Approval' | 'Documents' | 'Akun'
}

type FaqGroup = {
  key: string
  title: string
  desc: string
  items: FaqItem[]
}

const groups = ref<FaqGroup[]>([
  {
    key: 'dashboard',
    title: 'Dashboard',
    desc: 'Ringkasan kondisi sistem, badge notifikasi, dan informasi cepat.',
    items: [
      {
        id: 'dash-1',
        tag: 'Dashboard',
        q: 'Apa fungsi Dashboard?',
        a: 'Dashboard menampilkan ringkasan status project, notifikasi, dan hal yang perlu kamu tindak. Cocok buat cek kondisi sistem sebelum mulai kerja.',
      },
      {
        id: 'dash-2',
        tag: 'Dashboard',
        q: 'Badge merah di pojok atas artinya apa?',
        a: 'Badge merah berarti ada item yang butuh perhatian (misalnya approval/inbox). Klik ikon notifikasi untuk melihat detailnya.',
      },
      {
        id: 'dash-3',
        tag: 'Dashboard',
        q: 'Kenapa badge tidak berkurang?',
        a: 'Biasanya karena item belum benar-benar dibuka atau statusnya belum berubah. Coba buka itemnya, lalu refresh halaman.',
      },
    ],
  },
  {
    key: 'project',
    title: 'Project',
    desc: 'Pengelolaan project dari pembuatan sampai monitoring.',
    items: [
      {
        id: 'proj-1',
        tag: 'Project',
        q: 'Bagaimana cara membuat project baru?',
        a: 'Masuk menu Project → pilih “Project Register” → isi data project → simpan. Setelah itu project akan muncul di “Project Board”.',
      },
      {
        id: 'proj-2',
        tag: 'Project',
        q: 'Kenapa project tidak muncul?',
        a: 'Cek filter (status/periode/pencarian). Kalau masih kosong, kemungkinan akun kamu belum punya akses/permission ke data tersebut.',
      },
      {
        id: 'proj-3',
        tag: 'Project',
        q: 'Cara cepat cari project?',
        a: 'Gunakan search berdasarkan kode/nama. Biasanya paling cepat pakai kode project.',
      },
    ],
  },
  {
    key: 'planning',
    title: 'Plan On Desk',
    desc: 'Tahap planning sebelum project dieksekusi.',
    items: [
      {
        id: 'plan-1',
        tag: 'Planning',
        q: 'Untuk apa menu Plan On Desk?',
        a: 'Menu ini dipakai untuk menyusun kebutuhan/plan sebelum dieksekusi. Biasanya digunakan oleh planner.',
      },
      {
        id: 'plan-2',
        tag: 'Planning',
        q: 'Setelah planning, langkah berikutnya apa?',
        a: 'Setelah disimpan, biasanya masuk proses approval (jika alur approval diaktifkan) lalu lanjut ke tahap berikutnya.',
      },
    ],
  },
  {
    key: 'approval',
    title: 'Approval',
    desc: 'Menyetujui/menolak usulan sesuai alur.',
    items: [
      {
        id: 'app-1',
        tag: 'Approval',
        q: 'Bagaimana cara melakukan approval?',
        a: 'Masuk menu Approval → pilih item → baca detail → lakukan aksi Approve / Reject / Revisi (sesuai opsi yang tersedia).',
      },
      {
        id: 'app-2',
        tag: 'Approval',
        q: 'Kenapa tombol Approve/Reject tidak ada?',
        a: 'Biasanya karena kamu tidak punya permission, atau itemnya tidak berada di step yang benar. Pastikan role akun kamu sesuai.',
      },
      {
        id: 'app-3',
        tag: 'Approval',
        q: 'Setelah approve, kenapa status tidak berubah di list?',
        a: 'Coba refresh halaman atau kembali ke list. Kalau masih sama, cek apakah aksi berhasil (tidak ada error/validation).',
      },
    ],
  },
  {
    key: 'documents',
    title: 'Documents',
    desc: 'Upload, cari, download, dan kelola dokumen project.',
    items: [
      {
        id: 'doc-1',
        tag: 'Documents',
        q: 'Cara upload dokumen yang benar?',
        a: 'Masuk Documents → klik “Upload Dokumen” → pilih Project (wajib) → pilih mapping jenis dokumen (opsional) atau isi custom → pilih file → klik “Simpan Dokumen”.',
      },
      {
        id: 'doc-2',
        tag: 'Documents',
        q: 'Dokumen tidak muncul di list, kenapa?',
        a: 'Biasanya karena filter masih aktif. Klik “Reset” atau kosongkan filter Project/Status/Periode, lalu coba lagi.',
      },
      {
        id: 'doc-3',
        tag: 'Documents',
        q: 'Tidak bisa download dokumen?',
        a: 'Kemungkinan akses dibatasi. Pastikan kamu pemilik dokumen atau punya role yang diizinkan untuk akses file.',
      },
    ],
  },
  
])

const search = ref('')
const openId = ref<string>('doc-1')

const allItems = computed(() => groups.value.flatMap(g => g.items))

const filteredGroups = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return groups.value

  // filter per item, keep group jika ada item yang match
  return groups.value
    .map(g => {
      const items = g.items.filter(it => {
        const hay = `${it.q} ${it.a} ${it.tag ?? ''}`.toLowerCase()
        return hay.includes(q)
      })
      return { ...g, items }
    })
    .filter(g => g.items.length > 0)
})

function toggle(id: string) {
  openId.value = openId.value === id ? '' : id
}

// kontak (ubah aja sesuai kebutuhan)
const support = {
  email: 'developer@supercorridor.co.id',
  phone: '08xxxxxxxxxx',
}
</script>

<template>
  <AppLayout title="Bantuan" currentPage="help">
    <Head title="Bantuan" />

    <div class="max-w-5xl mx-auto px-4 lg:px-0 py-6 space-y-5">
      <!-- Header -->
      <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div class="flex items-start gap-3">
            <div class="w-11 h-11 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center">
              <LifeBuoy class="w-5 h-5 text-slate-700" />
            </div>
            <div>
              <h1 class="text-xl font-semibold text-slate-900">FAQ & Bantuan</h1>
              <p class="text-sm text-slate-600 mt-1">
                Jawaban cepat untuk pertanyaan umum penggunaan aplikasi.
              </p>
            </div>
          </div>

          <!-- Search -->
          <div class="w-full md:w-[360px] relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input
              v-model="search"
              type="text"
              placeholder="Cari pertanyaan... (contoh: dokumen, approval, project)"
              class="w-full rounded-xl border border-slate-300 bg-white pl-9 pr-3 py-2 text-sm
                     focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 outline-none transition"
            />
          </div>
        </div>
      </div>

      <!-- FAQ List -->
      <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="divide-y divide-slate-200">
          <div v-for="g in filteredGroups" :key="g.key" class="p-0">
            <!-- Group header -->
            <div class="px-6 py-4 bg-slate-50/60 border-b border-slate-200">
              <div class="font-semibold text-slate-900">{{ g.title }}</div>
              <div class="text-sm text-slate-500 mt-0.5">{{ g.desc }}</div>
            </div>

            <!-- Items -->
            <div class="divide-y divide-slate-200">
              <div v-for="it in g.items" :key="it.id" class="px-6">
                <button
                  type="button"
                  class="w-full py-4 flex items-start justify-between gap-4 text-left hover:bg-slate-50/60 transition"
                  @click="toggle(it.id)"
                >
                  <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-900">
                      {{ it.q }}
                    </div>
                    <div v-if="it.tag" class="mt-1 inline-flex text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                      {{ it.tag }}
                    </div>
                  </div>

                  <ChevronDown
                    class="w-5 h-5 text-slate-500 transition-transform mt-0.5"
                    :class="openId === it.id ? 'rotate-180' : ''"
                  />
                </button>

                <div v-if="openId === it.id" class="pb-4 -mt-1">
                  <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 leading-relaxed">
                    {{ it.a }}
                  </div>

                  <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full border border-slate-200 bg-white text-slate-600">
                      <CheckCircle2 class="w-4 h-4" />
                      Tips: kalau masih bingung, coba refresh & cek filter.
                    </span>
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full border border-amber-200 bg-amber-50 text-amber-700">
                      <AlertTriangle class="w-4 h-4" />
                      Kalau akses ditolak, kemungkinan permission/role.
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- empty state -->
          <div v-if="!filteredGroups.length" class="px-6 py-10 text-center">
            <div class="text-sm font-semibold text-slate-700">Tidak ada hasil</div>
            <div class="text-xs text-slate-500 mt-1">
              Coba kata kunci lain. Misal: “dokumen”, “approval”, “project”.
            </div>
          </div>
        </div>
      </div>

      <!-- Support contact -->
      <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <div class="text-sm font-semibold text-slate-900">Masih ada problem?</div>
            <div class="text-sm text-slate-600 mt-1">
              Jika ada problem silakan hubungi kontak support berikut:
            </div>
          </div>

          <div class="flex flex-col sm:flex-row gap-2">
            <a
              :href="`mailto:${support.email}`"
              class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"
            >
              <Mail class="w-4 h-4" />
              {{ support.email }}
            </a>

            <a
              :href="`tel:${support.phone}`"
              class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"
            >
              <Phone class="w-4 h-4" />
              {{ support.phone }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
