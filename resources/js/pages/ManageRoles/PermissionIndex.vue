<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue"
import ConfirmDialog from "@/components/ConfirmDialog.vue"
import { Head, Link, router } from "@inertiajs/vue3"
import { Edit, Trash2, Plus, X } from "lucide-vue-next"
import { ref, watch } from "vue"
import { useToast } from "@/composables/useToast"

const props = defineProps<{
  permissions: any
  filters: any
}>()

const { success, error } = useToast()

const search = ref(props.filters?.search ?? "")
const perPage = ref(props.filters?.per_page ?? 10)
const isLoading = ref(false)

// debounce search
let t: any = null
watch(search, () => {
  clearTimeout(t)
  t = setTimeout(() => applyFilters(), 350)
})

function applyFilters() {
  isLoading.value = true
  router.get(
    "/permissions",
    {
      search: search.value,
      per_page: perPage.value,
    },
    {
      preserveState: true,
      replace: true,
      onFinish: () => (isLoading.value = false),
    }
  )
}

function resetFilters() {
  search.value = ""
  perPage.value = 10
  applyFilters()
}

// ===============================
// ✅ Modal Delete state
// ===============================
const deleteModalOpen = ref(false)
const deleting = ref(false)
const selectedPermission = ref<any>(null)

function openDeleteModal(p: any) {
  selectedPermission.value = p
  deleteModalOpen.value = true
}

function closeDeleteModal(force = false) {
  if (deleting.value && !force) return
  deleteModalOpen.value = false
  selectedPermission.value = null
}

function confirmDelete() {
  if (!selectedPermission.value) return
  if (deleting.value) return

  deleting.value = true

  router.delete(`/permissions/${selectedPermission.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      success(`Permission "${selectedPermission.value?.name ?? ""}" berhasil dihapus.`)
      closeDeleteModal(true)
    },
    onError: (errs: any) => {
      const msg = errs?.error || "Gagal menghapus permission."
      error(String(msg))
      closeDeleteModal(true)
    },
    onFinish: () => {
      deleting.value = false
    },
  })
}
</script>

<template>
  <Head title="Permissions" />

  <AppLayout title="Manage Roles" currentPage="permissions.index">
    <!-- HEADER -->
    <div class="mb-4 flex items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Permissions</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola permission untuk akses fitur.</p>
      </div>

      <Link href="/permissions/create" class="btn-primary">
        <Plus class="w-4 h-4" />
        Add Permission
      </Link>
    </div>

    <!-- FILTERS -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 mb-4">
      <div class="flex flex-col md:flex-row md:items-center gap-3">
        <!-- Search -->
        <div class="relative w-full md:w-[420px]">
          <input
            v-model="search"
            placeholder="Search permission..."
            class="input-icon w-full h-10 text-sm"
          />
          <button
            v-if="search"
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded hover:bg-slate-100"
            @click="search = ''"
            title="Clear"
          >
            <X class="w-4 h-4 text-slate-500" />
          </button>
        </div>

        <!-- Per page -->
        <div class="flex items-center gap-2">
          <span class="text-sm text-slate-600">Show</span>
          <select v-model="perPage" @change="applyFilters" class="input h-10 w-28 text-sm">
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
        </div>

        <div class="flex items-center gap-2 md:ml-auto">
          <button type="button" class="btn-ghost h-10" @click="resetFilters">
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- TABLE -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden">
      <div class="px-4 py-3 border-b flex items-center justify-between">
        <div class="text-sm font-semibold text-slate-700">Data Permissions</div>
        <div v-if="isLoading" class="text-xs text-slate-500">Loading...</div>
      </div>

      <!-- DESKTOP -->
      <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-semibold border-b border-gray-100">
            <tr>
              <th class="px-4 py-3 text-left w-24">ID</th>
              <th class="px-4 py-3 text-left">Permission Name</th>
              <th class="px-4 py-3 text-center w-1">Actions</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="permission in props.permissions.data"
              :key="permission.id"
              class="hover:bg-gray-50"
            >
              <td class="px-4 py-3 font-semibold">
                {{ permission.id }}
              </td>

              <td class="px-4 py-3">
                <span class="font-medium text-slate-800">{{ permission.name }}</span>
              </td>

              <td class="px-4 py-3 whitespace-nowrap text-center">
                <div class="flex items-center justify-center gap-2">
                  <Link
                    :href="`/permissions/${permission.id}/edit`"
                    class="btn-action btn-action-blue"
                    title="Edit"
                  >
                    <Edit class="w-4 h-4" />
                    <span>Edit</span>
                  </Link>

                  <button
                    type="button"
                    class="btn-action btn-action-red"
                    title="Delete"
                    @click="openDeleteModal(permission)"
                  >
                    <Trash2 class="w-4 h-4" />
                    <span>Delete</span>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!props.permissions.data?.length">
              <td colspan="3" class="px-6 py-10 text-center text-slate-400">
                Data tidak ditemukan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- MOBILE -->
      <div class="md:hidden p-4 space-y-3">
        <div
          v-for="permission in props.permissions.data"
          :key="permission.id"
          class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm"
        >
          <div class="font-bold text-gray-800 text-lg">
            {{ permission.name }}
          </div>
          <div class="text-xs text-gray-500 mt-1">ID: {{ permission.id }}</div>

          <div class="flex gap-2 mt-4">
            <Link
              :href="`/permissions/${permission.id}/edit`"
              class="btn-action btn-action-blue w-full justify-center"
            >
              <Edit class="w-4 h-4" /> Edit
            </Link>

            <button
              type="button"
              class="btn-action btn-action-red w-full justify-center"
              @click="openDeleteModal(permission)"
            >
              <Trash2 class="w-4 h-4" /> Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- PAGINATION -->
    <div class="flex justify-end mt-4 gap-1">
      <Link
        v-for="(link, i) in props.permissions.links"
        :key="i"
        :href="link.url ?? '#'"
        class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
        :class="
          link.active
            ? 'bg-blue-600 text-white'
            : (link.url ? 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' : 'bg-slate-100 text-slate-400 cursor-not-allowed')
        "
        v-html="link.label"
      />
    </div>

    <!-- ✅ Modal Konfirmasi Delete -->
    <ConfirmDialog
      :show="deleteModalOpen"
      title="Hapus Permission?"
      :message="`Apakah Anda yakin ingin menghapus permission &quot;${selectedPermission?.name ?? ''}&quot;? Aksi ini tidak dapat dibatalkan.`"
      confirmText="Ya, Hapus"
      cancelText="Batal"
      :loading="deleting"
      @cancel="closeDeleteModal()"
      @confirm="confirmDelete"
    />
  </AppLayout>
</template>

<style scoped>
.input {
  @apply border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500;
}
.btn-primary {
  @apply flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition;
}
.btn-ghost {
  @apply px-3 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm;
}
.btn-action {
  @apply px-3 py-1.5 rounded-lg text-xs font-medium inline-flex items-center gap-1 transition border;
}
.btn-action-blue {
  @apply bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100;
}
.btn-action-red {
  @apply bg-red-50 text-red-700 border-red-200 hover:bg-red-100;
}
.input-icon {
  @apply border border-gray-300 rounded-md shadow-sm pl-4 pr-10 py-2 focus:ring-blue-500 focus:border-blue-500;
}
</style>
