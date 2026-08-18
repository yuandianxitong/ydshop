<template>
  <div class="coupon-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">优惠券</h2>
        <p class="page-desc">满减券 / 折扣券 / 兑换券与发放策略</p>
      </div>
      <div class="page-actions">
        <el-button type="primary" @click="handleAdd">
          <i class="i-lucide:plus mr-1" /> 新建优惠券
        </el-button>
      </div>
    </div>

    <!-- 统计概览 -->
    <div class="mini-grid mini-grid-4 stats-row">
      <div class="kpi-mini tone-blue">
        <div class="lb"><span class="ic">总</span>优惠券总数</div>
        <div class="nm num">{{ formatCount(pagination.total) }}</div>
        <div class="tr">分页统计</div>
      </div>
      <div class="kpi-mini tone-teal">
        <div class="lb"><span class="ic">启</span>启用中</div>
        <div class="nm num">{{ formatCount(stats.enabled) }}</div>
        <div class="tr">当前页内</div>
      </div>
      <div class="kpi-mini tone-amber">
        <div class="lb"><span class="ic">满</span>满减券</div>
        <div class="nm num">{{ formatCount(stats.fixed) }}</div>
        <div class="tr">当前页内</div>
      </div>
      <div class="kpi-mini tone-purple">
        <div class="lb"><span class="ic">折</span>折扣券</div>
        <div class="nm num">{{ formatCount(stats.percent) }}</div>
        <div class="tr">当前页内</div>
      </div>
    </div>
    <!-- 视图切换 + 筛选 -->
    <div class="filter-bar cpn-filter">
      <div class="seg cpn-seg">
        <button :class="{ on: viewMode === 'card' }" @click="viewMode = 'card'">券卡片 ({{ pagination.total }})</button>
        <button :class="{ on: viewMode === 'table' }" @click="viewMode = 'table'">详细列表</button>
      </div>
      <span class="filter-sp" />
      <el-input
        v-model="searchForm.keyword"
        placeholder="搜索优惠券"
        clearable
        style="width: 240px"
        @keyup.enter="handleSearch"
        @clear="handleSearch"
      />
      <el-select v-model="searchForm.type" placeholder="全部类型" clearable style="width: 130px" @change="handleSearch">
        <el-option label="满减券" value="fixed" />
        <el-option label="折扣券" value="percent" />
        <el-option label="无门槛" value="no_threshold" />
      </el-select>
      <el-select v-model="searchForm.status" placeholder="全部状态" clearable style="width: 110px" @change="handleSearch">
        <el-option label="启用" :value="1" />
        <el-option label="禁用" :value="0" />
      </el-select>
      <el-button @click="handleReset">重置</el-button>
      <el-button type="primary" @click="handleSearch">查询</el-button>
    </div>

    <!-- 卡片视图 -->
    <div v-if="viewMode === 'card'" v-loading="loading" class="cpn-grid">
      <div v-for="row in tableData" :key="row.id" class="cpn-card">
        <div class="cpn-l">
          <span class="cpn-glow cpn-glow-1" />
          <span class="cpn-glow cpn-glow-2" />
          <div class="cpn-l-body">
            <div class="cpn-type-badge">{{ typeTagMap[row.type]?.label || row.type }}</div>
            <div class="cpn-val">{{ formatCouponValue(row) }}</div>
            <div class="cpn-period num">{{ formatPeriod(row) }}</div>
          </div>
        </div>
        <div class="cpn-r">
          <div class="cpn-r-head">
            <div class="cpn-r-info">
              <div class="cpn-name">{{ row.name }}</div>
              <div class="cpn-id num">#{{ row.id }}</div>
            </div>
            <el-tag :type="row.status ? 'success' : 'info'" size="small">
              {{ row.status ? '启用' : '已禁用' }}
            </el-tag>
          </div>
          <div class="cpn-prog-wrap">
            <div class="cpn-prog-meta">
              <span>已发 {{ formatCount(row.used_count || 0) }} / {{ row.total_count ? formatCount(row.total_count) : '不限' }}</span>
              <span class="cpn-prog-pct num">{{ usageRate(row) }}%</span>
            </div>
            <div class="progbar"><i :style="{ width: usageRate(row) + '%' }" /></div>
          </div>
          <div class="tbl-acts cpn-acts">
            <el-button type="primary" size="small" text @click="handleEdit(row)">编辑</el-button>
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatusChange(row)"
            />
            <el-button type="danger" size="small" text @click="handleDelete(row.id, row.name)">删除</el-button>
          </div>
        </div>
      </div>

      <div v-if="!loading && !tableData.length" class="cpn-empty">
        <el-empty description="暂无优惠券，点击右上角新建" />
      </div>
    </div>

    <!-- 卡片视图分页 -->
    <div v-if="viewMode === 'card' && tableData.length" class="cpn-pagination">
      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.limit"
        :total="pagination.total"
        :page-sizes="[12, 24, 48]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
      />
    </div>

    <!-- 表格视图 -->
    <el-card v-if="viewMode === 'table'" class="table-card" shadow="never">
      <el-table v-loading="loading" :data="tableData">
        <el-table-column label="ID" prop="id" width="70" />

        <el-table-column label="优惠券名称" prop="name" min-width="150" show-overflow-tooltip />

        <el-table-column label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="typeTagMap[row.type]?.type" size="small">
              {{ typeTagMap[row.type]?.label || row.type }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column label="面值/折扣" width="120">
          <template #default="{ row }">
            <span v-if="row.type === 'percent'" class="value-text">{{ row.value }}折</span>
            <span v-else class="value-text price">¥{{ formatPrice(row.value) }}</span>
          </template>
        </el-table-column>

        <el-table-column label="使用门槛" width="120">
          <template #default="{ row }">
            <span v-if="row.min_amount > 0">满¥{{ formatPrice(row.min_amount) }}</span>
            <span v-else class="text-secondary">无门槛</span>
          </template>
        </el-table-column>

        <el-table-column label="发放/使用" width="120">
          <template #default="{ row }">
            <span>{{ row.total_count ?? '不限' }} / {{ row.used_count ?? 0 }}</span>
          </template>
        </el-table-column>

        <el-table-column label="有效期" min-width="200">
          <template #default="{ row }">
            <span v-if="row.start_at && row.end_at">{{ row.start_at }} ~ {{ row.end_at }}</span>
            <span v-else class="text-secondary">-</span>
          </template>
        </el-table-column>

        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatusChange(row)"
            />
          </template>
        </el-table-column>

        <el-table-column label="操作" width="160" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" size="small" text @click="handleEdit(row)">编辑</el-button>
            <el-button type="danger" size="small" text @click="handleDelete(row.id, row.name)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.limit"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
        class="pagination"
      />
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <CouponForm
      v-model="dialogVisible"
      :form-data="currentRow"
      @success="fetchList"
    />
  </div>
</template>

<script setup lang="ts" name="CouponList">
import { ElMessage } from 'element-plus'
import { computed, ref } from 'vue'
import { couponApi } from '@/api/marketing'
import { useListPage } from '@/hooks/useListPage'
import type { CouponInfo, CouponQuery } from '@/types/api'
import CouponForm from './components/CouponForm.vue'

// ─── 类型映射 ─────────────────────────────────────────────────────────────────
const typeTagMap: Record<string, { label: string; type: 'primary' | 'success' | 'warning' | 'info' | 'danger' }> = {
  fixed: { label: '满减券', type: 'primary' },
  percent: { label: '折扣券', type: 'warning' },
  no_threshold: { label: '无门槛', type: 'success' },
}

// ─── 视图模式 ─────────────────────────────────────────────────────────────────
const viewMode = ref<'card' | 'table'>('card')

// ─── 列表 ─────────────────────────────────────────────────────────────────────
const {
  list: tableData,
  loading,
  pagination,
  searchForm,
  getList: fetchList,
  handleSearch,
  resetSearch: handleReset,
  handlePageChange,
  handleSizeChange,
  handleDelete,
} = useListPage<CouponInfo, CouponQuery>({
  fetchFn: (params) => couponApi.getCouponList(params),
  deleteFn: (id) => couponApi.deleteCoupon(id),
  defaultSearchForm: { keyword: '', type: undefined, status: undefined },
  pageSize: 12,
})

// 优惠券面值显示
const formatCouponValue = (row: CouponInfo) => {
  if (row.type === 'percent') return `${row.value} 折`
  if (row.min_amount > 0) {
    return `¥ ${formatPrice(row.value)} / ${formatPrice(row.min_amount)}`
  }
  return `¥ ${formatPrice(row.value)}`
}

// 有效期显示
const formatPeriod = (row: CouponInfo) => {
  if (!row.start_at && !row.end_at) return '长期有效'
  return `${row.start_at || '即日起'} ~ ${row.end_at || '不限'}`
}

// 使用率
const usageRate = (row: CouponInfo) => {
  if (!row.total_count || row.total_count <= 0) return 0
  return Math.min(100, Math.round(((row.used_count || 0) / row.total_count) * 100))
}

// 当前页统计概览（KPI mini）
const stats = computed(() => {
  let enabled = 0, fixed = 0, percent = 0
  for (const row of tableData.value) {
    if (row.status === 1) enabled += 1
    if (row.type === 'fixed') fixed += 1
    if (row.type === 'percent') percent += 1
  }
  return { enabled, fixed, percent }
})

const formatCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')

// ─── 状态切换 ─────────────────────────────────────────────────────────────────
const handleStatusChange = async (row: any) => {
  try {
    await couponApi.updateCoupon(row.id, { status: row.status })
    ElMessage.success('状态更新成功')
  } catch (e) {
    row.status = row.status === 1 ? 0 : 1
    console.error('状态更新失败:', e)
  }
}

// ─── 弹窗（已抽取为独立组件 CouponForm）───────────────────────────────────────
const dialogVisible = ref(false)
const currentRow = ref<Partial<CouponInfo>>({})

const handleAdd = () => {
  currentRow.value = {}
  dialogVisible.value = true
}

const handleEdit = (row: any) => {
  currentRow.value = { ...row }
  dialogVisible.value = true
}

// ─── 工具 ─────────────────────────────────────────────────────────────────────
const formatPrice = (v: number) => (v == null ? '0.00' : Number(v).toFixed(2))
</script>

<style lang="scss" scoped>
.coupon-container {
  .stats-row {
    margin-bottom: 14px;
  }

  .value-text {
    font-weight: 500;
    &.price { color: #f5222d; }
  }

  .text-secondary { color: var(--el-text-color-secondary); }

  .form-hint {
    margin-left: 8px;
    font-size: 12px;
    color: var(--el-text-color-secondary);
  }
}

.cpn-filter {
  justify-content: flex-start;
  gap: 10px;
}

.cpn-seg {
  margin-right: auto;
}

.cpn-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;

  @media (max-width: 1280px) {
    grid-template-columns: repeat(2, 1fr);
  }
  @media (max-width: 768px) {
    grid-template-columns: 1fr;
  }
}

.cpn-card {
  position: relative;
  transition: transform 0.15s, box-shadow 0.15s;

  &:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
  }
}

.cpn-l {
  position: relative;
  overflow: hidden;
}

.cpn-l-body {
  position: relative;
  z-index: 1;
}

.cpn-glow {
  position: absolute;
  pointer-events: none;
  border-radius: 50%;

  &.cpn-glow-1 {
    top: -24px;
    left: -24px;
    width: 80px;
    height: 80px;
    background: radial-gradient(circle, rgba(255, 220, 180, 0.55) 0%, rgba(255, 180, 120, 0) 70%);
  }

  &.cpn-glow-2 {
    bottom: -30px;
    right: -20px;
    width: 90px;
    height: 90px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.18) 0%, rgba(255, 255, 255, 0) 70%);
  }
}

.cpn-type-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 10px;
  background: rgba(0, 0, 0, 0.22);
  border: 1px solid rgba(255, 255, 255, 0.18);
  font-size: 11px;
  letter-spacing: 0.04em;
  color: #fff;
}

.cpn-val {
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.18);
}

.cpn-period {
  font-size: 10.5px;
  color: #fff;
  opacity: 0.78;
  margin-top: 6px;
  letter-spacing: 0.02em;
}

.cpn-r-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
}

.cpn-r-info {
  min-width: 0;
  flex: 1;
}

.cpn-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-900);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cpn-id {
  font-size: 11px;
  color: var(--ink-400);
  margin-top: 3px;
}

.cpn-prog-wrap {
  margin-top: 10px;
}

.cpn-prog-meta {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  color: var(--ink-500);
  margin-bottom: 4px;
}

.cpn-prog-pct {
  font-weight: 600;
  color: var(--brand-500);
}

.cpn-acts {
  margin-top: 10px;
  align-items: center;
  gap: 8px;
}

.cpn-empty {
  grid-column: 1 / -1;
  padding: 40px 0;
}

.cpn-pagination {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}
</style>
