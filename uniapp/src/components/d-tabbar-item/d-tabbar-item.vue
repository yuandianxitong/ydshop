<template>
  <view
    class="d-tabbar-item"
    :class="{ 'd-tabbar-item--active': active }"
    @tap="onTap"
  >
    <view class="d-tabbar-item__icon-wrap">
      <d-icon
        :name="active ? iconActive : icon"
        size="44rpx"
        :color="iconColor"
      />
      <view v-if="badge && badge > 0" class="d-tabbar-item__badge">
        {{ badge > 99 ? '99+' : badge }}
      </view>
    </view>
    <text class="d-tabbar-item__text">{{ text }}</text>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppStore } from '@/store/app.store'

const props = defineProps<{
  text: string
  icon: string
  iconActive: string
  active: boolean
  badge?: number
}>()

const emit = defineEmits<{ tap: [] }>()

const appStore = useAppStore()

// 激活状态用后台主题色，未激活用 text-3 灰
// d-icon color 不能用 var(--*)（SVG 内 CSS var 不解析），需直接传值
const iconColor = computed(() => {
  if (props.active) {
    return (appStore.config?.theme_primary_color as string) || '#2979ff'
  }
  return '#a1a1aa'
})

function onTap() { emit('tap') }
</script>

<style lang="scss" scoped>
// 仅使用 var(--*) CSS 变量，不需要 @use tokens（避免与其他 d-* 组件
// scoped style 合并时 dart-sass 模块顺序冲突）

.d-tabbar-item {
  // 显式 flex 三件套：mp 老版本基础库不展开 flex: 1 简写
  flex-grow: 1;
  flex-shrink: 1;
  flex-basis: 0;
  width: 0;            // mp 兜底（部分基础库 flex-basis: 0 不生效，用 width: 0 + flex-grow 代替）
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100rpx;
  transition: transform var(--duration-base) ease-out;

  &--active { transform: scale(1.05); }

  &__icon-wrap {
    position: relative;
    width: 48rpx;
    height: 48rpx;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  // 注：图标颜色由 d-icon :color 传入（active/inactive 切换在 script），
  // 不在此处用 CSS 控制（SVG 不响应 currentColor 在 image 标签内的变化）

  &__text {
    margin-top: 4rpx;
    font-size: var(--font-xs);
    color: var(--color-text-3);
    transition: color var(--duration-base);
  }
  &--active &__text { color: var(--color-primary); }

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
