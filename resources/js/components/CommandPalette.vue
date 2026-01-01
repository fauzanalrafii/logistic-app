<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Search, FileText, Zap, Clock, Filter, MapPin, Building2, Loader2, CheckCircle } from 'lucide-vue-next';

const props = defineProps<{
    isOpen: boolean;
}>();

const emit = defineEmits(['close']);

const searchQuery = ref('');
const selectedIndex = ref(0);
const inputRef = ref<HTMLInputElement | null>(null);

// API search state
const isSearching = ref(false);
const searchResults = ref<any[]>([]);
const approvalResults = ref<any[]>([]);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

// Recent pages from localStorage
const recentPages = ref<{id: string, label: string, href: string}[]>([]);

function loadRecentPages() {
    try {
        const stored = localStorage.getItem('recentPages');
        if (stored) {
            recentPages.value = JSON.parse(stored).slice(0, 3);
        }
    } catch (e) {
        recentPages.value = [];
    }
}

function saveRecentPage(page: {id: string, label: string, href: string}) {
    const existing = recentPages.value.filter(p => p.id !== page.id);
    const updated = [{...page}, ...existing].slice(0, 5);
    recentPages.value = updated;
    localStorage.setItem('recentPages', JSON.stringify(updated));
}

// Debounced search API call
async function searchApi(query: string) {
    if (query.length < 2) {
        searchResults.value = [];
        approvalResults.value = [];
        return;
    }

    isSearching.value = true;
    try {
        const response = await fetch(`/api/search?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        searchResults.value = (data.projects || []).slice(0, 8);
        approvalResults.value = (data.approvals || []).slice(0, 5);
    } catch (e) {
        searchResults.value = [];
        approvalResults.value = [];
    } finally {
        isSearching.value = false;
    }
}

onMounted(() => {
    loadRecentPages();
});

// Static menu items - minimal
const staticPages = [
    { id: 'dashboard', label: 'Dashboard', href: '/dashboard' },
    { id: 'projects', label: 'Project Board', href: '/projects' },
    { id: 'plan-on-desk', label: 'Plan On Desk', href: '/projects/plan-on-desk' },
    { id: 'approvals', label: 'Approvals Inbox', href: '/approval' },
    { id: 'documents', label: 'Documents', href: '/documents' },
];

const staticActions = [
    { id: 'new-project', label: 'Create New Project', href: '/projects/create' },
    { id: 'logout', label: 'Logout', href: '/logout', method: 'post' },
];

// Flat list for keyboard nav only
const flatList = computed(() => {
    const items: any[] = [];
    
    if (!searchQuery.value) {
        recentPages.value.forEach((r, i) => items.push({ ...r, _idx: i, _type: 'recent' }));
    }
    
    approvalResults.value.forEach((a, i) => items.push({ 
        id: `approval-${a.id}`, 
        label: a.project_name,
        href: `/approval/${a.id}`,
        _idx: items.length, 
        _type: 'approval' 
    }));
    
    searchResults.value.forEach((p, i) => items.push({ 
        id: `project-${p.id}`, 
        label: p.name,
        href: `/projects/project-detail?id=${p.id}`,
        _idx: items.length, 
        _type: 'project' 
    }));
    
    if (!searchQuery.value || searchQuery.value.length < 2) {
        staticPages.slice(0, 5).forEach(p => items.push({ ...p, _idx: items.length, _type: 'page' }));
        staticActions.forEach(a => items.push({ ...a, _idx: items.length, _type: 'action' }));
    }
    
    return items;
});

// Watch search query
watch(searchQuery, (q) => {
    selectedIndex.value = 0;
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => searchApi(q), 300);
});

// Focus input when opened
watch(() => props.isOpen, (isOpen) => {
    if (isOpen) {
        searchQuery.value = '';
        selectedIndex.value = 0;
        searchResults.value = [];
        approvalResults.value = [];
        loadRecentPages();
        setTimeout(() => inputRef.value?.focus(), 50);
    }
});

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIndex.value = Math.min(selectedIndex.value + 1, flatList.value.length - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIndex.value = Math.max(selectedIndex.value - 1, 0);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const item = flatList.value[selectedIndex.value];
        if (item) navigateTo(item);
    } else if (e.key === 'Escape') {
        emit('close');
    }
}

function navigateTo(item: any) {
    emit('close');
    if (item._type === 'page' || item._type === 'recent') {
        saveRecentPage({ id: item.id, label: item.label, href: item.href });
    }
    if (item.method === 'post') {
        router.post(item.href);
    } else {
        router.visit(item.href);
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-start justify-center pt-[10vh]"
                @click.self="$emit('close')"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/60"></div>

                <!-- Modal -->
                <div
                    class="relative w-full max-w-xl mx-4 rounded-2xl bg-white shadow-2xl"
                    @keydown="handleKeydown"
                >
                    <!-- Search Input -->
                    <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100">
                        <Search v-if="!isSearching" class="w-5 h-5 text-slate-400" />
                        <Loader2 v-else class="w-5 h-5 text-blue-500 animate-spin" />
                        <input
                            ref="inputRef"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search projects, approvals..."
                            class="flex-1 bg-transparent text-slate-800 placeholder-slate-400 focus:outline-none"
                        />
                        <kbd class="px-1.5 py-0.5 text-[10px] text-slate-400 bg-slate-100 rounded">ESC</kbd>
                    </div>

                    <!-- Results -->
                    <div class="max-h-[50vh] overflow-y-auto py-2">
                        <!-- Recent (no search) -->
                        <template v-if="!searchQuery && recentPages.length > 0">
                            <div class="px-4 py-1 text-[10px] font-semibold text-slate-400 uppercase">Recent</div>
                            <button
                                v-for="(item, idx) in recentPages"
                                :key="item.id"
                                @click="navigateTo({...item, _type: 'recent'})"
                                class="cmd-item"
                                :class="{ 'cmd-item-active': selectedIndex === idx }"
                            >
                                <Clock class="w-4 h-4 text-slate-400" />
                                <span class="flex-1 truncate">{{ item.label }}</span>
                            </button>
                        </template>

                        <!-- Approvals -->
                        <template v-if="approvalResults.length > 0">
                            <div class="px-4 py-1 text-[10px] font-semibold text-orange-500 uppercase mt-2">Pending Approvals</div>
                            <button
                                v-for="(item, idx) in approvalResults"
                                :key="item.id"
                                @click="navigateTo({id: `approval-${item.id}`, href: `/approval/${item.id}`, _type: 'approval'})"
                                class="cmd-item"
                            >
                                <CheckCircle class="w-4 h-4 text-orange-500" />
                                <div class="flex-1 min-w-0">
                                    <div class="truncate">{{ item.project_name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ item.project_code }} • {{ item.flow_name }}</div>
                                </div>
                                <span class="text-[10px] bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full">Approve</span>
                            </button>
                        </template>

                        <!-- Projects -->
                        <template v-if="searchResults.length > 0">
                            <div class="px-4 py-1 text-[10px] font-semibold text-slate-400 uppercase mt-2">Projects</div>
                            <button
                                v-for="item in searchResults"
                                :key="item.id"
                                @click="navigateTo({id: `project-${item.id}`, href: `/projects/project-detail?id=${item.id}`, _type: 'project'})"
                                class="cmd-item"
                            >
                                <Building2 class="w-4 h-4 text-emerald-500" />
                                <div class="flex-1 min-w-0">
                                    <div class="truncate">{{ item.name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ item.code }} • {{ item.city || item.region }}</div>
                                </div>
                                <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full">{{ item.status }}</span>
                            </button>
                        </template>

                        <!-- Pages (no search or short query) -->
                        <template v-if="!searchQuery || searchQuery.length < 2">
                            <div class="px-4 py-1 text-[10px] font-semibold text-slate-400 uppercase mt-2">Pages</div>
                            <button
                                v-for="item in staticPages"
                                :key="item.id"
                                @click="navigateTo({...item, _type: 'page'})"
                                class="cmd-item"
                            >
                                <FileText class="w-4 h-4 text-slate-400" />
                                <span class="flex-1 truncate">{{ item.label }}</span>
                            </button>

                            <div class="px-4 py-1 text-[10px] font-semibold text-slate-400 uppercase mt-2">Actions</div>
                            <button
                                v-for="item in staticActions"
                                :key="item.id"
                                @click="navigateTo({...item, _type: 'action'})"
                                class="cmd-item"
                            >
                                <Zap class="w-4 h-4 text-amber-500" />
                                <span class="flex-1 truncate">{{ item.label }}</span>
                            </button>
                        </template>

                        <!-- Loading -->
                        <div v-if="isSearching && searchQuery.length >= 2" class="py-6 text-center text-slate-400 text-sm">
                            Searching...
                        </div>

                        <!-- Empty -->
                        <div v-if="searchQuery.length >= 2 && !isSearching && searchResults.length === 0 && approvalResults.length === 0" class="py-8 text-center">
                            <Search class="w-8 h-8 mx-auto text-slate-300 mb-2" />
                            <div class="text-slate-400 text-sm">No results found</div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center gap-4 px-4 py-2 border-t border-slate-100 text-[10px] text-slate-400">
                        <span><kbd class="bg-slate-100 px-1 rounded">↑↓</kbd> navigate</span>
                        <span><kbd class="bg-slate-100 px-1 rounded">↵</kbd> select</span>
                        <span><kbd class="bg-slate-100 px-1 rounded">esc</kbd> close</span>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Simple hover - no JS state updates */
.cmd-item {
    @apply w-full flex items-center gap-3 px-4 py-2 text-left text-slate-700;
}
.cmd-item:hover {
    @apply bg-slate-50;
}
.cmd-item-active {
    @apply bg-blue-50;
}
</style>
