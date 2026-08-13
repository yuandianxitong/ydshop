<template>
  <div class="config-panel">
    <div class="config-section">图片设置</div>
    <div class="config-hint">建议图片尺寸 750x360px，拖拽可排序</div>
    <div
      v-for="(item, i) in localItems"
      :key="i"
      class="config-card"
      draggable="true"
      @dragstart="onDragStart(i, $event)"
      @dragover.prevent="onDragOver(i)"
      @drop="onDrop(i)"
      @dragend="dragIdx = null"
    >
      <span class="config-card__close" @click="removeItem(i)">&times;</span>
      <span class="config-card__drag">&#x2807;</span>
      <ImageSelect v-model="item.image" @update:model-value="emitUpdate" />
      <div class="config-card__body">
        <el-input v-model="item.title" placeholder="标题（可选）" @change="emitUpdate" />
        <LinkPicker v-model="item.url" @update:model-value="emitUpdate" />
      </div>
    </div>
    <el-button type="primary" class="config-add-btn" @click="addItem"><i class="i-lucide:plus mr-1" />添加图片</el-button>

    <div class="config-section">播放设置</div>
    <div class="config-row">
      <span class="config-label">自动播放</span>
      <div class="config-control"><el-switch v-model="form.autoplay" @change="emitUpdate" /></div>
    </div>
    <div class="config-row">
      <span class="config-label">间隔(ms)</span>
      <div class="config-control">
        <div class="config-slider-combo">
          <el-slider v-model="form.interval" :min="1000" :max="8000" :step="500" @change="emitUpdate" />
          <el-input-number v-model="form.interval" :min="1000" :step="500" :controls="false" @change="emitUpdate" />
        </div>
      </div>
    </div>
    <div class="config-row">
      <span class="config-label">高度(px)</span>
      <div class="config-control">
        <div class="config-slider-combo">
          <el-slider v-model="form.height" :min="100" :max="600" @change="emitUpdate" />
          <el-input-number v-model="form.height" :min="100" :max="600" :controls="false" @change="emitUpdate" />
        </div>
      </div>
    </div>
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
function addItem() { localItems.value.push({ image: '', url: '', title: '' }); emitUpdate() }
function removeItem(i: number) { localItems.value.splice(i, 1); emitUpdate() }
const dragIdx = ref<number | null>(null)
function onDragStart(i: number, e: DragEvent) { dragIdx.value = i; e.dataTransfer!.effectAllowed = 'move' }
function onDragOver(i: number) { /* allow drop */ }
function onDrop(i: number) {
    if (dragIdx.value === null || dragIdx.value === i) return
    const [item] = localItems.value.splice(dragIdx.value, 1)
    localItems.value.splice(i, 0, item)
    dragIdx.value = null
    emitUpdate()
}
</script>
<style scoped>@import '../config-ui.scss';</style>
