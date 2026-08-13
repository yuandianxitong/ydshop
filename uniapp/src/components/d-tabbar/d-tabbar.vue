<template>
  <view class="d-tabbar" :style="{ background: colors.bg }">
    <view
      v-for="(item, idx) in items"
      :key="idx"
      class="d-tabbar__item"
      :class="{ 'd-tabbar__item--active': currentIdx === idx }"
      @tap="onTap(item, idx)"
    >
      <view class="d-tabbar__icon-wrap">
        <!-- 后台上传图标（URL）→ image；内置图标名（home/category/...）→ d-icon -->
        <image
          v-if="isImageIcon(item)"
          :src="appStore.getImageUrl(currentIdx === idx ? (item.activeIcon || item.icon) : item.icon)"
          class="d-tabbar__img"
        />
        <d-icon
          v-else
          :name="currentIdx === idx ? (item.activeIcon || item.icon) : item.icon"
          size="44rpx"
          :color="iconColor(currentIdx === idx)"
        />
        <view v-if="getBadge(item) > 0" class="d-tabbar__badge">
          {{ getBadge(item) > 99 ? '99+' : getBadge(item) }}
        </view>
      </view>
      <text class="d-tabbar__text" :style="{ color: textColor(currentIdx === idx) }">
        {{ item.text }}
      </text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { tabbarItems as defaultItems } from './tabbar.config'
import { useAppStore } from '@/store/app.store'
import { cartApi } from '@/api/cart'
import { messageApi } from '@/api/message'
import { getToken } from '@/utils/auth'

interface UiTabbarItem {
  text: string
  pagePath: string
  icon: string
  activeIcon?: string
  badgeKey?: 'cart' | 'message'
}

const props = defineProps<{ currentPath: string }>()

const appStore = useAppStore()
const cartBadge = ref(0)
const messageBadge = ref(0)

// pages.json 静态 tabBar 路径（这些可 switchTab，其他用 navigateTo）
const nativeTabPaths = new Set(defaultItems.map((d) => d.pagePath))

// 按 pagePath 查内置默认项（admin 未传 icon 时回退到内置 d-icon 名）
const defaultByPath = new Map(defaultItems.map((d) => [d.pagePath, d]))

// 优先读后台 tabbar_config；空则回退默认。每项 icon 为空时按 pagePath 回退到内置图标
const items = computed<UiTabbarItem[]>(() => {
    const admin = appStore.config?.tabbar_config as Array<Record<string, any>> | undefined
    if (Array.isArray(admin) && admin.length > 0) {
        return admin.map((a) => {
            const path = a.path || ''
            const fallback = defaultByPath.get(path)
            return {
                text: a.name || fallback?.text || '',
                pagePath: path,
                icon: a.icon || fallback?.icon || '',
                activeIcon: a.activeIcon || fallback?.iconActive || a.icon || fallback?.icon || '',
                badgeKey: fallback?.badgeKey,
            }
        })
    }
    return defaultItems.map((d) => ({
        text: d.text,
        pagePath: d.pagePath,
        icon: d.icon,
        activeIcon: d.iconActive,
        badgeKey: d.badgeKey,
    }))
})

const colors = computed(() => {
    const c = appStore.config?.tabbar_colors as Record<string, string> | undefined
    const primary = (appStore.config?.theme_primary_color as string) || '#2979ff'
    return {
        text: c?.text || '#a1a1aa',
        active: c?.active || primary,
        bg: c?.bg || '#ffffff',
    }
})

const currentIdx = computed(() => {
    return items.value.findIndex((t) =>
        t.pagePath === props.currentPath || t.pagePath === '/' + props.currentPath
    )
})

function isImageIcon(item: UiTabbarItem): boolean {
    const v = item.icon || ''
    // URL/路径 → image；命名图标（无 / 无 http）→ d-icon
    return /^https?:\/\//.test(v) || v.startsWith('/')
}

function iconColor(active: boolean): string {
    return active ? colors.value.active : colors.value.text
}
function textColor(active: boolean): string {
    return active ? colors.value.active : colors.value.text
}

function getBadge(item: UiTabbarItem): number {
    if (item.badgeKey === 'cart') return cartBadge.value
    if (item.badgeKey === 'message') return messageBadge.value
    return 0
}

async function refreshBadges() {
    if (!getToken()) {
        cartBadge.value = 0
        messageBadge.value = 0
        return
    }
    const [cartResult, messageResult] = await Promise.allSettled([
        cartApi.getCartList(),
        messageApi.getUnreadCount(),
    ])
    if (cartResult.status === 'fulfilled') {
        cartBadge.value = cartResult.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0)
    }
    if (messageResult.status === 'fulfilled') {
        messageBadge.value = Number(messageResult.value.count || 0)
    }
}

onMounted(refreshBadges)

function onTap(item: UiTabbarItem, idx: number) {
    if (currentIdx.value === idx) return
    const url = item.pagePath
    if (!url) return
    if (nativeTabPaths.has(url) || nativeTabPaths.has(url.replace(/^\//, ''))) {
        uni.switchTab({ url })
    } else {
        uni.navigateTo({ url })
    }
}
</script>

<style lang="scss" scoped>
.d-tabbar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  align-items: stretch;
  width: 100%;
  height: 100rpx;
  padding-bottom: env(safe-area-inset-bottom);
  // background 由模板 inline style 注入（来自 appStore.config.tabbar_colors.bg），
  // 这里只放一个 fallback；不依赖 CSS var 是因为 mp 滚动时 fixed 元素 var() 解析偶发失败
  background: #ffffff;
  border-top: 1rpx solid #e4e4e7;
  z-index: 500;
  box-sizing: content-box;
  // 触发硬件加速，规避 mp scroll-view 滚动时 fixed 元素背景闪烁问题
  transform: translateZ(0);
  will-change: transform;

  &__item {
    flex-grow: 1;
    flex-shrink: 1;
    flex-basis: 0;
    width: 0;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100rpx;
  }

  &__icon-wrap {
    position: relative;
    width: 48rpx;
    height: 48rpx;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__img {
    width: 44rpx;
    height: 44rpx;
    object-fit: contain;
  }

  &__text {
    margin-top: 4rpx;
    font-size: 20rpx;
    line-height: 1;
  }

  &__badge {
    position: absolute;
    top: -8rpx;
    right: -16rpx;
    min-width: 28rpx;
    height: 28rpx;
    line-height: 28rpx;
    padding: 0 6rpx;
    border-radius: 14rpx;
    background: var(--color-danger);
    color: #fff;
    font-size: 18rpx;
    text-align: center;
    box-sizing: border-box;
  }
}
</style>
