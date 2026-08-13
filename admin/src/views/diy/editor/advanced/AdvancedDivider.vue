<template>
  <div class="config-panel">
    <div class="config-row"><span class="config-label">线条样式</span><div class="config-control"><el-radio-group v-model="form.lineStyle" @change="emitUpdate"><el-radio value="solid">实线</el-radio><el-radio value="dashed">虚线</el-radio><el-radio value="dotted">点线</el-radio></el-radio-group></div></div>
    <div class="config-row"><span class="config-label">线条粗细</span><div class="config-control"><div class="config-slider-combo"><el-slider v-model="form.lineWidth" :min="1" :max="5" @change="emitUpdate" /><el-input-number v-model="form.lineWidth" :min="1" :max="5" :controls="false" @change="emitUpdate" /></div></div></div>
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
