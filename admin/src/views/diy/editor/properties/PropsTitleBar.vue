<template>
  <div class="config-panel">
    <div class="config-row">
      <span class="config-label">标题</span>
      <div class="config-control"><el-input v-model="form.title" @change="emitUpdate" /></div>
    </div>
    <div class="config-row">
      <span class="config-label">副标题</span>
      <div class="config-control"><el-input v-model="form.subtitle" @change="emitUpdate" /></div>
    </div>
    <div class="config-row">
      <span class="config-label">对齐方式</span>
      <div class="config-control">
        <el-radio-group v-model="form.align" @change="emitUpdate">
          <el-radio value="left">左对齐</el-radio>
          <el-radio value="center">居中</el-radio>
        </el-radio-group>
      </div>
    </div>
    <div class="config-row">
      <span class="config-label">更多链接</span>
      <div class="config-control"><LinkPicker v-model="form.more_url" @update:model-value="emitUpdate" /></div>
    </div>
    <div class="config-row">
      <span class="config-label">更多文字</span>
      <div class="config-control"><el-input v-model="form.more_text" placeholder="如：查看更多" @change="emitUpdate" /></div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, watch } from 'vue'
import LinkPicker from '../components/LinkPicker.vue'
const props = defineProps<{ modelValue: Record<string, any> }>()
const emit = defineEmits(['update:modelValue'])
const form = ref({ ...props.modelValue })
watch(() => props.modelValue, v => { form.value = { ...v } }, { deep: true })
function emitUpdate() { emit('update:modelValue', { ...form.value }) }
</script>
<style scoped>@import '../config-ui.scss';</style>
