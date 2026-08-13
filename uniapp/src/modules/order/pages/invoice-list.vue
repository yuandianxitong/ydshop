<template>
  <view class="invoice-list-page">
    <scroll-view scroll-y class="invoice-list-page__scroll">
      <view class="invoice-list-page__content">
        <view v-if="loading && list.length === 0" class="empty-wrap">
          <text>加载中...</text>
        </view>

        <view v-else-if="!loading && list.length === 0" class="empty-wrap">
          <d-icon name="file-text" size="80rpx" color="#d4d4d8" />
          <text class="empty-text">暂无发票记录</text>
        </view>

        <view
          v-for="item in list"
          :key="item.id"
          class="invoice-card"
        >
          <view class="invoice-card__header">
            <text class="invoice-card__order">订单号：{{ item.order_no }}</text>
            <view class="invoice-card__status" :class="`invoice-card__status--${item.status}`">
              {{ statusText(item.status) }}
            </view>
          </view>
          <view class="invoice-card__body">
            <text class="invoice-card__title">{{ item.title }}</text>
            <text class="invoice-card__meta">
              {{ item.type === 'company' ? '单位' : '个人' }} · ¥{{ formatAmount(item.amount) }}
            </text>
            <text class="invoice-card__email">邮箱：{{ item.recipient_email }}</text>
          </view>
          <view v-if="item.status === 'issued' && item.file_url" class="invoice-card__action">
            <view class="download-btn" @tap="onDownload(item)">下载电子发票</view>
          </view>
        </view>
      </view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { invoiceApi, type InvoiceItem } from '@/api/order'

const list = ref<InvoiceItem[]>([])
const loading = ref(false)

function statusText(s: string): string {
  return ({
    pending: '待审核',
    processing: '开票中',
    issued: '已开票',
    cancelled: '已取消',
  } as Record<string, string>)[s] || s
}

function formatAmount(n: number | string): string {
  return Number(n).toFixed(2)
}

async function fetchList() {
  loading.value = true
  try {
    const res = await invoiceApi.list({ page: 1, limit: 50 })
    list.value = res?.list || []
  } catch {
    // ignore
  } finally {
    loading.value = false
  }
}

function onDownload(item: InvoiceItem) {
  if (!item.file_url) return
  // #ifdef H5
  window.open(item.file_url, '_blank')
  // #endif
  // #ifdef MP-WEIXIN
  uni.setClipboardData({ data: item.file_url, success: () => {
    uni.showToast({ title: '链接已复制，粘贴到浏览器打开', icon: 'none' })
  }})
  // #endif
}

onShow(() => fetchList())

onMounted(() => fetchList())
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.invoice-list-page {
  min-height: 100vh;
  background: var(--color-bg, #{$bg-color});

  &__scroll { height: 100vh; }
  &__content { padding: $page-padding; }
}

.empty-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 120rpx 0;
  gap: 24rpx;
}

.empty-text { font-size: 28rpx; color: #ccc; }

.invoice-card {
  background: #fff;
  border-radius: 16rpx;
  margin-bottom: 24rpx;
  padding: 28rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16rpx;
  }
  &__order { font-size: 26rpx; color: $text-color-secondary; }
  &__status {
    font-size: 22rpx;
    padding: 4rpx 12rpx;
    border-radius: 8rpx;
    color: #fff;
    background: $text-color-secondary;
    &--pending { background: #faad14; }
    &--processing { background: #1677ff; }
    &--issued { background: #52c41a; }
    &--cancelled { background: #999; }
  }
  &__body {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    margin-bottom: 20rpx;
  }
  &__title { font-size: 30rpx; font-weight: 600; color: var(--color-text, #{$text-color}); }
  &__meta { font-size: 26rpx; color: $text-color-secondary; }
  &__email { font-size: 24rpx; color: $text-color-secondary; }
  &__action {
    padding-top: 20rpx;
    border-top: 1rpx solid #f5f5f5;
  }
}

.download-btn {
  height: 64rpx;
  line-height: 64rpx;
  text-align: center;
  background: var(--color-primary, #{$primary-color});
  color: #fff;
  border-radius: 8rpx;
  font-size: 26rpx;
}
</style>
