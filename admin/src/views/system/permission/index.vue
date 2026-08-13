<template>
    <div class="permission-container">
        <div class="page-head">
            <div>
                <div class="page-title">{{ $t('permission.title') }}</div>
                <div class="page-desc">{{ $t('permission.desc') }}</div>
            </div>
            <div class="page-actions">
                <el-button
                    v-has-perm="['system.permission.create']"
                    type="primary"
                    @click="handleAdd"
                >
                    <i class="i-svg:plus" />
                    {{ $t('permission.addPermission') }}
                </el-button>
            </div>
        </div>

        <div class="filter-bar">
            <el-input
                v-model="searchForm.keyword"
                :placeholder="$t('permission.searchPlaceholder')"
                clearable
                style="width: 220px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-label">{{ $t('permission.group') }}：</span>
            <el-input
                v-model="searchForm.group"
                :placeholder="$t('permission.groupPlaceholder')"
                clearable
                style="width: 160px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-sp" />
            <el-button @click="resetSearch">{{ $t('common.reset') }}</el-button>
            <el-button type="primary" @click="handleSearch">{{ $t('common.search') }}</el-button>
        </div>

        <ProTable
            :title="$t('permission.title')"
            storage-key="permission-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #status="{ row }">
                <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
                    {{ row.status === 1 ? $t('common.enable') : $t('common.disable') }}
                </el-tag>
            </template>

            <template #action="{ row }">
                <el-button
                    v-has-perm="['system.permission.update']"
                    type="primary"
                    size="small"
                    text
                    @click="handleEdit(row)"
                >
                    {{ $t('common.edit') }}
                </el-button>
                <el-button
                    v-has-perm="['system.permission.delete']"
                    type="danger"
                    size="small"
                    text
                    @click="handleDelete(row.id, row.title)"
                >
                    {{ $t('common.delete') }}
                </el-button>
            </template>
        </ProTable>

        <!-- 新增/编辑弹窗 -->
        <PermissionForm
            v-model="formVisible"
            :form-data="currentFormData"
            @success="getList"
        />
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { permissionApi } from '@/api/permission'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'
import type { PermissionInfo } from '@/types/system'

import PermissionForm from './components/PermissionForm.vue'

const { t } = useI18n()

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
} = useListPage<PermissionInfo, { keyword: string; group: string }>({
    fetchFn: (params) => permissionApi.getPermissionList(params),
    deleteFn: (id) => permissionApi.deletePermission(id),
    defaultSearchForm: { keyword: '', group: '' }
})

// 列定义
const columns: ProColumn[] = [
    { key: 'id', label: t('common.id'), prop: 'id', width: 80, required: true },
    { key: 'name', label: t('permission.permCode'), prop: 'name', minWidth: 180, showOverflowTooltip: true },
    { key: 'title', label: t('permission.permName'), prop: 'title', width: 150 },
    { key: 'group', label: t('permission.group'), prop: 'group', width: 120 },
    { key: 'description', label: t('permission.description'), prop: 'description', minWidth: 180, showOverflowTooltip: true },
    { key: 'status', label: t('common.status'), width: 100 },
    { key: 'sort', label: t('common.sort'), prop: 'sort', width: 80 },
    { key: 'created_at', label: t('common.createdAt'), prop: 'created_at', width: 200 },
    { key: 'action', label: t('common.operation'), width: 160, fixed: 'right', required: true },
]

// 表单弹窗
const formVisible = ref(false)
const currentFormData = ref<Partial<PermissionInfo>>({})

const handleAdd = () => {
    currentFormData.value = {}
    formVisible.value = true
}

const handleEdit = (row: PermissionInfo) => {
    currentFormData.value = { ...row }
    formVisible.value = true
}
</script>
