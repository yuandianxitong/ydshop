import { ref } from 'vue'
import { paymentApi } from '@/api/payment'
import type { PayChannel, TradeType } from '@/api/payment'
import { isWeixin, isWeixinBrowser, isApp } from '@/utils/platform'

export interface PayOptions {
  order_no: string
  channel: PayChannel
}

function getTradeType(): TradeType {
  if (isWeixin()) return 'jsapi'       // 微信小程序
  if (isWeixinBrowser()) return 'jsapi' // H5 在微信浏览器内
  if (isApp()) return 'app'
  return 'h5'
}

export function usePayment() {
  const loading = ref(false)

  async function pay(options: PayOptions): Promise<boolean> {
    const { order_no, channel } = options
    const trade_type = getTradeType()

    loading.value = true
    try {
      const result = await paymentApi.createOrder({ order_no, channel, trade_type })
      // 零元订单：后端直接完成支付，无需拉起渠道
      if (result?.need_pay === false || result?.paid === true) {
        return true
      }
      // 后端 payment_data 形如 { trade_type, data: {...} }；服务端会按 X-Client-Type 覆盖前端假设的 trade_type
      const serverTradeType = result.payment_data?.trade_type ?? trade_type
      const payParams = result.payment_data?.data ?? {}

      // H5: 跳转支付 URL
      if (serverTradeType === 'h5' && payParams.h5_url) {
        // #ifdef H5
        window.location.href = payParams.h5_url
        // #endif
        return true
      }

      // jsapi / app / native：调起 uni.requestPayment
      const provider = channel === 'wechat' ? 'wxpay' : 'alipay'
      await uni.requestPayment({
        provider,
        ...payParams,
      })

      return true
    } catch (error: any) {
      // User cancelled payment
      if (error?.errMsg?.includes('cancel')) {
        uni.showToast({ title: '已取消支付', icon: 'none' })
      } else {
        uni.showToast({ title: '支付失败', icon: 'none' })
      }
      return false
    } finally {
      loading.value = false
    }
  }

  async function checkPayResult(order_no: string): Promise<boolean> {
    try {
      const result = await paymentApi.queryOrder(order_no)
      return result.status === 'paid'
    } catch {
      return false
    }
  }

  return { loading, pay, checkPayResult }
}
