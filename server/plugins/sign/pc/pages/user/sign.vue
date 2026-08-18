<template>
  <div>
    <h2 class="text-xl font-bold text-gray-900 mb-6">每日签到</h2>

    <!-- Stats bar -->
    <div class="card p-6 mb-6 flex items-center gap-8">
      <div class="text-center">
        <div class="text-2xl font-bold text-indigo-600">{{ calendar?.continuous_days ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-1">连续签到（天）</div>
      </div>
      <div class="w-px h-10 bg-gray-200" />
      <div class="text-center">
        <div class="text-2xl font-bold text-amber-500">{{ calendar?.today_points ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-1">今日可获积分</div>
      </div>
      <div class="ml-auto">
        <button
          v-if="!calendar?.today_signed"
          :disabled="signing"
          class="btn-primary min-w-24"
          @click="handleCheckin"
        >
          {{ signing ? '签到中...' : '立即签到' }}
        </button>
        <div v-else class="flex items-center gap-1.5 text-green-600 font-medium">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          今日已签到
        </div>
      </div>
    </div>

    <!-- Calendar card -->
    <div class="card p-6">
      <!-- Month navigation -->
      <div class="flex items-center justify-between mb-4">
        <button
          class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors"
          @click="prevMonth"
        >
          <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <span class="text-base font-semibold text-gray-800">{{ currentMonthLabel }}</span>
        <button
          class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors"
          :disabled="!canGoNext"
          :class="canGoNext ? '' : 'opacity-30 cursor-not-allowed'"
          @click="nextMonth"
        >
          <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>

      <!-- Weekday headers -->
      <div class="grid grid-cols-7 mb-2">
        <div
          v-for="day in weekdays"
          :key="day"
          class="text-center text-xs text-gray-400 font-medium py-1"
        >
          {{ day }}
        </div>
      </div>

      <!-- Calendar grid -->
      <div v-if="calendarLoading" class="text-center py-10 text-gray-400">加载中...</div>
      <div v-else class="grid grid-cols-7 gap-1">
        <!-- Leading empty cells -->
        <div v-for="n in leadingBlanks" :key="'b-' + n" />

        <!-- Day cells -->
        <div
          v-for="day in daysInMonth"
          :key="day"
          class="aspect-square flex flex-col items-center justify-center rounded-lg text-sm transition-colors"
          :class="getDayCellClass(day)"
        >
          <span class="leading-none">{{ day }}</span>
          <svg
            v-if="isDaySigned(day)"
            class="w-3 h-3 mt-0.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
          </svg>
        </div>
      </div>

      <!-- Legend -->
      <div class="flex items-center gap-4 mt-4 pt-4 border-t border-gray-100 text-xs text-gray-500">
        <div class="flex items-center gap-1.5">
          <div class="w-4 h-4 rounded bg-indigo-500" />
          已签到
        </div>
        <div class="flex items-center gap-1.5">
          <div class="w-4 h-4 rounded border-2 border-indigo-500" />
          今天
        </div>
        <div class="flex items-center gap-1.5">
          <div class="w-4 h-4 rounded bg-gray-100" />
          未签到
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useMessage } from 'naive-ui'
import { memberApi } from '~/api/member'
import type { SignCalendar } from '~/api/member'

const message = useMessage()

const weekdays = ['日', '一', '二', '三', '四', '五', '六']

const today = new Date()
const currentYear = ref(today.getFullYear())
const currentMonth = ref(today.getMonth() + 1) // 1-indexed

const calendar = ref<SignCalendar | null>(null)
const calendarLoading = ref(false)
const signing = ref(false)

const currentMonthStr = computed(() => {
  const m = String(currentMonth.value).padStart(2, '0')
  return `${currentYear.value}-${m}`
})

const currentMonthLabel = computed(() => {
  return `${currentYear.value} 年 ${currentMonth.value} 月`
})

const canGoNext = computed(() => {
  return (
    currentYear.value < today.getFullYear() ||
    (currentYear.value === today.getFullYear() && currentMonth.value < today.getMonth() + 1)
  )
})

// First weekday of the current month (0=Sun, 6=Sat)
const leadingBlanks = computed(() => {
  const d = new Date(currentYear.value, currentMonth.value - 1, 1)
  return d.getDay()
})

const daysInMonth = computed(() => {
  return new Date(currentYear.value, currentMonth.value, 0).getDate()
})

const todayStr = computed(() => {
  const y = today.getFullYear()
  const m = String(today.getMonth() + 1).padStart(2, '0')
  const d = String(today.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
})

function isDaySigned(day: number): boolean {
  if (!calendar.value) return false
  const m = String(currentMonth.value).padStart(2, '0')
  const d = String(day).padStart(2, '0')
  const dateStr = `${currentYear.value}-${m}-${d}`
  return calendar.value.signed_dates.includes(dateStr)
}

function isToday(day: number): boolean {
  const m = String(currentMonth.value).padStart(2, '0')
  const d = String(day).padStart(2, '0')
  return `${currentYear.value}-${m}-${d}` === todayStr.value
}

function getDayCellClass(day: number): string {
  if (isDaySigned(day)) {
    return 'bg-indigo-500 text-white'
  }
  if (isToday(day)) {
    return 'border-2 border-indigo-500 text-indigo-600 font-semibold'
  }
  return 'bg-gray-50 text-gray-700'
}

async function loadCalendar() {
  calendarLoading.value = true
  try {
    const res = await memberApi.getSignCalendar(currentMonthStr.value)
    if (res.code === 200) {
      calendar.value = res.data
    }
  } catch {
    // ignore
  } finally {
    calendarLoading.value = false
  }
}

async function handleCheckin() {
  signing.value = true
  try {
    const res = await memberApi.signCheckin()
    if (res.code === 200) {
      message.success(`签到成功！获得 ${res.data.points_awarded} 积分`)
      await loadCalendar()
    } else {
      message.error(res.message || '签到失败')
    }
  } catch {
    message.error('签到失败')
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
