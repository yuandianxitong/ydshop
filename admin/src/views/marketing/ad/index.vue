<template>
    <div class="ad-container">
        <div class="page-head">
            <div>
                <div class="page-title">广告</div>
                <div class="page-desc">投放至各广告位的物料，支持时间窗调度、轮播排序、批量启停</div>
            </div>
            <div class="page-actions">
                <el-button v-has-perm="['marketing.ad.create']" type="primary" @click="handleAdd">
                    <i class="i-svg:plus" />
                    新建广告
                </el-button>
            </div>
        </div>

        <!-- 搜索区域 -->
        <div class="filter-bar">
            <el-select
                v-model="searchForm.position_id"
                placeholder="全部广告位"
                clearable
                style="width: 200px"
                @change="handleSearch"
            >
                <el-option v-for="p in positions" :key="p.id" :label="p.name" :value="p.id" />
            </el-select>
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
            <el-input
                v-model="searchForm.keyword"
                placeholder="广告名"
                clearable
                style="width: 220px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-sp" />
            <el-button @click="resetSearch">重置</el-button>
            <el-button type="primary" @click="handleSearch">搜索</el-button>
        </div>

        <!-- 表格 -->
        <ProTable
            title="广告列表"
            storage-key="ad-list"
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
                >批量启用</el-button>
                <el-button
                    size="small"
                    type="warning"
                    @click="handleBatchStatus(ids, 0, clearSelection)"
                >批量禁用</el-button>
            </template>

            <template #image="{ row }">
                <img
                    :src="appStore.getImageUrl(row.image)"
                    style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px"
                />
            </template>
            <template #position="{ row }">{{ row.position?.name || '-' }}</template>
            <template #link="{ row }">
                <span class="text-xs text-gray-500">{{ row.link || '（无跳转）' }}</span>
            </template>
            <template #period="{ row }">
                <span class="text-xs">
                    {{ row.start_at?.slice(0, 16) || '立即' }} ~ {{ row.end_at?.slice(0, 16) || '永久' }}
                </span>
            </template>
            <template #status="{ row }">
                <el-tag :type="row.status ? 'success' : 'info'" size="small">
                    {{ row.status ? '启用' : '禁用' }}
                </el-tag>
            </template>
            <template #active="{ row }">
                <el-tag :type="(getActiveBadge(row).type as any)" size="small">
                    {{ getActiveBadge(row).text }}
                </el-tag>
            </template>
            <template #action="{ row }">
                <el-button
                    v-has-perm="['marketing.ad.update']"
                    type="primary"
                    size="small"
                    text
                    @click="handleEdit(row)"
                >编辑</el-button>
                <el-button
                    v-has-perm="['marketing.ad.delete']"
                    type="danger"
                    size="small"
                    text
                    @click="handleDelete(row.id, row.title)"
                >删除</el-button>
            </template>
        </ProTable>

        <AdForm
            v-model="formVisible"
            :form-data="formData"
            :positions="positions"
            @success="getList"
        />
    </div>
</template>

<script setup lang="ts" name="MarketingAd">
import { ElMessage } from 'element-plus'
import { onMounted, ref } from 'vue'

import { adApi, type AdInfo } from '@/api/marketing/ad'
import { adPositionApi, type AdPositionInfo } from '@/api/marketing/adPosition'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'
import { useAppStore } from '@/store/modules/app.store'

import AdForm from './components/AdForm.vue'

const appStore = useAppStore()
const positions = ref<AdPositionInfo[]>([])

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
} = useListPage<AdInfo, { keyword: string; position_id?: number | ''; status?: number | '' }>({
    fetchFn: (params) => adApi.getList(params),
    deleteFn: (id) => adApi.delete(id),
    defaultSearchForm: { keyword: '', position_id: '', status: '' },
})

const columns: ProColumn[] = [
    { key: 'title', label: '名称', prop: 'title', minWidth: 180, showOverflowTooltip: true, required: true },
    { key: 'image', label: '缩图', width: 100 },
    { key: 'position', label: '广告位', minWidth: 160 },
    { key: 'link', label: '链接', minWidth: 200, showOverflowTooltip: true },
    { key: 'period', label: '上架期', minWidth: 220 },
    { key: 'sort', label: '排序', prop: 'sort', width: 100 },
    { key: 'status', label: '状态', prop: 'status', width: 90 },
    { key: 'active', label: '当前生效', width: 110 },
    { key: 'action', label: '操作', width: 140, fixed: 'right', required: true },
]

const formVisible = ref(false)
const formData = ref<Partial<AdInfo> & { id?: number }>({})

async function loadPositions() {
    const res = await adPositionApi.getAll()
    if (res.code === 200) positions.value = res.data || []
}

function handleAdd() {
    formData.value = {}
    formVisible.value = true
}

function handleEdit(row: AdInfo) {
    formData.value = { id: row.id }
    formVisible.value = true
}

async function handleBatchStatus(ids: number[], status: 0 | 1, clearSelection: () => void) {
    if (!ids.length) {
        ElMessage.warning('请先选择')
        return
    }
    await adApi.batchStatus(ids, status)
    ElMessage.success('操作成功')
    clearSelection()
    getList()
}

function getActiveBadge(row: AdInfo): { type: string; text: string } {
    if (!row.position || row.position.status === 0) return { type: 'warning', text: '位置停用' }
    if (row.status === 0) return { type: 'danger', text: '禁用' }
    const now = Date.now()
    if (row.start_at && new Date(row.start_at).getTime() > now) return { type: 'info', text: '未到' }
    if (row.end_at && new Date(row.end_at).getTime() <= now) return { type: 'info', text: '已过' }
    return { type: 'success', text: '是 ✅' }
}

onMounted(loadPositions)
</script>

<style scoped lang="scss">
.ad-container {
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
