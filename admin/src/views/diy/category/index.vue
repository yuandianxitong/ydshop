<script setup lang="ts" name="DiyCategory">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getConfigsByGroup, batchUpdateConfigs } from '@/api/system/config'
import { useAppStore } from '@/store/modules/app.store'

type StyleKey = 'style1' | 'style2' | 'style3'

const CONFIG_KEY = 'category_page_style_uniapp'

const appStore = useAppStore()
const current = ref<StyleKey>('style1')
const loading = ref(false)
const saving = ref(false)

const styles = [
  { key: 'style1' as StyleKey, label: '左侧分类树 + 右侧商品流', image: '/storage/diy-defaults/category-style1.png' },
  { key: 'style2' as StyleKey, label: '顶部 tab + 瀑布流网格', image: '/storage/diy-defaults/category-style2.png' },
  { key: 'style3' as StyleKey, label: '三栏 (一级 / 二级 / 商品)', image: '/storage/diy-defaults/category-style3.png' },
]

async function loadConfig() {
  loading.value = true
  try {
    const res = await getConfigsByGroup('diy')
    const configs = res.data || []
    const found = configs.find((c: any) => c.config_key === CONFIG_KEY)
    const v = (found?.config_value as StyleKey) || 'style1'
    current.value = ['style1', 'style2', 'style3'].includes(v) ? v : 'style1'
  } catch {
    current.value = 'style1'
  } finally {
    loading.value = false
  }
}

async function handleSave() {
  saving.value = true
  try {
    await batchUpdateConfigs([
      { config_key: CONFIG_KEY, config_value: current.value },
    ])
    ElMessage.success('已保存,移动端分类页骨架已切换')
  } finally {
    saving.value = false
  }
}

onMounted(loadConfig)
</script>

<template>
  <div class="diy-category-container" v-loading="loading">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">分类配置</h2>
        <p class="page-desc">仅移动端 — 选择分类页骨架,保存后所有移动端用户立即生效 (PC 没有分类页概念)</p>
      </div>
      <div class="page-actions">
        <el-button type="primary" :loading="saving" @click="handleSave">保存设置</el-button>
      </div>
    </div>

    <!-- 3 骨架卡片 -->
    <div class="style-grid">
      <div
        v-for="(item, idx) in styles"
        :key="item.key"
        class="style-card"
        :class="{ on: current === item.key }"
        @click="current = item.key"
      >
        <div class="style-cover">
          <img :src="appStore.getImageUrl(item.image)" :alt="item.label" />
          <div v-if="current === item.key" class="badge">当前选择</div>
        </div>
        <div class="style-name">Style {{ idx + 1 }}</div>
        <div class="style-desc">{{ item.label }}</div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.diy-category-container { padding: 0; }

.page-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 18px;
}

.page-title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--ink-900);
}

.page-desc {
  margin: 4px 0 0;
  font-size: 12.5px;
  color: var(--ink-500);
}

.style-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.style-card {
  border: 2px solid var(--ink-100);
  border-radius: var(--r-lg, 12px);
  padding: 12px;
  cursor: pointer;
  background: #fff;
  transition: all .15s;

  &:hover { border-color: var(--brand-300, #93b4ff); }

  &.on {
    border-color: var(--brand-500, #4f6bff);
    box-shadow: 0 4px 16px rgba(79, 107, 255, .15);
  }
}

.style-cover {
  position: relative;
  height: 500px;
  background: var(--ink-50, #f8fafc);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }
}

.badge {
  position: absolute;
  top: 8px;
  right: 8px;
  padding: 2px 10px;
  background: var(--success, #10b981);
  color: #fff;
  border-radius: 999px;
  font-size: 12px;
}

.style-name {
  text-align: center;
  font-size: 14px;
  font-weight: 600;
  margin-top: 10px;
  color: var(--ink-900);
}

.style-desc {
  text-align: center;
  font-size: 12px;
  color: var(--ink-500);
  margin-top: 4px;
}

@media (max-width: 1024px) {
  .style-grid { grid-template-columns: 1fr; }
}
</style>
