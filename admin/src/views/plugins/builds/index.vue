<template>
    <div class="ap-page">
        <div class="page-head">
            <div>
                <h2 class="page-title">云编译</h2>
                <p class="page-desc">安装插件后后台 / PC 在带 Node 的 worker 上重编，完成后请硬刷新</p>
            </div>
            <div class="page-actions">
                <el-button type="primary" @click="rebuild('admin')">重建 Admin</el-button>
                <el-button @click="rebuild('pc')">重建 PC</el-button>
                <el-button @click="load">刷新</el-button>
            </div>
        </div>
        <el-table v-loading="loading" :data="list" stripe>
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column prop="target" label="目标" width="90" />
            <el-table-column prop="trigger" label="触发" width="110" />
            <el-table-column prop="plugin_code" label="插件" width="140" />
            <el-table-column label="状态" width="120">
                <template #default="{ row }">
                    <el-tag :type="tagType(row.status)" size="small">{{ buildStatusLabel(row.status) }}</el-tag>
                </template>
            </el-table-column>
            <el-table-column prop="started_at" label="开始" width="180" />
            <el-table-column prop="finished_at" label="结束" width="180" />
            <el-table-column label="操作" width="100">
                <template #default="{ row }">
                    <el-button link type="primary" @click="log = row.log || '（无日志）'">日志</el-button>
                </template>
            </el-table-column>
        </el-table>
        <el-pagination
            v-if="total > pageSize"
            class="mt-4"
            background
            layout="prev, pager, next"
            :total="total"
            :page-size="pageSize"
            :current-page="page"
            @current-change="
                (p: number) => {
                    page = p
                    load()
                }
            "
        />
        <el-dialog v-model="logVisible" title="构建日志" width="720px">
            <pre class="build-log">{{ log }}</pre>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { computed, onMounted, ref } from 'vue'

import { buildStatusLabel, pluginBuildApi, type PluginBuildInfo } from '@/api/plugin-build'

const list = ref<PluginBuildInfo[]>([])
const loading = ref(false)
const total = ref(0)
const page = ref(1)
const pageSize = 20
const log = ref('')
const logVisible = computed({
    get: () => log.value !== '',
    set: (v) => {
        if (!v) log.value = ''
    },
})

const tagType = (s: number) => (s === 2 ? 'success' : s === 3 ? 'danger' : s === 1 ? 'warning' : 'info')

const load = async () => {
    loading.value = true
    try {
        const res = await pluginBuildApi.list({ page_no: page.value, page_size: pageSize })
        list.value = res.data?.list || []
        total.value = res.data?.total || 0
    } catch (e: any) {
        ElMessage.error(e?.message || '加载失败')
    } finally {
        loading.value = false
    }
}

const rebuild = async (target: 'admin' | 'pc') => {
    try {
        await pluginBuildApi.rebuild(target)
        ElMessage.success(`已入队重建 ${target}，完成后请硬刷新`)
        await load()
    } catch (e: any) {
        ElMessage.error(e?.message || '入队失败')
    }
}

onMounted(load)
</script>

<style scoped>
.build-log {
    max-height: 480px;
    overflow: auto;
    font-size: 12px;
    white-space: pre-wrap;
}
</style>
