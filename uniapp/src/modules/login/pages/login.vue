<template>
  <view class="login-page">
    <!-- Themed header -->
    <view class="login-header" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="login-back" :style="{ top: `calc(${statusBarHeight}px + 24rpx)` }" @tap="handleBack">
        <d-icon name="arrow-left" size="40rpx" color="#ffffff" />
      </view>
      <view class="login-header__bubble login-header__bubble--1" />
      <view class="login-header__bubble login-header__bubble--2" />
      <view class="login-header__bubble login-header__bubble--3" />
      <view class="logo-area">
        <image class="logo" :src="logoUrl || '/static/logo.png'" mode="aspectFit" />
        <text class="app-name">{{ siteName || '元点商城' }}</text>
        <text class="app-slogan">欢迎回来</text>
      </view>
    </view>

    <view class="content-wrap">
      <!-- 表单 -->
      <view class="form-card">
        <view class="tab-bar">
          <view
            class="tab-item"
            :class="{ active: loginType === 'password' }"
            @tap="loginType = 'password'"
          >
            密码登录
          </view>
          <view
            class="tab-item"
            :class="{ active: loginType === 'sms' }"
            @tap="loginType = 'sms'"
          >
            验证码登录
          </view>
        </view>

        <view class="input-group">
          <d-icon name="user" size="40rpx" color="#909399" />
          <input
            v-model="mobile"
            type="number"
            maxlength="11"
            placeholder="请输入手机号"
            class="uni-input"
            placeholder-class="input-placeholder"
          />
        </view>

        <view v-if="loginType === 'password'" class="input-group">
          <d-icon name="unlock" size="40rpx" color="#909399" />
          <input
            v-model="password"
            :password="!showPwd"
            placeholder="请输入密码"
            class="uni-input"
            placeholder-class="input-placeholder"
          />
          <view class="input-suffix" @tap="showPwd = !showPwd">
            <text class="pwd-toggle-text">{{ showPwd ? '隐藏' : '显示' }}</text>
          </view>
        </view>

        <view v-else class="input-group">
          <d-icon name="unlock" size="40rpx" color="#909399" />
          <input
            v-model="smsCode"
            type="number"
            maxlength="6"
            placeholder="请输入验证码"
            class="uni-input"
            placeholder-class="input-placeholder"
          />
          <view
            class="sms-btn"
            :class="{ disabled: countdown > 0 }"
            @tap="handleSendCode"
          >
            {{ countdown > 0 ? `${countdown}s` : '获取验证码' }}
          </view>
        </view>

        <d-agreement-check v-model="agreed" />

        <view class="login-btn" :class="{ loading }" @tap="handleLogin">
          <text class="login-btn__text">{{ loading ? '登录中...' : '登录' }}</text>
        </view>

        <!-- #ifdef MP-WEIXIN -->
        <view class="other-login">
          <view class="divider">
            <view class="divider__line" />
            <text class="divider__text">其他方式登录</text>
            <view class="divider__line" />
          </view>

          <view
            class="wechat-btn"
            :class="{ loading: wechatQuickLoading }"
            @tap="!wechatQuickLoading && handleWechatQuickLogin()"
          >
            <d-icon name="wechat" size="56rpx" color="#07c160" />
          </view>
        </view>
        <!-- #endif -->

        <!-- #ifdef H5 -->
        <view v-if="isWechatBrowser" class="other-login">
          <view class="divider">
            <view class="divider__line" />
            <text class="divider__text">其他方式登录</text>
            <view class="divider__line" />
          </view>

          <view class="phone-bind-btn wechat-oauth-btn" @tap="handleWechatOAuthLogin">
            微信授权登录
          </view>
        </view>
        <!-- #endif -->
      </view>

      <view class="footer-link">
        <text class="footer-link__text">还没有账号？</text>
        <text class="footer-link__action" @tap="goRegister">立即注册</text>
      </view>
    </view>

    <!-- #ifdef MP-WEIXIN -->
    <d-wechat-auth-popup
      v-model="showAuthPopup"
      :show-phone="authPopupShowPhone"
      :phone-display="authPhoneDisplay"
      :default-nickname="profileDefaults.nickname"
      :default-avatar="profileDefaults.avatar ? appStore.getImageUrl(profileDefaults.avatar) : ''"
      :loading="authSubmitting"
      @phone-auth="handleAuthPhoneCode"
      @submit="handleAuthSubmit"
      @close="handleAuthClose"
    />
    <!-- #endif -->
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useAppStore } from '@/store/app.store'
import { useLogin } from '../composables/useLogin'

const appStore = useAppStore()

const {
  loading, loginType, countdown,
  loginByPassword, loginBySms, sendCode,
  wechatQuickLoading, loginByWechatQuick,
  showAuthPopup, authPopupShowPhone, authPhoneDisplay, authSubmitting, profileDefaults,
  handleAuthPhoneCode, handleAuthSubmit, handleAuthClose,
} = useLogin()

const mobile = ref('')
const password = ref('')
const smsCode = ref('')
const agreed = ref(false)
const showPwd = ref(false)

const statusBarHeight = ref(0)
const systemInfo = uni.getSystemInfoSync()
statusBarHeight.value = systemInfo.statusBarHeight || 0

// #ifdef H5
const isWechatBrowser = computed(
  () => typeof navigator !== 'undefined' && navigator.userAgent.toLowerCase().includes('micromessenger')
)
// #endif

const siteName = computed(() => appStore.config.site_name || '')
const logoUrl = computed(() => {
  const logo = appStore.config.site_logo
  return logo ? appStore.getImageUrl(logo) : ''
})

onShow(() => {
  appStore.getConfig()
})

function checkAgreement(): boolean {
  if (!agreed.value) {
    uni.showToast({ title: '请先同意用户协议', icon: 'none' })
    return false
  }
  return true
}

async function handleLogin() {
  if (!checkAgreement()) return
  if (loginType.value === 'password') {
    await loginByPassword(mobile.value, password.value)
  } else {
    await loginBySms(mobile.value, smsCode.value)
  }
}

async function handleSendCode() {
  await sendCode(mobile.value)
}

function goRegister() {
  uni.navigateTo({ url: '/modules/login/pages/register' })
}

function handleBack() {
  const pages = getCurrentPages()
  if (pages.length > 1) {
    uni.navigateBack()
    return
  }
  uni.switchTab({ url: '/pages/index/index' })
}

async function handleWechatQuickLogin() {
  if (!checkAgreement()) return
  await loginByWechatQuick()
}

// #ifdef H5
// 微信内退出登录后自动授权被抑制，这里提供手动入口重新走微信授权
async function handleWechatOAuthLogin() {
  if (!checkAgreement()) return
  const { startWechatOAuth } = await import('@/utils/wechat-oauth')
  await startWechatOAuth()
}
// #endif
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.login-page {
  min-height: 100vh;
  background-color: #f7f8fa;
  display: flex;
  flex-direction: column;
}

.login-header {
  background: var(--color-primary, #{$primary-color});
  border-radius: 0 0 60rpx 60rpx;
  position: relative;
  overflow: hidden;

  &__bubble {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);

    &--1 {
      width: 300rpx;
      height: 300rpx;
      top: -80rpx;
      right: -60rpx;
    }

    &--2 {
      width: 180rpx;
      height: 180rpx;
      top: 100rpx;
      left: -40rpx;
      background: rgba(255, 255, 255, 0.06);
    }

    &--3 {
      width: 120rpx;
      height: 120rpx;
      bottom: 40rpx;
      right: 80rpx;
      background: rgba(255, 255, 255, 0.05);
    }
  }
}

.login-back {
  position: absolute;
  left: 24rpx;
  z-index: 2;
  width: 72rpx;
  height: 72rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.16);
}

.logo-area {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 60rpx 0 80rpx;
  position: relative;
  z-index: 1;

  .logo {
    width: 120rpx;
    height: 120rpx;
    border-radius: 28rpx;
    background-color: rgba(255, 255, 255, 0.2);
    box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.1);
  }

  .app-name {
    font-size: 36rpx;
    font-weight: 700;
    color: #ffffff;
    margin-top: 24rpx;
  }

  .app-slogan {
    font-size: 26rpx;
    color: rgba(255, 255, 255, 0.75);
    margin-top: 8rpx;
  }
}

.content-wrap {
  position: relative;
  z-index: 1;
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 0 32rpx;
  margin-top: -30rpx;
  box-sizing: border-box;
}

.form-card {
  background-color: #ffffff;
  border-radius: 24rpx;
  padding: 40rpx 32rpx;
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.06);
}

.tab-bar {
  display: flex;
  justify-content: center;
  gap: 80rpx;
  margin-bottom: 60rpx;
  border-bottom: none;

  .tab-item {
    padding: 16rpx 0;
    font-size: 30rpx;
    color: $text-color-secondary;
    position: relative;
    font-weight: 500;
    transition: all 0.3s;

    &.active {
      color: var(--color-text, #{$text-color});
      font-weight: 600;
      font-size: 36rpx;

      &::after {
        content: '';
        position: absolute;
        bottom: -10rpx;
        left: 50%;
        transform: translateX(-50%);
        width: 40rpx;
        height: 6rpx;
        background: var(--color-primary, #{$primary-color});
        border-radius: 3rpx;
      }
    }
  }
}

.input-group {
  display: flex;
  align-items: center;
  margin-bottom: 28rpx;
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

.login-btn {
  margin-top: 48rpx;
  height: 100rpx;
  background-image: linear-gradient(45deg, $primary-color, lighten($primary-color, 10%));
  border-radius: 50rpx;
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

.other-login {
  margin-top: 120rpx;
}

.divider {
  display: flex;
  align-items: center;
  margin-bottom: 40rpx;

  &__line {
    flex: 1;
    height: 1rpx;
    background: #e4e7ed;
  }

  &__text {
    font-size: 24rpx;
    color: #909399;
    padding: 0 30rpx;
  }
}

.wechat-btn {
  width: 100rpx;
  height: 100rpx;
  border-radius: 50%;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.1);

  &.loading {
    opacity: 0.5;
  }
}

.phone-bind-btn {
  width: 100%;
  height: 96rpx;
  line-height: 96rpx;
  background: #07c160;
  color: #ffffff;
  border-radius: 48rpx;
  font-size: 30rpx;
  border: none;
  font-weight: 500;
}

.wechat-oauth-btn {
  text-align: center;
}

.footer-link {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: auto;
  padding: 40rpx 0;
  padding-bottom: 60rpx;

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
