<template>
  <u-popup
    :show="visible"
    mode="bottom"
    :safeAreaInsetBottom="true"
    :customStyle="{ borderRadius: '24rpx 24rpx 0 0' }"
    @close="visible = false"
  >
    <view class="d-payment-popup">
      <view class="d-payment-popup__header">
        <text class="d-payment-popup__title">选择支付方式</text>
        <view class="d-payment-popup__amount">
          <text class="d-payment-popup__symbol">¥</text>
          <text class="d-payment-popup__price">{{ amountText }}</text>
        </view>
      </view>

      <view class="d-payment-popup__methods">
        <view
          v-for="method in availableMethods"
          :key="method.channel"
          class="d-payment-popup__method"
          :class="{ 'is-active': selected === method.channel }"
          @tap="selected = method.channel"
        >
          <view class="d-payment-popup__method-icon" :class="`d-payment-popup__method-icon--${method.channel}`">
            <d-icon :name="method.icon" size="40rpx" color="#ffffff" />
          </view>
          <text class="d-payment-popup__method-name">{{ method.name }}</text>
          <u-icon
            :name="selected === method.channel ? 'checkmark-circle-fill' : 'checkmark-circle'"
            :color="selected === method.channel ? method.activeColor : '#cccccc'"
            size="40rpx"
          />
        </view>

        <view v-if="availableMethods.length === 0" class="d-payment-popup__empty">
          当前环境暂无可用支付方式
        </view>
      </view>

      <view class="d-payment-popup__footer">
        <u-button
          type="primary"
          :loading="loading"
          :disabled="loading || availableMethods.length === 0"
          :customStyle="{ width: '100%' }"
          @click="handlePay"
        >
          确认支付
        </u-button>
      </view>
    </view>
  </u-popup>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { PayChannel } from '@/api/payment'
import { isWeixin, isWeixinBrowser } from '@/utils/platform'

type PaymentMethod = {
  channel: PayChannel
  name: string
  icon: string
  activeColor: string
}

const paymentMethods: PaymentMethod[] = [
  { channel: 'wechat', name: '微信支付', icon: 'wechat', activeColor: '#07c160' },
  { channel: 'alipay', name: '支付宝支付', icon: 'alipay', activeColor: '#1677ff' },
]

const props = withDefaults(defineProps<{
  modelValue: boolean
  amount: number
  loading?: boolean
  channels?: PayChannel[]
}>(), {
  loading: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  pay: [channel: PayChannel]
}>()

const selected = ref<PayChannel>('wechat')

const visible = computed({
  get: () => props.modelValue,
  set: (val: boolean) => emit('update:modelValue', val),
})

const amountText = computed(() => {
  return (props.amount / 100).toFixed(2)
})

function isAlipayMiniProgram(): boolean {
  // #ifdef MP-ALIPAY
  return true
  // #endif
  return false
}

const runtimeChannels = computed<PayChannel[]>(() => {
  if (isWeixin() || isWeixinBrowser()) return ['wechat']
  if (isAlipayMiniProgram()) return ['alipay']
  return ['wechat', 'alipay']
})

const configuredChannels = computed<PayChannel[]>(() => {
  return props.channels?.length ? props.channels : ['wechat', 'alipay']
})

const availableMethods = computed(() => {
  return paymentMethods.filter((method) => {
    return configuredChannels.value.includes(method.channel) && runtimeChannels.value.includes(method.channel)
  })
})

watch(availableMethods, (methods) => {
  if (methods.length > 0 && !methods.some((method) => method.channel === selected.value)) {
    selected.value = methods[0].channel
  }
}, { immediate: true })

function handlePay() {
  if (!availableMethods.value.some((method) => method.channel === selected.value)) return
  emit('pay', selected.value)
}
</script>

<style lang="scss" scoped>
.d-payment-popup {
  padding: 40rpx 32rpx;

  &__header {
    text-align: center;
    margin-bottom: 48rpx;
  }

  &__title {
    display: block;
    font-size: 32rpx;
    font-weight: 600;
    color: #333333;
    margin-bottom: 20rpx;
  }

  &__symbol {
    font-size: 32rpx;
    font-weight: 600;
    color: #fa3534;
  }

  &__price {
    font-size: 56rpx;
    font-weight: 700;
    color: #fa3534;
  }

  &__methods {
    margin-bottom: 48rpx;
  }

  &__method {
    display: flex;
    align-items: center;
    padding: 28rpx 24rpx;
    background: #f8f8f8;
    border-radius: 16rpx;
    margin-bottom: 20rpx;
    border: 2rpx solid transparent;
    transition: all 0.2s;

    &.is-active {
      background: #ffffff;
      border-color: #2979ff;
      box-shadow: 0 4rpx 16rpx rgba(41, 121, 255, 0.1);
    }
  }

  &__method-icon {
    width: 72rpx;
    height: 72rpx;
    border-radius: 16rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20rpx;

    &--wechat {
      background: #07c160;
    }

    &--alipay {
      background: #1677ff;
    }
  }

  &__method-name {
    flex: 1;
    font-size: 30rpx;
    color: #333333;
    font-weight: 500;
  }

  &__empty {
    padding: 40rpx 24rpx;
    text-align: center;
    font-size: 28rpx;
    color: #999999;
    background: #f8f8f8;
    border-radius: 16rpx;
  }

  &__footer {
    padding-bottom: 20rpx;
  }
}
</style>
