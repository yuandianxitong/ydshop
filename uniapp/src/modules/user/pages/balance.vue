<template>
  <d-page :safe-area="true">
    <view class="balance-page">
      <!-- Balance Display -->
      <view class="balance-header">
        <text class="balance-label">当前余额（元）</text>
        <text class="balance-amount">{{ balance }}</text>
        <u-button type="primary" size="normal" class="recharge-btn" @click="showRecharge = !showRecharge">
          {{ showRecharge ? '收起' : '立即充值' }}
        </u-button>
      </view>

      <!-- Recharge Section -->
      <view v-if="showRecharge" class="recharge-section">
        <text class="section-title">选择充值金额</text>
        <view class="amount-grid">
          <view
            v-for="item in presetAmounts"
            :key="item"
            class="amount-card"
            :class="{ 'is-active': selectedAmount === item && !useCustom }"
            @tap="selectAmount(item)"
          >
            <text class="amount-card__value">¥{{ item }}</text>
          </view>
        </view>

        <view class="custom-amount">
          <u-input
            v-model="customAmount"
            type="digit"
            placeholder="自定义金额"
            border="none"
            clearable
            class="custom-input"
            @focus="useCustom = true"
          >
            <template #prefix>
              <text class="custom-prefix">¥</text>
            </template>
          </u-input>
        </view>

        <u-button
          type="primary"
          :customStyle="{ width: '100%' }"
          :loading="paying"
          :disabled="paying || finalAmount <= 0"
          class="pay-btn"
          @click="showPayPopup = true"
        >
          确认充值 ¥{{ finalAmount.toFixed(2) }}
        </u-button>
      </view>

      <!-- Balance Log List -->
      <view class="log-section">
        <text class="section-title">余额明细</text>
        <scroll-view
          scroll-y
          class="log-scroll"
          @scrolltolower="getList"
        >
          <view v-for="group in groupedLogs" :key="group.month" class="month-section">
            <view class="month-header">
              <text class="month-header__label">{{ group.monthLabel }}</text>
              <text class="month-header__summary">
                收入 ¥{{ group.totalIn.toFixed(2) }} · 支出 ¥{{ group.totalOut.toFixed(2) }}
              </text>
            </view>
            <view v-for="item in group.items" :key="item.id" class="log-item">
              <view class="log-item__left">
                <text class="log-item__remark">{{ item.remark || '余额变动' }}</text>
                <text class="log-item__time">{{ (item.created_at || '').substring(5, 16) }}</text>
              </view>
              <text
                class="log-item__amount"
                :class="Number(item.amount) >= 0 ? 'log-item__amount--in' : 'log-item__amount--out'"
              >
                {{ Number(item.amount) >= 0 ? '+' : '' }}{{ Number(item.amount).toFixed(2) }}
              </text>
            </view>
          </view>

          <d-list-loader
            :loading="loading"
            :finished="finished"
            :total="total"
            empty-text="暂无余额记录"
          />
        </scroll-view>
      </view>
    </view>

    <!-- Payment Popup -->
    <d-payment-popup
      v-model="showPayPopup"
      :amount="finalAmount * 100"
      :loading="paying"
      @pay="handlePay"
    />
  </d-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { userApi, type BalanceLogItem } from '@/api/user'
import type { PayChannel } from '@/api/payment'
import { usePaging } from '@/hooks/usePaging'

const balance = ref('0.00')
const showRecharge = ref(false)
const showPayPopup = ref(false)
const paying = ref(false)

const presetAmounts = [10, 30, 50, 100, 200, 500]
const selectedAmount = ref(0)
const customAmount = ref('')
const useCustom = ref(false)

const finalAmount = computed(() => {
  if (useCustom.value && customAmount.value) {
    const val = parseFloat(customAmount.value)
    return isNaN(val) || val <= 0 ? 0 : val
  }
  return selectedAmount.value
})

function selectAmount(amount: number) {
  selectedAmount.value = amount
  useCustom.value = false
  customAmount.value = ''
}

const { list, loading, finished, total, getList, refresh } = usePaging<BalanceLogItem>({
  fetchFun: (params) => userApi.getBalanceLogs(params),
})

interface MonthGroup {
  month: string
  monthLabel: string
  items: BalanceLogItem[]
  totalIn: number
  totalOut: number
}

const groupedLogs = computed<MonthGroup[]>(() => {
  const groups = new Map<string, MonthGroup>()
  for (const item of list.value) {
    const month = String(item.created_at || '').substring(0, 7)
    if (!month) continue
    if (!groups.has(month)) {
      const [y, m] = month.split('-')
      groups.set(month, {
        month,
        monthLabel: `${y}年${parseInt(m)}月`,
        items: [],
        totalIn: 0,
        totalOut: 0,
      })
    }
    const g = groups.get(month)!
    g.items.push(item)
    const amt = Number(item.amount ?? 0)
    if (amt >= 0) g.totalIn += amt
    else g.totalOut += Math.abs(amt)
  }
  return Array.from(groups.values()).sort((a, b) => b.month.localeCompare(a.month))
})

async function loadBalance() {
  try {
    const res = await userApi.getBalance()
    balance.value = res.balance || '0.00'
  } catch {
    // ignore
  }
}

async function handlePay(channel: PayChannel) {
  if (finalAmount.value <= 0) {
    uni.showToast({ title: '请选择充值金额', icon: 'none' })
    return
  }

  paying.value = true
  try {
    const res = await userApi.recharge({
      amount: finalAmount.value,
      channel,
    })

    showPayPopup.value = false

    // Call native payment
    const payData = res.payment_data?.data
    try {
      // #ifdef MP-WEIXIN
      await new Promise<void>((resolve, reject) => {
        uni.requestPayment({
          provider: 'wxpay',
          timeStamp: payData.timeStamp,
          nonceStr: payData.nonceStr,
          package: payData.package,
          signType: payData.signType,
          paySign: payData.paySign,
          success: () => resolve(),
          fail: (err: any) => reject(err),
        })
      })
      // #endif
      // #ifdef H5
      if (payData.paySign) {
        // 微信浏览器内 JSAPI 支付
        await new Promise<void>((resolve, reject) => {
          function onBridgeReady() {
            (window as any).WeixinJSBridge.invoke('getBrandWCPayRequest', {
              appId: payData.appId,
              timeStamp: payData.timeStamp,
              nonceStr: payData.nonceStr,
              package: payData.package,
              signType: payData.signType,
              paySign: payData.paySign,
            }, (res: any) => {
              if (res.err_msg === 'get_brand_wcpay_request:ok') {
                resolve()
              } else {
                reject(new Error(res.err_msg || 'cancel'))
              }
            })
          }
          if (typeof (window as any).WeixinJSBridge !== 'undefined') {
            onBridgeReady()
          } else {
            document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false)
          }
        })
      } else if (payData.h5_url) {
        // 普通浏览器 H5 支付跳转
        window.location.href = payData.h5_url
        return
      }
      // #endif

      uni.showToast({ title: '充值成功', icon: 'success' })
      showRecharge.value = false
      await loadBalance()
      await refresh()
    } catch {
      uni.showToast({ title: '支付已取消', icon: 'none' })
    }
  } catch {
    // error handled by request interceptor
  } finally {
    paying.value = false
  }
}

onMounted(() => {
  loadBalance()
  getList()
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.balance-page {
  padding: 0;
}

.balance-header {
  background: linear-gradient(135deg, #2979ff, #1e5fcc);
  border-radius: 24rpx;
  padding: 48rpx 40rpx;
  margin-bottom: 24rpx;
  text-align: center;

  .balance-label {
    display: block;
    font-size: 26rpx;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 16rpx;
  }

  .balance-amount {
    display: block;
    font-size: 72rpx;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 32rpx;
  }

  .recharge-btn {
    border-radius: 40rpx !important;
    background: rgba(255, 255, 255, 0.2) !important;
    border: 2rpx solid rgba(255, 255, 255, 0.5) !important;
    color: #ffffff !important;
  }
}

.section-title {
  display: block;
  font-size: 30rpx;
  font-weight: 600;
  color: var(--color-text, #{$text-color});
  margin-bottom: 24rpx;
}

.recharge-section {
  background: #ffffff;
  border-radius: 24rpx;
  padding: 32rpx;
  margin-bottom: 24rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
}

.amount-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 20rpx;
  margin-bottom: 24rpx;

  .amount-card {
    width: calc(33.33% - 14rpx);
    height: 120rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f8f8;
    border-radius: 16rpx;
    border: 2rpx solid transparent;
    transition: all 0.2s;

    &.is-active {
      background: rgba(41, 121, 255, 0.06);
      border-color: var(--color-primary, #{$primary-color});
    }

    &__value {
      font-size: 32rpx;
      font-weight: 600;
      color: var(--color-text, #{$text-color});
    }
  }
}

.custom-amount {
  background: #f8f8f8;
  border-radius: 16rpx;
  margin-bottom: 32rpx;
  padding: 0 8rpx;

  .custom-prefix {
    font-size: 32rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});
  }

  .custom-input {
    background: transparent;
  }
}

.pay-btn {
  border-radius: 16rpx !important;
  height: 96rpx !important;
  font-size: 32rpx !important;
}

.log-section {
  background: #ffffff;
  border-radius: 24rpx;
  padding: 32rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
}

.log-scroll {
  max-height: 800rpx;
}

.month-section { margin-bottom: 24rpx; }

.month-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  padding: 24rpx 32rpx 16rpx;
  background: #fafafa;

  &__label { font-size: 28rpx; font-weight: 600; color: #18181b; }
  &__summary { font-size: 22rpx; color: #71717a; }
}

.log-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24rpx 32rpx;
  background: #fff;
  border-bottom: 1rpx solid #f4f4f5;

  &__left {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    min-width: 0;
    flex: 1;
  }

  &__remark {
    font-size: 28rpx;
    color: #18181b;
  }

  &__time {
    font-size: 22rpx;
    color: #a1a1aa;
  }

  &__amount {
    font-size: 32rpx;
    font-weight: 600;
    flex-shrink: 0;
    margin-left: 24rpx;
    font-variant-numeric: tabular-nums;

    &--in { color: $success-color; }
    &--out { color: $danger-color; }
  }
}
</style>
