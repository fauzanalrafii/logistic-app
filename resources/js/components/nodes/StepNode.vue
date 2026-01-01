<script setup>
import { Handle, Position } from '@vue-flow/core';
import { computed } from 'vue';
import { Play, CheckCircle, Square, Pencil, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
});

defineEmits(['edit-node', 'delete-node']);

const nodeClasses = computed(() => {
    switch (props.data.type) {
        case 'start':
            return 'bg-gradient-to-br from-blue-50 to-blue-100 border-blue-300';
        case 'end':
            return 'bg-gradient-to-br from-green-50 to-green-100 border-green-300';
        default:
            return 'bg-white border-gray-200 hover:border-indigo-300 hover:shadow-xl';
    }
});

const iconBgClass = computed(() => {
    switch (props.data.type) {
        case 'start':
            return 'bg-blue-500';
        case 'end':
            return 'bg-green-500';
        default:
            return 'bg-indigo-500';
    }
});
</script>

<template>
    <div
        class="step-node px-4 py-3 rounded-xl shadow-lg border-2 min-w-[180px] transition-all duration-200"
        :class="nodeClasses"
    >
        <!-- Step Number Badge -->
        <div
            v-if="data.type === 'step'"
            class="absolute -top-3 -right-3 w-7 h-7 bg-indigo-600 text-white rounded-full flex items-center justify-center text-xs font-bold shadow-md"
        >
            {{ data.stepOrder }}
        </div>

        <!-- Node Content -->
        <div class="flex items-center gap-3">
            <!-- Icon -->
            <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center" :class="iconBgClass">
                <!-- Start Icon -->
                <Play v-if="data.type === 'start'" class="w-5 h-5 text-white" />
                <!-- Step Icon -->
                <CheckCircle v-else-if="data.type === 'step'" class="w-5 h-5 text-white" />
                <!-- End Icon -->
                <Square v-else-if="data.type === 'end'" class="w-5 h-5 text-white" />
            </div>

            <!-- Text Content -->
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 truncate">{{ data.label }}</p>
                <p v-if="data.roleName" class="text-xs text-gray-500 truncate">{{ data.roleName }}</p>
                <p v-if="data.slaHours" class="text-xs text-indigo-500 font-medium mt-0.5">⏱ SLA: {{ data.slaHours }}j</p>
            </div>
        </div>

        <!-- Action Buttons (only for step nodes in edit mode) -->
        <div v-if="data.type === 'step' && data.editable" class="flex items-center justify-end gap-1 mt-2 pt-2 border-t border-gray-200">
            <button
                type="button"
                @click.stop.prevent="data.onEdit && data.onEdit(data)"
                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                title="Edit"
            >
                <Pencil class="w-4 h-4" />
            </button>
            <button
                type="button"
                @click.stop.prevent="data.onDelete && data.onDelete(data)"
                class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                title="Hapus"
            >
                <Trash2 class="w-4 h-4" />
            </button>
        </div>

        <!-- Handles for approval flow (left/right) -->
        <Handle v-if="data.type !== 'start'" type="target" :position="Position.Left" class="!bg-gray-400 !w-3 !h-3" />
        <Handle v-if="data.type !== 'end'" type="source" :position="Position.Right" class="!bg-indigo-500 !w-3 !h-3" />
    </div>
</template>

<style scoped>
.step-node {
    position: relative;
}
</style>
