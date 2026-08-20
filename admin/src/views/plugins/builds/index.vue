<template>
    <div class="plugin-builds-container">
        <div class="page-head">
            <div>
                <div class="page-title">云编译</div>
                <div class="page-desc">有 Node worker 时手动重建 admin / PC；安装插件不会自动入队</div>
            </div>
            <div class="page-actions">
                <el-button type="primary" @click="rebuild('admin')">重建 Admin</el-button>
                <el-button @click="rebuild('pc')">重建 PC</el-button>
            </div>
        </div>

        <div class="filter-bar">
            <el-input
                v-model="searchForm.code"
                placeholder="插件 code"
                clearable
                style="width: 220px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-sp" />
            <el-button @click="resetSearch">重置</el-button>
            <el-button type="primary" @click="handleSearch">查询</el-button>
        </div>

        <ProTable
            title="云编译任务"
            storage-key="plugin-build-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #status="{ row }">
                <el-tag :type="tagType(row.status)" size="small">{{ buildStatusLabel(row.status) }}</el-tag>
            </template>
            <template #action="{ row }">
                <el-button type="primary" size="small" text @click="openLog(row.log)">日志</el-button>
            </template>
        </ProTable>

        <el-dialog v-model="logVisible" title="构建日志" width="720px">
            <pre class="build-log">{{ log }}</pre>
        </el-dialog>
    </div>
</template>

<script setup lang="ts" name="PluginBuilds">
import { ElMessage } from 'element-plus'
import { computed, ref } from 'vue'

import { buildStatusLabel, pluginBuildApi, type PluginBuildInfo } from '@/api/plugin-build'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'

const {
    list,
    loading,
    pagination,
    searchForm,
    getList,
    handleSearch,
    resetSearch,
    handlePageChange,
    handleSizeChange,
} = useListPage<PluginBuildInfo, { code: string }>({
    fetchFn: async (params) => {
        const res = await pluginBuildApi.list({
            page_no: params.page,
            page_size: params.limit,
            code: params.code || undefined,
        })
        const total = res.data?.total || 0
        return {
            ...res,
            data: {
                list: res.data?.list || [],
                pagination: { current_page: params.page, per_page: params.limit, total, last_page: 1 },
            },
        }
    },
    defaultSearchForm: { code: '' },
})

const columns: ProColumn[] = [
    { key: 'id', label: 'ID', width: 80, required: true },
    { key: 'target', label: '目标', width: 90 },
    { key: 'trigger', label: '触发', width: 110 },
    { key: 'plugin_code', label: '插件', minWidth: 140 },
    { key: 'status', label: '状态', width: 140 },
    { key: 'started_at', label: '开始', width: 180 },
    { key: 'finished_at', label: '结束', width: 180 },
    { key: 'action', label: '操作', width: 100, fixed: 'right', required: true },
]

const log = ref('')
const logVisible = computed({
    get: () => log.value !== '',
    set: (v) => {
        if (!v) log.value = ''
    },
})

const tagType = (s: number) => (s === 2 ? 'success' : s === 3 ? 'danger' : s === 1 ? 'warning' : 'info')

const openLog = (text?: string) => {
    log.value = text || '（无日志）'
}

const rebuild = async (target: 'admin' | 'pc') => {
    try {
        await pluginBuildApi.rebuild(target)
        ElMessage.success(`已入队重建 ${target}，完成后请硬刷新`)
        await getList()
    } catch (e: any) {
        ElMessage.error(e?.message || '入队失败')
    }
}
</script>

<style scoped>
.build-log {
    max-height: 480px;
    overflow: auto;
    font-size: 12px;
    white-space: pre-wrap;
}
</style>
