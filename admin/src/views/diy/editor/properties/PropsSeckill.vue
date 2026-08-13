<template>
  <div class="config-panel">
    <div class="config-row config-row--top">
      <span class="config-label">秒杀活动</span>
      <div class="config-control"><SeckillPicker v-model="form.activity_id" @update:model-value="onActivityChange" /></div>
    </div>
    <div class="config-row">
      <span class="config-label">展示数量</span>
      <div class="config-control">
        <div class="config-slider-combo">
          <el-slider v-model="form.limit" :min="1" :max="10" @change="emitUpdate" />
          <el-input-number v-model="form.limit" :min="1" :max="10" :controls="false" @change="emitUpdate" />
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, watch } from 'vue'
import SeckillPicker from '../components/SeckillPicker.vue'
const props = defineProps<{ modelValue: Record<string, any> }>()
const emit = defineEmits(['update:modelValue'])
const form = ref({ ...props.modelValue })
watch(() => props.modelValue, v => { form.value = { ...v } }, { deep: true })
function emitUpdate() { emit('update:modelValue', { ...form.value }) }
function onActivityChange(id: number | null) { form.value.activity_id = id; emitUpdate() }
</script>
<style scoped>@import '../config-ui.scss';</style>
