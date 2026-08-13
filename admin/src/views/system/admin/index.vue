<template>
    <div class="admin-container">
        <div class="page-head">
            <div>
                <div class="page-title">{{ $t('admin.title') }}</div>
                <div class="page-desc">{{ $t('admin.desc') }}</div>
            </div>
            <div class="page-actions">
                <el-button
                    v-has-perm="['system.admin.create']"
                    type="primary"
                    @click="handleAdd"
                >
                    <i class="i-svg:plus" />
                    {{ $t('admin.addAdmin') }}
                </el-button>
            </div>
        </div>

        <!-- 搜索区域 -->
        <div class="filter-bar">
            <el-input
                v-model="searchForm.keyword"
                :placeholder="$t('admin.searchPlaceholder')"
                clearable
                style="width: 220px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-label">{{ $t('common.status') }}：</span>
            <el-select
                v-model="searchForm.status"
                :placeholder="$t('admin.statusPlaceholder')"
                clearable
                style="width: 130px"
                @change="handleSearch"
            >
                <el-option :label="$t('common.normal')" :value="1" />
                <el-option :label="$t('common.disable')" :value="0" />
            </el-select>
            <span class="filter-label">{{ $t('admin.department') }}：</span>
            <el-input
                v-model="searchForm.department"
                :placeholder="$t('admin.deptPlaceholder')"
                clearable
                style="width: 160px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-sp" />
            <el-button @click="resetSearch">{{ $t('common.reset') }}</el-button>
            <el-button type="primary" @click="handleSearch">{{ $t('common.search') }}</el-button>
        </div>

        <!-- 表格区域 -->
        <ProTable
            :title="$t('admin.title')"
            storage-key="admin-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            :batch-delete-fn="handleBatchDelete"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #avatar="{ row }">
                <el-avatar
                    :size="40"
                    :src="appStore.getImageUrl(row.avatar)"
                    :alt="row.nickname || row.username"
                >
                    {{ (row.nickname || row.username)?.[0] }}
                </el-avatar>
            </template>

            <template #roles="{ row }">
                <el-tag
                    v-for="role in row.roles"
                    :key="role.id"
                    size="small"
                    effect="light"
                    class="role-tag"
                >
                    {{ role.title }}
                </el-tag>
                <span v-if="!row.roles?.length" class="text-ink-400">{{
                    $t('common.noRole')
                }}</span>
            </template>

            <template #status="{ row }">
                <el-switch
                    v-model="row.status"
                    :active-value="1"
                    :inactive-value="0"
                    :disabled="
                        row.id == 1 || !userStore.hasPermission('system.admin.update')
                    "
                    @change="handleStatusChange(row)"
                />
            </template>

            <template #action="{ row }">
                <el-button
                    v-has-perm="['system.admin.update']"
                    type="primary"
                    size="small"
                    text
                    @click="handleEdit(row)"
                >
                    {{ $t('common.edit') }}
                </el-button>
                <el-button
                    v-has-perm="['system.admin.update']"
                    type="warning"
                    size="small"
                    text
                    @click="handleResetPassword(row)"
                >
                    {{ $t('common.resetPassword') }}
                </el-button>
                <el-button
                    v-if="row.id != 1"
                    v-has-perm="['system.admin.delete']"
                    type="danger"
                    size="small"
                    text
                    @click="handleDelete(row.id, row.username)"
                >
                    {{ $t('common.delete') }}
                </el-button>
            </template>

            <template #batchActions="{ selectedIds, clearSelection }">
                <el-button
                    v-has-perm="['system.admin.update']"
                    size="small"
                    type="warning"
                    @click="handleBatchDisable(selectedIds, clearSelection)"
                >
                    {{ $t('admin.batchDisable') }}
                </el-button>
            </template>
        </ProTable>

        <!-- 新增/编辑弹窗 -->
        <AdminForm
            v-model="formVisible"
            :form-data="formData"
            :role-options="roleOptions"
            :department-options="departmentOptions"
            @success="getList"
        />

        <!-- 重置密码弹窗 -->
        <ResetPasswordDialog
            v-model="resetPasswordVisible"
            :admin-info="currentAdmin"
            @success="getList"
        />
    </div>
</template>

<script setup lang="ts" name="AdminList">
import { ElMessage } from 'element-plus'
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { adminApi } from '@/api/admin'
import { departmentApi } from '@/api/department'
import { roleApi } from '@/api/role'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'
import { useUserStore } from '@/store'
import { useAppStore } from '@/store'
import type { AdminInfo, RoleOption } from '@/types/system'

import AdminForm from './components/AdminForm.vue'
import ResetPasswordDialog from './components/ResetPasswordDialog.vue'

const { t } = useI18n()
const userStore = useUserStore()
const appStore = useAppStore()

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
} = useListPage<AdminInfo, { keyword: string; status?: number; department: string }>({
    fetchFn: (params) => adminApi.getAdminList(params),
    deleteFn: (id) => adminApi.deleteAdmin(id),
    batchDeleteFn: (ids) => adminApi.batchDeleteAdmin({ ids }),
    updateStatusFn: (id, status) => adminApi.updateAdminStatus(id, { status }),
    defaultSearchForm: {
        keyword: '',
        status: undefined,
        department: ''
    }
})

// 列定义
const columns: ProColumn[] = [
    { key: 'id', label: 'ID', prop: 'id', width: 80, required: true },
    { key: 'avatar', label: '头像', width: 100 },
    { key: 'username', label: '账号', prop: 'username', minWidth: 120 },
    { key: 'nickname', label: '昵称', prop: 'nickname', width: 140 },
    { key: 'email', label: '邮箱', prop: 'email', width: 180, showOverflowTooltip: true },
    { key: 'mobile', label: '手机', prop: 'mobile', width: 160 },
    { key: 'department', label: '部门', prop: 'department', width: 140 },
    { key: 'position', label: '岗位', prop: 'position', width: 120, defaultVisible: false },
    { key: 'roles', label: '角色', width: 200 },
    { key: 'status', label: '状态', width: 100 },
    { key: 'last_login_time', label: '最后登录', prop: 'last_login_time', width: 160, defaultVisible: false },
    { key: 'created_at', label: '创建时间', prop: 'created_at', width: 160, defaultVisible: false },
    { key: 'action', label: '操作', width: 220, fixed: 'right', required: true },
]

// 弹窗相关
const formVisible = ref(false)
const formData = ref<Partial<AdminInfo>>({})
const resetPasswordVisible = ref(false)
const currentAdmin = ref<AdminInfo | null>(null)

// 角色选项
const roleOptions = ref<RoleOption[]>([])

// 部门选项
const departmentOptions = ref<any[]>([])

// 获取角色选项
const getRoleOptions = async () => {
    try {
        const response = await roleApi.getRoleOptions()
        roleOptions.value = response.data
    } catch (error) {
        console.error('获取角色选项失败:', error)
    }
}

// 获取部门选项
const getDepartmentOptions = async () => {
    try {
        const response = await departmentApi.getOptions()
        departmentOptions.value = response.data
    } catch (error) {
        console.error('获取部门选项失败:', error)
    }
}

// 新增管理员
const handleAdd = () => {
    formData.value = {
        status: 1
    }
    formVisible.value = true
}

// 编辑管理员
const handleEdit = (row: AdminInfo) => {
    formData.value = { ...row }
    formVisible.value = true
}

// 重置密码
const handleResetPassword = (row: AdminInfo) => {
    currentAdmin.value = row
    resetPasswordVisible.value = true
}

// 批量停用
const handleBatchDisable = async (ids: number[], clearSelection: () => void) => {
    try {
        await Promise.all(ids.map((id) => adminApi.updateAdminStatus(id, { status: 0 })))
        ElMessage.success(t('admin.batchDisableSuccess'))
        clearSelection()
        getList()
    } catch {
        ElMessage.error(t('admin.batchDisableFailed'))
    }
}

onMounted(() => {
    getRoleOptions()
    getDepartmentOptions()
})
</script>

<style lang="scss" scoped>
.admin-container {
    .role-tag {
        margin-right: 4px;
        margin-bottom: 4px;
    }
}
</style>
