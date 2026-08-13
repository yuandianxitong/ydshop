<template>
    <div class="announcement-container">
        <div class="page-head">
            <div>
                <div class="page-title">{{ $t('announcementMgmt.title') }}</div>
                <div class="page-desc">{{ $t('announcementMgmt.desc') }}</div>
            </div>
            <div class="page-actions">
                <el-button
                    v-has-perm="['announcement.create']"
                    type="primary"
                    @click="handleAdd"
                >
                    <i class="i-svg:plus" />
                    {{ $t('announcementMgmt.addAnnouncement') }}
                </el-button>
            </div>
        </div>
        <!-- 搜索区域 -->
        <div class="filter-bar">
            <el-input
                v-model="searchForm.keyword"
                :placeholder="$t('announcementMgmt.titlePlaceholder')"
                clearable
                style="width: 220px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-label">{{ $t('announcementMgmt.announcementType') }}：</span>
            <el-select
                v-model="searchForm.type"
                :placeholder="$t('announcementMgmt.typePlaceholder')"
                clearable
                style="width: 130px"
                @change="handleSearch"
            >
                <el-option :label="$t('announcementMgmt.typeOptions.notice')" :value="1" />
                <el-option :label="$t('announcementMgmt.typeOptions.update')" :value="2" />
                <el-option :label="$t('announcementMgmt.typeOptions.activity')" :value="3" />
            </el-select>
            <span class="filter-label">{{ $t('common.status') }}：</span>
            <el-select
                v-model="searchForm.status"
                :placeholder="$t('common.selectPlaceholder')"
                clearable
                style="width: 130px"
                @change="handleSearch"
            >
                <el-option :label="$t('announcementMgmt.published')" :value="1" />
                <el-option :label="$t('announcementMgmt.draft')" :value="0" />
            </el-select>
            <span class="filter-sp" />
            <el-button @click="resetSearch">{{ $t('common.reset') }}</el-button>
            <el-button type="primary" @click="handleSearch">{{ $t('common.search') }}</el-button>
        </div>

        <!-- 表格区域 -->
        <ProTable
            :title="$t('announcementMgmt.title')"
            storage-key="announcement-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #title="{ row }">
                <span>{{ row.title }}</span>
            </template>

            <template #type="{ row }">
                <el-tag :type="typeTagMap[row.type] || 'info'" size="small">
                    {{ typeTextMap[row.type] || row.type }}
                </el-tag>
            </template>

            <template #status="{ row }">
                <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
                    {{
                        row.status === 1
                            ? $t('announcementMgmt.published')
                            : $t('announcementMgmt.draft')
                    }}
                </el-tag>
            </template>

            <template #action="{ row }">
                <el-button
                    v-has-perm="['announcement.update']"
                    type="primary"
                    size="small"
                    text
                    @click="handleEdit(row)"
                >
                    {{ $t('common.edit') }}
                </el-button>
                <el-button
                    v-has-perm="['announcement.delete']"
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
        <AnnouncementForm v-model="formVisible" :form-data="formData" @success="getList" />
    </div>
</template>

<script setup lang="ts" name="AnnouncementList">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { announcementApi } from '@/api/announcement'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'

import AnnouncementForm from './components/AnnouncementForm.vue'

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
} = useListPage<any, { keyword: string; type?: number; status?: number }>({
    fetchFn: (params) => announcementApi.getList(params),
    deleteFn: (id) => announcementApi.delete(id),
    defaultSearchForm: { keyword: '', type: undefined, status: undefined }
})

// 列定义
const columns: ProColumn[] = [
    { key: 'title', label: t('announcementMgmt.announcementTitle'), prop: 'title', minWidth: 250, showOverflowTooltip: true, required: true },
    { key: 'type', label: t('announcementMgmt.announcementType'), prop: 'type', width: 110 },
    { key: 'status', label: t('common.status'), prop: 'status', width: 100 },
    { key: 'sort', label: t('common.sort'), prop: 'sort', width: 100 },
    { key: 'publish_at', label: t('announcementMgmt.publishAt'), prop: 'publish_at', width: 160 },
    { key: 'created_at', label: t('common.createdAt'), prop: 'created_at', width: 160 },
    { key: 'action', label: t('common.operation'), width: 150, fixed: 'right', required: true },
]

const formVisible = ref(false)
const formData = ref<Record<string, any>>({})

const typeTextMap = computed(
    () =>
        ({
            1: t('announcementMgmt.typeOptions.notice'),
            2: t('announcementMgmt.typeOptions.update'),
            3: t('announcementMgmt.typeOptions.activity')
        }) as Record<number, string>
)
const typeTagMap: Record<number, 'primary' | 'success' | 'warning' | 'info' | 'danger'> = {
    1: 'primary',
    2: 'warning',
    3: 'success'
}

const handleAdd = () => {
    formData.value = { type: 1, status: 1, sort: 0 }
    formVisible.value = true
}

const handleEdit = (row: any) => {
    formData.value = { ...row }
    formVisible.value = true
}
</script>
