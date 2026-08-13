<template>
    <div class="log-container">
        <div class="page-head">
            <div>
                <div class="page-title">{{ $t('log.loginTitle') }}</div>
                <div class="page-desc">{{ $t('log.loginDesc') }}</div>
            </div>
        </div>

        <div class="filter-bar">
            <el-input
                v-model="searchForm.keyword"
                :placeholder="$t('loginLog.usernamePlaceholder')"
                clearable
                style="width: 200px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-label">IP：</span>
            <el-input
                v-model="searchForm.ip"
                :placeholder="$t('loginLog.ipPlaceholder')"
                clearable
                style="width: 160px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-label">{{ $t('common.status') }}：</span>
            <el-select
                v-model="searchForm.login_result"
                :placeholder="$t('common.all')"
                clearable
                style="width: 130px"
                @change="handleSearch"
            >
                <el-option :label="$t('loginLog.resultOptions.success')" :value="1" />
                <el-option :label="$t('loginLog.resultOptions.failed')" :value="0" />
            </el-select>
            <span class="filter-sp" />
            <el-button @click="resetSearch">{{ $t('common.reset') }}</el-button>
            <el-button type="primary" @click="handleSearch">{{ $t('common.search') }}</el-button>
        </div>

        <ProTable
            :title="$t('loginLog.title')"
            storage-key="login-log-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #login_result="{ row }">
                <el-tag :type="row.login_result ? 'success' : 'danger'" size="small">
                    {{
                        row.login_result
                            ? $t('loginLog.resultOptions.success')
                            : $t('loginLog.resultOptions.failed')
                    }}
                </el-tag>
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

// 列定义
const columns: ProColumn[] = [
    { key: 'id', label: t('common.id'), prop: 'id', width: 80, required: true },
    { key: 'username', label: t('admin.username'), prop: 'username', width: 120 },
    { key: 'ip', label: t('loginLog.loginIp'), prop: 'ip', width: 140 },
    { key: 'browser', label: t('loginLog.browser'), prop: 'browser', width: 160, showOverflowTooltip: true },
    { key: 'os', label: t('loginLog.os'), prop: 'os', width: 130 },
    { key: 'login_result', label: t('loginLog.loginResult'), width: 120 },
    { key: 'login_message', label: t('loginLog.loginMessage'), prop: 'login_message', minWidth: 160, showOverflowTooltip: true },
    { key: 'login_time', label: t('loginLog.loginTime'), prop: 'login_time', width: 170 },
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
} = useListPage<any, { keyword: string; ip: string; login_result?: number }>({
    fetchFn: (params) => logApi.getLoginLogList(params),
    defaultSearchForm: { keyword: '', ip: '', login_result: undefined }
})
</script>
