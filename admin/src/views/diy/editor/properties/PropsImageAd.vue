<template>
  <div class="config-panel">
    <div class="config-row">
      <span class="config-label">布局</span>
      <div class="config-control">
        <el-radio-group v-model="form.layout" @change="emitUpdate">
          <el-radio value="single">单图</el-radio>
          <el-radio value="double">双图</el-radio>
          <el-radio value="triple">三图</el-radio>
        </el-radio-group>
      </div>
    </div>
    <div class="config-section">图片设置</div>
    <div v-for="(item, i) in localItems" :key="i" class="config-card" draggable="true" @dragstart="onDragStart(i, $event)" @dragover.prevent @drop="onDrop(i)" @dragend="dragIdx = null">
      <span class="config-card__close" @click="localItems.splice(i,1); emitUpdate()">&times;</span>
      <span class="config-card__drag">&#x2807;</span>
      <ImageSelect v-model="localItems[i].image" @update:model-value="emitUpdate" />
      <div class="config-card__body">
        <LinkPicker v-model="item.url" @update:model-value="emitUpdate" />
      </div>
    </div>
    <el-button type="primary" class="config-add-btn" @click="localItems.push({image:'',url:''}); emitUpdate()"><i class="i-lucide:plus mr-1" />添加图片</el-button>
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
watch(() => props.modelValue, v => { form.value = { ...v }; localItems.value = [...(v.items || [])] }, { deep: true })
function emitUpdate() { emit('update:modelValue', { ...form.value, items: localItems.value }) }
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
