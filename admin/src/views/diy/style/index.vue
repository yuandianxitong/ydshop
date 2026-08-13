<script setup lang="ts" name="DiyStyle">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getConfigsByGroup, batchUpdateConfigs } from '@/api/system/config'

const DEFAULT_COLORS: Record<string, string> = {
  theme_primary_color: '#2979ff',
  theme_auxiliary_color: '#ff9900',
  theme_text_color: '#333333',
  theme_bg_color: '#f5f5f5',
}

const colorItems = [
  { key: 'theme_primary_color', label: '主题颜色', desc: '按钮、链接、Tab 高亮等主色调' },
  { key: 'theme_auxiliary_color', label: '辅助颜色', desc: '价格、促销标签等辅助色' },
  { key: 'theme_text_color', label: '文字颜色', desc: '主文字颜色' },
  { key: 'theme_bg_color', label: '背景颜色', desc: '页面底色' },
]

const presets = [
  { name: '默认蓝',   primary: '#2979ff', aux: '#ff9900' },
  { name: '清新绿',   primary: '#16a34a', aux: '#f59e0b' },
  { name: '中国红',   primary: '#dc2626', aux: '#fbbf24' },
  { name: '商务紫',   primary: '#7c3aed', aux: '#ec4899' },
  { name: '雅黑橙',   primary: '#1f2937', aux: '#f97316' },
]

const loading = ref(false)
const saving = ref(false)
const colors = reactive<Record<string, string>>({ ...DEFAULT_COLORS })

async function loadConfigs() {
  loading.value = true
  try {
    const list = await getConfigsByGroup('shop_style')
    if (Array.isArray(list.data)) {
      for (const item of list.data) {
        if (item.config_key in colors) {
          colors[item.config_key] = item.config_value || DEFAULT_COLORS[item.config_key]
        }
      }
    }
  } finally {
    loading.value = false
  }
}

async function handleSave() {
  saving.value = true
  try {
    const configs = Object.keys(colors).map((key) => ({
      config_key: key,
      config_value: colors[key],
    }))
    await batchUpdateConfigs(configs)
    ElMessage.success('保存成功')
  } finally {
    saving.value = false
  }
}

async function handleReset() {
  try {
    await ElMessageBox.confirm('确定将所有配色恢复为系统默认?恢复后立即生效。', '重置默认', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
  } catch {
    return
  }
  Object.assign(colors, DEFAULT_COLORS)
  saving.value = true
  try {
    const configs = Object.keys(colors).map((key) => ({
      config_key: key,
      config_value: colors[key],
    }))
    await batchUpdateConfigs(configs)
    ElMessage.success('已恢复默认配色')
  } finally {
    saving.value = false
  }
}

function applyPreset(p: { primary: string; aux: string }) {
  colors.theme_primary_color = p.primary
  colors.theme_auxiliary_color = p.aux
}

onMounted(loadConfigs)
</script>

<template>
  <div class="diy-style-container" v-loading="loading">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">商城风格</h2>
        <p class="page-desc">移动端商城主题色板，调整后所有页面实时生效</p>
      </div>
      <div class="page-actions">
        <el-button @click="handleReset">重置默认</el-button>
        <el-button type="primary" :loading="saving" @click="handleSave">保存设置</el-button>
      </div>
    </div>

    <!-- KPI -->
    <div class="row-14">
      <div class="kpi-mini">
        <div class="lb">主色调</div>
        <div class="nm flex items-center gap-2" style="font-size:18px">
          <span class="color-dot" :style="{ background: colors.theme_primary_color }" />
          <span class="num">{{ colors.theme_primary_color.toUpperCase() }}</span>
        </div>
        <div class="tr"><span style="color:var(--ink-500)">primary</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">辅助色</div>
        <div class="nm flex items-center gap-2" style="font-size:18px">
          <span class="color-dot" :style="{ background: colors.theme_auxiliary_color }" />
          <span class="num">{{ colors.theme_auxiliary_color.toUpperCase() }}</span>
        </div>
        <div class="tr"><span style="color:var(--ink-500)">auxiliary</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">文字色</div>
        <div class="nm flex items-center gap-2" style="font-size:18px">
          <span class="color-dot" :style="{ background: colors.theme_text_color }" />
          <span class="num">{{ colors.theme_text_color.toUpperCase() }}</span>
        </div>
        <div class="tr"><span style="color:var(--ink-500)">text</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">背景色</div>
        <div class="nm flex items-center gap-2" style="font-size:18px">
          <span class="color-dot" :style="{ background: colors.theme_bg_color, border: '1px solid var(--ink-100)' }" />
          <span class="num">{{ colors.theme_bg_color.toUpperCase() }}</span>
        </div>
        <div class="tr"><span style="color:var(--ink-500)">background</span></div>
      </div>
    </div>

    <!-- 双栏：配置 / 预览 -->
    <div class="row-12">
      <!-- 左：配置 -->
      <div class="cfg-col">
        <el-card class="cfg-card" shadow="never">
          <div class="card-head">
            <div class="card-title">色板配置</div>
          </div>
          <div class="card-body">
            <div v-for="item in colorItems" :key="item.key" class="color-item">
              <div class="ci-info">
                <div class="ci-label">{{ item.label }}</div>
                <div class="ci-desc">{{ item.desc }}</div>
              </div>
              <div class="ci-control">
                <el-color-picker v-model="colors[item.key]" />
                <el-input v-model="colors[item.key]" style="width: 130px; margin-left: 8px" />
              </div>
            </div>
          </div>
        </el-card>

        <el-card class="cfg-card" shadow="never">
          <div class="card-head">
            <div class="card-title">预设方案</div>
            <div class="card-meta">点击一键应用</div>
          </div>
          <div class="card-body">
            <div class="preset-grid">
              <div
                v-for="p in presets"
                :key="p.name"
                class="preset-card"
                @click="applyPreset(p)"
              >
                <div class="pc-swatch">
                  <span :style="{ background: p.primary }" />
                  <span :style="{ background: p.aux }" />
                </div>
                <div class="pc-name">{{ p.name }}</div>
              </div>
            </div>
          </div>
        </el-card>
      </div>

      <!-- 右：预览 -->
      <el-card class="cfg-card" shadow="never">
        <div class="card-head">
          <div class="card-title">效果预览</div>
          <div class="card-meta">实时反映色板</div>
        </div>
        <div class="card-body preview-body">
          <div class="preview-phone" :style="{ background: colors.theme_bg_color }">
            <div class="ph-nav" :style="{ background: colors.theme_primary_color }">
              <span>商城首页</span>
            </div>
            <div class="ph-body">
              <div class="ph-card">
                <div class="ph-img" />
                <div class="ph-title" :style="{ color: colors.theme_text_color }">精选商品标题示例</div>
                <div class="ph-meta">
                  <span class="ph-price" :style="{ color: colors.theme_auxiliary_color }">¥99.00</span>
                  <button class="ph-btn" :style="{ background: colors.theme_primary_color }">立即购买</button>
                </div>
              </div>

              <div class="ph-tabs">
                <span class="ph-tab on" :style="{ color: colors.theme_primary_color, borderBottomColor: colors.theme_primary_color }">推荐</span>
                <span class="ph-tab" :style="{ color: colors.theme_text_color }">新品</span>
                <span class="ph-tab" :style="{ color: colors.theme_text_color }">热销</span>
              </div>

              <div class="ph-tag-row">
                <span class="ph-tag" :style="{ background: colors.theme_auxiliary_color }">限时特惠</span>
                <span class="ph-chip" :style="{ borderColor: colors.theme_primary_color, color: colors.theme_primary_color }">满减</span>
              </div>
            </div>
          </div>
        </div>
      </el-card>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.diy-style-container { padding: 0; }

:deep(.el-card__body) { padding: 0; }

.cfg-col {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 18px;
  border-bottom: 1px solid var(--ink-100);
}

.card-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-900);
  display: flex;
  align-items: center;
  gap: 8px;

  &::before {
    content: '';
    width: 3px;
    height: 14px;
    border-radius: 2px;
    background: var(--brand-500);
  }
}

.card-meta { font-size: 12px; color: var(--ink-400); }
.card-body { padding: 16px 18px 18px; }

// ── 色板项 ──
.color-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 0;
  border-bottom: 1px solid var(--ink-100);

  &:last-child { border-bottom: 0; }
}

.ci-label { font-size: 13.5px; font-weight: 600; color: var(--ink-900); margin-bottom: 4px; }
.ci-desc  { font-size: 12px; color: var(--ink-500); }

.ci-control {
  display: flex;
  align-items: center;
}

.color-dot {
  display: inline-block;
  width: 14px;
  height: 14px;
  border-radius: 4px;
  flex-shrink: 0;
}

// ── 预设方案 ──
.preset-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 10px;
}

.preset-card {
  border: 1px solid var(--ink-100);
  border-radius: var(--r-md);
  padding: 10px;
  cursor: pointer;
  text-align: center;
  transition: all .15s;

  &:hover {
    border-color: var(--brand-500);
    box-shadow: 0 4px 12px rgba(15, 23, 42, .06);
  }
}

.pc-swatch {
  display: flex;
  height: 28px;
  border-radius: var(--r-sm);
  overflow: hidden;
  margin-bottom: 8px;

  span { flex: 1; }
}

.pc-name { font-size: 12px; color: var(--ink-700); font-weight: 500; }

// ── 预览手机 ──
.preview-body {
  display: flex;
  justify-content: center;
  padding: 28px 0;
}

.preview-phone {
  width: 260px;
  height: 480px;
  border-radius: 20px;
  overflow: hidden;
  border: 8px solid #1f2937;
  box-shadow: 0 12px 32px rgba(15, 23, 42, .12);
  transition: background .15s;
}

.ph-nav {
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 13px;
  font-weight: 500;
  transition: background .15s;
}

.ph-body { padding: 12px; }

.ph-card {
  background: #fff;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 14px;
}

.ph-img {
  height: 90px;
  background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
  border-radius: 6px;
  margin-bottom: 10px;
}

.ph-title {
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 8px;
  transition: color .15s;
}

.ph-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.ph-price {
  font-size: 16px;
  font-weight: 700;
  font-family: var(--font-num);
  transition: color .15s;
}

.ph-btn {
  height: 28px;
  padding: 0 12px;
  border: 0;
  border-radius: 14px;
  color: #fff;
  font-size: 12px;
  cursor: pointer;
  transition: background .15s;
}

.ph-tabs {
  display: flex;
  gap: 16px;
  background: #fff;
  border-radius: 8px;
  padding: 8px 12px;
  margin-bottom: 12px;
}

.ph-tab {
  font-size: 12.5px;
  padding-bottom: 4px;
  border-bottom: 2px solid transparent;
}

.ph-tab.on { font-weight: 600; }

.ph-tag-row {
  display: flex;
  gap: 6px;
}

.ph-tag {
  display: inline-block;
  font-size: 11px;
  padding: 3px 10px;
  border-radius: 10px;
  color: #fff;
  transition: background .15s;
}

.ph-chip {
  display: inline-block;
  font-size: 11px;
  padding: 2px 10px;
  border-radius: 10px;
  background: #fff;
  border: 1px solid;
  transition: all .15s;
}

@media (max-width: 1280px) {
  .preset-grid { grid-template-columns: repeat(3, 1fr); }
}
</style>
