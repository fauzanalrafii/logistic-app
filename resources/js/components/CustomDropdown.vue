<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { ChevronDown, Check } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: [String, Number, null],
        default: ''
    },
    options: {
        type: Array,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Pilih...'
    },
    icon: {
        type: [Object, Function],
        default: null
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const dropdownRef = ref(null);

const toggle = () => isOpen.value = !isOpen.value;

const select = (option) => {
    emit('update:modelValue', option);
    isOpen.value = false;
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Helper to determine if an option is selected
const isSelected = (option) => {
    return props.modelValue === option;
};
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <!-- Trigger Button -->
        <button 
            type="button"
            @click="toggle"
            class="w-full flex items-center justify-between text-left pl-9 pr-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all bg-white"
            :class="isOpen ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-300 hover:border-slate-400'"
        >
            <!-- Start Icon (Absolute like in original design) -->
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                <component :is="icon" :size="16" v-if="icon" />
            </div>

            <!-- Content -->
            <span class="block truncate" :class="modelValue ? 'text-slate-700' : 'text-slate-400'">
                <slot name="trigger" :value="modelValue" :placeholder="placeholder">
                    {{ modelValue || placeholder }}
                </slot>
            </span>
            
            <!-- Chevron -->
            <ChevronDown 
                :size="16" 
                class="text-slate-400 transition-transform duration-200 ml-2 flex-shrink-0"
                :class="isOpen ? 'rotate-180 text-blue-500' : ''"
            />
        </button>

        <!-- Dropdown Menu -->
        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div v-if="isOpen" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-auto py-1 focus:outline-none">
                <!-- Placeholder / Clear Option -->
                <div 
                    @click="select('')"
                    class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-slate-50 text-slate-500 text-sm border-b border-slate-50 mb-1"
                >
                    <span class="block truncate font-medium">{{ placeholder }}</span>
                </div>

                <!-- Options -->
                <div
                    v-for="(option, idx) in options"
                    :key="idx"
                    @click="select(option)"
                    class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-slate-50 transition-colors"
                >
                    <slot name="option" :option="option" :selected="isSelected(option)">
                        <span class="block truncate text-sm" :class="isSelected(option) ? 'font-semibold text-blue-600' : 'text-slate-600'">
                            {{ option }}
                        </span>
                    </slot>

                    <!-- Checkmark for selected -->
                    <span
                        v-if="isSelected(option)"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-blue-600"
                    >
                        <Check :size="14" />
                    </span>
                </div>
            </div>
        </transition>
    </div>
</template>
