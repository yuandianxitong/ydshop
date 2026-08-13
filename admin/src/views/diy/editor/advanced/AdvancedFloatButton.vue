<template>
  <div class="config-panel">
    <div class="config-row"><span class="config-label">按钮尺寸</span><div class="config-control"><div class="config-slider-combo"><el-slider v-model="form.btnSize" :min="40" :max="80" @change="emitUpdate" /><el-input-number v-model="form.btnSize" :min="40" :max="80" :controls="false" @change="emitUpdate" /></div></div></div>
    <div class="config-row"><span class="config-label">默认透明</span><div class="config-control"><div class="config-slider-combo"><el-slider v-model="form.defaultOpacity" :min="0" :max="100" @change="emitUpdate" /><el-input-number v-model="form.defaultOpacity" :min="0" :max="100" :controls="false" @change="emitUpdate" /></div></div></div>
    <div class="config-row"><span class="config-label">悬停透明</span><div class="config-control"><div class="config-slider-combo"><el-slider v-model="form.hoverOpacity" :min="0" :max="100" @change="emitUpdate" /><el-input-number v-model="form.hoverOpacity" :min="0" :max="100" :controls="false" @change="emitUpdate" /></div></div></div>
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
