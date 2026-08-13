<template>
  <div class="config-panel">
    <div class="config-row config-row--top"><span class="config-label">展示信息</span><div class="config-control"><el-checkbox v-model="form.showCountdown" @change="emitUpdate">倒计时</el-checkbox><el-checkbox v-model="form.showProgress" @change="emitUpdate">进度条</el-checkbox></div></div>
    <div class="config-row"><span class="config-label">倒计时样式</span><div class="config-control"><el-radio-group v-model="form.countdownStyle" @change="emitUpdate"><el-radio value="standard">标准</el-radio><el-radio value="flip">翻牌</el-radio></el-radio-group></div></div>
    <div class="config-row"><span class="config-label">主题色</span><div class="config-control"><div class="config-color"><el-color-picker v-model="form.themeColor" @change="emitUpdate" /><span class="config-color__value">{{ form.themeColor }}</span><span class="config-color__reset" @click="form.themeColor = '#ff4d4f'; emitUpdate()">重置</span></div></div></div>
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
