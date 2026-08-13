<template>
    <div class="ap-market">
        <div class="page-head">
            <div>
                <h2 class="page-title">插件市场</h2>
                <p class="page-desc">浏览官网商城组件；购买后可在「我的应用」下载 zip，或在此本地上传安装</p>
            </div>
            <div class="page-actions">
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
                        <span v-if="item.code" class="code">{{ item.code }}</span>
                    </div>
                </div>
                <div class="card-actions">
                    <el-button type="primary" link @click="openBuy(item)">去官网{{ item.is_free ? '获取' : '购买' }}</el-button>
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
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { pluginApi } from '@/api/plugin'
import LocalUpload from './components/LocalUpload.vue'

const uploadVisible = ref(false)
const loading = ref(false)
const error = ref('')
const list = ref<Array<Record<string, any>>>([])
const router = useRouter()

const loadCatalog = async () => {
    loading.value = true
    error.value = ''
    try {
        const res = await pluginApi.catalog({ page: 1, limit: 48 })
        const payload = (res as any)?.data ?? res
        list.value = Array.isArray(payload?.list) ? payload.list : []
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
}
.code {
    font-size: 12px;
    color: var(--el-text-color-placeholder);
}
.card-actions {
    margin-top: auto;
}
</style>
