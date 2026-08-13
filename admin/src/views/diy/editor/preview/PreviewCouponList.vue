<template>
  <!-- 编辑器侧始终用占位渲染，真实数据在 uniapp 运行时由后端 hydrate 注入 -->
  <div class="preview-coupon-list">
    <div class="preview-coupon-list__header">
      <span class="preview-coupon-list__title">领取你的专属优惠券</span>
      <div class="preview-coupon-list__more">
        <span>全部优惠券</span>
        <i class="i-svg:chevron-right" />
      </div>
    </div>

    <div class="preview-coupon-list__scroll">
      <div class="preview-coupon-list__inner">
        <div
          v-for="i in cardCount"
          :key="i"
          class="preview-coupon-list__item"
        >
          <div
            v-if="i < cardCount"
            class="preview-coupon-list__divider"
          />
          <span class="preview-coupon-list__amount">¥{{ amounts[(i - 1) % amounts.length] }}</span>
          <span class="preview-coupon-list__desc">满{{ thresholds[(i - 1) % thresholds.length] }}可用</span>
          <div class="preview-coupon-list__btn">
            <span>立即领取</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  coupon_ids?: number[]
  style?: string
}>()

const amounts = [20, 50, 100, 150]
const thresholds = [199, 399, 699, 999]
const cardCount = computed(() => Math.max(props.coupon_ids?.length ?? 0, 4))
</script>

<style scoped>
.preview-coupon-list {
  margin: 8px 12px;
  padding: 12px 10px 10px;
  border-radius: 8px;
  background: linear-gradient(135deg, #ff8c6e 0%, #ff5a4a 100%);
  box-shadow: 0 4px 10px rgba(255, 90, 74, 0.22);
}

.preview-coupon-list__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 2px;
  margin-bottom: 9px;
}

.preview-coupon-list__title {
  font-size: 15px;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: 0.5px;
}

.preview-coupon-list__more {
  display: flex;
  align-items: center;
  gap: 2px;
  font-size: 11px;
  color: #ffffff;
  opacity: 0.92;
}

.preview-coupon-list__scroll {
  width: 100%;
  overflow-x: auto;
  white-space: nowrap;
}

.preview-coupon-list__inner {
  display: inline-flex;
  padding: 0 1px;
}

.preview-coupon-list__item {
  position: relative;
  flex-shrink: 0;
  width: 84px;
  padding: 8px 6px 6px;
  border-radius: 5px;
  background: #fff1ec;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.preview-coupon-list__item:not(:first-child) {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}

.preview-coupon-list__item:not(:last-child) {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

/* 相邻优惠券连接处：上下凹口 + 竖向虚线，形成联券效果 */
.preview-coupon-list__divider {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  width: 0;
  border-right: 1px dashed #ffb9a7;
  z-index: 2;
  pointer-events: none;
}

.preview-coupon-list__divider::before,
.preview-coupon-list__divider::after {
  content: '';
  position: absolute;
  left: 50%;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #ff7359;
  transform: translateX(-50%);
}

.preview-coupon-list__divider::before { top: -4px; }
.preview-coupon-list__divider::after { bottom: -4px; }

.preview-coupon-list__amount {
  font-size: 18px;
  font-weight: 800;
  line-height: 1.1;
  color: #f5483b;
  font-variant-numeric: tabular-nums;
}

.preview-coupon-list__desc {
  margin-top: 3px;
  font-size: 11px;
  color: #f5483b;
}

.preview-coupon-list__btn {
  margin-top: 6px;
  width: 100%;
  background: #f5483b;
  border-radius: 3px;
  padding: 5px 0;
  display: flex;
  align-items: center;
  justify-content: center;
}
.preview-coupon-list__btn span {
  font-size: 11px;
  font-weight: 600;
  color: #ffffff;
  letter-spacing: 0.5px;
}

</style>
