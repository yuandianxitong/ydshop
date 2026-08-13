<template>
  <div class="config-panel">
    <div class="config-row">
      <span class="config-label">数据来源</span>
      <div class="config-control">
        <el-select v-model="form.source" @change="emitUpdate">
          <el-option value="all" label="全部文章" />
          <el-option value="category" label="指定分类" />
        </el-select>
      </div>
    </div>
    <div v-if="form.source === 'category'" class="config-row">
      <span class="config-label">选择分类</span>
      <div class="config-control">
        <el-select v-model="form.category_id" placeholder="选择分类" @change="emitUpdate">
          <el-option v-for="cat in categoryOptions" :key="cat.id" :value="cat.id" :label="cat.name" />
        </el-select>
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
      <span class="config-label">布局样式</span>
      <div class="config-control">
        <el-radio-group v-model="form.layout" @change="emitUpdate">
          <el-radio value="left-image">左图右文</el-radio>
          <el-radio value="big-image">大图模式</el-radio>
          <el-radio value="text-only">纯文字</el-radio>
        </el-radio-group>
      </div>
    </div>

    <div class="config-section">显示控制</div>
    <div class="config-row">
      <span class="config-label">显示摘要</span>
      <div class="config-control"><el-switch v-model="form.showSummary" @change="emitUpdate" /></div>
    </div>
    <div class="config-row">
      <span class="config-label">显示阅读量</span>
      <div class="config-control"><el-switch v-model="form.showViewCount" @change="emitUpdate" /></div>
    </div>
    <div class="config-row">
      <span class="config-label">显示日期</span>
      <div class="config-control"><el-switch v-model="form.showDate" @change="emitUpdate" /></div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { articleCategoryApi } from '@/api/article-category'
const props = defineProps<{ modelValue: Record<string, any> }>()
const emit = defineEmits(['update:modelValue'])
const form = ref({ ...props.modelValue })
const categoryOptions = ref<any[]>([])
watch(() => props.modelValue, v => { form.value = { ...v } }, { deep: true })
function emitUpdate() { emit('update:modelValue', { ...form.value }) }
onMounted(async () => {
  try {
    const res = await articleCategoryApi.getOptions()
    categoryOptions.value = (res?.data as any) || []
  } catch { /* ignore */ }
})
</script>
<style scoped>@import '../config-ui.scss';</style>
