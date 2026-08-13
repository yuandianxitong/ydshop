<template>
  <div class="preview-seckill">
    <!-- Loading skeleton -->
    <div v-if="loading" class="preview-seckill__content">
      <div class="preview-seckill__header">
        <div class="preview-seckill__head-left">
          <span class="preview-seckill__title">限时秒杀</span>
        </div>
      </div>
      <div class="preview-seckill__list">
        <div v-for="i in Math.min(limit || 4, 4)" :key="'sk' + i" class="preview-seckill__item">
          <div class="preview-seckill__img-wrap preview-seckill__skeleton" />
          <div class="preview-seckill__skeleton-text" style="width: 90%; margin-top: 5px" />
          <div class="preview-seckill__skeleton-text" style="width: 50%; margin-top: 3px" />
        </div>
      </div>
    </div>

    <!-- Real data -->
    <div v-else-if="goodsList.length > 0" class="preview-seckill__content">
      <div class="preview-seckill__header">
        <div class="preview-seckill__head-left">
          <span class="preview-seckill__title">限时秒杀</span>
          <span v-if="sessionLabel" class="preview-seckill__tag">{{ sessionLabel }}</span>
          <div v-if="showCountdown" class="preview-seckill__countdown">
            <span class="preview-seckill__time-block">{{ hh }}</span>
            <span class="preview-seckill__time-sep">:</span>
            <span class="preview-seckill__time-block">{{ mm }}</span>
            <span class="preview-seckill__time-sep">:</span>
            <span class="preview-seckill__time-block">{{ ss }}</span>
          </div>
        </div>
        <div class="preview-seckill__more">
          <span>查看更多</span>
          <i class="i-svg:arrow-right preview-seckill__more-icon" />
        </div>
      </div>
      <div class="preview-seckill__scroll">
        <div class="preview-seckill__list">
          <div v-for="item in goodsList" :key="item.item_id" class="preview-seckill__item">
            <div class="preview-seckill__img-wrap">
              <img class="preview-seckill__img" :src="item.cover || '/storage/diy-defaults/goods.png'" />
            </div>
            <div class="preview-seckill__name">{{ item.name }}</div>
            <div class="preview-seckill__price-row">
              <span class="preview-seckill__price">¥{{ formatPrice(item.flash_price) }}</span>
              <span v-if="item.original_price > item.flash_price" class="preview-seckill__origin">
                ¥{{ formatPrice(item.original_price) }}
              </span>
            </div>
            <div class="preview-seckill__btn">
              <span>立即抢购</span>
              <i class="i-svg:arrow-right preview-seckill__btn-icon" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty: keep card shell + placeholder boxes so editor WYSIWYG height matches real state -->
    <div v-else class="preview-seckill__content preview-seckill__content--empty">
      <div class="preview-seckill__header">
        <div class="preview-seckill__head-left">
          <span class="preview-seckill__title">限时秒杀</span>
        </div>
        <div class="preview-seckill__more">
          <i class="i-svg:diy-seckill" />
          <span>暂无秒杀活动</span>
        </div>
      </div>
      <div class="preview-seckill__list">
        <div v-for="i in Math.min(limit || 4, 4)" :key="'ph' + i" class="preview-seckill__item">
          <div class="preview-seckill__img-wrap" />
          <div class="preview-seckill__name preview-seckill__name--placeholder">商品名称</div>
          <div class="preview-seckill__price-row">
            <span class="preview-seckill__price">¥--</span>
          </div>
          <div class="preview-seckill__btn"><span>立即抢购</span></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'

import { diyApi } from '@/api/diy'

interface SeckillGoods {
  item_id: number
  sku_id: number
  spu_id: number
  name: string
  cover: string
  flash_price: number
  original_price: number
  stock: number
  sold_count: number
}
interface SeckillData {
  id: number
  name: string
  start_at: string
  end_at: string
  session_label: string
  goods: SeckillGoods[]
}

const props = defineProps<{
  activity_id?: number | null
  limit?: number
}>()

const seckillData = ref<SeckillData | null>(null)
const loading = ref(false)
let debounceTimer: ReturnType<typeof setTimeout> | null = null
let lastHash = ''

function getPropsHash() {
  return JSON.stringify({ activity_id: props.activity_id, limit: props.limit })
}

async function fetchData() {
  const hash = getPropsHash()
  if (hash === lastHash) return
  lastHash = hash
  loading.value = true
  try {
    const res = await diyApi.previewData([
      { type: 'seckill', props: { activity_id: props.activity_id, limit: props.limit } },
    ])
    seckillData.value = res.data?.components?.[0]?.props?.seckill_data ?? null
  } catch {
    seckillData.value = null
  } finally {
    loading.value = false
  }
}

function debouncedFetch() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchData, 500)
}

watch(() => [props.activity_id, props.limit], debouncedFetch, { deep: true })
onMounted(fetchData)

const goodsList = computed<SeckillGoods[]>(() => {
  const list = seckillData.value?.goods ?? []
  const lim = props.limit ?? list.length
  return list.slice(0, lim)
})

const sessionLabel = computed(() => seckillData.value?.session_label ?? '')

const endsAtMs = computed(() => {
  const s = seckillData.value?.end_at
  if (!s) return 0
  const t = new Date(s.replace(' ', 'T')).getTime()
  return Number.isFinite(t) ? t : 0
})

const showCountdown = computed(() => endsAtMs.value > 0)

const now = ref(Date.now())
let countdownTimer: ReturnType<typeof setInterval> | null = null

const remainSec = computed(() => Math.max(0, Math.floor((endsAtMs.value - now.value) / 1000)))
const hh = computed(() => pad(Math.floor(remainSec.value / 3600)))
const mm = computed(() => pad(Math.floor((remainSec.value % 3600) / 60)))
const ss = computed(() => pad(remainSec.value % 60))

function pad(n: number) {
  return n < 10 ? `0${n}` : String(n)
}
function formatPrice(v: number) {
  return Number.isFinite(v) ? Number(v).toFixed(2).replace(/\.00$/, '') : '0'
}

watch(
  endsAtMs,
  (v) => {
    if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null }
    if (v > 0) {
      now.value = Date.now()
      countdownTimer = setInterval(() => {
        now.value = Date.now()
        if (now.value >= endsAtMs.value && countdownTimer) {
          clearInterval(countdownTimer)
          countdownTimer = null
        }
      }, 1000)
    }
  },
  { immediate: true }
)

onUnmounted(() => {
  if (countdownTimer) clearInterval(countdownTimer)
  if (debounceTimer) clearTimeout(debounceTimer)
})
</script>

<style scoped>
.preview-seckill { margin: 8px 12px; }

.preview-seckill__content {
  padding: 10px 9px 9px;
  border-radius: 9px;
  background: #ffffff;
  box-shadow: 0 3px 11px rgba(15, 23, 42, 0.06);
}

.preview-seckill__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.preview-seckill__head-left {
  display: flex;
  align-items: center;
  min-width: 0;
}

.preview-seckill__title {
  font-size: 16px;
  line-height: 1;
  font-weight: 800;
  color: #1f2937;
  letter-spacing: 0.5px;
  flex-shrink: 0;
}

.preview-seckill__tag {
  margin-left: 7px;
  height: 17px;
  padding: 0 5px;
  border-radius: 3.5px;
  background: #ff3b30;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 700;
  color: #ffffff;
  white-space: nowrap;
}

.preview-seckill__countdown {
  margin-left: 5px;
  display: flex;
  align-items: center;
  gap: 2px;
}

.preview-seckill__time-block {
  min-width: 14px;
  height: 14px;
  padding: 0 2px;
  border-radius: 2.5px;
  background: #ff3b30;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 9px;
  font-weight: 800;
  color: #ffffff;
  line-height: 14px;
  font-variant-numeric: tabular-nums;
}

.preview-seckill__time-sep {
  font-size: 9px;
  font-weight: 800;
  color: #ff3b30;
  line-height: 14px;
}

.preview-seckill__more {
  display: flex;
  align-items: center;
  gap: 1px;
  flex-shrink: 0;
  font-size: 11px;
  color: #909399;
}
.preview-seckill__more-icon { width: 10px; height: 10px; }

.preview-seckill__scroll { width: 100%; overflow-x: auto; white-space: nowrap; }
.preview-seckill__list { display: inline-flex; gap: 6px; }

.preview-seckill__item {
  width: 86px;
  padding-bottom: 2px;
  flex-shrink: 0;
}

.preview-seckill__img-wrap {
  width: 86px;
  height: 86px;
  border-radius: 5px;
  overflow: hidden;
  background: #f5f5f5;
}
.preview-seckill__img { width: 100%; height: 100%; display: block; object-fit: cover; }

.preview-seckill__name {
  margin-top: 5px;
  width: 100%;
  font-size: 11px;
  line-height: 1.3;
  color: #4b5563;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.preview-seckill__price-row {
  margin-top: 3px;
  display: flex;
  align-items: baseline;
  gap: 3px;
}

.preview-seckill__price {
  font-size: 13px;
  line-height: 1;
  color: #ff2f2f;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
}

.preview-seckill__origin {
  font-size: 9px;
  color: #b5b5b5;
  text-decoration: line-through;
  font-variant-numeric: tabular-nums;
}

.preview-seckill__btn {
  margin-top: 4px;
  width: 56px;
  height: 17px;
  border-radius: 999px;
  background: linear-gradient(90deg, #ff4c42 0%, #ff1f2d 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1px;
  box-shadow: 0 2px 5px rgba(255, 47, 47, 0.22);
  font-size: 9px;
  font-weight: 700;
  color: #ffffff;
  white-space: nowrap;
}
.preview-seckill__btn-icon { width: 9px; height: 9px; }

.preview-seckill__content--empty .preview-seckill__img-wrap {
  background: linear-gradient(135deg, #f5f7fa, #e4e7ed);
}
.preview-seckill__name--placeholder { color: #c0c4cc; }

.preview-seckill__skeleton {
  animation: seckill-skeleton 1.5s ease-in-out infinite;
}
.preview-seckill__skeleton-text {
  height: 9px;
  background: #e4e7ed;
  border-radius: 2px;
  animation: seckill-skeleton 1.5s ease-in-out infinite;
}
@keyframes seckill-skeleton { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
</style>
