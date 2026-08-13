<template>
  <div class="config-panel">
    <div class="config-row">
      <span class="config-label">展示样式</span>
      <div class="config-control">
        <el-radio-group v-model="form.style" @change="emitUpdate">
          <el-radio value="icon-grid">图标网格</el-radio>
          <el-radio value="scroll">横向滚动</el-radio>
        </el-radio-group>
      </div>
    </div>
    <div class="config-row">
      <span class="config-label">行数</span>
      <div class="config-control"><el-input-number v-model="form.rows" :min="1" :max="3" :controls="false" @change="emitUpdate" /></div>
    </div>
    <div class="config-row">
      <span class="config-label">每行列数</span>
      <div class="config-control"><el-input-number v-model="form.columns" :min="3" :max="6" :controls="false" @change="emitUpdate" /></div>
    </div>
    <div class="config-section">分类项目</div>
    <div v-for="(item, i) in localItems" :key="i" class="config-card" draggable="true" @dragstart="onDragStart(i, $event)" @dragover.prevent @drop="onDrop(i)" @dragend="dragIdx = null">
      <span class="config-card__close" @click="removeItem(i)">&times;</span>
      <ImageSelect v-model="localItems[i].icon" @update:model-value="emitUpdate" />
      <div class="config-card__body">
        <el-input v-model="item.title" placeholder="分类名称" @change="emitUpdate" />
        <LinkPicker v-model="item.link" @update:model-value="emitUpdate" />
      </div>
    </div>
    <el-button type="primary" class="config-add-btn" @click="addItem"><i class="i-lucide:plus mr-1" />添加分类</el-button>
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
function addItem() { localItems.value.push({ icon: '', title: '', link: '' }); emitUpdate() }
function removeItem(i: number) { localItems.value.splice(i, 1); emitUpdate() }
const dragIdx = ref<number | null>(null)
function onDragStart(i: number, e: DragEvent) { dragIdx.value = i; e.dataTransfer!.effectAllowed = 'move' }
function onDrop(i: number) {
    if (dragIdx.value === null || dragIdx.value === i) return
    const [item] = localItems.value.splice(dragIdx.value, 1)
    localItems.value.splice(i, 0, item)
    dragIdx.value = null
    emitUpdate()
}
</script>
<style scoped>@import '../config-ui.scss';</style>
