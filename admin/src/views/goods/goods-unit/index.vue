<template>
  <div class="goods-unit-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">计量单位</h2>
        <p class="page-desc">商品的计量单位维护与换算关系配置</p>
      </div>
      <div class="page-actions">
        <el-button v-if="activeTab === 'set'" v-has-perm="['goods.goods-unit.create']" type="primary" @click="handleAddGroup">
          <i class="i-svg:plus" /> 新建分组
        </el-button>
        <el-button v-else v-has-perm="['goods.goods-unit.create']" type="primary" @click="handleAddUnit">
          <i class="i-svg:plus" /> 新建单位
        </el-button>
      </div>
    </div>

    <!-- 概览统计 -->
    <div class="row-14">
      <div v-for="card in statCards" :key="card.label" class="card unit-stat">
        <div class="card-body">
          <div class="unit-stat-l">{{ card.label }}</div>
          <div class="unit-stat-v num">{{ formatCount(card.value) }}</div>
          <div class="unit-stat-s">{{ card.suffix }}</div>
        </div>
      </div>
    </div>

    <!-- Tab + 搜索 -->
    <div class="filter-bar unit-filter">
      <div class="seg unit-tab-seg">
        <button :class="{ on: activeTab === 'unit' }" @click="activeTab = 'unit'">
          单位列表 ({{ stats.unit_total ?? '—' }})
        </button>
        <button :class="{ on: activeTab === 'conv' }" @click="activeTab = 'conv'">
          换算关系 ({{ stats.conv_total ?? '—' }})
        </button>
        <button :class="{ on: activeTab === 'set' }" @click="activeTab = 'set'">
          分组管理
        </button>
      </div>
      <el-input
        v-if="activeTab === 'unit'"
        v-model="searchKeyword"
        placeholder="搜索单位 / 编码"
        clearable
        style="width: 240px"
        @clear="reloadUnits"
        @keyup.enter="reloadUnits"
      />
    </div>

    <!-- Tab 1: 单位列表 -->
    <template v-if="activeTab === 'unit'">
      <!-- 分组筛选芯片 -->
      <div class="unit-group-chips">
        <button
          class="unit-group-chip"
          :class="{ on: selectedGroupId === 0 }"
          @click="filterByGroup(0)"
        >
          全部 <span class="num">{{ stats.unit_total ?? 0 }}</span>
        </button>
        <button
          v-for="g in groups"
          :key="g.id"
          class="unit-group-chip"
          :class="{ on: selectedGroupId === g.id }"
          @click="filterByGroup(g.id)"
        >
          {{ g.name }} <span class="num">{{ g.units_count ?? 0 }}</span>
        </button>
      </div>

      <ProTable
        title="单位列表"
        storage-key="goods-unit-list"
        :columns="unitColumns"
        :data="unitList"
        :loading="unitLoading"
        :pagination="unitPagination"
        :batch-delete-fn="handleBatchDelete"
        @page-change="handlePageChange"
        @size-change="handleSizeChange"
      >
        <template #code="{ row }">
          <span class="chip num unit-code">{{ row.code }}</span>
        </template>

        <template #name="{ row }">
          <span class="unit-name">{{ row.name }}</span>
          <el-tag v-if="row.is_base" type="primary" size="small" style="margin-left: 8px">基准</el-tag>
        </template>

        <template #name_en="{ row }">
          <span class="unit-en">{{ row.name_en || '—' }}</span>
        </template>

        <template #group="{ row }">
          <el-tag :type="toneToTagType(row.group?.tone)" size="small">
            {{ row.group?.name || '—' }}
          </el-tag>
        </template>

        <template #ratio_text="{ row }">
          <span :class="row.is_base ? '' : 'num'">{{ row.ratio_text }}</span>
        </template>

        <template #linked_spu_count="{ row }">
          <span class="num unit-linked">{{ formatCount(row.linked_spu_count || 0) }}</span>
        </template>

        <template #status="{ row }">
          <el-switch
            v-model="row.status"
            :active-value="1"
            :inactive-value="0"
            :disabled="!userStore.hasPermission('goods.goods-unit.update')"
            @change="handleStatusChange(row)"
          />
        </template>

        <template #action="{ row }">
          <el-button v-has-perm="['goods.goods-unit.update']" type="primary" size="small" text @click="handleEditUnit(row)">
            编辑
          </el-button>
          <el-button
            v-if="!row.is_base"
            v-has-perm="['goods.goods-unit.delete']"
            type="danger"
            size="small"
            text
            @click="handleDelete(row.id, row.name)"
          >
            删除
          </el-button>
        </template>
      </ProTable>
    </template>

    <!-- Tab 2: 换算关系 -->
    <template v-else-if="activeTab === 'conv'">
      <div class="card conv-hint">
        <div class="card-body conv-hint-body">
          <div class="conv-hint-ic">≡</div>
          <div class="conv-hint-txt">
            <div class="t">换算关系说明</div>
            <div class="d">系统按基准单位自动计算转换，下单 / 库存 / 财务核算时可配置目标单位与小数舍入策略</div>
          </div>
        </div>
      </div>

      <el-card class="table-card" shadow="never" v-loading="convLoading">
        <el-table :data="conversions">
          <el-table-column label="源单位" min-width="130">
            <template #default="{ row }">
              <span class="chip num">{{ row.from_code }}</span>
              <span class="conv-from-name">{{ row.from_name }}</span>
            </template>
          </el-table-column>
          <el-table-column label="" width="60" align="center">
            <template #default>
              <span class="conv-arrow num">→</span>
            </template>
          </el-table-column>
          <el-table-column label="目标单位" min-width="130">
            <template #default="{ row }">
              <span class="chip num">{{ row.to_code }}</span>
              <span class="conv-to-name">{{ row.to_name }}</span>
            </template>
          </el-table-column>
          <el-table-column label="分组" width="120">
            <template #default="{ row }">
              <el-tag :type="toneToTagType(row.group_tone)" size="small">{{ row.group_name }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="换算系数" align="right" width="180">
            <template #default="{ row }">
              <span class="num conv-ratio">{{ formatRatio(row.ratio) }}</span>
            </template>
          </el-table-column>
        </el-table>
        <el-empty v-if="!convLoading && !conversions.length" description="暂无换算关系" />
      </el-card>
    </template>

    <!-- Tab 3: 分组管理 -->
    <template v-else>
      <div v-loading="groupLoading" class="group-grid">
        <div v-for="g in groups" :key="g.id" class="card group-card">
          <div class="group-card-head" :style="{ background: toneToBg(g.tone) }">
            <div>
              <div class="group-card-title" :style="{ color: toneToFg(g.tone) }">{{ g.name }}</div>
              <div class="group-card-desc">{{ g.units_count ?? 0 }} 个单位 · {{ g.code }}</div>
            </div>
            <div class="group-card-badge" :style="{ color: toneToFg(g.tone) }">{{ g.units_count ?? 0 }}</div>
          </div>
          <div class="group-card-body">
            <div v-if="!unitsByGroup[g.id]?.length" class="group-card-empty">暂无单位</div>
            <span
              v-for="u in (unitsByGroup[g.id] || [])"
              :key="u.id"
              class="chip"
              :style="u.is_base ? { color: toneToFg(g.tone), background: toneToBg(g.tone) } : {}"
            >{{ u.name }}</span>
          </div>
          <div class="group-card-foot">
            <el-button v-has-perm="['goods.goods-unit.update']" type="primary" size="small" text @click="handleEditGroup(g)">
              编辑分组
            </el-button>
            <el-button v-has-perm="['goods.goods-unit.create']" type="primary" size="small" text @click="handleAddUnitInGroup(g)">
              添加单位
            </el-button>
            <el-button v-has-perm="['goods.goods-unit.delete']" type="danger" size="small" text @click="handleDeleteGroup(g)">
              删除
            </el-button>
          </div>
        </div>

        <div v-if="!groupLoading && !groups.length" class="group-empty">
          <el-empty description="暂无分组，点击右上角新建分组" />
        </div>
      </div>
    </template>

    <!-- 弹窗 -->
    <GoodsUnitForm
      v-model="unitFormVisible"
      :form-data="unitFormData"
      :group-options="groups"
      @success="onUnitSuccess"
    />
    <GoodsUnitGroupForm
      v-model="groupFormVisible"
      :form-data="groupFormData"
      @success="onGroupSuccess"
    />
  </div>
</template>

<script setup lang="ts" name="GoodsUnitList">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted, ref, watch } from 'vue'

import {
  goodsUnitApi,
  goodsUnitGroupApi,
  type GoodsUnitConversion,
  type GoodsUnitGroupInfo,
  type GoodsUnitInfo,
  type GoodsUnitStats,
} from '@/api/goods-unit'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'
import { useUserStore } from '@/store'

import GoodsUnitForm from './components/GoodsUnitForm.vue'
import GoodsUnitGroupForm from './components/GoodsUnitGroupForm.vue'

const userStore = useUserStore()

// ─── Tab 状态 ─────────────────────────────────────────────────
type TabKey = 'unit' | 'conv' | 'set'
const activeTab = ref<TabKey>('unit')

// ─── 概览统计 ─────────────────────────────────────────────────
const stats = ref<Partial<GoodsUnitStats>>({})

const statCards = computed(() => [
  { label: '计量单位', value: stats.value.unit_total ?? 0, suffix: '已配置' },
  { label: '换算关系', value: stats.value.conv_total ?? 0, suffix: '条' },
  { label: '关联商品', value: stats.value.linked_total ?? 0, suffix: '件' },
  { label: '分组数',   value: stats.value.group_total ?? 0, suffix: '类' },
])

const loadStats = async () => {
  try {
    const res = await goodsUnitApi.getStats()
    stats.value = (res as any).data || {}
  } catch (e) {
    console.error(e)
  }
}

// ─── 分组数据（多 Tab 共用）────────────────────────────────────
const groups = ref<GoodsUnitGroupInfo[]>([])
const groupLoading = ref(false)

const loadGroups = async () => {
  groupLoading.value = true
  try {
    const res = await goodsUnitGroupApi.getList()
    groups.value = (res as any).data || []
  } finally {
    groupLoading.value = false
  }
}

// ─── Tab 1: 单位列表 ────────────────────────────────────────
const searchKeyword = ref('')
const selectedGroupId = ref<number>(0)

const {
  list: unitList,
  loading: unitLoading,
  pagination: unitPagination,
  searchForm: unitSearchForm,
  getList: getUnitList,
  handleSearch: triggerUnitSearch,
  handlePageChange,
  handleSizeChange,
  handleDelete,
  handleBatchDelete,
  handleStatusChange,
} = useListPage<GoodsUnitInfo, { keyword: string; group_id?: number; status?: number }>({
  fetchFn: (params) => goodsUnitApi.getList(params),
  deleteFn: (id) => goodsUnitApi.delete(id),
  batchDeleteFn: (ids) => goodsUnitApi.batchDelete({ ids }),
  updateStatusFn: (id, status) => goodsUnitApi.updateStatus(id, { status }),
  defaultSearchForm: { keyword: '', group_id: undefined, status: undefined },
  immediate: false,
})

const reloadUnits = () => {
  unitSearchForm.keyword = searchKeyword.value
  triggerUnitSearch()
}

const filterByGroup = (groupId: number) => {
  selectedGroupId.value = groupId
  unitSearchForm.group_id = groupId || undefined
  triggerUnitSearch()
}

const unitColumns: ProColumn[] = [
  { key: 'code', label: '编码', width: 100, required: true },
  { key: 'name', label: '单位名', minWidth: 140, required: true },
  { key: 'name_en', label: '英文名', width: 140, defaultVisible: false },
  { key: 'group', label: '分组', width: 110 },
  { key: 'ratio_text', label: '换算关系', minWidth: 180 },
  { key: 'decimal_places', label: '小数位', prop: 'decimal_places', width: 100, align: 'right' },
  { key: 'linked_spu_count', label: '关联商品', width: 110, align: 'right' },
  { key: 'status', label: '状态', width: 90 },
  { key: 'action', label: '操作', width: 160, fixed: 'right', required: true },
]

// ─── Tab 2: 换算关系 ────────────────────────────────────────
const conversions = ref<GoodsUnitConversion[]>([])
const convLoading = ref(false)

const loadConversions = async () => {
  convLoading.value = true
  try {
    const res = await goodsUnitApi.getConversions()
    conversions.value = (res as any).data || []
  } finally {
    convLoading.value = false
  }
}

// ─── Tab 3: 分组管理 — 单位预览 ────────────────────────────
const unitsByGroup = ref<Record<number, GoodsUnitInfo[]>>({})

const loadUnitsByGroup = async () => {
  // 拉所有启用单位用于分组预览
  try {
    const res = await goodsUnitApi.getList({ page: 1, limit: 200 })
    const all = (res as any).data?.list || []
    const map: Record<number, GoodsUnitInfo[]> = {}
    for (const u of all) {
      const gid = Number(u.group_id || 0)
      if (!map[gid]) map[gid] = []
      map[gid].push(u)
    }
    unitsByGroup.value = map
  } catch {
    unitsByGroup.value = {}
  }
}

// ─── 弹窗 ────────────────────────────────────────────────
const unitFormVisible = ref(false)
const unitFormData = ref<Record<string, any>>({})
const groupFormVisible = ref(false)
const groupFormData = ref<Record<string, any>>({})

const handleAddUnit = () => {
  unitFormData.value = { status: 1, decimal_places: 2, sort: 0, is_base: 0, ratio: 1, group_id: groups.value[0]?.id }
  unitFormVisible.value = true
}

const handleAddUnitInGroup = (g: GoodsUnitGroupInfo) => {
  unitFormData.value = { status: 1, decimal_places: 2, sort: 0, is_base: 0, ratio: 1, group_id: g.id }
  unitFormVisible.value = true
}

const handleEditUnit = (row: GoodsUnitInfo) => {
  unitFormData.value = { ...row }
  unitFormVisible.value = true
}

const handleAddGroup = () => {
  groupFormData.value = { tone: 'blue', sort: groups.value.length, status: 1 }
  groupFormVisible.value = true
}

const handleEditGroup = (g: GoodsUnitGroupInfo) => {
  groupFormData.value = { ...g }
  groupFormVisible.value = true
}

const handleDeleteGroup = async (g: GoodsUnitGroupInfo) => {
  try {
    await ElMessageBox.confirm(`确定删除分组「${g.name}」？`, '删除确认', {
      type: 'warning',
    })
    await goodsUnitGroupApi.delete(g.id)
    ElMessage.success('删除成功')
    await loadGroups()
    await loadStats()
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

const onUnitSuccess = async () => {
  await loadStats()
  await loadGroups()
  if (activeTab.value === 'unit') triggerUnitSearch()
  if (activeTab.value === 'conv') loadConversions()
  if (activeTab.value === 'set')  loadUnitsByGroup()
}

const onGroupSuccess = async () => {
  await loadGroups()
  await loadStats()
  if (activeTab.value === 'set') loadUnitsByGroup()
}

// ─── 工具 ────────────────────────────────────────────────
const formatCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')

const formatRatio = (n: number) => {
  const s = n.toFixed(6)
  return s.replace(/\.?0+$/, '')
}

const TONE_TAG: Record<string, 'primary' | 'success' | 'warning' | 'danger' | 'info'> = {
  rose: 'danger',
  blue: 'primary',
  cyan: 'info',
  violet: 'primary',
  amber: 'warning',
  teal: 'success',
}
const toneToTagType = (tone?: string) => TONE_TAG[tone || 'blue'] || 'primary'

const TONE_BG: Record<string, string> = {
  rose:   '#fff1f2',
  blue:   '#eff6ff',
  cyan:   '#ecfeff',
  violet: '#f5f3ff',
  amber:  '#fffbeb',
  teal:   '#ecfdf5',
}
const TONE_FG: Record<string, string> = {
  rose:   '#dc2626',
  blue:   '#2563eb',
  cyan:   '#0891b2',
  violet: '#7c3aed',
  amber:  '#d97706',
  teal:   '#0d9488',
}
const toneToBg = (tone?: string) => TONE_BG[tone || 'blue'] || TONE_BG.blue
const toneToFg = (tone?: string) => TONE_FG[tone || 'blue'] || TONE_FG.blue

// ─── 初始化 ────────────────────────────────────────────────
onMounted(async () => {
  await loadStats()
  await loadGroups()
  await getUnitList()
})

// 切 Tab 时按需加载
watch(activeTab, async (val) => {
  if (val === 'conv' && !conversions.value.length) await loadConversions()
  if (val === 'set' && !Object.keys(unitsByGroup.value).length) await loadUnitsByGroup()
})
</script>

<style lang="scss" scoped>
.goods-unit-container {
  .row-14 {
    margin-bottom: 14px;
  }
}

.unit-stat {
  padding: 0;

  .unit-stat-l {
    font-size: 12.5px;
    color: var(--ink-500);
  }

  .unit-stat-v {
    font-size: 26px;
    font-weight: 700;
    color: var(--ink-900);
    margin-top: 8px;
    letter-spacing: -0.01em;
  }

  .unit-stat-s {
    font-size: 11px;
    color: var(--ink-400);
    margin-top: 4px;
  }
}

.unit-filter {
  justify-content: flex-start;
  gap: 10px;
}

.unit-tab-seg {
  margin-right: auto;
}

.unit-group-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 14px;
  margin-bottom: 12px;
}

.unit-group-chip {
  padding: 6px 14px;
  border-radius: 16px;
  font-size: 12.5px;
  font-weight: 500;
  border: 1px solid var(--ink-100);
  background: #fff;
  color: var(--ink-600);
  cursor: pointer;
  transition: all 0.12s;

  &:hover {
    border-color: var(--brand-400);
    color: var(--brand-500);
  }

  &.on {
    border-color: var(--brand-500);
    background: var(--brand-50);
    color: var(--brand-600);
  }

  .num {
    margin-left: 4px;
    opacity: 0.7;
  }
}

.unit-code {
  font-size: 11.5px;
}

.unit-name {
  font-weight: 500;
  color: var(--ink-900);
}

.unit-en {
  color: var(--ink-500);
  font-size: 12.5px;
  font-style: italic;
}

.unit-linked {
  color: var(--brand-500);
}

/* Tab 2: 换算关系 */
.conv-hint {
  margin-top: 14px;
  margin-bottom: 12px;
  background: linear-gradient(90deg, #eff6ff 0%, #fff 60%);
  border: 1px solid var(--ink-100);
}

.conv-hint-body {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
}

.conv-hint-ic {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #dbeafe;
  color: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-family: var(--font-num);
  font-size: 16px;
}

.conv-hint-txt {
  flex: 1;

  .t {
    font-size: 13px;
    font-weight: 600;
    color: var(--ink-900);
  }

  .d {
    font-size: 12px;
    color: var(--ink-500);
    margin-top: 2px;
  }
}

.conv-arrow {
  color: var(--brand-500);
  font-weight: 600;
}

.conv-from-name,
.conv-to-name {
  margin-left: 8px;
  font-size: 12.5px;
  color: var(--ink-500);
}

.conv-ratio {
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-900);
}

/* Tab 3: 分组管理 */
.group-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 14px;
  margin-top: 14px;
}

.group-card {
  padding: 0;
  overflow: hidden;
}

.group-card-head {
  padding: 14px 16px;
  border-bottom: 1px solid var(--ink-100);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.group-card-title {
  font-size: 14px;
  font-weight: 600;
}

.group-card-desc {
  font-size: 11.5px;
  color: var(--ink-500);
  margin-top: 2px;
}

.group-card-badge {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-family: var(--font-num);
  font-size: 14px;
}

.group-card-body {
  padding: 12px 16px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  min-height: 56px;
}

.group-card-empty {
  font-size: 12px;
  color: var(--ink-400);
  padding: 4px 0;
}

.group-card-foot {
  padding: 10px 16px;
  border-top: 1px solid var(--ink-100);
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

.group-empty {
  grid-column: 1 / -1;
  padding: 40px 0;
}
</style>
