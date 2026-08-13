<template>
  <div class="image-select">
    <!-- 单图模式 -->
    <template v-if="!multiple">
      <div class="image-select-single" @click="openPicker">
        <template v-if="modelValue">
          <img :src="appStore.getImageUrl(modelValue as string)" class="preview-img" alt="" />
          <div class="remove-btn" @click.stop="handleRemove(0)">
            <i class="i-svg:x" />
          </div>
        </template>
        <template v-else>
          <i class="i-lucide:plus add-icon" />
        </template>
      </div>
    </template>

    <!-- 多图模式 -->
    <template v-else>
      <div class="image-select-multi">
        <div
          v-for="(url, idx) in (modelValue as string[])"
          :key="idx"
          class="image-select-item"
        >
          <img :src="appStore.getImageUrl(url)" class="preview-img" alt="" />
          <div class="remove-btn" @click.stop="handleRemove(idx)">
            <i class="i-svg:x" />
          </div>
        </div>
        <div
          v-if="!limit || (modelValue as string[]).length < limit"
          class="image-select-add"
          @click="openPicker"
        >
          <i class="i-lucide:plus add-icon" />
        </div>
      </div>
    </template>
  </div>

  <!-- 使用 Teleport 将弹窗渲染到 body，避免在 table 等嵌套容器中的 z-index 问题 -->
  <Teleport to="body">
    <MaterialPicker
      v-model="pickerVisible"
      :multiple="multiple"
      :limit="pickerLimit"
      @confirm="handleConfirm"
    />
  </Teleport>
</template>

<script setup lang="ts" name="ImageSelect">
import { ref, computed } from 'vue'
import { useAppStore } from '@/store'
import MaterialPicker from '@/components/MaterialPicker/index.vue'

interface Props {
  modelValue: string | string[]
  multiple?: boolean
  limit?: number
}

interface Emits {
  (e: 'update:modelValue', value: string | string[]): void
}

const props = withDefaults(defineProps<Props>(), {
  multiple: false,
  limit: 0,
})
const emit = defineEmits<Emits>()

const appStore = useAppStore()
const pickerVisible = ref(false)

// For multi mode, limit how many more can be selected
const pickerLimit = computed(() => {
  if (!props.multiple || !props.limit) return props.limit
  const current = Array.isArray(props.modelValue) ? props.modelValue.length : 0
  return Math.max(props.limit - current, 0)
})

const openPicker = () => {
  pickerVisible.value = true
}

const handleConfirm = (urls: string[]) => {
  if (!props.multiple) {
    emit('update:modelValue', urls[0] || '')
  } else {
    const current = Array.isArray(props.modelValue) ? [...props.modelValue] : []
    const merged = [...current, ...urls]
    emit('update:modelValue', props.limit ? merged.slice(0, props.limit) : merged)
  }
}

const handleRemove = (idx: number) => {
  if (!props.multiple) {
    emit('update:modelValue', '')
  } else {
    const arr = [...(props.modelValue as string[])]
    arr.splice(idx, 1)
    emit('update:modelValue', arr)
  }
}
</script>

<style lang="scss" scoped>
.image-select-single,
.image-select-item,
.image-select-add {
  width: 80px;
  height: 80px;
  border: 1px dashed var(--el-border-color);
  border-radius: 6px;
  cursor: pointer;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  transition: border-color 0.2s;

  &:hover {
    border-color: var(--el-color-primary);
  }

  .preview-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  .remove-btn {
    position: absolute;
    top: 0;
    right: 0;
    width: 20px;
    height: 20px;
    background: rgba(0, 0, 0, 0.5);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-bottom-left-radius: 4px;
    font-size: 12px;
    opacity: 0;
    transition: opacity 0.15s;
  }

  &:hover .remove-btn {
    opacity: 1;
  }

  .add-icon {
    font-size: 28px;
    color: #8c939d;
  }
}

.image-select-item {
  border-style: solid;
}

.image-select-multi {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
</style>
