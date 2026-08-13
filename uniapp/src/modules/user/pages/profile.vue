<template>
  <d-page :safe-area="true">
    <!-- 用户信息卡片 -->
    <view class="user-card" @tap="goEditProfile">
      <view class="avatar-wrap">
        <image
          v-if="avatarUrl"
          class="avatar"
          :src="avatarUrl"
          mode="aspectFill"
        />
        <view v-else class="default-avatar">
          <d-icon name="person-circle" size="64rpx" color="#c0c4cc" />
        </view>
      </view>
      <view class="user-info">
        <text class="nickname">{{ userStore.nickname || '未设置昵称' }}</text>
        <text class="mobile">{{ userInfo?.mobile ? maskMobile(userInfo.mobile) : '' }}</text>
      </view>
      <d-icon name="arrow-right" size="36rpx" color="#cccccc" />
    </view>

    <!-- 用户信息详情 -->
    <view class="info-card">
      <u-cell-group border>
        <u-cell
          title="昵称"
          :value="userInfo?.nickname || '未设置'"
          :is-link="false"
        />
        <u-cell
          title="性别"
          :value="genderText(userInfo?.gender)"
          :is-link="false"
        />
        <u-cell
          title="生日"
          :value="userInfo?.birthday || '未设置'"
          :is-link="false"
        />
        <u-cell
          title="邮箱"
          :value="userInfo?.email || '未设置'"
          :is-link="false"
        />
        <u-cell
          title="手机"
          :value="userInfo?.mobile || '未绑定'"
          :is-link="false"
        />
      </u-cell-group>
    </view>

    <!-- 菜单列表 -->
    <view class="menu-card">
      <u-cell-group>
        <u-cell
          v-if="hasDistribution"
          title="分销中心"
          isLink
          @click="goDistribution"
        >
          <template #icon>
            <d-icon name="share" size="40rpx" color="#f59e0b" style="margin-right: 16rpx" />
          </template>
        </u-cell>
        <u-cell
          title="编辑资料"
          isLink
          @click="goEditProfile"
        >
          <template #icon>
            <d-icon name="user" size="40rpx" color="#2979ff" style="margin-right: 16rpx" />
          </template>
        </u-cell>
        <u-cell
          title="修改密码"
          isLink
          @click="goChangePassword"
        >
          <template #icon>
            <d-icon name="shield" size="40rpx" color="#19be6b" style="margin-right: 16rpx" />
          </template>
        </u-cell>
        <u-cell
          title="设置"
          isLink
          @click="goSettings"
        >
          <template #icon>
            <d-icon name="gear" size="40rpx" color="#ff9900" style="margin-right: 16rpx" />
          </template>
        </u-cell>
        <u-cell
          title="关于"
          isLink
          :value="version"
          @click="goAbout"
        >
          <template #icon>
            <d-icon name="question" size="40rpx" color="#909399" style="margin-right: 16rpx" />
          </template>
        </u-cell>
      </u-cell-group>
    </view>

    <!-- 退出登录 -->
    <view class="logout-area">
      <u-button
        type="error"
        plain
        :customStyle="{ width: '100%' }"
        @click="handleLogout"
        class="logout-btn"
      >
        退出登录
      </u-button>
    </view>
  </d-page>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useUserStore } from '@/store/user.store'
import { useUser } from '../composables/useUser'
import type { UserInfo } from '@/types/api'
import { useAppStore } from '@/store/app.store'

const userStore = useUserStore()
const appStore = useAppStore()
const { loadProfile } = useUser()
const hasDistribution = computed(() => {
  const plugins = appStore.config?.installed_plugins
  return Array.isArray(plugins) && plugins.includes('distribution')
})

const userInfo = ref<UserInfo | null>(null)
const version = ref('v1.0.0')

const avatarUrl = computed(() => {
  return appStore.getImageUrl(userInfo.value?.avatar || '')
})

function maskMobile(mobile: string): string {
  if (!mobile || mobile.length < 11) return mobile
  return mobile.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2')
}

function genderText(g: number | undefined): string {
  if (g === 1) return '男'
  if (g === 2) return '女'
  return '未设置'
}

onShow(async () => {
  try {
    userInfo.value = await loadProfile()
  } catch {
    // not logged in or error
  }
})

function goDistribution() {
  uni.navigateTo({ url: '/modules/distribution/pages/index' })
}

function goEditProfile() {
  uni.navigateTo({ url: '/modules/user/pages/edit-profile' })
}

function goChangePassword() {
  uni.navigateTo({ url: '/modules/user/pages/change-password' })
}

function goSettings() {
  uni.navigateTo({ url: '/modules/user/pages/settings' })
}

function goAbout() {
  uni.showModal({
    title: '关于 Dev007',
    content: '版本: v1.0.0\n一个专业的全栈框架',
    showCancel: false,
  })
}

function handleLogout() {
  uni.showModal({
    title: '提示',
    content: '确认退出登录？',
    success: (res) => {
      if (res.confirm) {
        userStore.logout()
      }
    },
  })
}
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.user-card {
  display: flex;
  align-items: center;
  background: #ffffff;
  border-radius: 24rpx;
  padding: 36rpx 32rpx;
  margin-bottom: 24rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);

  .avatar-wrap {
    width: 120rpx;
    height: 120rpx;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    border: 4rpx solid #f0f4ff;

    .avatar {
      width: 100%;
      height: 100%;
    }

    .default-avatar {
      width: 100%;
      height: 100%;
      background: #e8e8e8;
      display: flex;
      align-items: center;
      justify-content: center;
    }
  }

  .user-info {
    flex: 1;
    margin-left: 24rpx;

    .nickname {
      display: block;
      font-size: 34rpx;
      font-weight: 600;
      color: var(--color-text, #{$text-color});
      margin-bottom: 10rpx;
    }

    .mobile {
      font-size: 26rpx;
      color: $text-color-secondary;
    }
  }
}

.info-card {
  background: #ffffff;
  border-radius: 24rpx;
  overflow: hidden;
  margin-bottom: 24rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
}

.menu-card {
  background: #ffffff;
  border-radius: 24rpx;
  overflow: hidden;
  margin-bottom: 24rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);

}

.logout-area {
  padding: 20rpx 0;

  .logout-btn {
    border-radius: 16rpx !important;
    height: 96rpx !important;
    font-size: 32rpx !important;
  }
}
</style>
