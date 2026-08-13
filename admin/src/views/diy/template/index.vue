<script setup lang="ts" name="DiyTemplate">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted, reactive, ref, watch } from 'vue'

import { diyTemplateApi as themeApi, type DiyTemplateInfo as DiyThemeInfo } from '@/api/diyTemplate'
import { batchUpdateConfigs, getConfigsByGroup } from '@/api/system/config'
import { useAppStore } from '@/store/modules/app.store'

import CreateTemplateDialog from './CreateTemplateDialog.vue'

const appStore = useAppStore()

interface ThemePreview extends DiyThemeInfo {
  c1?: string
  c2?: string
  accent?: string
}

const searchForm = reactive({
  platform: 'uniapp' as 'uniapp' | 'pc',
  page_type: '' as '' | 'home' | 'custom',
})
const loading = ref(false)
const themeList = ref<ThemePreview[]>([])
const activeId = ref<number | null>(null)

const fmtCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')

const stats = computed(() => {
  let system = 0, custom = 0
  for (const t of themeList.value) {
    if (t.is_system) system += 1
    else custom += 1
  }
  return { system, custom }
})

// 主题色板（hash-based — 后端无色板字段时占位生成）
const palettes = [
  { c1: '#0f172a', c2: '#f8fafc', accent: '#4f6bff' },
  { c1: '#831843', c2: '#fdf2f8', accent: '#f43f5e' },
  { c1: '#365314', c2: '#f7fee7', accent: '#65a30d' },
  { c1: '#1e3a8a', c2: '#eff6ff', accent: '#0ea5e9' },
  { c1: '#7f1d1d', c2: '#fef2f2', accent: '#ef4444' },
  { c1: '#020617', c2: '#1e293b', accent: '#a855f7' },
]
const paletteOf = (t: DiyThemeInfo) => {
  let h = 0
  for (const ch of String(t.id ?? t.name ?? '')) h = (h * 31 + ch.charCodeAt(0)) | 0
  return palettes[Math.abs(h) % palettes.length]
}

const fetchList = async () => {
  try {
    loading.value = true
    const res = await themeApi.getList({
      platform: searchForm.platform,
      page_type: searchForm.page_type || undefined,
      limit: 50,
    })
    themeList.value = (res.data.list || []).map((t: DiyThemeInfo) => ({ ...t, ...paletteOf(t) }))
  } finally {
    loading.value = false
  }
}

const themeConfigKey = computed(() => `current_theme_id_${searchForm.platform}`)
const createPageType = computed<'home' | 'custom'>(() => searchForm.page_type || 'home')

const fetchActiveId = async () => {
  // 捕获当前 key，请求返回时若 platform 已切换则丢弃结果，避免竞态覆盖
  const key = themeConfigKey.value
  try {
    const res = await getConfigsByGroup('diy')
    if (key !== themeConfigKey.value) return
    const item = (res.data || []).find((c: any) => c.config_key === key)
    activeId.value = item ? Number(item.config_value) || null : null
  } catch {
    // 后端尚未保存当前主题
  }
}

const handleApply = async (t: ThemePreview) => {
  try {
    await batchUpdateConfigs([{ config_key: themeConfigKey.value, config_value: t.id }])
    activeId.value = t.id
    ElMessage.success(`已启用模板「${t.name}」`)
  } catch {
    ElMessage.error('启用失败')
  }
}

const showCreateDialog = ref(false)
const openCreateDialog = () => { showCreateDialog.value = true }
const onCreated = () => {
  showCreateDialog.value = false
  fetchList()
}

const handleDelete = async (theme: DiyThemeInfo) => {
  try {
    await ElMessageBox.confirm(`确定删除模板「${theme.name}」？删除后不可恢复！`, '删除确认', {
      confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning',
    })
    await themeApi.delete(theme.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (e) {
    if (e !== 'cancel') console.error('删除失败:', e)
  }
}

watch(() => searchForm.platform, async () => {
  await fetchActiveId()
})

onMounted(async () => {
  await fetchActiveId()
  fetchList()
})
</script>

<template>
  <div class="diy-template-container" v-loading="loading">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">模板中心</h2>
        <p class="page-desc">店铺模板与节日皮肤，可定时切换</p>
      </div>
      <div class="page-actions">
        <el-button type="primary" v-has-perm="['diy.template.create']" @click="openCreateDialog">
          <i class="i-lucide:plus mr-1" /> 新建模板
        </el-button>
      </div>
    </div>

    <!-- KPI -->
    <div class="row-14">
      <div class="kpi-mini">
        <div class="lb">模板总数</div>
        <div class="nm num">{{ fmtCount(themeList.length) }}</div>
        <div class="tr"><span style="color:var(--ink-500)">本端统计</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">系统模板</div>
        <div class="nm num">{{ fmtCount(stats.system) }}</div>
        <div class="tr"><span style="color:var(--brand-500)">官方提供</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">自定义</div>
        <div class="nm num">{{ fmtCount(stats.custom) }}</div>
        <div class="tr"><span style="color:var(--success)">可删除</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">当前生效</div>
        <div class="nm num" style="font-size:18px">
          {{ themeList.find(t => t.id === activeId)?.name || '—' }}
        </div>
        <div class="tr"><span style="color:var(--ink-500)">本端</span></div>
      </div>
    </div>

    <!-- 平台/页面类型切换 -->
    <div class="filter-bar">
      <span class="filter-label">平台：</span>
      <el-select v-model="searchForm.platform" style="width: 120px" @change="fetchList">
        <el-option label="移动端" value="uniapp" />
        <el-option label="PC 端" value="pc" />
      </el-select>
      <span class="filter-label" style="margin-left: 12px">页面类型：</span>
      <el-select v-model="searchForm.page_type" style="width: 120px" @change="fetchList">
        <el-option label="全部" value="" />
        <el-option label="首页" value="home" />
        <el-option label="专题" value="custom" />
      </el-select>
    </div>

    <!-- 主题卡片 -->
    <div v-if="themeList.length" class="row-cards-4 theme-grid">
      <div
        v-for="t in themeList"
        :key="t.id"
        class="theme-card"
        :class="{ active: activeId === t.id }"
      >
        <!-- 预览区 -->
        <div class="th-preview" :style="{ background: t.c2 }">
          <img
            :src="appStore.getImageUrl(t.cover || '/storage/diy-defaults/template-cover.png')"
            :alt="t.name"
            class="theme-cover-img"
          />
          <div class="th-preview-head">
            <div class="th-brand" :style="{ color: t.c1 }">素白</div>
            <div class="th-dots">
              <i v-for="i in 3" :key="i" :style="{ background: t.c1, opacity: .4 }" />
            </div>
          </div>
          <div class="th-preview-sub" :style="{ color: t.c1, opacity: .65 }">清欢茶 · 春日上新</div>
          <div class="th-preview-title" :style="{ color: t.c1 }">素白纯净</div>
          <div class="th-preview-actions">
            <span class="th-cta" :style="{ background: t.accent }">立即抢购</span>
            <span class="th-link" :style="{ borderColor: t.c1, color: t.c1 }">了解更多</span>
          </div>
          <div v-if="activeId === t.id" class="th-active-badge">当前启用</div>
        </div>

        <!-- 信息区 -->
        <div class="th-body">
          <div class="th-head">
            <div>
              <div class="th-name">{{ t.name }}</div>
              <div class="th-id num">TH-{{ String(t.id).padStart(2, '0') }}</div>
            </div>
            <span :class="['tag', activeId === t.id ? 'tag-green' : (t.is_system ? 'tag-amber' : 'tag-blue')]">
              {{ activeId === t.id ? '当前启用' : (t.is_system ? '系统' : '自定义') }}
            </span>
          </div>

          <div class="th-swatches">
            <div class="th-swatch-row">
              <i :style="{ background: t.c1 }" />
              <i :style="{ background: t.c2 }" />
              <i :style="{ background: t.accent }" />
            </div>
            <span class="th-hex num">
              {{ (t.c1 || '').toUpperCase() }} / {{ (t.c2 || '').toUpperCase() }} / {{ (t.accent || '').toUpperCase() }}
            </span>
          </div>

          <div class="tbl-acts th-acts">
            <el-button
              size="small"
              :type="activeId === t.id ? 'default' : 'primary'"
              :disabled="activeId === t.id"
              @click="handleApply(t)"
            >{{ activeId === t.id ? '已启用' : '启用' }}</el-button>
            <el-button
              v-if="!t.is_system"
              text type="danger" size="small"
              v-has-perm="['diy.template.delete']"
              @click="handleDelete(t)"
            >删除</el-button>
          </div>
        </div>
      </div>
    </div>

    <el-empty v-else-if="!loading" description="暂无模板，点击右上角「新建模板」创建" />

    <CreateTemplateDialog
      v-if="showCreateDialog"
      :default-platform="searchForm.platform"
      :default-page-type="createPageType"
      @close="showCreateDialog = false"
      @created="onCreated"
    />
  </div>
</template>

<style lang="scss" scoped>
.diy-template-container { padding: 0; }

:deep(.el-card__body) { padding: 0; }

// ── 主题卡片 ──
.theme-card {
  background: #fff;
  border: 1px solid var(--ink-100);
  border-radius: var(--r-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  transition: all .15s ease;

  &.active {
    border: 1.5px solid var(--brand-500);
    box-shadow: 0 0 0 3px var(--brand-50), 0 6px 20px rgba(15, 23, 42, .08);
  }

  &:not(.active):hover {
    box-shadow: 0 6px 20px rgba(15, 23, 42, .08);
    transform: translateY(-2px);
  }
}

.row-cards-4 {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.th-preview {
  position: relative;
  height: 500px;
  padding: 18px;
  overflow: hidden;
}

.theme-cover-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  z-index: 0;
}

.th-preview-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.th-brand {
  font-size: 16px;
  font-weight: 600;
  letter-spacing: -.02em;
}

.th-dots {
  display: flex;
  gap: 6px;

  i { width: 8px; height: 8px; border-radius: 4px; display: inline-block; }
}

.th-preview-sub {
  margin-top: 16px;
  font-size: 11px;
}

.th-preview-title {
  margin-top: 6px;
  font-size: 24px;
  font-weight: 700;
  letter-spacing: -.02em;
}

.th-preview-actions {
  margin-top: 14px;
  display: flex;
  gap: 8px;
  align-items: center;
}

.th-cta {
  padding: 6px 12px;
  color: #fff;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}

.th-link {
  padding: 5px 11px;
  border: 1px solid;
  border-radius: 4px;
  font-size: 11px;
  opacity: .75;
}

.th-active-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 2px 10px;
  background: var(--brand-500);
  color: #fff;
  font-size: 11px;
  border-radius: 10px;
  font-weight: 500;
}

// ── 卡片信息区 ──
.th-body {
  padding: 14px 18px 16px;
}

.th-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.th-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-900);
}

.th-id {
  font-size: 11px;
  color: var(--ink-400);
  margin-top: 2px;
}

.th-swatches {
  margin-top: 12px;
  display: flex;
  gap: 8px;
  align-items: center;
}

.th-swatch-row {
  display: flex;
  gap: 4px;

  i {
    width: 18px;
    height: 18px;
    border-radius: 4px;
    border: 1px solid var(--ink-100);
    display: inline-block;
  }
}

.th-hex {
  font-size: 11px;
  color: var(--ink-500);
}

.th-acts {
  margin-top: 12px;
  justify-content: flex-end;
  display: flex;
  gap: 4px;
}

.filter-label { font-size: 12px; color: var(--ink-500); margin-left: 4px; }
</style>
