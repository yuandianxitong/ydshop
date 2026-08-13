<template>
  <div class="config-panel">
    <div class="config-section">标题样式</div>
    <div class="config-row"><span class="config-label">字号</span><div class="config-control"><div class="config-slider-combo"><el-slider v-model="form.titleSize" :min="12" :max="36" @change="emitUpdate" /><el-input-number v-model="form.titleSize" :min="12" :max="36" :controls="false" @change="emitUpdate" /></div></div></div>
    <div class="config-row"><span class="config-label">颜色</span><div class="config-control"><div class="config-color"><el-color-picker v-model="form.titleColor" @change="emitUpdate" /><span class="config-color__value">{{ form.titleColor }}</span><span class="config-color__reset" @click="form.titleColor = '#333333'; emitUpdate()">重置</span></div></div></div>
    <div class="config-row"><span class="config-label">粗细</span><div class="config-control"><el-radio-group v-model="form.titleWeight" @change="emitUpdate"><el-radio value="normal">常规</el-radio><el-radio value="bold">粗体</el-radio></el-radio-group></div></div>
    <div class="config-section">副标题样式</div>
    <div class="config-row"><span class="config-label">字号</span><div class="config-control"><div class="config-slider-combo"><el-slider v-model="form.subtitleSize" :min="12" :max="24" @change="emitUpdate" /><el-input-number v-model="form.subtitleSize" :min="12" :max="24" :controls="false" @change="emitUpdate" /></div></div></div>
    <div class="config-row"><span class="config-label">颜色</span><div class="config-control"><div class="config-color"><el-color-picker v-model="form.subtitleColor" @change="emitUpdate" /><span class="config-color__value">{{ form.subtitleColor }}</span><span class="config-color__reset" @click="form.subtitleColor = '#999999'; emitUpdate()">重置</span></div></div></div>
    <div class="config-section">查看更多</div>
    <div class="config-row"><span class="config-label">文字</span><div class="config-control"><el-input v-model="form.moreText" placeholder="查看更多" @change="emitUpdate" /></div></div>
    <div class="config-row"><span class="config-label">颜色</span><div class="config-control"><div class="config-color"><el-color-picker v-model="form.moreColor" @change="emitUpdate" /><span class="config-color__value">{{ form.moreColor }}</span><span class="config-color__reset" @click="form.moreColor = '#999999'; emitUpdate()">重置</span></div></div></div>
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
