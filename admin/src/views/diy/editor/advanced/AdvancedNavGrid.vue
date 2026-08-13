<template>
  <div class="config-panel">
    <div class="config-row"><span class="config-label">图标尺寸</span><div class="config-control"><div class="config-slider-combo"><el-slider v-model="form.iconSize" :min="24" :max="60" @change="emitUpdate" /><el-input-number v-model="form.iconSize" :min="24" :max="60" :controls="false" @change="emitUpdate" /></div></div></div>
    <div class="config-row"><span class="config-label">文字大小</span><div class="config-control"><div class="config-slider-combo"><el-slider v-model="form.fontSize" :min="10" :max="16" @change="emitUpdate" /><el-input-number v-model="form.fontSize" :min="10" :max="16" :controls="false" @change="emitUpdate" /></div></div></div>
    <div class="config-row"><span class="config-label">文字颜色</span><div class="config-control"><div class="config-color"><el-color-picker v-model="form.fontColor" @change="emitUpdate" /><span class="config-color__value">{{ form.fontColor }}</span><span class="config-color__reset" @click="form.fontColor = '#333333'; emitUpdate()">重置</span></div></div></div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
const props = defineProps<{ modelValue: Record<string, any> }>()
const emit = defineEmits(['update:modelValue'])
const form = ref({ ...props.modelValue })
watch(() => props.modelValue, v => { form.value = { ...v } }, { deep: true })
function emitUpdate() { emit('update:modelValue', { ...form.value }) }
</script>
<style scoped>@import '../config-ui.scss';</style>
