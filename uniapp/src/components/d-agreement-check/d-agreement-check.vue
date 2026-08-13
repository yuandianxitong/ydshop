<template>
  <view class="agreement" @tap="toggle">
    <view class="agreement__icon">
      <d-icon
        :name="modelValue ? 'check' : 'check-blank'"
        :color="modelValue ? '#2979ff' : '#cccccc'"
        size="32rpx"
      />
    </view>
    <text class="agreement-text">
      我已阅读并同意
      <text class="link" @tap.stop="openUrl(privacyUrl)">《隐私政策》</text>
      和
      <text class="link" @tap.stop="openUrl(termsUrl)">《用户协议》</text>
    </text>
  </view>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
  modelValue: boolean
  termsUrl?: string
  privacyUrl?: string
}>(), {
  termsUrl: '/modules/agreement/pages/agreement?code=user_agreement',
  privacyUrl: '/modules/agreement/pages/agreement?code=privacy_policy',
})

const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>()

function toggle() {
  emit('update:modelValue', !props.modelValue)
}

function openUrl(url?: string) {
  if (url) {
    uni.navigateTo({ url })
  }
}
</script>

<style lang="scss" scoped>
.agreement {
  display: flex;
  flex-direction: row;
  align-items: center;
  padding: 16rpx 0;
}

.agreement__icon {
  flex-shrink: 0;
  width: 32rpx;
  height: 32rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  /* 小程序自定义组件外层会多一层节点，压掉额外偏移 */
  :deep(.d-icon) {
    display: block;
    vertical-align: top;
  }
}

.agreement-text {
  flex: 1;
  margin-left: 10rpx;
  font-size: 24rpx;
  line-height: 32rpx;
  color: #999999;
}

.link {
  color: #2979ff;
}
</style>
