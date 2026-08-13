<template>
  <view class="settings-page">
    <scroll-view scroll-y class="settings-page__scroll">
      <view class="settings-page__content">
        <!-- 账号 -->
        <view class="section-title">账号</view>
        <u-cell-group border>
          <u-cell title="个人资料" is-link @tap="goPath('/modules/user/pages/edit-profile')" />
          <u-cell title="修改密码" is-link @tap="goPath('/modules/user/pages/change-password')" />
          <u-cell title="收货地址" is-link @tap="goPath('/modules/address/pages/list')" />
        </u-cell-group>

        <!-- 通用 -->
        <view class="section-title">通用</view>
        <u-cell-group border>
          <u-cell title="意见反馈" is-link @tap="goPath('/modules/feedback/pages/feedback')" />
          <u-cell title="清理缓存" :value="cacheSize" is-link @tap="handleClearCache" />
        </u-cell-group>

        <!-- 关于 -->
        <view class="section-title">关于</view>
        <u-cell-group border>
          <u-cell title="关于我们" is-link @tap="goPath('/modules/about/pages/about')" />
          <u-cell title="用户协议" is-link @tap="goPath('/modules/about/pages/agreement')" />
        </u-cell-group>

        <view class="logout-wrap">
          <u-button
            type="error"
            :customStyle="{ width: '100%', height: '96rpx', borderRadius: '16rpx', fontSize: '30rpx' }"
            @click="handleLogout"
          >
            退出登录
          </u-button>
        </view>

        <view style="height: 80rpx" />
      </view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useUserStore } from '@/store/user.store'

const userStore = useUserStore()
const cacheSize = ref('')

function goPath(url: string) {
  uni.navigateTo({ url })
}

function handleClearCache() {
  try {
    uni.clearStorageSync()
    cacheSize.value = ''
    uni.showToast({ title: '已清理', icon: 'success' })
  } catch {
    uni.showToast({ title: '清理失败', icon: 'none' })
  }
}

function handleLogout() {
  uni.showModal({
    title: '确认退出',
    content: '确认退出当前账号？',
    confirmColor: '#ef4444',
    success: (res) => {
      if (!res.confirm) return
      userStore.logout()
    },
  })
}
</script>

<style lang="scss" scoped>
.settings-page {
  min-height: 100vh;
  background: #f5f5f5;
  &__scroll { height: 100vh; }
  &__content { padding-bottom: 40rpx; }
}
.section-title {
  padding: 32rpx 32rpx 16rpx;
  font-size: 24rpx;
  color: #71717a;
}
.logout-wrap {
  margin-top: 48rpx;
  padding: 0 32rpx;
}
</style>
