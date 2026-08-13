<template>
    <div class="role-container">
        <div class="page-head">
            <div>
                <div class="page-title">{{ $t('role.title') }}</div>
                <div class="page-desc">{{ $t('role.desc') }}</div>
            </div>
            <div class="page-actions">
                <el-button
                    v-has-perm="['system.role.create']"
                    type="primary"
                    @click="handleAdd"
                >
                    <i class="i-svg:plus" />
                    {{ $t('role.addRole') }}
                </el-button>
            </div>
        </div>

        <!-- 搜索区域 -->
        <div class="filter-bar">
            <el-input
                v-model="searchForm.keyword"
                :placeholder="$t('role.searchPlaceholder')"
                clearable
                style="width: 220px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-label">{{ $t('common.status') }}：</span>
            <el-select
                v-model="searchForm.status"
                :placeholder="$t('role.statusPlaceholder')"
                clearable
                style="width: 130px"
                @change="handleSearch"
            >
                <el-option :label="$t('common.normal')" :value="1" />
                <el-option :label="$t('common.disable')" :value="0" />
            </el-select>
            <span class="filter-sp" />
            <el-button @click="resetSearch">{{ $t('common.reset') }}</el-button>
            <el-button type="primary" @click="handleSearch">{{ $t('common.search') }}</el-button>
        </div>

        <!-- 表格区域 -->
        <ProTable
            :title="$t('role.title')"
            storage-key="role-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            :batch-delete-fn="handleBatchDelete"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #is_system="{ row }">
                <el-tag :type="row.is_system ? 'danger' : 'info'" size="small">
                    {{ row.is_system ? $t('common.yes') : $t('common.no') }}
                </el-tag>
            </template>

            <template #status="{ row }">
                <el-switch
                    v-model="row.status"
                    :active-value="1"
                    :inactive-value="0"
                    :disabled="
                        !userStore.hasPermission('system.role.update') || row.is_system
                    "
                    @change="handleStatusChange(row)"
                />
            </template>

            <template #action="{ row }">
                <el-button
                    v-if="!row.is_system"
                    v-has-perm="['system.role.update']"
                    type="primary"
                    size="small"
                    text
                    @click="handleEdit(row)"
                >
                    {{ $t('common.edit') }}
                </el-button>
                <el-button
                    v-has-perm="['system.role.permission']"
                    type="success"
                    size="small"
                    text
                    @click="handleAssignPermissions(row)"
                >
                    {{ $t('common.assignPermission') }}
                </el-button>
                <el-button
                    v-if="!row.is_system"
                    v-has-perm="['system.role.delete']"
                    type="danger"
                    size="small"
                    text
                    @click="handleDelete(row.id, row.title)"
                >
                    {{ $t('common.delete') }}
                </el-button>
            </template>

            <template #batchActions="{ selectedIds, clearSelection }">
                <el-button
                    v-has-perm="['system.role.update']"
                    size="small"
                    type="warning"
                    @click="handleBatchDisable(selectedIds, clearSelection)"
                >
                    {{ $t('role.batchDisable') }}
                </el-button>
            </template>
        </ProTable>

        <!-- 新增/编辑弹窗 -->
        <RoleForm v-model="formVisible" :form-data="formData" @success="getList" />

        <!-- 权限分配弹窗 -->
        <AssignPermissionsDialog
            v-model="assignVisible"
            :role-info="currentRole"
            @success="getList"
        />
    </div>
</template>

<script setup lang="ts" name="RoleList">
import { ElMessage } from 'element-plus'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { roleApi } from '@/api/role'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'
import { useUserStore } from '@/store'
import type { RoleInfo } from '@/types/system'

import AssignPermissionsDialog from './components/AssignPermissionsDialog.vue'
import RoleForm from './components/RoleForm.vue'

const { t } = useI18n()
const userStore = useUserStore()

// 使用统一的列表页 composable
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
    handleBatchDelete,
    handleStatusChange
} = useListPage<RoleInfo, { keyword: string; status?: number }>({
    fetchFn: (params) => roleApi.getRoleList(params),
    deleteFn: (id) => roleApi.deleteRole(id),
    batchDeleteFn: (ids) => roleApi.batchDeleteRole({ ids }),
    updateStatusFn: (id, status) => roleApi.updateRoleStatus(id, { status }),
    defaultSearchForm: {
        keyword: '',
        status: undefined
    }
})

// 列定义
const columns: ProColumn[] = [
    { key: 'id', label: 'ID', prop: 'id', width: 80, required: true },
    { key: 'name', label: '角色标识', prop: 'name', width: 150 },
    { key: 'title', label: '角色名称', prop: 'title', width: 150 },
    { key: 'description', label: '描述', prop: 'description', showOverflowTooltip: true },
    { key: 'is_system', label: '系统角色', width: 120 },
    { key: 'status', label: '状态', width: 100 },
    { key: 'created_at', label: '创建时间', prop: 'created_at', width: 200 },
    { key: 'action', label: '操作', width: 250, fixed: 'right', required: true },
]

// 弹窗相关
const formVisible = ref(false)
const formData = ref<Partial<RoleInfo>>({})
const assignVisible = ref(false)
const currentRole = ref<RoleInfo | null>(null)

// 新增角色
const handleAdd = () => {
    formData.value = {
        status: 1
    }
    formVisible.value = true
}

// 编辑角色
const handleEdit = (row: RoleInfo) => {
    formData.value = { ...row }
    formVisible.value = true
}

// 分配权限
const handleAssignPermissions = (row: RoleInfo) => {
    currentRole.value = row
    assignVisible.value = true
}

// 批量停用
const handleBatchDisable = async (ids: number[], clearSelection: () => void) => {
    try {
        await Promise.all(ids.map((id) => roleApi.updateRoleStatus(id, { status: 0 })))
        ElMessage.success(t('role.batchDisableSuccess'))
        clearSelection()
        getList()
    } catch {
        ElMessage.error(t('role.batchDisableFailed'))
    }
}
</script>
