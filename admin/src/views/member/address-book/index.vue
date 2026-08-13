<template>
  <div class="address-book-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">地址簿</h2>
        <p class="page-desc">会员收货地址统一管理</p>
      </div>
      <div class="page-actions">
        <el-button :loading="exportingAddr" @click="handleExport"><i class="i-svg:download" /> 导出</el-button>
      </div>
    </div>

    <!-- KPI -->
    <div class="row-14">
      <div v-for="card in kpiCards" :key="card.label" class="kpi-mini">
        <div class="lb">{{ card.label }}</div>
        <div class="nm num">{{ card.value }}</div>
        <div class="tr"><span :class="card.tone">{{ card.suffix }}</span></div>
      </div>
    </div>

    <!-- 单行过滤栏 -->
    <div class="filter-bar">
      <el-input
        v-model="searchForm.keyword"
        placeholder="搜索收件人 / 手机 / 地址"
        clearable
        style="width: 280px"
        @keyup.enter="handleSearch"
      />
      <span class="filter-label">默认：</span>
      <el-select v-model="searchForm.is_default" placeholder="全部" clearable style="width: 110px" @change="handleSearch">
        <el-option label="是" value="1" />
        <el-option label="否" value="0" />
      </el-select>
      <span class="filter-sp" />
      <el-button @click="handleResetAll">重置</el-button>
      <el-button type="primary" @click="handleSearch">查询</el-button>
    </div>

    <!-- 表格 -->
    <ProTable
      title="地址明细"
      storage-key="address-book-list"
      :columns="columns"
      :data="list"
      :loading="loading"
      :pagination="pagination"
      :show-column-config="false"
      :show-batch-delete="false"
      @page-change="handlePageChange"
      @size-change="handleSizeChange"
    >
      <template #user="{ row }">
        <div class="user-cell">
          <el-avatar :size="28" :src="row.user_avatar || ''" class="user-cell__avatar">
            {{ (row.user_nickname || row.user_mobile || '?')[0] }}
          </el-avatar>
          <div class="user-cell__info">
            <div class="user-cell__name">{{ row.user_nickname || '—' }}</div>
            <div v-if="row.user_mobile" class="user-cell__mobile num">{{ row.user_mobile }}</div>
          </div>
        </div>
      </template>

      <template #name="{ row }">
        <span>{{ row.name || '—' }}</span>
      </template>

      <template #phone="{ row }">
        <span class="num">{{ row.phone || '—' }}</span>
      </template>

      <template #full_address="{ row }">
        <span class="addr-cell">
          <i class="i-svg:map-pin addr-icon" />
          {{ formatAddress(row) }}
        </span>
      </template>

      <template #is_default="{ row }">
        <el-tag v-if="row.is_default" type="success" size="small">默认</el-tag>
        <span v-else class="text-ink-400">—</span>
      </template>

      <template #created_at="{ row }">
        <span class="num">{{ row.created_at }}</span>
      </template>

      <template #action="{ row }">
        <el-button v-has-perm="['member.address.delete']" type="danger" size="small" text @click="handleDelete(row)">
          删除
        </el-button>
      </template>
    </ProTable>
  </div>
</template>

<script setup lang="ts" name="AddressBookList">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted, reactive, ref } from 'vue'

import { addressBookApi, type AddressInfo, type AddressStats } from '@/api/address-book'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useExport } from '@/composables/useExport'
import { useListPage } from '@/hooks/useListPage'

interface AddressSearchForm {
  keyword: string
  is_default: string
}

const {
  list,
  loading,
  pagination,
  searchForm,
  getList,
  handleSearch,
  resetSearch,
  handlePageChange,
  handleSizeChange,
} = useListPage<AddressInfo, AddressSearchForm>({
  fetchFn: (params) => addressBookApi.getList(params),
  defaultSearchForm: { keyword: '', is_default: '' },
})

const handleResetAll = () => resetSearch()

const stats = reactive<AddressStats>({ total: 0, default: 0, users: 0, avg: 0 })

const loadStats = async () => {
  try {
    const res = await addressBookApi.getStats()
    Object.assign(stats, (res as any).data || {})
  } catch (e) {
    console.error(e)
  }
}

const formatCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')

const kpiCards = computed(() => [
  { label: '总地址数', value: formatCount(stats.total), suffix: `平均 ${stats.avg} / 人`, tone: '' },
  { label: '默认地址', value: formatCount(stats.default), suffix: stats.users > 0 ? `${Math.round((stats.default / stats.users) * 100)}% 覆盖` : '0%', tone: '' },
  { label: '会员数', value: formatCount(stats.users), suffix: '有地址会员', tone: '' },
  { label: '当前页', value: formatCount(list.value.length), suffix: '条记录', tone: '' },
])

const columns: ProColumn[] = [
  { key: 'user', label: '会员', width: 200, required: true },
  { key: 'name', label: '收件人', width: 100 },
  { key: 'phone', label: '手机', width: 130 },
  { key: 'full_address', label: '详细地址', minWidth: 280 },
  { key: 'is_default', label: '默认', width: 100 },
  { key: 'created_at', label: '创建时间', width: 200 },
  { key: 'action', label: '操作', width: 100, fixed: 'right', required: true },
]

const formatAddress = (row: AddressInfo) => {
  const parts = [row.province, row.city, row.district, row.detail].filter(Boolean)
  return parts.join(' ')
}

const handleDelete = async (row: AddressInfo) => {
  try {
    await ElMessageBox.confirm('确定要删除该地址吗？', '删除确认', {
      confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning',
    })
    await addressBookApi.delete(row.id)
    ElMessage.success('删除成功')
    await Promise.all([getList(), loadStats()])
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

const { exporting: exportingAddr, doExport: doExportAddr } = useExport()
const handleExport = () => {
  doExportAddr('/adminapi/member/address/export', searchForm, '会员地址簿')
}

onMounted(() => {
  loadStats()
})
</script>

<style lang="scss" scoped>
.address-book-container {
  .user-cell {
    display: flex;
    align-items: center;
    gap: 8px;

    &__avatar { flex-shrink: 0; }
    &__info { line-height: 1.3; min-width: 0; }
    &__name { font-size: 13px; color: var(--ink-900); }
    &__mobile { font-size: 11px; color: var(--ink-400); }
  }
  .addr-cell { display: inline-flex; align-items: center; gap: 6px; }
  .addr-icon { width: 13px; height: 13px; color: var(--ink-400, #a3acba); }
}
</style>
