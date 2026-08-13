<template>
  <d-page :safe-area="true">
    <view class="sign-page">
      <!-- Stats header -->
      <view class="stats-card">
        <view class="stat-item">
          <text class="stat-value">{{ calendar?.continuous_days ?? 0 }}</text>
          <text class="stat-label">连续签到（天）</text>
        </view>
        <view class="stat-divider" />
        <view class="stat-item">
          <text class="stat-value">{{ calendar?.today_points ?? 0 }}</text>
          <text class="stat-label">今日可获积分</text>
        </view>
      </view>

      <!-- Calendar card -->
      <view class="calendar-card">
        <!-- Month navigation -->
        <view class="calendar-header">
          <view class="nav-btn" @tap="prevMonth">
            <d-icon name="arrow-left" size="32rpx" color="#333" />
          </view>
          <text class="month-label">{{ currentMonthLabel }}</text>
          <view
            class="nav-btn"
            :class="canGoNext ? '' : 'nav-btn--disabled'"
            @tap="nextMonth"
          >
            <d-icon name="arrow-right" size="32rpx" color="#333" />
          </view>
        </view>

        <!-- Weekday headers -->
        <view class="weekdays">
          <view v-for="day in weekdays" :key="day" class="weekday">
            <text class="weekday-text">{{ day }}</text>
          </view>
        </view>

        <!-- Day grid -->
        <view v-if="calendarLoading" class="loading-wrap">
          <text class="loading-text">加载中...</text>
        </view>
        <view v-else class="days-grid">
          <!-- Leading blanks -->
          <view v-for="n in leadingBlanks" :key="'b-' + n" class="day-cell" />

          <!-- Day cells -->
          <view
            v-for="day in daysInMonth"
            :key="day"
            class="day-cell"
            :class="getDayCellClass(day)"
          >
            <text class="day-number">{{ day }}</text>
            <text v-if="isDaySigned(day)" class="day-check">✓</text>
          </view>
        </view>

        <!-- Legend -->
        <view class="legend">
          <view class="legend-item">
            <view class="legend-dot legend-dot--signed" />
            <text class="legend-text">已签到</text>
          </view>
          <view class="legend-item">
            <view class="legend-dot legend-dot--today" />
            <text class="legend-text">今天</text>
          </view>
          <view class="legend-item">
            <view class="legend-dot legend-dot--normal" />
            <text class="legend-text">未签到</text>
          </view>
        </view>
      </view>

      <!-- Sign-in button -->
      <view class="btn-wrap">
        <button
          v-if="!calendar?.today_signed"
          class="sign-btn"
          :disabled="signing"
          :loading="signing"
          @tap="handleCheckin"
        >
          {{ signing ? '签到中...' : '立即签到' }}
        </button>
        <view v-else class="signed-tip">
          <text class="signed-icon">✓</text>
          <text class="signed-text">今日已签到</text>
        </view>
      </view>
    </view>
  </d-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { memberApi } from '@/api/member'
import type { SignCalendar } from '@/api/member'

const weekdays = ['日', '一', '二', '三', '四', '五', '六']

const todayDate = new Date()
const currentYear = ref(todayDate.getFullYear())
const currentMonth = ref(todayDate.getMonth() + 1)

const calendar = ref<SignCalendar | null>(null)
const calendarLoading = ref(false)
const signing = ref(false)

const currentMonthStr = computed(() => {
  const m = String(currentMonth.value).padStart(2, '0')
  return `${currentYear.value}-${m}`
})

const currentMonthLabel = computed(() => {
  return `${currentYear.value}年${currentMonth.value}月`
})

const canGoNext = computed(() => {
  const y = todayDate.getFullYear()
  const m = todayDate.getMonth() + 1
  return currentYear.value < y || (currentYear.value === y && currentMonth.value < m)
})

const leadingBlanks = computed(() => {
  const d = new Date(currentYear.value, currentMonth.value - 1, 1)
  return d.getDay()
})

const daysInMonth = computed(() => {
  return new Date(currentYear.value, currentMonth.value, 0).getDate()
})

const todayStr = computed(() => {
  const y = todayDate.getFullYear()
  const m = String(todayDate.getMonth() + 1).padStart(2, '0')
  const d = String(todayDate.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
})

function isDaySigned(day: number): boolean {
  if (!calendar.value) return false
  const m = String(currentMonth.value).padStart(2, '0')
  const d = String(day).padStart(2, '0')
  return calendar.value.signed_dates.includes(`${currentYear.value}-${m}-${d}`)
}

function isToday(day: number): boolean {
  const m = String(currentMonth.value).padStart(2, '0')
  const d = String(day).padStart(2, '0')
  return `${currentYear.value}-${m}-${d}` === todayStr.value
}

function getDayCellClass(day: number): string {
  if (isDaySigned(day)) return 'day-cell--signed'
  if (isToday(day)) return 'day-cell--today'
  return ''
}

async function loadCalendar() {
  calendarLoading.value = true
  try {
    const res = await memberApi.getSignCalendar(currentMonthStr.value)
    if (res) {
      calendar.value = res
    }
  } catch {
    // ignore
  } finally {
    calendarLoading.value = false
  }
}

async function handleCheckin() {
  if (signing.value) return
  signing.value = true
  try {
    const res = await memberApi.signCheckin()
    if (res) {
      uni.showToast({ title: `签到成功！获得${res.points_awarded}积分`, icon: 'success' })
      await loadCalendar()
    }
  } catch (e: any) {
    uni.showToast({ title: e?.message || '签到失败', icon: 'none' })
  } finally {
    signing.value = false
  }
}

function prevMonth() {
  if (currentMonth.value === 1) {
    currentMonth.value = 12
    currentYear.value -= 1
  } else {
    currentMonth.value -= 1
  }
  loadCalendar()
}

function nextMonth() {
  if (!canGoNext.value) return
  if (currentMonth.value === 12) {
    currentMonth.value = 1
    currentYear.value += 1
  } else {
    currentMonth.value += 1
  }
  loadCalendar()
}

onMounted(() => {
  loadCalendar()
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.sign-page {
  padding-bottom: 40rpx;
}

// ── Stats card ────────────────────────────────────────────────────────────────
.stats-card {
  display: flex;
  align-items: center;
  background: linear-gradient(135deg, #2979ff, #4f8ef5);
  border-radius: 24rpx;
  padding: 40rpx;
  margin-bottom: 24rpx;
}

.stat-item {
  flex: 1;
  text-align: center;
}

.stat-value {
  display: block;
  font-size: 56rpx;
  font-weight: 700;
  color: #ffffff;
  line-height: 1.2;
}

.stat-label {
  display: block;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.75);
  margin-top: 8rpx;
}

.stat-divider {
  width: 1rpx;
  height: 60rpx;
  background: rgba(255, 255, 255, 0.3);
  flex-shrink: 0;
}

// ── Calendar card ─────────────────────────────────────────────────────────────
.calendar-card {
  background: #ffffff;
  border-radius: 24rpx;
  padding: 32rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
  margin-bottom: 24rpx;
}

.calendar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24rpx;
}

.nav-btn {
  width: 60rpx;
  height: 60rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12rpx;
  background: #f5f5f5;

  &--disabled {
    opacity: 0.3;
  }
}

.month-label {
  font-size: 32rpx;
  font-weight: 600;
  color: #1a1a1a;
}

// ── Weekdays ──────────────────────────────────────────────────────────────────
.weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  margin-bottom: 12rpx;
}

.weekday {
  text-align: center;
  padding: 8rpx 0;
}

.weekday-text {
  font-size: 24rpx;
  color: #999;
  font-weight: 500;
}

// ── Days grid ─────────────────────────────────────────────────────────────────
.loading-wrap {
  text-align: center;
  padding: 60rpx 0;
}

.loading-text {
  font-size: 28rpx;
  color: #999;
}

.days-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 8rpx;
}

.day-cell {
  aspect-ratio: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-radius: 12rpx;
  background: #f7f7f7;

  &--signed {
    background: #2979ff;

    .day-number {
      color: #ffffff;
    }

    .day-check {
      color: #ffffff;
    }
  }

  &--today {
    background: transparent;
    border: 3rpx solid #2979ff;

    .day-number {
      color: #2979ff;
      font-weight: 700;
    }
  }
}

.day-number {
  font-size: 26rpx;
  color: #333;
  line-height: 1;
}

.day-check {
  font-size: 20rpx;
  color: #2979ff;
  margin-top: 4rpx;
  line-height: 1;
}

// ── Legend ────────────────────────────────────────────────────────────────────
.legend {
  display: flex;
  align-items: center;
  gap: 32rpx;
  margin-top: 24rpx;
  padding-top: 24rpx;
  border-top: 1rpx solid #f0f0f0;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 10rpx;
}

.legend-dot {
  width: 24rpx;
  height: 24rpx;
  border-radius: 6rpx;

  &--signed {
    background: #2979ff;
  }

  &--today {
    background: transparent;
    border: 3rpx solid #2979ff;
  }

  &--normal {
    background: #f7f7f7;
    border: 1rpx solid #e8e8e8;
  }
}

.legend-text {
  font-size: 22rpx;
  color: #999;
}

// ── Sign button ───────────────────────────────────────────────────────────────
.btn-wrap {
  padding: 0 8rpx;
}

.sign-btn {
  width: 100%;
  height: 88rpx;
  border-radius: 44rpx;
  background: linear-gradient(135deg, #2979ff, #4f8ef5);
  color: #ffffff;
  font-size: 32rpx;
  font-weight: 600;
  border: none;
  box-shadow: 0 8rpx 24rpx rgba(41, 121, 255, 0.35);

  &[disabled] {
    opacity: 0.6;
  }
}

.signed-tip {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  height: 88rpx;
  background: #f0f7ff;
  border-radius: 44rpx;
  border: 2rpx solid #b3d4ff;
}

.signed-icon {
  font-size: 36rpx;
  color: #2979ff;
  font-weight: 700;
}

.signed-text {
  font-size: 30rpx;
  color: #2979ff;
  font-weight: 500;
}
</style>
