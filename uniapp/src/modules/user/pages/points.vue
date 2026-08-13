<template>
  <d-page :safe-area="true">
    <view class="points-page">
      <!-- Points Display -->
      <view class="points-header">
        <text class="points-label">当前积分</text>
        <text class="points-amount">{{ points }}</text>
      </view>

      <!-- Points Log List -->
      <view class="log-section">
        <text class="section-title">积分明细</text>
        <scroll-view
          scroll-y
          class="log-scroll"
          @scrolltolower="getList"
        >
          <view v-for="group in groupedLogs" :key="group.month" class="month-section">
            <view class="month-header">
              <text class="month-header__label">{{ group.monthLabel }}</text>
              <text class="month-header__summary">
                获得 +{{ group.totalIn }} · 消耗 -{{ group.totalOut }}
              </text>
            </view>
            <view v-for="item in group.items" :key="item.id" class="log-item">
              <view class="log-item__left">
                <text class="log-item__type">{{ item.type_text || typeText(item.type) }}</text>
                <text v-if="item.remark" class="log-item__remark">{{ item.remark }}</text>
                <text class="log-item__time">{{ (item.created_at || '').substring(5, 16) }}</text>
              </view>
              <text
                class="log-item__points"
                :class="Number(item.points) >= 0 ? 'log-item__points--in' : 'log-item__points--out'"
              >
                {{ Number(item.points) >= 0 ? '+' : '' }}{{ item.points }}
              </text>
            </view>
          </view>

          <d-list-loader
            :loading="loading"
            :finished="finished"
            :total="total"
            empty-text="暂无积分记录"
          />
        </scroll-view>
      </view>
    </view>
  </d-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { userApi, type PointsLogItem } from '@/api/user'
import { usePaging } from '@/hooks/usePaging'

const points = ref(0)

const { list, loading, finished, total, getList } = usePaging<PointsLogItem>({
  fetchFun: (params) => userApi.getPointsLogs(params),
})

const TYPE_TEXT: Record<number, string> = {
  1: '后台调整',
  2: '注册赠送',
  3: '签到',
  4: '消费赠送',
  5: '消费扣减',
  6: '积分商城兑换',
}

function typeText(type: number): string {
  return TYPE_TEXT[type] ?? '其他'
}

interface PtsMonthGroup {
  month: string
  monthLabel: string
  items: PointsLogItem[]
  totalIn: number
  totalOut: number
}

const groupedLogs = computed<PtsMonthGroup[]>(() => {
  const groups = new Map<string, PtsMonthGroup>()
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
    const pts = Number(item.points ?? 0)
    if (pts >= 0) g.totalIn += pts
    else g.totalOut += Math.abs(pts)
  }
  return Array.from(groups.values()).sort((a, b) => b.month.localeCompare(a.month))
})

async function loadPoints() {
  try {
    const res = await userApi.getPoints()
    points.value = res.points || 0
  } catch {
    // ignore
  }
}

onMounted(() => {
  loadPoints()
  getList()
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.points-page {
  padding: 0;
}

.points-header {
  background: linear-gradient(135deg, #ff9900, #f59e0b);
  border-radius: 24rpx;
  padding: 48rpx 40rpx;
  margin-bottom: 24rpx;
  text-align: center;

  .points-label {
    display: block;
    font-size: 26rpx;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 16rpx;
  }

  .points-amount {
    display: block;
    font-size: 72rpx;
    font-weight: 700;
    color: #ffffff;
  }
}

.section-title {
  display: block;
  font-size: 30rpx;
  font-weight: 600;
  color: var(--color-text, #{$text-color});
  margin-bottom: 24rpx;
}

.log-section {
  background: #ffffff;
  border-radius: 24rpx;
  padding: 32rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
}

.log-scroll {
  max-height: 1000rpx;
}

.month-section {
  margin-bottom: 24rpx;
}

.month-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  padding: 24rpx 0 16rpx;
  background: #fafafa;

  &__label {
    font-size: 28rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});
  }

  &__summary {
    font-size: 22rpx;
    color: $text-color-secondary;
  }
}

.log-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24rpx 0;
  border-bottom: 1rpx solid $border-color;

  &:last-child {
    border-bottom: none;
  }

  &__left {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    min-width: 0;
    flex: 1;
  }

  &__type {
    font-size: 28rpx;
    color: var(--color-text, #{$text-color});
    font-weight: 500;
  }

  &__remark {
    font-size: 24rpx;
    color: $text-color-secondary;
  }

  &__time {
    font-size: 22rpx;
    color: $text-color-secondary;
  }

  &__points {
    font-size: 32rpx;
    font-weight: 600;
    flex-shrink: 0;
    margin-left: 24rpx;
    font-variant-numeric: tabular-nums;

    &--in {
      color: $success-color;
    }

    &--out {
      color: $danger-color;
    }
  }
}
</style>
