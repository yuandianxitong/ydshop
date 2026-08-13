<template>
  <div class="config-panel">
    <div class="config-row">
      <span class="config-label">数据来源</span>
      <div class="config-control">
        <el-radio-group v-model="form.source" @change="emitUpdate">
          <el-radio value="auto">自动</el-radio>
          <el-radio value="manual">手动</el-radio>
        </el-radio-group>
      </div>
    </div>
    <template v-if="form.source === 'manual'">
      <div class="config-section">公告内容</div>
      <div v-for="(item, i) in localItems" :key="i" class="config-card" draggable="true" @dragstart="onDragStart(i, $event)" @dragover.prevent @drop="onDrop(i)" @dragend="dragIdx = null">
        <span class="config-card__close" @click="localItems.splice(i,1); emitUpdate()">&times;</span>
        <div class="config-card__body">
          <el-input v-model="item.text" placeholder="公告文字" @change="emitUpdate" />
        </div>
      </div>
      <el-button type="primary" class="config-add-btn" @click="localItems.push({text:'',url:''}); emitUpdate()"><i class="i-lucide:plus mr-1" />添加公告</el-button>
    </template>
    <div class="config-row" style="margin-top:14px">
      <span class="config-label">滚动速度</span>
      <div class="config-control">
        <div class="config-slider-combo">
          <el-slider v-model="form.speed" :min="10" :max="200" @change="emitUpdate" />
          <el-input-number v-model="form.speed" :min="10" :max="200" :controls="false" @change="emitUpdate" />
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, watch } from 'vue'
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
