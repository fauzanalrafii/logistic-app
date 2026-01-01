<script setup>
import { ref, watch, markRaw } from 'vue';
import { VueFlow } from '@vue-flow/core';
import { Background } from '@vue-flow/background';
import { Controls } from '@vue-flow/controls';
import { Plus, Info } from 'lucide-vue-next';
import StepNode from './nodes/StepNode.vue';

import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/controls/dist/style.css';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
    },
    readonly: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

// Node types registration with event handling
const nodeTypes = {
    stepNode: markRaw({
        ...StepNode,
        emits: ['edit-node', 'delete-node'],
    }),
};

const nodes = ref([]);
const edges = ref([]);
const editingNode = ref(null);
const isDragging = ref(false);

// Handle edit node event from StepNode component
const handleEditNode = (nodeData) => {
    // Get original step data to include sla_hours
    const originalStep = props.modelValue[nodeData.stepIndex];
    editingNode.value = { 
        ...nodeData,
        slaHours: originalStep?.sla_hours ?? nodeData.slaHours ?? null,
    };
};

// Handle delete node event from StepNode component
const handleDeleteNode = (nodeData) => {
    if (confirm('Hapus step ini?')) {
        const newSteps = props.modelValue.filter((_, index) => index !== nodeData.stepIndex);
        // Reorder
        newSteps.forEach((step, index) => {
            step.step_order = index + 1;
        });
        emit('update:modelValue', newSteps);
    }
};

// Build nodes from steps
const buildNodes = (steps) => {
    const nodeList = [];
    const edgeList = [];
    const startX = 50;
    const nodeSpacing = 250;
    const y = 150;

    // Start node
    nodeList.push({
        id: 'start',
        type: 'stepNode',
        position: { x: startX, y },
        data: {
            type: 'start',
            label: 'Mulai',
            editable: false,
        },
    });

    // Step nodes
    steps.forEach((step, index) => {
        const role = props.roles.find(r => r.id === step.required_role_id);
        nodeList.push({
            id: `step-${step.id || index}`,
            type: 'stepNode',
            position: { x: startX + (index + 1) * nodeSpacing, y },
            data: {
                type: 'step',
                label: step.name || `Step ${index + 1}`,
                stepOrder: step.step_order,
                roleId: step.required_role_id,
                roleName: role?.name || 'Pilih Role',
                slaHours: step.sla_hours,
                stepIndex: index,
                originalId: step.id,
                editable: !props.readonly,
                onEdit: handleEditNode,
                onDelete: handleDeleteNode,
            },
        });
    });

    // End node
    nodeList.push({
        id: 'end',
        type: 'stepNode',
        position: { x: startX + (steps.length + 1) * nodeSpacing, y },
        data: {
            type: 'end',
            label: 'Selesai',
            editable: false,
        },
    });

    // Build approval edges (forward flow)
    for (let i = 0; i < nodeList.length - 1; i++) {
        edgeList.push({
            id: `e${i}`,
            source: nodeList[i].id,
            target: nodeList[i + 1].id,
            type: 'smoothstep',
            animated: true,
            style: { stroke: '#6366f1', strokeWidth: 2 },
        });
    }

    nodes.value = nodeList;
    edges.value = edgeList;
};

// Watch for external changes - skip when dragging
watch(() => props.modelValue, (newSteps) => {
    if (!isDragging.value) {
        buildNodes(newSteps);
    }
}, { immediate: true, deep: true });

// Add new step - immediately open edit modal
const addStep = () => {
    const nextOrder = props.modelValue.length + 1;
    const stepIndex = props.modelValue.length;
    
    // Open edit modal with new step data - default SLA 24 hours
    editingNode.value = {
        type: 'step',
        label: '',  // Empty - user must fill
        stepOrder: nextOrder,
        roleId: null,
        slaHours: 24,  // Default 24 jam
        stepIndex: stepIndex,
        isNew: true,  // Flag for new step
    };
};

// Auto layout nodes
const autoLayout = () => {
    buildNodes(props.modelValue);
};

// Handle node drag start
const onNodeDragStart = () => {
    isDragging.value = true;
};

// Handle node drag stop - reorder based on x position
const onNodeDragStop = () => {
    const stepNodes = nodes.value
        .filter(n => n.data.type === 'step')
        .sort((a, b) => a.position.x - b.position.x);

    // Check if order actually changed
    let orderChanged = false;
    stepNodes.forEach((node, index) => {
        if (node.data.stepOrder !== index + 1) {
            orderChanged = true;
        }
    });

    if (orderChanged) {
        const reorderedSteps = stepNodes.map((node, index) => {
            const step = props.modelValue.find(s => 
                s.id === node.data.originalId || 
                props.modelValue.indexOf(s) === node.data.stepIndex
            );
            return {
                ...step,
                name: node.data.label,
                step_order: index + 1,
                required_role_id: node.data.roleId,
                sla_hours: node.data.slaHours,
            };
        });

        emit('update:modelValue', reorderedSteps);
        
        // Rebuild nodes with new order after a short delay
        setTimeout(() => {
            isDragging.value = false;
            buildNodes(reorderedSteps);
        }, 50);
    } else {
        // No order change, just reset dragging flag and snap back to layout
        isDragging.value = false;
        buildNodes(props.modelValue);
    }
};

// Save node edit
const saveNodeEdit = () => {
    if (!editingNode.value) return;
    
    // Validation
    if (!editingNode.value.label || !editingNode.value.label.trim()) {
        alert('Nama step wajib diisi!');
        return;
    }
    if (!editingNode.value.roleId) {
        alert('Role wajib dipilih!');
        return;
    }
    // Validate SLA minimum 1 hour if filled
    if (editingNode.value.slaHours !== null && editingNode.value.slaHours < 1) {
        alert('SLA minimal 1 jam!');
        return;
    }

    const newSteps = [...props.modelValue];
    
    if (editingNode.value.isNew) {
        // Creating new step
        newSteps.push({
            name: editingNode.value.label.trim(),
            step_order: editingNode.value.stepOrder,
            required_role_id: editingNode.value.roleId,
            sla_hours: editingNode.value.slaHours || null,
        });
    } else {
        // Editing existing step
        const stepIndex = editingNode.value.stepIndex;
        if (newSteps[stepIndex]) {
            newSteps[stepIndex] = {
                ...newSteps[stepIndex],
                name: editingNode.value.label.trim(),
                required_role_id: editingNode.value.roleId,
                sla_hours: editingNode.value.slaHours || null,
            };
        }
    }
    
    emit('update:modelValue', newSteps);
    editingNode.value = null;
};

// Expose methods to parent for custom node events
defineExpose({
    handleEditNode,
    handleDeleteNode,
});
</script>

<template>
    <div class="workflow-editor">
        <!-- Toolbar (hidden in readonly mode) -->
        <div v-if="!readonly" class="flex items-center justify-between mb-4 p-3 bg-gray-50 rounded-lg border">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="addStep"
                    class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
                >
                    <Plus class="w-4 h-4 mr-1.5" />
                    Tambah Step
                </button>
            </div>
            <div class="flex items-center gap-3 text-sm text-gray-500">
                <span class="flex items-center gap-1">
                    <span class="w-3 h-3 bg-blue-500 rounded-full"></span> Start
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-3 h-3 bg-indigo-500 rounded-full"></span> Step
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-3 h-3 bg-green-500 rounded-full"></span> End
                </span>
            </div>
        </div>

        <!-- Vue Flow Canvas -->
        <div class="h-[450px] bg-gradient-to-br from-gray-50 to-slate-100 rounded-xl border-2 border-gray-200 overflow-hidden">
            <VueFlow
                v-model:nodes="nodes"
                v-model:edges="edges"
                :node-types="nodeTypes"
                :default-viewport="{ x: 50, y: 100, zoom: 0.9 }"
                fit-view-on-init
                class="workflow-flow"
                @node-drag-start="onNodeDragStart"
                @node-drag-stop="onNodeDragStop"
            >
                <Background pattern-color="#e2e8f0" :gap="20" />
                <Controls position="bottom-right" />
            </VueFlow>
        </div>
        
        <!-- Info Text -->
        <div class="mt-3 flex items-center gap-2 text-sm text-gray-500">
            <Info class="w-4 h-4 text-amber-500" />
            <span>Jika ada <strong class="text-red-500">penolakan</strong> di step manapun, proses kembali ke <strong>awal</strong> untuk revisi.</span>
        </div>

        <!-- Edit Modal -->
        <div
            v-if="editingNode"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="editingNode = null"
        >
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ editingNode.isNew ? 'Tambah Step Baru' : 'Edit Step' }}
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <label for="edit-step-name" class="block text-sm font-medium text-gray-700 mb-1">Nama Step</label>
                        <input
                            id="edit-step-name"
                            v-model="editingNode.label"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="Contoh: Approval Atasan"
                        />
                    </div>
                    <div>
                        <label for="edit-step-role" class="block text-sm font-medium text-gray-700 mb-1">Role yang Diperlukan</label>
                        <select
                            id="edit-step-role"
                            v-model="editingNode.roleId"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">-- Pilih Role --</option>
                            <option v-for="role in roles" :key="role.id" :value="role.id">
                                {{ role.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="edit-step-sla" class="block text-sm font-medium text-gray-700 mb-1">SLA (Jam)</label>
                        <input
                            id="edit-step-sla"
                            v-model.number="editingNode.slaHours"
                            type="number"
                            min="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="Contoh: 24 untuk 1 hari"
                        />
                        <p class="text-xs text-gray-500 mt-1">Batas waktu maksimal untuk approval step ini (opsional)</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button
                        type="button"
                        @click="editingNode = null"
                        class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="saveNodeEdit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                    >
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.workflow-editor {
    /* Fix cursor visibility */
    cursor: default;
}
.workflow-flow {
    cursor: default;
}
.workflow-flow .vue-flow__node {
    cursor: move !important;
}
.workflow-flow .vue-flow__node:active {
    cursor: grabbing !important;
}
.workflow-flow .vue-flow__pane {
    cursor: default !important;
}
.workflow-flow .vue-flow__edge-path {
    stroke-dasharray: 5;
    animation: dash 0.5s linear infinite;
}
@keyframes dash {
    to {
        stroke-dashoffset: -10;
    }
}
</style>
