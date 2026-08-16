<template>
    <div class="ap-market">
        <div class="page-head">
            <div>
                <h2 class="page-title">插件市场</h2>
                <p class="page-desc">
                    连接官网账号后可一键安装已购组件；也可在官网「我的应用」下载 zip 后本地上传
                </p>
            </div>
            <div class="page-actions">
                <template v-if="connected">
                    <span class="account-chip">{{ accountLabel }}</span>
                    <el-button @click="disconnect">断开</el-button>
                </template>
                <el-button v-else type="success" @click="connectVisible = true">连接官网账号</el-button>
                <el-button @click="loadCatalog">刷新目录</el-button>
                <el-button type="primary" @click="uploadVisible = true">本地上传</el-button>
            </div>
        </div>

        <el-alert
            v-if="error"
            type="warning"
            :closable="false"
            :title="error"
            style="margin-bottom: 16px"
        />

        <div v-loading="loading" class="market-grid">
            <el-empty v-if="!loading && !list.length" description="暂无上架的 Shop 组件，可先本地上传插件包" :image-size="96" />
            <article v-for="item in list" :key="item.code || item.id" class="market-card">
                <div class="card-icon">
                    <img v-if="item.icon" :src="item.icon" alt="" />
                    <i v-else class="i-lucide-blocks" />
                </div>
                <div class="card-body">
                    <h3>{{ item.name || item.code }}</h3>
                    <p>{{ item.summary || '付费商城组件' }}</p>
                    <div class="card-meta">
                        <el-tag size="small" :type="item.is_free ? 'success' : 'warning'">
                            {{ item.is_free ? '免费' : '市场购买' }}
                        </el-tag>
                        <el-tag v-if="item.installed" size="small" type="info">已安装{{ item.installed_version ? ` ${item.installed_version}` : '' }}</el-tag>
                        <el-tag v-else-if="item.owned" size="small">已购买</el-tag>
                        <span v-if="item.code" class="code">{{ item.code }}</span>
                    </div>
                </div>
                <div class="card-actions">
                    <el-button
                        v-if="item.owned || item.is_free"
                        type="primary"
                        :loading="installing === item.code"
                        :disabled="!!item.installed"
                        @click="install(item)"
                    >
                        {{ item.installed ? '已安装' : '一键安装' }}
                    </el-button>
                    <el-button v-else type="primary" link @click="openBuy(item)">
                        去官网购买
                    </el-button>
                </div>
            </article>
        </div>

        <el-dialog
            v-model="uploadVisible"
            title="本地上传插件包"
            width="640px"
            :destroy-on-close="true"
        >
            <LocalUpload @installed="onInstalled" />
        </el-dialog>

        <el-dialog
            v-model="connectVisible"
            title="连接官网账号"
            width="440px"
            :destroy-on-close="true"
            @closed="resetConnect"
        >
            <el-form :model="connectForm" label-width="72px" @submit.prevent="connect">
                <el-form-item label="账号">
                    <el-input v-model="connectForm.account" placeholder="官网手机号 / 账号" autocomplete="username" />
                </el-form-item>
                <el-form-item label="密码">
                    <el-input v-model="connectForm.password" type="password" placeholder="官网登录密码" show-password autocomplete="current-password" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="connectVisible = false">取消</el-button>
                <el-button type="primary" :loading="connecting" @click="connect">连接</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { pluginApi } from '@/api/plugin'
import LocalUpload from './components/LocalUpload.vue'

const uploadVisible = ref(false)
const connectVisible = ref(false)
const connecting = ref(false)
const installing = ref('')
const loading = ref(false)
const error = ref('')
const list = ref<Array<Record<string, any>>>([])
const connected = ref(false)
const account = ref<{ account?: string; nickname?: string } | null>(null)
const connectForm = ref({ account: '', password: '' })
const router = useRouter()

const accountLabel = computed(() => {
    const nick = account.value?.nickname || account.value?.account || '已连接'
    return nick
})

const loadCatalog = async () => {
    loading.value = true
    error.value = ''
    try {
        const res = await pluginApi.catalog({ page: 1, limit: 48 })
        const payload = (res as any)?.data ?? res
        list.value = Array.isArray(payload?.list) ? payload.list : []
        connected.value = !!payload?.connected
        account.value = payload?.account ?? null
    } catch (e: any) {
        error.value = e?.message || '官方市场暂不可达，仍可使用本地上传'
        list.value = []
    } finally {
        loading.value = false
    }
}

const openBuy = (item: Record<string, any>) => {
    const url = item.buy_url || (item.code ? `https://www.dev007.cn/market/${item.code}` : 'https://www.dev007.cn/market/apps?runtime=shop')
    window.open(url, '_blank', 'noopener')
}

const connect = async () => {
    if (!connectForm.value.account || !connectForm.value.password) {
        ElMessage.warning('请输入官网账号和密码')
        return
    }
    connecting.value = true
    try {
        await pluginApi.connectOfficial({ ...connectForm.value })
        ElMessage.success('已连接官网账号')
        connectVisible.value = false
        await loadCatalog()
    } catch (e: any) {
        ElMessage.error(e?.message || '连接失败')
    } finally {
        connecting.value = false
    }
}

const disconnect = async () => {
    try {
        await ElMessageBox.confirm('断开后无法一键安装，本地已装插件不受影响', '断开官网账号', { type: 'warning' })
        await pluginApi.disconnectOfficial()
        ElMessage.success('已断开')
        await loadCatalog()
    } catch {
        // cancelled
    }
}

const install = async (item: Record<string, any>) => {
    if (!connected.value) {
        connectVisible.value = true
        ElMessage.info('请先连接官网账号')
        return
    }
    const code = String(item.code || '')
    if (!code) return
    installing.value = code
    try {
        await pluginApi.installOfficial(code)
        ElMessage.success(`安装成功：${code}`)
        router.push('/plugins/installed')
    } catch (e: any) {
        ElMessage.error(e?.message || '安装失败')
    } finally {
        installing.value = ''
    }
}

const resetConnect = () => {
    connectForm.value = { account: '', password: '' }
}

const onInstalled = () => {
    uploadVisible.value = false
    router.push('/plugins/installed')
}

onMounted(loadCatalog)
</script>

<style lang="scss" scoped>
.page-head {
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.page-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}
.page-desc {
    font-size: 13px;
    color: var(--el-text-color-secondary);
    margin: 4px 0 0;
}
.page-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.account-chip {
    font-size: 13px;
    color: var(--el-text-color-regular);
    padding: 0 8px;
}
.market-grid {
    min-height: 240px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}
.market-card {
    border: 1px solid var(--el-border-color);
    border-radius: 10px;
    padding: 16px;
    background: var(--el-bg-color);
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.card-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: var(--el-fill-color);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    img { width: 100%; height: 100%; object-fit: cover; }
}
.card-body h3 {
    margin: 0 0 6px;
    font-size: 15px;
}
.card-body p {
    margin: 0;
    font-size: 13px;
    color: var(--el-text-color-secondary);
    line-height: 1.5;
}
.card-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 8px;
    flex-wrap: wrap;
}
.code {
    font-size: 12px;
    color: var(--el-text-color-placeholder);
}
.card-actions {
    margin-top: auto;
}
</style>
