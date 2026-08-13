<script setup lang="ts" name="DiyTabbar">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { useUserStore } from '@/store/modules/user.store'
import { getConfigsByGroup, batchUpdateConfigs } from '@/api/system/config'
import LinkPicker from '../editor/components/LinkPicker.vue'

interface TabBarItem {
  name: string
  path: string
  icon: string
  activeIcon: string
}

const userStore = useUserStore()
const uploadHeaders = ref({ Authorization: `Bearer ${userStore.token}` })
const loading = ref(false)
const saving = ref(false)
const items = ref<TabBarItem[]>([])
const dragIdx = ref<number | null>(null)

const colors = reactive({
  text: '#94a3b8',
  active: '#4f6bff',
  bg: '#ffffff',
})

const swatchPalette = ['#4f6bff', '#10b981', '#f43f5e', '#f59e0b', '#8b5cf6', '#0f172a', '#94a3b8', '#ffffff']

const defaultItems: TabBarItem[] = [
  { name: '首页', path: '/pages/index/index', icon: '', activeIcon: '' },
  { name: '分类', path: '/pages/category/index', icon: '', activeIcon: '' },
  { name: '购物车', path: '/pages/cart/index', icon: '', activeIcon: '' },
  { name: '我的', path: '/pages/user/index', icon: '', activeIcon: '' },
]

// json 类型配置后端会对 config_value 再做一次 json_encode；
// 早期版本前端误把 stringify 过的字符串再发过来导致双层编码，这里兼容自愈一次
function parseJsonConfig(raw: string): any {
  let v = JSON.parse(raw)
  if (typeof v === 'string') v = JSON.parse(v)
  return v
}

async function loadConfig() {
  loading.value = true
  try {
    const res = await getConfigsByGroup('diy')
    const configs = res.data || []
    const found = configs.find((c: any) => c.config_key === 'tabbar_config')
    if (found && found.config_value) {
      try {
        const parsed = parseJsonConfig(found.config_value)
        items.value = Array.isArray(parsed) ? parsed : [...defaultItems]
      } catch { items.value = [...defaultItems] }
    } else {
      items.value = [...defaultItems]
    }

    const c = configs.find((c: any) => c.config_key === 'tabbar_colors')
    if (c?.config_value) {
      try {
        const parsed = parseJsonConfig(c.config_value)
        if (parsed && typeof parsed === 'object') Object.assign(colors, parsed)
      } catch { /* ignore */ }
    }
  } catch {
    items.value = [...defaultItems]
  } finally {
    loading.value = false
  }
}

async function handleSave() {
  saving.value = true
  try {
    // config_type=json 时后端会自行 json_encode，前端直接传原始对象
    await batchUpdateConfigs([
      { config_key: 'tabbar_config', config_value: items.value },
      { config_key: 'tabbar_colors', config_value: { ...colors } },
    ])
    ElMessage.success('保存成功')
  } finally {
    saving.value = false
  }
}

function addItem() {
  if (items.value.length >= 5) {
    ElMessage.warning('最多 5 个导航项')
    return
  }
  items.value.push({ name: '', path: '', icon: '', activeIcon: '' })
}

function removeItem(i: number) {
  items.value.splice(i, 1)
}

function onUpload(res: any, i: number, field: 'icon' | 'activeIcon') {
  if (res.code === 200) items.value[i][field] = res.data.url
}

function onDragStart(i: number, e: DragEvent) {
  dragIdx.value = i
  e.dataTransfer!.effectAllowed = 'move'
}
function onDrop(i: number) {
  if (dragIdx.value === null || dragIdx.value === i) return
  const [item] = items.value.splice(dragIdx.value, 1)
  items.value.splice(i, 0, item)
  dragIdx.value = null
}

function resetColors() {
  colors.text = '#94a3b8'
  colors.active = '#4f6bff'
  colors.bg = '#ffffff'
}

onMounted(loadConfig)
</script>

<template>
  <div class="diy-tabbar-container" v-loading="loading">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">底部导航 (TabBar)</h2>
        <p class="page-desc">多端底部导航统一配置，支持小程序 / H5 / APP</p>
      </div>
      <div class="page-actions">
        <el-button type="primary" :loading="saving" @click="handleSave">应用配置</el-button>
      </div>
    </div>

    <!-- row-83: 左配置 + 右预览 -->
    <div class="row-83">
      <!-- 左：导航项配置 -->
      <el-card class="tb-card" shadow="never">
        <div class="card-head">
          <div class="card-title">导航项配置</div>
          <div class="card-meta">最多 5 个</div>
        </div>
        <div class="card-body">
          <div
            v-for="(item, i) in items"
            :key="i"
            class="ti-row"
            draggable="true"
            @dragstart="onDragStart(i, $event)"
            @dragover.prevent
            @drop="onDrop(i)"
            @dragend="dragIdx = null"
          >
            <div class="ti-drag">⋮⋮</div>
            <div class="ti-content">
              <div class="ti-uploads">
                <div class="ti-upload-box">
                  <el-upload
                    class="ti-upload"
                    :action="'/adminapi/upload/image'"
                    :headers="uploadHeaders"
                    :show-file-list="false"
                    :on-success="(res: any) => onUpload(res, i, 'icon')"
                  >
                    <img v-if="item.icon" :src="item.icon" />
                    <div v-else class="ti-upload-empty">
                      <span class="ti-plus">+</span>
                    </div>
                  </el-upload>
                  <div class="ti-upload-label">未选中图标</div>
                  <div class="ti-upload-hint">PNG · 81×81</div>
                </div>
                <div class="ti-upload-box">
                  <el-upload
                    class="ti-upload"
                    :action="'/adminapi/upload/image'"
                    :headers="uploadHeaders"
                    :show-file-list="false"
                    :on-success="(res: any) => onUpload(res, i, 'activeIcon')"
                  >
                    <img v-if="item.activeIcon" :src="item.activeIcon" />
                    <div v-else class="ti-upload-empty">
                      <span class="ti-plus">+</span>
                    </div>
                  </el-upload>
                  <div class="ti-upload-label">选中图标</div>
                  <div class="ti-upload-hint">PNG · 81×81</div>
                </div>

                <div class="ti-fields">
                  <div class="ti-field">
                    <span class="ti-field-label">名称</span>
                    <el-input v-model="item.name" placeholder="导航名称（≤4 字）" maxlength="4" />
                  </div>
                  <div class="ti-field">
                    <span class="ti-field-label">链接</span>
                    <LinkPicker v-model="item.path" />
                  </div>
                </div>
              </div>
            </div>
            <button class="ti-del" @click="removeItem(i)" title="删除">×</button>
          </div>

          <div class="ti-add-row">
            <el-button :disabled="items.length >= 5" @click="addItem">
              + 添加导航项（{{ items.length }}/5）
            </el-button>
          </div>
        </div>
      </el-card>

      <!-- 右：实时预览 -->
      <el-card class="tb-card" shadow="never">
        <div class="card-head">
          <div class="card-title">实时预览</div>
          <div class="card-meta">小程序</div>
        </div>
        <div class="card-body preview-body">
          <div class="phone">
            <div class="phone-status">
              <span>9:41</span>
              <span>•••</span>
            </div>
            <div class="phone-screen">
              <div class="ps-brand">素白</div>
              <div class="ps-sub">清欢茶 · 春日上新</div>
              <div class="ps-grid">
                <div v-for="(c, i) in ['#fee2e2','#dbeafe','#dcfce7','#fef3c7']" :key="i" :style="{ background: c }" />
              </div>
            </div>
            <div class="phone-tabbar" :style="{ background: colors.bg }">
              <div
                v-for="(t, i) in items"
                :key="i"
                class="pt-item"
                :style="{ color: i === 0 ? colors.active : colors.text }"
              >
                <img v-if="i === 0 && t.activeIcon" :src="t.activeIcon" class="pt-icon" />
                <img v-else-if="t.icon" :src="t.icon" class="pt-icon" />
                <i v-else class="i-svg:layout-grid pt-icon-fb" />
                <span :style="{ fontWeight: i === 0 ? 600 : 400 }">{{ t.name || '未命名' }}</span>
              </div>
            </div>
          </div>
        </div>
      </el-card>
    </div>

    <!-- 配色 / 选中色 -->
    <el-card class="tb-card" shadow="never">
      <div class="card-head">
        <div class="card-title">配色 / 选中色</div>
        <div class="card-meta">配置导航文字与背景颜色</div>
      </div>
      <div class="card-body">
        <div class="color-grid">
          <!-- 未选中 -->
          <div class="color-col">
            <div class="cc-title">未选中文字颜色</div>
            <div class="cc-input">
              <span class="cc-dot" :style="{ background: colors.text }" />
              <el-input v-model="colors.text" style="width:120px" />
              <div class="cc-palette">
                <button
                  v-for="p in swatchPalette"
                  :key="p"
                  class="cc-pal-btn"
                  :class="{ on: colors.text.toLowerCase() === p }"
                  :style="{ background: p, border: p === '#ffffff' ? '1px solid var(--ink-200)' : 'none' }"
                  @click="colors.text = p"
                />
              </div>
            </div>
            <div class="cc-preview">
              <span :style="{ color: colors.text, fontSize: '9px' }">○</span>
              <span :style="{ color: colors.text }">未选中</span>
            </div>
          </div>

          <!-- 选中 -->
          <div class="color-col">
            <div class="cc-title">选中文字颜色</div>
            <div class="cc-input">
              <span class="cc-dot" :style="{ background: colors.active }" />
              <el-input v-model="colors.active" style="width:120px" />
              <div class="cc-palette">
                <button
                  v-for="p in swatchPalette"
                  :key="p"
                  class="cc-pal-btn"
                  :class="{ on: colors.active.toLowerCase() === p }"
                  :style="{ background: p, border: p === '#ffffff' ? '1px solid var(--ink-200)' : 'none' }"
                  @click="colors.active = p"
                />
              </div>
            </div>
            <div class="cc-preview">
              <span :style="{ color: colors.active, fontSize: '9px' }">●</span>
              <span :style="{ color: colors.active, fontWeight: 600 }">已选中</span>
            </div>
          </div>

          <!-- 背景 -->
          <div class="color-col">
            <div class="cc-title">背景颜色</div>
            <div class="cc-input">
              <span class="cc-dot" :style="{ background: colors.bg, border: '1px solid var(--ink-200)' }" />
              <el-input v-model="colors.bg" style="width:120px" />
              <div class="cc-palette">
                <button
                  v-for="p in swatchPalette"
                  :key="p"
                  class="cc-pal-btn"
                  :class="{ on: colors.bg.toLowerCase() === p }"
                  :style="{ background: p, border: p === '#ffffff' ? '1px solid var(--ink-200)' : 'none' }"
                  @click="colors.bg = p"
                />
              </div>
            </div>
            <div class="cc-preview" :style="{ background: colors.bg, border: '1px solid var(--ink-100)' }">
              <span style="color: var(--ink-500)">TabBar 背景</span>
            </div>
          </div>
        </div>

        <div class="color-foot">
          <div class="cf-tip">建议：未选中色与背景色保持足够对比度（4.5:1 以上）</div>
          <div class="cf-actions">
            <el-button size="small" @click="resetColors">恢复默认</el-button>
            <el-button size="small" type="primary" :loading="saving" @click="handleSave">保存配色</el-button>
          </div>
        </div>
      </div>
    </el-card>
  </div>
</template>

<style lang="scss" scoped>
.diy-tabbar-container { padding: 0; }

:deep(.el-card__body) { padding: 0; }

.tb-card { margin-bottom: 14px; }

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
.card-body { padding: 14px 18px; }

// ── 导航项 ──
.ti-row {
  display: grid;
  grid-template-columns: 18px 1fr 32px;
  gap: 12px;
  align-items: flex-start;
  padding: 14px 0;
  border-bottom: 1px solid var(--ink-100);

  &:last-of-type { border-bottom: 0; }
}

.ti-drag {
  cursor: grab;
  color: var(--ink-300);
  font-size: 14px;
  padding-top: 24px;
  user-select: none;

  &:active { cursor: grabbing; }
}

.ti-uploads {
  display: flex;
  gap: 18px;
  align-items: flex-start;
}

.ti-upload-box { text-align: center; }

.ti-upload {
  width: 70px;
  height: 70px;
  border-radius: 6px;
  border: 1px dashed var(--el-border-color);
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  overflow: hidden;
  transition: border-color .2s;

  &:hover {
    border-color: var(--el-color-primary);
  }

  img { width: 100%; height: 100%; object-fit: contain; }
}

.ti-upload-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #8c939d;
}

.ti-plus { font-size: 28px; line-height: 1; font-weight: 300; }
.ti-up-text { display: none; }

.ti-upload-label { font-size: 11.5px; color: var(--ink-500); margin-top: 6px; }
.ti-upload-hint  { font-size: 11px; color: var(--ink-300); margin-top: 2px; }

.ti-fields {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding-top: 2px;
  min-width: 0;
}

.ti-field {
  display: flex;
  align-items: center;
  gap: 8px;
}

.ti-field-label {
  font-size: 11.5px;
  color: var(--ink-500);
  min-width: 48px;
  flex-shrink: 0;
}

.ti-del {
  width: 28px;
  height: 28px;
  border: 0;
  background: transparent;
  color: var(--ink-400);
  cursor: pointer;
  font-size: 18px;
  border-radius: 4px;
  margin-top: 18px;

  &:hover { color: var(--rose-500); background: var(--rose-50); }
}

.ti-add-row { margin-top: 14px; }

// ── 预览手机 ──
.preview-body {
  display: flex;
  justify-content: center;
  padding: 18px;
}

.phone {
  width: 260px;
  height: 480px;
  background: #f8fafc;
  border: 8px solid #1f2937;
  border-radius: 20px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 12px 32px rgba(0, 0, 0, .18);
}

.phone-status {
  height: 24px;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 14px;
  font-size: 10px;
  font-family: var(--font-num);
  color: #1f2937;
}

.phone-screen {
  height: calc(100% - 24px - 56px);
  padding: 14px;
  background: linear-gradient(180deg, #fff, #f8fafc);
}

.ps-brand {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.ps-sub {
  font-size: 10px;
  color: #64748b;
  margin-top: 2px;
}

.ps-grid {
  margin-top: 14px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;

  div {
    height: 80px;
    border-radius: 6px;
  }
}

.phone-tabbar {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 56px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  transition: background .15s;
}

.pt-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  font-size: 9px;
  transition: color .15s;
}

.pt-icon {
  width: 18px;
  height: 18px;
  object-fit: contain;
}

.pt-icon-fb {
  font-size: 18px;
}

// ── 配色 ──
.color-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 24px;
  padding-bottom: 14px;
}

.cc-title {
  font-size: 12px;
  font-weight: 600;
  color: var(--ink-700);
  margin-bottom: 10px;
}

.cc-input {
  display: flex;
  align-items: center;
  gap: 10px;
}

.cc-dot {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  flex-shrink: 0;
}

.cc-palette {
  display: flex;
  gap: 4px;
}

.cc-pal-btn {
  width: 18px;
  height: 18px;
  border-radius: 4px;
  cursor: pointer;
  padding: 0;
  outline: none;

  &.on {
    outline: 2px solid var(--brand-500);
    outline-offset: 1px;
  }
}

.cc-preview {
  margin-top: 10px;
  padding: 10px 12px;
  background: var(--ink-50);
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 2px;
  font-size: 11px;
}

.color-foot {
  margin-top: 4px;
  padding-top: 14px;
  border-top: 1px dashed var(--ink-100);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.cf-tip { font-size: 11.5px; color: var(--ink-500); }
.cf-actions { display: flex; gap: 8px; }

@media (max-width: 1280px) {
  .color-grid { grid-template-columns: 1fr; }
}
</style>
