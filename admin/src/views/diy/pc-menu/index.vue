<script setup lang="ts" name="DiyPcMenu">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getConfigsByGroup, batchUpdateConfigs } from '@/api/system/config'

interface HeaderItem {
  label: string
  path: string
}
interface FooterLink {
  label: string
  path: string
}
interface FooterColumn {
  title: string
  links: FooterLink[]
}
interface FooterConfig {
  columns: FooterColumn[]
  copyright: string
}

// ===== 默认值（必须与 pc/composables/usePcMenu.ts 和 server init.sql 一致）=====
const DEFAULT_HEADER: HeaderItem[] = [
  { label: '首页',     path: '/' },
  { label: '热销榜单', path: '/goods?sort=sales' },
  { label: '新品推荐', path: '/goods?sort=newest' },
  { label: '好物优选', path: '/goods?is_recommend=1' },
  { label: '限时秒杀', path: '/marketing/flash-sale' },
  { label: '领券中心', path: '/marketing/coupon' },
  { label: '商城资讯', path: '/article' },
  { label: '帮助中心', path: '/help' },
]
const DEFAULT_FOOTER: FooterConfig = {
  columns: [
    { title: '关于我们', links: [{ label: '关于元点', path: '/about' }, { label: '联系我们', path: '/contact' }] },
    { title: '帮助中心', links: [{ label: '用户协议', path: '/article/agreement' }, { label: '隐私政策', path: '/article/privacy' }] },
    { title: '友情链接', links: [{ label: '管理后台', path: '/admin/' }] },
    { title: '联系方式', links: [{ label: '邮箱：642508814@qq.com', path: '' }, { label: '微信：Vince_Dorian', path: '' }] },
  ],
  copyright: '© {YEAR} 元点Shop. All rights reserved. Powered by yd-admin',
}

// ===== state =====
const tab = ref<'header' | 'footer'>('header')
const loading = ref(false)
const saving = ref(false)
const headerItems = ref<HeaderItem[]>([])
const footer = reactive<FooterConfig>({ columns: [], copyright: '' })

// 拖拽
const headerDragIdx = ref<number | null>(null)
const linkDragRef = ref<{ col: number; idx: number } | null>(null)

// ===== parse 自愈 =====
function parseJsonConfig(raw: any): any {
  if (raw == null) return null
  if (typeof raw !== 'string') return raw
  try {
    let v = JSON.parse(raw)
    if (typeof v === 'string') v = JSON.parse(v)
    return v
  } catch {
    return null
  }
}

// ===== 加载 =====
async function loadConfig() {
  loading.value = true
  try {
    const res = await getConfigsByGroup('diy')
    const configs = res.data || []

    const h = configs.find((c: any) => c.config_key === 'pc_header_menu')
    const headerParsed = parseJsonConfig(h?.config_value)
    headerItems.value = Array.isArray(headerParsed) && headerParsed.length
      ? headerParsed
      : structuredClone(DEFAULT_HEADER)

    const f = configs.find((c: any) => c.config_key === 'pc_footer_config')
    const footerParsed = parseJsonConfig(f?.config_value)
    if (footerParsed && typeof footerParsed === 'object' && Array.isArray(footerParsed.columns)) {
      footer.columns = footerParsed.columns
      footer.copyright = footerParsed.copyright || DEFAULT_FOOTER.copyright
    } else {
      footer.columns = structuredClone(DEFAULT_FOOTER.columns)
      footer.copyright = DEFAULT_FOOTER.copyright
    }
  } catch {
    headerItems.value = structuredClone(DEFAULT_HEADER)
    footer.columns = structuredClone(DEFAULT_FOOTER.columns)
    footer.copyright = DEFAULT_FOOTER.copyright
  } finally {
    loading.value = false
  }
}

// ===== 校验 =====
function validate(): string | null {
  for (const [i, it] of headerItems.value.entries()) {
    if (!it.label?.trim()) return `头部菜单第 ${i + 1} 项名称为空`
    if (it.label.length > 20) return `头部菜单第 ${i + 1} 项名称超过 20 字`
  }
  if (footer.columns.length > 5) return '底部列数不能超过 5 列'
  for (const [ci, col] of footer.columns.entries()) {
    if (!col.title?.trim()) return `底部第 ${ci + 1} 列标题为空`
    if (col.links.length > 10) return `底部第 ${ci + 1} 列链接超过 10 条`
    for (const [li, l] of col.links.entries()) {
      if (!l.label?.trim()) return `底部第 ${ci + 1} 列第 ${li + 1} 条链接名称为空`
    }
  }
  return null
}

// ===== 保存 =====
async function handleSave() {
  const err = validate()
  if (err) {
    ElMessage.warning(err)
    return
  }
  saving.value = true
  try {
    // config_type=json 时后端会自行 json_encode，前端直接传原始对象
    await batchUpdateConfigs([
      { config_key: 'pc_header_menu', config_value: headerItems.value },
      { config_key: 'pc_footer_config', config_value: { columns: footer.columns, copyright: footer.copyright } },
    ])
    ElMessage.success('保存成功')
  } finally {
    saving.value = false
  }
}

// ===== 恢复默认 =====
async function restoreDefault() {
  try {
    await ElMessageBox.confirm('恢复默认会覆盖当前表单，确认？', '提示', { type: 'warning' })
  } catch {
    return
  }
  if (tab.value === 'header') {
    headerItems.value = structuredClone(DEFAULT_HEADER)
  } else {
    footer.columns = structuredClone(DEFAULT_FOOTER.columns)
    footer.copyright = DEFAULT_FOOTER.copyright
  }
}

// ===== 头部菜单操作 =====
function addHeaderItem() {
  if (headerItems.value.length >= 12) {
    ElMessage.warning('最多 12 个菜单项')
    return
  }
  headerItems.value.push({ label: '', path: '/' })
}
function removeHeaderItem(i: number) {
  headerItems.value.splice(i, 1)
}
function onHeaderDragStart(i: number, e: DragEvent) {
  headerDragIdx.value = i
  e.dataTransfer!.effectAllowed = 'move'
}
function onHeaderDrop(i: number) {
  if (headerDragIdx.value === null || headerDragIdx.value === i) return
  const [it] = headerItems.value.splice(headerDragIdx.value, 1)
  headerItems.value.splice(i, 0, it)
  headerDragIdx.value = null
}

// ===== 底部列操作 =====
function addColumn() {
  if (footer.columns.length >= 5) {
    ElMessage.warning('最多 5 列')
    return
  }
  footer.columns.push({ title: '新列', links: [{ label: '', path: '' }] })
}
function removeColumn(ci: number) {
  footer.columns.splice(ci, 1)
}
function moveColumn(ci: number, delta: 1 | -1) {
  const ni = ci + delta
  if (ni < 0 || ni >= footer.columns.length) return
  const [col] = footer.columns.splice(ci, 1)
  footer.columns.splice(ni, 0, col)
}

// ===== 底部链接操作 =====
function addLink(ci: number) {
  if (footer.columns[ci].links.length >= 10) {
    ElMessage.warning('每列最多 10 条')
    return
  }
  footer.columns[ci].links.push({ label: '', path: '' })
}
function removeLink(ci: number, li: number) {
  footer.columns[ci].links.splice(li, 1)
}
function onLinkDragStart(ci: number, li: number, e: DragEvent) {
  linkDragRef.value = { col: ci, idx: li }
  e.dataTransfer!.effectAllowed = 'move'
}
function onLinkDrop(ci: number, li: number) {
  const from = linkDragRef.value
  if (!from || from.col !== ci) {
    // 仅支持同列内拖拽
    linkDragRef.value = null
    return
  }
  if (from.idx === li) return
  const [l] = footer.columns[ci].links.splice(from.idx, 1)
  footer.columns[ci].links.splice(li, 0, l)
  linkDragRef.value = null
}

onMounted(loadConfig)
</script>

<template>
  <div class="pc-menu-container">
    <div class="page-head">
      <div>
        <div class="page-title">PC 头部 / 底部配置</div>
        <div class="page-desc">配置 PC 商城顶部导航菜单与底部链接</div>
      </div>
      <div class="page-actions">
        <el-button @click="restoreDefault">恢复默认</el-button>
        <el-button type="primary" :loading="saving" @click="handleSave">
          <i class="i-svg:save" />
          保存
        </el-button>
      </div>
    </div>

    <div class="tab-card">
      <el-tabs v-model="tab" v-loading="loading">
        <!-- ===== Tab 1: 头部菜单 ===== -->
        <el-tab-pane label="头部菜单" name="header">
          <div class="text-xs text-gray-400 mb-3">
            拖拽 ⠿ 调整顺序。path 支持站内（/...）/ 外链（https://...）/ 空（纯文本）。
          </div>

          <div class="flex flex-col gap-2">
            <div
              v-for="(it, i) in headerItems"
              :key="i"
              class="flex items-center gap-2 p-2 bg-gray-50 rounded border border-gray-100"
              draggable="true"
              @dragstart="onHeaderDragStart(i, $event)"
              @dragover.prevent
              @drop="onHeaderDrop(i)"
              @dragend="headerDragIdx = null"
            >
              <span class="cursor-move text-gray-400 select-none">⠿</span>
              <el-input v-model="it.label" placeholder="名称（≤20 字）" maxlength="20" style="width: 200px" />
              <el-input v-model="it.path" placeholder="路径，如 /goods?sort=sales 或 https://..." class="flex-1" />
              <el-button link type="danger" @click="removeHeaderItem(i)">删除</el-button>
            </div>
          </div>

          <el-button class="mt-3" @click="addHeaderItem">+ 添加菜单项（{{ headerItems.length }}/12）</el-button>
        </el-tab-pane>

        <!-- ===== Tab 2: 底部菜单 ===== -->
        <el-tab-pane label="底部菜单" name="footer">
          <div class="text-xs text-gray-400 mb-3">
            最多 5 列，每列最多 10 条链接。链接 path 留空则渲染纯文本（用于联系方式 label-value）。
          </div>

          <div class="flex flex-col gap-3">
            <div
              v-for="(col, ci) in footer.columns"
              :key="ci"
              class="p-3 bg-gray-50 rounded border border-gray-100"
            >
              <div class="flex items-center gap-2 mb-3">
                <el-input v-model="col.title" placeholder="列标题" style="width: 240px" />
                <div class="flex-1" />
                <el-button link :disabled="ci === 0" @click="moveColumn(ci, -1)">↑ 上移</el-button>
                <el-button link :disabled="ci === footer.columns.length - 1" @click="moveColumn(ci, 1)">↓ 下移</el-button>
                <el-button link type="danger" @click="removeColumn(ci)">删除列</el-button>
              </div>

              <div class="flex flex-col gap-2">
                <div
                  v-for="(l, li) in col.links"
                  :key="li"
                  class="flex items-center gap-2"
                  draggable="true"
                  @dragstart="onLinkDragStart(ci, li, $event)"
                  @dragover.prevent
                  @drop="onLinkDrop(ci, li)"
                  @dragend="linkDragRef = null"
                >
                  <span class="cursor-move text-gray-400 select-none">⠿</span>
                  <el-input v-model="l.label" placeholder="链接文字" style="width: 200px" />
                  <el-input v-model="l.path" placeholder="路径（留空 = 纯文本）" class="flex-1" />
                  <el-button link type="danger" @click="removeLink(ci, li)">×</el-button>
                </div>
              </div>

              <el-button class="mt-2" size="small" @click="addLink(ci)">+ 添加链接</el-button>
            </div>
          </div>

          <el-button class="mt-3" @click="addColumn">+ 添加列</el-button>

          <div class="mt-6">
            <label class="block text-sm text-gray-700 mb-2">版权文本</label>
            <el-input
              v-model="footer.copyright"
              type="textarea"
              :rows="2"
              placeholder="© {YEAR} ... 占位符 {YEAR} 会自动替换为当前年份"
            />
            <p class="text-xs text-gray-400 mt-1">提示：{YEAR} 会自动替换为当前年份</p>
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>
  </div>
</template>

<style scoped lang="scss">
.pc-menu-container {
  padding: 16px;

  .page-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
  }

  .page-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
  }

  .page-desc {
    font-size: 13px;
    color: var(--ink-500, #8b95a7);
    margin-top: 4px;
  }

  .page-actions {
    display: flex;
    gap: 8px;
  }

  .tab-card {
    background: #fff;
    border-radius: 6px;
    padding: 16px 20px 20px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
  }
}
</style>
