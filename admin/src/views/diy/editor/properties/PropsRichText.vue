<template>
  <div class="config-panel">
    <div class="config-row config-row--top">
      <span class="config-label">内容</span>
      <div class="config-control">
        <el-input v-model="form.content" type="textarea" :rows="8" placeholder="输入HTML内容" @change="emitUpdate" />
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
