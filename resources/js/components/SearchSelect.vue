<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
    modelValue: [String, Number, null],
    items: { type: Array, required: true },
    itemLabel: { type: String, required: true },
    itemValue: { type: String, required: true },
    label: { type: String, default: "" },
    placeholder: { type: String, default: "Search..." },

    // 🔥 penentu mode
    remote: { type: Boolean, default: false },
});


const emit = defineEmits(["update:modelValue", "search"]);

const open = ref(false);
const search = ref("");
const isSelecting = ref(false);
const backupValue = ref(null);
const backupLabel = ref("");
const hasSelected = ref(false);


watch(search, (val) => {
    if (props.remote) {
        emit("search", val);
    }
});

watch(
    () => props.modelValue,
    (val) => {
        if (isSelecting.value) return;

        const selected = props.items.find(
            i => i[props.itemValue] === val
        );

        search.value = selected ? selected[props.itemLabel] : "";
    },
    { immediate: true }
);

const filteredItems = computed(() => {
    // 🔥 remote search: data sudah dari API
    if (props.remote) {
        return props.items;
    }

    // 🔥 local search (planner)
    if (!search.value) return props.items;

    return props.items.filter(item =>
        item[props.itemLabel]
            ?.toString()
            .toLowerCase()
            .includes(search.value.toLowerCase())
    );
});

function onFocus() {
    backupValue.value = props.modelValue;

    const selected = props.items.find(
        i => i[props.itemValue] === props.modelValue
    );
    backupLabel.value = selected ? selected[props.itemLabel] : "";

    hasSelected.value = false;   // 🔥 reset
    search.value = "";
    open.value = true;
}

function onBlur() {
    setTimeout(() => {
        if (!hasSelected.value) {
            // 🔁 rollback
            emit("update:modelValue", backupValue.value);
            search.value = backupLabel.value;
        }

        open.value = false;
    }, 100);
}

function select(item) {
    hasSelected.value = true;    // 🔥 PENTING

    emit("update:modelValue", item[props.itemValue]);
    search.value = item[props.itemLabel];
    open.value = false;
}
</script>

<template>
    <div class="relative w-full">
        <label v-if="label" class="block text-sm font-semibold mb-1">
            {{ label }}
        </label>

        <input
            v-model="search"
            @focus="onFocus"
            @blur="onBlur"
            type="text"
            :placeholder="placeholder"
            class="input w-full"
        />

        <div
            v-if="open"
            class="absolute w-full z-40 bg-white border border-gray-200 rounded-lg shadow max-h-52 overflow-auto mt-1"
        >
            <div
                v-for="item in filteredItems"
                :key="item[itemValue]"
                @mousedown.prevent="select(item)"
                class="px-3 py-2 hover:bg-blue-100 cursor-pointer"
            >
                {{ item[itemLabel] }}
            </div>

            <div
                v-if="filteredItems.length === 0"
                class="px-3 py-2 text-gray-400"
            >
                No results
            </div>
        </div>
    </div>
</template>

<style scoped>
.input {
    @apply border border-gray-300 rounded-lg px-3 py-2 shadow-sm
    focus:ring-blue-500 focus:border-blue-500;
}
</style>