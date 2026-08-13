<template>
    <div class="notification-container">
        <div class="page-head">
            <div>
                <div class="page-title">{{ $t('notificationMgmt.title') }}</div>
                <div class="page-desc">{{ $t('notificationMgmt.desc') }}</div>
            </div>
            <div class="page-actions">
                <el-button
                    v-has-perm="['system.notification.create']"
                    type="primary"
                    @click="handleAdd"
                >
                    <i class="i-svg:plus" />
                    {{ $t('notificationMgmt.addNotification') }}
                </el-button>
            </div>
        </div>

        <!-- 搜索区域 -->
        <div class="filter-bar">
            <el-input
                v-model="searchForm.keyword"
                :placeholder="$t('notificationMgmt.titlePlaceholder')"
                clearable
                style="width: 220px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-label">{{ $t('notificationMgmt.notificationType') }}：</span>
            <el-select
                v-model="searchForm.type"
                :placeholder="$t('notificationMgmt.typePlaceholder')"
                clearable
                style="width: 130px"
                @change="handleSearch"
            >
                <el-option :label="$t('notificationMgmt.typeOptions.system')" :value="1" />
                <el-option :label="$t('notificationMgmt.typeOptions.todo')" :value="2" />
                <el-option :label="$t('notificationMgmt.typeOptions.business')" :value="3" />
            </el-select>
            <span class="filter-sp" />
            <el-button @click="resetSearch">{{ $t('common.reset') }}</el-button>
            <el-button type="primary" @click="handleSearch">{{ $t('common.search') }}</el-button>
        </div>

        <!-- 表格区域 -->
        <ProTable
            :title="$t('notificationMgmt.title')"
            storage-key="notification-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #type="{ row }">
                <el-tag :type="typeTagMap[row.type] || 'info'" size="small">
                    {{ typeTextMap[row.type] || row.type }}
                </el-tag>
            </template>

            <template #target_type="{ row }">
                <el-tag
                    :type="row.target_type === 1 ? 'primary' : 'warning'"
                    size="small"
                    effect="plain"
                >
                    {{
                        row.target_type === 1
                            ? $t('notificationMgmt.scopeOptions.all')
                            : $t('notificationMgmt.scopeOptions.specified')
                    }}
                </el-tag>
            </template>

            <template #status="{ row }">
                <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
                    {{
                        row.status === 1
                            ? $t('notificationMgmt.published')
                            : $t('notificationMgmt.draft')
                    }}
                </el-tag>
            </template>

            <template #action="{ row }">
                <el-button
                    v-has-perm="['system.notification.update']"
                    type="primary"
                    size="small"
                    text
                    @click="handleEdit(row)"
                >
                    {{ $t('common.edit') }}
                </el-button>
                <el-button
                    v-has-perm="['system.notification.delete']"
                    type="danger"
                    size="small"
                    text
                    @click="handleDelete(row.id, row.title)"
                >
                    {{ $t('common.delete') }}
                </el-button>
            </template>
        </ProTable>

        <!-- 表单弹窗 -->
        <NotificationForm v-model="formVisible" :form-data="formData" @success="getList" />
    </div>
</template>

<script setup lang="ts" name="NotificationList">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { notificationApi } from '@/api/notification'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'

import NotificationForm from './components/NotificationForm.vue'

const { t } = useI18n()

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
    handleDelete
} = useListPage<any, { keyword: string; type?: number }>({
    fetchFn: (params) => {
        const query: Record<string, any> = {
            page: params.page,
            limit: params.limit
        }
        if (params.keyword?.trim()) query.keyword = params.keyword.trim()
        if (params.type !== undefined) query.type = params.type
        return notificationApi.getList(query)
    },
    deleteFn: (id) => notificationApi.delete(id),
    defaultSearchForm: { keyword: '', type: undefined }
})

// 列定义
const columns: ProColumn[] = [
    { key: 'title', label: t('notificationMgmt.notificationTitle'), prop: 'title', minWidth: 250, showOverflowTooltip: true, required: true },
    { key: 'type', label: t('notificationMgmt.notificationType'), prop: 'type', width: 110 },
    { key: 'target_type', label: t('notificationMgmt.targetScope'), prop: 'target_type', width: 120 },
    { key: 'reads_count', label: t('notificationMgmt.readCount'), prop: 'reads_count', width: 120 },
    { key: 'status', label: t('common.status'), prop: 'status', width: 100 },
    { key: 'created_at', label: t('common.createdAt'), prop: 'created_at', width: 200 },
    { key: 'action', label: t('common.operation'), width: 150, fixed: 'right', required: true },
]

const formVisible = ref(false)
const formData = ref<Record<string, any>>({})

const typeTextMap = computed(
    () =>
        ({
            1: t('notificationMgmt.typeOptions.system'),
            2: t('notificationMgmt.typeOptions.todo'),
            3: t('notificationMgmt.typeOptions.business')
        }) as Record<number, string>
)
const typeTagMap: Record<number, 'primary' | 'success' | 'warning' | 'info' | 'danger'> = {
    1: 'primary',
    2: 'warning',
    3: 'success'
}

const handleAdd = () => {
    formData.value = { type: 1, target_type: 1, status: 1 }
    formVisible.value = true
}

const handleEdit = (row: any) => {
    formData.value = { ...row }
    formVisible.value = true
}
</script>
