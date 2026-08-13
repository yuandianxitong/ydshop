<template>
    <div class="ap-page">
        <div class="page-head">
            <div>
                <h2 class="page-title">已安装应用</h2>
                <p class="page-desc">已安装 {{ total }} 个模块，按分类查看与管理</p>
            </div>
            <div class="page-actions">
                <el-input
                    v-model="query"
                    placeholder="搜索应用名称"
                    style="width: 280px"
                    clearable
                >
                    <template #prefix>
                        <el-icon><Search /></el-icon>
                    </template>
                </el-input>
            </div>
        </div>

        <div v-loading="loading">
            <div v-for="g in groups" :key="g.key" class="ap-section">
                <div class="ap-section-head">
                    <span class="ap-section-bar"></span>
                    <span class="ap-section-title">{{ g.label }}</span>
                    <span v-if="g.desc" class="ap-section-desc">· {{ g.desc }}</span>
                </div>
                <div v-if="filtered(g.key).length" class="ap-grid">
                    <AppCard
                        v-for="app in filtered(g.key)"
                        :key="app.code"
                        :app="app"
                        @refresh="loadList"
                    />
                </div>
                <el-empty v-else description="无匹配应用" :image-size="80" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Search } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { computed, onMounted, ref } from 'vue'

import { pluginApi, type PluginInfo } from '@/api/plugin'

import AppCard from './components/AppCard.vue'

const list = ref<PluginInfo[]>([])
const loading = ref(false)
const query = ref('')

const groups = [
    { key: 'core', label: '核心模块', desc: '商城运营基础能力' },
    { key: 'channel_market', label: '渠道与市场', desc: '' },
    { key: 'value_added', label: '增值服务', desc: '' },
]

const total = computed(() => list.value.length)

const filtered = (cat: string) =>
    list.value.filter(
        (a) =>
            a.category === cat &&
            (!query.value ||
                a.name.includes(query.value) ||
                (a.description || '').includes(query.value))
    )

const loadList = async () => {
    loading.value = true
    try {
        const res = await pluginApi.list()
        list.value = res.data || []
    } catch (e: any) {
        ElMessage.error(e?.message || '加载失败')
    } finally {
        loading.value = false
    }
}

onMounted(loadList)
</script>

<style lang="scss" scoped>
.page-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
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
.ap-section {
    margin-bottom: 28px;
}
.ap-section-head {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
    .ap-section-bar {
        width: 3px;
        height: 16px;
        background: var(--el-color-primary);
    }
    .ap-section-title {
        font-weight: 600;
    }
    .ap-section-desc {
        font-size: 13px;
        color: var(--el-text-color-secondary);
    }
}
.ap-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 14px;
}
</style>
