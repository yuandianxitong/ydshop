<template>
    <div class="system-license">
        <div class="page-head">
            <div>
                <div class="page-title">产品授权</div>
                <div class="page-desc">
                    录入官网购买的授权码并绑定部署域名，激活后可使用商业能力与官方升级通道
                </div>
            </div>
            <div class="page-actions">
                <el-button :loading="loading" @click="loadStatus">刷新状态</el-button>
                <el-button
                    v-if="canActivate && hasKey"
                    :loading="heartbeatLoading"
                    @click="handleHeartbeat"
                >
                    立即校验
                </el-button>
            </div>
        </div>

        <div class="set-sections">
            <div class="set-card">
                <div class="set-card-head">
                    <h3>授权状态</h3>
                    <span class="dsc">当前部署实例与官网授权中心的同步结果</span>
                </div>
                <div class="set-card-body">
                    <div class="status-row">
                        <el-tag :type="statusTagType" size="large" effect="dark">
                            {{ statusLabel }}
                        </el-tag>
                        <span class="status-msg">{{ status?.message || '加载中…' }}</span>
                    </div>
                    <el-descriptions :column="2" border class="mt-4">
                        <el-descriptions-item label="产品标识">
                            {{ status?.product_slug || '-' }}
                        </el-descriptions-item>
                        <el-descriptions-item label="商业能力">
                            {{ status?.pro_enabled ? '已解锁' : '未解锁' }}
                        </el-descriptions-item>
                        <el-descriptions-item label="授权码">
                            {{ status?.state?.license_key_masked || '未录入' }}
                        </el-descriptions-item>
                        <el-descriptions-item label="绑定域名">
                            {{ status?.state?.domain || '未绑定' }}
                        </el-descriptions-item>
                        <el-descriptions-item label="授权中心">
                            {{ status?.site_base_url || '-' }}
                        </el-descriptions-item>
                        <el-descriptions-item label="最近校验">
                            {{ checkedAtText }}
                        </el-descriptions-item>
                    </el-descriptions>
                </div>
            </div>

            <div class="set-card">
                <div class="set-card-head">
                    <h3>录入 / 激活授权</h3>
                    <span class="dsc">授权码在官网「我的授权」中查看；首次激活会绑定域名</span>
                </div>
                <div class="set-card-body">
                    <el-alert
                        type="info"
                        :closable="false"
                        show-icon
                        class="mb-4"
                        title="请到 https://www.dev007.cn/user/licenses 复制授权码。激活后域名不可更换。"
                    />
                    <el-form :model="form" label-width="100px" style="max-width: 640px">
                        <el-form-item label="授权码" required>
                            <el-input
                                v-model="form.license_key"
                                placeholder="粘贴 32 位授权码"
                                clearable
                                :disabled="!canActivate"
                            />
                        </el-form-item>
                        <el-form-item label="部署域名">
                            <el-input
                                v-model="form.domain"
                                placeholder="留空则使用当前访问域名，如 shop.example.com"
                                clearable
                                :disabled="!canActivate"
                            />
                        </el-form-item>
                        <el-form-item>
                            <el-button
                                type="primary"
                                :loading="activateLoading"
                                :disabled="!canActivate"
                                @click="handleActivate"
                            >
                                激活授权
                            </el-button>
                            <el-button
                                v-if="canActivate && hasKey"
                                type="danger"
                                plain
                                :loading="clearLoading"
                                @click="handleClear"
                            >
                                清除本地授权
                            </el-button>
                        </el-form-item>
                    </el-form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts" name="SystemLicense">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted, reactive, ref } from 'vue'

import { licenseApi, type LicenseStatusResult } from '@/api/system/license'
import { useUserStore } from '@/store'

const userStore = useUserStore()
const loading = ref(false)
const activateLoading = ref(false)
const heartbeatLoading = ref(false)
const clearLoading = ref(false)
const status = ref<LicenseStatusResult | null>(null)

const form = reactive({
    license_key: '',
    domain: '',
})

const canActivate = computed(() => userStore.hasPermission('system.license.activate'))
const hasKey = computed(() => !!status.value?.state?.license_key_masked)

const statusLabel = computed(() => {
    const map: Record<string, string> = {
        active: '有效',
        grace: '宽限期',
        expired: '已过期',
        revoked: '已撤销',
        inactive: '未激活',
    }
    return map[status.value?.status || ''] || status.value?.status || '-'
})

const statusTagType = computed(() => {
    const map: Record<string, 'success' | 'warning' | 'danger' | 'info'> = {
        active: 'success',
        grace: 'warning',
        expired: 'danger',
        revoked: 'danger',
        inactive: 'info',
    }
    return map[status.value?.status || ''] || 'info'
})

const checkedAtText = computed(() => {
    const ts = status.value?.state?.checked_at
    if (!ts) return '-'
    return new Date(ts * 1000).toLocaleString()
})

async function loadStatus() {
    loading.value = true
    try {
        const res = await licenseApi.status()
        status.value = res.data || null
        if (status.value?.state?.domain && !form.domain) {
            form.domain = status.value.state.domain
        }
    } finally {
        loading.value = false
    }
}

async function handleActivate() {
    const key = form.license_key.trim()
    if (!key) {
        ElMessage.warning('请填写授权码')
        return
    }
    activateLoading.value = true
    try {
        const res = await licenseApi.activate({
            license_key: key,
            domain: form.domain.trim() || undefined,
        })
        status.value = res.data?.status || null
        form.license_key = ''
        ElMessage.success('激活成功')
        await loadStatus()
    } catch {
        // request 拦截器已提示
    } finally {
        activateLoading.value = false
    }
}

async function handleHeartbeat() {
    heartbeatLoading.value = true
    try {
        const res = await licenseApi.heartbeat()
        status.value = res.data?.status || null
        ElMessage.success('校验完成')
        await loadStatus()
    } catch {
        // ignore
    } finally {
        heartbeatLoading.value = false
    }
}

async function handleClear() {
    try {
        await ElMessageBox.confirm(
            '清除后需重新激活才能使用商业能力，确定继续？',
            '清除本地授权',
            { type: 'warning' }
        )
    } catch {
        return
    }
    clearLoading.value = true
    try {
        await licenseApi.clear()
        ElMessage.success('已清除')
        form.license_key = ''
        form.domain = ''
        await loadStatus()
    } finally {
        clearLoading.value = false
    }
}

onMounted(loadStatus)
</script>

<style scoped lang="scss">
.system-license {
    .status-row {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .status-msg {
        color: var(--el-text-color-secondary);
        font-size: 14px;
    }
    .mt-4 {
        margin-top: 16px;
    }
    .mb-4 {
        margin-bottom: 16px;
    }
}
</style>
