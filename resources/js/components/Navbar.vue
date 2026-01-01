<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { router, usePage, Link } from "@inertiajs/vue3";
import { useDarkMode } from "@/composables/useDarkMode";
import {
    Menu,
    ChevronDown,
    LogOut,
    User,
    Settings,
    CircleHelp,
    Bell,
    ChevronRight,
    Search,
    Moon,
    Sun,
} from "lucide-vue-next";

defineEmits(["toggleSidebar", "openCommandPalette"]);

defineProps({
    title: { type: String, default: "Velox Logistics" },
});

// Dark Mode
const { isDark, toggle: toggleDarkMode } = useDarkMode();

// Detect Mac OS for keyboard shortcut display
const isMac = computed(() => {
    if (typeof navigator !== 'undefined') {
        return /Mac|iPod|iPhone|iPad/.test(navigator.platform || navigator.userAgent);
    }
    return false;
});

const page = usePage<any>();

// =====================
// USER DROPDOWN
// =====================
const isUserOpen = ref(false);
const toggleUser = () => {
    isUserOpen.value = !isUserOpen.value;
    if (isUserOpen.value) notifOpen.value = false;
};

// =====================
// NOTIF DROPDOWN
// =====================
const notifOpen = ref(false);
function toggleNotif() {
    notifOpen.value = !notifOpen.value;
    if (notifOpen.value) isUserOpen.value = false;
}
function closeNotif() {
    notifOpen.value = false;
}
function closeUser() {
    isUserOpen.value = false;
}

function onClickOutside(e: MouseEvent) {
    const notifEl = document.getElementById("notif-wrap");
    const userEl = document.getElementById("user-wrap");

    if (notifEl && !notifEl.contains(e.target as Node)) notifOpen.value = false;
    if (userEl && !userEl.contains(e.target as Node)) isUserOpen.value = false;
}

onMounted(() => document.addEventListener("click", onClickOutside));
onBeforeUnmount(() => document.removeEventListener("click", onClickOutside));

function fmt(value?: string) {
    if (!value) return "-";
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
    });
}

// =====================
// DATA
// =====================
const currentUser = computed(() => page.props.auth?.user ?? null);

// dari middleware baru (notifications)
const notif = computed(
    () => (page.props.notifications as any) || { count: 0, items: [] }
);
const notifCount = computed(() => Number(notif.value.count ?? 0));
const notifItems = computed(() => notif.value.items ?? []);

// fallback lama kalau belum ada notifications
const approvalCount = computed(() =>
    Number(page.props.approval?.inbox_count ?? 0)
);
const badgeCount = computed(() =>
    notifCount.value > 0 ? notifCount.value : approvalCount.value
);

const logout = () => router.post("/logout");

function initials(title?: string) {
    const t = String(title ?? "").trim();
    if (!t) return "N";
    const parts = t.split(/\s+/).slice(0, 2);
    return parts.map((p) => p[0]?.toUpperCase()).join("") || "N";
}
</script>

<template>
    <header
        class="absolute top-4 left-4 right-4 z-20 h-[72px] rounded-[32px] backdrop-blur-md backdrop-saturate-[1.5] flex items-center justify-between px-6 lg:px-8 transition-all duration-300 transform-gpu will-change-transform"
        :style="isDark ? `
            background: linear-gradient(135deg, rgba(30,41,59,0.8) 0%, rgba(15,23,42,0.9) 50%, rgba(30,41,59,0.85) 100%);
            border: 1.5px solid rgba(100, 116, 139, 0.3);
            box-shadow: 
                0 8px 40px rgba(0, 0, 0, 0.3),
                0 2px 8px rgba(0, 0, 0, 0.2),
                inset 0 0 0 1px rgba(100, 116, 139, 0.1),
                inset 0 2px 20px 0 rgba(100, 116, 139, 0.05);
        ` : `
            background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 50%, rgba(255,255,255,0.03) 100%);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            box-shadow: 
                0 8px 40px rgba(0, 0, 0, 0.08),
                0 2px 8px rgba(0, 0, 0, 0.05),
                inset 0 0 0 1px rgba(255, 255, 255, 0.2),
                inset 0 2px 20px 0 rgba(255, 255, 255, 0.1),
                inset 0 -5px 15px 0 rgba(255, 255, 255, 0.08);
        `"
    >
        <!-- overlay -->
        <div
            v-if="isUserOpen || notifOpen"
            @click="
                () => {
                    closeUser();
                    closeNotif();
                }
            "
            class="fixed inset-0 z-10 cursor-default"
        ></div>

        <!-- left -->
        <div class="flex items-center gap-3">
            <button
                @click="$emit('toggleSidebar')"
                :class="['lg:hidden focus:outline-none transition-colors', isDark ? 'text-slate-300 hover:text-blue-400' : 'text-slate-600 hover:text-blue-600']"
                aria-label="Toggle Sidebar"
            >
                <Menu class="w-6 h-6" />
            </button>

            <h2
                :class="['text-base sm:text-lg font-bold tracking-tight', isDark ? 'text-white' : 'text-slate-900']"
            >
                {{ title }}
            </h2>
        </div>

        <!-- right -->
        <div class="flex items-center gap-2 sm:gap-4">
            <!-- SEARCH BUTTON -->
            <button
                type="button"
                @click="$emit('openCommandPalette')"
                :class="['hidden sm:flex items-center gap-2 px-4 py-2 rounded-full transition-all duration-200', isDark ? 'text-slate-300 hover:text-white' : 'text-slate-600 hover:text-slate-800']"
                :style="isDark ? `
                    background: linear-gradient(135deg, rgba(51,65,85,0.6) 0%, rgba(30,41,59,0.8) 100%);
                    border: 1.5px solid rgba(100, 116, 139, 0.4);
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
                    backdrop-filter: blur(8px);
                ` : `
                    background: linear-gradient(135deg, rgba(255,255,255,0.6) 0%, rgba(255,255,255,0.4) 100%);
                    border: 1.5px solid rgba(255, 255, 255, 0.5);
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08), inset 0 0 0 1px rgba(255, 255, 255, 0.4), inset 0 1px 8px rgba(255, 255, 255, 0.3);
                    backdrop-filter: blur(8px);
                `"
            >
                <Search class="w-4 h-4" />
                <span class="text-sm font-medium">Search...</span>
                <kbd :class="['ml-1 px-1.5 py-0.5 text-[10px] font-bold rounded border', isDark ? 'bg-slate-700 text-slate-300 border-slate-600' : 'bg-white/70 text-slate-600 border-slate-200/60']">{{ isMac ? '⌘K' : 'Ctrl+K' }}</kbd>
            </button>

            <!-- DARK MODE TOGGLE -->
            <button
                type="button"
                @click="toggleDarkMode"
                :class="['w-10 h-10 inline-flex items-center justify-center rounded-full focus:outline-none transition', isDark ? 'text-slate-300 hover:bg-slate-700' : 'text-slate-600 hover:bg-slate-100']"
                :aria-label="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
            >
                <Moon v-if="!isDark" class="w-5 h-5" />
                <Sun v-else class="w-5 h-5 text-amber-400" />
            </button>

            <!-- NOTIFICATION -->
            <div id="notif-wrap" class="relative z-20">
                <button
                    type="button"
                    @click.stop="toggleNotif"
                    :class="['relative w-10 h-10 inline-flex items-center justify-center rounded-full focus:outline-none transition', isDark ? 'text-slate-300 hover:bg-slate-700' : 'text-slate-600 hover:bg-slate-100']"
                    aria-label="Notifications"
                >
                    <Bell class="w-6 h-6" />

                    <!-- badge -->
                    <span
                        v-if="badgeCount > 0"
                        class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-extrabold bg-rose-600 text-white flex items-center justify-center leading-none"
                    >
                        {{ badgeCount > 99 ? "99+" : badgeCount }}
                    </span>
                </button>

                <!-- Dropdown -->
                <div
                    v-if="notifOpen"
                    class="absolute right-0 mt-2 w-[380px] bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden"
                >
                    <!-- header -->
                    <div
                        class="px-4 py-3 border-b border-slate-100 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-2">
                            <div
                                class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 text-blue-700 flex items-center justify-center"
                            >
                                <Bell class="w-5 h-5" />
                            </div>
                            <div>
                                <div
                                    class="text-sm font-extrabold text-slate-900 leading-tight"
                                >
                                    Notifikasi
                                </div>
                                <div
                                    class="text-[11px] text-slate-500 leading-tight"
                                >
                                    {{
                                        badgeCount > 0
                                            ? `${badgeCount} menunggu tindakan`
                                            : "Tidak ada yang menunggu"
                                    }}
                                </div>
                            </div>
                        </div>

                        <Link
                            href="/notifications"
                            class="text-xs font-bold text-blue-600 hover:text-blue-700"
                            @click="closeNotif"
                        >
                            Lihat semua
                        </Link>
                    </div>

                    <!-- body -->
                    <div
                        v-if="!notifItems.length"
                        class="px-4 py-10 text-center"
                    >
                        <div
                            class="mx-auto w-12 h-12 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500"
                        >
                            <Bell class="w-6 h-6" />
                        </div>
                        <div class="mt-3 text-sm font-semibold text-slate-700">
                            Tidak ada notifikasi
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            Kalau ada approval/SLA overdue, akan muncul di sini.
                        </div>
                    </div>

                    <div
                        v-else
                        class="max-h-[360px] overflow-y-auto divide-y divide-slate-100"
                    >
                        <Link
                            v-for="n in notifItems"
                            :key="n.id"
                            :href="n.href"
                            class="group block px-4 py-3 hover:bg-slate-50 transition"
                            @click="closeNotif"
                        >
                            <div class="flex items-start gap-3">
                                <!-- left badge -->
                                <div
                                    class="shrink-0 w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-[11px] font-extrabold text-slate-700 group-hover:bg-blue-50 group-hover:border-blue-200 group-hover:text-blue-700 transition"
                                >
                                    {{ initials(n.title) }}
                                </div>

                                <!-- content -->
                                <div class="min-w-0 flex-1">
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <div
                                                class="text-sm font-extrabold text-slate-900 truncate"
                                            >
                                                {{ n.title }}
                                            </div>

                                            <!-- ✅ pakai subtitle (gabungan desc/meta) -->
                                            <div
                                                class="text-xs text-slate-600 mt-0.5 line-clamp-2"
                                            >
                                                {{ n.subtitle }}
                                            </div>
                                        </div>

                                        <div class="shrink-0 text-right">
                                            <div
                                                class="text-[11px] text-slate-400 whitespace-nowrap"
                                            >
                                                {{ fmt(n.time) }}
                                            </div>
                                            <ChevronRight
                                                class="w-4 h-4 text-slate-300 group-hover:text-blue-500 transition ml-auto mt-1"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- USER MENU -->
            <div id="user-wrap" class="relative z-20">
                <button
                    @click.stop="toggleUser"
                    :class="['cursor-pointer flex items-center gap-3 focus:outline-none pl-3 border-l transition-colors', isDark ? 'border-slate-600' : 'border-gray-200']"
                >
                    <div class="text-right hidden md:block">
                        <div :class="['text-sm font-extrabold', isDark ? 'text-white' : 'text-slate-900']">
                            {{
                                currentUser?.name || currentUser?.Name || "User"
                            }}
                        </div>
                        <div
                            :class="['text-xs font-semibold capitalize', isDark ? 'text-slate-400' : 'text-slate-500']"
                        >
                            {{ currentUser?.role || "-" }}
                        </div>
                    </div>

                    <img
                        :class="['h-9 w-9 rounded-full object-cover border', isDark ? 'border-slate-600' : 'border-gray-200']"
                        :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(
                            currentUser?.name || currentUser?.Name || 'User'
                        )}&background=0D8ABC&color=fff`"
                        :alt="currentUser?.name || currentUser?.Name || 'User'"
                    />

                    <ChevronDown
                        :class="['w-4 h-4 transition-transform duration-200', isDark ? 'text-slate-400' : 'text-slate-600', { 'rotate-180': isUserOpen }]"
                        :stroke-width="3"
                    />
                </button>

                <div
                    v-if="isUserOpen"
                    class="absolute right-0 mt-2 w-60 bg-white rounded-2xl shadow-2xl border border-slate-200 py-2 overflow-hidden"
                >
                    <div
                        class="px-4 pb-3 mb-2 border-b border-slate-100 md:hidden"
                    >
                        <div class="text-sm font-extrabold text-slate-900">
                            {{
                                currentUser?.name || currentUser?.Name || "User"
                            }}
                        </div>
                        <div
                            class="text-xs text-slate-500 font-semibold capitalize"
                        >
                            {{ currentUser?.role || "-" }}
                        </div>
                    </div>

                    <Link
                    href="/profile"
                    @click="isUserOpen = false"
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 flex items-center transition-colors"
                    >
                    <User class="w-4 h-4 mr-2" /> Profil Saya
                    </Link>

                    <Link
                    href="/settings"
                    @click="isUserOpen = false"
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 flex items-center transition-colors"
                    >
                    <Settings class="w-4 h-4 mr-2" /> Settings
                    </Link>

                    <Link
                    href="/help"
                    @click="isUserOpen = false"
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 flex items-center transition-colors"
                    >
                    <CircleHelp class="w-4 h-4 mr-2" /> Bantuan
                    </Link>


                    <div class="border-t border-slate-100 my-1"></div>

                    <button
                        @click="logout"
                        class="w-full text-left px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 flex items-center transition"
                    >
                        <LogOut class="w-4 h-4 mr-2" /> Keluar
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
