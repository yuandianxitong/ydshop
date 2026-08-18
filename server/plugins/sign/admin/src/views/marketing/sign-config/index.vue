<template>
  <div class="page-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">签到配置</h2>
        <p class="page-desc">每日签到、连续奖励、补签卡与积分发放规则</p>
      </div>
      <div class="page-actions">
        <el-button @click="scrollToRecords">签到记录</el-button>
        <el-button type="primary" @click="openConfigDrawer">应用配置</el-button>
      </div>
    </div>

    <!-- KPI 迷你卡 -->
    <div class="row-14 sc-kpi">
      <div v-for="(k, i) in kpiList" :key="i" class="kpi-mini" :class="k.tone">
        <div class="lb">{{ k.label }}</div>
        <div class="nm num">{{ formatCount(k.value) }}</div>
        <div class="tr"><span>{{ k.note }}</span></div>
      </div>
    </div>

    <!-- 周历 + 里程碑奖励 -->
    <div class="row-83 sc-row" v-loading="loading">
      <!-- 周历 -->
      <div class="card sc-week-card">
        <div class="card-head">
          <div class="card-title">每周签到周期</div>
          <div class="card-meta">7 天循环 · 周日重置</div>
        </div>
        <div class="card-body">
          <div class="sc-week">
            <div
              v-for="d in weekDays"
              :key="d.day"
              class="sc-day"
              :class="{ 'sc-today': d.today }"
            >
              <div v-if="d.today" class="sc-today-tag">今日</div>
              <div class="sc-day-num">第 {{ d.day }} 天</div>
              <div class="sc-day-name">{{ d.label }}</div>
              <div class="sc-day-circle" :class="{ 'sc-day-done': d.done, 'sc-day-current': d.today }">
                <i v-if="d.done" class="i-svg:check" />
                <span v-else class="num">{{ d.day }}</span>
              </div>
              <div class="sc-day-reward num">+{{ d.points }} 分</div>
            </div>
          </div>
          <div class="sc-week-meta">
            <div class="sc-meta-row">
              <span class="sc-meta-l">基础积分</span>
              <span class="num">{{ form['sign.points_base'] }} 分</span>
            </div>
            <div class="sc-meta-row">
              <span class="sc-meta-l">每日递增</span>
              <span class="num">+{{ form['sign.points_increment'] }} 分/天</span>
            </div>
            <div class="sc-meta-row">
              <span class="sc-meta-l">单日上限</span>
              <span class="num">{{ form['sign.points_max'] }} 分</span>
            </div>
            <div class="sc-meta-row">
              <span class="sc-meta-l">里程碑周期</span>
              <span class="num">连续 {{ form['sign.continuous_bonus_days'] }} 天</span>
            </div>
          </div>
        </div>
      </div>

      <!-- 里程碑奖励 -->
      <div class="card sc-bonus-card">
        <div class="card-head">
          <div class="card-title">连续签到奖励</div>
          <div class="card-meta">里程碑奖励</div>
        </div>
        <div class="card-body sc-bonus-body">
          <div
            v-for="m in milestones"
            :key="m.day"
            class="sc-bonus-row"
          >
            <div class="sc-bonus-info">
              <div class="sc-bonus-day">连续 {{ m.day }} 天</div>
              <div class="sc-bonus-reward">额外 +{{ m.points }} 积分</div>
            </div>
            <div class="sc-bonus-pct num">{{ m.pct }}%</div>
            <div class="progbar"><i :style="{ width: m.pct + '%' }" /></div>
          </div>
        </div>
      </div>
    </div>

    <!-- 最近签到记录 -->
    <div ref="recordsRef" class="card sc-records-card">
      <div class="card-head">
        <div class="card-title">最近签到记录</div>
      </div>

      <div class="filter-bar sc-filter">
        <el-input
          v-model="searchForm.user_id"
          placeholder="用户ID"
          clearable
          style="width: 130px"
          @keyup.enter="handleSearch"
        />
        <el-date-picker
          v-model="dateRange"
          type="daterange"
          range-separator="至"
          start-placeholder="开始日期"
          end-placeholder="结束日期"
          value-format="YYYY-MM-DD"
          style="width: 240px"
        />
        <el-select v-model="searchForm.is_makeup" placeholder="是否补签" clearable style="width: 130px" @change="handleSearch">
          <el-option label="正常签到" :value="0" />
          <el-option label="补签" :value="1" />
        </el-select>
        <el-select v-model="searchForm.source" placeholder="来源" clearable style="width: 130px" @change="handleSearch">
          <el-option label="微信小程序" value="mp_weixin" />
          <el-option label="H5" value="h5" />
          <el-option label="APP" value="app" />
          <el-option label="未知" value="unknown" />
        </el-select>
        <el-button @click="resetSearch">重置</el-button>
        <el-button type="primary" @click="handleSearch">查询</el-button>
      </div>

      <ProTable
        title="签到记录"
        storage-key="sign-log-list"
        :columns="logColumns"
        :data="logList"
        :loading="logLoading"
        :pagination="pagination"
        :show-column-config="false"
        @page-change="handlePageChange"
        @size-change="handleSizeChange"
      >
        <template #user="{ row }">
          <div class="user-cell">
            <el-avatar :size="28" :src="row.user_avatar || ''" class="user-cell__avatar">
              {{ (row.user_nickname || row.user_mobile || '?')[0] }}
            </el-avatar>
            <div class="user-cell__info">
              <div class="user-cell__name">{{ row.user_nickname || `用户${row.user_id}` }}</div>
              <div v-if="row.user_mobile" class="user-cell__mobile num">{{ row.user_mobile }}</div>
            </div>
          </div>
        </template>

        <template #is_makeup="{ row }">
          <el-tag :type="row.is_makeup ? 'warning' : 'success'" size="small">{{ row.is_makeup ? '补签' : '正常签到' }}</el-tag>
        </template>

        <template #source="{ row }">
          <el-tag :type="sourceTagMap[row.source]?.type || 'info'" size="small">{{ sourceTagMap[row.source]?.label || row.source }}</el-tag>
        </template>

        <template #created_at="{ row }">
          <span class="num text-ink-500">{{ row.created_at }}</span>
        </template>
      </ProTable>
    </div>

    <!-- 规则配置 Dialog -->
    <el-dialog
      v-model="drawerVisible"
      title="规则配置"
      width="640px"
      destroy-on-close
      :close-on-click-modal="false"
    >
      <div class="dialog-content" v-loading="loading">
        <el-form
          ref="formRef"
          :model="form"
          label-width="170px"
          class="settings-form"
        >
          <el-form-item label="基础签到积分">
            <el-input-number
              v-model="form['sign.points_base']"
              :min="1"
              :max="9999"
              :step="1"
              controls-position="right"
            />
            <span class="field-unit">积分</span>
            <div class="field-desc">每次签到获得的基础积分（第 1 天）。</div>
          </el-form-item>

          <el-form-item label="连续签到积分递增">
            <el-input-number
              v-model="form['sign.points_increment']"
              :min="0"
              :max="9999"
              :step="1"
              controls-position="right"
            />
            <span class="field-unit">积分/天</span>
            <div class="field-desc">每多签到一天额外增加的积分数，设为 0 则不递增。</div>
          </el-form-item>

          <el-form-item label="每日最高签到积分">
            <el-input-number
              v-model="form['sign.points_max']"
              :min="1"
              :max="9999"
              :step="1"
              controls-position="right"
            />
            <span class="field-unit">积分</span>
            <div class="field-desc">单次签到可获得积分的上限（不含连续奖励）。</div>
          </el-form-item>

          <el-form-item label="连续签到奖励天数">
            <el-input-number
              v-model="form['sign.continuous_bonus_days']"
              :min="1"
              :max="365"
              :step="1"
              controls-position="right"
            />
            <span class="field-unit">天</span>
            <div class="field-desc">连续签到达到该天数时触发一次额外奖励，之后重置周期。</div>
          </el-form-item>

          <el-form-item label="连续签到额外奖励积分">
            <el-input-number
              v-model="form['sign.continuous_bonus_points']"
              :min="0"
              :max="9999"
              :step="1"
              controls-position="right"
            />
            <span class="field-unit">积分</span>
            <div class="field-desc">每完成一个连续签到周期时额外奖励的积分。</div>
          </el-form-item>

          <el-divider content-position="left">补签配置</el-divider>

          <el-form-item label="补签开关">
            <el-switch
              :model-value="form['sign.makeup_enabled'] === '1'"
              @update:model-value="(v: string | number | boolean) => form['sign.makeup_enabled'] = v ? '1' : '0'"
            />
            <div class="field-desc">关闭后用户无法补签遗漏的日期。</div>
          </el-form-item>

          <el-form-item label="消耗类型">
            <el-radio-group v-model="form['sign.makeup_currency']">
              <el-radio value="points">积分</el-radio>
              <el-radio value="balance">余额</el-radio>
            </el-radio-group>
            <div class="field-desc">补签时使用的资源类型。</div>
          </el-form-item>

          <el-form-item :label="`单价（${form['sign.makeup_currency'] === 'balance' ? '元' : '积分'}/天）`">
            <el-input-number
              v-model="form['sign.makeup_price']"
              :min="0"
              :precision="form['sign.makeup_currency'] === 'balance' ? 2 : 0"
              controls-position="right"
            />
            <span class="field-unit">{{ form['sign.makeup_currency'] === 'balance' ? '元' : '积分' }}</span>
            <div class="field-desc">每补 1 天消耗的资源数量。</div>
          </el-form-item>

          <el-form-item label="补签时限">
            <el-input-number
              v-model="form['sign.makeup_days_limit']"
              :min="1"
              :max="60"
              :precision="0"
              controls-position="right"
            />
            <span class="field-unit">天</span>
            <div class="field-desc">允许补签距今最多 N 天内的签到。</div>
          </el-form-item>
        </el-form>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="drawerVisible = false">取消</el-button>
          <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { computed, onMounted, ref, watch } from 'vue'

import { type SignConfig, signConfigApi, signLogApi, type SignLogItem, type SignLogQuery,type SignLogStats } from '@/api/marketing'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'

const loading = ref(false)
const saving = ref(false)
const drawerVisible = ref(false)

type SignFormState = SignConfig

const form = ref<SignFormState>({
  'sign.points_base': 1,
  'sign.points_increment': 1,
  'sign.points_max': 7,
  'sign.continuous_bonus_days': 7,
  'sign.continuous_bonus_points': 10,
  'sign.makeup_enabled': '0',
  'sign.makeup_currency': 'points',
  'sign.makeup_price': 5,
  'sign.makeup_days_limit': 7,
})

const DAY_LABELS = ['周一', '周二', '周三', '周四', '周五', '周六', '周日']

const stats = ref<SignLogStats>({
  today_count: 0,
  continuous_7_users: 0,
  month_count: 0,
  month_points: 0,
})

const kpiList = computed(() => [
  { label: '今日签到',     value: stats.value.today_count,        tone: 'tone-blue',   note: '今日签到记录' },
  { label: '连续 7 天',    value: stats.value.continuous_7_users, tone: 'tone-purple', note: '当前活跃' },
  { label: '月签到次数',   value: stats.value.month_count,        tone: 'tone-teal',   note: '本月统计' },
  { label: '本月发放积分', value: stats.value.month_points,       tone: 'tone-amber',  note: '本月统计' },
])

const formatCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')

const fetchStats = async () => {
  try {
    const res = await signLogApi.getStats()
    if (res.data) {
      stats.value = res.data
    }
  } catch (e) {
    console.error('获取签到统计失败', e)
  }
}

const {
  list: logList,
  loading: logLoading,
  pagination,
  searchForm,
  handleSearch,
  resetSearch,
  handlePageChange,
  handleSizeChange,
} = useListPage<SignLogItem, SignLogQuery>({
  fetchFn: (params) => signLogApi.getList(params),
  defaultSearchForm: {
    user_id: '',
    sign_date_start: '',
    sign_date_end: '',
    is_makeup: '',
    source: '',
  },
  pageSize: 10,
})

const dateRange = ref<[string, string] | null>(null)
watch(dateRange, (v) => {
  if (Array.isArray(v) && v.length === 2) {
    searchForm.sign_date_start = v[0]
    searchForm.sign_date_end = v[1]
  } else {
    searchForm.sign_date_start = ''
    searchForm.sign_date_end = ''
  }
})

const sourceTagMap: Record<string, { label: string; type: '' | 'success' | 'info' | 'warning' }> = {
  mp_weixin: { label: '微信小程序', type: 'success' },
  h5:        { label: 'H5',          type: 'warning' },
  app:       { label: 'APP',         type: 'success' },
  unknown:   { label: '未知',        type: 'info'    },
}

const logColumns: ProColumn[] = [
  { key: 'user',            label: '会员',     minWidth: 200 },
  { key: 'sign_date',       label: '签到日期', width: 150 },
  { key: 'continuous_days', label: '连续天数', width: 120, align: 'right' },
  { key: 'points_awarded',  label: '获得积分', width: 120, align: 'right' },
  { key: 'is_makeup',       label: '类型',     width: 120 },
  { key: 'source',          label: '来源',     width: 120 },
  { key: 'created_at',      label: '时间',     width: 200 },
]

const weekDays = computed(() => {
  const today = new Date().getDay() // 0..6 (Sun=0)
  const todayIdx = today === 0 ? 6 : today - 1 // 转为 0..6 (Mon=0)
  const base = Number(form.value['sign.points_base']) || 0
  const inc = Number(form.value['sign.points_increment']) || 0
  const max = Number(form.value['sign.points_max']) || 0
  return Array.from({ length: 7 }, (_, i) => ({
    day: i + 1,
    label: DAY_LABELS[i],
    points: Math.min(base + inc * i, max || (base + inc * i)),
    today: i === todayIdx,
    done: i < todayIdx,
  }))
})

const milestones = computed(() => {
  const baseDay = Number(form.value['sign.continuous_bonus_days']) || 7
  const basePts = Number(form.value['sign.continuous_bonus_points']) || 10
  return [
    { day: baseDay, points: basePts, pct: 50 },
    { day: baseDay * 2, points: basePts * 2, pct: 75 },
    { day: baseDay * 4, points: basePts * 4, pct: 100 },
  ]
})

const loadConfig = async () => {
  loading.value = true
  try {
    const res = await signConfigApi.getConfig()
    if (res.data) {
      const cfg = res.data
      form.value['sign.points_base']             = Number(cfg['sign.points_base'] ?? 1)
      form.value['sign.points_increment']        = Number(cfg['sign.points_increment'] ?? 1)
      form.value['sign.points_max']              = Number(cfg['sign.points_max'] ?? 7)
      form.value['sign.continuous_bonus_days']   = Number(cfg['sign.continuous_bonus_days'] ?? 7)
      form.value['sign.continuous_bonus_points'] = Number(cfg['sign.continuous_bonus_points'] ?? 10)
      form.value['sign.makeup_enabled']          = String(cfg['sign.makeup_enabled'] ?? '0')
      form.value['sign.makeup_currency']         = String(cfg['sign.makeup_currency'] ?? 'points')
      form.value['sign.makeup_price']            = Number(cfg['sign.makeup_price'] ?? 5)
      form.value['sign.makeup_days_limit']       = Number(cfg['sign.makeup_days_limit'] ?? 7)
    }
  } catch {
    ElMessage.error('获取签到配置失败')
  } finally {
    loading.value = false
  }
}

const openConfigDrawer = () => {
  drawerVisible.value = true
}

const handleSave = async () => {
  saving.value = true
  try {
    await signConfigApi.updateConfig(form.value)
    ElMessage.success('保存成功')
    drawerVisible.value = false
    fetchStats()
  } catch {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

const recordsRef = ref<HTMLElement>()
const scrollToRecords = () => recordsRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })

onMounted(() => {
  loadConfig()
  fetchStats()
})
</script>

<style scoped lang="scss">
.sc-kpi {
  margin-bottom: 14px;
}

.sc-row {
  margin-bottom: 14px;
}

.sc-week {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 8px;
}

.sc-day {
  position: relative;
  padding: 14px 8px;
  border-radius: 8px;
  text-align: center;
  background: #fff;
  border: 1px solid var(--ink-100);
}

.sc-today {
  background: linear-gradient(180deg, #fff, var(--brand-50));
  border-color: var(--brand-500);
}

.sc-today-tag {
  position: absolute;
  top: -8px;
  right: -4px;
  background: var(--brand-500);
  color: #fff;
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 8px;
}

.sc-day-num {
  font-size: 11px;
  color: var(--ink-400);
}

.sc-day-name {
  font-size: 12px;
  color: var(--ink-700);
  margin-top: 2px;
}

.sc-day-circle {
  margin: 10px auto;
  width: 34px;
  height: 34px;
  border-radius: 17px;
  background: var(--ink-100);
  color: var(--ink-400);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;

  &.sc-day-done {
    background: var(--brand-500);
    color: #fff;
  }

  &.sc-day-current {
    background: #fff;
    border: 2px dashed var(--brand-500);
    color: var(--brand-500);
  }
}

.sc-day-reward {
  font-size: 11.5px;
  color: var(--ink-700);
  font-weight: 500;
}

.sc-week-meta {
  margin-top: 18px;
  padding: 14px 16px;
  background: var(--ink-50);
  border-radius: 6px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px 24px;
  font-size: 13px;
}

.sc-meta-row {
  display: flex;
  justify-content: space-between;
}

.sc-meta-l {
  color: var(--ink-500);
}

.sc-bonus-body {
  padding: 10px 18px 18px;
}

.sc-bonus-row {
  padding: 12px 0;
  border-bottom: 1px solid var(--ink-100);

  &:last-child {
    border-bottom: 0;
  }
}

.sc-bonus-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.sc-bonus-day {
  font-size: 13px;
  font-weight: 600;
  color: var(--ink-900);
}

.sc-bonus-reward {
  font-size: 12px;
  color: var(--ink-500);
}

.sc-bonus-pct {
  font-size: 13px;
  font-weight: 600;
  color: var(--brand-500);
  margin-bottom: 8px;
}

/* 最近签到记录 */
.sc-records-card {
  margin-top: 14px;
}

.sc-filter {
  margin-bottom: 12px;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 8px;

  &__avatar { flex-shrink: 0; }
  &__info { line-height: 1.3; min-width: 0; }
  &__name { font-size: 13px; color: var(--ink-900); }
  &__mobile { font-size: 11px; color: var(--ink-400); }
}

.card-title {
  font-size: 16px;
  font-weight: 600;
}

/* Dialog */
.dialog-content {
  padding: 4px 4px 0;
  max-height: 65vh;
  overflow-y: auto;
}

.settings-form {
  margin-top: 4px;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.field-unit {
  margin-left: 8px;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

.field-desc {
  margin-top: 4px;
  color: var(--el-text-color-placeholder);
  font-size: 12px;
  line-height: 1.5;
}
</style>
