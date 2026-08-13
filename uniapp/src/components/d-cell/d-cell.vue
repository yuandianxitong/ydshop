<template>
  <view
    class="d-cell"
    :class="{ 'd-cell--clickable': clickable, 'd-cell--no-border': isLast }"
    @tap="onTap"
  >
    <view class="d-cell__label">
      <slot name="icon" />
      <text class="d-cell__label-text">{{ label }}</text>
    </view>
    <view class="d-cell__value">
      <slot>
        <text class="d-cell__value-text">{{ value }}</text>
      </slot>
    </view>
    <view v-if="arrow" class="d-cell__arrow">
      <d-icon name="arrow-right" size="28rpx" color="#a1a1aa" />
    </view>
  </view>
</template>

<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    label: string
    value?: string | number
    arrow?: boolean         // 末尾箭头
    clickable?: boolean     // 是否高亮可点击（决定 active 样式）
    isLast?: boolean        // 是否为分组最后一项（无下边框）
  }>(),
  {
    value: '',
    arrow: false,
    clickable: true,
    isLast: false,
  }
)

const emit = defineEmits<{ tap: [] }>()

function onTap() {
  if (props.clickable) emit('tap')
}
</script>

<style lang="scss" scoped>

.d-cell {
  display: flex;
  align-items: center;
  min-height: 80rpx;
  padding: var(--space-1) var(--space-3);
  background: var(--color-bg-1);
  border-bottom: 1rpx solid var(--color-border-2);

  &--clickable:active { background: var(--color-bg-3); }
  &--no-border { border-bottom: none; }

  &__label {
    display: flex;
    align-items: center;
    flex-shrink: 0;
    min-width: 128rpx;
  }
  &__label-text {
    font-size: var(--font-base);
    color: var(--color-text-2);
  }
  &__value {
    flex: 1;
    margin: 0 var(--space-2);
    text-align: right;
    overflow: hidden;
  }
  &__value-text {
    font-size: var(--font-base);
    color: var(--color-text-1);
  }
  &__arrow {
    flex-shrink: 0;
  }
}
</style>
