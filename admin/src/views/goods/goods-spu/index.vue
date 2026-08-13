<template>
  <div class="goods-spu-container">
    <!-- 编辑抽屉 -->
    <el-drawer
      v-model="drawerVisible"
      :title="editId ? '编辑商品' : '新增商品'"
      size="80%"
      :destroy-on-close="true"
      @closed="handleDrawerClosed"
    >
      <SpuEditForm
        v-if="drawerVisible"
        :spu-id="editId"
        @success="handleEditSuccess"
        @cancel="drawerVisible = false"
      />
    </el-drawer>

    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">商品列表</h2>
        <p class="page-desc">商品的统一管理与上下架</p>
      </div>
      <div class="page-actions">
        <el-button @click="handleImport">
          <i class="i-svg:upload" /> 批量导入
        </el-button>
        <el-button :loading="exporting" @click="handleExport">
          <i class="i-svg:download" /> 导出
        </el-button>
        <el-button v-has-perm="['goods.goods-spu.create']" type="primary" @click="handleAdd">
          <i class="i-svg:plus" /> 新建商品
        </el-button>
      </div>
    </div>

    <!-- 统计概览 -->
    <div class="mini-grid mini-grid-4 stats-row">
      <div class="kpi-mini tone-blue">
        <div class="lb"><span class="ic">总</span>全部商品</div>
        <div class="nm num">{{ formatCount(pagination.total) }}</div>
        <div class="tr">分页统计</div>
      </div>
      <div class="kpi-mini tone-teal">
        <div class="lb"><span class="ic">售</span>已上架</div>
        <div class="nm num">{{ formatCount(stats.on_sale) }}</div>
        <div class="tr">当前页内</div>
      </div>
      <div class="kpi-mini tone-amber">
        <div class="lb"><span class="ic">稿</span>草稿</div>
        <div class="nm num">{{ formatCount(stats.draft) }}</div>
        <div class="tr">当前页内</div>
      </div>
      <div class="kpi-mini tone-rose">
        <div class="lb"><span class="ic">下</span>已下架</div>
        <div class="nm num">{{ formatCount(stats.off_sale) }}</div>
        <div class="tr">当前页内</div>
      </div>
    </div>

    <!-- 搜索区域 -->
    <div class="filter-bar">
      <el-input
        v-model="searchForm.keyword"
        placeholder="搜索商品名称 / SPU 编号"
        clearable
        style="width: 240px"
        @keyup.enter="handleSearch"
      />
      <span class="filter-label">分类：</span>
      <el-tree-select
        v-model="searchForm.category_id"
        :data="categoryTree"
        :props="({ label: 'name', value: 'id', children: 'children' } as any)"
        placeholder="全部"
        clearable
        check-strictly
        style="width: 200px"
        @change="handleSearch"
      />
      <span class="filter-label">状态：</span>
      <el-select
        v-model="searchForm.status"
        placeholder="全部"
        clearable
        style="width: 140px"
        @change="handleSearch"
      >
        <el-option label="草稿" value="draft" />
        <el-option label="已上架" value="on_sale" />
        <el-option label="已下架" value="off_sale" />
      </el-select>
      <span class="filter-label">类型：</span>
      <el-select
        v-model="searchForm.type"
        placeholder="全部"
        clearable
        style="width: 130px"
        @change="handleSearch"
      >
        <el-option label="实物" value="physical" />
        <el-option label="虚拟" value="virtual" />
        <el-option label="组合" value="combo" />
      </el-select>
      <span class="filter-sp" />
      <el-button @click="resetSearch">重置</el-button>
      <el-button type="primary" @click="handleSearch">查询</el-button>
    </div>

    <!-- 表格 -->
    <ProTable
      title="商品列表"
      storage-key="goods-spu-list"
      :columns="columns"
      :data="list"
      :loading="loading"
      :pagination="pagination"
      selectable
      :show-column-config="false"
      @page-change="handlePageChange"
      @size-change="handleSizeChange"
    >
      <template #headerExtra="{ selectedIds, clearSelection }">
        <div class="seg goods-status-seg">
          <button
            v-for="opt in statusFilterOptions"
            :key="opt.value ?? 'all'"
            :class="{ on: searchForm.status === opt.value }"
            @click="handleStatusFilter(opt.value)"
          >
            {{ opt.label }}
          </button>
        </div>
        <el-button
          v-has-perm="['goods.goods-spu.update']"
          :disabled="!selectedIds.length"
          @click="handleBatchOnSale(selectedIds, clearSelection)"
        >
          批量上架
        </el-button>
        <el-button
          v-has-perm="['goods.goods-spu.update']"
          :disabled="!selectedIds.length"
          @click="handleBatchOffSale(selectedIds, clearSelection)"
        >
          批量下架
        </el-button>
        <el-button
          v-has-perm="['goods.goods-spu.update']"
          :disabled="!selectedIds.length"
          @click="handleBatchPickup('enable_pickup', selectedIds, clearSelection)"
        >
          批量开启自提
        </el-button>
        <el-button
          v-has-perm="['goods.goods-spu.update']"
          :disabled="!selectedIds.length"
          @click="handleBatchPickup('disable_pickup', selectedIds, clearSelection)"
        >
          批量关闭自提
        </el-button>
      </template>

      <template #info="{ row }">
        <div class="product-info">
          <el-image
            v-if="row.images && row.images.length"
            :src="row.images[0]"
            :preview-src-list="row.images"
            :preview-teleported="true"
            class="product-thumb"
            fit="cover"
            style="cursor: pointer"
          />
          <div v-else class="product-thumb product-thumb--empty">
            <i class="i-lucide:image" />
          </div>
          <div class="product-meta">
            <div class="product-name">{{ row.name }}</div>
            <div class="product-spu-no num">{{ row.spu_no }}</div>
          </div>
        </div>
      </template>

      <template #category="{ row }">
        <span v-if="row.category?.name">{{ formatCategoryPath(row.category) }}</span>
        <span v-else class="text-ink-400">—</span>
      </template>

      <template #brand="{ row }">
        <span v-if="row.brand?.name">{{ row.brand.name }}</span>
        <span v-else class="text-ink-400">—</span>
      </template>

      <template #type="{ row }">
        <span :class="['tag', `tag-${typeToneMap[row.type] || 'gray'}`]">{{ typeTagMap[row.type]?.label || row.type }}</span>
      </template>

      <template #price="{ row }">
        <span v-if="row.min_price === row.max_price" class="num">
          ¥{{ formatPrice(row.min_price) }}
        </span>
        <span v-else class="num">
          ¥{{ formatPrice(row.min_price) }} ~ ¥{{ formatPrice(row.max_price) }}
        </span>
      </template>

      <template #status="{ row }">
        <span :class="['tag', `tag-${statusToneMap[row.status] || 'gray'}`]">{{ statusTagMap[row.status]?.label || row.status }}</span>
      </template>

      <template #action="{ row }">
        <el-button
          v-has-perm="['goods.goods-spu.update']"
          type="primary"
          size="small"
          text
          @click="handleEdit(row)"
        >
          编辑
        </el-button>
        <el-button
          v-if="row.status !== 'on_sale'"
          v-has-perm="['goods.goods-spu.update']"
          type="success"
          size="small"
          text
          @click="handleOnSale(row)"
        >
          上架
        </el-button>
        <el-button
          v-else
          v-has-perm="['goods.goods-spu.update']"
          type="warning"
          size="small"
          text
          @click="handleOffSale(row)"
        >
          下架
        </el-button>
        <el-button
          v-has-perm="['goods.goods-spu.delete']"
          type="danger"
          size="small"
          text
          @click="handleDelete(row.id, row.name)"
        >
          删除
        </el-button>
      </template>

    </ProTable>

    <!-- 批量导入弹窗 -->
    <ImportDialog
      v-model="importVisible"
      title="批量导入商品"
      :template-url="goodsSpuApi.importTemplateUrl"
      :preview-fn="goodsSpuApi.importPreview"
      :confirm-fn="(filePath) => goodsSpuApi.importConfirm({ file_path: filePath })"
      :preview-columns="importPreviewColumns"
      :notes="importNotes"
      @success="getList"
    />
  </div>
</template>

<script setup lang="ts" name="GoodsSpuList">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted, ref } from 'vue'

import { goodsCategoryApi } from '@/api/goods-category'
import { goodsSpuApi } from '@/api/goods-spu'
import ImportDialog from '@/components/ImportDialog/index.vue'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'
import { exportCsv } from '@/utils/exportCsv'

import SpuEditForm from './components/SpuEditForm.vue'

interface SpuSearchForm {
  keyword: string
  category_id?: number
  status?: string
  type?: string
}

// 状态标签映射
const statusTagMap: Record<string, { label: string; type: string }> = {
  draft: { label: '草稿', type: 'info' },
  on_sale: { label: '已上架', type: 'success' },
  off_sale: { label: '已下架', type: 'warning' },
}
const statusToneMap: Record<string, string> = {
  draft: 'gray',
  on_sale: 'green',
  off_sale: 'amber',
}

// 类型标签映射
const typeTagMap: Record<string, { label: string; type: string }> = {
  physical: { label: '实物', type: '' },
  virtual: { label: '虚拟', type: 'warning' },
  combo: { label: '组合', type: 'danger' },
}
const typeToneMap: Record<string, string> = {
  physical: 'blue',
  virtual: 'amber',
  combo: 'rose',
}

// 通用列表 composable
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
  handleDelete,
} = useListPage<Record<string, any>, SpuSearchForm>({
  fetchFn: (params) => goodsSpuApi.getList(params),
  deleteFn: (id) => goodsSpuApi.delete(id),
  defaultSearchForm: {
    keyword: '',
    category_id: undefined,
    status: undefined,
    type: undefined,
  },
})

// 状态快速切换（与设计稿 erp-tabsel 一致）
const statusFilterOptions: { label: string; value?: string }[] = [
  { label: '全部', value: undefined },
  { label: '在售', value: 'on_sale' },
  { label: '已下架', value: 'off_sale' },
  { label: '草稿', value: 'draft' },
]

const handleStatusFilter = (value?: string) => {
  searchForm.status = value
  handleSearch()
}

// 列定义
const columns: ProColumn[] = [
  { key: 'id', label: 'ID', prop: 'id', width: 80, required: true },
  { key: 'info', label: '商品信息', minWidth: 240, required: true },
  { key: 'category', label: '分类', width: 140, showOverflowTooltip: true },
  { key: 'brand', label: '品牌', width: 120, showOverflowTooltip: true },
  { key: 'type', label: '类型', width: 100 },
  { key: 'price', label: '价格区间', width: 150 },
  { key: 'total_stock', label: '库存', prop: 'total_stock', width: 100 },
  { key: 'sales_count', label: '销量', prop: 'sales_count', width: 100 },
  { key: 'status', label: '状态', width: 120 },
  { key: 'sort', label: '排序', prop: 'sort', width: 80, defaultVisible: false },
  { key: 'created_at', label: '创建时间', prop: 'created_at', width: 180, defaultVisible: false },
  { key: 'action', label: '操作', width: 220, fixed: 'right', required: true },
]

// 当前页状态分布（KPI mini 概览，待后端聚合接口接入后替换）
const stats = computed(() => {
  const counters = { on_sale: 0, draft: 0, off_sale: 0 }
  for (const row of list.value) {
    const s = (row as Record<string, any>).status as keyof typeof counters
    if (s in counters) counters[s] += 1
  }
  return counters
})

const formatCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')
const formatPrice = (price: number) => (price == null ? '0.00' : Number(price).toFixed(2))

const formatCategoryPath = (category: Record<string, any>) => {
  const parentName = category.parent?.name
  if (parentName && parentName !== category.name) {
    return `${parentName} / ${category.name}`
  }
  return category.name
}

// 抽屉状态
const drawerVisible = ref(false)
const editId = ref(0)

// 批量导入 / 导出
const importVisible = ref(false)
const exporting = ref(false)

const importPreviewColumns = [
  { key: 'name', label: '商品名称' },
  { key: 'type', label: '商品类型' },
  { key: 'category_id', label: '分类 ID' },
  { key: 'brand_id', label: '品牌 ID' },
  { key: 'unit_id', label: '单位 ID' },
  { key: 'price', label: '售价' },
  { key: 'stock', label: '库存' },
  { key: 'status', label: '状态' },
]

const importNotes = [
  '标记 <em class="req">*</em> 的字段为必填：商品名称',
  '商品类型支持 <code>physical</code>（实物）/ <code>virtual</code>（虚拟），<code>combo</code> 组合商品请通过编辑抽屉创建',
  '分类 / 品牌 / 单位 ID 必须是系统中已存在的记录',
  '导入的商品默认创建为单 SKU 商品（无规格），多规格请通过编辑抽屉补充',
  '状态默认为 <code>draft</code>，导入后可手动批量上架',
]

const handleImport = () => {
  importVisible.value = true
}

const handleExport = async () => {
  if (exporting.value) return
  exporting.value = true
  try {
    // 拉全量（受当前搜索条件约束）。设上限避免一次性拉太多。
    const res = await goodsSpuApi.getList({
      ...searchForm,
      page: 1,
      limit: 1000,
    })
    const rows = res.data?.list || []
    if (!rows.length) {
      ElMessage.warning('当前筛选条件下没有可导出的数据')
      return
    }
    const today = new Date().toISOString().slice(0, 10)
    exportCsv(`商品列表_${today}.csv`, rows, [
      { label: 'ID', key: 'id' },
      { label: 'SPU 编号', key: 'spu_no' },
      { label: '商品名称', key: 'name' },
      {
        label: '类型',
        key: (r) => typeTagMap[(r as Record<string, any>).type]?.label || (r as Record<string, any>).type,
      },
      {
        label: '状态',
        key: (r) => statusTagMap[(r as Record<string, any>).status]?.label || (r as Record<string, any>).status,
      },
      {
        label: '价格区间',
        key: (r) => {
          const row = r as Record<string, any>
          if (row.min_price === row.max_price) return `¥${formatPrice(row.min_price)}`
          return `¥${formatPrice(row.min_price)} ~ ¥${formatPrice(row.max_price)}`
        },
      },
      { label: '库存', key: 'total_stock' },
      { label: '销量', key: 'sales_count' },
      { label: '排序', key: 'sort' },
      { label: '创建时间', key: 'created_at' },
    ])
    ElMessage.success(`已导出 ${rows.length} 条`)
  } catch (e) {
    console.error('导出失败:', e)
  } finally {
    exporting.value = false
  }
}

const handleDrawerClosed = () => {
  editId.value = 0
}

const handleEditSuccess = () => {
  drawerVisible.value = false
  getList()
}

// 分类树
const categoryTree = ref<Record<string, any>[]>([])
const loadCategoryTree = async () => {
  try {
    const res = await goodsCategoryApi.getTree()
    categoryTree.value = res.data || []
  } catch (error) {
    console.error('加载分类树失败:', error)
  }
}

// 新增 / 编辑
const handleAdd = () => {
  editId.value = 0
  drawerVisible.value = true
}

const handleEdit = (row: Record<string, any>) => {
  editId.value = row.id
  drawerVisible.value = true
}

// 单条上架 / 下架
const handleOnSale = async (row: Record<string, any>) => {
  try {
    await goodsSpuApi.updateStatus(row.id, { status: 'on_sale' })
    ElMessage.success('上架成功')
    getList()
  } catch (error) {
    console.error('上架失败:', error)
  }
}

const handleOffSale = async (row: Record<string, any>) => {
  try {
    await goodsSpuApi.updateStatus(row.id, { status: 'off_sale' })
    ElMessage.success('下架成功')
    getList()
  } catch (error) {
    console.error('下架失败:', error)
  }
}

// 批量上架 / 下架
const handleBatchOnSale = async (ids: number[], clearSelection: () => void) => {
  if (!ids.length) return
  try {
    await ElMessageBox.confirm(`确定要将选中的 ${ids.length} 个商品上架吗？`, '批量上架确认', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await goodsSpuApi.batchOnSale({ ids })
    ElMessage.success('批量上架成功')
    clearSelection()
    getList()
  } catch (error) {
    if (error !== 'cancel') console.error('批量上架失败:', error)
  }
}

const handleBatchOffSale = async (ids: number[], clearSelection: () => void) => {
  if (!ids.length) return
  try {
    await ElMessageBox.confirm(`确定要将选中的 ${ids.length} 个商品下架吗？`, '批量下架确认', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await goodsSpuApi.batchOffSale({ ids })
    ElMessage.success('批量下架成功')
    clearSelection()
    getList()
  } catch (error) {
    if (error !== 'cancel') console.error('批量下架失败:', error)
  }
}

// 批量配送方式（开启/关闭自提）
const handleBatchPickup = async (
  action: 'enable_pickup' | 'disable_pickup',
  ids: number[],
  clearSelection: () => void,
) => {
  if (!ids.length) return
  const label = action === 'enable_pickup' ? '开启自提' : '关闭自提'
  try {
    await ElMessageBox.confirm(
      `确定要将选中的 ${ids.length} 个商品批量${label}吗？`,
      `批量${label}确认`,
      { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' },
    )
    await goodsSpuApi.batchDelivery({ ids, action })
    ElMessage.success(`批量${label}成功`)
    clearSelection()
    getList()
  } catch (error) {
    if (error !== 'cancel') console.error(`批量${label}失败:`, error)
  }
}

onMounted(() => {
  loadCategoryTree()
})
</script>

<style lang="scss" scoped>
.goods-spu-container {
  .stats-row {
    margin-bottom: 14px;
  }

  .goods-status-seg {
    margin-right: 4px;
  }

  :deep(.el-drawer__body) {
    padding: 0 20px;
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    height: 100%;
    overflow: hidden;
  }

  .product-info {
    display: flex;
    align-items: center;
    gap: 10px;

    .product-thumb {
      width: 56px;
      height: 56px;
      border-radius: 4px;
      flex-shrink: 0;
      border: 1px solid var(--el-border-color-lighter);

      &--empty {
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--el-fill-color-light);
        color: var(--el-text-color-secondary);
      }
    }

    .product-meta {
      min-width: 0;

      .product-name {
        font-size: 14px;
        font-weight: 500;
        color: var(--el-text-color-primary);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .product-spu-no {
        font-size: 12px;
        color: var(--el-text-color-secondary);
        margin-top: 2px;
      }
    }
  }
}
</style>
