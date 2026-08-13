<template>
  <d-page :safe-area="true">
    <view class="change-pwd">
      <view class="hint">
        <d-icon name="error-warn" size="32rpx" color="#2979ff" />
        <text class="hint__text">修改成功后将退出登录，请使用新密码重新登录</text>
      </view>

      <view class="form">
        <view class="field">
          <d-icon name="unlock" size="36rpx" color="#909399" />
          <input
            v-model="form.old_password"
            class="field__input"
            :password="!showOld"
            placeholder="请输入当前密码"
            placeholder-class="field__placeholder"
          />
          <text class="field__toggle" @tap="showOld = !showOld">{{ showOld ? '隐藏' : '显示' }}</text>
        </view>

        <view class="field">
          <d-icon name="unlock" size="36rpx" color="#909399" />
          <input
            v-model="form.new_password"
            class="field__input"
            :password="!showNew"
            placeholder="请输入新密码（6-20位）"
            placeholder-class="field__placeholder"
          />
          <text class="field__toggle" @tap="showNew = !showNew">{{ showNew ? '隐藏' : '显示' }}</text>
        </view>

        <view v-if="form.new_password" class="strength">
          <view class="strength__track">
            <view
              class="strength__bar"
              :style="{
                width: newPwdStrength.level === 'weak' ? '33%' : newPwdStrength.level === 'medium' ? '66%' : '100%',
                background: newPwdStrength.color,
              }"
            />
          </view>
          <text class="strength__label" :style="{ color: newPwdStrength.color }">{{ newPwdStrength.label }}</text>
        </view>

        <view class="field">
          <d-icon name="unlock" size="36rpx" color="#909399" />
          <input
            v-model="form.confirm_password"
            class="field__input"
            :password="!showConfirm"
            placeholder="请再次输入新密码"
            placeholder-class="field__placeholder"
          />
          <text class="field__toggle" @tap="showConfirm = !showConfirm">{{ showConfirm ? '隐藏' : '显示' }}</text>
        </view>
      </view>

      <view
        class="submit"
        :class="{ 'submit--loading': loading }"
        @tap="!loading && handleSubmit()"
      >
        <text class="submit__text">{{ loading ? '提交中...' : '确认修改' }}</text>
      </view>
    </view>
  </d-page>
</template>

<script setup lang="ts">
import { reactive, ref, computed } from 'vue'
import { useUser } from '../composables/useUser'
import { isPassword } from '@/utils/validate'

const { loading, changePassword } = useUser()

const showOld = ref(false)
const showNew = ref(false)
const showConfirm = ref(false)

const form = reactive({
  old_password: '',
  new_password: '',
  confirm_password: '',
})

function getPasswordStrength(pwd: string): { level: 'weak' | 'medium' | 'strong'; label: string; color: string } {
  if (pwd.length === 0) return { level: 'weak', label: '', color: '#d4d4d8' }
  if (pwd.length < 8) return { level: 'weak', label: '弱', color: '#ef4444' }
  const hasLower = /[a-z]/.test(pwd)
  const hasUpper = /[A-Z]/.test(pwd)
  const hasDigit = /\d/.test(pwd)
  const hasSymbol = /[^A-Za-z0-9]/.test(pwd)
  const variety = (hasLower || hasUpper ? 1 : 0) + (hasDigit ? 1 : 0) + (hasSymbol ? 1 : 0)
  if (variety >= 3) return { level: 'strong', label: '强', color: '#22c55e' }
  if (pwd.length >= 12 || variety >= 2) return { level: 'medium', label: '中', color: '#f59e0b' }
  return { level: 'weak', label: '弱', color: '#ef4444' }
}

const newPwdStrength = computed(() => getPasswordStrength(form.new_password))

async function handleSubmit() {
  if (!form.old_password) {
    uni.showToast({ title: '请输入当前密码', icon: 'none' })
    return
  }
  if (!isPassword(form.new_password)) {
    uni.showToast({ title: '新密码长度6-20位', icon: 'none' })
    return
  }
  if (form.new_password !== form.confirm_password) {
    uni.showToast({ title: '两次密码输入不一致', icon: 'none' })
    return
  }
  if (form.old_password === form.new_password) {
    uni.showToast({ title: '新密码不能与当前密码相同', icon: 'none' })
    return
  }

  await changePassword({
    old_password: form.old_password,
    new_password: form.new_password,
  })
}
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

/* 约 6px 圆角 */
$radius: 12rpx;

.change-pwd {
  padding-bottom: 40rpx;
}

.hint {
  display: flex;
  align-items: flex-start;
  gap: 12rpx;
  padding: 20rpx 24rpx;
  margin-bottom: 24rpx;
  background: rgba(41, 121, 255, 0.08);
  border-radius: $radius;
  border: 1rpx solid rgba(41, 121, 255, 0.12);

  &__text {
    flex: 1;
    font-size: 24rpx;
    line-height: 1.5;
    color: var(--color-primary, #{$primary-color});
  }
}

.form {
  display: flex;
  flex-direction: column;
  gap: 20rpx;
  margin-bottom: 48rpx;
}

.field {
  display: flex;
  align-items: center;
  height: 96rpx;
  padding: 0 24rpx;
  background: #ffffff;
  border: 1rpx solid $border-color;
  border-radius: $radius;
  box-sizing: border-box;
  transition: border-color 0.2s, box-shadow 0.2s;

  &:focus-within {
    border-color: var(--color-primary, #{$primary-color});
    box-shadow: 0 0 0 2rpx rgba(41, 121, 255, 0.15);
  }

  &__input {
    flex: 1;
    min-width: 0;
    height: 100%;
    margin: 0 20rpx;
    font-size: 28rpx;
    color: var(--color-text, #{$text-color});
    background: transparent;
  }

  &__placeholder {
    color: #c0c4cc;
    font-size: 28rpx;
  }

  &__toggle {
    flex-shrink: 0;
    font-size: 26rpx;
    color: $text-color-secondary;
    padding-left: 8rpx;
  }
}

.strength {
  display: flex;
  align-items: center;
  gap: 16rpx;
  padding: 0 4rpx;
  margin-top: -4rpx;

  &__track {
    flex: 1;
    height: 6rpx;
    background: #f0f0f0;
    border-radius: 3rpx;
    overflow: hidden;
  }

  &__bar {
    height: 100%;
    border-radius: 3rpx;
    transition: width 200ms ease;
  }

  &__label {
    flex-shrink: 0;
    width: 32rpx;
    font-size: 22rpx;
    text-align: center;
  }
}

.submit {
  height: 88rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-primary, #{$primary-color});
  border-radius: $radius;
  box-shadow: 0 8rpx 20rpx -8rpx rgba(41, 121, 255, 0.45);

  &:active {
    opacity: 0.92;
  }

  &--loading {
    opacity: 0.7;
  }

  &__text {
    font-size: 30rpx;
    font-weight: 600;
    color: #ffffff;
    letter-spacing: 2rpx;
  }
}
</style>
