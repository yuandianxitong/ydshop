<template>
  <div class="config-panel">
    <div class="config-row">
      <span class="config-label">数据来源</span>
      <div class="config-control">
        <el-select v-model="form.source" @change="emitUpdate">
          <el-option value="manual" label="手动选择" />
          <el-option value="category" label="按分类" />
          <el-option value="tag" label="按标签" />
        </el-select>
      </div>
    </div>
    <div v-if="form.source === 'manual'" class="config-row config-row--top">
      <span class="config-label">选择商品</span>
      <div class="config-control"><GoodsPicker v-model="form.goods_ids" @update:model-value="onGoodsChange" /></div>
    </div>
    <div v-if="form.source === 'category'" class="config-row config-row--top">
      <span class="config-label">选择分类</span>
      <div class="config-control"><CategoryPicker v-model="categoryIds" @update:model-value="onCategoryChange" /></div>
    </div>
    <div v-if="form.source === 'tag'" class="config-row">
      <span class="config-label">标签</span>
      <div class="config-control">
        <el-radio-group v-model="form.tag" @change="emitUpdate">
          <el-radio value="hot">热销</el-radio>
          <el-radio value="new">新品</el-radio>
          <el-radio value="recommend">推荐</el-radio>
        </el-radio-group>
      </div>
    </div>
    <div class="config-row">
      <span class="config-label">显示数量</span>
      <div class="config-control">
        <div class="config-slider-combo">
          <el-slider v-model="form.limit" :min="1" :max="20" @change="emitUpdate" />
          <el-input-number v-model="form.limit" :min="1" :max="20" :controls="false" @change="emitUpdate" />
        </div>
      </div>
    </div>
    <div class="config-row">
      <span class="config-label">每行列数</span>
      <div class="config-control">
        <el-radio-group v-model="form.columns" @change="emitUpdate">
          <el-radio-button :value="2">2列</el-radio-button>
          <el-radio-button :value="3">3列</el-radio-button>
          <el-radio-button :value="4">4列</el-radio-button>
        </el-radio-group>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, watch } from 'vue'
import GoodsPicker from '@/components/GoodsPicker/index.vue'
import CategoryPicker from '@/components/CategoryPicker/index.vue'
const props = defineProps<{ modelValue: Record<string, any> }>()
const emit = defineEmits(['update:modelValue'])
const form = ref({ ...props.modelValue })
const categoryIds = ref<number[]>(props.modelValue.category_id ? [props.modelValue.category_id] : [])
watch(() => props.modelValue, v => { form.value = { ...v }; categoryIds.value = v.category_id ? [v.category_id] : [] }, { deep: true })
function emitUpdate() { emit('update:modelValue', { ...form.value }) }
function onGoodsChange(ids: number | number[] | undefined) { form.value.goods_ids = Array.isArray(ids) ? ids : (ids != null ? [ids] : []); emitUpdate() }
function onCategoryChange(ids: number[] | undefined) { form.value.category_id = (ids && ids[0]) || null; emitUpdate() }
</script>
<style scoped>@import '../config-ui.scss';</style>
