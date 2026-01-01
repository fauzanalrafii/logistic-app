<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import logo from "@/assets/images/supercorridor.png";
import { ref, watchEffect, computed } from "vue";
import { useDarkMode } from "@/composables/useDarkMode";
import {
    LayoutDashboard,
    FolderKanban,
    FilePlus,
    ClipboardList,
    History,
    MapPin,
    FileText,
    ClipboardCheck,
    Workflow,
    Shield,
    Users,
    Key,
    UserCog,
    ScrollText,
    Activity,
    FileSearch,
    Hammer,
    Package,
    Boxes,
    Rocket,
    Archive,
    ChevronDown,
    ChevronRight,
    ChevronLeft,
} from "lucide-vue-next";

const props = defineProps<{
    isOpen: boolean;
    currentPage: string;
}>();

const page = usePage();
const { isDark } = useDarkMode();

const canManageRoles = computed(() => {
    return page.props.auth.permissions?.includes("manage_roles") ?? false;
});

const approvalCount = computed(() => Number(page.props.approval?.inbox_count ?? 0));

/** =========================
 * Helpers
 * ========================= */
const permissions = computed<string[]>(() => page.props?.auth?.permissions ?? []);

function can(permission: string) {
    return permissions.value.includes(permission);
}

defineEmits(["toggleSidebar"]);

// Submenu Project
/** Logs Section */
const canViewLogsSection = computed(() => can("audit_logs.view") || can("logs_monitor.view"));

/** =========================
 * Submenu state
 * ========================= */
const isProjectOpen = ref(false);

// Submenu Roles
const isRolesOpen = ref(false);

watchEffect(() => {
    const projectPages = ["project.create", "project.list", "project.history"];
    isProjectOpen.value = projectPages.includes(props.currentPage);
    const rolesPages = [
        "roles.index",
        "permissions.index",
        "assign-roles.index",
        "assign-permissions.index",
    ];
    isRolesOpen.value = rolesPages.includes(props.currentPage);
});
</script>

<template>
    <div>
        <div
            v-if="isOpen"
            @click="$emit('toggleSidebar')"
            class="fixed inset-0 z-20 bg-black opacity-50 transition-opacity lg:hidden"
        ></div>

        <aside
            class="fixed inset-y-0 left-0 z-30 w-64 overflow-y-auto transition-transform duration-300 transform lg:static lg:inset-0 lg:translate-x-0 h-screen sidebar-glass [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none] text-white"
            :class="[
                isOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <!-- Header Logo -->
            <div class="sidebar-header flex flex-col items-center justify-center py-6 px-4 text-center shrink-0">
                <img
                    :src="logo"
                    alt="Supercorridor Logo"
                    class="w-14 h-14 object-contain mb-3"
                />
                <h1 class="text-sm font-bold tracking-tight leading-tight text-white">
                    PT. Trans Indonesia<br />
                    <span class="text-blue-300">Supercorridor</span>
                </h1>
                <p class="text-[10px] text-slate-500 tracking-wider mt-1 uppercase">
                    Deployment Jaringan
                </p>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                <!-- Dashboard -->
                <Link
                    href="/dashboard"
                    class="sidebar-link"
                    :class="currentPage === 'dashboard' ? 'sidebar-link-active' : ''"
                >
                    <span class="flex items-center gap-3">
                        <LayoutDashboard class="menu-icon" />
                        <span>Dashboard</span>
                    </span>
                </Link>

                <!-- Project (Submenu) -->
                <button
                    type="button"
                    class="sidebar-link w-full"
                    :class="
                        ['project.create', 'project.list', 'project.history'].includes(currentPage)
                            ? 'sidebar-link-active'
                            : ''
                    "
                    @click="isProjectOpen = !isProjectOpen"
                >
                    <span class="flex items-center gap-3">
                        <FolderKanban class="menu-icon" />
                        <span>Project</span>
                    </span>
                    <component 
                        :is="isProjectOpen ? ChevronDown : ChevronRight" 
                        class="w-4 h-4 text-slate-500" 
                    />
                </button>

                <div v-show="isProjectOpen" class="ml-4 mt-1 space-y-0.5 pl-4 border-l border-slate-700/50">
                    <Link
                        href="/projects/create"
                        class="sidebar-sublink"
                        :class="currentPage === 'project.create' ? 'sidebar-sublink-active' : ''"
                    >
                        <span class="flex items-center gap-3">
                            <FilePlus class="menu-icon w-4 h-4" />
                            <span>Project Register</span>
                        </span>
                    </Link>

                    <Link
                        href="/projects"
                        class="sidebar-sublink"
                        :class="currentPage === 'project.list' ? 'sidebar-sublink-active' : ''"
                    >
                        <span class="flex items-center gap-3">
                            <ClipboardList class="menu-icon w-4 h-4" />
                            <span>Project Board</span>
                        </span>
                    </Link>

                    <Link
                        href="/projects/history"
                        class="sidebar-sublink"
                        :class="currentPage === 'project.history' ? 'sidebar-sublink-active' : ''"
                    >
                        <span class="flex items-center gap-3">
                            <History class="menu-icon w-4 h-4" />
                            <span>History Project</span>
                        </span>
                    </Link>
                </div>

                <!-- Plan On Desk -->
                <Link
                    href="/projects/plan-on-desk"
                    class="sidebar-link"
                    :class="currentPage === 'project.planOnDesk' ? 'sidebar-link-active' : ''"
                >
                    <span class="flex items-center gap-3">
                        <MapPin class="menu-icon" />
                        <span>Plan On Desk</span>
                    </span>
                </Link>

                <!-- MENU LAIN -->
                <div class="section-divider"></div>
                <label class="section-label">Other</label>

                <Link href="#" class="sidebar-link">
                    <span class="flex items-center gap-3">
                        <FileSearch class="menu-icon" />
                        <span>SPK Survey</span>
                    </span>
                </Link>

                <Link href="#" class="sidebar-link">
                    <span class="flex items-center gap-3">
                        <FileText class="menu-icon" />
                        <span>Survey & DRM</span>
                    </span>
                </Link>

                <Link href="#" class="sidebar-link">
                    <span class="flex items-center gap-3">
                        <Hammer class="menu-icon" />
                        <span>SPK Implementasi</span>
                    </span>
                </Link>

                <Link href="#" class="sidebar-link">
                    <span class="flex items-center gap-3">
                        <Package class="menu-icon" />
                        <span>Procurement (PP)</span>
                    </span>
                </Link>

                <Link href="#" class="sidebar-link">
                    <span class="flex items-center gap-3">
                        <Boxes class="menu-icon" />
                        <span>Material (MOS)</span>
                    </span>
                </Link>

                <Link href="#" class="sidebar-link">
                    <span class="flex items-center gap-3">
                        <Hammer class="menu-icon" />
                        <span>Proses Konstruksi</span>
                    </span>
                </Link>

                <Link href="#" class="sidebar-link">
                    <span class="flex items-center gap-3">
                        <Rocket class="menu-icon" />
                        <span>Go Live</span>
                    </span>
                </Link>

                <Link href="#" class="sidebar-link">
                    <span class="flex items-center gap-3">
                        <Archive class="menu-icon" />
                        <span>Inventory & Asset</span>
                    </span>
                </Link>

                <Link
                    href="/documents"
                    class="sidebar-link"
                    :class="currentPage === 'documents' ? 'sidebar-link-active' : ''"
                >
                    <span class="flex items-center gap-3">
                        <FileText class="menu-icon" />
                        <span>Documents</span>
                    </span>
                </Link>

                <!-- SECTION: APPROVALS -->
                <div class="section-divider">
                    <span class="section-label">Approvals</span>
                </div>

                <Link
                    href="/approval"
                    class="sidebar-link"
                    :class="currentPage === 'approval' ? 'sidebar-link-active' : ''"
                >
                    <span class="flex items-center gap-3">
                        <ClipboardCheck class="menu-icon" />
                        <span>Approvals</span>
                    </span>
                    <span v-if="approvalCount > 0" class="menu-badge">
                        {{ approvalCount }}
                    </span>
                </Link>

                <Link
                    href="/approval/workflows"
                    class="sidebar-link"
                    :class="currentPage === 'approval.workflow.index' ? 'sidebar-link-active' : ''"
                >
                    <span class="flex items-center gap-3">
                        <Workflow class="menu-icon" />
                        <span>Approval Workflow</span>
                    </span>
                </Link>

                <!-- SECTION: MANAGE ROLES -->
                <div v-if="canManageRoles" class="section-divider">
                    <span class="section-label">Roles & Permissions</span>
                </div>

                <div v-if="canManageRoles">
                    <button
                        type="button"
                        class="sidebar-link w-full"
                        :class="
                            ['roles.index', 'permissions.index', 'assign-roles.index', 'assign-permissions.index'].includes(currentPage)
                                ? 'sidebar-link-active'
                                : ''
                        "
                        @click="isRolesOpen = !isRolesOpen"
                    >
                        <span class="flex items-center gap-3">
                            <Shield class="menu-icon" />
                            <span>Manage Roles</span>
                        </span>
                        <component :is="isRolesOpen ? ChevronDown : ChevronRight" class="w-4 h-4 text-slate-500" />
                    </button>

                    <div v-show="isRolesOpen" class="ml-4 mt-1 space-y-0.5 pl-4 border-l border-slate-700/50">
                        <Link
                            href="/roles"
                            class="sidebar-sublink"
                            :class="currentPage === 'roles.index' ? 'sidebar-sublink-active' : ''"
                        >
                            <span class="flex items-center gap-3">
                                <Users class="menu-icon w-4 h-4" />
                                <span>Roles</span>
                            </span>
                        </Link>

                        <Link
                            href="/permissions"
                            class="sidebar-sublink"
                            :class="currentPage === 'permissions.index' ? 'sidebar-sublink-active' : ''"
                        >
                            <span class="flex items-center gap-3">
                                <Key class="menu-icon w-4 h-4" />
                                <span>Permissions</span>
                            </span>
                        </Link>

                        <Link
                            href="/assign-roles"
                            class="sidebar-sublink"
                            :class="currentPage === 'assign-roles.index' ? 'sidebar-sublink-active' : ''"
                        >
                            <span class="flex items-center gap-3">
                                <UserCog class="menu-icon w-4 h-4" />
                                <span>Assign Roles</span>
                            </span>
                        </Link>

                        <Link
                            href="/assign-permissions"
                            class="sidebar-sublink"
                            :class="currentPage === 'assign-permissions.index' ? 'sidebar-sublink-active' : ''"
                        >
                            <span class="flex items-center gap-3">
                                <Key class="menu-icon w-4 h-4" />
                                <span>Assign Permissions</span>
                            </span>
                        </Link>
                    </div>
                </div>

                <!-- SECTION: LOGS -->
                <div v-if="canViewLogsSection" class="section-divider">
                    <span class="section-label">Logs</span>
                </div>

                <Link
                    v-if="can('audit_logs.view')"
                    href="/audit-logs"
                    class="sidebar-link"
                    :class="props.currentPage === 'audit-logs' ? 'sidebar-link-active' : ''"
                >
                    <span class="flex items-center gap-3">
                        <ScrollText class="menu-icon" />
                        <span>Audit Logs</span>
                    </span>
                </Link>

                <Link
                    v-if="can('logs_monitor.view')"
                    href="/logs/monitor"
                    class="sidebar-link"
                    :class="props.currentPage === 'logs-monitor' ? 'sidebar-link-active' : ''"
                >
                    <span class="flex items-center gap-3">
                        <Activity class="menu-icon" />
                        <span>Logs Monitor</span>
                    </span>
                </Link>

            </nav>
        </aside>
    </div>
</template>

<style scoped>
/* Additional scoped styles if needed */
</style>
