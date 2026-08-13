<template>
  <div class="preview-image-cube" :style="gridStyle">
    <template v-if="items && items.length">
      <div v-for="(item, i) in items" :key="i" class="preview-image-cube__cell" :style="cellStyle(item)">
        <img :src="item.image || '/storage/diy-defaults/image-cube.png'" class="preview-image-cube__img" />
      </div>
    </template>
    <div v-else class="preview-image-cube__empty">
      <i class="i-svg:layout-grid text-2xl" />
      <span>图片魔方</span>
    </div>
  </div>
</template>
<script setup lang="ts">
import type { CSSProperties } from 'vue'
import { computed } from 'vue'
const props = defineProps<{ rows?: number; cols?: number; gap?: number; borderRadius?: number; marginTop?: number; marginBottom?: number; items?: any[] }>()

const placeholderColors = [
  'linear-gradient(135deg,#667eea,#764ba2)',
  'linear-gradient(135deg,#f093fb,#f5576c)',
  'linear-gradient(135deg,#4facfe,#00f2fe)',
  'linear-gradient(135deg,#43e97b,#38f9d7)',
  'linear-gradient(135deg,#fa709a,#fee140)',
  'linear-gradient(135deg,#a18cd1,#fbc2eb)',
]

const gridStyle = computed<CSSProperties>(() => {
  const c = props.cols || 3
  const g = props.gap ?? 4
  return {
    display: 'grid',
    gridTemplateColumns: `repeat(${c}, 1fr)`,
    gridAutoRows: 'auto',
    gap: `${g}px`,
    padding: '8px',
    boxSizing: 'border-box',
    borderRadius: `${props.borderRadius || 0}px`,
    marginTop: `${props.marginTop ?? 16}px`,
    marginBottom: `${props.marginBottom ?? 16}px`,
    overflow: 'hidden',
  }
})

function cellStyle(item: any): CSSProperties {
  return {
    gridRow: `${item.rowStart || 'auto'} / span ${item.rowSpan || 1}`,
    gridColumn: `${item.colStart || 'auto'} / span ${item.colSpan || 1}`,
    borderRadius: '4px',
    overflow: 'hidden',
  }
}
</script>
<style scoped>
.preview-image-cube__img { width: 100%; height: auto; display: block; }
.preview-image-cube__placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; font-weight: 600; }
.preview-image-cube__empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; color: #c0c4cc; font-size: 12px; min-height: 120px; background: #f0f2f5; border-radius: 4px; margin: 8px; }
</style>
