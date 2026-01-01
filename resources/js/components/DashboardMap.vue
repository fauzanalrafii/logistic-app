<script setup lang="ts">
import { onMounted, ref, watch, toRef } from 'vue'
import { router } from '@inertiajs/vue3'
import 'leaflet/dist/leaflet.css'
import L from 'leaflet'
import { cityCoordinates, provinceCoordinates } from '@/data/coordinates'

const props = defineProps<{
  regionData: { name: string; count: number; province?: string; projects?: any[] }[]
  isDark?: boolean
}>()

const isDark = toRef(props, 'isDark')
const mapContainer = ref<HTMLElement | null>(null)
let map: L.Map | null = null
let currentTileLayer: L.TileLayer | null = null

// Tile layer URLs - using Stadia Maps for dark mode (more colorful)
const LIGHT_TILES = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png'
const DARK_TILES = 'https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png'

onMounted(() => {
  // Expose Inertia navigation helper to window for Leaflet popups
  (window as any).navigateToProject = (id: number) => {
      router.visit(`/projects/project-detail?id=${id}`)
  }
  initMap()
})

// Watch for dark mode changes
watch(isDark, (newVal) => {
  if (map && currentTileLayer) {
    map.removeLayer(currentTileLayer)
    currentTileLayer = L.tileLayer(newVal ? DARK_TILES : LIGHT_TILES, {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
      subdomains: 'abcd',
      maxZoom: 19
    }).addTo(map)
  }
})

function initMap() {
  if (!mapContainer.value) return
  
  // Initialize map centered on Indonesia
  // Disable scrollWheelZoom so page can scroll smoothly
  map = L.map(mapContainer.value, {
    scrollWheelZoom: false,  // Disable scroll zoom - prevents page scroll issues
  }).setView([-2.5, 118.0], 5)

  // Use CartoDB tiles (dark or light based on isDark prop)
  currentTileLayer = L.tileLayer(isDark.value ? DARK_TILES : LIGHT_TILES, {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
    subdomains: 'abcd',
    maxZoom: 19
  }).addTo(map)

  // Enable scroll zoom only when Ctrl key is pressed
  mapContainer.value.addEventListener('wheel', (e: WheelEvent) => {
    if (e.ctrlKey) {
      e.preventDefault()
      map?.scrollWheelZoom.enable()
    } else {
      map?.scrollWheelZoom.disable()
    }
  }, { passive: false })

  updateMarkers()
}

function updateMarkers() {
  if (!map) return
  
  // Clear existing layers (except tiles) if you want robust updates, 
  // but for simplicity we rely on initial load or full reactivity later.
  // Here we just add markers.
  
  const markers: L.Marker[] = []
  
  props.regionData.forEach(item => {
    // Normalize: Remove 'KOTA' or 'KABUPATEN' for better matching if needed, 
    // but for now strict match on UPPERCASE is usually enough if the dict is good.
    // We try direct match first.
    let cityName = item.name.toUpperCase().trim()
    
    // Attempt 1: Direct Match
    let coords = cityCoordinates[cityName]
    
    // Attempt 2: If starts with KOTA ..., remove it
    if (!coords && cityName.startsWith('KOTA ')) {
         const clean = cityName.replace('KOTA ', '').trim()
         coords = cityCoordinates[clean]
    }
    
    // Attempt 3: Fallback to Province
    let isApproximation = false
    if (!coords && item.province) {
        const provName = item.province.toUpperCase().trim()
        coords = provinceCoordinates[provName]
        if (coords) isApproximation = true
    }
    
    if (coords) {
      // Create custom pulsing icon with glow effect
      const size = 14 + Math.min(item.count, 20) // Dynamic size base
      const glowColor = isDark.value ? 'rgba(59, 130, 246, 0.6)' : 'rgba(59, 130, 246, 0.3)'
      const glowSize = isDark.value ? '15px' : '8px'
      
      const customIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `
          <div class="marker-container" style="width: ${size}px; height: ${size}px;">
             <div class="marker-ring ${isDark.value ? 'marker-ring-glow' : ''}" style="background: ${isDark.value ? 'rgba(59, 130, 246, 0.5)' : 'rgba(59, 130, 246, 0.75)'}"></div>
             <div class="marker-dot" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); box-shadow: 0 0 ${glowSize} ${glowColor}, inset 0 1px 2px rgba(255,255,255,0.3);"></div>
          </div>
        `,
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2]
      })

      const marker = L.marker(coords, { icon: customIcon }).addTo(map!)
      markers.push(marker)
      
      // Build Project List HTML
      let projectsHtml = ''
      if (item.projects && item.projects.length > 0) {
         projectsHtml = '<div class="mt-3 border-t border-slate-100 pt-2 text-left space-y-2">'
         item.projects.forEach((proj: any) => {
             projectsHtml += `
               <div class="text-xs">
                  <div onclick="navigateToProject(${proj.id})" class="font-bold text-slate-700 truncate max-w-[150px] hover:text-blue-600 hover:underline block cursor-pointer transition-colors">${proj.name}</div>
                  <div class="text-[10px] text-slate-400 capitalize">${proj.status}</div>
               </div>
             `
         })
         
         if (item.count > 5) {
            const more = item.count - 5
            projectsHtml += `<div class="text-[10px] text-blue-500 font-bold mt-1 text-center">+ ${more} projects more</div>`
         }
         projectsHtml += '</div>'
      }

      marker.bindPopup(`
        <div class="text-center min-w-[150px]">
          <div class="font-bold text-slate-800">${item.name}</div>
          ${isApproximation ? `<div class="text-[10px] text-orange-500 font-semibold mb-1">(${item.province})</div>` : ''}
          <div class="text-slate-600 text-xs text-[10px] uppercase tracking-wider">Total Projects</div>
          <div class="text-blue-600 font-bold text-lg mb-1">${item.count}</div>
          ${projectsHtml}
        </div>
      `)
    } else {
        console.warn('Coordinates not found for city:', cityName)
    }
  })
  
  // Auto-Zoom (Fit Bounds)
  if (markers.length > 0) {
      const group = L.featureGroup(markers);
      map!.fitBounds(group.getBounds().pad(0.2));
  }
}



// Optional: Watch for data changes
watch(() => props.regionData, () => {
   // Re-render markers if data changes dynamically (filtering)
   // For now, simpler implementation is static on mount.
})
</script>

<template>
  <div class="w-full h-full relative z-0">
     <div ref="mapContainer" class="w-full h-full rounded-2xl"></div>

     <!-- Legend Overlay (Cosmetic) -->
     <div :class="[
        'absolute bottom-4 left-4 z-[999] backdrop-blur-sm p-3 rounded-xl shadow-lg border text-xs font-semibold transition-colors duration-300',
        isDark ? 'bg-slate-800/90 border-slate-700 text-slate-300' : 'bg-white/90 border-slate-100/50 text-slate-600'
     ]">
        <div class="flex items-center gap-2">
           <div class="w-3 h-3 rounded-full bg-blue-500 border-2 border-white shadow-sm ring-1 ring-blue-500/30"></div>
           <span>Lokasi Proyek</span>
        </div>
        <div :class="['mt-2 pt-2 border-t text-[10px] font-normal', isDark ? 'border-slate-700 text-slate-500' : 'border-slate-200 text-slate-400']">
           Ctrl + scroll untuk zoom
        </div>
     </div>
  </div>
</template>

<style>
/* Leaflet marker fix for Vite/Webpack */
.leaflet-pane { z-index: 10; }
.leaflet-popup-content-wrapper { @apply rounded-xl border border-slate-100 shadow-xl; }
.leaflet-popup-content { @apply m-4; }

/* Custom Pulse Marker */
.custom-div-icon {
  background: transparent !important;
  border: none !important;
}
.marker-container {
  @apply relative flex items-center justify-center;
}
.marker-dot {
  @apply w-full h-full rounded-full border-2 border-white/80 z-10;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.marker-dot:hover {
  transform: scale(1.2);
}
.marker-ring {
  @apply absolute top-0 left-0 w-full h-full rounded-full opacity-75;
  animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
.marker-ring-glow {
  box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
}

@keyframes pulse-ring {
  0%, 100% {
    transform: scale(1);
    opacity: 0.75;
  }
  50% {
    transform: scale(2);
    opacity: 0;
  }
}
</style>
