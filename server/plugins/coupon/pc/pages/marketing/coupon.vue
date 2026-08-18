<template>
  <div class="bg-gray-50 min-h-screen pb-16">
    <div class="mx-auto max-w-1200px px-4 py-6">

      <!-- Page header -->
      <h1 class="text-2xl font-bold text-gray-800 mb-6">优惠券中心</h1>

      <!-- Tabs -->
      <div class="bg-white rounded-sm mb-4">
        <div class="flex border-b border-gray-100">
          <button
            class="coupon-tab"
            :class="{ 'coupon-tab--active': activeTab === 'available' }"
            @click="setTab('available')"
          >
            可领取
          </button>
          <button
            class="coupon-tab"
            :class="{ 'coupon-tab--active': activeTab === 'my' }"
            @click="setTab('my')"
          >
            我的优惠券
          </button>
        </div>

        <!-- Available coupons -->
        <div v-if="activeTab === 'available'" class="p-6 min-h-300px">
          <div v-if="availableLoading" class="grid grid-cols-3 gap-4">
            <div v-for="i in 6" :key="i" class="h-28 bg-gray-100 rounded animate-pulse" />
          </div>
          <template v-else-if="availableCoupons.length">
            <div class="grid grid-cols-3 gap-4">
              <div
                v-for="coupon in availableCoupons"
                :key="coupon.id"
                class="coupon-card"
              >
                <div class="coupon-card__left">
                  <div class="coupon-card__value">
                    <span v-if="coupon.type === 'fixed'" class="coupon-card__amount">
                      <span class="coupon-card__unit">¥</span>{{ coupon.value }}
                    </span>
                    <span v-else-if="coupon.type === 'percent'" class="coupon-card__amount">
                      {{ coupon.value }}<span class="coupon-card__unit">折</span>
                    </span>
                    <span v-else class="coupon-card__amount text-base">免运费</span>
                  </div>
                  <div class="coupon-card__condition">
                    满{{ coupon.min_amount }}元可用
                  </div>
                </div>
                <div class="coupon-card__right">
                  <span class="coupon-card__type-tag">{{ couponTypeLabel(coupon.type) }}</span>
                  <div class="coupon-card__name">{{ coupon.name }}</div>
                  <div class="coupon-card__validity">
                    {{ coupon.start_at.substring(0, 10) }} ~ {{ coupon.end_at.substring(0, 10) }}
                  </div>
                  <button
                    class="coupon-card__claim-btn"
                    :disabled="claimingId === coupon.id"
                    @click="handleClaim(coupon)"
                  >
                    {{ claimingId === coupon.id ? '领取中...' : '立即领取' }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="availableTotalPages > 1" class="flex justify-center items-center gap-2 mt-6 pt-4 border-t border-gray-100">
              <button class="pagination-btn" :disabled="availablePage <= 1" @click="goAvailablePage(availablePage - 1)">&lsaquo;</button>
              <button
                v-for="p in availableVisiblePages"
                :key="p"
                class="pagination-btn"
                :class="{ 'pagination-btn--active': availablePage === p }"
                @click="goAvailablePage(p)"
              >{{ p }}</button>
              <button class="pagination-btn" :disabled="availablePage >= availableTotalPages" @click="goAvailablePage(availablePage + 1)">&rsaquo;</button>
            </div>
          </template>
          <div v-else class="flex flex-col items-center justify-center py-16 text-gray-400">
            <span class="i-carbon-ticket text-5xl mb-3 block" />
            <p class="text-sm">暂无可领取的优惠券</p>
          </div>
        </div>

        <!-- My coupons -->
        <div v-if="activeTab === 'my'" class="p-6 min-h-300px">
          <!-- Filter -->
          <div class="flex gap-2 mb-5">
            <button
              v-for="f in myFilters"
              :key="f.value"
              class="px-4 py-1.5 text-sm rounded-full transition-colors"
              :class="myFilter === f.value ? 'bg-[var(--color-primary)] text-white' : 'text-gray-500 border border-gray-200 hover:bg-gray-50'"
              @click="setMyFilter(f.value)"
            >
              {{ f.label }}
            </button>
          </div>

          <div v-if="myLoading" class="grid grid-cols-3 gap-4">
            <div v-for="i in 6" :key="i" class="h-28 bg-gray-100 rounded animate-pulse" />
          </div>
          <template v-else-if="myCoupons.length">
            <div class="grid grid-cols-3 gap-4">
              <div
                v-for="coupon in myCoupons"
                :key="coupon.id"
                class="coupon-card"
                :class="coupon.status !== 'unused' ? 'coupon-card--used' : ''"
              >
                <div class="coupon-card__left">
                  <div class="coupon-card__value">
                    <span v-if="coupon.type === 'fixed'" class="coupon-card__amount">
                      <span class="coupon-card__unit">¥</span>{{ coupon.value }}
                    </span>
                    <span v-else-if="coupon.type === 'percent'" class="coupon-card__amount">
                      {{ coupon.value }}<span class="coupon-card__unit">折</span>
                    </span>
                    <span v-else class="coupon-card__amount text-base">免运费</span>
                  </div>
                  <div class="coupon-card__condition">
                    满{{ coupon.min_amount }}元可用
                  </div>
                </div>
                <div class="coupon-card__right">
                  <span
                    class="coupon-card__status-tag"
                    :class="`coupon-card__status-tag--${coupon.status === 'unused' ? 'active' : 'used'}`"
                  >
                    {{ couponStatusLabel(coupon.status) }}
                  </span>
                  <div class="coupon-card__name">{{ coupon.name }}</div>
                  <div class="coupon-card__validity">
                    {{ coupon.start_at.substring(0, 10) }} ~ {{ coupon.end_at.substring(0, 10) }}
                  </div>
                  <NuxtLink
                    v-if="coupon.status === 'unused'"
                    to="/goods"
                    class="coupon-card__use-btn"
                  >
                    去使用
                  </NuxtLink>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="myTotalPages > 1" class="flex justify-center items-center gap-2 mt-6 pt-4 border-t border-gray-100">
              <button class="pagination-btn" :disabled="myPage <= 1" @click="goMyPage(myPage - 1)">&lsaquo;</button>
              <button
                v-for="p in myVisiblePages"
                :key="p"
                class="pagination-btn"
                :class="{ 'pagination-btn--active': myPage === p }"
                @click="goMyPage(p)"
              >{{ p }}</button>
              <button class="pagination-btn" :disabled="myPage >= myTotalPages" @click="goMyPage(myPage + 1)">&rsaquo;</button>
            </div>
          </template>
          <div v-else class="flex flex-col items-center justify-center py-16 text-gray-400">
            <span class="i-carbon-ticket text-5xl mb-3 block" />
            <p class="text-sm">暂无{{ myFilterLabel }}优惠券</p>
            <button class="mt-3 text-sm text-[var(--color-primary)] hover:underline" @click="setTab('available')">
              去领取优惠券
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useMessage } from 'naive-ui'
import { marketingApi, type CouponItem, type MyCouponItem } from '~/api/marketing'

const message = useMessage()

const PAGE_SIZE = 12

// Tabs
const activeTab = ref<'available' | 'my'>('available')

function setTab(tab: 'available' | 'my') {
  activeTab.value = tab
  if (tab === 'available') fetchAvailable()
  else fetchMyCoupons()
}

// Available coupons
const availableCoupons = ref<CouponItem[]>([])
const availableLoading = ref(false)
const availablePage = ref(1)
const availableTotal = ref(0)
const availableTotalPages = computed(() => Math.ceil(availableTotal.value / PAGE_SIZE))
const availableVisiblePages = computed(() => buildPageRange(availablePage.value, availableTotalPages.value))

async function fetchAvailable() {
  availableLoading.value = true
  try {
    const res = await marketingApi.getReceivableCoupons()
    if (res.code === 200) {
      availableTotal.value = res.data.length
      const offset = (availablePage.value - 1) * PAGE_SIZE
      availableCoupons.value = res.data.slice(offset, offset + PAGE_SIZE)
    }
  } finally {
    availableLoading.value = false
  }
}

function goAvailablePage(p: number) {
  availablePage.value = p
  fetchAvailable()
}

// Claim coupon
const claimingId = ref<number | null>(null)

async function handleClaim(coupon: CouponItem) {
  claimingId.value = coupon.id
  try {
    const res = await marketingApi.claimCoupon(coupon.id)
    if (res.code === 200) {
      message.success('领取成功！')
      fetchAvailable()
    }
  } finally {
    claimingId.value = null
  }
}

// My coupons
const myFilters = [
  { label: '全部', value: '' },
  { label: '未使用', value: 'unused' },
  { label: '已使用', value: 'used' },
  { label: '已过期', value: 'expired' },
] as const
const myFilter = ref<'' | 'unused' | 'used' | 'expired'>('')
const myFilterLabel = computed(() => myFilters.find(f => f.value === myFilter.value)?.label ?? '')
const myCoupons = ref<MyCouponItem[]>([])
const myLoading = ref(false)
const myPage = ref(1)
const myTotal = ref(0)
const myTotalPages = computed(() => Math.ceil(myTotal.value / PAGE_SIZE))
const myVisiblePages = computed(() => buildPageRange(myPage.value, myTotalPages.value))

async function fetchMyCoupons() {
  myLoading.value = true
  try {
    const params: Record<string, any> = { page_no: myPage.value, page_size: PAGE_SIZE }
    if (myFilter.value) params.status = myFilter.value
    const res = await marketingApi.getMyCoupons(params)
    if (res.code === 200) {
      myCoupons.value = res.data.list
      myTotal.value = res.data.pagination.total
    }
  } finally {
    myLoading.value = false
  }
}

function setMyFilter(val: '' | 'unused' | 'used' | 'expired') {
  myFilter.value = val
  myPage.value = 1
  fetchMyCoupons()
}

function goMyPage(p: number) {
  myPage.value = p
  fetchMyCoupons()
}

function couponTypeLabel(type: CouponItem['type']): string {
  return type === 'fixed' ? '满减券' : type === 'percent' ? '折扣券' : '无门槛'
}

function couponStatusLabel(status: MyCouponItem['status']): string {
  return status === 'unused' ? '未使用' : status === 'used' ? '已使用' : '已过期'
}

function buildPageRange(current: number, total: number): number[] {
  const pages: number[] = []
  const start = Math.max(1, current - 2)
  const end = Math.min(total, start + 4)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
}

onMounted(() => {
  fetchAvailable()
})
</script>

<style scoped>
.coupon-tab {
  padding: 14px 24px;
  font-size: 0.875rem;
  color: #6b7280;
  border-bottom: 2px solid transparent;
  background: none;
  cursor: pointer;
  transition: all 0.15s;
}
.coupon-tab:hover { color: var(--color-primary); }
.coupon-tab--active {
  color: var(--color-primary);
  font-weight: 500;
  border-bottom-color: var(--color-primary);
}

/* Coupon card */
.coupon-card {
  display: flex;
  border-radius: 4px;
  overflow: hidden;
  border: 1px solid #e5e7eb;
  transition: box-shadow 0.15s;
}
.coupon-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }

.coupon-card--used {
  opacity: 0.55;
  filter: grayscale(0.3);
}

.coupon-card__left {
  background: var(--color-primary);
  color: #fff;
  padding: 16px 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 90px;
}

.coupon-card__value {
  text-align: center;
}

.coupon-card__amount {
  font-size: 1.75rem;
  font-weight: 700;
  line-height: 1;
}
.coupon-card__unit {
  font-size: 0.875rem;
  font-weight: 400;
}

.coupon-card__condition {
  font-size: 0.6875rem;
  margin-top: 4px;
  opacity: 0.85;
  white-space: nowrap;
}

.coupon-card__right {
  flex: 1;
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  background: #fff;
}

.coupon-card__type-tag,
.coupon-card__status-tag {
  display: inline-block;
  padding: 1px 8px;
  border-radius: 10px;
  font-size: 0.6875rem;
  font-weight: 500;
  width: fit-content;
}

.coupon-card__type-tag {
  background: #eff6ff;
  color: var(--color-primary);
}

.coupon-card__status-tag--active { background: #eff6ff; color: var(--color-primary); }
.coupon-card__status-tag--used { background: #f3f4f6; color: #6b7280; }
.coupon-card__status-tag--expired { background: #fff1f0; color: #ef4444; }

.coupon-card__name {
  font-size: 0.8125rem;
  font-weight: 500;
  color: #1f2937;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.coupon-card__validity {
  font-size: 0.6875rem;
  color: #9ca3af;
}

.coupon-card__claim-btn,
.coupon-card__use-btn {
  margin-top: auto;
  padding: 5px 14px;
  font-size: 0.75rem;
  border-radius: 2px;
  border: 1px solid var(--color-primary);
  color: var(--color-primary);
  background: #fff;
  cursor: pointer;
  text-align: center;
  transition: all 0.15s;
  text-decoration: none;
  display: inline-block;
  width: fit-content;
}
.coupon-card__claim-btn:not(:disabled):hover,
.coupon-card__use-btn:hover {
  background: var(--color-primary);
  color: #fff;
}
.coupon-card__claim-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-btn {
  padding: 5px 10px;
  font-size: 0.8125rem;
  border-radius: 2px;
  border: 1px solid #e5e7eb;
  color: #4b5563;
  background: #fff;
  cursor: pointer;
  min-width: 2rem;
  transition: all 0.15s;
}
.pagination-btn:hover:not(:disabled) { border-color: var(--color-primary); color: var(--color-primary); }
.pagination-btn--active { background: var(--color-primary) !important; border-color: var(--color-primary) !important; color: #fff !important; }
.pagination-btn:disabled { opacity: 0.4; cursor: not-allowed; }
</style>
