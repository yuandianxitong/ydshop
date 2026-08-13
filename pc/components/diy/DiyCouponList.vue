<template>
  <section v-if="couponList.length" class="diy-coupon-list">
    <div class="diy-coupon-list__header">
      <strong>领取你的专属优惠券</strong>
      <NuxtLink to="/marketing/coupon">全部优惠券 →</NuxtLink>
    </div>
    <div class="diy-coupon-list__items">
      <div v-for="coupon in couponList" :key="coupon.id" class="diy-coupon-list__item">
        <div class="diy-coupon-list__amount">{{ coupon.amount_text }}</div>
        <div class="diy-coupon-list__desc">{{ coupon.threshold_text }}</div>
        <button :disabled="claimingId === coupon.id" @click="claim(coupon.id)">
          {{ claimingId === coupon.id ? '领取中' : '立即领取' }}
        </button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { useMessage } from 'naive-ui'
import { marketingApi } from '~/api/marketing'

interface CouponEntry {
  id: number
  name: string
  amount_text: string
  threshold_text: string
}

const props = defineProps<{ coupon_ids?: number[]; style?: string; coupon_list?: CouponEntry[] }>()
const couponList = computed(() => props.coupon_list ?? [])
const claimingId = ref<number | null>(null)
const message = useMessage()

async function claim(id: number) {
  claimingId.value = id
  try {
    const res = await marketingApi.claimCoupon(id)
    if (res.code === 200) message.success('领取成功')
  } finally {
    claimingId.value = null
  }
}
</script>

<style scoped>
.diy-coupon-list { margin: 16px 0; padding: 18px; border-radius: 10px; color: #fff; background: linear-gradient(135deg,#ff8c6e,#ff5a4a); }
.diy-coupon-list__header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.diy-coupon-list__header a { color: #fff; font-size: 13px; opacity: .9; }
.diy-coupon-list__items { display: flex; gap: 10px; overflow-x: auto; }
.diy-coupon-list__item { flex: 0 0 180px; text-align: center; color: #ef4438; background: #fff1ec; border-radius: 8px; padding: 14px; }
.diy-coupon-list__amount { font-size: 24px; font-weight: 800; }
.diy-coupon-list__desc { font-size: 13px; margin: 4px 0 10px; }
.diy-coupon-list button { width: 100%; padding: 6px; border: 0; border-radius: 5px; color: #fff; background: #ef4438; cursor: pointer; }
.diy-coupon-list button:disabled { opacity: .65; }
</style>
