<template>
  <view class="avatar-upload" @tap="handleUpload">
    <view class="avatar-wrap">
      <image
        v-if="displayUrl"
        class="avatar"
        :src="displayUrl"
        mode="aspectFill"
      />
      <view v-else class="default-avatar">
        <d-icon name="person-circle" size="80rpx" color="#c0c4cc" />
      </view>
      <view class="camera-overlay">
        <d-icon name="image" size="36rpx" color="#ffffff" />
      </view>
    </view>
    <text class="hint">点击更换头像</text>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useUpload } from '@/hooks/useUpload'
import { useAppStore } from '@/store/app.store'

const props = defineProps<{
  modelValue: string
}>()

const appStore = useAppStore()
const displayUrl = computed(() => appStore.getImageUrl(props.modelValue))

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const { chooseAndUpload } = useUpload({ maxSize: 5 })

async function handleUpload() {
  try {
    const url = await chooseAndUpload()
    emit('update:modelValue', url)
  } catch {
    // user cancelled or upload error
  }
}
</script>

<style lang="scss" scoped>
.avatar-upload {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 8rpx 0 0;
}

.avatar-wrap {
  position: relative;
  width: 148rpx;
  height: 148rpx;
  border-radius: 50%;
  overflow: hidden;
  border: 2rpx solid #e8eefc;
  box-shadow: 0 6rpx 16rpx rgba(41, 121, 255, 0.08);

  .avatar {
    width: 100%;
    height: 100%;
  }

  .default-avatar {
    width: 100%;
    height: 100%;
    background: #f2f4f7;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .camera-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 48rpx;
    background: rgba(0, 0, 0, 0.42);
    display: flex;
    align-items: center;
    justify-content: center;
  }
}

.hint {
  margin-top: 16rpx;
  font-size: 24rpx;
  color: #909399;
}
</style>
