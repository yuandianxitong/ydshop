<template>
  <view class="member-page">
    <scroll-view scroll-y class="member-page__scroll" :scroll-into-view="scrollAnchor">
      <!-- Hero：当前等级 + 成长值进度 -->
      <view class="hero">
        <view class="hero__row">
          <view class="hero__badge">
            <d-icon name="vip-crown" size="48rpx" color="#ffffff" />
          </view>
          <view class="hero__main">
            <text class="hero__label">我的等级</text>
            <text class="hero__name">{{ currentLevel?.name || '普通用户' }}</text>
          </view>
          <view class="hero__growth">
            <text class="hero__growth-num">{{ growth }}</text>
            <text class="hero__growth-label">成长值</text>
          </view>
        </view>

        <view class="hero__progress">
          <view class="hero__progress-track">
            <view class="hero__progress-fill" :style="{ width: progressPercent + '%' }" />
          </view>
          <view class="hero__progress-row">
            <text class="hero__progress-tip">{{ progressTip }}</text>
            <text class="hero__progress-target">{{ progressTargetText }}</text>
          </view>
        </view>
      </view>

      <!-- 等级体系：横向滚动选择 -->
      <view class="section">
        <view class="section__head">
          <text class="section__title">等级体系</text>
          <text class="section__action" @tap="scrollToRules">查看规则 ›</text>
        </view>

        <scroll-view v-if="levels.length > 0" scroll-x class="levels-scroll" :show-scrollbar="false">
          <view class="levels-row">
            <view
              v-for="(lv, i) in levels"
              :key="lv.id"
              class="level-chip"
              :class="[
                `level-chip--tier-${i % 6}`,
                {
                  'level-chip--active': i === selectedIndex,
                  'level-chip--current': lv.id === currentLevel?.id,
                  'level-chip--locked': isLocked(lv),
                },
              ]"
              @tap="selectedIndex = i"
            >
              <view class="level-chip__badge">
                <text v-if="!isLocked(lv)" class="level-chip__badge-text">{{ badgeOf(lv) }}</text>
                <d-icon v-else name="lock" size="32rpx" color="rgba(255,255,255,0.85)" />
              </view>
              <text class="level-chip__name">{{ lv.name }}</text>
              <text class="level-chip__status">
                {{ statusOf(lv) }}
              </text>
            </view>
          </view>
        </scroll-view>

        <view v-else class="levels-empty">
          <text>暂无等级数据</text>
        </view>
      </view>

      <!-- 选中等级的权益 -->
      <view v-if="selectedLevel" class="section">
        <view class="section__head">
          <text class="section__title">{{ selectedLevel.name }} 权益</text>
          <text
            class="section__tag"
            :class="isLocked(selectedLevel) ? 'section__tag--locked' : 'section__tag--unlocked'"
          >
            {{ isLocked(selectedLevel) ? `差 ${needGrowth} 解锁` : '已解锁' }}
          </text>
        </view>

        <view
          v-if="selectedBenefits.length > 0"
          class="benefit-grid"
          :class="{ 'benefit-grid--locked': isLocked(selectedLevel) }"
        >
          <view v-for="b in selectedBenefits" :key="b.label" class="benefit-cell">
            <view class="benefit-cell__icon" :style="{ background: b.bg }">
              <d-icon :name="b.icon" size="40rpx" :color="b.color" />
            </view>
            <text class="benefit-cell__label">{{ b.label }}</text>
            <text class="benefit-cell__value">{{ b.value }}</text>
          </view>
        </view>

        <view v-else class="benefit-empty">
          <text>该等级暂无配置权益</text>
        </view>
      </view>

      <!-- 成长值规则 -->
      <view id="rules" class="section">
        <view class="section__head">
          <text class="section__title">成长值规则</text>
        </view>
        <view class="rules-card">
          <view class="rules-item">
            <view class="rules-item__dot" />
            <text class="rules-item__text">订单完成后获得对应成长值，每消费 1 元 = 1 成长值</text>
          </view>
          <view class="rules-item">
            <view class="rules-item__dot" />
            <text class="rules-item__text">成长值累计达到对应等级阈值后自动升级</text>
          </view>
          <view class="rules-item">
            <view class="rules-item__dot" />
            <text class="rules-item__text">等级升级后享受对应等级的全部权益</text>
          </view>
        </view>
      </view>

      <view class="bottom-spacer" />
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { memberApi, type MemberLevel, type MemberProfile } from '@/api/member'

const profile = ref<MemberProfile | null>(null)
const levels = ref<MemberLevel[]>([])
const scrollAnchor = ref('')
const selectedIndex = ref(0)

const growth = computed(() => Number(profile.value?.growth_value ?? 0))

const currentLevel = computed<MemberLevel | null>(() => {
  if (profile.value?.member_level) return profile.value.member_level
  if (!profile.value?.member_level_id) return null
  return levels.value.find(l => l.id === profile.value!.member_level_id) || null
})

const selectedLevel = computed<MemberLevel | null>(() => levels.value[selectedIndex.value] || null)

const needGrowth = computed(() => {
  if (!selectedLevel.value) return 0
  return Math.max(selectedLevel.value.growth_min - growth.value, 0)
})

function badgeOf(lv: MemberLevel): string {
  const m = (lv.name || '').match(/V?\d+/)
  if (m) return m[0].startsWith('V') ? m[0] : `V${m[0]}`
  return (lv.name || '').slice(0, 2) || 'V'
}

function isLocked(lv: MemberLevel): boolean {
  return Number(lv.growth_min) > growth.value
}

function statusOf(lv: MemberLevel): string {
  if (lv.id === currentLevel.value?.id) return '当前'
  if (isLocked(lv)) return '未解锁'
  return '已享有'
}

const nextLevel = computed<MemberLevel | null>(() => {
  if (!currentLevel.value || levels.value.length === 0) return levels.value[0] || null
  return levels.value.find(l => l.growth_min > currentLevel.value!.growth_min) || null
})

const isMaxLevel = computed(() => currentLevel.value !== null && nextLevel.value === null)

const progressTip = computed(() => {
  if (!currentLevel.value) return '完成首单解锁专属等级'
  if (isMaxLevel.value) return '已达最高等级'
  if (nextLevel.value) {
    const need = Math.max(nextLevel.value.growth_min - growth.value, 0)
    return `还差 ${need} 成长值升级`
  }
  return ''
})

const progressTargetText = computed(() => {
  if (isMaxLevel.value) return 'MAX'
  return nextLevel.value ? `→ ${nextLevel.value.name}` : ''
})

const progressPercent = computed(() => {
  if (isMaxLevel.value) return 100
  if (!currentLevel.value || !nextLevel.value) {
    if (nextLevel.value) return Math.min(100, (growth.value / nextLevel.value.growth_min) * 100)
    return 0
  }
  const span = nextLevel.value.growth_min - currentLevel.value.growth_min
  if (span <= 0) return 100
  const cur = growth.value - currentLevel.value.growth_min
  return Math.max(0, Math.min(100, (cur / span) * 100))
})

interface BenefitItem {
  icon: string
  label: string
  value: string
  color: string
  bg: string
}

const selectedBenefits = computed<BenefitItem[]>(() => {
  const lv = selectedLevel.value
  if (!lv) return []
  const out: BenefitItem[] = []

  const dt = discountText(lv)
  if (dt) out.push({ icon: 'tag', label: '会员折扣', value: dt, color: '#f59e0b', bg: 'rgba(245,158,11,0.10)' })

  const pt = pointsText(lv)
  if (pt) out.push({ icon: 'star', label: '积分奖励', value: pt, color: '#3b82f6', bg: 'rgba(59,130,246,0.10)' })

  if (lv.free_freight)
    out.push({ icon: 'send', label: '包邮', value: '全场包邮', color: '#10b981', bg: 'rgba(16,185,129,0.10)' })

  for (const p of extractPrivileges(lv)) {
    out.push({ icon: 'shield', label: p, value: '专享', color: '#8b5cf6', bg: 'rgba(139,92,246,0.10)' })
  }
  return out
})

function extractPrivileges(lv: MemberLevel | null): string[] {
  if (!lv || !Array.isArray(lv.privileges)) return []
  return lv.privileges.filter(s => typeof s === 'string' && s.trim() !== '')
}

function discountText(lv: MemberLevel | null): string {
  if (!lv) return ''
  const d = Number(lv.discount)
  if (!d || d >= 1) return ''
  const tenths = Math.round(d * 100) / 10
  const display = tenths % 1 === 0 ? tenths.toFixed(0) : tenths.toFixed(1)
  return `${display} 折`
}

function pointsText(lv: MemberLevel | null): string {
  if (!lv) return ''
  const r = Number(lv.points_rate)
  if (!r || r <= 1) return ''
  const display = r % 1 === 0 ? r.toFixed(0) : r.toFixed(1)
  return `${display} 倍`
}

function scrollToRules() {
  scrollAnchor.value = ''
  setTimeout(() => { scrollAnchor.value = 'rules' }, 0)
}

async function loadProfile() {
  try {
    profile.value = await memberApi.getMemberProfile()
  } catch (e) {
    console.error('Failed to load profile', e)
  }
}

async function loadLevels() {
  try {
    const list = await memberApi.getMemberLevels()
    levels.value = Array.isArray(list) ? list : []
  } catch (e) {
    console.error('Failed to load levels', e)
  }
}

watch([levels, currentLevel], () => {
  if (!currentLevel.value || levels.value.length === 0) return
  const idx = levels.value.findIndex(l => l.id === currentLevel.value!.id)
  if (idx >= 0) selectedIndex.value = idx
})

onShow(() => {
  loadProfile()
  loadLevels()
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.member-page {
  min-height: 100vh;
  background: var(--color-bg, #{$bg-color});

  &__scroll {
    height: 100vh;
  }
}

// Hero — 主题色渐变（不再用 6 个 tier 大背景）
.hero {
  margin: 24rpx;
  padding: 36rpx 32rpx 32rpx;
  border-radius: 24rpx;
  color: #ffffff;
  background-color: var(--color-primary, #{$primary-color});
  background-image: linear-gradient(135deg, rgba(255,255,255,0.18) 0%, rgba(0,0,0,0.18) 100%);
  box-shadow: 0 12rpx 32rpx rgba(0,0,0,0.12);

  &__row {
    display: flex;
    align-items: center;
    gap: 20rpx;
    margin-bottom: 28rpx;
  }

  &__badge {
    width: 88rpx;
    height: 88rpx;
    border-radius: 28rpx;
    background: rgba(255,255,255,0.20);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  &__main {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
    min-width: 0;
  }

  &__label {
    font-size: 22rpx;
    color: rgba(255,255,255,0.78);
  }

  &__name {
    font-size: 38rpx;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 1rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__growth {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4rpx;
  }

  &__growth-num {
    font-size: 40rpx;
    font-weight: 700;
    color: #ffffff;
    line-height: 1;
    font-variant-numeric: tabular-nums;
  }

  &__growth-label {
    font-size: 22rpx;
    color: rgba(255,255,255,0.78);
  }

  &__progress-track {
    height: 14rpx;
    border-radius: 999rpx;
    background: rgba(0,0,0,0.18);
    overflow: hidden;
  }

  &__progress-fill {
    height: 100%;
    border-radius: 999rpx;
    background: linear-gradient(90deg, #fde68a 0%, #fbbf24 100%);
    box-shadow: 0 0 12rpx rgba(251,191,36,0.4);
    transition: width 0.5s ease;
  }

  &__progress-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 14rpx;
  }

  &__progress-tip {
    font-size: 22rpx;
    color: rgba(255,255,255,0.85);
  }

  &__progress-target {
    font-size: 22rpx;
    color: #fde68a;
    font-weight: 600;
  }
}

// Section
.section {
  margin: 24rpx;

  &__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8rpx 4rpx 18rpx;
  }

  &__title {
    font-size: 30rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});
  }

  &__action {
    font-size: 24rpx;
    color: var(--color-primary, #{$primary-color});
  }

  &__tag {
    padding: 4rpx 14rpx;
    border-radius: 8rpx;
    font-size: 22rpx;
    font-weight: 500;

    &--unlocked {
      background: rgba(16,185,129,0.10);
      color: #10b981;
    }

    &--locked {
      background: rgba(0,0,0,0.05);
      color: $text-color-secondary;
    }
  }
}

// Level chips — 横向滚动选择
.levels-scroll {
  white-space: nowrap;
  margin: 0 -24rpx;
  padding: 12rpx 0 16rpx;
}

.levels-row {
  display: inline-flex;
  gap: 16rpx;
  padding: 0 24rpx;
}

.level-chip {
  flex-shrink: 0;
  width: 180rpx;
  // 因 scroll-x 容器在 mp-weixin 下 overflow-y:hidden，外环/角标超出 chip 自身边界都会被裁。
  // 因此选中/当前的视觉指示一律画在 chip 自身的 border 上，绝不让任何元素溢出。
  padding: 24rpx 16rpx;
  border: 3rpx solid transparent;
  border-radius: 20rpx;
  background: #ffffff;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10rpx;
  position: relative;
  box-shadow: 0 4rpx 14rpx rgba(0,0,0,0.04);
  transition: border-color 0.2s, box-shadow 0.2s;

  &__badge {
    width: 84rpx;
    height: 84rpx;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
  }

  &__badge-text {
    font-size: 32rpx;
    font-weight: 800;
    color: #ffffff;
    text-shadow: 0 2rpx 6rpx rgba(0,0,0,0.25);
  }

  &__name {
    font-size: 26rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});
  }

  &__status {
    font-size: 20rpx;
    color: $text-color-secondary;
    padding: 2rpx 12rpx;
    border-radius: 999rpx;
    background: rgba(0,0,0,0.04);
  }

  // 6 个 tier badge 配色
  &--tier-0 &__badge { background: linear-gradient(135deg, #a17c5b, #d7ac8a); }
  &--tier-1 &__badge { background: linear-gradient(135deg, #a8b2c2, #e2e8f0); }
  &--tier-2 &__badge { background: linear-gradient(135deg, #d4a857, #f5d27c); }
  &--tier-3 &__badge { background: linear-gradient(135deg, #a38fed, #d8b4fe); }
  &--tier-4 &__badge { background: linear-gradient(135deg, #8bb6f0, #bfdbfe); }
  &--tier-5 &__badge { background: linear-gradient(135deg, #4b4b4b, #1c1c1c); }

  // 选中态：3rpx 主题色边框（在 chip 自身边界内绘制，绝不溢出 → scroll-view 不会裁切顶部）
  &--active {
    border-color: var(--color-primary, #{$primary-color});
    box-shadow: 0 6rpx 18rpx rgba(0,0,0,0.08);
  }

  // 当前等级：金色边框 + 状态 pill 高亮（"当前"角标改放 chip 内右上角，不再溢出顶边）
  &--current {
    border-color: #fbbf24;

    &::before {
      content: '当前';
      position: absolute;
      top: 8rpx;
      right: 8rpx;
      padding: 2rpx 10rpx;
      background: linear-gradient(135deg, #fbbf24, #f59e0b);
      color: #ffffff;
      font-size: 18rpx;
      font-weight: 600;
      border-radius: 999rpx;
      z-index: 1;
    }

    .level-chip__status {
      background: rgba(251,191,36,0.12);
      color: #d97706;
      font-weight: 600;
    }
  }

  // 当前 + 选中：主题色边框胜出
  &--active.level-chip--current {
    border-color: var(--color-primary, #{$primary-color});
  }

  // 未解锁
  &--locked {
    .level-chip__badge { filter: grayscale(0.4) brightness(0.85); }
    .level-chip__name { color: $text-color-secondary; }
  }
}

.levels-empty {
  padding: 60rpx 0;
  text-align: center;
  background: #ffffff;
  border-radius: 20rpx;

  text {
    font-size: 24rpx;
    color: $text-color-secondary;
  }
}

// Benefits
.benefit-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12rpx;
  padding: 24rpx 16rpx;
  background: #ffffff;
  border-radius: 20rpx;
  box-shadow: 0 4rpx 14rpx rgba(0,0,0,0.04);

  &--locked {
    opacity: 0.6;
    filter: grayscale(0.3);
  }
}

.benefit-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8rpx;
  padding: 12rpx 0;

  &__icon {
    width: 80rpx;
    height: 80rpx;
    border-radius: 22rpx;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__label {
    font-size: 22rpx;
    color: $text-color-secondary;
  }

  &__value {
    font-size: 24rpx;
    color: var(--color-text, #{$text-color});
    font-weight: 600;
  }
}

.benefit-empty {
  text-align: center;
  padding: 60rpx 0;
  background: #ffffff;
  border-radius: 20rpx;
  box-shadow: 0 4rpx 14rpx rgba(0,0,0,0.04);

  text {
    font-size: 24rpx;
    color: $text-color-secondary;
  }
}

// Rules
.rules-card {
  background: #ffffff;
  border-radius: 20rpx;
  padding: 28rpx 32rpx;
  box-shadow: 0 4rpx 14rpx rgba(0,0,0,0.04);
}

.rules-item {
  display: flex;
  align-items: flex-start;
  gap: 16rpx;
  padding: 10rpx 0;

  &__dot {
    width: 12rpx;
    height: 12rpx;
    background-color: var(--color-primary, #{$primary-color});
    border-radius: 50%;
    margin-top: 14rpx;
    flex-shrink: 0;
  }

  &__text {
    flex: 1;
    font-size: 26rpx;
    color: var(--color-text, #{$text-color});
    line-height: 1.7;
  }
}

.bottom-spacer { height: 40rpx; }
</style>
