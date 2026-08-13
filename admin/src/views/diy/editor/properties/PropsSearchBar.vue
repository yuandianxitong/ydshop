<template>
  <div class="config-panel">
    <div class="config-row">
      <span class="config-label">占位文字</span>
      <div class="config-control"><el-input v-model="form.placeholder" @change="emitUpdate" /></div>
    </div>
    <div class="config-row">
      <span class="config-label">圆角</span>
      <div class="config-control">
        <div class="config-slider-combo">
          <el-slider v-model="form.border_radius" :min="0" :max="50" @change="emitUpdate" />
          <el-input-number v-model="form.border_radius" :min="0" :max="50" :controls="false" @change="emitUpdate" />
        </div>
      </div>
    </div>
    <div class="config-row">
      <span class="config-label">背景色</span>
      <div class="config-control">
        <div class="config-color">
          <el-color-picker v-model="form.bg_color" @change="emitUpdate" />
          <span class="config-color__value">{{ form.bg_color }}</span>
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
watch(() => props.modelValue, v => { form.value = { ...v } }, { deep: true })
function emitUpdate() { emit('update:modelValue', { ...form.value }) }
</script>
<style scoped>@import '../config-ui.scss';</style>
