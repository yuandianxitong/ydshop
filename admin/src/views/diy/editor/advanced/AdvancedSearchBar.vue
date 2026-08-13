<template>
  <div class="config-panel">
    <div class="config-row"><span class="config-label">文字对齐</span><div class="config-control"><el-radio-group v-model="form.textAlign" @change="emitUpdate"><el-radio value="left">居左</el-radio><el-radio value="center">居中</el-radio></el-radio-group></div></div>
    <div class="config-row"><span class="config-label">图标颜色</span><div class="config-control"><div class="config-color"><el-color-picker v-model="form.iconColor" @change="emitUpdate" /><span class="config-color__value">{{ form.iconColor }}</span><span class="config-color__reset" @click="form.iconColor = '#c0c4cc'; emitUpdate()">重置</span></div></div></div>
    <div class="config-row"><span class="config-label">输入框高</span><div class="config-control"><div class="config-slider-combo"><el-slider v-model="form.inputHeight" :min="28" :max="50" @change="emitUpdate" /><el-input-number v-model="form.inputHeight" :min="28" :max="50" :controls="false" @change="emitUpdate" /></div></div></div>
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
