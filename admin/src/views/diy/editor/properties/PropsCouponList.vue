<template>
  <div class="config-panel">
    <div class="config-row">
      <span class="config-label">展示样式</span>
      <div class="config-control">
        <el-radio-group v-model="form.style" @change="emitUpdate">
          <el-radio value="horizontal">横向</el-radio>
          <el-radio value="vertical">竖向</el-radio>
        </el-radio-group>
      </div>
    </div>
    <div class="config-row config-row--top">
      <span class="config-label">优惠券</span>
      <div class="config-control"><CouponPicker v-model="form.coupon_ids" @update:model-value="onCouponChange" /></div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, watch } from 'vue'
import CouponPicker from '../components/CouponPicker.vue'
const props = defineProps<{ modelValue: Record<string, any> }>()
const emit = defineEmits(['update:modelValue'])
const form = ref({ ...props.modelValue })
watch(() => props.modelValue, v => { form.value = { ...v } }, { deep: true })
function emitUpdate() { emit('update:modelValue', { ...form.value }) }
function onCouponChange(ids: number[]) { form.value.coupon_ids = ids; emitUpdate() }
</script>
<style scoped>@import '../config-ui.scss';</style>
