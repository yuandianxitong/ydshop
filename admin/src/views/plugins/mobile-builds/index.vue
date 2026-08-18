<template>
    <div class="ap-page">
        <div class="page-head">
            <div>
                <h2 class="page-title">客户端发布</h2>
                <p class="page-desc">新原生页才编译；官方预置页会跳过。上传后出现在微信「开发版本」</p>
            </div>
            <div class="page-actions">
                <el-button type="primary" @click="create('h5')">编译 H5</el-button>
                <el-button @click="create('mp-weixin')">编译小程序</el-button>
                <el-button @click="load">刷新</el-button>
            </div>
        </div>

        <el-card shadow="never" class="mb-4">
            <div class="flex flex-wrap items-end gap-3">
                <el-input v-model="appid" placeholder="微信小程序 AppID" style="width: 240px" />
                <el-input v-model="uploadKey" type="textarea" :rows="2" placeholder="miniprogram-ci 私钥" style="width: 360px" />
                <el-button type="primary" @click="saveChannel">保存密钥</el-button>
                <el-button v-if="hasKey" @click="clearChannel">清除密钥</el-button>
                <span class="text-13 text-gray">版本 {{ uploadVersion }}{{ hasKey ? ' · 已配置密钥' : '' }}</span>
            </div>
        </el-card>

        <el-table v-loading="loading" :data="list" stripe>
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column prop="platform" label="端" width="110" />
            <el-table-column prop="trigger" label="触发" width="110" />
            <el-table-column prop="plugin_code" label="插件" width="140" />
            <el-table-column label="状态" width="140">
                <template #default="{ row }">
                    <el-tag :type="tagType(row.status)" size="small">{{
                        buildStatusLabel(row.status, 'mobile')
                    }}</el-tag>
                </template>
            </el-table-column>
            <el-table-column prop="finished_at" label="结束" width="180" />
            <el-table-column label="操作" width="160">
                <template #default="{ row }">
                    <el-button link type="primary" @click="log = row.log || '（无日志）'">日志</el-button>
                    <el-button
                        v-if="row.platform === 'mp-weixin' && (row.status === 2 || row.status === 4)"
                        link
                        type="primary"
                        @click="upload(row.id)"
                    >
                        上传
                    </el-button>
                </template>
            </el-table-column>
        </el-table>
        <el-dialog v-model="logVisible" title="构建日志" width="720px">
            <pre class="build-log">{{ log }}</pre>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { computed, onMounted, ref } from 'vue'

import { buildStatusLabel, mobileBuildApi, type MobileBuildInfo } from '@/api/plugin-build'

const list = ref<MobileBuildInfo[]>([])
const loading = ref(false)
const appid = ref('')
const uploadKey = ref('')
const hasKey = ref(false)
const uploadVersion = ref('1.0.0')
const log = ref('')
const logVisible = computed({
    get: () => log.value !== '',
    set: (v) => {
        if (!v) log.value = ''
    },
})

const tagType = (s: number) =>
    s === 2 || s === 4 ? 'success' : s === 3 ? 'danger' : s === 5 ? 'info' : s === 1 ? 'warning' : 'info'

const load = async () => {
    loading.value = true
    try {
        const res = await mobileBuildApi.list({ page_no: 1, page_size: 30 })
        list.value = res.data?.list || []
        const ch = await mobileBuildApi.channel()
        appid.value = ch.data?.wechat_appid || ''
        hasKey.value = !!ch.data?.has_key
        uploadVersion.value = ch.data?.wechat_upload_version || '1.0.0'
    } catch (e: any) {
        ElMessage.error(e?.message || '加载失败')
    } finally {
        loading.value = false
    }
}

const create = async (platform: 'h5' | 'mp-weixin') => {
    try {
        await mobileBuildApi.create(platform)
        ElMessage.success('已入队')
        await load()
    } catch (e: any) {
        ElMessage.error(e?.message || '入队失败')
    }
}

const saveChannel = async () => {
    try {
        await mobileBuildApi.saveChannel({ wechat_appid: appid.value, wechat_upload_key: uploadKey.value })
        uploadKey.value = ''
        ElMessage.success('已保存')
        await load()
    } catch (e: any) {
        ElMessage.error(e?.message || '保存失败')
    }
}

const clearChannel = async () => {
    await mobileBuildApi.clearChannel()
    ElMessage.success('已清除')
    await load()
}

const upload = async (id: number) => {
    try {
        await mobileBuildApi.upload(id)
        ElMessage.success('已上传到微信开发版')
        await load()
    } catch (e: any) {
        ElMessage.error(e?.message || '上传失败')
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
