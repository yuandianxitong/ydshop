<template>
  <div class="picker-panel">
    <el-input
      v-model="filterText"
      placeholder="搜索分类名称"
      clearable
      class="picker-search"
    >
      <template #prefix><i class="i-ri-search-line" /></template>
    </el-input>

    <div class="picker-tree-wrap">
      <el-tree
        ref="treeRef"
        :data="treeData"
        :props="{ label: 'name', children: 'children' }"
        node-key="id"
        show-checkbox
        check-strictly
        default-expand-all
        :filter-node-method="filterNode"
        :default-checked-keys="initialIds"
        @check="onCheck"
      />
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

<script setup lang="ts" name="CategoryPickerPanel">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { goodsCategoryApi } from '@/api/goods-category'

interface CategoryItem {
  id: number
  name: string
  parent_id: number
}

const props = defineProps<{
  initialIds: number[]
  limit?: number
}>()

const emit = defineEmits<{
  (e: 'confirm', items: CategoryItem[]): void
  (e: 'cancel'): void
}>()

const treeRef = ref<any>(null)
const treeData = ref<any[]>([])
const flatMap = ref<Map<number, CategoryItem>>(new Map())
const filterText = ref('')

const checkedCount = ref(props.initialIds.length)

const overLimit = computed(() => !!props.limit && checkedCount.value > props.limit)

const countText = computed(() =>
  props.limit ? `已选 ${checkedCount.value}/${props.limit}` : `已选 ${checkedCount.value}`,
)

function flatten(nodes: any[]) {
  for (const n of nodes) {
    flatMap.value.set(n.id, { id: n.id, name: n.name, parent_id: n.parent_id ?? 0 })
    if (n.children) flatten(n.children)
  }
}

function filterNode(value: string, data: any): boolean {
  if (!value) return true
  return String(data.name || '').includes(value)
}

watch(filterText, (v) => {
  treeRef.value?.filter(v)
})

onMounted(async () => {
  try {
    const res = await goodsCategoryApi.getTree()
    treeData.value = res.data || []
    flatten(treeData.value)
    await nextTick()
    if (props.initialIds.length) {
      treeRef.value?.setCheckedKeys(props.initialIds)
    }
  } catch { /* ignore */ }
})

function onCheck() {
  const keys = (treeRef.value?.getCheckedKeys() as number[]) || []
  if (props.limit && keys.length > props.limit) {
    ElMessage.warning(`最多选 ${props.limit} 个`)
    const trimmed = keys.slice(0, props.limit)
    treeRef.value?.setCheckedKeys(trimmed)
    checkedCount.value = trimmed.length
    return
  }
  checkedCount.value = keys.length
}

function onConfirm() {
  const keys = (treeRef.value?.getCheckedKeys() as number[]) || []
  const items: CategoryItem[] = []
  for (const k of keys) {
    const item = flatMap.value.get(k)
    if (item) items.push(item)
    else items.push({ id: k, name: `分类#${k}`, parent_id: 0 })
  }
  emit('confirm', items)
}
</script>

<style lang="scss" scoped>
.picker-panel { display: flex; flex-direction: column; gap: 12px; }
.picker-search { width: 100%; }
.picker-tree-wrap {
  height: 360px;
  overflow-y: auto;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 4px;
  padding: 8px;
}
.picker-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding-top: 12px; border-top: 1px solid var(--el-border-color-lighter);
}
.footer-count { font-size: 13px; color: var(--el-text-color-regular); }
.footer-count.over { color: var(--el-color-danger); font-weight: 500; }
</style>
