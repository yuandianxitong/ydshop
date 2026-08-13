<template>
  <div class="config-panel">
    <div class="config-row">
      <span class="config-label">广告位</span>
      <div class="config-control">
        <el-select v-model="form.position_code" placeholder="选择广告位" style="width:100%" @change="emitUpdate">
          <el-option v-for="p in positions" :key="p.id" :label="`${p.name} (${p.code})`" :value="p.code" />
        </el-select>
      </div>
    </div>
    <div v-if="positionHint" class="config-row">
      <span class="config-label"></span>
      <div class="config-control text-xs text-gray-400">{{ positionHint }}</div>
    </div>
    <div class="config-row">
      <span class="config-label">高度 (px)</span>
      <div class="config-control">
        <el-input-number v-model="form.height" :min="0" :max="2000" :step="10" @change="emitUpdate" />
        <span class="text-xs text-gray-400 ml-2">0 = 自适应</span>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue'
import { adPositionApi, type AdPositionInfo } from '@/api/marketing/adPosition'

const props = defineProps<{ modelValue: Record<string, any> }>()
const emit = defineEmits(['update:modelValue'])

const form = ref({ ...props.modelValue })
const positions = ref<AdPositionInfo[]>([])

watch(() => props.modelValue, v => { form.value = { ...v } }, { deep: true })

const currentPosition = computed(() => positions.value.find(p => p.code === form.value.position_code))
const positionHint = computed(() => {
  const p = currentPosition.value
  if (!p || (!p.recommended_width && !p.recommended_height)) return ''
  return `建议尺寸 ${p.recommended_width}×${p.recommended_height}`
})

function emitUpdate() {
  emit('update:modelValue', { ...form.value })
}

onMounted(async () => {
  try {
    const res = await adPositionApi.getAll()
    if (res.code === 200) positions.value = res.data || []
  } catch { /* silent */ }
})
</script>
<style scoped>@import '../config-ui.scss';</style>
