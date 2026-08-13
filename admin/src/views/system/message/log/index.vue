<template>
    <div class="message-log">
        <div class="page-head">
            <div>
                <div class="page-title">{{ $t('messageLog.title') }}</div>
                <div class="page-desc">{{ $t('messageLog.desc') }}</div>
            </div>
        </div>

        <!-- 搜索区域 -->
        <div class="filter-bar">
            <el-input
                v-model="searchForm.receiver"
                :placeholder="$t('messageLog.receiverPlaceholder')"
                clearable
                style="width: 220px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-label">{{ $t('messageLog.channel') }}：</span>
            <el-select
                v-model="searchForm.channel"
                :placeholder="$t('common.all')"
                clearable
                style="width: 150px"
                @change="handleSearch"
            >
                <el-option :label="$t('messageTemplate.sms')" value="sms" />
                <el-option :label="$t('messageTemplate.official')" value="wechat_official" />
                <el-option :label="$t('messageTemplate.miniapp')" value="wechat_mini" />
            </el-select>
            <span class="filter-label">{{ $t('common.status') }}：</span>
            <el-select
                v-model="searchForm.status"
                :placeholder="$t('common.all')"
                clearable
                style="width: 130px"
                @change="handleSearch"
            >
                <el-option :label="$t('messageLog.statusOptions.pending')" :value="0" />
                <el-option :label="$t('messageLog.statusOptions.success')" :value="1" />
                <el-option :label="$t('messageLog.statusOptions.failed')" :value="2" />
            </el-select>
            <span class="filter-sp" />
            <el-button @click="resetSearch">{{ $t('common.reset') }}</el-button>
            <el-button type="primary" @click="handleSearch">{{ $t('common.search') }}</el-button>
        </div>

        <!-- 表格 -->
        <ProTable
            :title="$t('messageLog.title')"
            storage-key="message-log-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #channel="{ row }">
                <el-tag size="small" :type="channelTagType[row.channel]">
                    {{ channelTextMap[row.channel] || row.channel }}
                </el-tag>
            </template>

            <template #status="{ row }">
                <el-tag :type="statusTagType[row.status]" size="small">
                    {{ statusTextMap[row.status] }}
                </el-tag>
            </template>
        </ProTable>
    </div>
</template>

<script setup lang="ts" name="MessageLog">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import { messageLogApi } from '@/api/message'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'

const { t } = useI18n()

const channelTextMap = computed<Record<string, string>>(() => ({
    sms: t('messageTemplate.sms'),
    wechat_official: t('messageTemplate.official'),
    wechat_mini: t('messageTemplate.miniapp')
}))
const channelTagType: Record<string, any> = {
    sms: 'primary',
    wechat_official: 'success',
    wechat_mini: 'warning'
}
const statusTextMap = computed<Record<number, string>>(() => ({
    0: t('messageLog.statusOptions.pending'),
    1: t('messageLog.statusOptions.success'),
    2: t('messageLog.statusOptions.failed')
}))
const statusTagType: Record<number, any> = { 0: 'info', 1: 'success', 2: 'danger' }

// 列定义
const columns: ProColumn[] = [
    { key: 'template_code', label: t('messageTemplate.templateCode'), prop: 'template_code', width: 160, required: true },
    { key: 'channel', label: t('messageLog.channel'), width: 120, align: 'center' },
    { key: 'receiver', label: t('messageLog.receiver'), prop: 'receiver', minWidth: 180, showOverflowTooltip: true },
    { key: 'status', label: t('common.status'), width: 90, align: 'center' },
    { key: 'error_msg', label: t('messageLog.errorMessage'), prop: 'error_msg', minWidth: 200, showOverflowTooltip: true },
    { key: 'sent_at', label: t('messageLog.sendTime'), prop: 'sent_at', width: 160 },
    { key: 'created_at', label: t('common.createdAt'), prop: 'created_at', width: 160 },
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
} = useListPage<any, { channel: string; status?: number; receiver: string }>({
    fetchFn: (params) => messageLogApi.getList(params),
    defaultSearchForm: { channel: '', status: undefined, receiver: '' }
})
</script>
