<template>
  <div class="picker-panel">
    <div class="picker-body">
      <div class="picker-tree">
        <div
          class="tree-item"
          :class="{ active: currentCategoryId === undefined }"
          @click="selectCategory(undefined)"
        >全部商品</div>
        <el-tree
          :data="categoryTree"
          :props="{ label: 'name', children: 'children' }"
          node-key="id"
          highlight-current
          @node-click="(node: any) => selectCategory(node.id)"
        />
      </div>

      <div class="picker-main">
        <el-input
          v-model="keyword"
          placeholder="搜索商品名称"
          clearable
          class="picker-search"
          @input="onSearchInput"
          @clear="onSearchInput"
        >
          <template #prefix><i class="i-ri-search-line" /></template>
        </el-input>

        <el-table
          ref="tableRef"
          :data="goodsList"
          v-loading="loading"
          :row-key="(row: any) => row.id"
          height="360"
          @select="onSelect"
          @select-all="onSelectAll"
        >
          <el-table-column v-if="multiple" type="selection" width="44" reserve-selection :selectable="rowSelectable" />
          <el-table-column v-else width="60">
            <template #default="{ row }">
              <el-radio :model-value="draftFirst?.id" :label="row.id" @change="onRadioPick(row as SpuItem)">&nbsp;</el-radio>
            </template>
          </el-table-column>
          <el-table-column label="图片" width="100">
            <template #default="{ row }">
              <img v-if="row.thumb" :src="row.thumb" class="row-thumb" alt="" />
            </template>
          </el-table-column>
          <el-table-column prop="name" label="名称" show-overflow-tooltip min-width="200" />
          <el-table-column label="价格" width="90">
            <template #default="{ row }">¥{{ row.min_price ?? '-' }}</template>
          </el-table-column>
          <el-table-column label="状态" width="100">
            <template #default="{ row }">
              <el-tag :type="row.status === 'on_sale' ? 'success' : 'info'" size="small">
                {{ row.status === 'on_sale' ? '在售' : (row.status === 'off_sale' ? '下架' : row.status) }}
              </el-tag>
            </template>
          </el-table-column>
        </el-table>

        <el-pagination
          v-model:current-page="page"
          :total="total"
          :page-size="pageSize"
          layout="total, prev, pager, next"
          class="picker-pagination"
          @current-change="loadGoods"
        />
      </div>
    </div>

    <div class="picker-footer">
      <span class="footer-count" :class="{ over: overLimit }">
        {{ countText }}
      </span>
      <span class="footer-actions">
        <el-button @click="emit('cancel')">取消</el-button>
        <el-button type="primary" :disabled="overLimit" @click="onConfirm">确定</el-button>
      </span>
    </div>
  </div>
</template>

<script setup lang="ts" name="GoodsPickerPanel">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { goodsSpuApi } from '@/api/goods-spu'
import { goodsCategoryApi } from '@/api/goods-category'

interface SpuItem {
  id: number
  name: string
  thumb?: string
  min_price?: number | string
  status: string
}

const props = defineProps<{
  initialSelected: SpuItem[]
  multiple: boolean
  limit?: number
}>()

const emit = defineEmits<{
  (e: 'confirm', items: SpuItem[]): void
  (e: 'cancel'): void
}>()

const tableRef = ref<any>(null)
const loading = ref(false)
const keyword = ref('')
const currentCategoryId = ref<number | undefined>(undefined)
const page = ref(1)
const pageSize = 10
const total = ref(0)
const goodsList = ref<SpuItem[]>([])
const categoryTree = ref<any[]>([])

// draft：跨页/搜索保留勾选
const draft = ref<Map<number, SpuItem>>(new Map())

const draftFirst = computed<SpuItem | undefined>(() => draft.value.values().next().value)

const overLimit = computed(() => {
  if (!props.multiple || !props.limit) return false
  return draft.value.size > props.limit
})

const countText = computed(() => {
  if (!props.multiple) return draft.value.size ? '已选 1' : '已选 0'
  return props.limit ? `已选 ${draft.value.size}/${props.limit}` : `已选 ${draft.value.size}`
})

function rowSelectable(row: SpuItem): boolean {
  if (!props.multiple || !props.limit) return true
  if (draft.value.has(row.id)) return true
  return draft.value.size < props.limit
}

onMounted(async () => {
  for (const it of props.initialSelected) {
    if (props.multiple && props.limit && draft.value.size >= props.limit) break
    draft.value.set(it.id, it)
  }
  if (categoryTree.value.length === 0) {
    try {
      const res = await goodsCategoryApi.getTree()
      categoryTree.value = res.data || []
    } catch { /* ignore */ }
  }
  await loadGoods()
})

async function loadGoods() {
  loading.value = true
  try {
    const params: Record<string, any> = { page: page.value, limit: pageSize }
    if (keyword.value) params.keyword = keyword.value
    if (currentCategoryId.value) params.category_id = currentCategoryId.value
    const res = await goodsSpuApi.getList(params as any)
    const data: any = res.data || {}
    goodsList.value = (data.list || []).map((g: any) => ({
      id: g.id,
      name: g.name,
      thumb: g.thumb || (g.images && g.images[0]) || '',
      min_price: g.min_price,
      status: g.status,
    }))
    total.value = data.pagination?.total || 0
    await nextTick()
    syncTableSelection()
  } finally {
    loading.value = false
  }
}

function syncTableSelection() {
  if (!props.multiple || !tableRef.value) return
  for (const row of goodsList.value) {
    tableRef.value.toggleRowSelection(row, draft.value.has(row.id))
  }
}

function selectCategory(id: number | undefined) {
  currentCategoryId.value = id
  page.value = 1
  loadGoods()
}

let searchTimer: ReturnType<typeof setTimeout>
function onSearchInput() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => { page.value = 1; loadGoods() }, 300)
}

function onSelect(_selection: SpuItem[], row: SpuItem) {
  if (draft.value.has(row.id)) {
    draft.value.delete(row.id)
  } else {
    if (props.multiple && props.limit && draft.value.size >= props.limit) {
      ElMessage.warning(`最多选 ${props.limit} 个`)
      // 还原勾选状态
      tableRef.value?.toggleRowSelection(row, false)
      return
    }
    draft.value.set(row.id, row)
  }
}

function onSelectAll(selection: SpuItem[]) {
  // 当页全选/全不选
  if (selection.length === 0) {
    for (const row of goodsList.value) draft.value.delete(row.id)
  } else {
    for (const row of goodsList.value) {
      if (draft.value.has(row.id)) continue
      if (props.multiple && props.limit && draft.value.size >= props.limit) {
        tableRef.value?.toggleRowSelection(row, false)
        continue
      }
      draft.value.set(row.id, row)
    }
  }
}

function onRadioPick(row: SpuItem) {
  draft.value.clear()
  draft.value.set(row.id, row)
}

function onConfirm() {
  if (!props.multiple) {
    const items = draftFirst.value ? [draftFirst.value] : []
    emit('confirm', items)
    return
  }
  emit('confirm', Array.from(draft.value.values()))
}

watch(() => props.multiple, () => {
  // multiple 变化时强制清空 draft，避免数据形态混乱
  draft.value.clear()
  for (const it of props.initialSelected) {
    if (props.multiple && props.limit && draft.value.size >= props.limit) break
    draft.value.set(it.id, it)
  }
})
</script>

<style lang="scss" scoped>
.picker-panel { display: flex; flex-direction: column; }
.picker-body { display: flex; gap: 12px; height: 440px; }
.picker-tree {
  width: 180px;
  border-right: 1px solid var(--el-border-color-lighter);
  overflow-y: auto;
  padding-right: 8px;
}
.tree-item {
  padding: 6px 8px;
  cursor: pointer;
  font-size: 13px;
  border-radius: 4px;
  &:hover { background: var(--el-fill-color-light); }
  &.active { background: var(--el-color-primary-light-9); color: var(--el-color-primary); }
}
.picker-main { flex: 1; display: flex; flex-direction: column; gap: 8px; min-width: 0; }
.picker-search { width: 100%; }
.picker-pagination {
  justify-content: flex-end;
}
.row-thumb { width: 32px; height: 32px; border-radius: 3px; object-fit: cover; }
.picker-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding-top: 12px; margin-top: 12px;
  border-top: 1px solid var(--el-border-color-lighter);
}
.footer-count { font-size: 13px; color: var(--el-text-color-regular); }
.footer-count.over { color: var(--el-color-danger); font-weight: 500; }
</style>
