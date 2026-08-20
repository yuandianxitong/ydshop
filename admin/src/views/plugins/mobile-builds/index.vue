<template>
    <div class="mobile-builds-container">
        <div class="page-head">
            <div>
                <div class="page-title">客户端发布</div>
                <div class="page-desc">
                    新原生页才编译；官方预置页会跳过。上传后出现在微信「开发版本」。同机编译同时只跑一个任务，避免打满
                    4G 内存
                </div>
            </div>
            <div class="page-actions">
                <el-button @click="openChannel">小程序配置</el-button>
                <el-button
                    type="primary"
                    :loading="creating === 'h5'"
                    :disabled="buildBusy"
                    @click="create('h5')"
                >
                    编译 H5
                </el-button>
                <el-button
                    :loading="creating === 'mp-weixin'"
                    :disabled="buildBusy"
                    @click="create('mp-weixin')"
                >
                    编译小程序
                </el-button>
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
            <span class="channel-hint">
                {{ channelHint }}
            </span>
            <span class="filter-sp" />
            <el-button @click="resetSearch">重置</el-button>
            <el-button type="primary" @click="handleSearch">查询</el-button>
        </div>

        <ProTable
            title="客户端发布任务"
            storage-key="mobile-build-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
        >
            <template #status="{ row }">
                <el-tag :type="tagType(row.status)" size="small">{{
                    buildStatusLabel(row.status, 'mobile')
                }}</el-tag>
            </template>
            <template #action="{ row }">
                <el-button type="primary" size="small" text @click="openLog(row)">日志</el-button>
                <el-button
                    v-if="row.platform === 'mp-weixin' && (row.status === 2 || row.status === 4)"
                    type="primary"
                    size="small"
                    text
                    @click="upload(row.id)"
                >
                    上传
                </el-button>
                <el-button
                    v-if="row.status === 0 || row.status === 1"
                    type="warning"
                    size="small"
                    text
                    @click="cancelBuild(row)"
                >
                    取消
                </el-button>
                <el-button
                    v-if="row.status !== 1"
                    type="danger"
                    size="small"
                    text
                    @click="handleDelete(row.id, `${row.platform} #${row.id}`)"
                >
                    删除
                </el-button>
            </template>
        </ProTable>

        <el-dialog v-model="channelVisible" title="小程序配置" width="520px" destroy-on-close>
            <el-form label-width="88px">
                <el-form-item label="AppID">
                    <el-input v-model="appid" placeholder="微信小程序 AppID" />
                </el-form-item>
                <el-form-item label="私钥">
                    <el-input
                        v-model="uploadKey"
                        type="textarea"
                        :rows="4"
                        :placeholder="hasKey ? '已配置密钥，留空则不改' : 'miniprogram-ci 私钥'"
                    />
                </el-form-item>
                <el-form-item label="版本号">
                    <el-input v-model="uploadVersion" placeholder="如 1.0.0" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button v-if="hasKey" @click="clearChannel">清除密钥</el-button>
                <el-button @click="channelVisible = false">取消</el-button>
                <el-button type="primary" @click="saveChannel">保存</el-button>
            </template>
        </el-dialog>

        <el-dialog v-model="logVisible" title="构建日志" width="720px">
            <pre class="build-log">{{ log }}</pre>
        </el-dialog>
    </div>
</template>

<script setup lang="ts" name="PluginMobileBuilds">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'

import { buildStatusLabel, mobileBuildApi, type MobileBuildInfo } from '@/api/plugin-build'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'

const isPending = (status: number) => status === 0 || status === 1

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
    handleDelete,
} = useListPage<MobileBuildInfo, { code: string }>({
    fetchFn: async (params) => {
        const res = await mobileBuildApi.list({
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
    deleteFn: (id) => mobileBuildApi.delete(id),
    defaultSearchForm: { code: '' },
    immediate: false,
})

const columns: ProColumn[] = [
    { key: 'id', label: 'ID', width: 80, required: true },
    { key: 'platform', label: '端', width: 110 },
    { key: 'trigger', label: '触发', width: 110 },
    { key: 'plugin_code', label: '插件', minWidth: 140 },
    { key: 'status', label: '状态', width: 160 },
    { key: 'finished_at', label: '结束', width: 180 },
    { key: 'action', label: '操作', width: 260, fixed: 'right', required: true },
]

const creating = ref<'' | 'h5' | 'mp-weixin'>('')
const buildBusy = computed(() => list.value.some((row) => isPending(row.status)))
const appid = ref('')
const uploadKey = ref('')
const hasKey = ref(false)
const uploadVersion = ref('1.0.0')
const channelVisible = ref(false)
const log = ref('')
const logId = ref<number | null>(null)
const logVisible = computed({
    get: () => log.value !== '',
    set: (v) => {
        if (!v) {
            log.value = ''
            logId.value = null
        }
    },
})

const channelHint = computed(() => {
    const id = appid.value || '未配置 AppID'
    const ver = uploadVersion.value || '1.0.0'
    return `${id} · 版本 ${ver}${hasKey.value ? ' · 已配置密钥' : ''}`
})

const tagType = (s: number) =>
    s === 2 || s === 4 ? 'success' : s === 3 ? 'danger' : s === 1 ? 'warning' : 'info'

let pollTimer: number | null = null
let pollBusy = false

const stopPoll = () => {
    if (pollTimer != null) {
        clearInterval(pollTimer)
        pollTimer = null
    }
}

const startPoll = () => {
    if (pollTimer != null) return
    pollTimer = window.setInterval(() => {
        void silentRefresh()
    }, 3000)
}

const syncPoll = () => {
    if (list.value.some((row) => isPending(row.status))) startPoll()
    else stopPoll()
}

const silentRefresh = async () => {
    if (pollBusy || document.hidden) return
    pollBusy = true
    try {
        const res = await mobileBuildApi.list({
            page_no: pagination.page,
            page_size: pagination.limit,
            code: searchForm.code || undefined,
        })
        list.value = res.data?.list || []
        pagination.total = res.data?.total || 0
        if (logId.value != null) {
            const row = list.value.find((item) => item.id === logId.value)
            if (row) log.value = row.log || '（无日志）'
        }
        syncPoll()
    } catch {
        /* 轮询失败不打断页面 */
    } finally {
        pollBusy = false
    }
}

watch(list, syncPoll, { deep: true })

const loadChannel = async () => {
    const ch = await mobileBuildApi.channel()
    appid.value = ch.data?.wechat_appid || ''
    hasKey.value = !!ch.data?.has_key
    uploadVersion.value = ch.data?.wechat_upload_version || '1.0.0'
}

const openChannel = async () => {
    try {
        await loadChannel()
        uploadKey.value = ''
        channelVisible.value = true
    } catch (e: any) {
        ElMessage.error(e?.message || '加载配置失败')
    }
}

const create = async (platform: 'h5' | 'mp-weixin') => {
    if (creating.value) return
    creating.value = platform
    try {
        await mobileBuildApi.create(platform)
        ElMessage.success('已入队')
        pagination.page = 1
        await getList()
        startPoll()
    } catch (e: any) {
        ElMessage.error(e?.message || '入队失败')
    } finally {
        creating.value = ''
    }
}

const saveChannel = async () => {
    try {
        await mobileBuildApi.saveChannel({
            wechat_appid: appid.value,
            wechat_upload_key: uploadKey.value,
            wechat_upload_version: uploadVersion.value,
        })
        uploadKey.value = ''
        ElMessage.success('已保存')
        channelVisible.value = false
        await loadChannel()
    } catch (e: any) {
        ElMessage.error(e?.message || '保存失败')
    }
}

const clearChannel = async () => {
    await mobileBuildApi.clearChannel()
    ElMessage.success('已清除')
    await loadChannel()
}

const openLog = (row: MobileBuildInfo) => {
    logId.value = row.id
    log.value = row.log || '（无日志）'
}

const cancelBuild = async (row: MobileBuildInfo) => {
    try {
        await ElMessageBox.confirm(
            row.status === 1 ? '编译进程将尽快停止，确定取消？' : '确定取消该排队任务？',
            '取消任务',
            { type: 'warning', confirmButtonText: '确定', cancelButtonText: '返回' }
        )
    } catch {
        return
    }
    try {
        await mobileBuildApi.cancel(row.id)
        ElMessage.success('已取消')
        await silentRefresh()
    } catch {
        /* request.ts 已提示 */
    }
}

const upload = async (id: number) => {
    try {
        await mobileBuildApi.upload(id)
        ElMessage.success('已上传到微信开发版')
        await getList()
        await loadChannel()
    } catch (e: any) {
        ElMessage.error(e?.message || '上传失败')
    }
}

const onVisible = () => {
    if (!document.hidden && list.value.some((row) => isPending(row.status))) {
        void silentRefresh()
    }
}

onMounted(async () => {
    document.addEventListener('visibilitychange', onVisible)
    try {
        await loadChannel()
    } catch {
        /* 列表仍可看 */
    }
    await getList()
    syncPoll()
})

onUnmounted(() => {
    document.removeEventListener('visibilitychange', onVisible)
    stopPoll()
})
</script>

<style scoped>
.channel-hint {
    font-size: 13px;
    color: var(--el-text-color-secondary);
}
.build-log {
    max-height: 480px;
    overflow: auto;
    font-size: 12px;
    white-space: pre-wrap;
}
</style>
