<template>
  <div class="full-discount-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">促销活动</h2>
        <p class="page-desc">满减、满赠、第 N 件半价、买 N 送 M</p>
      </div>
    </div>

    <!-- 搜索区域 -->
    <div class="filter-bar">
      <el-input
        v-model="searchForm.keyword"
        placeholder="搜索活动名称"
        clearable
        style="width: 220px"
        @keyup.enter="handleSearch"
      />
      <span class="filter-label">优惠类型：</span>
      <el-select v-model="searchForm.type" placeholder="全部" clearable style="width: 140px" @change="handleSearch">
        <el-option label="满减" value="reduce" />
        <el-option label="满折" value="percent" />
        <el-option label="包邮" value="freight" />
      </el-select>
      <span class="filter-label">状态：</span>
      <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 130px" @change="handleSearch">
        <el-option label="启用" :value="1" />
        <el-option label="禁用" :value="0" />
      </el-select>
      <span class="filter-sp" />
      <el-button @click="handleReset">重置</el-button>
      <el-button type="primary" @click="handleSearch">查询</el-button>
    </div>

    <!-- 表格区域 -->
    <el-card class="table-card" shadow="never">
      <div class="table-header">
        <div class="table-title">满减活动管理</div>
        <div class="table-actions">
          <el-button type="primary" @click="handleAdd">
            <i class="i-lucide:plus mr-1" />
            新增满减活动
          </el-button>
        </div>
      </div>

      <el-table v-loading="loading" :data="tableData">
        <el-table-column label="ID" prop="id" width="70" />

        <el-table-column label="活动名称" prop="name" min-width="150" show-overflow-tooltip />

        <el-table-column label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="typeTagMap[row.type]?.type" size="small">
              {{ typeTagMap[row.type]?.label || row.type }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column label="规则摘要" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            <span v-if="row.rules && row.rules.length">
              {{ formatRulesSummary(row.rules, row.type) }}
            </span>
            <span v-else class="text-secondary">-</span>
          </template>
        </el-table-column>

        <el-table-column label="适用范围" width="120">
          <template #default="{ row }">
            {{ scopeLabel[row.use_scope] || row.use_scope }}
          </template>
        </el-table-column>

        <el-table-column label="有效期" min-width="200">
          <template #default="{ row }">
            <span v-if="row.start_at && row.end_at">{{ row.start_at }} ~ {{ row.end_at }}</span>
            <span v-else class="text-secondary">-</span>
          </template>
        </el-table-column>

        <el-table-column label="可叠加" width="100">
          <template #default="{ row }">
            <el-tag :type="row.stackable ? 'success' : 'info'" size="small">
              {{ row.stackable ? '是' : '否' }}
            </el-tag>
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
    <FullDiscountForm
      v-model="dialogVisible"
      :form-data="currentRow"
      @success="fetchList"
    />
  </div>
</template>

<script setup lang="ts" name="FullDiscountList">
import { ElMessage } from 'element-plus'
import { ref } from 'vue'
import { fullDiscountApi } from '@/api/marketing'
import { useListPage } from '@/hooks/useListPage'
import type { FullDiscountInfo, FullDiscountQuery } from '@/types/api'
import FullDiscountForm from './components/FullDiscountForm.vue'

// ─── 映射 ─────────────────────────────────────────────────────────────────────
const typeTagMap: Record<string, { label: string; type: 'primary' | 'success' | 'warning' | 'info' | 'danger' }> = {
  reduce: { label: '满减', type: 'primary' },
  percent: { label: '满折', type: 'warning' },
  freight: { label: '包邮', type: 'success' },
}

const scopeLabel: Record<string, string> = {
  all: '全部商品',
  category: '指定分类',
  spu: '指定商品',
}

const formatRulesSummary = (rules: any[], type: string) => {
  if (!rules.length) return '-'
  const r = rules[0]
  if (type === 'reduce') return `满¥${r.min_amount}减¥${r.value}${rules.length > 1 ? `…共${rules.length}档` : ''}`
  if (type === 'percent') return `满¥${r.min_amount}打${r.value}折${rules.length > 1 ? `…共${rules.length}档` : ''}`
  return `满¥${r.min_amount}包邮${rules.length > 1 ? `…共${rules.length}档` : ''}`
}

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
} = useListPage<FullDiscountInfo, FullDiscountQuery>({
  fetchFn: (params) => fullDiscountApi.getFullDiscountList(params),
  deleteFn: (id) => fullDiscountApi.deleteFullDiscount(id),
  defaultSearchForm: { keyword: '', type: undefined, status: undefined },
})

// ─── 状态 ─────────────────────────────────────────────────────────────────────
const handleStatusChange = async (row: Record<string, any>) => {
  try {
    await fullDiscountApi.updateFullDiscount(row.id, { status: row.status })
    ElMessage.success('状态更新成功')
  } catch (e) {
    row.status = row.status === 1 ? 0 : 1
    console.error('状态更新失败:', e)
  }
}

// ─── 弹窗（已抽取为独立组件 FullDiscountForm）─────────────────────────────────
const dialogVisible = ref(false)
const currentRow = ref<Partial<FullDiscountInfo>>({})

const handleAdd = () => {
  currentRow.value = {}
  dialogVisible.value = true
}

const handleEdit = (row: any) => {
  currentRow.value = { ...row }
  dialogVisible.value = true
}

// useListPage 已在 mounted 自动加载
</script>

<style lang="scss" scoped>
.full-discount-container {
  .stats-row {
    margin-bottom: 14px;
  }

  .text-secondary { color: var(--el-text-color-secondary); }

  .rules-editor {
    width: 100%;

    .rule-row {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 8px;

      .rule-label {
        color: var(--el-text-color-regular);
        white-space: nowrap;

        &.rule-freight {
          font-weight: 500;
          color: var(--el-color-success);
        }
      }
    }
  }

  .form-hint {
    margin-left: 8px;
    font-size: 12px;
    color: var(--el-text-color-secondary);
  }
}
</style>
