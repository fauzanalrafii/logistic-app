<template>
  <div class="bg-white border border-slate-200 rounded-b-lg p-6">
    <h3 class="text-md font-semibold text-slate-900 mb-4">
      Dokumen Proyek 
      <span class="text-sm text-slate-500">({{ documents?.length || 0 }} dokumen)</span>
    </h3>

    <!-- Documents List -->
    <div v-if="documents && documents.length > 0" class="space-y-3">
      <div 
        v-for="doc in documents" 
        :key="doc.id"
        class="flex items-center gap-4 p-4 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 transition-colors"
      >
        <!-- Icon -->
        <div class="text-2xl shrink-0">{{ getDocumentIcon(doc.mime_type) }}</div>

        <!-- File Info -->
        <div class="flex-1 min-w-0">
          <div class="text-sm font-semibold text-slate-900 truncate">{{ doc.file_name }}</div>
          <div class="flex items-center gap-3 mt-1 text-xs text-slate-500">
            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded font-semibold">{{ getDocumentTypeLabel(doc.document_type) }}</span>
            <span>{{ formatFileSize(doc.file_size) }}</span>
            <span>•</span>
            <span>{{ doc.uploaded_by }}</span>
            <span>•</span>
            <span>{{ formatDateTime(doc.uploaded_at) }}</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 shrink-0">
          <a 
            :href="`/documents/${doc.id}/view`"
            target="_blank"
            class="px-3 py-1.5 text-sm bg-white border border-slate-300 text-slate-700 rounded hover:text-blue-700 hover:border-blue-500 transition-colors"
          >
            Lihat
          </a>
          <a 
            :href="`/documents/${doc.id}/download`"
            class="px-3 py-1.5 text-sm bg-white border border-slate-300 text-slate-700 rounded hover:text-blue-700 hover:border-blue-500 transition-colors"
          >
            Download
          </a>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <div class="text-5xl mb-3">📂</div>
      <div class="text-slate-600 font-medium">Belum ada dokumen</div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  documents?: any[]
}>()

const formatDateTime = (value?: string | null) => {
  if (!value) return '-'
  const d = new Date(value)
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const formatFileSize = (bytes: number) => bytes === 0 ? '0 Bytes' : (() => {
  const k = 1024, sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
})()

const getDocumentIcon = (mime: string) => 
  mime.includes('pdf') ? '📄' : mime.includes('word') || mime.includes('document') ? '📝' : 
  mime.includes('sheet') || mime.includes('excel') ? '📊' : mime.includes('image') ? '🖼️' : '📎'

const getDocumentTypeLabel = (type: string) => 
  ({ PROPOSAL: 'Proposal', TECHNICAL: 'Teknis', REPORT: 'Laporan', CONTRACT: 'Kontrak', INVOICE: 'Invoice', OTHER: 'Lainnya' }[type] || type)
</script>
