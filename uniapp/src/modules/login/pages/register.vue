<template>
  <view class="register-page">
    <!-- Themed header -->
    <view class="register-header" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="register-header__bubble register-header__bubble--1" />
      <view class="register-header__bubble register-header__bubble--2" />
      <view class="register-header__back" @tap="goLogin">
        <d-icon name="arrow-left" size="40rpx" color="#ffffff" />
      </view>
      <view class="page-header">
        <text class="page-title">创建账号</text>
        <text class="page-subtitle">注册后即可使用全部功能</text>
      </view>
    </view>

    <!-- 表单 -->
    <view class="form-area">
        <view class="input-group">
          <d-icon name="user" size="40rpx" color="#c0c4cc" />
          <input
            v-model="form.mobile"
            type="number"
            maxlength="11"
            placeholder="请输入手机号"
            class="uni-input"
            placeholder-class="input-placeholder"
          />
        </view>

        <view class="input-group">
          <d-icon name="unlock" size="40rpx" color="#c0c4cc" />
          <input
            v-model="form.code"
            type="number"
            maxlength="6"
            placeholder="请输入验证码"
            class="uni-input"
            placeholder-class="input-placeholder"
          />
          <view class="sms-btn" :class="{ disabled: countdown > 0 }" @tap="handleSendCode">
            {{ countdown > 0 ? `${countdown}s` : '获取验证码' }}
          </view>
        </view>

        <view class="input-group">
          <d-icon name="unlock" size="40rpx" color="#c0c4cc" />
          <input
            v-model="form.password"
            :password="!showPwd"
            placeholder="请设置密码（6-20位）"
            class="uni-input"
            placeholder-class="input-placeholder"
          />
          <view class="input-suffix" @tap="showPwd = !showPwd">
            <text class="pwd-toggle-text">{{ showPwd ? '隐藏' : '显示' }}</text>
          </view>
        </view>

        <view class="input-group">
          <d-icon name="unlock" size="40rpx" color="#c0c4cc" />
          <input
            v-model="form.confirmPassword"
            :password="!showConfirmPwd"
            placeholder="请再次输入密码"
            class="uni-input"
            placeholder-class="input-placeholder"
          />
          <view class="input-suffix" @tap="showConfirmPwd = !showConfirmPwd">
            <text class="pwd-toggle-text">{{ showConfirmPwd ? '隐藏' : '显示' }}</text>
          </view>
        </view>

        <d-agreement-check v-model="agreed" />

        <view class="submit-btn" :class="{ loading }" @tap="handleRegister">
          <text class="submit-btn__text">{{ loading ? '注册中...' : '立即注册' }}</text>
        </view>
      </view>

      <view class="footer-link">
        <text class="footer-link__text">已有账号？</text>
        <text class="footer-link__action" @tap="goLogin">立即登录</text>
      </view>
    </view>
</template>

<script setup lang="ts">
import { ref, reactive, onUnmounted } from 'vue'
import { authApi } from '@/api/auth'
import { setToken } from '@/utils/auth'
import { isPassword } from '@/utils/validate'
import { useUserStore } from '@/store/user.store'

const userStore = useUserStore()

const statusBarHeight = ref(0)
try {
  statusBarHeight.value = uni.getSystemInfoSync().statusBarHeight || 0
} catch {
  statusBarHeight.value = 44
}

const loading = ref(false)
const agreed = ref(false)
const showPwd = ref(false)
const showConfirmPwd = ref(false)
const countdown = ref(0)
let countdownTimer: ReturnType<typeof setInterval> | null = null

const form = reactive({
  mobile: '',
  code: '',
  password: '',
  confirmPassword: '',
})

function startCountdown() {
  countdown.value = 60
  countdownTimer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      clearInterval(countdownTimer!)
      countdownTimer = null
    }
  }, 1000)
}

async function handleSendCode() {
  if (countdown.value > 0) return
  if (!/^1[3-9]\d{9}$/.test(form.mobile)) {
    uni.showToast({ title: '请输入正确的手机号', icon: 'none' })
    return
  }
  try {
    await authApi.sendSmsCode({ mobile: form.mobile, scene: 'register' })
    uni.showToast({ title: '验证码已发送', icon: 'none' })
    startCountdown()
  } catch {
    // 错误已由请求拦截器处理
  }
}

async function handleRegister() {
  if (!/^1[3-9]\d{9}$/.test(form.mobile)) {
    uni.showToast({ title: '请输入正确的手机号', icon: 'none' })
    return
  }
  if (!form.code) {
    uni.showToast({ title: '请输入验证码', icon: 'none' })
    return
  }
  if (!isPassword(form.password)) {
    uni.showToast({ title: '密码长度6-20位', icon: 'none' })
    return
  }
  if (form.password !== form.confirmPassword) {
    uni.showToast({ title: '两次密码输入不一致', icon: 'none' })
    return
  }
  if (!agreed.value) {
    uni.showToast({ title: '请先同意用户协议', icon: 'none' })
    return
  }

  loading.value = true
  try {
    const result = await authApi.register({
      mobile: form.mobile,
      code: form.code,
      password: form.password,
      password_confirmation: form.confirmPassword,
    })
    if (result.token) {
      setToken(result.token)
      userStore.token = result.token
      userStore.userInfo = (result as any).user_info || (result as any).user || null
      // #ifdef H5
      import('@/utils/wechat-oauth').then(({ bindOaOpenidAfterLogin }) => {
        bindOaOpenidAfterLogin()
      }).catch(() => {})
      // #endif
    }
    uni.showToast({ title: '注册成功' })
    setTimeout(() => {
      uni.reLaunch({ url: '/pages/index/index' })
    }, 1500)
  } finally {
    loading.value = false
  }
}

function goLogin() {
  uni.navigateBack()
}

onUnmounted(() => {
  if (countdownTimer) {
    clearInterval(countdownTimer)
    countdownTimer = null
  }
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.register-page {
  min-height: 100vh;
  background-color: #f7f8fa;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
}

.register-header {
  background: var(--color-primary, #{$primary-color});
  border-radius: 0 0 60rpx 60rpx;
  position: relative;
  overflow: hidden;
  padding-bottom: 60rpx;

  &__bubble {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);

    &--1 {
      width: 260rpx;
      height: 260rpx;
      top: -60rpx;
      right: -40rpx;
    }

    &--2 {
      width: 160rpx;
      height: 160rpx;
      bottom: 20rpx;
      left: -30rpx;
      background: rgba(255, 255, 255, 0.05);
    }
  }

  &__back {
    position: relative;
    z-index: 1;
    padding: 20rpx 32rpx;
  }
}

.page-header {
  padding: 0 40rpx 0;
  position: relative;
  z-index: 1;

  .page-title {
    display: block;
    font-size: 48rpx;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 12rpx;
  }

  .page-subtitle {
    font-size: 28rpx;
    color: rgba(255, 255, 255, 0.75);
  }
}

.form-area {
  background-color: #ffffff;
  border-radius: 24rpx;
  padding: 36rpx 28rpx;
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.06);
  margin: -30rpx 32rpx 0;
  position: relative;
  z-index: 1;
}

.input-group {
  display: flex;
  align-items: center;
  margin-bottom: 24rpx;
  border: none;
  background-color: #f5f7fa;
  border-radius: 16rpx;
  padding: 0 28rpx;
  height: 96rpx;
  transition: all 0.2s;

  &:focus-within {
    box-shadow: 0 0 0 1px var(--color-primary, #{$primary-color});
    background-color: #fff;
  }

  .uni-input {
    flex: 1;
    height: 100%;
    font-size: 30rpx;
    color: var(--color-text, #{$text-color});
    background: transparent;
  }

  .input-suffix {
    padding-left: 20rpx;
  }

  .pwd-toggle-text {
    font-size: 28rpx;
    color: $text-color-secondary;
  }

  .sms-btn {
    font-size: 28rpx;
    color: var(--color-primary, #{$primary-color});
    font-weight: 500;
    white-space: nowrap;

    &.disabled {
      color: $text-color-secondary;
    }
  }
}

.input-placeholder {
  color: #c0c4cc;
  font-size: 30rpx;
}

.submit-btn {
  margin-top: 36rpx;
  height: 96rpx;
  background-image: linear-gradient(45deg, $primary-color, lighten($primary-color, 10%));
  border-radius: 48rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 10rpx 20rpx -10rpx var(--color-primary, #{$primary-color});
  transition: all 0.3s ease;

  &:active {
    transform: translateY(2rpx);
    box-shadow: 0 5rpx 10rpx -5rpx var(--color-primary, #{$primary-color});
  }

  &.loading {
    opacity: 0.7;
  }

  &__text {
    font-size: 32rpx;
    font-weight: 600;
    color: #ffffff;
    letter-spacing: 2rpx;
  }
}

.footer-link {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: auto;
  padding: 40rpx 0;
  padding-bottom: 60rpx;
  position: relative;
  z-index: 1;

  &__text {
    font-size: 28rpx;
    color: $text-color-secondary;
  }

  &__action {
    font-size: 28rpx;
    color: var(--color-primary, #{$primary-color});
    margin-left: 12rpx;
    font-weight: 500;
  }
}
</style>
