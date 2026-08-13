<template>
  <div class="config-panel">
    <div class="config-row">
      <span class="config-label">类型</span>
      <div class="config-control">
        <el-radio-group v-model="form.type" @change="emitUpdate">
          <el-radio value="blank">空白</el-radio>
          <el-radio value="line">分割线</el-radio>
        </el-radio-group>
      </div>
    </div>
    <div class="config-row">
      <span class="config-label">高度</span>
      <div class="config-control">
        <div class="config-slider-combo">
          <el-slider v-model="form.height" :min="1" :max="100" @change="emitUpdate" />
          <el-input-number v-model="form.height" :min="1" :max="100" :controls="false" @change="emitUpdate" />
        </div>
      </div>
    </div>
    <div v-if="form.type === 'line'" class="config-row">
      <span class="config-label">颜色</span>
      <div class="config-control">
        <div class="config-color">
          <el-color-picker v-model="form.color" @change="emitUpdate" />
          <span class="config-color__value">{{ form.color }}</span>
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
