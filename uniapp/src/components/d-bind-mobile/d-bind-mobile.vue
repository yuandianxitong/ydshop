<template>
  <u-popup :show="visible" mode="center" round="24rpx" :closeOnClickOverlay="false">
    <view class="d-bind-mobile">
      <view class="d-bind-mobile__header">
        <text class="d-bind-mobile__title">绑定手机号</text>
        <view class="d-bind-mobile__close" @tap="handleClose">
          <d-icon name="close" size="36rpx" color="#71717a" />
        </view>
      </view>

      <text class="d-bind-mobile__tip">绑定后可用手机号登录，并接收订单通知</text>

      <view class="d-bind-mobile__field">
        <u-input
          v-model="mobile"
          type="number"
          :maxlength="11"
          placeholder="请输入手机号"
          clearable
          :customStyle="{ flex: 1 }"
        />
        <u-button
          type="primary"
          size="small"
          :disabled="!isMobileValid || countdown > 0"
          :loading="sendingCode"
          :customStyle="{ marginLeft: '20rpx', flexShrink: 0 }"
          @click="handleSendCode"
        >
          {{ countdown > 0 ? `${countdown}s` : '获取验证码' }}
        </u-button>
      </view>

      <view class="d-bind-mobile__field">
        <u-input
          v-model="code"
          type="number"
          :maxlength="6"
          placeholder="请输入验证码"
          clearable
          :customStyle="{ flex: 1 }"
        />
      </view>

      <u-button
        type="primary"
        shape="circle"
        :loading="binding"
        :disabled="!canSubmit || binding"
        :customStyle="{ width: '100%', marginTop: '20rpx' }"
        @click="handleBind"
      >
        确认绑定
      </u-button>
    </view>
  </u-popup>
</template>

<script setup lang="ts">
import { ref, computed, onBeforeUnmount } from 'vue'
import { authApi } from '@/api/auth'
import { userApi } from '@/api/user'

defineProps<{ visible: boolean }>()

const emit = defineEmits<{
  (e: 'update:visible', v: boolean): void
  (e: 'success', mobile: string): void
}>()

const mobile = ref('')
const code = ref('')
const countdown = ref(0)
const sendingCode = ref(false)
const binding = ref(false)
let timer: ReturnType<typeof setInterval> | null = null

const isMobileValid = computed(() => /^1[3-9]\d{9}$/.test(mobile.value))
const canSubmit = computed(() => isMobileValid.value && code.value.length === 6)

function clearTimer() {
  if (timer) {
    clearInterval(timer)
    timer = null
  }
}

function handleClose() {
  emit('update:visible', false)
}

async function handleSendCode() {
  if (!isMobileValid.value || countdown.value > 0 || sendingCode.value) return
  sendingCode.value = true

  try {
    await authApi.sendSmsCode({ mobile: mobile.value, scene: 'bind_mobile' })
    uni.showToast({ title: '验证码已发送', icon: 'success' })
    countdown.value = 60
    timer = setInterval(() => {
      countdown.value--
      if (countdown.value <= 0) clearTimer()
    }, 1000)
  } catch {
    // 错误提示由请求拦截器统一弹出
  } finally {
    sendingCode.value = false
  }
}

async function handleBind() {
  if (!canSubmit.value || binding.value) return
  binding.value = true

  try {
    await userApi.bindMobile({ mobile: mobile.value, code: code.value })
    uni.showToast({ title: '绑定成功', icon: 'success' })
    emit('success', mobile.value)
    emit('update:visible', false)
    mobile.value = ''
    code.value = ''
  } catch {
    // 手机号被占用等业务错误由请求拦截器提示，保持弹窗打开便于改号重试
  } finally {
    binding.value = false
  }
}

onBeforeUnmount(() => {
  clearTimer()
})
</script>

<style lang="scss" scoped>
.d-bind-mobile {
  width: 620rpx;
  padding: 40rpx;

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__title {
    font-size: 34rpx;
    font-weight: 600;
    color: #18181b;
  }

  &__tip {
    display: block;
    margin: 16rpx 0 32rpx;
    font-size: 24rpx;
    color: #909399;
  }

  &__field {
    display: flex;
    align-items: center;
    margin-bottom: 24rpx;
  }
}
</style>
