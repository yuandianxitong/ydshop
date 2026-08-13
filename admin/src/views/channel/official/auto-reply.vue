<template>
    <div class="auto-reply">
        <div class="page-head">
            <div>
                <div class="page-title">自动回复</div>
                <div class="page-desc">管理公众号自动回复规则</div>
            </div>
            <div class="page-actions">
                <el-button type="primary" @click="handleAdd">
                    <i class="i-svg:plus" />
                    {{ $t('channel.autoReply.addReply') }}
                </el-button>
            </div>
        </div>
        <!-- 搜索区域 -->
        <div class="filter-bar">
            <span class="filter-label">{{ $t('channel.autoReply.type') }}：</span>
            <el-select
                v-model="searchForm.type"
                :placeholder="$t('common.all')"
                clearable
                style="width: 150px"
                @change="handleSearch"
            >
                <el-option :label="$t('channel.autoReply.typeKeyword')" value="keyword" />
                <el-option :label="$t('channel.autoReply.typeSubscribe')" value="subscribe" />
                <el-option :label="$t('channel.autoReply.typeDefault')" value="default" />
            </el-select>
            <span class="filter-sp" />
            <el-button @click="resetSearch">{{ $t('common.reset') }}</el-button>
            <el-button type="primary" @click="handleSearch">{{ $t('common.search') }}</el-button>
        </div>

        <!-- 表格 -->
        <ProTable
            :title="$t('channel.autoReply.title')"
            storage-key="auto-reply-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #type="{ row }">
                <el-tag :type="typeTagMap[row.type]" size="small">{{
                    typeTextMap[row.type]
                }}</el-tag>
            </template>

            <template #match_type="{ row }">
                {{
                    row.match_type === 'exact'
                        ? $t('channel.autoReply.matchExact')
                        : $t('channel.autoReply.matchFuzzy')
                }}
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
                    @click="handleDelete(row.id, row.keyword || row.content)"
                    >{{ $t('common.delete') }}</el-button
                >
            </template>
        </ProTable>

        <!-- 编辑弹窗 -->
        <el-dialog
            :model-value="formVisible"
            :title="form.id ? $t('channel.autoReply.editReply') : $t('channel.autoReply.addReply')"
            width="550px"
            destroy-on-close
            @update:model-value="formVisible = $event"
        >
            <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
                <el-form-item :label="$t('channel.autoReply.replyType')" prop="type">
                    <el-select v-model="form.type" style="width: 100%">
                        <el-option :label="$t('channel.autoReply.typeKeyword')" value="keyword" />
                        <el-option
                            :label="$t('channel.autoReply.typeSubscribe')"
                            value="subscribe"
                        />
                        <el-option :label="$t('channel.autoReply.typeDefault')" value="default" />
                    </el-select>
                </el-form-item>
                <template v-if="form.type === 'keyword'">
                    <el-form-item :label="$t('channel.autoReply.keyword')" prop="keyword">
                        <el-input
                            v-model="form.keyword"
                            :placeholder="$t('channel.autoReply.keywordPlaceholder')"
                        />
                    </el-form-item>
                    <el-form-item :label="$t('channel.autoReply.matchType')">
                        <el-radio-group v-model="form.match_type">
                            <el-radio value="exact">{{
                                $t('channel.autoReply.matchExactFull')
                            }}</el-radio>
                            <el-radio value="fuzzy">{{
                                $t('channel.autoReply.matchFuzzyFull')
                            }}</el-radio>
                        </el-radio-group>
                    </el-form-item>
                </template>
                <el-form-item :label="$t('channel.autoReply.replyContent')" prop="content">
                    <el-input
                        v-model="form.content"
                        type="textarea"
                        :rows="4"
                        :placeholder="$t('channel.autoReply.contentPlaceholder')"
                    />
                </el-form-item>
                <el-form-item :label="$t('common.sort')">
                    <el-input-number v-model="form.sort_order" :min="0" />
                </el-form-item>
                <el-form-item :label="$t('common.status')">
                    <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="formVisible = false">{{ $t('common.cancel') }}</el-button>
                <el-button type="primary" :loading="submitting" @click="handleSubmit">{{
                    $t('common.confirm')
                }}</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts" name="WechatAutoReply">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { autoReplyApi } from '@/api/wechat'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'

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
} = useListPage<any, { type: string }>({
    fetchFn: (params) => {
        const query: Record<string, any> = {
            page: params.page,
            limit: params.limit
        }
        if (params.type) query.type = params.type
        return autoReplyApi.getList(query)
    },
    deleteFn: (id) => autoReplyApi.delete(id),
    defaultSearchForm: { type: '' }
})

// 列定义
const columns: ProColumn[] = [
    { key: 'type', label: t('channel.autoReply.type'), width: 120, required: true },
    { key: 'keyword', label: t('channel.autoReply.keyword'), prop: 'keyword', width: 200, showOverflowTooltip: true },
    { key: 'match_type', label: t('channel.autoReply.matchType'), width: 100 },
    { key: 'content', label: t('channel.autoReply.replyContent'), prop: 'content', minWidth: 250, showOverflowTooltip: true },
    { key: 'status', label: t('common.status'), width: 80, align: 'center' },
    { key: 'action', label: t('common.operation'), width: 150, fixed: 'right', required: true },
]

const formVisible = ref(false)
const submitting = ref(false)
const formRef = ref<FormInstance>()
const form = reactive<Record<string, any>>({
    type: 'keyword',
    keyword: '',
    match_type: 'exact',
    content: '',
    sort_order: 0,
    status: 1
})

const typeTextMap = computed<Record<string, string>>(() => ({
    keyword: t('channel.autoReply.typeKeywordShort'),
    subscribe: t('channel.autoReply.typeSubscribe'),
    default: t('channel.autoReply.typeDefault')
}))
const typeTagMap: Record<string, any> = {
    keyword: 'primary',
    subscribe: 'success',
    default: 'info'
}

const rules = computed<FormRules>(() => ({
    type: [
        { required: true, message: t('channel.autoReply.validate.typeRequired'), trigger: 'change' }
    ],
    content: [
        {
            required: true,
            message: t('channel.autoReply.validate.contentRequired'),
            trigger: 'blur'
        }
    ]
}))

const handleAdd = () => {
    Object.assign(form, {
        id: undefined,
        type: 'keyword',
        keyword: '',
        match_type: 'exact',
        content: '',
        sort_order: 0,
        status: 1
    })
    formVisible.value = true
}

const handleEdit = (row: any) => {
    Object.assign(form, row)
    formVisible.value = true
}

const handleSubmit = async () => {
    try {
        await formRef.value?.validate()
        submitting.value = true
        if (form.id) {
            await autoReplyApi.update(form.id, form)
        } else {
            await autoReplyApi.create(form)
        }
        ElMessage.success(form.id ? t('message.updateSuccess') : t('message.createSuccess'))
        formVisible.value = false
        getList()
    } catch (e: any) {
        if (e?.message) ElMessage.error(e.message)
    } finally {
        submitting.value = false
    }
}
</script>

<style lang="scss" scoped>
.auto-reply {
}
</style>
