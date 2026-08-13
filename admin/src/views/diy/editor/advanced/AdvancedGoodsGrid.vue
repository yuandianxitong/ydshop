<template>
  <div class="config-panel">
    <div class="config-row"><span class="config-label">卡片样式</span><div class="config-control"><el-radio-group v-model="form.cardStyle" @change="emitUpdate"><el-radio value="shadow">阴影</el-radio><el-radio value="border">边框</el-radio><el-radio value="none">无</el-radio></el-radio-group></div></div>
    <div class="config-row config-row--top"><span class="config-label">展示信息</span><div class="config-control"><el-checkbox v-model="form.showName" @change="emitUpdate">商品名称</el-checkbox><el-checkbox v-model="form.showPrice" @change="emitUpdate">商品价格</el-checkbox><el-checkbox v-model="form.showBuyBtn" @change="emitUpdate">购买按钮</el-checkbox></div></div>
    <div class="config-row"><span class="config-label">价格颜色</span><div class="config-control"><div class="config-color"><el-color-picker v-model="form.priceColor" @change="emitUpdate" /><span class="config-color__value">{{ form.priceColor }}</span><span class="config-color__reset" @click="form.priceColor = '#ff4d4f'; emitUpdate()">重置</span></div></div></div>
    <div class="config-row"><span class="config-label">按钮文字</span><div class="config-control"><el-input v-model="form.btnText" placeholder="购买" @change="emitUpdate" /></div></div>
    <div class="config-row"><span class="config-label">按钮颜色</span><div class="config-control"><div class="config-color"><el-color-picker v-model="form.btnColor" @change="emitUpdate" /><span class="config-color__value">{{ form.btnColor }}</span><span class="config-color__reset" @click="form.btnColor = '#ff4d4f'; emitUpdate()">重置</span></div></div></div>
    <div class="config-row"><span class="config-label">图片比例</span><div class="config-control"><el-radio-group v-model="form.imageRatio" @change="emitUpdate"><el-radio value="1:1">1:1</el-radio><el-radio value="4:3">4:3</el-radio><el-radio value="16:9">16:9</el-radio></el-radio-group></div></div>
    <div class="config-row"><span class="config-label">列表模式</span><div class="config-control"><el-radio-group v-model="form.listMode" @change="emitUpdate"><el-radio value="grid">网格</el-radio><el-radio value="list">列表</el-radio><el-radio value="waterfall">瀑布流</el-radio></el-radio-group></div></div>
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
