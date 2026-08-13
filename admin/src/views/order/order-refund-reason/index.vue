<template>
  <div class="refund-reason-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">退货原因</h2>
        <p class="page-desc">退款 / 售后申请的预设原因模板，便于用户快速选择</p>
      </div>
      <div class="page-actions">
        <el-button
          type="primary"
          @click="handleAdd"
          v-has-perm="['order.refund-reason.create']"
        >
          <i class="i-svg:plus" /> 新建原因
        </el-button>
      </div>
    </div>

    <!-- 单行过滤栏 -->
    <div class="filter-bar">
      <el-input
        v-model="searchForm.keyword"
        placeholder="搜索原因名称"
        clearable
        style="width: 260px"
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
        <el-option label="启用" :value="1" />
        <el-option label="禁用" :value="0" />
      </el-select>
      <span class="filter-sp" />
      <el-button @click="resetSearch">重置</el-button>
      <el-button type="primary" @click="handleSearch">查询</el-button>
    </div>

    <!-- 表格 -->
    <ProTable
      title="退货原因"
      storage-key="order-refund-reason-list"
      :columns="columns"
      :data="pagedList"
      :loading="loading"
      :pagination="pagination"
      :show-column-config="false"
      @page-change="handlePageChange"
      @size-change="handleSizeChange"
    >
      <template #name="{ row }">
        <span class="text-secondary">{{ row.name }}</span>
      </template>

      <template #sort="{ row }">
        <span class="num">{{ row.sort ?? 0 }}</span>
      </template>

      <template #status="{ row }">
        <span :class="['tag', row.status === 1 ? 'tag-green' : 'tag-gray']">
          {{ row.status === 1 ? '启用' : '禁用' }}
        </span>
      </template>

      <template #created_at="{ row }">
        <span class="num">{{ row.created_at }}</span>
      </template>

      <template #action="{ row }">
        <el-button
          type="primary"
          size="small"
          text
          @click="handleEdit(row)"
          v-has-perm="['order.refund-reason.update']"
        >
          编辑
        </el-button>
        <el-button
          type="danger"
          size="small"
          text
          @click="handleDelete(row)"
          v-has-perm="['order.refund-reason.delete']"
        >
          删除
        </el-button>
      </template>
    </ProTable>

    <!-- 新增/编辑弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑退款原因' : '新增退款原因'"
      width="500px"
      :close-on-click-modal="false"
      @close="handleDialogClose"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="80px"
      >
        <el-form-item label="原因名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入退款原因" />
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" controls-position="right" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-switch
            v-model="form.status"
            :active-value="1"
            :inactive-value="0"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <span class="flex justify-end gap-2">
          <el-button @click="handleDialogClose">取消</el-button>
          <el-button type="primary" :loading="submitLoading" @click="handleSubmit">
            确定
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="OrderRefundReasonList">
import { ref, reactive, computed, onMounted } from 'vue'

import { ElMessage, ElMessageBox, ElForm } from 'element-plus'
import { orderRefundReasonApi } from '@/api/order-refund-reason'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'

interface RefundReasonRow {
  id: number
  name: string
  sort: number
  status: number
  created_at: string
}

// 全量数据（API 不分页，前端做关键字 / 状态过滤 + 分页）
const allData = ref<RefundReasonRow[]>([])
const loading = ref(false)

// 搜索条件
const searchForm = reactive<{ keyword: string; status: number | '' }>({
  keyword: '',
  status: '',
})

// 分页（前端分页）
const pagination = reactive({ page: 1, limit: 20, total: 0 })

const filteredList = computed<RefundReasonRow[]>(() => {
  const kw = searchForm.keyword.trim().toLowerCase()
  return allData.value.filter((r) => {
    if (kw && !(r.name || '').toLowerCase().includes(kw)) return false
    if (searchForm.status !== '' && r.status !== searchForm.status) return false
    return true
  })
})

const pagedList = computed(() => {
  const start = (pagination.page - 1) * pagination.limit
  return filteredList.value.slice(start, start + pagination.limit)
})

const columns: ProColumn[] = [
  { key: 'id', label: 'ID', prop: 'id', width: 80, required: true },
  { key: 'name', label: '退款原因', minWidth: 240, required: true },
  { key: 'sort', label: '排序', width: 100, align: 'right' },
  { key: 'status', label: '状态', width: 100 },
  { key: 'created_at', label: '创建时间', width: 200 },
  { key: 'action', label: '操作', width: 160, fixed: 'right', required: true },
]

// 弹窗
const dialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)
const submitLoading = ref(false)
const formRef = ref<InstanceType<typeof ElForm>>()

const form = reactive({
  name: '',
  sort: 0,
  status: 1,
})

const rules = {
  name: [{ required: true, message: '请输入退款原因', trigger: 'blur' }],
}

const fetchList = async () => {
  try {
    loading.value = true
    const response = await orderRefundReasonApi.getList()
    allData.value = (response.data || []) as RefundReasonRow[]
    pagination.total = filteredList.value.length
  } catch (error) {
    console.error('获取列表失败:', error)
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  pagination.page = 1
  pagination.total = filteredList.value.length
}

const resetSearch = () => {
  searchForm.keyword = ''
  searchForm.status = ''
  pagination.page = 1
  pagination.total = filteredList.value.length
}

const handlePageChange = (page: number) => {
  pagination.page = page
}
const handleSizeChange = (size: number) => {
  pagination.limit = size
  pagination.page = 1
}

const handleAdd = () => {
  isEdit.value = false
  editId.value = null
  Object.assign(form, { name: '', sort: 0, status: 1 })
  dialogVisible.value = true
}

const handleEdit = (row: RefundReasonRow) => {
  isEdit.value = true
  editId.value = row.id
  Object.assign(form, {
    name:   row.name ?? '',
    sort:   row.sort ?? 0,
    status: row.status ?? 1,
  })
  dialogVisible.value = true
}

const handleDelete = async (row: RefundReasonRow) => {
  try {
    await ElMessageBox.confirm(
      '确定要删除该退款原因吗？',
      '删除确认',
      { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' }
    )
    await orderRefundReasonApi.delete(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
    }
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return
  try {
    await formRef.value.validate()
    submitLoading.value = true

    if (isEdit.value && editId.value) {
      await orderRefundReasonApi.update(editId.value, { ...form })
      ElMessage.success('编辑成功')
    } else {
      await orderRefundReasonApi.create({ ...form })
      ElMessage.success('新增成功')
    }

    fetchList()
    handleDialogClose()
  } catch (error) {
    console.error('提交失败:', error)
  } finally {
    submitLoading.value = false
  }
}

const handleDialogClose = () => {
  formRef.value?.resetFields()
  dialogVisible.value = false
}

onMounted(() => {
  fetchList()
})
</script>

