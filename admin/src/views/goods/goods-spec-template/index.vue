<template>
  <div class="goods-spec-template-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">规格模板</h2>
        <p class="page-desc">预设可复用的多规格组合，添加商品时一键应用</p>
      </div>
      <div class="page-actions">
        <el-button v-has-perm="['goods.goods-spec-template.create']" type="primary" @click="handleAdd">
          <i class="i-lucide:plus mr-1" /> 新增模板
        </el-button>
      </div>
    </div>

    <!-- 搜索 -->
    <div class="filter-bar">
      <el-input
        v-model="searchForm.keyword"
        placeholder="搜索模板名称"
        clearable
        style="width: 240px"
        @keyup.enter="handleSearch"
      />
      <span class="filter-label">状态：</span>
      <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 110px" @change="handleSearch">
        <el-option label="启用" :value="1" />
        <el-option label="禁用" :value="0" />
      </el-select>
      <span class="filter-sp" />
      <el-button @click="handleReset">重置</el-button>
      <el-button type="primary" @click="handleSearch">查询</el-button>
    </div>

    <!-- 表格 -->
    <el-card class="table-card" shadow="never">
      <div class="table-header">
        <div class="table-title">规格模板 <span class="table-count">共 {{ pagination.total }} 条</span></div>
        <div class="table-actions">
          <el-button
            v-has-perm="['goods.goods-spec-template.delete']"
            :disabled="!multipleSelection.length"
            @click="handleBatchDelete"
          >
            <i class="i-svg:trash-2 mr-1" /> 批量删除
          </el-button>
        </div>
      </div>

      <el-table v-loading="loading" :data="tableData" @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="55" />
        <el-table-column label="ID" prop="id" width="80" />
        <el-table-column label="模板名称" prop="name" min-width="160" show-overflow-tooltip />
        <el-table-column label="规格摘要" min-width="320">
          <template #default="{ row }">
            <span v-if="row.items?.length" class="spec-summary">
              <span v-for="(item, i) in row.items" :key="i" class="spec-summary-item">
                <b>{{ item.name }}</b>
                <span class="spec-summary-vals">({{ (item.values || []).join('/') }})</span>
              </span>
            </span>
            <span v-else class="text-secondary">—</span>
          </template>
        </el-table-column>
        <el-table-column label="备注" prop="description" min-width="160" show-overflow-tooltip>
          <template #default="{ row }">{{ row.description || '—' }}</template>
        </el-table-column>
        <el-table-column label="排序" prop="sort" width="100" align="center" />
        <el-table-column label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :class="['tag-tone-' + (row.status === 1 ? 'green' : 'gray')]" size="small" effect="light">{{ row.status === 1 ? '启用' : '禁用' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="创建时间" prop="created_at" width="200">
          <template #default="{ row }"><span class="num text-secondary">{{ row.created_at }}</span></template>
        </el-table-column>
        <el-table-column label="操作" width="160" fixed="right">
          <template #default="{ row }">
            <el-button v-has-perm="['goods.goods-spec-template.update']" type="primary" size="small" text @click="handleEdit(row)">编辑</el-button>
            <el-button v-has-perm="['goods.goods-spec-template.delete']" type="danger" size="small" text @click="handleDelete(row)">删除</el-button>
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
        @size-change="fetchList"
        @current-change="fetchList"
      />
    </el-card>

    <GoodsSpecTemplateForm v-model="formVisible" :form-data="formData" @success="fetchList" />
  </div>
</template>

<script setup lang="ts" name="GoodsSpecTemplateList">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { goodsSpecTemplateApi, type GoodsSpecTemplateInfo } from '@/api/goods-spec-template'
import GoodsSpecTemplateForm from './components/GoodsSpecTemplateForm.vue'

const searchForm = reactive({
  keyword: '',
  status: undefined as number | undefined,
})

const tableData = ref<GoodsSpecTemplateInfo[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, limit: 20, total: 0 })
const multipleSelection = ref<GoodsSpecTemplateInfo[]>([])
const formVisible = ref(false)
const formData = ref<Partial<GoodsSpecTemplateInfo>>({})

const fetchList = async () => {
  try {
    loading.value = true
    const res = await goodsSpecTemplateApi.getList({
      ...searchForm,
      page: pagination.page,
      limit: pagination.limit,
    })
    tableData.value = res.data.list
    pagination.total = res.data.pagination.total
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  pagination.page = 1
  fetchList()
}

const handleReset = () => {
  Object.assign(searchForm, { keyword: '', status: undefined })
  pagination.page = 1
  fetchList()
}

const handleSelectionChange = (sel: GoodsSpecTemplateInfo[]) => {
  multipleSelection.value = sel
}

const handleAdd = () => {
  formData.value = { status: 1, sort: 0, items: [] }
  formVisible.value = true
}

const handleEdit = (row: any) => {
  formData.value = { ...row, items: (row.items || []).map((i: any) => ({ name: i.name, values: [...(i.values || [])] })) }
  formVisible.value = true
}

const handleDelete = async (row: any) => {
  try {
    await ElMessageBox.confirm(`确定要删除模板「${row.name}」？`, '删除确认', {
      confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning',
    })
    await goodsSpecTemplateApi.delete(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

const handleBatchDelete = async () => {
  if (!multipleSelection.value.length) return
  try {
    await ElMessageBox.confirm(
      `确定要删除选中的 ${multipleSelection.value.length} 条模板吗？`,
      '批量删除确认',
      { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' },
    )
    await goodsSpecTemplateApi.batchDelete({ ids: multipleSelection.value.map(r => r.id) })
    ElMessage.success('批量删除成功')
    fetchList()
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

onMounted(() => {
  fetchList()
})
</script>

<style lang="scss" scoped>
.goods-spec-template-container {
  .spec-summary {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 12.5px;
    color: var(--ink-700);

    b {
      color: var(--ink-900);
      font-weight: 600;
    }
  }
  .spec-summary-vals {
    color: var(--ink-500);
    margin-left: 4px;
  }
  .text-secondary { color: var(--ink-500); }
}
</style>
