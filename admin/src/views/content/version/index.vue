<template>
    <div class="version-container">
        <div class="page-head">
            <div>
                <div class="page-title">应用版本</div>
                <div class="page-desc">管理应用版本发布信息</div>
            </div>
            <div class="page-actions">
                <el-button v-has-perm="['version.create']" type="primary" @click="handleAdd">
                    <i class="i-svg:plus" />
                    {{ $t('versionMgmt.addVersion') }}
                </el-button>
            </div>
        </div>
        <!-- 搜索区域 -->
        <div class="filter-bar">
            <span class="filter-label">{{ $t('versionMgmt.platform') }}：</span>
            <el-select
                v-model="searchForm.platform"
                :placeholder="$t('versionMgmt.platformPlaceholder')"
                clearable
                style="width: 150px"
                @change="handleSearch"
            >
                <el-option :label="$t('versionMgmt.platformOptions.android')" value="android" />
                <el-option :label="$t('versionMgmt.platformOptions.ios')" value="ios" />
                <el-option :label="$t('versionMgmt.platformOptions.harmony')" value="harmony" />
            </el-select>
            <span class="filter-label">{{ $t('common.status') }}：</span>
            <el-select
                v-model="searchForm.status"
                :placeholder="$t('common.selectPlaceholder')"
                clearable
                style="width: 130px"
                @change="handleSearch"
            >
                <el-option :label="$t('common.enable')" :value="1" />
                <el-option :label="$t('common.disable')" :value="0" />
            </el-select>
            <span class="filter-sp" />
            <el-button @click="resetSearch">{{ $t('common.reset') }}</el-button>
            <el-button type="primary" @click="handleSearch">{{ $t('common.search') }}</el-button>
        </div>

        <!-- 表格区域 -->
        <ProTable
            :title="$t('versionMgmt.title')"
            storage-key="version-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #platform="{ row }">
                <el-tag :type="platformTagMap[row.platform] || 'info'" size="small">
                    {{ platformTextMap[row.platform] || row.platform }}
                </el-tag>
            </template>

            <template #force_update="{ row }">
                <el-tag :type="row.force_update === 1 ? 'danger' : 'info'" size="small">
                    {{ row.force_update === 1 ? $t('common.yes') : $t('common.no') }}
                </el-tag>
            </template>

            <template #status="{ row }">
                <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
                    {{ row.status === 1 ? $t('common.enable') : $t('common.disable') }}
                </el-tag>
            </template>

            <template #action="{ row }">
                <el-button
                    v-has-perm="['version.update']"
                    type="primary"
                    size="small"
                    text
                    @click="handleEdit(row)"
                >
                    {{ $t('common.edit') }}
                </el-button>
                <el-button
                    v-has-perm="['version.delete']"
                    type="danger"
                    size="small"
                    text
                    @click="handleDelete(row.id, `${row.platform} v${row.version}`)"
                >
                    {{ $t('common.delete') }}
                </el-button>
            </template>
        </ProTable>

        <!-- 表单弹窗 -->
        <VersionForm v-model="formVisible" :form-data="formData" @success="getList" />
    </div>
</template>

<script setup lang="ts" name="VersionList">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { versionApi } from '@/api/version'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'

import VersionForm from './components/VersionForm.vue'

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
} = useListPage<any, { platform?: string; status?: number }>({
    fetchFn: (params) => versionApi.getList(params),
    deleteFn: (id) => versionApi.delete(id),
    defaultSearchForm: { platform: undefined, status: undefined }
})

// 列定义
const columns: ProColumn[] = [
    { key: 'platform', label: t('versionMgmt.platform'), prop: 'platform', width: 120, required: true },
    { key: 'version', label: t('versionMgmt.version'), prop: 'version', width: 120 },
    { key: 'version_code', label: t('versionMgmt.versionCode'), prop: 'version_code', width: 120 },
    { key: 'download_url', label: t('versionMgmt.downloadUrl'), prop: 'download_url', minWidth: 250, showOverflowTooltip: true },
    { key: 'force_update', label: t('versionMgmt.forceUpdate'), prop: 'force_update', width: 110 },
    { key: 'status', label: t('common.status'), prop: 'status', width: 100 },
    { key: 'created_at', label: t('common.createdAt'), prop: 'created_at', width: 160 },
    { key: 'action', label: t('common.operation'), width: 150, fixed: 'right', required: true },
]

const formVisible = ref(false)
const formData = ref<Record<string, any>>({})

const platformTextMap = computed(
    () =>
        ({
            android: t('versionMgmt.platformOptions.android'),
            ios: t('versionMgmt.platformOptions.ios'),
            harmony: t('versionMgmt.platformOptions.harmony')
        }) as Record<string, string>
)
const platformTagMap: Record<string, 'primary' | 'success' | 'warning' | 'info' | 'danger'> = {
    android: 'success',
    ios: 'primary',
    harmony: 'warning'
}

const handleAdd = () => {
    formData.value = { platform: '', force_update: 0, status: 1 }
    formVisible.value = true
}

const handleEdit = (row: any) => {
    formData.value = { ...row }
    formVisible.value = true
}
</script>
