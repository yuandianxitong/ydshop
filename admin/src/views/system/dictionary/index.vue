<template>
    <div class="dictionary-container">
        <div class="page-head">
            <div>
                <div class="page-title">{{ $t('dictionary.title') }}</div>
                <div class="page-desc">{{ $t('dictionary.desc') }}</div>
            </div>
            <div class="page-actions">
                <el-button
                    v-has-perm="'system.dictionary.create'"
                    type="primary"
                    @click="handleAddDict"
                >
                    <i class="i-svg:plus" />
                    {{ $t('dictionary.addDict') }}
                </el-button>
            </div>
        </div>

        <!-- 搜索区域 -->
        <div class="filter-bar">
            <el-input
                v-model="searchForm.keyword"
                :placeholder="$t('dictionary.searchPlaceholder')"
                clearable
                style="width: 220px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-label">{{ $t('common.status') }}：</span>
            <el-select
                v-model="searchForm.status"
                :placeholder="$t('dictionary.statusPlaceholder')"
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

        <!-- 主字典表格 -->
        <ProTable
            :title="$t('dictionary.title')"
            storage-key="dictionary-list"
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
                <el-button type="primary" size="small" text @click="handleOpenItems(row)">
                    {{ $t('dictionary.items') }}
                </el-button>
                <el-button
                    v-has-perm="'system.dictionary.update'"
                    type="primary"
                    size="small"
                    text
                    @click="handleEditDict(row)"
                >
                    {{ $t('common.edit') }}
                </el-button>
                <el-button
                    v-has-perm="'system.dictionary.delete'"
                    type="danger"
                    size="small"
                    text
                    @click="handleDelete(row.id, row.name)"
                >
                    {{ $t('common.delete') }}
                </el-button>
            </template>
        </ProTable>

        <!-- 字典项弹窗 -->
        <DictItemsDialog
            v-model="itemDialogVisible"
            :dict-id="currentDict?.id || 0"
            :dict-name="currentDict?.name || ''"
            :dict-code="currentDict?.code || ''"
        />

        <!-- 字典表单弹窗 -->
        <DictForm v-model="showDictForm" :form-data="dictFormData" @success="getList" />
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { dictionaryApi } from '@/api/dictionary'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'

import DictForm from './components/DictForm.vue'
import DictItemsDialog from './components/DictItemsDialog.vue'

const { t } = useI18n()

// ========== 字典列表 ==========
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
    fetchFn: (params) => dictionaryApi.getList(params),
    deleteFn: (id) => dictionaryApi.delete(id),
    defaultSearchForm: { keyword: '', status: undefined }
})

// 列定义
const columns: ProColumn[] = [
    { key: 'id', label: t('common.id'), prop: 'id', width: 80, required: true },
    { key: 'name', label: t('dictionary.dictName'), prop: 'name', minWidth: 140 },
    { key: 'code', label: t('dictionary.dictCode'), prop: 'code', minWidth: 160 },
    { key: 'description', label: t('dictionary.description'), prop: 'description', minWidth: 180, showOverflowTooltip: true },
    { key: 'sort', label: t('common.sort'), prop: 'sort', width: 80, align: 'center' },
    { key: 'status', label: t('common.status'), width: 100, align: 'center' },
    { key: 'created_at', label: t('common.createdAt'), prop: 'created_at', width: 200 },
    { key: 'action', label: t('common.operation'), width: 220, fixed: 'right', required: true },
]

// 字典表单
const showDictForm = ref(false)
const dictFormData = ref<any>({})

const handleAddDict = () => {
    dictFormData.value = {}
    showDictForm.value = true
}

const handleEditDict = (row: any) => {
    dictFormData.value = { ...row }
    showDictForm.value = true
}

// ========== 字典项 ==========
const itemDialogVisible = ref(false)
const currentDict = ref<any>(null)

const handleOpenItems = (row: any) => {
    currentDict.value = row
    itemDialogVisible.value = true
}
</script>
