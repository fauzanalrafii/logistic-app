<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { ChevronRight, Pencil, Tag, Zap, Clock } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import WorkflowEditor from '@/components/WorkflowEditor.vue';

const props = defineProps({
  workflow: Object,
  steps: Array,
  currentPage: String,
});

const stepsWithRoles = computed(() => {
  return props.steps.map(step => ({
    id: step.id,
    name: step.name,
    step_order: step.step_order,
    required_role_id: step.required_role_id,
    role_name: step.role_name,
    sla_hours: step.sla_hours,
  }));
});

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};
</script>

<template>
  <Head>
    <title>Detail Workflow - {{ workflow.name }}</title>
  </Head>
  
  <AppLayout :currentPage="currentPage">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
        <a href="/approval/workflows" class="hover:text-slate-700 transition-colors">
          Workflow
        </a>
        <ChevronRight class="w-4 h-4" />
        <span class="text-slate-700">{{ workflow.name }}</span>
      </div>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold text-gray-800">{{ workflow.name }}</h1>
          <p class="text-sm text-slate-500 mt-0.5">Detail workflow approval</p>
        </div>
        <div class="flex items-center gap-2">
          <span 
            :class="[
              'badge',
              workflow.is_active ? 'badge-green' : 'badge-gray'
            ]"
          >
            {{ workflow.is_active ? 'Aktif' : 'Non-aktif' }}
          </span>
          <a
            :href="`/approval/workflows/${workflow.id}/edit`"
            class="btn-primary"
          >
            <Pencil class="w-4 h-4" />
            <span>Edit</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex items-center gap-3">
          <div class="flex-shrink-0 bg-purple-50 rounded-lg p-2 border border-purple-100">
            <Tag class="w-5 h-5 text-purple-600" />
          </div>
          <div>
            <p class="text-xs font-medium text-slate-500">Tipe Proses</p>
            <p class="text-sm font-semibold text-slate-800">{{ workflow.process_type }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex items-center gap-3">
          <div class="flex-shrink-0 bg-blue-50 rounded-lg p-2 border border-blue-100">
            <Zap class="w-5 h-5 text-blue-600" />
          </div>
          <div>
            <p class="text-xs font-medium text-slate-500">Jumlah Step</p>
            <p class="text-sm font-semibold text-slate-800">{{ steps.length }} step</p>
          </div>
        </div>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex items-center gap-3">
          <div class="flex-shrink-0 bg-green-50 rounded-lg p-2 border border-green-100">
            <Clock class="w-5 h-5 text-green-600" />
          </div>
          <div>
            <p class="text-xs font-medium text-slate-500">Dibuat</p>
            <p class="text-sm font-semibold text-slate-800">{{ formatDate(workflow.created_at) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Workflow Diagram -->
    <div class="mb-4">
      <WorkflowEditor :model-value="stepsWithRoles" :readonly="true" />
    </div>

    <!-- Steps Table -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
        <h2 class="text-sm font-semibold text-slate-700">Daftar Step Approval</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-semibold border-b border-gray-100">
            <tr>
              <th class="px-4 py-3 text-left">Urutan</th>
              <th class="px-4 py-3 text-left">Nama Step</th>
              <th class="px-4 py-3 text-left">Role</th>
              <th class="px-4 py-3 text-left">SLA</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="step in steps" :key="step.id" class="hover:bg-gray-50 transition">
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-100 text-indigo-700 rounded-full font-semibold text-xs">
                  {{ step.step_order }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="font-medium text-slate-800">{{ step.name }}</div>
              </td>
              <td class="px-4 py-3">
                <span class="badge badge-blue">
                  {{ step.role_name }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span v-if="step.sla_hours" class="text-sm text-indigo-600 font-medium">
                  {{ step.sla_hours }} jam
                </span>
                <span v-else class="text-xs text-slate-400">-</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.btn-primary {
  @apply flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition;
}

.badge {
  @apply inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border;
}
.badge-green {
  @apply bg-green-50 text-green-700 border-green-200;
}
.badge-gray {
  @apply bg-gray-50 text-gray-600 border-gray-200;
}
.badge-blue {
  @apply bg-blue-50 text-blue-700 border-blue-200;
}
</style>
