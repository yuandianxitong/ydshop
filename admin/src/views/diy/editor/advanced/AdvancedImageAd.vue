<template>
  <div class="config-panel">
    <div class="config-row"><span class="config-label">图片间距</span><div class="config-control"><div class="config-slider-combo"><el-slider v-model="form.imageGap" :min="0" :max="20" @change="emitUpdate" /><el-input-number v-model="form.imageGap" :min="0" :max="20" :controls="false" @change="emitUpdate" /></div></div></div>
    <div class="config-row"><span class="config-label">图片圆角</span><div class="config-control"><div class="config-slider-combo"><el-slider v-model="form.imageRadius" :min="0" :max="30" @change="emitUpdate" /><el-input-number v-model="form.imageRadius" :min="0" :max="30" :controls="false" @change="emitUpdate" /></div></div></div>
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
