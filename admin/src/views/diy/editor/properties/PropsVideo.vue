<template>
  <div class="config-panel">
    <div class="config-section">视频设置</div>
    <div class="config-row config-row--top">
      <span class="config-label">视频文件</span>
      <div class="config-control">
        <Upload type="file" :multiple="false" :limit="1" @success="onVideoUpload">
          <el-button type="primary" size="small"><i class="i-svg:upload mr-1" />上传视频</el-button>
        </Upload>
        <div v-if="form.src" class="config-file-name">
          <i class="i-lucide:video" />
          <span>{{ form.src.split('/').pop() }}</span>
          <i class="i-svg:x config-file-remove" @click="form.src = ''; emitUpdate()" />
        </div>
      </div>
    </div>
    <div class="config-row config-row--top">
      <span class="config-label">封面图</span>
      <div class="config-control">
        <ImageSelect v-model="form.poster" @update:model-value="emitUpdate" />
      </div>
    </div>

    <div class="config-section">播放设置</div>
    <div class="config-row">
      <span class="config-label">宽高比</span>
      <div class="config-control">
        <el-radio-group v-model="form.aspectRatio" @change="emitUpdate">
          <el-radio-button value="16:9">16:9</el-radio-button>
          <el-radio-button value="4:3">4:3</el-radio-button>
          <el-radio-button value="1:1">1:1</el-radio-button>
        </el-radio-group>
      </div>
    </div>
    <div class="config-row">
      <span class="config-label">自动播放</span>
      <div class="config-control"><el-switch v-model="form.autoplay" @change="emitUpdate" /></div>
    </div>
    <div class="config-row">
      <span class="config-label">循环播放</span>
      <div class="config-control"><el-switch v-model="form.loop" @change="emitUpdate" /></div>
    </div>
    <div class="config-row">
      <span class="config-label">静音</span>
      <div class="config-control"><el-switch v-model="form.muted" @change="emitUpdate" /></div>
    </div>
    <div class="config-row">
      <span class="config-label">圆角(px)</span>
      <div class="config-control">
        <div class="config-slider-combo">
          <el-slider v-model="form.borderRadius" :min="0" :max="20" @change="emitUpdate" />
          <el-input-number v-model="form.borderRadius" :min="0" :max="20" :controls="false" @change="emitUpdate" />
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, watch } from 'vue'
import Upload from '@/components/Upload/index.vue'
import ImageSelect from '@/components/ImageSelect/index.vue'
const props = defineProps<{ modelValue: Record<string, any> }>()
const emit = defineEmits(['update:modelValue'])
const form = ref({ ...props.modelValue })
watch(() => props.modelValue, v => { form.value = { ...v } }, { deep: true })
function emitUpdate() { emit('update:modelValue', { ...form.value }) }
function onVideoUpload(response: any) {
  if (response?.data?.url) {
    form.value.src = response.data.url
    emitUpdate()
  }
}
</script>
<style scoped>
@import '../config-ui.scss';
.config-file-name { display: flex; align-items: center; gap: 4px; font-size: 12px; color: #666; margin-top: 8px; background: #f5f7fa; padding: 6px 8px; border-radius: 4px; }
.config-file-name span { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.config-file-remove { cursor: pointer; color: #c0c4cc; }
.config-file-remove:hover { color: #f56c6c; }
</style>
