<script setup lang="ts">
    import {
        ref,
        computed
    } from 'vue';
    import {
        Head,
        router
    } from '@inertiajs/vue3';
    import AppLayout from '@/layouts/AppLayout.vue';
    import {
        Search,
        Database,
        Zap,
        X,
        Loader2,
        ChevronLeft,
        ChevronRight,
        ChevronsLeft,
        ChevronsRight
    } from 'lucide-vue-next';
    import CustomDropdown from '@/components/CustomDropdown.vue';
    import CustomDatePicker from '@/components/CustomDatePicker.vue';

    // --- PROPS ---
    interface Props {
        logs: any;
        filters: Record < string,
        any > ;
        statuses: Record < string,
        string > ;
        planners: Record < string,
        string > ;
        projects: Record < string,
        string > ;
        roles: Record < string,
        string > ;
        targetOptions: string[];
        actionOptions: string[];
        permissions: Record<string, string>;
    }

    const props = defineProps < Props > ();

    // --- STATE ---
    const search = ref(props.filters?.search || '');
    const actionFilter = ref(props.filters?.action || '');
    const targetFilter = ref(props.filters?.target || '');
    const dateFrom = ref(props.filters?.date_from || '');
    const dateTo = ref(props.filters?.date_to || '');
    const isLoading = ref(false);

    // --- MANUAL SEARCH TRIGGER ---
    const applyFilter = () => {
        isLoading.value = true;

        router.get('/audit-logs', {
            search: search.value,
            action: actionFilter.value,
            target: targetFilter.value,
            date_from: dateFrom.value,
            date_to: dateTo.value
        }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => isLoading.value = false
        });
    };

    // Clear all filters
    const clearFilters = () => {
        search.value = '';
        actionFilter.value = '';
        targetFilter.value = '';
        dateFrom.value = '';
        dateTo.value = '';
        applyFilter();
    };

    // --- HELPERS ---
    const formatValue = (key, value) => {
        if (value === null || value === undefined) return '-';

        const lowerKey = key.toLowerCase();

        if (lowerKey === 'planner_id' && props.planners) {
            const plannerName = props.planners[String(value)];
            if (plannerName) return plannerName; // Kembalikan Nama Fauzan
        }

        if ((lowerKey === 'status_id' || lowerKey === 'status') && props.statuses) {
            const statusName = props.statuses[String(value)];
            if (statusName) return statusName;
        }

        if (lowerKey === 'project_id' && props.projects) {
            const projectName = props.projects[String(value)];
            if (projectName) return projectName;
        }

        if (lowerKey === 'role_id' && props.roles) {
            const roleName = props.roles[String(value)];
            if (roleName) return roleName;
        }

        if (lowerKey === 'permission_id' && props.permissions) {
            const permissionName = props.permissions[String(value)];
            if (permissionName) return permissionName;
        }

        if (lowerKey === 'uploaded_by' && props.planners) {
            const uploaderName = props.planners[String(value)];
            if (uploaderName) return uploaderName;
        }

        if (lowerKey === 'user_l_id' && props.planners) {
            const userName = props.planners[String(value)];
            if (userName) return userName;
        }

        if (lowerKey.includes('file_size') || lowerKey.includes('size')) {
            const sizeInBytes = Number(value);
            if (!Number.isNaN(sizeInBytes)) {
                if (sizeInBytes >= 1073741824) {
                    return (sizeInBytes / 1073741824).toFixed(2) + ' GB';
                } else if (sizeInBytes >= 1048576) {
                    return (sizeInBytes / 1048576).toFixed(2) + ' MB';
                } else if (sizeInBytes >= 1024) {
                    return (sizeInBytes / 1024).toFixed(2) + ' KB';
                } else {
                    return sizeInBytes + ' B';
                }
            }
        }

        if (lowerKey.includes('uploaded_at') || lowerKey.includes('updated_at') || lowerKey.includes('time')) {
            const date = new Date(value);
            if (!Number.isNaN(date.getTime())) {
                return date.toLocaleDateString('id-ID', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }

        return value;
    };

    const getSnapshotData = (log) => {
        return log.action === 'CREATE' ? log.new_values : log.old_values;
    }

    const isChanged = (key, log) => {
        if (log.action !== 'UPDATE') return false;
        if (!log.old_values || !log.new_values) return false;
        const oldVal = log.old_values[key];
        const newVal = log.new_values[key];
        if (oldVal === undefined && newVal !== undefined) return false;
        return oldVal != newVal;
    };

    const formatDateTime = (dateString) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const formatTarget = (tableName) => {
        switch (tableName) {
            case 'vl_projects':
                return {
                    label: 'Project', color: 'bg-indigo-50 text-indigo-700 border-indigo-200'
                };
            case 'vl_documents':
                return {
                    label: 'Document', color: 'bg-amber-50 text-amber-700 border-amber-200'
                };
            case 'vl_users':
                return {
                    label: 'Assign Role', color: 'bg-pink-50 text-pink-700 border-pink-200'
                };
            case 'vl_permissions':
                return {
                    label: 'Permission', color: 'bg-cyan-50 text-cyan-700 border-cyan-200'
                };
            case 'vl_roles':
                return {
                    label: 'Role', color: 'bg-orange-50 text-orange-700 border-orange-200'
                };
            case 'vl_role_has_permissions':
                return {
                    label: 'Assign Permission', color: 'bg-purple-50 text-purple-700 border-purple-200'
                };
            default:
                return {
                    label: tableName.replaceAll('vl_', '').replaceAll('_', ' ').toUpperCase(),
                        color: 'bg-slate-100 text-slate-600 border-slate-200'
                };
        }
    };

    const getActionColor = (action) => {
        switch (action) {
            case 'CREATE':
                return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            case 'UPDATE':
                return 'bg-blue-50 text-blue-700 border-blue-200';
            case 'DELETE':
                return 'bg-rose-50 text-rose-700 border-rose-200';
            default:
                return 'bg-slate-100 text-slate-700';
        }
    };

    // --- PAGINATION ---
    const paginationPages = computed(() => {
        const current = props.logs.current_page;
        const last = props.logs.last_page;
        const MAX_VISIBLE = 6;

        if (last <= MAX_VISIBLE) {
            return Array.from({ length: last }, (_, i) => i + 1);
        }

        let start = current - Math.floor(MAX_VISIBLE / 2);
        start = Math.max(1, start);
        let end = start + MAX_VISIBLE - 1;

        if (end > last) {
            end = last;
            start = Math.max(1, end - MAX_VISIBLE + 1);
        }

        const pages = [];
        for (let i = start; i <= end; i++) {
            pages.push(i);
        }
        return pages;
    });

    const changePage = (page) => {
        if (page < 1 || page > props.logs.last_page || page === props.logs.current_page) return;
        isLoading.value = true;
        
        router.get('/audit-logs', {
            search: search.value,
            action: actionFilter.value,
            target: targetFilter.value,
            date_from: dateFrom.value,
            date_to: dateTo.value,
            page: page
        }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => isLoading.value = false
        });
    };
</script>

<template>

    <Head>
        <title>Audit Logs</title>
    </Head>

    <AppLayout title="Audit Logs" currentPage="audit-logs">
        <div class="py-4">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">

                    <div
                        class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                                Riwayat Aktivitas
                                <div v-if="isLoading"
                                    class="flex items-center justify-center bg-blue-50 rounded-full px-2 py-0.5">
                                    <span
                                        class="animate-spin h-3 w-3 border-2 border-blue-500 border-t-transparent rounded-full mr-1.5"></span>
                                    <span class="text-[10px] font-bold text-blue-600 tracking-wide">SYNCING...</span>
                                </div>
                            </h2>
                            <p class="text-sm text-slate-500 mt-1">
                                Total Data: <span
                                    class="font-mono font-bold text-slate-900 bg-slate-100 px-1.5 py-0.5 rounded">{{ logs . total }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- Filter Section -->
                        <div class="space-y-3">
                            <h3 class="text-sm font-semibold text-slate-700">Filter & Pencarian</h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                <!-- Date From Filter -->
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5" for="date-from">Dari
                                        Tanggal</label>
                                    <div class="relative">
                                        <CustomDatePicker v-model="dateFrom" placeholder="Pilih Tanggal" />
                                    </div>
                                </div>

                                <!-- Date To Filter -->
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5" for="date-to">Sampai
                                        Tanggal</label>
                                    <div class="relative">
                                        <CustomDatePicker v-model="dateTo" placeholder="Pilih Tanggal" />
                                    </div>
                                </div>

                                <!-- Target Filter -->
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5"
                                        for="target-filter">Target Data</label>
                                    <div class="relative">
                                        <CustomDropdown v-model="targetFilter" :options="targetOptions"
                                            placeholder="Semua Data" :icon="Database">
                                            <template #trigger="{ value, placeholder }">
                                                <span v-if="value">{{ formatTarget(value).label }}</span>
                                                <span v-else>{{ placeholder }}</span>
                                            </template>
                                            <template #option="{ option, selected }">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="px-1.5 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wider"
                                                        :class="formatTarget(option).color">
                                                        {{ formatTarget(option).label }}
                                                    </span>
                                                </div>
                                            </template>
                                        </CustomDropdown>
                                    </div>
                                </div>

                                <!-- Action Filter -->
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5"
                                        for="action-filter">Jenis Aksi</label>
                                    <div class="relative">
                                        <CustomDropdown v-model="actionFilter" :options="actionOptions"
                                            placeholder="Semua Aksi" :icon="Zap">
                                            <template #option="{ option, selected }">
                                                <span
                                                    class="px-1.5 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wider"
                                                    :class="getActionColor(option)">
                                                    {{ option }}
                                                </span>
                                            </template>
                                        </CustomDropdown>
                                    </div>
                                </div>
                            </div>

                            <!-- Search and Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                                <!-- Search Input -->
                                <div class="flex-1 flex-shrink-0">
                                    <div class="relative">
                                        <Search :size="16"
                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                        <input v-model="search" type="text" placeholder="Keyword / User / ID..."
                                            class="w-full pl-9 pr-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                            @keyup.enter="applyFilter" />
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex gap-2">
                                    <button @click="clearFilters"
                                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:border-slate-400 transition-colors flex items-center gap-2 disabled:opacity-50"
                                        :disabled="isLoading">
                                        <X :size="16" />
                                        Reset Filter
                                    </button>
                                    <button @click="applyFilter"
                                        class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]"
                                        :disabled="isLoading">
                                        <Loader2 v-if="isLoading" :size="16" class="animate-spin" />
                                        <Search v-else :size="16" />
                                        {{ isLoading ? 'Mencari...' : 'Cari Data' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden relative">
                    <div v-if="isLoading"
                        class="absolute inset-0 bg-white/60 z-20 flex items-center justify-center backdrop-blur-[1px]">
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="min-w-full text-xs text-left">
                            <thead
                                class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-bold tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 w-10 text-center">#</th>
                                    <th class="px-4 py-3 w-48">User</th>
                                    <th class="px-4 py-3 w-24">Aksi</th>
                                    <th class="px-4 py-3 w-32">Target Data</th>
                                    <th class="px-4 py-3 min-w-[300px]">Detail Perubahan</th>
                                    <th class="px-4 py-3 w-32 whitespace-nowrap">Waktu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(log, idx) in logs.data" :key="log.id"
                                    class="hover:bg-slate-50 transition-colors"
                                    :class="idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/40'">
                                    <td class="px-4 py-3 align-top text-slate-500 text-center">
                                        {{ idx + 1 + (logs . from - 1) }}</td>

                                    <td class="px-4 py-3 align-top">
                                        <div class="font-bold text-slate-800 text-sm">
                                            {{ log . user ? log . user . Name : 'System' }}</div>
                                        <div
                                            class="text-slate-500 mt-1 flex flex-wrap gap-1 items-center font-mono text-[10px]">
                                            <span
                                                class="bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">@{{ log.user ? log.user.UserID : '-' }}</span>
                                            <span class="opacity-75">#{{ log . user_id }}</span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-0.5 font-mono">{{ log . ip_address }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 align-top">
                                        <span
                                            class="px-2.5 py-1 rounded text-[10px] font-bold border shadow-sm inline-block text-center min-w-[80px]"
                                            :class="getActionColor(log.action)">{{ log . action }}</span>
                                    </td>

                                    <td class="px-4 py-3 align-top">
                                        <div v-if="log.table_name">
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2 py-1 rounded text-[10px] font-bold border mb-1 shadow-sm whitespace-nowrap"
                                                :class="formatTarget(log.table_name).color">{{ formatTarget(log . table_name) . label }}</span>
                                            <div class="text-slate-500 font-mono text-[10px]">ID: <span
                                                    class="bg-slate-100 px-1 rounded select-all">#{{ log . record_id }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3.5 align-top">
                                        <div
                                            class="w-full border border-slate-200 rounded-lg overflow-hidden bg-gradient-to-br from-white to-slate-50/30 shadow-sm">
                                            <div v-for="(val, key) in getSnapshotData(log)" :key="key"
                                                class="grid grid-cols-[140px_1fr] border-b border-slate-100 last:border-0 transition-colors hover:bg-slate-50/50"
                                                :class="[
                                                    log.action === 'UPDATE' && isChanged(key, log) ?
                                                    'bg-amber-50/40' : '',
                                                ]">
                                                <div
                                                    class="px-3 py-2 font-semibold text-slate-600 text-right border-r border-slate-200 text-[10px] uppercase tracking-wide bg-slate-50/50 flex items-center justify-end">
                                                    <span class="truncate">{{ key }}</span>
                                                </div>

                                                <div class="px-3 py-2 text-[11px] break-all">
                                                    <div v-if="log.action === 'UPDATE'">
                                                        <div v-if="isChanged(key, log)"
                                                            class="flex flex-wrap items-center gap-2">
                                                            <span
                                                                class="text-rose-600 line-through opacity-70 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200">
                                                                {{ formatValue(key, log . old_values[key]) }}
                                                            </span>
                                                            <svg class="w-4 h-4 text-slate-400" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                            </svg>
                                                            <span
                                                                class="text-emerald-700 font-semibold bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                                                {{ formatValue(key, log . new_values[key]) }}
                                                            </span>
                                                        </div>
                                                        <div v-else class="text-slate-600">{{ formatValue(key, val) }}
                                                        </div>
                                                    </div>
                                                    <div v-else-if="log.action === 'CREATE'">
                                                        <span
                                                            class="font-medium text-emerald-700 bg-emerald-50/80 px-2 py-0.5 rounded-md border border-emerald-200 inline-block">
                                                            {{ formatValue(key, val) }}
                                                        </span>
                                                    </div>
                                                    <div v-else-if="log.action === 'DELETE'">
                                                        <span
                                                            class="font-medium text-rose-700 line-through bg-rose-50/80 px-2 py-0.5 rounded-md border border-rose-200 inline-block">
                                                            {{ formatValue(key, val) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td
                                        class="px-4 py-3 align-top whitespace-nowrap font-mono text-slate-500 text-[11px]">
                                        {{ formatDateTime(log . created_at) }}</td>
                                </tr>
                                <tr v-if="logs.data.length === 0">
                                    <td colspan="6" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center text-slate-400"><svg
                                                class="h-10 w-10 mb-3 opacity-50" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p class="text-sm font-medium">Data tidak ditemukan.</p>
                                            <p class="text-xs mt-1">Coba ubah filter atau kata kunci pencarian.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-slate-50 px-4 py-3 border-t border-slate-200 text-xs text-slate-500 flex flex-col sm:flex-row justify-between items-center gap-3"
                        v-if="logs.data.length > 0">
                        <span>Menampilkan <span class="font-bold text-slate-700">{{ logs . from }}</span> - <span
                                class="font-bold text-slate-700">{{ logs . to }}</span> dari <span
                                class="font-bold text-slate-700">{{ logs . total }}</span> data</span>

                        <div class="flex gap-1">
                            <!-- Prev -->
                            <button @click="changePage(logs.current_page - 1)" 
                                :disabled="logs.current_page === 1"
                                class="px-2 py-1.5 border rounded-lg transition-all text-slate-600 border-slate-300 hover:bg-slate-50 hover:border-slate-400 disabled:opacity-50 disabled:cursor-not-allowed">
                                <ChevronLeft :size="16" />
                            </button>

                            <!-- Jump -5 -->
                            <button v-if="logs.current_page > 3 && logs.last_page > 5" 
                                @click="changePage(logs.current_page - 5 < 1 ? 1 : logs.current_page - 5)"
                                class="px-2 py-1.5 border rounded-lg transition-all text-slate-600 border-slate-300 hover:bg-slate-50 hover:border-slate-400">
                                <ChevronsLeft :size="16" />
                            </button>

                            <!-- Pages -->
                            <button v-for="page in paginationPages" :key="page"
                                @click="changePage(page)"
                                class="px-3 py-1.5 border rounded-lg transition-all text-[11px] font-medium"
                                :class="page === logs.current_page ? 
                                    'bg-blue-600 text-white border-blue-600 shadow-sm' : 
                                    'bg-white text-slate-600 border-slate-300 hover:bg-slate-50 hover:border-slate-400'">
                                {{ page }}
                            </button>

                            <!-- Jump +5 -->
                            <button v-if="logs.current_page < logs.last_page - 2 && logs.last_page > 5"
                                @click="changePage(logs.current_page + 5 > logs.last_page ? logs.last_page : logs.current_page + 5)"
                                class="px-2 py-1.5 border rounded-lg transition-all text-slate-600 border-slate-300 hover:bg-slate-50 hover:border-slate-400">
                                <ChevronsRight :size="16" />
                            </button>

                            <!-- Next -->
                            <button @click="changePage(logs.current_page + 1)" 
                                :disabled="logs.current_page === logs.last_page"
                                class="px-2 py-1.5 border rounded-lg transition-all text-slate-600 border-slate-300 hover:bg-slate-50 hover:border-slate-400 disabled:opacity-50 disabled:cursor-not-allowed">
                                <ChevronRight :size="16" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
