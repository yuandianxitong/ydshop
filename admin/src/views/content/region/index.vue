<template>
    <div class="region-container">
        <div class="page-head">
            <div>
                <div class="page-title">{{ $t('regionMgmt.title') }}</div>
                <div class="page-desc">{{ $t('regionMgmt.desc') }}</div>
            </div>
            <div class="page-actions">
                <el-button v-has-perm="['region.create']" type="primary" @click="handleAdd">
                    <i class="i-svg:plus" />
                    {{ $t('regionMgmt.addRegion') }}
                </el-button>
            </div>
        </div>
        <!-- 面包屑 + 搜索 -->
        <div class="filter-bar">
            <el-breadcrumb separator="/">
                <el-breadcrumb-item>
                    <a @click.prevent="navigateTo(-1)">{{ $t('regionMgmt.title') }}</a>
                </el-breadcrumb-item>
                <el-breadcrumb-item v-for="(item, index) in breadcrumbs" :key="item.id">
                    <a
                        v-if="index < breadcrumbs.length - 1"
                        @click.prevent="navigateTo(index)"
                        >{{ item.name }}</a
                    >
                    <span v-else>{{ item.name }}</span>
                </el-breadcrumb-item>
            </el-breadcrumb>
            <span class="filter-sp" />
            <el-input
                v-model="searchForm.keyword"
                :placeholder="$t('regionMgmt.namePlaceholder')"
                clearable
                style="width: 220px"
                @keyup.enter="handleSearch"
            />
            <el-button @click="resetSearch">{{ $t('common.reset') }}</el-button>
            <el-button type="primary" @click="handleSearch">{{ $t('common.search') }}</el-button>
        </div>

        <!-- 表格区域 -->
        <ProTable
            :title="currentParentId === 0 ? $t('regionMgmt.title') : breadcrumbs[breadcrumbs.length - 1]?.name"
            storage-key="region-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #level="{ row }">
                <el-tag :type="levelTagMap[row.level] || 'info'" size="small">
                    {{ levelTextMap[row.level] || row.level }}
                </el-tag>
            </template>

            <template #status="{ row }">
                <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
                    {{ row.status === 1 ? $t('common.enable') : $t('common.disable') }}
                </el-tag>
            </template>

            <template #action="{ row }">
                <el-button type="primary" size="small" text @click="handleDrillDown(row)">
                    下级管理
                </el-button>
                <el-button
                    v-has-perm="['region.create']"
                    type="primary"
                    size="small"
                    text
                    @click="handleAddChild(row)"
                >
                    {{ $t('regionMgmt.addChildRegion') }}
                </el-button>
                <el-button
                    v-has-perm="['region.update']"
                    type="primary"
                    size="small"
                    text
                    @click="handleEdit(row)"
                >
                    {{ $t('common.edit') }}
                </el-button>
                <el-button
                    v-has-perm="['region.delete']"
                    type="danger"
                    size="small"
                    text
                    @click="handleDelete(row.id, row.name)"
                >
                    {{ $t('common.delete') }}
                </el-button>
            </template>
        </ProTable>

        <!-- 表单弹窗 -->
        <RegionForm v-model="formVisible" :form-data="formData" @success="getList" />
    </div>
</template>

<script setup lang="ts" name="RegionList">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { regionApi } from '@/api/region'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'

import RegionForm from './components/RegionForm.vue'

const { t } = useI18n()

interface BreadcrumbItem {
    id: number
    name: string
    level: number
}

const currentParentId = ref(0)
const currentLevel = ref(0)
const breadcrumbs = ref<BreadcrumbItem[]>([])

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
    handleDelete
} = useListPage<any, { keyword: string }>({
    fetchFn: (params) => {
        const query: Record<string, any> = {
            page_no: params.page,
            page_size: params.limit,
            parent_id: currentParentId.value
        }
        if (params.keyword?.trim()) {
            query.keyword = params.keyword.trim()
        }
        return regionApi.getList(query)
    },
    deleteFn: (id) => regionApi.delete(id),
    defaultSearchForm: { keyword: '' }
})

// 列定义
const columns: ProColumn[] = [
    { key: 'name', label: t('regionMgmt.regionName'), prop: 'name', minWidth: 200, required: true },
    { key: 'code', label: t('regionMgmt.regionCode'), prop: 'code', width: 150 },
    { key: 'level', label: t('regionMgmt.level'), prop: 'level', width: 150 },
    { key: 'sort', label: t('common.sort'), prop: 'sort', width: 100 },
    { key: 'status', label: t('common.status'), prop: 'status', width: 100 },
    { key: 'action', label: t('common.operation'), width: 320, fixed: 'right', required: true },
]

const formVisible = ref(false)
const formData = ref<Record<string, any>>({})

const levelTextMap = computed(
    () =>
        ({
            1: t('regionMgmt.levelOptions.province'),
            2: t('regionMgmt.levelOptions.city'),
            3: t('regionMgmt.levelOptions.district'),
            4: t('regionMgmt.levelOptions.street')
        }) as Record<number, string>
)
const levelTagMap: Record<number, 'primary' | 'success' | 'warning' | 'info' | 'danger'> = {
    1: 'primary',
    2: 'success',
    3: 'warning',
    4: 'info'
}

const handleDrillDown = (row: any) => {
    breadcrumbs.value.push({ id: row.id, name: row.name, level: row.level })
    currentParentId.value = row.id
    currentLevel.value = row.level
    searchForm.keyword = ''
    pagination.page = 1
    getList()
}

const navigateTo = (index: number) => {
    if (index === -1) {
        // Navigate to root
        breadcrumbs.value = []
        currentParentId.value = 0
        currentLevel.value = 0
    } else {
        // Navigate to specific breadcrumb level
        const target = breadcrumbs.value[index]
        breadcrumbs.value = breadcrumbs.value.slice(0, index + 1)
        currentParentId.value = target.id
        currentLevel.value = target.level
    }
    searchForm.keyword = ''
    pagination.page = 1
    getList()
}

const handleAdd = () => {
    const level = currentParentId.value === 0 ? 1 : currentLevel.value + 1
    const parentName =
        breadcrumbs.value.length > 0 ? breadcrumbs.value[breadcrumbs.value.length - 1].name : ''
    formData.value = {
        parent_id: currentParentId.value,
        parent_name: parentName,
        level,
        sort: 0,
        status: 1
    }
    formVisible.value = true
}

const handleAddChild = (row: any) => {
    formData.value = {
        parent_id: row.id,
        parent_name: row.name,
        level: (row.level || 0) + 1,
        sort: 0,
        status: 1
    }
    formVisible.value = true
}

const handleEdit = (row: any) => {
    formData.value = { ...row }
    formVisible.value = true
}
</script>

<style lang="scss" scoped>
.region-container {
    :deep(.el-breadcrumb) {
        font-size: 14px;

        a {
            cursor: pointer;
            color: var(--el-color-primary);

            &:hover {
                text-decoration: underline;
            }
        }
    }
}
</style>
