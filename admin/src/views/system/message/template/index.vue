<template>
    <div class="message-template">
        <div class="page-head">
            <div>
                <div class="page-title">{{ $t('messageTemplate.title') }}</div>
                <div class="page-desc">{{ $t('messageTemplate.desc') }}</div>
            </div>
            <div class="page-actions">
                <el-button type="primary" @click="handleAdd">
                    <i class="i-svg:plus" />
                    {{ $t('messageTemplate.addTemplate') }}
                </el-button>
            </div>
        </div>

        <!-- 搜索区域 -->
        <div class="filter-bar">
            <el-input
                v-model="searchForm.keyword"
                :placeholder="$t('messageTemplate.searchPlaceholder')"
                clearable
                style="width: 220px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-label">{{ $t('common.status') }}：</span>
            <el-select
                v-model="searchForm.status"
                :placeholder="$t('common.all')"
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

        <!-- 表格 -->
        <ProTable
            :title="$t('messageTemplate.title')"
            storage-key="message-template-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #sms_enabled="{ row }">
                <el-tag :type="row.sms_enabled ? 'success' : 'info'" size="small">
                    {{
                        row.sms_enabled
                            ? $t('messageTemplate.on')
                            : $t('messageTemplate.off')
                    }}
                </el-tag>
            </template>

            <template #wechat_official_enabled="{ row }">
                <el-tag
                    :type="row.wechat_official_enabled ? 'success' : 'info'"
                    size="small"
                >
                    {{
                        row.wechat_official_enabled
                            ? $t('messageTemplate.on')
                            : $t('messageTemplate.off')
                    }}
                </el-tag>
            </template>

            <template #wechat_mini_enabled="{ row }">
                <el-tag :type="row.wechat_mini_enabled ? 'success' : 'info'" size="small">
                    {{
                        row.wechat_mini_enabled
                            ? $t('messageTemplate.on')
                            : $t('messageTemplate.off')
                    }}
                </el-tag>
            </template>

            <template #status="{ row }">
                <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
                    {{ row.status === 1 ? $t('common.enable') : $t('common.disable') }}
                </el-tag>
            </template>

            <template #action="{ row }">
                <el-button type="primary" size="small" text @click="handleEdit(row)">{{
                    $t('common.edit')
                }}</el-button>
                <el-button
                    type="danger"
                    size="small"
                    text
                    @click="handleDelete(row.id, row.name)"
                    >{{ $t('common.delete') }}</el-button
                >
            </template>
        </ProTable>

        <!-- 编辑弹窗 -->
        <TemplateForm v-model="formVisible" :form-data="formData" @success="getList" />
    </div>
</template>

<script setup lang="ts" name="MessageTemplate">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { messageTemplateApi } from '@/api/message'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'

import TemplateForm from './components/TemplateForm.vue'

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
} = useListPage<any, { keyword: string; status?: number }>({
    fetchFn: (params) => messageTemplateApi.getList(params),
    deleteFn: (id) => messageTemplateApi.delete(id),
    defaultSearchForm: { keyword: '', status: undefined }
})

// 列定义
const columns: ProColumn[] = [
    { key: 'name', label: t('messageTemplate.templateName'), prop: 'name', minWidth: 150, showOverflowTooltip: true, required: true },
    { key: 'code', label: t('messageTemplate.templateCode'), prop: 'code', width: 180 },
    { key: 'sms_enabled', label: t('messageTemplate.sms'), width: 100, align: 'center' },
    { key: 'wechat_official_enabled', label: t('messageTemplate.official'), width: 100, align: 'center' },
    { key: 'wechat_mini_enabled', label: t('messageTemplate.miniapp'), width: 100, align: 'center' },
    { key: 'status', label: t('common.status'), width: 100, align: 'center' },
    { key: 'created_at', label: t('common.createdAt'), prop: 'created_at', width: 200 },
    { key: 'action', label: t('common.operation'), width: 150, fixed: 'right', required: true },
]

const formVisible = ref(false)
const formData = ref<Record<string, any>>({})

const handleAdd = () => {
    formData.value = {
        status: 1,
        sms_enabled: 0,
        wechat_official_enabled: 0,
        wechat_mini_enabled: 0
    }
    formVisible.value = true
}

const handleEdit = (row: any) => {
    formData.value = { ...row }
    formVisible.value = true
}
</script>
