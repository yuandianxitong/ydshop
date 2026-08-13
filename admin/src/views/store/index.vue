<template>
    <div class="store-container">
        <div class="page-head">
            <div>
                <div class="page-title">门店管理</div>
                <div class="page-desc">管理自提门店信息，支持关键字搜索与状态筛选</div>
            </div>
            <div class="page-actions">
                <el-button v-has-perm="['store.create']" type="primary" @click="handleAdd">
                    <i class="i-svg:plus" />
                    新建门店
                </el-button>
            </div>
        </div>

        <!-- 搜索区域 -->
        <div class="filter-bar">
            <el-input
                v-model="searchForm.keyword"
                placeholder="门店名称 / 编码 / 地址 / 电话"
                clearable
                style="width: 260px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-label">状态：</span>
            <el-select
                v-model="searchForm.status"
                placeholder="请选择"
                clearable
                style="width: 130px"
                @change="handleSearch"
            >
                <el-option label="启用" :value="1" />
                <el-option label="禁用" :value="0" />
            </el-select>
            <span class="filter-sp" />
            <el-button @click="resetSearch">重置</el-button>
            <el-button type="primary" @click="handleSearch">搜索</el-button>
        </div>

        <!-- 表格区域 -->
        <ProTable
            title="门店列表"
            storage-key="store-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #status="{ row }">
                <el-switch
                    :model-value="row.status === 1"
                    @change="(val: string | number | boolean) => handleStatusChange(row, val)"
                />
            </template>

            <template #is_open_now="{ row }">
                <el-tag v-if="row.status === 0" type="info" size="small">已禁用</el-tag>
                <el-tag v-else-if="row.is_open_now" type="success" size="small">营业中</el-tag>
                <el-tag v-else type="warning" size="small">已歇业</el-tag>
            </template>

            <template #action="{ row }">
                <el-button
                    v-has-perm="['store.edit']"
                    type="primary"
                    size="small"
                    text
                    @click="handleEdit(row)"
                >
                    编辑
                </el-button>
                <el-button
                    v-has-perm="['store.delete']"
                    type="danger"
                    size="small"
                    text
                    @click="handleDelete(row.id, row.name)"
                >
                    删除
                </el-button>
            </template>
        </ProTable>

        <!-- 表单弹窗 -->
        <StoreForm v-model="formVisible" :form-data="formData" @success="getList" />
    </div>
</template>

<script setup lang="ts" name="StoreIndex">
import { ElMessage } from 'element-plus'
import { ref } from 'vue'
import { storeApi, type Store, type StoreListQuery } from '@/api/store'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'
import StoreForm from './components/StoreForm.vue'

const {
    list,
    loading,
    pagination,
    searchForm,
    getList,
    handleSearch,
    resetSearch,
    handleSizeChange,
    handlePageChange,
    handleDelete,
} = useListPage<Store, { keyword: string; status?: number }>({
    fetchFn: (params) => storeApi.list(params as StoreListQuery),
    deleteFn: (id) => storeApi.delete(id),
    defaultSearchForm: { keyword: '', status: undefined },
})

// 列定义
const columns: ProColumn[] = [
    { key: 'id', label: 'ID', prop: 'id', width: 80, required: true },
    { key: 'name', label: '名称', prop: 'name', minWidth: 160, showOverflowTooltip: true, required: true },
    { key: 'code', label: '编码', prop: 'code', width: 140 },
    { key: 'address', label: '地址', prop: 'address', minWidth: 220, showOverflowTooltip: true },
    { key: 'phone', label: '电话', prop: 'phone', width: 150 },
    { key: 'is_open_now', label: '营业状态', prop: 'is_open_now', width: 120 },
    { key: 'status', label: '启停', prop: 'status', width: 90 },
    { key: 'sort', label: '排序', prop: 'sort', width: 100 },
    { key: 'action', label: '操作', width: 160, fixed: 'right', required: true },
]

const formVisible = ref(false)
const formData = ref<Partial<Store> & { id?: number }>({})

const handleAdd = () => {
    formData.value = {}
    formVisible.value = true
}

const handleEdit = (row: Store) => {
    formData.value = { id: row.id }
    formVisible.value = true
}

async function handleStatusChange(row: Store, val: string | number | boolean) {
    const on = Boolean(val)
    try {
        await storeApi.update(row.id!, { ...row, status: on ? 1 : 0 })
        ElMessage.success(on ? '已启用' : '已禁用')
        getList()
    } catch {
        // 请求拦截器已 toast
    }
}
</script>

<style scoped lang="scss">
.store-container {
    .page-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
    }

    .page-title {
        font-size: 18px;
        font-weight: 600;
    }

    .page-desc {
        font-size: 13px;
        color: var(--ink-500, #8b95a7);
        margin-top: 4px;
    }

    .filter-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 16px;

        .filter-label {
            color: var(--el-text-color-regular);
            font-size: 14px;
        }

        .filter-sp {
            flex: 1;
        }
    }
}
</style>
