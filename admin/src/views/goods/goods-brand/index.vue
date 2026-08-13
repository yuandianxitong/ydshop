<template>
  <div class="goods-brand-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">品牌管理</h2>
        <p class="page-desc">入驻品牌、品牌故事与授权资料</p>
      </div>
      <div class="page-actions">
        <el-button
          type="primary"
          @click="handleAdd"
          v-has-perm="['goods.goods-brand.create']"
        >
          <i class="i-lucide:plus mr-1" />
          新增品牌
        </el-button>
      </div>
    </div>

    <!-- 搜索区域 -->
    <div class="filter-bar">
      <el-input
        v-model="searchForm.keyword"
        placeholder="搜索品牌关键词"
        clearable
        style="width: 240px"
        @keyup.enter="handleSearch"
      />
      <span class="filter-label">状态：</span>
      <el-select
        v-model="searchForm.status"
        placeholder="全部"
        clearable
        style="width: 130px"
        @change="handleSearch"
      >
        <el-option label="正常" :value="1" />
        <el-option label="禁用" :value="0" />
      </el-select>
      <span class="filter-sp" />
      <el-button @click="handleReset">重置</el-button>
      <el-button type="primary" @click="handleSearch">查询</el-button>
    </div>

    <!-- 操作区域 -->
    <el-card class="table-card" shadow="never">
      <div class="table-header">
        <div class="table-title">品牌管理</div>
        <div class="table-actions">
          <el-button
            :disabled="!multipleSelection.length"
            @click="handleBatchDelete"
            v-has-perm="['goods.goods-brand.delete']"
          >
            <i class="i-svg:trash-2 mr-1" />
            批量删除
          </el-button>
        </div>
      </div>

      <!-- 表格 -->
      <el-table
        v-loading="loading"
        :data="tableData"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="55" />
        <el-table-column label="ID" prop="id" width="80" />
        
        <el-table-column label="品牌名称" prop="name" width="120" />
        
        <el-table-column label="Logo" width="100" align="center">
          <template #default="{ row }">
            <el-image
              v-if="row.logo"
              :src="appStore.getImageUrl(row.logo)"
              :preview-src-list="[appStore.getImageUrl(row.logo)]"
              :preview-teleported="true"
              style="width:40px;height:40px;cursor:pointer"
              fit="contain"
            />
            <span v-else>-</span>
          </template>
        </el-table-column>
        
        <el-table-column label="描述" prop="description" min-width="120" show-overflow-tooltip />
        
        <el-table-column label="排序" prop="sort" width="120" />

        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatusChange(row)"
              :disabled="!userStore.hasPermission('goods.goods-brand.update')"
            />
          </template>
        </el-table-column>
        
        <el-table-column label="创建时间" prop="created_at" width="200" />

        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button
              type="primary"
              size="small"
              text
              @click="handleEdit(row)"
              v-has-perm="['goods.goods-brand.update']"
            >
              编辑
            </el-button>
            <el-button
              type="danger"
              size="small"
              text
              @click="handleDelete(row)"
              v-has-perm="['goods.goods-brand.delete']"
            >
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.limit"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="fetchList"
        @current-change="fetchList"
        class="pagination"
      />
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <GoodsBrandForm
      v-model="formVisible"
      :form-data="formData"
      @success="fetchList"
    />
  </div>
</template>

<script setup lang="ts" name="GoodsBrandList">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { goodsBrandApi } from '@/api/goods-brand'
import GoodsBrandForm from './components/GoodsBrandForm.vue'
import { useUserStore, useAppStore } from '@/store'

const appStore = useAppStore()


// 搜索表单
const searchForm = reactive({
  keyword: '',
  status: undefined as number | undefined,
  page: 1,
  limit: 20,
})

// 列表数据
const tableData = ref<Record<string, any>[]>([])
const loading = ref(false)

// 分页
const pagination = reactive({
  page: 1,
  limit: 20,
  total: 0,
})

// 表格选择
const multipleSelection = ref<Record<string, any>[]>([])

// 弹窗
const formVisible = ref(false)
const formData = ref<Record<string, any>>({})

// 获取列表
const fetchList = async () => {
  try {
    loading.value = true
    const params = {
      ...searchForm,
      page: pagination.page,
      limit: pagination.limit,
    }
    const response = await goodsBrandApi.getList(params)
    tableData.value = response.data.list
    pagination.total = response.data.pagination.total
  } catch (error) {
    console.error('获取列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 搜索
const handleSearch = () => {
  pagination.page = 1
  fetchList()
}

// 重置搜索
const handleReset = () => {
  Object.assign(searchForm, {
    keyword: '',
    status: undefined,
  })
  pagination.page = 1
  fetchList()
}

// 表格选择
const handleSelectionChange = (selection: Record<string, any>[]) => {
  multipleSelection.value = selection
}

// 用户Store
const userStore = useUserStore()

// 状态变更
const handleStatusChange = async (row: Record<string, any>) => {
  try {
    await goodsBrandApi.updateStatus(row.id, { status: row.status })
    ElMessage.success('状态更新成功')
  } catch (error) {
    row.status = row.status === 1 ? 0 : 1
    console.error('状态更新失败:', error)
  }
}
// 新增
const handleAdd = () => {
  formData.value = { status: 1 }
  formVisible.value = true
}

// 编辑
const handleEdit = (row: Record<string, any>) => {
  formData.value = { ...row }
  formVisible.value = true
}

// 删除
const handleDelete = async (row: Record<string, any>) => {
  try {
    await ElMessageBox.confirm(
      '确定要删除该记录吗？删除后不可恢复！',
      '删除确认',
      { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' }
    )
    await goodsBrandApi.delete(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
    }
  }
}

// 批量删除
const handleBatchDelete = async () => {
  try {
    await ElMessageBox.confirm(
      `确定要删除选中的${multipleSelection.value.length}条记录吗？删除后不可恢复！`,
      '批量删除确认',
      { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' }
    )
    const ids = multipleSelection.value.map(item => item.id)
    await goodsBrandApi.batchDelete({ ids })
    ElMessage.success('批量删除成功')
    fetchList()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('批量删除失败:', error)
    }
  }
}

onMounted(() => {
  fetchList()
})
</script>

<style lang="scss" scoped>
.goods-brand-container {}
</style>