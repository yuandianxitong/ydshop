<template>
  <div class="config-panel">
    <div class="config-row"><span class="config-label">指示器</span><div class="config-control"><el-radio-group v-model="form.indicatorType" @change="emitUpdate"><el-radio value="dot">圆点</el-radio><el-radio value="number">数字</el-radio><el-radio value="bar">条形</el-radio><el-radio value="none">无</el-radio></el-radio-group></div></div>
    <div class="config-row"><span class="config-label">指示位置</span><div class="config-control"><el-radio-group v-model="form.indicatorPosition" @change="emitUpdate"><el-radio value="center">居中</el-radio><el-radio value="left">居左</el-radio><el-radio value="right">居右</el-radio></el-radio-group></div></div>
    <div class="config-row"><span class="config-label">切换动画</span><div class="config-control"><el-radio-group v-model="form.animation" @change="emitUpdate"><el-radio value="slide">滑动</el-radio><el-radio value="fade">淡入淡出</el-radio></el-radio-group></div></div>
    <div class="config-row"><span class="config-label">填充模式</span><div class="config-control"><el-radio-group v-model="form.objectFit" @change="emitUpdate"><el-radio value="cover">填充</el-radio><el-radio value="contain">适应</el-radio></el-radio-group></div></div>
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
