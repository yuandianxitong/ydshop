<script setup lang="ts" name="DeliveryStaffList">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'

import { deliveryStaffApi } from '@/api/delivery'
import { useExport } from '@/composables/useExport'
import { useListPage } from '@/hooks/useListPage'
import { useUserStore } from '@/store'
import type { DeliveryStaffInfo, DeliveryStaffQuery } from '@/types/api'

import DeliveryStaffForm from './components/DeliveryStaffForm.vue'

const userStore = useUserStore()
const router = useRouter()

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
  handleBatchDelete: doBatchDelete,
  handleStatusChange,
} = useListPage<DeliveryStaffInfo, DeliveryStaffQuery>({
  fetchFn: (params) => deliveryStaffApi.getList(params),
  deleteFn: (id) => deliveryStaffApi.delete(id),
  batchDeleteFn: (ids) => deliveryStaffApi.batchDelete({ ids }),
  updateStatusFn: (id, status) => deliveryStaffApi.updateStatus(id, { status }),
  defaultSearchForm: { keyword: '', status: undefined },
})

const multipleSelection = ref<DeliveryStaffInfo[]>([])
const formVisible = ref(false)
const formData = ref<Partial<DeliveryStaffInfo>>({})

const fmtCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')

const stats = computed(() => {
  let enabled = 0, disabled = 0
  for (const row of tableData.value) {
    if (row.status === 1) enabled += 1
    else disabled += 1
  }
  return { enabled, disabled }
})

const handleSelectionChange = (sel: DeliveryStaffInfo[]) => { multipleSelection.value = sel }

// el-switch @change 与 useListPage.handleStatusChange 直接对接
const handleStatusToggle = (row: any) => handleStatusChange(row)

const handleAdd = () => { formData.value = { status: 1 }; formVisible.value = true }
const handleEdit = (row: any) => { formData.value = { ...row }; formVisible.value = true }

const handleBatchDelete = () => doBatchDelete(multipleSelection.value.map((i) => i.id))

const { exporting: exportingStaff, doExport: doExportStaff } = useExport()
const handleExport = () => {
  doExportStaff('/adminapi/delivery/staff/export', searchForm, '配送员花名册')
}

// 头像首字渐变
const avatarText = (row: any) => (row.name || '?').charAt(0)
const avatarTones = ['#cbd5e1', '#94a3b8', '#a5b4fc', '#fca5a5', '#fcd34d', '#86efac']
const avatarTone = (row: any) => {
  let h = 0
  for (const ch of String(row.id ?? row.name ?? '')) h = (h * 31 + ch.charCodeAt(0)) | 0
  return avatarTones[Math.abs(h) % avatarTones.length]
}
</script>

<template>
  <div class="delivery-staff-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">配送员管理</h2>
        <p class="page-desc">同城配送骑手、自提送货员的人员档案与绩效</p>
      </div>
      <div class="page-actions">
        <el-button :loading="exportingStaff" @click="handleExport">导出花名册</el-button>
        <el-button @click="router.push('/delivery/shift')">排班调度</el-button>
        <el-button
          type="primary"
          v-has-perm="['delivery.staff.create']"
          @click="handleAdd"
        >
          <i class="i-lucide:plus mr-1" /> 新增配送员
        </el-button>
      </div>
    </div>

    <!-- KPI -->
    <div class="row-14">
      <div class="kpi-mini">
        <div class="lb">配送员总数</div>
        <div class="nm num">{{ fmtCount(pagination.total) }}</div>
        <div class="tr"><span style="color:var(--ink-500)">分页统计</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">在岗</div>
        <div class="nm num">{{ fmtCount(stats.enabled) }}</div>
        <div class="tr"><span style="color:var(--success)">本页统计</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">已离岗</div>
        <div class="nm num">{{ fmtCount(stats.disabled) }}</div>
        <div class="tr"><span style="color:var(--rose-500)">本页统计</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">今日完单</div>
        <div class="nm num">—</div>
        <div class="tr"><span style="color:var(--ink-500)">待对接配送系统</span></div>
      </div>
    </div>

    <!-- filter-bar -->
    <div class="filter-bar">
      <el-input
        v-model="searchForm.keyword"
        placeholder="搜索姓名 / 手机号"
        clearable
        style="width: 240px"
        @keyup.enter="handleSearch"
      />
      <span class="filter-label">状态：</span>
      <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 120px">
        <el-option label="在岗" :value="1" />
        <el-option label="休息" :value="0" />
      </el-select>
      <span class="filter-sp" />
      <el-button @click="handleReset">重置</el-button>
      <el-button type="primary" @click="handleSearch">查询</el-button>
    </div>

    <!-- 表格 -->
    <el-card class="table-card" shadow="never">
      <div class="table-header">
        <span class="table-title">配送员花名册 <span class="table-count">共 {{ fmtCount(pagination.total) }} 人</span></span>
        <div class="table-actions">
          <el-button
            :disabled="!multipleSelection.length"
            v-has-perm="['delivery.staff.delete']"
            @click="handleBatchDelete"
          >
            <i class="i-svg:trash-2 mr-1" /> 批量删除
          </el-button>
        </div>
      </div>

      <el-table
        v-loading="loading"
        :data="tableData"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="50" />

        <el-table-column label="工号" width="100">
          <template #default="{ row }"><span class="num text-brand">R-{{ row.id }}</span></template>
        </el-table-column>

        <el-table-column label="姓名" min-width="140">
          <template #default="{ row }">
            <div class="user-cell">
              <div class="av small" :style="{ background: 'linear-gradient(135deg,' + avatarTone(row) + ',#94a3b8)' }">
                {{ avatarText(row) }}
              </div>
              <span class="staff-name">{{ row.name || '-' }}</span>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="联系方式" prop="phone" width="140">
          <template #default="{ row }"><span class="num text-secondary">{{ row.phone || '-' }}</span></template>
        </el-table-column>

        <el-table-column label="负责片区" min-width="160">
          <template #default="{ row }"><span class="text-secondary">{{ row.area || '—' }}</span></template>
        </el-table-column>

        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              :disabled="!userStore.hasPermission('delivery.staff.update')"
              @change="() => handleStatusToggle(row)"
            />
          </template>
        </el-table-column>

        <el-table-column label="入职日期" prop="created_at" width="200">
          <template #default="{ row }"><span class="num text-secondary">{{ row.created_at }}</span></template>
        </el-table-column>

        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <div class="flex gap-1">
              <el-button
                text type="primary" size="small"
                v-has-perm="['delivery.staff.update']"
                @click="handleEdit(row)"
              >编辑</el-button>
              <el-button
                text type="danger" size="small"
                v-has-perm="['delivery.staff.delete']"
                @click="handleDelete(row.id, row.name)"
              >删除</el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.limit"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        class="pagination"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
      />
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <DeliveryStaffForm
      v-model="formVisible"
      :form-data="formData"
      @success="fetchList"
    />
  </div>
</template>

<style lang="scss" scoped>
.delivery-staff-container {
  padding: 0;
}

// .user-cell + .av 走 shop-extras.scss 全局；保留 small 变体
.user-cell .av.small { width: 28px; height: 28px; font-size: 11.5px; }

.staff-name { font-weight: 500; color: var(--ink-900); font-size: 13px; }


.filter-label {
  font-size: 12px;
  color: var(--ink-500);
  margin-left: 4px;
}
</style>
