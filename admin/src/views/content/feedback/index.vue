<template>
    <div class="feedback-container">
        <div class="page-head">
            <div>
                <div class="page-title">{{ $t('feedbackMgmt.title') }}</div>
                <div class="page-desc">{{ $t('feedbackMgmt.desc') }}</div>
            </div>
        </div>
        <!-- 搜索区域 -->
        <div class="filter-bar">
            <span class="filter-label">{{ t('feedbackMgmt.type') }}：</span>
            <el-select
                v-model="searchForm.type"
                :placeholder="t('feedbackMgmt.typePlaceholder')"
                clearable
                style="width: 140px"
                @change="handleSearch"
            >
                <el-option :label="t('feedbackMgmt.types.suggestion')" value="suggestion" />
                <el-option :label="t('feedbackMgmt.types.bug')" value="bug" />
                <el-option :label="t('feedbackMgmt.types.complaint')" value="complaint" />
                <el-option :label="t('feedbackMgmt.types.other')" value="other" />
            </el-select>
            <span class="filter-label">{{ t('common.status') }}：</span>
            <el-select
                v-model="searchForm.status"
                :placeholder="t('feedbackMgmt.statusPlaceholder')"
                clearable
                style="width: 140px"
                @change="handleSearch"
            >
                <el-option :label="t('feedbackMgmt.statusTexts.pending')" :value="0" />
                <el-option :label="t('feedbackMgmt.statusTexts.processing')" :value="1" />
                <el-option :label="t('feedbackMgmt.statusTexts.replied')" :value="2" />
                <el-option :label="t('feedbackMgmt.statusTexts.closed')" :value="3" />
            </el-select>
            <span class="filter-sp" />
            <el-button @click="resetSearch">{{ t('common.reset') }}</el-button>
            <el-button type="primary" @click="handleSearch">{{ t('common.search') }}</el-button>
        </div>

        <!-- 列表 -->
        <ProTable
            :title="t('feedbackMgmt.title')"
            storage-key="feedback-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #type="{ row }">
                <el-tag :type="typeTagMap[row.type] || 'info'" size="small">
                    {{ typeText(row.type) }}
                </el-tag>
            </template>

            <template #status="{ row }">
                <el-tag :type="statusTagMap[row.status] || 'info'" size="small">
                    {{ statusText(row.status) }}
                </el-tag>
            </template>

            <template #action="{ row }">
                <el-button
                    v-has-perm="['feedback.detail']"
                    type="primary"
                    size="small"
                    text
                    @click="handleDetail(row)"
                    >{{ t('common.detail') }}</el-button
                >
                <el-button
                    v-if="row.status < 2"
                    v-has-perm="['feedback.reply']"
                    type="success"
                    size="small"
                    text
                    @click="handleReply(row)"
                    >{{ t('feedbackMgmt.reply') }}</el-button
                >
                <el-button
                    v-if="row.status < 3"
                    v-has-perm="['feedback.close']"
                    type="warning"
                    size="small"
                    text
                    @click="handleClose(row)"
                    >{{ t('feedbackMgmt.close') }}</el-button
                >
                <el-button
                    v-has-perm="['feedback.delete']"
                    type="danger"
                    size="small"
                    text
                    @click="handleDeleteRow(row)"
                    >{{ t('common.delete') }}</el-button
                >
            </template>
        </ProTable>

        <!-- 详情/回复对话框 -->
        <el-dialog
            v-model="dialogVisible"
            :title="
                dialogMode === 'reply'
                    ? t('feedbackMgmt.replyDialog')
                    : t('feedbackMgmt.detailDialog')
            "
            width="600px"
            destroy-on-close
        >
            <el-descriptions v-if="currentRow" :column="1" border>
                <el-descriptions-item :label="t('feedbackMgmt.feedbackId')">{{
                    currentRow.id
                }}</el-descriptions-item>
                <el-descriptions-item :label="t('feedbackMgmt.type')">
                    <el-tag :type="typeTagMap[currentRow.type] || 'info'" size="small">
                        {{ typeText(currentRow.type) }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item :label="t('common.status')">
                    <el-tag :type="statusTagMap[currentRow.status] || 'info'" size="small">
                        {{ statusText(currentRow.status) }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item :label="t('feedbackMgmt.contact')">{{
                    currentRow.contact || '-'
                }}</el-descriptions-item>
                <el-descriptions-item :label="t('feedbackMgmt.submittedAt')">{{
                    currentRow.created_at
                }}</el-descriptions-item>
                <el-descriptions-item :label="t('feedbackMgmt.content')">{{
                    currentRow.content
                }}</el-descriptions-item>
                <el-descriptions-item
                    v-if="currentRow.reply"
                    :label="t('feedbackMgmt.replyContent')"
                    >{{ currentRow.reply }}</el-descriptions-item
                >
                <el-descriptions-item
                    v-if="currentRow.replied_at"
                    :label="t('feedbackMgmt.repliedAt')"
                    >{{ currentRow.replied_at }}</el-descriptions-item
                >
            </el-descriptions>

            <div v-if="dialogMode === 'reply'" style="margin-top: 16px">
                <el-input
                    v-model="replyContent"
                    type="textarea"
                    :rows="4"
                    :placeholder="t('feedbackMgmt.replyPlaceholder')"
                />
            </div>

            <template #footer>
                <el-button @click="dialogVisible = false">{{ t('common.close') }}</el-button>
                <el-button
                    v-if="dialogMode === 'reply'"
                    type="primary"
                    :loading="submitLoading"
                    @click="submitReply"
                    >{{ t('feedbackMgmt.submitReply') }}</el-button
                >
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ElMessage, ElMessageBox } from 'element-plus'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { feedbackApi } from '@/api/feedback'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import type { FeedbackInfo } from '@/types/content'
import { useListPage } from '@/hooks/useListPage'

const { t } = useI18n()

const typeTagMap: Record<string, 'primary' | 'success' | 'warning' | 'info' | 'danger'> = {
    suggestion: 'primary',
    bug: 'danger',
    complaint: 'warning',
    other: 'info'
}
const statusTagMap: Record<number, 'primary' | 'success' | 'warning' | 'info' | 'danger'> = {
    0: 'info',
    1: 'warning',
    2: 'success',
    3: 'info'
}

/** 将类型 code 翻译为显示文本（未知类型降级为原始值） */
function typeText(type: string): string {
    const key = `feedbackMgmt.types.${type}`
    const translated = t(key)
    return translated === key ? type : translated
}

/** 将状态 code 翻译为显示文本 */
function statusText(status: number): string {
    const map: Record<number, string> = {
        0: 'feedbackMgmt.statusTexts.pending',
        1: 'feedbackMgmt.statusTexts.processing',
        2: 'feedbackMgmt.statusTexts.replied',
        3: 'feedbackMgmt.statusTexts.closed'
    }
    return map[status] ? t(map[status]) : t('feedbackMgmt.unknown')
}

// 列定义
const columns: ProColumn[] = [
    { key: 'id', label: t('common.id'), prop: 'id', width: 70, required: true },
    { key: 'content', label: t('feedbackMgmt.content'), prop: 'content', minWidth: 250, showOverflowTooltip: true },
    { key: 'type', label: t('feedbackMgmt.type'), prop: 'type', width: 120 },
    { key: 'contact', label: t('feedbackMgmt.contact'), prop: 'contact', width: 150, showOverflowTooltip: true },
    { key: 'status', label: t('common.status'), prop: 'status', width: 100 },
    { key: 'created_at', label: t('feedbackMgmt.submittedAt'), prop: 'created_at', width: 170 },
    { key: 'action', label: t('common.operation'), width: 200, fixed: 'right', required: true },
]

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
    handlePageChange
} = useListPage<FeedbackInfo, { status: number | string; type: string }>({
    fetchFn: async (params) => {
        const res = await feedbackApi.getList(params)
        const payload = res?.data || {}
        return {
            ...res,
            data: {
                list: (payload.list || payload.data || []) as FeedbackInfo[],
                pagination: {
                    total: payload.total || 0,
                    page: params.page,
                    limit: params.limit,
                    total_pages: 0
                }
            }
        } as any
    },
    defaultSearchForm: { status: '', type: '' }
})

const submitLoading = ref(false)
const dialogVisible = ref(false)
const dialogMode = ref<'detail' | 'reply'>('detail')
const currentRow = ref<FeedbackInfo | null>(null)
const replyContent = ref('')

const handleDetail = (row: FeedbackInfo) => {
    currentRow.value = row
    dialogMode.value = 'detail'
    dialogVisible.value = true
}

const handleReply = (row: FeedbackInfo) => {
    currentRow.value = row
    replyContent.value = ''
    dialogMode.value = 'reply'
    dialogVisible.value = true
}

const submitReply = async () => {
    if (!replyContent.value.trim()) {
        ElMessage.warning(t('feedbackMgmt.replyContentRequired'))
        return
    }
    submitLoading.value = true
    try {
        await feedbackApi.reply(currentRow.value!.id, replyContent.value)
        ElMessage.success(t('feedbackMgmt.replySuccess'))
        dialogVisible.value = false
        await getList()
    } finally {
        submitLoading.value = false
    }
}

const handleClose = async (row: FeedbackInfo) => {
    try {
        await ElMessageBox.confirm(t('feedbackMgmt.closeConfirm'), t('common.tip'), { type: 'warning' })
    } catch {
        return
    }
    await feedbackApi.close(row.id)
    ElMessage.success(t('feedbackMgmt.closeSuccess'))
    await getList()
}

const handleDeleteRow = async (row: FeedbackInfo) => {
    try {
        await ElMessageBox.confirm(t('feedbackMgmt.deleteConfirm'), t('common.tip'), {
            type: 'warning'
        })
    } catch {
        return
    }
    await feedbackApi.delete(row.id)
    ElMessage.success(t('common.success'))
    await getList()
}
</script>
