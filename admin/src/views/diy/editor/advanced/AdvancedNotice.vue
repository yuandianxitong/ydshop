<template>
  <div class="config-panel">
    <div class="config-row config-row--top">
      <span class="config-label">左侧图标</span>
      <div class="config-control">
        <ImageSelect v-model="form.leftIcon" @update:model-value="emitUpdate" />
        <div v-if="!form.leftIcon" class="config-hint" style="margin-top:4px;margin-bottom:0">默认喇叭图标</div>
      </div>
    </div>
    <div class="config-row"><span class="config-label">文字颜色</span><div class="config-control"><div class="config-color"><el-color-picker v-model="form.textColor" @change="emitUpdate" /><span class="config-color__value">{{ form.textColor }}</span><span class="config-color__reset" @click="form.textColor = '#333333'; emitUpdate()">重置</span></div></div></div>
    <div class="config-row"><span class="config-label">滚动方向</span><div class="config-control"><el-radio-group v-model="form.scrollDirection" @change="emitUpdate"><el-radio value="horizontal">水平</el-radio><el-radio value="vertical">垂直</el-radio></el-radio-group></div></div>
    <div class="config-row"><span class="config-label">关闭按钮</span><div class="config-control"><el-radio-group v-model="form.showClose" @change="emitUpdate"><el-radio :value="true">显示</el-radio><el-radio :value="false">隐藏</el-radio></el-radio-group></div></div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import ImageSelect from '@/components/ImageSelect/index.vue'
const props = defineProps<{ modelValue: Record<string, any> }>()
const emit = defineEmits(['update:modelValue'])
const form = ref({ ...props.modelValue })
watch(() => props.modelValue, v => { form.value = { ...v } }, { deep: true })
function emitUpdate() { emit('update:modelValue', { ...form.value }) }
</script>
<style scoped>@import '../config-ui.scss';</style>
