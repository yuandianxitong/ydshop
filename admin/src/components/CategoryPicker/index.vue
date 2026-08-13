<template>
  <div class="category-picker">
    <div class="picker-tags">
      <el-tag
        v-for="item in displayItems"
        :key="item.id"
        :closable="!disabled"
        :type="item.deleted ? 'info' : 'primary'"
        :effect="item.deleted ? 'plain' : 'light'"
        class="picker-tag"
        @close="removeId(item.id)"
      >{{ item.name }}</el-tag>
      <el-button
        v-if="!disabled"
        size="small"
        @click="dialogVisible = true"
      >选择分类</el-button>
    </div>

    <el-dialog
      v-model="dialogVisible"
      title="选择分类"
      width="460px"
      append-to-body
      :close-on-click-modal="false"
    >
      <PickerPanel
        v-if="dialogVisible"
        :initial-ids="modelIds"
        :limit="limit"
        @confirm="onConfirm"
        @cancel="dialogVisible = false"
      />
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="CategoryPicker">
import { computed, ref, watch } from 'vue'
import { goodsCategoryApi } from '@/api/goods-category'
import PickerPanel from './PickerPanel.vue'

interface CategoryItem {
  id: number
  name: string
  parent_id: number
  deleted?: boolean
}

interface Props {
  modelValue: number[] | undefined
  limit?: number
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), { disabled: false })

const emit = defineEmits<{
  (e: 'update:modelValue', value: number[]): void
}>()

const dialogVisible = ref(false)
const selectedItems = ref<CategoryItem[]>([])

const modelIds = computed<number[]>(() =>
  Array.isArray(props.modelValue) ? props.modelValue.filter((n) => Number.isFinite(n)) : [],
)

const displayItems = computed<CategoryItem[]>(() =>
  modelIds.value.map((id) => {
    const found = selectedItems.value.find((s) => s.id === id)
    return found ?? { id, name: `分类#${id}（加载中）`, parent_id: 0 }
  }),
)

watch(
  modelIds,
  async (ids) => {
    if (!ids.length) {
      selectedItems.value = []
      return
    }
    const knownIds = new Set(selectedItems.value.map((s) => s.id))
    const missing = ids.filter((id) => !knownIds.has(id))
    if (!missing.length) {
      selectedItems.value = selectedItems.value.filter((s) => ids.includes(s.id))
      return
    }
    try {
      const res = await goodsCategoryApi.getByIds(missing)
      const fetched = (res.data || []) as CategoryItem[]
      const fetchedMap = new Map(fetched.map((f) => [f.id, f]))
      const next: CategoryItem[] = []
      for (const id of ids) {
        const existed = selectedItems.value.find((s) => s.id === id)
        if (existed) { next.push(existed); continue }
        const f = fetchedMap.get(id)
        next.push(f ?? { id, name: `分类#${id}（已删除）`, parent_id: 0, deleted: true })
      }
      selectedItems.value = next
    } catch {
      selectedItems.value = ids.map((id) => {
        const existed = selectedItems.value.find((s) => s.id === id)
        return existed ?? { id, name: `分类#${id}（加载失败）`, parent_id: 0 }
      })
    }
  },
  { immediate: true },
)

function removeId(id: number) {
  emit('update:modelValue', modelIds.value.filter((v) => v !== id))
}

function onConfirm(items: CategoryItem[]) {
  selectedItems.value = items
  emit('update:modelValue', items.map((i) => i.id))
  dialogVisible.value = false
}
</script>

<style lang="scss" scoped>
.category-picker { width: 100%; }
.picker-tags { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.picker-tag { font-size: 13px; }
</style>
