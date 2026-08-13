<template>
  <div v-if="items && items.length" class="diy-image-cube" :style="gridStyle">
    <div
      v-for="(item, i) in items"
      :key="i"
      class="diy-image-cube__cell"
      :style="cellStyle(item)"
      @click="onClick(item)"
    >
      <img :src="item.image" class="diy-image-cube__img" :alt="item.title || ''" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'

interface CubeItem {
  image: string
  link?: string
  title?: string
  rowStart?: number
  colStart?: number
  rowSpan?: number
  colSpan?: number
}

const props = defineProps<{
  rows?: number
  cols?: number
  gap?: number
  borderRadius?: number
  marginTop?: number
  marginBottom?: number
  items?: CubeItem[]
}>()

const router = useRouter()

const gridStyle = computed(() => ({
    display: 'grid',
    gridTemplateColumns: `repeat(${props.cols || 2}, 1fr)`,
    gridAutoRows: 'auto',
    gap: `${props.gap ?? 8}px`,
    borderRadius: `${props.borderRadius ?? 0}px`,
    marginTop: `${props.marginTop ?? 16}px`,
    marginBottom: `${props.marginBottom ?? 16}px`,
    overflow: 'hidden',
}))

function cellStyle(item: CubeItem) {
    return {
        gridRow: `${item.rowStart || 'auto'} / span ${item.rowSpan || 1}`,
        gridColumn: `${item.colStart || 'auto'} / span ${item.colSpan || 1}`,
        borderRadius: `${props.borderRadius ?? 4}px`,
    }
}

function onClick(item: CubeItem) {
    const link = item.link?.trim()
    if (!link) return
    // 外链开新标签页；内链走 router
    if (/^https?:\/\//i.test(link)) {
        window.open(link, '_blank', 'noopener,noreferrer')
    } else {
        router.push(link)
    }
}
</script>

<style scoped>
.diy-image-cube { width: 100%; }
.diy-image-cube__cell {
    cursor: pointer;
    overflow: hidden;
    transition: transform 0.2s;
}
.diy-image-cube__cell:hover { transform: translateY(-2px); }
.diy-image-cube__img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
}
</style>
