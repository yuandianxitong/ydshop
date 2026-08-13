<template>
    <div class="help-category-container">
        <div class="page-head">
            <div>
                <div class="page-title">帮助分类</div>
                <div class="page-desc">帮助中心的分类管理，支持启用 / 停用、排序、图标</div>
            </div>
            <div class="page-actions">
                <el-button v-has-perm="['content.help_category.create']" type="primary" @click="handleAdd">
                    <i class="i-svg:plus" />
                    新建分类
                </el-button>
            </div>
        </div>

        <!-- 搜索区域 -->
        <div class="filter-bar">
            <el-input
                v-model="searchForm.keyword"
                placeholder="分类名"
                clearable
                style="width: 220px"
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
                <el-option label="停用" :value="0" />
            </el-select>
            <span class="filter-sp" />
            <el-button @click="resetSearch">重置</el-button>
            <el-button type="primary" @click="handleSearch">搜索</el-button>
        </div>

        <!-- 表格区域 -->
        <ProTable
            title="分类列表"
            storage-key="help-category-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #icon="{ row }">
                <img
                    v-if="row.icon"
                    :src="appStore.getImageUrl(row.icon)"
                    style="width: 32px; height: 32px; object-fit: cover; border-radius: 4px"
                />
                <span v-else class="text-gray-400">-</span>
            </template>
            <template #status="{ row }">
                <el-tag :type="row.status ? 'success' : 'info'" size="small">
                    {{ row.status ? '启用' : '停用' }}
                </el-tag>
            </template>
            <template #action="{ row }">
                <el-button
                    v-has-perm="['content.help_category.update']"
                    type="primary"
                    size="small"
                    text
                    @click="handleEdit(row)"
                >编辑</el-button>
                <el-button
                    v-has-perm="['content.help_category.delete']"
                    type="danger"
                    size="small"
                    text
                    @click="handleDelete(row.id, row.name)"
                >删除</el-button>
            </template>
        </ProTable>

        <!-- 表单弹窗 -->
        <HelpCategoryForm v-model="formVisible" :form-data="formData" @success="getList" />
    </div>
</template>

<script setup lang="ts" name="ContentHelpCategory">
import { ref } from 'vue'
import { useAppStore } from '@/store/modules/app.store'
import { helpCategoryApi, type HelpCategoryInfo } from '@/api/content/helpCategory'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'
import HelpCategoryForm from './components/HelpCategoryForm.vue'

const appStore = useAppStore()

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
} = useListPage<HelpCategoryInfo, { keyword: string; status?: number | '' }>({
    fetchFn: (params) => helpCategoryApi.getList(params),
    deleteFn: (id) => helpCategoryApi.delete(id),
    defaultSearchForm: { keyword: '', status: '' },
})

const columns: ProColumn[] = [
    { key: 'id', label: 'ID', prop: 'id', width: 80, required: true },
    { key: 'name', label: '名称', prop: 'name', minWidth: 200, showOverflowTooltip: true, required: true },
    { key: 'icon', label: '图标', width: 100 },
    { key: 'help_count', label: '帮助数', prop: 'help_count', width: 100 },
    { key: 'status', label: '状态', prop: 'status', width: 90 },
    { key: 'sort', label: '排序', prop: 'sort', width: 90 },
    { key: 'action', label: '操作', width: 160, fixed: 'right', required: true },
]

const formVisible = ref(false)
const formData = ref<Partial<HelpCategoryInfo> & { id?: number }>({})

function handleAdd() {
    formData.value = {}
    formVisible.value = true
}

function handleEdit(row: HelpCategoryInfo) {
    formData.value = { id: row.id }
    formVisible.value = true
}
</script>

<style scoped lang="scss">
.help-category-container {
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
