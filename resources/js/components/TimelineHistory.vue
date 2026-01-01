<template>
  <div class="bg-white border border-slate-200 rounded-b-lg p-6">
    <div class="space-y-4">
      <template v-for="(h, idx) in sortedHistories" :key="`status-${h.idx}`">
        <!-- Status Item -->
        <div class="flex gap-3">
          <div class="flex flex-col items-center">
            <div 
              class="w-10 h-10 rounded-full border-2 flex items-center justify-center text-sm font-bold bg-white shrink-0"
              :class="h.isActive ? 'border-blue-600 text-blue-600' : 'border-slate-300 text-slate-400'"
            >
              {{ h.idx }}
            </div>
            <div v-if="idx < sortedHistories.length - 1" class="w-0.5 flex-1 bg-slate-200 my-2"></div>
          </div>

          <div class="flex-1 pb-4">
            <div class="text-sm font-semibold" :class="h.isActive ? 'text-blue-700' : 'text-slate-700'">
              {{ h.name }}
            </div>
            <div class="text-xs text-slate-500 mt-1">{{ formatDateTime(h.changed_at) }}</div>
            <div class="text-xs text-slate-400 mt-0.5">Oleh: {{ h.changer }}</div>
            <div v-if="h.note" class="mt-2 text-xs italic text-slate-500 bg-slate-50 px-2 py-1.5 rounded border border-slate-200">
              "{{ h.note }}"
            </div>

            <!-- Approval (if exists) -->
            <template v-if="statusApprovalMap[h.name]">
              <template v-for="approval in [findApprovalByProcessType(statusApprovalMap[h.name])]" :key="`approval-${approval?.id}`">
                <div v-if="approval" class="mt-3">
                  <button 
                    @click="planningOpen = !planningOpen"
                    class="w-full flex items-center justify-between gap-2 px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition-colors"
                  >
                    <span>{{ approval.flow?.name || 'Approval' }}</span>
                    <span class="px-1.5 py-0.5 text-[10px] font-bold uppercase rounded shrink-0" :class="{
                      'bg-green-100 text-green-700': approval.status === 'APPROVED',
                      'bg-red-100 text-red-700': approval.status === 'REJECTED',
                      'bg-amber-100 text-amber-700': ['PENDING', 'IN_REVIEW'].includes(approval.status),
                      'bg-slate-100 text-slate-600': !['APPROVED', 'REJECTED', 'PENDING', 'IN_REVIEW'].includes(approval.status)
                    }">{{ approval.status }}</span>
                  </button>

                  <!-- Approval Steps -->
                  <div v-if="planningOpen && approval.flow?.steps" class="mt-2 ml-4 space-y-2">
                    <div 
                      v-for="s in approval.flow.steps" 
                      :key="`step-${s.id}`"
                      class="flex items-start gap-2 p-3 bg-white border border-slate-200 rounded text-xs"
                    >
                      <div 
                        class="w-6 h-6 rounded-full border-2 flex items-center justify-center text-[10px] font-bold shrink-0"
                        :class="[
                          approval.actions?.find((a: any) => a.step_id === s.id)?.action === 'APPROVE' ? 'border-green-600 bg-green-50 text-green-600' :
                          approval.actions?.find((a: any) => a.step_id === s.id)?.action === 'REJECT' ? 'border-red-600 bg-red-50 text-red-600' :
                          approval.current_step?.id === s.id ? 'border-amber-500 bg-amber-50 text-amber-500' : 'border-slate-300 bg-slate-50 text-slate-400'
                        ]"
                      >
                        {{ s.order }}
                      </div>
                      <div class="flex-1 min-w-0">
                        <div class="font-semibold text-slate-800 mb-1">{{ s.name }}</div>
                        <template v-if="approval.actions?.find((a: any) => a.step_id === s.id)">
                          <div class="text-slate-600 mb-1">
                            <span class="font-medium">{{ approval.actions.find((a: any) => a.step_id === s.id).user_name }}</span>
                          </div>
                          <div class="text-slate-400 text-[11px] mb-1">
                            {{ formatDateTime(approval.actions.find((a: any) => a.step_id === s.id).acted_at) }}
                          </div>
                          <div v-if="approval.actions.find((a: any) => a.step_id === s.id).comment" class="mt-2 text-xs text-slate-600 bg-slate-50 border border-slate-200 px-2 py-1.5 rounded italic">
                            "{{ approval.actions.find((a: any) => a.step_id === s.id).comment }}"
                          </div>
                        </template>
                        <template v-else-if="approval.current_step?.id === s.id">
                          <div class="text-amber-600 font-medium">⏳ Sedang diproses...</div>
                        </template>
                        <template v-else>
                          <div class="text-slate-400">○ Menunggu</div>
                        </template>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </template>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{
  sortedHistories: any[]
  statusApprovalMap: any
  findApprovalByProcessType: (type: string) => any
}>()

const planningOpen = ref(false)

const formatDateTime = (value?: string | null) => {
  if (!value) return '-'
  const d = new Date(value)
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
