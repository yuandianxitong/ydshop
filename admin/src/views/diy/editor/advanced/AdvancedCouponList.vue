<template>
  <div class="config-panel">
    <div class="config-row"><span class="config-label">卡片主色</span><div class="config-control"><div class="config-color"><el-color-picker v-model="form.themeColor" @change="emitUpdate" /><span class="config-color__value">{{ form.themeColor }}</span><span class="config-color__reset" @click="form.themeColor = '#ff4d4f'; emitUpdate()">重置</span></div></div></div>
    <div class="config-row"><span class="config-label">按钮文字</span><div class="config-control"><el-input v-model="form.btnText" placeholder="立即领取" @change="emitUpdate" /></div></div>
    <div class="config-row"><span class="config-label">展示信息</span><div class="config-control"><el-checkbox v-model="form.showCondition" @change="emitUpdate">使用条件</el-checkbox></div></div>
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
