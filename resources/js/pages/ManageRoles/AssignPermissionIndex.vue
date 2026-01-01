<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue"
import ConfirmDialog from "@/components/ConfirmDialog.vue"
import { Link, router, Head } from "@inertiajs/vue3"
import { Edit, Trash2, Plus, X } from "lucide-vue-next"
import { ref, watch } from "vue"
import { useToast } from "@/composables/useToast"

const { success, error } = useToast()

const props = defineProps<{
  assignments: {
    data: Array<{ id: number; name: string; permissions: string[] }>
    total: number
    per_page: number
    current_page: number
    last_page: number
    links?: any[]
  }
  filters: { search?: string; per_page?: number }
}>()

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
    "/assign-permissions",
    { search: search.value, per_page: perPage.value },
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
// ✅ Modal Delete
// ===============================
const deleteModalOpen = ref(false)
const deleting = ref(false)
const selectedAssign = ref<{ id: number; name: string } | null>(null)

function openDeleteModal(item: { id: number; name: string }) {
  selectedAssign.value = { id: item.id, name: item.name }
  deleteModalOpen.value = true
}

function closeDeleteModal(force = false) {
  if (deleting.value && !force) return
  deleteModalOpen.value = false
  selectedAssign.value = null
}

function confirmDelete() {
  if (!selectedAssign.value || deleting.value) return

  deleting.value = true

  router.delete(`/assign-permissions/${selectedAssign.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      success(`Assign permission untuk "${selectedAssign.value?.name ?? ""}" berhasil dihapus.`)
      closeDeleteModal(true)
    },
    onError: (errs: any) => {
      const msg = errs?.error || "Gagal menghapus assign permission."
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
  <Head>
    <title>Assign Permissions</title>
  </Head>

  <AppLayout title="Manage Roles" currentPage="assign-permissions.index">
    <div class="mb-4 flex items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Assign Permission</h1>
        <p class="text-sm text-slate-500 mt-1">Atur permission untuk user.</p>
      </div>

      <Link href="/assign-permissions/create" class="btn-primary">
        <Plus class="w-4 h-4" />
        Assign Permission
      </Link>
    </div>

    <!-- TOP BAR -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 mb-4">
      <div class="flex flex-col md:flex-row md:items-center gap-3 justify-between">
        <div class="relative w-full md:w-[420px]">
          <input
            v-model="search"
            placeholder="Search user / permission..."
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

        <div class="flex items-center gap-2">
          <span class="text-sm text-slate-600">Show</span>
          <select v-model="perPage" @change="applyFilters" class="input h-10 w-28 text-sm">
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="25">25</option>
          </select>

          <button type="button" class="btn-ghost h-10" @click="resetFilters">
            Reset
          </button>
        </div>
      </div>

      <div v-if="isLoading" class="text-xs text-slate-500 mt-3">Loading...</div>
    </div>

    <!-- TABLE -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
      <div class="px-4 py-3 border-b">
        <div class="text-sm font-semibold text-slate-700">Data Assign Permission</div>
      </div>

      <!-- DESKTOP -->
      <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b">
            <tr>
              <th class="px-4 py-3 text-left w-48">User</th>
              <th class="px-4 py-3 text-left">Permissions</th>
              <th class="px-4 py-3 text-center w-1">Actions</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100">
            <tr v-for="item in props.assignments.data" :key="item.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-semibold">
                {{ item.name }}
              </td>

              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="permission in item.permissions"
                    :key="permission"
                    class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs"
                  >
                    {{ permission }}
                  </span>
                  <span v-if="item.permissions.length === 0" class="text-gray-400 italic">-</span>
                </div>
              </td>

              <td class="px-4 py-3 whitespace-nowrap text-center">
                <div class="flex items-center justify-center gap-2">
                  <Link
                    :href="`/assign-permissions/${item.id}/edit`"
                    class="btn-action btn-action-blue"
                  >
                    <Edit class="w-4 h-4" />
                    <span>Edit</span>
                  </Link>

                  <button
                    type="button"
                    @click="openDeleteModal(item)"
                    class="btn-action btn-action-red"
                  >
                    <Trash2 class="w-4 h-4" />
                    <span>Delete</span>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="props.assignments.data.length === 0">
              <td colspan="3" class="text-center py-10 text-slate-400">
                No data found
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- MOBILE -->
      <div class="md:hidden p-4 space-y-3">
        <div
          v-for="item in props.assignments.data"
          :key="item.id"
          class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm"
        >
          <div class="font-bold text-gray-800 text-lg">
            {{ item.name }}
          </div>

          <div class="mt-2">
            <div class="text-xs text-gray-500">Permissions:</div>
            <div class="flex flex-wrap gap-1 mt-1">
              <span
                v-for="permission in item.permissions"
                :key="permission"
                class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs"
              >
                {{ permission }}
              </span>
              <span v-if="item.permissions.length === 0" class="text-gray-400 italic">-</span>
            </div>
          </div>

          <div class="flex gap-2 mt-4">
            <Link
              :href="`/assign-permissions/${item.id}/edit`"
              class="btn-action btn-action-blue w-full justify-center"
            >
              <Edit class="w-4 h-4" /> Edit
            </Link>

            <button
              type="button"
              @click="openDeleteModal(item)"
              class="btn-action btn-action-red w-full justify-center"
            >
              <Trash2 class="w-4 h-4" /> Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- PAGINATION (simple prev/next seperti punyamu) -->
    <div class="flex justify-end mt-4 gap-2">
      <Link
        :href="
          props.assignments.current_page > 1
            ? `?page=${props.assignments.current_page - 1}&per_page=${perPage}&search=${search}`
            : '#'
        "
        class="px-4 py-2 rounded-lg text-sm border border-gray-300 bg-white text-gray-700 hover:bg-gray-100"
        :class="props.assignments.current_page > 1 ? '' : 'opacity-50 pointer-events-none'"
      >
        « Previous
      </Link>

      <Link
        :href="
          props.assignments.current_page < props.assignments.last_page
            ? `?page=${props.assignments.current_page + 1}&per_page=${perPage}&search=${search}`
            : '#'
        "
        class="px-4 py-2 rounded-lg text-sm border border-gray-300 bg-white text-gray-700 hover:bg-gray-100"
        :class="props.assignments.current_page < props.assignments.last_page ? '' : 'opacity-50 pointer-events-none'"
      >
        Next »
      </Link>
    </div>

    <!-- ✅ Modal Delete -->
    <ConfirmDialog
      :show="deleteModalOpen"
      title="Hapus Assign Permission?"
      :message="`Apakah Anda yakin ingin menghapus permission untuk user &quot;${selectedAssign?.name ?? ''}&quot;? Aksi ini tidak dapat dibatalkan.`"
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
.input-icon {
  @apply border border-gray-300 rounded-md shadow-sm pl-4 pr-10 py-2 focus:ring-blue-500 focus:border-blue-500;
}
.btn-primary {
  @apply flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition;
}
.btn-ghost {
  @apply flex items-center justify-center gap-1 px-3 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm;
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
</style>
