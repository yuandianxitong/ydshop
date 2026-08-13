<template>
    <div class="help-container">
        <div class="page-head">
            <div>
                <div class="page-title">帮助列表</div>
                <div class="page-desc">维护商城帮助内容（富文本），支持分类筛选、批量启停</div>
            </div>
            <div class="page-actions">
                <el-button v-has-perm="['content.help.create']" type="primary" @click="handleAdd">
                    <i class="i-svg:plus" />
                    新建帮助
                </el-button>
            </div>
        </div>

        <!-- 搜索区域 -->
        <div class="filter-bar">
            <el-select
                v-model="searchForm.category_id"
                placeholder="全部分类"
                clearable
                style="width: 200px"
                @change="handleSearch"
            >
                <el-option v-for="c in categories" :key="c.id" :label="c.name" :value="c.id" />
            </el-select>
            <span class="filter-label">状态：</span>
            <el-select
                v-model="searchForm.status"
                placeholder="请选择"
                clearable
                style="width: 130px"
                @change="handleSearch"
            >
                <el-option label="已发布" :value="1" />
                <el-option label="草稿" :value="0" />
            </el-select>
            <el-input
                v-model="searchForm.keyword"
                placeholder="标题关键词"
                clearable
                style="width: 220px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-sp" />
            <el-button @click="resetSearch">重置</el-button>
            <el-button type="primary" @click="handleSearch">搜索</el-button>
        </div>

        <!-- 表格区域 -->
        <ProTable
            title="帮助列表"
            storage-key="help-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            selectable
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #batchActions="{ selectedIds: ids, clearSelection }">
                <el-button
                    size="small"
                    type="success"
                    @click="handleBatchStatus(ids, 1, clearSelection)"
                >批量发布</el-button>
                <el-button
                    size="small"
                    type="warning"
                    @click="handleBatchStatus(ids, 0, clearSelection)"
                >批量下架</el-button>
            </template>

            <template #category="{ row }">{{ row.category?.name || '-' }}</template>
            <template #status="{ row }">
                <el-tag :type="row.status ? 'success' : 'info'" size="small">
                    {{ row.status ? '已发布' : '草稿' }}
                </el-tag>
            </template>

            <template #action="{ row }">
                <el-button
                    v-has-perm="['content.help.update']"
                    type="primary"
                    size="small"
                    text
                    @click="handleEdit(row)"
                >编辑</el-button>
                <el-button
                    v-has-perm="['content.help.delete']"
                    type="danger"
                    size="small"
                    text
                    @click="handleDelete(row.id, row.title)"
                >删除</el-button>
            </template>
        </ProTable>

        <HelpForm v-model="formVisible" :form-data="formData" @success="getList" />
    </div>
</template>

<script setup lang="ts" name="ContentHelp">
import { ElMessage } from 'element-plus'
import { onMounted, ref } from 'vue'

import { helpApi, type HelpInfo } from '@/api/content/help'
import { helpCategoryApi, type HelpCategoryInfo } from '@/api/content/helpCategory'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'

import HelpForm from './components/HelpForm.vue'

const categories = ref<HelpCategoryInfo[]>([])

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
} = useListPage<HelpInfo, { keyword: string; category_id?: number | ''; status?: number | '' }>({
    fetchFn: (params) => helpApi.getList(params),
    deleteFn: (id) => helpApi.delete(id),
    defaultSearchForm: { keyword: '', category_id: '', status: '' },
})

const columns: ProColumn[] = [
    { key: 'title', label: '标题', prop: 'title', minWidth: 240, showOverflowTooltip: true, required: true },
    { key: 'category', label: '分类', minWidth: 120 },
    { key: 'view_count', label: '阅读量', prop: 'view_count', width: 100 },
    { key: 'admin_name', label: '创建人', prop: 'admin_name', width: 120 },
    { key: 'status', label: '状态', prop: 'status', width: 100 },
    { key: 'updated_at', label: '更新时间', prop: 'updated_at', width: 170 },
    { key: 'action', label: '操作', width: 140, fixed: 'right', required: true },
]

async function loadCategories() {
    const res = await helpCategoryApi.getAll()
    if (res.code === 200) categories.value = res.data || []
}

const formVisible = ref(false)
const formData = ref<Partial<HelpInfo> & { id?: number }>({})

function handleAdd() {
    formData.value = {}
    formVisible.value = true
}

function handleEdit(row: HelpInfo) {
    formData.value = { id: row.id }
    formVisible.value = true
}

async function handleBatchStatus(ids: number[], status: 0 | 1, clearSelection: () => void) {
    if (!ids.length) {
        ElMessage.warning('请先选择')
        return
    }
    await helpApi.batchStatus(ids, status)
    ElMessage.success('操作成功')
    clearSelection()
    getList()
}

onMounted(loadCategories)
</script>

<style scoped lang="scss">
.help-container {
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
