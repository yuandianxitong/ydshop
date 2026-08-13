<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { adPositionApi } from '@/api/marketing/adPosition'
import { adApi } from '@/api/marketing/ad'
import { useAppStore } from '@/store/modules/app.store'

const props = defineProps<{
  position_code?: string
  height?: number
}>()

const appStore = useAppStore()
const ads = ref<any[]>([])
const positionInfo = ref<any | null>(null)

async function loadPreview() {
  ads.value = []
  positionInfo.value = null
  if (!props.position_code) return
  const [posRes, adRes] = await Promise.all([
    adPositionApi.getAll(),
    adApi.getList({ page_no: 1, page_size: 50 }),
  ])
  positionInfo.value = (posRes.data || []).find((p: any) => p.code === props.position_code) || null
  // 位置停用 → 不展示任何广告
  if (!positionInfo.value || positionInfo.value.status !== 1) return
  ads.value = (adRes.data?.list || []).filter((a: any) => a.position_id === positionInfo.value.id && a.is_active === 1)
}

watch(() => props.position_code, loadPreview)
onMounted(loadPreview)
</script>

<template>
  <div
    class="preview-ad-slot"
    :style="{ height: props.height ? `${props.height}px` : 'auto', minHeight: '80px' }"
  >
    <div v-if="!props.position_code" class="preview-empty">请在右侧选择广告位</div>
    <div v-else-if="!positionInfo" class="preview-empty">广告位不存在</div>
    <div v-else-if="positionInfo.status !== 1" class="preview-empty">[{{ positionInfo.name }}] 广告位已停用</div>
    <div v-else-if="!ads.length" class="preview-empty">
      [{{ positionInfo.name }}] 暂无生效广告
    </div>
    <img
      v-else
      :src="appStore.getImageUrl(ads[0].image)"
      style="width:100%;height:100%;object-fit:cover"
    />
  </div>
</template>

<style scoped>
.preview-ad-slot { border: 1px dashed #d0d4de; border-radius: 4px; background: #f8f9fc; }
.preview-empty {
  display: flex; align-items: center; justify-content: center;
  height: 80px; color: #909399; font-size: 12px;
}
</style>
