<template>
  <div class="config-panel">
    <div class="config-row">
      <span class="config-label">填充模式</span>
      <div class="config-control">
        <el-radio-group v-model="form.objectFit" @change="emitUpdate">
          <el-radio value="contain">适应</el-radio>
          <el-radio value="cover">填充</el-radio>
        </el-radio-group>
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
