<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { User, Mail, Shield, Bell, Info } from 'lucide-vue-next'

const props = defineProps<{
  userInfo: {
    name: string
    email: string
    role: string
  }
}>()

const initials = computed(() => {
  const t = (props.userInfo?.name ?? 'User').trim()
  if (!t) return 'U'
  const parts = t.split(/\s+/).slice(0, 2)
  return parts.map(p => (p[0] ?? '').toUpperCase()).join('') || 'U'
})
</script>

<template>
  <AppLayout title="Settings" currentPage="">
    <Head title="Settings" />

    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Settings</h1>
          <p class="text-sm text-slate-500 mt-1">Pengaturan akun & preferensi dasar.</p>
        </div>

        <Link
          href="/dashboard"
          class="text-sm font-semibold text-slate-600 hover:text-slate-900"
        >
          Kembali
        </Link>
      </div>

      <!-- Akun -->
      <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-4">Akun</h2>

        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center">
            <span class="font-extrabold text-slate-700">{{ initials }}</span>
          </div>

          <div class="min-w-0 flex-1">
            <div class="text-base font-bold text-slate-900 truncate">
              {{ props.userInfo.name }}
            </div>
            <div class="text-sm text-slate-500 flex items-center gap-2 mt-1">
              <Mail class="w-4 h-4" />
              <span class="truncate">{{ props.userInfo.email }}</span>
            </div>
          </div>

          <span class="inline-flex items-center gap-2 text-xs font-bold px-3 py-1 rounded-full border border-slate-200 bg-slate-50 text-slate-700">
            <Shield class="w-4 h-4" />
            {{ props.userInfo.role }}
          </span>
        </div>

        <div class="mt-5 grid sm:grid-cols-2 gap-4">
          <div>
            <div class="text-xs font-semibold text-slate-500 mb-1">Profil</div>
            <Link
              href="/profile"
              class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700"
            >
              <User class="w-4 h-4" />
              Buka Halaman Profil
            </Link>
          </div>

          <div>
            <div class="text-xs font-semibold text-slate-500 mb-1">Keamanan</div>
            <button
              type="button"
              disabled
              class="text-sm font-semibold text-slate-400 cursor-not-allowed"
              title="Belum diaktifkan"
            >
              Ubah Password (soon)
            </button>
          </div>
        </div>
      </div>

      <!-- Notifikasi (dummy) -->
      <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-4">Notifikasi</h2>

        <div class="space-y-3">
          <div class="flex items-center justify-between border border-slate-200 rounded-lg px-4 py-3 bg-slate-50">
            <div class="flex items-start gap-3">
              <Bell class="w-5 h-5 text-slate-600 mt-0.5" />
              <div>
                <div class="text-sm font-bold text-slate-800">Approval</div>
                <div class="text-xs text-slate-500">Tampilkan badge & notifikasi approval.</div>
              </div>
            </div>
            <span class="text-xs font-bold px-3 py-1 rounded-full border border-slate-200 bg-white text-slate-700">
              Aktif
            </span>
          </div>

          <div class="flex items-center justify-between border border-slate-200 rounded-lg px-4 py-3 bg-slate-50">
            <div class="flex items-start gap-3">
              <Bell class="w-5 h-5 text-slate-600 mt-0.5" />
              <div>
                <div class="text-sm font-bold text-slate-800">SLA Overdue</div>
                <div class="text-xs text-slate-500">Peringatan jika proyek melewati SLA.</div>
              </div>
            </div>
            <span class="text-xs font-bold px-3 py-1 rounded-full border border-slate-200 bg-white text-slate-700">
              Aktif
            </span>
          </div>
        </div>

        <div class="mt-4 flex items-start gap-2 text-xs text-slate-500">
          <Info class="w-4 h-4 mt-0.5" />
          <span>Ini masih tampilan dulu. Nanti kalau mau, baru kita buat toggle + simpan ke database.</span>
        </div>
      </div>

      <!-- Bantuan singkat -->
      <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-2">Bantuan</h2>
        <p class="text-sm text-slate-600">
          Kalau kamu butuh panduan penggunaan aplikasi, buka halaman bantuan.
        </p>

        <div class="mt-3">
          <Link
            href="/help"
            class="text-sm font-semibold text-blue-600 hover:text-blue-700"
          >
            Buka Halaman Bantuan →
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
