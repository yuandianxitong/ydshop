<script setup lang="ts" name="ExpressCompanyList">
import { ref } from 'vue'
import { expressCompanyApi } from '@/api/express-company'
import { useListPage } from '@/hooks/useListPage'
import { useUserStore } from '@/store'
import type { ExpressCompanyInfo, ExpressCompanyQuery } from '@/types/api'
import ExpressCompanyForm from './components/ExpressCompanyForm.vue'

const userStore = useUserStore()

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
  handleStatusChange,
} = useListPage<ExpressCompanyInfo, ExpressCompanyQuery>({
  fetchFn: (params) => expressCompanyApi.getList(params),
  deleteFn: (id) => expressCompanyApi.delete(id),
  updateStatusFn: (id, status) => expressCompanyApi.updateStatus(id, { status }),
  defaultSearchForm: { keyword: '', status: undefined },
  pageSize: 24,
})

// 物流商品牌色 — 通过编码 hash 映射调色板（红 / 橙 / 黄 / 绿 系）
const palette = ['#dc2626', '#ef4444', '#ea580c', '#f97316', '#f59e0b', '#eab308', '#16a34a', '#10b981']
const codeColor = (code: string) => {
  let h = 0
  for (const ch of String(code || '')) h = (h * 31 + ch.charCodeAt(0)) | 0
  return palette[Math.abs(h) % palette.length]
}

// 弹窗
const formVisible = ref(false)
const formData = ref<Partial<ExpressCompanyInfo>>({})

const handleAdd = () => { formData.value = { status: 1 }; formVisible.value = true }
const handleEdit = (row: ExpressCompanyInfo) => { formData.value = { ...row }; formVisible.value = true }

const handleStatusToggle = (row: ExpressCompanyInfo) => handleStatusChange(row)
</script>

<template>
  <div class="express-company-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">物流公司</h2>
        <p class="page-desc">对接的物流商、运单与轨迹查询</p>
      </div>
      <div class="page-actions">
        <el-button
          type="primary"
          v-has-perm="['delivery.express.create']"
          @click="handleAdd"
        >
          <i class="i-lucide:plus mr-1" /> 新增物流商
        </el-button>
      </div>
    </div>

    <!-- filter-bar -->
    <div class="filter-bar">
      <el-input
        v-model="searchForm.keyword"
        placeholder="搜索物流商名称 / 编码"
        clearable
        style="width: 240px"
        @keyup.enter="handleSearch"
      />
      <span class="filter-label">状态：</span>
      <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 120px">
        <el-option label="启用" :value="1" />
        <el-option label="禁用" :value="0" />
      </el-select>
      <span class="filter-sp" />
      <el-button @click="handleReset">重置</el-button>
      <el-button type="primary" @click="handleSearch">查询</el-button>
    </div>

    <!-- 物流商卡片网格 -->
    <div class="row-13 carrier-grid" v-loading="loading">
      <el-card v-for="c in tableData" :key="c.id" class="carrier-card" shadow="never">
        <div class="carrier-body">
          <div class="carrier-head">
            <div class="carrier-logo">
              <el-image v-if="c.logo" :src="c.logo" fit="contain" class="carrier-logo-img" />
              <div v-else class="carrier-logo-fallback" :style="{ background: codeColor(c.code) }">
                {{ String(c.code || '').slice(0, 3).toUpperCase() || '?' }}
              </div>
            </div>
            <div class="carrier-info">
              <div class="carrier-name-row">
                <span class="carrier-name">{{ c.name }}</span>
                <el-tag v-if="c.is_default" class="tag-tone-green carrier-default-chip" size="small" effect="light">默认</el-tag>
              </div>
              <el-tag class="carrier-code-chip num" size="small" effect="plain">{{ c.code || '-' }}</el-tag>
            </div>
            <el-switch
              v-model="c.status"
              :active-value="1"
              :inactive-value="0"
              :disabled="!userStore.hasPermission('delivery.express.update')"
              @change="handleStatusToggle(c)"
            />
          </div>

          <div class="carrier-foot">
            <el-button text size="small" v-has-perm="['delivery.express.update']" @click="handleEdit(c)">编辑</el-button>
            <el-button text type="danger" size="small" v-has-perm="['delivery.express.delete']" @click="handleDelete(c.id, c.name)">删除</el-button>
          </div>
        </div>
      </el-card>

      <el-empty v-if="!loading && !tableData.length" description="暂无物流商" :image-size="80" style="grid-column: 1 / -1" />
    </div>

    <!-- 分页 -->
    <div v-if="pagination.total > pagination.limit" class="pagination-wrap">
      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.limit"
        :total="pagination.total"
        :page-sizes="[12, 24, 48, 96]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
      />
    </div>

    <!-- 新增/编辑弹窗 -->
    <ExpressCompanyForm
      v-model="formVisible"
      :form-data="formData"
      @success="fetchList"
    />
  </div>
</template>

<style lang="scss" scoped>
.express-company-container {
  padding: 0;
}

// ── 物流商卡片 ──
.carrier-grid {
  margin-bottom: 14px;
}

.carrier-card {
  position: relative;
  transition: border-color .15s, box-shadow .15s, transform .15s;

  :deep(.el-card__body) { padding: 0; }

  &:hover {
    border-color: var(--brand-300, #a5b4fc);
    box-shadow: 0 6px 20px rgba(15, 23, 42, .08);
  }
}

.carrier-body {
  padding: 16px 18px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.carrier-head {
  display: flex;
  align-items: center;
  gap: 12px;
}

.carrier-logo {
  width: 44px;
  height: 44px;
  border-radius: 8px;
  flex-shrink: 0;
  overflow: hidden;
  background: var(--ink-50);
  border: 1px solid var(--ink-100);
  display: flex;
  align-items: center;
  justify-content: center;
}

.carrier-logo-img {
  width: 100%;
  height: 100%;
}

.carrier-logo-fallback {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.4px;
  color: #fff;
}

.carrier-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.carrier-name-row {
  display: flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
}

.carrier-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-900);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  min-width: 0;
}

.carrier-default-chip {
  flex-shrink: 0;
}

.carrier-code-chip {
  align-self: flex-start;
  font-size: 11px;
  letter-spacing: .3px;
  --el-tag-bg-color: transparent;
}

.carrier-foot {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 4px;
  padding-top: 10px;
  border-top: 1px solid var(--ink-100);
}

.pagination-wrap {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 14px;
}


.filter-label {
  font-size: 12px;
  color: var(--ink-500);
  margin-left: 4px;
}
</style>
