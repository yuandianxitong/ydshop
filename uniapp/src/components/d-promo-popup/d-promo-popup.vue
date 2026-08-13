<template>
  <u-popup :show="visible" mode="bottom" round="24rpx" safeAreaInsetBottom @close="handleClose">
    <view class="d-promo-popup">
      <view class="d-promo-popup__header">
        <text class="d-promo-popup__title">活动详情</text>
        <view class="d-promo-popup__close" @tap="handleClose">
          <d-icon name="close" size="36rpx" color="#71717a" />
        </view>
      </view>

      <scroll-view scroll-y class="d-promo-popup__body">
        <view v-if="!rules || rules.length === 0" class="empty-tip">
          <text>该商品暂无满减活动</text>
        </view>

        <view v-for="rule in rules" :key="rule.id" class="rule-item">
          <view class="rule-item__title">
            <view class="rule-item__tag">满减</view>
            <text class="rule-item__name">{{ rule.name }}</text>
          </view>
          <view class="rule-item__tier" v-for="(tier, ti) in rule.rules" :key="ti">
            <text v-if="rule.type === 'reduce'">满 ¥{{ tierMin(tier) }} 减 ¥{{ tier.value }}</text>
            <text v-else-if="rule.type === 'percent'">满 ¥{{ tierMin(tier) }} 享 {{ formatPercent(tier.value) }} 折</text>
            <text v-else-if="rule.type === 'freight'">满 ¥{{ tierMin(tier) }} 包邮</text>
          </view>
          <text class="rule-item__time">{{ rule.end_at }} 结束</text>
        </view>
      </scroll-view>
    </view>
  </u-popup>
</template>

<script setup lang="ts">
import type { FullDiscountRule } from '@/api/marketing'

defineProps<{
  visible: boolean
  rules: FullDiscountRule[]
}>()

const emit = defineEmits<{ (e: 'update:visible', v: boolean): void }>()

function handleClose() {
  emit('update:visible', false)
}

// 兼容 admin 存的 min_amount 与历史 min
function tierMin(tier: any): number {
  return Number(tier?.min_amount ?? tier?.min ?? 0)
}

function formatPercent(v: number): string {
  // 0.8 → 8（8 折）
  return (v * 10).toFixed(1).replace(/\.0$/, '')
}
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.d-promo-popup {
  display: flex;
  flex-direction: column;
  max-height: 70vh;

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 32rpx;
    border-bottom: 1rpx solid #f0f0f0;
  }
  &__title { font-size: 32rpx; font-weight: 600; color: var(--color-text, #{$text-color}); }
  &__close { padding: 8rpx; }
  &__body { flex: 1; padding: 24rpx 32rpx; }
}

.empty-tip {
  text-align: center;
  padding: 80rpx 0;
  font-size: 28rpx;
  color: $text-color-secondary;
}

.rule-item {
  margin-bottom: 32rpx;
  padding-bottom: 32rpx;
  border-bottom: 1rpx dashed #eee;
  &:last-child { border-bottom: none; }

  &__title {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 16rpx;
  }
  &__tag {
    font-size: 22rpx;
    padding: 4rpx 12rpx;
    border-radius: 4rpx;
    background: var(--color-primary, #{$primary-color});
    color: #fff;
  }
  &__name { font-size: 28rpx; font-weight: 500; color: var(--color-text, #{$text-color}); }
  &__tier {
    font-size: 26rpx;
    color: $text-color-secondary;
    line-height: 1.6;
  }
  &__time {
    display: block;
    font-size: 22rpx;
    color: #aaa;
    margin-top: 12rpx;
  }
}
</style>
