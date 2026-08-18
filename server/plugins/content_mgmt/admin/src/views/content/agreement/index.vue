<template>
    <div class="agreement-container">
        <div class="page-head">
            <div>
                <div class="page-title">协议管理</div>
                <div class="page-desc">管理用户协议、隐私政策等内容</div>
            </div>
            <div class="page-actions">
                <el-button v-has-perm="['agreement.create']" type="primary" @click="handleAdd">
                    <i class="i-svg:plus" />
                    {{ $t('agreementMgmt.addAgreement') }}
                </el-button>
            </div>
        </div>
        <!-- 搜索区域 -->
        <div class="filter-bar">
            <el-input
                v-model="searchForm.keyword"
                :placeholder="$t('agreementMgmt.titlePlaceholder')"
                clearable
                style="width: 220px"
                @keyup.enter="handleSearch"
            />
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
            :title="$t('agreementMgmt.title')"
            storage-key="agreement-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #code="{ row }">
                <el-tag type="info" size="small">
                    {{ row.code }}
                </el-tag>
            </template>

            <template #status="{ row }">
                <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
                    {{ row.status === 1 ? $t('common.enable') : $t('common.disable') }}
                </el-tag>
            </template>

            <template #action="{ row }">
                <el-button
                    v-has-perm="['agreement.update']"
                    type="primary"
                    size="small"
                    text
                    @click="handleEdit(row)"
                >
                    {{ $t('common.edit') }}
                </el-button>
                <el-button
                    v-has-perm="['agreement.delete']"
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
        <AgreementForm v-model="formVisible" :form-data="formData" @success="getList" />
    </div>
</template>

<script setup lang="ts" name="AgreementList">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { agreementApi } from '@/api/agreement'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'

import AgreementForm from './components/AgreementForm.vue'

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
} = useListPage<any, { keyword: string; status?: number }>({
    fetchFn: (params) => agreementApi.getList(params),
    deleteFn: (id) => agreementApi.delete(id),
    defaultSearchForm: { keyword: '', status: undefined }
})

// 列定义
const columns: ProColumn[] = [
    { key: 'title', label: t('agreementMgmt.agreementTitle'), prop: 'title', minWidth: 250, showOverflowTooltip: true, required: true },
    { key: 'code', label: t('agreementMgmt.agreementCode'), prop: 'code', width: 180 },
    { key: 'status', label: t('common.status'), prop: 'status', width: 100 },
    { key: 'created_at', label: t('common.createdAt'), prop: 'created_at', width: 160 },
    { key: 'action', label: t('common.operation'), width: 150, fixed: 'right', required: true },
]

const formVisible = ref(false)
const formData = ref<Record<string, any>>({})

const handleAdd = () => {
    formData.value = { status: 1 }
    formVisible.value = true
}

const handleEdit = (row: any) => {
    formData.value = { ...row }
    formVisible.value = true
}
</script>
