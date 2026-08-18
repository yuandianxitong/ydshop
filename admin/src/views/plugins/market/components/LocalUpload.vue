<template>
    <div class="ap-upload">
        <el-upload
            drag
            :auto-upload="false"
            :show-file-list="false"
            accept=".zip"
            :on-change="onSelected"
        >
            <el-icon class="el-icon--upload"><UploadFilled /></el-icon>
            <div class="el-upload__text">拖入或点击选择 <em>.zip</em> 插件包</div>
        </el-upload>

        <div v-if="progress > 0" class="ap-progress">
            <el-progress :percentage="progress" />
            <div v-if="step" class="ap-step">{{ step }}</div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { UploadFilled } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { ref } from 'vue'

import { pluginApi } from '@/api/plugin'
import { waitForBuilds } from '@/api/plugin-build'

const emit = defineEmits<{ installed: [code: string] }>()
const progress = ref(0)
const step = ref('')

const onSelected = async (raw: any) => {
    const file = raw?.raw as File | undefined
    if (!file) return
    progress.value = 0
    step.value = '上传中…'
    try {
        const res = await pluginApi.uploadInstall(file, (p) => {
            progress.value = p
            if (p === 100) step.value = '安装中…（解包 / 校验 / 执行 SQL / 注册菜单）'
        })
        progress.value = 100
        const code = res.data?.code || ''
        const mode = res.data?.mode || res.data?.builds?.mode || 'cloud'
        if (mode === 'dev' || mode === 'sync') {
            ElMessage.success(`后端已安装：${code}。前端已软链，请刷新后台；新页 404 时重启 npm run dev`)
            step.value = ''
            emit('installed', code)
            return
        }
        step.value = '后端已安装，等待云编译…'
        const webIds = (res.data?.builds?.admin_pc || []).map((r) => r.id).filter(Boolean)
        const mobileIds = (res.data?.builds?.mobile || []).map((r) => r.id).filter(Boolean)
        try {
            await waitForBuilds(webIds, mobileIds)
            ElMessage.success(`后端已安装：${code}。请硬刷新后台`)
        } catch {
            ElMessage.warning(`后端已安装：${code}，编译未完成，请到「云编译」查看`)
        }
        step.value = ''
        emit('installed', code)
    } catch (e: any) {
        step.value = ''
        progress.value = 0
        ElMessage.error(e?.message || '安装失败')
    }
}
</script>

<style lang="scss" scoped>
.ap-upload {
    padding: 32px;
    max-width: 600px;
}
.ap-progress {
    margin-top: 16px;
}
.ap-step {
    margin-top: 8px;
    font-size: 13px;
    color: var(--el-text-color-secondary);
}
</style>
