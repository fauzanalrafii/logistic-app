<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { 
  User, 
  Shield, 
  CheckCircle2, 
  Fingerprint, 
  Briefcase,
  ChevronDown,
  MapPin,
  Mail,
  Copy,
  Calendar,
  Monitor,
  Smartphone
} from 'lucide-vue-next'
import { computed, ref } from 'vue'

interface RoleData {
  name: string
  permissions: string[]
}

interface Profile {
  id: number
  user_id: string | null
  name: string
  position: string
  location: string
  email: string
}

interface Session {
  agent: {
    os: string
    browser: string
    icon: string
    raw: string
  }
  ip_address: string
  is_current_device: boolean
  last_active: string
}

const props = defineProps<{
  profile: Profile
  roles: RoleData[]
  permissions: string[]
  sessions: Session[]
}>()

// Initials for Avatar
const initials = computed(() => {
  const name = props.profile.name || 'User'
  const parts = name.trim().split(' ')
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
})

// Avatar Gradient
const avatarClass = computed(() => {
  const classes = [
    'from-blue-500 to-indigo-600 shadow-blue-200',
    'from-emerald-500 to-teal-600 shadow-emerald-200',
    'from-orange-500 to-amber-600 shadow-orange-200',
    'from-purple-500 to-fuchsia-600 shadow-purple-200',
    'from-rose-500 to-pink-600 shadow-rose-200',
  ]
  return classes[props.profile.name.length % classes.length]
})

// Expanded state for roles
const expandedRoles = ref<Record<string, boolean>>({})

// Initialize expanded state
if (props.roles.length === 1) {
  expandedRoles.value[props.roles[0].name] = true
}

function toggleRole(roleName: string) {
  expandedRoles.value[roleName] = !expandedRoles.value[roleName]
}

// Helpers
function formatRoleName(role: string): string {
  return role
    .replace(/_/g, ' ')
    .toLowerCase()
    .replace(/\b\w/g, c => c.toUpperCase())
}

function groupRolePermissions(perms: string[]) {
   const groups: Record<string, string[]> = {}
   perms.forEach(p => {
     const parts = p.split('.')
     const category = parts[0] || 'General'
     if(!groups[category]) groups[category] = []
     groups[category].push(p)
   })
   return groups
}

// Copy to clipboard helper
const copied = ref(false)
function copyToClipboard(text: string) {
    navigator.clipboard.writeText(text)
    copied.value = true
    setTimeout(() => copied.value = false, 2000)
}
</script>

<template>
  <Head>
    <title>Account Profile</title>
  </Head>

  <AppLayout title="Accounts" currentPage="profile">
    <div class="max-w-6xl mx-auto pb-12">
      
      <!-- Page Header -->
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Profil Saya</h1>
        <p class="text-gray-500 text-sm mt-1">Kelola informasi pribadi dan hak akses Anda.</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Column: Profile Card (Sticky) -->
        <div class="lg:col-span-4 lg:sticky lg:top-8 space-y-6">
            
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <!-- Cover Banner -->
                <div class="h-32 bg-gray-50 border-b border-gray-100 relative">
                    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] [background-size:16px_16px]"></div>
                </div>

                <div class="px-6 pb-6 relative">
                    <!-- Avatar -->
                    <div class="mb-4 -mt-12 flex justify-between items-end">
                        <div 
                            class="h-24 w-24 rounded-2xl bg-gradient-to-br flex items-center justify-center text-3xl font-bold text-white shadow-lg ring-4 ring-white relative z-10"
                            :class="avatarClass"
                        >
                            {{ initials }}
                            <!-- Online Status Dot -->
                            <div class="absolute -bottom-1 -right-1 h-5 w-5 bg-green-500 rounded-full border-4 border-white"></div>
                        </div>
                    </div>

                    <!-- Identity -->
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 leading-tight">{{ profile.name }}</h2>
                        <div class="flex items-center gap-2 text-gray-500 text-sm mt-1 font-medium">
                            <Briefcase class="w-3.5 h-3.5" />
                            {{ profile.position }}
                        </div>
                        <div class="flex items-center gap-2 text-gray-500 text-sm mt-0.5">
                            <MapPin class="w-3.5 h-3.5" />
                            {{ profile.location }}
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-100 space-y-4">
                        <!-- Email -->
                        <div class="flex items-start gap-3 group">
                            <div class="p-2 rounded-lg bg-gray-50 text-gray-400 group-hover:text-blue-500 group-hover:bg-blue-50 transition-colors">
                                <Mail class="w-4 h-4" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Alamat Email</p>
                                <p v-if="profile.email && profile.email !== '-'" class="text-sm font-medium text-gray-900 truncate" :title="profile.email">
                                    {{ profile.email }}
                                </p>
                                <p v-else class="text-sm text-gray-400 italic">Tidak tersedia</p>
                            </div>
                        </div>

                        <!-- Username -->
                        <div class="flex items-start gap-3 group">
                            <div class="p-2 rounded-lg bg-gray-50 text-gray-400 group-hover:text-purple-500 group-hover:bg-purple-50 transition-colors">
                                <User class="w-4 h-4" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Username</p>
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-medium text-gray-900">{{ profile.user_id || 'N/A' }}</p>
                                    <button 
                                        v-if="profile.user_id" 
                                        @click="copyToClipboard(profile.user_id || '')" 
                                        class="text-gray-300 hover:text-gray-500 transition-colors"
                                        title="Salin ID"
                                    >
                                        <CheckCircle2 v-if="copied" class="w-3.5 h-3.5 text-green-500" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ID -->
                         <div class="flex items-start gap-3 group">
                            <div class="p-2 rounded-lg bg-gray-50 text-gray-400 group-hover:text-amber-500 group-hover:bg-amber-50 transition-colors">
                                <Fingerprint class="w-4 h-4" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">ID User</p>
                                <p class="text-sm font-mono text-gray-600">ID #{{ profile.id }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Summary -->
                <div class="bg-gray-50/50 px-6 py-4 border-t border-gray-100 grid grid-cols-2 divide-x divide-gray-100">
                    <div class="text-center">
                        <span class="block text-lg font-bold text-gray-900">{{ roles.length }}</span>
                        <span class="block text-[10px] text-gray-500 uppercase font-medium mt-0.5">Roles</span>
                    </div>
                    <div class="text-center">
                         <span class="block text-lg font-bold text-gray-900">{{ permissions.length }}</span>
                        <span class="block text-[10px] text-gray-500 uppercase font-medium mt-0.5">Permissions</span>
                    </div>
                </div>
              </div>
        </div>

        <!-- Right Column: Access & Roles -->
        <div class="lg:col-span-8 space-y-6">
            
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Manajemen Akses</h2>
                        <p class="text-sm text-gray-500 mt-0.5">Role yang diberikan ke akun Anda beserta permissionnya.</p>
                    </div>
                    <Shield class="w-10 h-10 text-gray-100" />
                </div>

                <div class="p-6">
                     <div v-if="roles.length === 0" class="text-center py-12 rounded-xl border-2 border-dashed border-gray-100 bg-gray-50/30">
                        <div class="bg-gray-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-400">
                             <Shield class="w-6 h-6" />
                        </div>
                        <h3 class="text-sm font-bold text-gray-900">Tidak Ada Role Ditugaskan</h3>
                         <p class="text-xs text-gray-500 mt-1 max-w-xs mx-auto">Akun ini belum memiliki role spesifik. Akses mungkin terbatas.</p>
                     </div>

                     <div v-else class="space-y-4">
                         <div 
                            v-for="(role, idx) in roles" 
                            :key="role.name"
                            class="border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 group"
                            :class="[ expandedRoles[role.name] ? 'ring-2 ring-blue-500/10 border-blue-200 shadow-sm' : 'hover:border-blue-300 hover:shadow-sm' ]"
                         >
                            <button 
                                @click="toggleRole(role.name)"
                                class="w-full flex items-center justify-between p-4 bg-white hover:bg-blue-50/30 transition-colors text-left"
                            >
                                <div class="flex items-center gap-4">
                                    <div 
                                        class="h-10 w-10 rounded-lg flex items-center justify-center font-bold text-lg transition-colors border shadow-sm"
                                        :class="expandedRoles[role.name] 
                                            ? 'bg-blue-50 text-blue-600 border-blue-100' 
                                            : 'bg-white text-gray-400 border-gray-200'"
                                    >
                                        {{ role.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                            {{ formatRoleName(role.name) }}
                                             <span v-if="role.permissions.length > 50" class="text-[10px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-600 border border-amber-100 font-bold">Admin Level</span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-0.5 font-mono">
                                            role_key: {{ role.name }}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-3">
                                    <div class="text-right hidden sm:block">
                                        <div class="text-xs font-semibold text-gray-900">{{ role.permissions.length }}</div>
                                        <div class="text-[10px] text-gray-400">Permissions</div>
                                    </div>
                                    <ChevronDown 
                                        class="w-5 h-5 text-gray-400 transition-transform duration-300"
                                        :class="{ 'rotate-180': expandedRoles[role.name] }"
                                    />
                                </div>
                            </button>

                            <!-- Permissions Drawer -->
                            <div v-if="expandedRoles[role.name]" class="bg-gray-50/50 border-t border-gray-100 p-5 animate-in slide-in-from-top-1">
                                <div v-if="role.permissions.length === 0" class="text-xs text-gray-400 italic text-center py-2">
                                    Tidak ada permission spesifik ditemukan untuk role ini.
                                </div>
                                <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                     <div 
                                        v-for="(perms, category) in groupRolePermissions(role.permissions)" 
                                        :key="category"
                                        class=""
                                     >
                                        <h4 class="text-[10px] font-bold uppercase tracking-wide text-gray-400 mb-2 border-b border-gray-200/50 pb-1">
                                            {{ formatRoleName(category as string) }}
                                        </h4>
                                        <ul class="space-y-1">
                                            <li v-for="p in perms" :key="p" class="flex items-start gap-2 text-xs text-gray-600 group">
                                                <div class="mt-1 w-1 h-1 rounded-full bg-gray-300 group-hover:bg-blue-400 transition-colors"></div>
                                                <span class="break-all">{{ p }}</span>
                                            </li>
                                        </ul>
                                     </div>
                                </div>
                            </div>
                         </div>
                     </div>
                </div>
            </div>

             <!-- Login Activity -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 overflow-hidden">
                 <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gray-100 rounded-lg text-gray-500">
                         <Calendar class="w-5 h-5" />
                    </div>
                    <div>
                         <h3 class="text-sm font-bold text-gray-900">Riwayat Login</h3>
                         <p class="text-xs text-gray-500">Perangkat yang terhubung dengan akun Anda</p>
                    </div>
                 </div>
                 
                 <div v-if="sessions.length === 0" class="text-center text-gray-400 text-xs py-4">
                    Informasi sesi aktif tidak tersedia.
                 </div>

                 <div v-else class="space-y-3">
                     <div 
                        v-for="(session, idx) in sessions" 
                        :key="idx"
                        class="flex items-center justify-between p-3 rounded-xl border transition-colors"
                        :class="session.is_current_device ? 'bg-green-50/50 border-green-100' : 'bg-white border-gray-100 hover:border-gray-200'"
                     >
                         <div class="flex items-center gap-4">
                             <div 
                                class="h-10 w-10 rounded-full flex items-center justify-center text-gray-500 bg-gray-50 border border-gray-100"
                                :class="{ 'text-green-600 bg-green-100 border-green-200': session.is_current_device }"
                             >
                                 <Monitor v-if="session.agent.icon === 'Monitor'" class="w-5 h-5" />
                                 <Smartphone v-else class="w-5 h-5" />
                             </div>
                             <div>
                                 <div class="text-xs font-bold text-gray-900 flex items-center gap-2">
                                     {{ session.agent.os }} • {{ session.agent.browser }}
                                     <span v-if="session.is_current_device" class="text-[9px] px-1.5 py-0.5 rounded bg-green-100 text-green-700 border border-green-200 font-bold uppercase tracking-wide">
                                         Perangkat Ini
                                     </span>
                                 </div>
                                 <div class="text-[10px] text-gray-500 mt-0.5 flex items-center gap-1.5 font-mono">
                                     <span>{{ session.ip_address }}</span>
                                     <span class="text-gray-300">•</span>
                                     <span>Terakhir aktif {{ session.last_active }}</span>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
            </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
