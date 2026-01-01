<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { ChevronLeft, ChevronRight, Calendar, X } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Pilih Tanggal'
    },
    min: {
        type: String,
        default: ''
    },
    max: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const containerRef = ref(null);
const currentMonth = ref(new Date().getMonth());
const currentYear = ref(new Date().getFullYear());

// Helper: Names of months and days
const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', ' Jum', 'Sab'];

// Initialize calendar based on value or today
watch(() => props.modelValue, (val) => {
    if (val) {
        const date = new Date(val);
        if (!Number.isNaN(date.getTime())) {
            currentMonth.value = date.getMonth();
            currentYear.value = date.getFullYear();
        }
    }
}, { immediate: true });

// Calendar Logic
const daysInMonth = computed(() => {
    return new Date(currentYear.value, currentMonth.value + 1, 0).getDate();
});

const firstDayOfMonth = computed(() => {
    return new Date(currentYear.value, currentMonth.value, 1).getDay();
});

const blankDays = computed(() => {
    return Array.from({ length: firstDayOfMonth.value }, (_, i) => i);
});

const days = computed(() => {
    return Array.from({ length: daysInMonth.value }, (_, i) => i + 1);
});

const prevMonth = () => {
    if (currentMonth.value === 0) {
        currentMonth.value = 11;
        currentYear.value--;
    } else {
        currentMonth.value--;
    }
};

const nextMonth = () => {
    if (currentMonth.value === 11) {
        currentMonth.value = 0;
        currentYear.value++;
    } else {
        currentMonth.value++;
    }
};

const isSelected = (day) => {
    if (!props.modelValue) return false;
    const date = new Date(props.modelValue);
    return date.getDate() === day &&
           date.getMonth() === currentMonth.value &&
           date.getFullYear() === currentYear.value;
};

const isToday = (day) => {
    const today = new Date();
    return day === today.getDate() &&
           currentMonth.value === today.getMonth() &&
           currentYear.value === today.getFullYear();
};

const selectDate = (day) => {
    // Format: YYYY-MM-DD
    const date = new Date(currentYear.value, currentMonth.value, day);
    // Adjust for timezone offset to avoid previous day issue when converting to ISO string
    // Or simpler manual formatting:
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    
    emit('update:modelValue', `${year}-${month}-${d}`);
    isOpen.value = false;
};

const clearDate = (e) => {
    e.stopPropagation();
    emit('update:modelValue', '');
};

const formattedValue = computed(() => {
    if (!props.modelValue) return '';
    const date = new Date(props.modelValue);
    if (Number.isNaN(date.getTime())) return props.modelValue;
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

// Click Outside
const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <div class="relative" ref="containerRef">
        <!-- Trigger -->
        <div 
            @click="isOpen = !isOpen"
            class="w-full flex items-center justify-between pl-9 pr-3 py-2 text-sm border rounded-lg cursor-pointer bg-white transition-all select-none"
            :class="isOpen ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-300 hover:border-slate-400'"
        >
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                <Calendar :size="16" />
            </div>

            <span :class="modelValue ? 'text-slate-700' : 'text-slate-400'">
                {{ formattedValue || placeholder }}
            </span>

            <div v-if="modelValue" @click="clearDate" class="ml-2 hover:bg-slate-100 rounded-full p-0.5 text-slate-400 hover:text-slate-600 transition-colors">
                <X :size="14" />
            </div>
        </div>

        <!-- Dropdown -->
        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div v-if="isOpen" class="absolute z-50 mt-1 bg-white rounded-lg shadow-xl border border-slate-200 p-4 w-72 left-0 sm:left-auto">
                <!-- Header -->
                <div class="flex justify-between items-center mb-4">
                    <button type="button" @click.stop="prevMonth" class="p-1 hover:bg-slate-100 rounded-lg transition-colors text-slate-600">
                        <ChevronLeft :size="20" />
                    </button>
                    <div class="text-sm font-bold text-slate-800">
                        {{ monthNames[currentMonth] }} {{ currentYear }}
                    </div>
                    <button type="button" @click.stop="nextMonth" class="p-1 hover:bg-slate-100 rounded-lg transition-colors text-slate-600">
                        <ChevronRight :size="20" />
                    </button>
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-7 gap-1 mb-2">
                    <div v-for="day in dayNames" :key="day" class="text-center text-[10px] uppercase font-bold text-slate-400 py-1">
                        {{ day }}
                    </div>
                </div>
                <div class="grid grid-cols-7 gap-1 text-sm">
                    <div v-for="blank in blankDays" :key="'blank-' + blank" class="p-1"></div>
                    <button
                        type="button"
                        v-for="day in days"
                        :key="day"
                        @click.stop="selectDate(day)"
                        class="w-8 h-8 rounded-full flex items-center justify-center transition-all text-xs font-medium relative"
                        :class="[
                            isSelected(day) ? 'bg-blue-600 text-white shadow-md' : 'text-slate-700 hover:bg-blue-50 hover:text-blue-600',
                            isToday(day) && !isSelected(day) ? 'ring-1 ring-blue-500 text-blue-600 font-bold' : ''
                        ]"
                    >
                        {{ day }}
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>
