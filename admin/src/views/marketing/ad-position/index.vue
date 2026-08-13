<template>
    <div class="ad-position-container">
        <div class="page-head">
            <div>
                <div class="page-title">广告位</div>
                <div class="page-desc">管理广告投放位置：编码、推荐尺寸、是否轮播、状态</div>
            </div>
            <div class="page-actions">
                <el-button v-has-perm="['marketing.ad_position.create']" type="primary" @click="handleAdd">
                    <i class="i-svg:plus" />
                    新建广告位
                </el-button>
            </div>
        </div>

        <!-- 搜索区域 -->
        <div class="filter-bar">
            <el-input
                v-model="searchForm.keyword"
                placeholder="名称 / 编码"
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

        <!-- 表格 -->
        <ProTable
            title="广告位列表"
            storage-key="ad-position-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #code="{ row }">
                <code class="text-xs text-gray-500">{{ row.code }}</code>
            </template>
            <template #recommended="{ row }">
                {{ row.recommended_width }}×{{ row.recommended_height }}
            </template>
            <template #is_carousel="{ row }">
                <el-tag :type="row.is_carousel ? 'success' : 'info'" size="small">{{ row.is_carousel ? '轮播' : '单图' }}</el-tag>
            </template>
            <template #status="{ row }">
                <el-tag :type="row.status ? 'success' : 'info'" size="small">{{ row.status ? '启用' : '停用' }}</el-tag>
            </template>
            <template #action="{ row }">
                <el-button
                    v-has-perm="['marketing.ad_position.update']"
                    type="primary"
                    size="small"
                    text
                    @click="handleEdit(row)"
                >编辑</el-button>
                <el-button
                    v-has-perm="['marketing.ad_position.delete']"
                    type="danger"
                    size="small"
                    text
                    @click="handleDelete(row.id, row.name)"
                >删除</el-button>
            </template>
        </ProTable>

        <AdPositionForm v-model="formVisible" :form-data="formData" @success="getList" />
    </div>
</template>

<script setup lang="ts" name="MarketingAdPosition">
import { ref } from 'vue'
import { adPositionApi, type AdPositionInfo } from '@/api/marketing/adPosition'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'
import AdPositionForm from './components/AdPositionForm.vue'

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
} = useListPage<AdPositionInfo, { keyword: string; status?: number | '' }>({
    fetchFn: (params) => adPositionApi.getList(params),
    deleteFn: (id) => adPositionApi.delete(id),
    defaultSearchForm: { keyword: '', status: '' },
})

const columns: ProColumn[] = [
    { key: 'id', label: 'ID', prop: 'id', width: 80, required: true },
    { key: 'name', label: '名称', prop: 'name', minWidth: 180, showOverflowTooltip: true, required: true },
    { key: 'code', label: '编码', prop: 'code', width: 200 },
    { key: 'recommended', label: '推荐尺寸', width: 120 },
    { key: 'is_carousel', label: '轮播', prop: 'is_carousel', width: 100 },
    { key: 'status', label: '状态', prop: 'status', width: 100 },
    { key: 'sort', label: '排序', prop: 'sort', width: 90 },
    { key: 'action', label: '操作', width: 160, fixed: 'right', required: true },
]

const formVisible = ref(false)
const formData = ref<Partial<AdPositionInfo> & { id?: number }>({})

function handleAdd() {
    formData.value = {}
    formVisible.value = true
}

function handleEdit(row: AdPositionInfo) {
    formData.value = { id: row.id }
    formVisible.value = true
}
</script>

<style scoped lang="scss">
.ad-position-container {
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
