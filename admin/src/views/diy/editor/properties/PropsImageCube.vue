<template>
  <div class="config-panel">
    <div class="config-section">网格设置</div>
    <div class="config-row">
      <span class="config-label">行数</span>
      <div class="config-control"><el-input-number v-model="form.rows" :min="1" :max="6" :controls="false" @change="emitUpdate" /></div>
    </div>
    <div class="config-row">
      <span class="config-label">列数</span>
      <div class="config-control"><el-input-number v-model="form.cols" :min="1" :max="6" :controls="false" @change="emitUpdate" /></div>
    </div>
    <div class="config-row">
      <span class="config-label">间距(px)</span>
      <div class="config-control">
        <div class="config-slider-combo">
          <el-slider v-model="form.gap" :min="0" :max="20" @change="emitUpdate" />
          <el-input-number v-model="form.gap" :min="0" :max="20" :controls="false" @change="emitUpdate" />
        </div>
      </div>
    </div>
    <div class="config-row">
      <span class="config-label">圆角(px)</span>
      <div class="config-control">
        <div class="config-slider-combo">
          <el-slider v-model="form.borderRadius" :min="0" :max="20" @change="emitUpdate" />
          <el-input-number v-model="form.borderRadius" :min="0" :max="20" :controls="false" @change="emitUpdate" />
        </div>
      </div>
    </div>
    <div class="config-row">
      <span class="config-label">上外边距(px)</span>
      <div class="config-control">
        <div class="config-slider-combo">
          <el-slider v-model="form.marginTop" :min="0" :max="80" @change="emitUpdate" />
          <el-input-number v-model="form.marginTop" :min="0" :max="80" :controls="false" @change="emitUpdate" />
        </div>
      </div>
    </div>
    <div class="config-row">
      <span class="config-label">下外边距(px)</span>
      <div class="config-control">
        <div class="config-slider-combo">
          <el-slider v-model="form.marginBottom" :min="0" :max="80" @change="emitUpdate" />
          <el-input-number v-model="form.marginBottom" :min="0" :max="80" :controls="false" @change="emitUpdate" />
        </div>
      </div>
    </div>

    <div class="config-section">格子配置</div>
    <div v-for="(item, i) in localItems" :key="i" class="config-card">
      <span class="config-card__close" @click="removeItem(i)">&times;</span>
      <div class="config-card__label">格子 {{ i + 1 }}</div>
      <ImageSelect v-model="localItems[i].image" @update:model-value="emitUpdate" />
      <div class="config-card__body">
        <LinkPicker v-model="item.link" @update:model-value="emitUpdate" />
        <div class="config-grid-pos">
          <div class="config-grid-pos__item">
            <span class="config-grid-pos__label">起始行</span>
            <el-input-number v-model="item.rowStart" :min="1" :max="form.rows || 6" :controls="false" size="small" @change="emitUpdate" />
          </div>
          <div class="config-grid-pos__item">
            <span class="config-grid-pos__label">起始列</span>
            <el-input-number v-model="item.colStart" :min="1" :max="form.cols || 6" :controls="false" size="small" @change="emitUpdate" />
          </div>
          <div class="config-grid-pos__item">
            <span class="config-grid-pos__label">跨行数</span>
            <el-input-number v-model="item.rowSpan" :min="1" :max="form.rows || 6" :controls="false" size="small" @change="emitUpdate" />
          </div>
          <div class="config-grid-pos__item">
            <span class="config-grid-pos__label">跨列数</span>
            <el-input-number v-model="item.colSpan" :min="1" :max="form.cols || 6" :controls="false" size="small" @change="emitUpdate" />
          </div>
        </div>
      </div>
    </div>
    <el-button type="primary" class="config-add-btn" @click="addItem"><i class="i-lucide:plus mr-1" />添加格子</el-button>
  </div>
</template>
<script setup lang="ts">
import { ref, watch } from 'vue'
import ImageSelect from '@/components/ImageSelect/index.vue'
import LinkPicker from '../components/LinkPicker.vue'
const props = defineProps<{ modelValue: Record<string, any> }>()
const emit = defineEmits(['update:modelValue'])
const form = ref({ ...props.modelValue })
const localItems = ref<any[]>([...(props.modelValue.items || [])])
watch(() => props.modelValue, (val) => { form.value = { ...val }; localItems.value = [...(val.items || [])] }, { deep: true })
function emitUpdate() { emit('update:modelValue', { ...form.value, items: localItems.value }) }
function addItem() { localItems.value.push({ image: '', link: '', rowStart: 1, colStart: 1, rowSpan: 1, colSpan: 1 }); emitUpdate() }
function removeItem(i: number) { localItems.value.splice(i, 1); emitUpdate() }
</script>
<style scoped>
@import '../config-ui.scss';
.config-card__label { font-size: 12px; font-weight: 500; color: #333; margin-bottom: 8px; }
.config-grid-pos { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-top: 8px; }
.config-grid-pos__item { display: flex; align-items: center; gap: 4px; }
.config-grid-pos__label { font-size: 11px; color: #999; white-space: nowrap; flex-shrink: 0; }
.config-grid-pos__item :deep(.el-input-number) { width: 100%; }
</style>
