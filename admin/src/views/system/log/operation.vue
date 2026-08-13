<template>
    <div class="log-container">
        <div class="page-head">
            <div>
                <div class="page-title">{{ $t('log.operationTitle') }}</div>
                <div class="page-desc">{{ $t('log.operationDesc') }}</div>
            </div>
        </div>

        <div class="filter-bar">
            <el-input
                v-model="searchForm.keyword"
                :placeholder="$t('log.keywordPlaceholder')"
                clearable
                style="width: 220px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-label">{{ $t('log.method') }}：</span>
            <el-select
                v-model="searchForm.method"
                :placeholder="$t('common.all')"
                clearable
                style="width: 130px"
                @change="handleSearch"
            >
                <el-option label="GET" value="GET" />
                <el-option label="POST" value="POST" />
                <el-option label="PUT" value="PUT" />
                <el-option label="DELETE" value="DELETE" />
            </el-select>
            <span class="filter-sp" />
            <el-button @click="resetSearch">{{ $t('common.reset') }}</el-button>
            <el-button type="primary" @click="handleSearch">{{ $t('common.search') }}</el-button>
        </div>

        <ProTable
            :title="$t('log.operationTitle')"
            storage-key="operation-log-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #method="{ row }">
                <el-tag :type="methodTagType(row.method)" size="small" effect="light">
                    {{ row.method }}
                </el-tag>
            </template>

            <template #execution_time="{ row }">
                {{ row.execution_time ? (row.execution_time * 1000).toFixed(0) : '-' }}
            </template>
        </ProTable>
    </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

import { logApi } from '@/api/log'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'

const { t } = useI18n()

const methodTagType = (method: string) => {
    const map: Record<string, string> = {
        GET: 'success',
        POST: 'primary',
        PUT: 'warning',
        DELETE: 'danger'
    }
    return (map[method] || 'info') as any
}

// 列定义
const columns: ProColumn[] = [
    { key: 'id', label: t('common.id'), prop: 'id', width: 80, required: true },
    { key: 'username', label: t('log.operator'), prop: 'username', width: 120 },
    { key: 'method', label: t('log.method'), width: 120 },
    { key: 'path', label: t('log.requestPath'), prop: 'path', minWidth: 200, showOverflowTooltip: true },
    { key: 'action', label: t('log.action'), prop: 'action', width: 120 },
    { key: 'description', label: t('log.actionDesc'), prop: 'description', minWidth: 160, showOverflowTooltip: true },
    { key: 'ip', label: t('log.ip'), prop: 'ip', width: 140 },
    { key: 'execution_time', label: t('log.duration'), width: 120 },
    { key: 'operation_time', label: t('log.operationTime'), prop: 'operation_time', width: 170 },
]

const {
    list,
    loading,
    pagination,
    searchForm,
    handleSearch,
    resetSearch,
    handleSizeChange,
    handlePageChange
} = useListPage<any, { keyword: string; method: string }>({
    fetchFn: (params) => logApi.getOperationLogList(params),
    defaultSearchForm: { keyword: '', method: '' }
})
</script>
