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
            title="连接元点官方市场账号"
            width="440px"
            :destroy-on-close="true"
            :close-on-click-modal="false"
        >
            <p class="connect-lead">尚未连接元点官方市场账号，连接后可验证已购组件并一键安装。</p>
            <p class="connect-hint">将打开元点官方市场的登录授权页面，授权后自动完成连接与权益同步，全程无需填写任何凭证。</p>
            <template #footer>
                <el-button @click="connectVisible = false">取消</el-button>
                <el-button type="primary" :loading="connecting" @click="connect">连接账号并继续</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { pluginApi } from '@/api/plugin'
import { useMarketplaceOauth } from '@/composables/useMarketplaceOauth'
import { resetRouter } from '@/router'
import useUserStore from '@/store/modules/user.store'
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
const router = useRouter()
const { openPopup, awaitAuthCode } = useMarketplaceOauth()
let activePopup: Window | null = null

const callbackUrl =
    window.location.origin + import.meta.env.BASE_URL.replace(/\/$/, '') + '/marketplace/oauth-callback'

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
    const popup = openPopup()
    if (!popup) {
        ElMessage.error('请允许浏览器弹出窗口后重试')
        return
    }
    activePopup = popup
    connecting.value = true
    try {
        const res = await pluginApi.initiateConnect(callbackUrl)
        const intent = (res as any)?.data ?? res
        const authorizeUrl = String(intent?.authorize_url || '')
        if (!authorizeUrl) {
            popup.close()
            throw new Error('未返回授权地址')
        }
        const { state, code } = await awaitAuthCode(popup, authorizeUrl)
        await pluginApi.exchangeConnect({ state, code })
        ElMessage.success('已连接官网账号')
        connectVisible.value = false
        await loadCatalog()
    } catch (e: any) {
        try {
            popup.close()
        } catch {
            /* 窗口可能已被关闭 */
        }
        if (e?.message && e.message !== 'cancel') {
            ElMessage.error(e.message || '连接失败')
        }
    } finally {
        connecting.value = false
        activePopup = null
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
        const res = await pluginApi.installOfficial(code)
        await reportInstall(res.data)
        await reloadAdminRoutes()
        await router.replace('/plugins/installed')
    } catch (e: any) {
        ElMessage.error(e?.message || '安装失败')
    } finally {
        installing.value = ''
    }
}

const reloadAdminRoutes = async () => {
    const userStore = useUserStore()
    await userStore.getUserInfo()
    resetRouter()
}

const reportInstall = async (data?: { code?: string }) => {
    const code = data?.code || ''
    ElMessage.success(`后端已安装：${code}。开发机新页 404 时请重启 npm run dev`)
}

watch(connectVisible, (visible) => {
    if (!visible && connecting.value && activePopup && !activePopup.closed) {
        activePopup.close()
    }
})

const onInstalled = async () => {
    uploadVisible.value = false
    await reloadAdminRoutes()
    await router.replace('/plugins/installed')
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
.connect-lead {
    margin: 0;
    font-size: 14px;
    line-height: 1.6;
    font-weight: 500;
}
.connect-hint {
    margin: 12px 0 0;
    font-size: 13px;
    color: var(--el-text-color-secondary);
    line-height: 1.6;
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
