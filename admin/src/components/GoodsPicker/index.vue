<template>
  <div class="goods-picker">
    <div class="picker-tags">
      <el-tag
        v-for="item in displayItems"
        :key="item.id"
        :closable="!disabled"
        :type="item.deleted ? 'info' : (item.status !== 'on_sale' ? 'info' : 'primary')"
        :effect="item.deleted ? 'plain' : 'light'"
        class="picker-tag"
        @close="removeId(item.id)"
      >
        <img v-if="item.thumb" :src="item.thumb" class="picker-tag-thumb" alt="" />
        <span class="picker-tag-text">{{ item.name }}</span>
      </el-tag>
      <el-button
        v-if="!disabled"
        size="small"
        @click="dialogVisible = true"
      >{{ multiple ? '选择商品' : (modelIds.length ? '更换' : '选择商品') }}</el-button>
    </div>

    <el-dialog
      v-model="dialogVisible"
      :title="multiple ? '选择商品' : '选择单个商品'"
      width="860px"
      append-to-body
      :close-on-click-modal="false"
    >
      <PickerPanel
        v-if="dialogVisible"
        :initial-selected="selectedItems"
        :multiple="multiple"
        :limit="limit"
        @confirm="onConfirm"
        @cancel="dialogVisible = false"
      />
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="GoodsPicker">
import { computed, ref, watch } from 'vue'
import { goodsSpuApi } from '@/api/goods-spu'
import PickerPanel from './PickerPanel.vue'

interface SpuItem {
  id: number
  name: string
  thumb?: string
  min_price?: number | string
  status: string
  deleted?: boolean
}

interface Props {
  modelValue: number | number[] | undefined
  multiple?: boolean
  limit?: number
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  multiple: true,
  disabled: false,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: number | number[] | undefined): void
}>()

const dialogVisible = ref(false)
const selectedItems = ref<SpuItem[]>([])

const modelIds = computed<number[]>(() => {
  if (props.multiple) {
    return Array.isArray(props.modelValue) ? props.modelValue.filter((n) => Number.isFinite(n)) : []
  }
  return typeof props.modelValue === 'number' ? [props.modelValue] : []
})

const displayItems = computed<SpuItem[]>(() => {
  return modelIds.value.map((id) => {
    const found = selectedItems.value.find((s) => s.id === id)
    return found ?? { id, name: `商品#${id}（加载中）`, status: 'unknown' }
  })
})

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
      const res = await goodsSpuApi.getByIds(missing)
      const fetched = (res.data || []) as SpuItem[]
      const fetchedMap = new Map(fetched.map((f) => [f.id, f]))
      const next: SpuItem[] = []
      for (const id of ids) {
        const existed = selectedItems.value.find((s) => s.id === id)
        if (existed) { next.push(existed); continue }
        const f = fetchedMap.get(id)
        next.push(f ?? { id, name: `商品#${id}（已删除）`, status: 'unknown', deleted: true })
      }
      selectedItems.value = next
    } catch {
      selectedItems.value = ids.map((id) => {
        const existed = selectedItems.value.find((s) => s.id === id)
        return existed ?? { id, name: `商品#${id}（加载失败）`, status: 'unknown' }
      })
    }
  },
  { immediate: true },
)

function removeId(id: number) {
  if (props.multiple) {
    const next = modelIds.value.filter((v) => v !== id)
    emit('update:modelValue', next)
  } else {
    emit('update:modelValue', undefined)
  }
}

function onConfirm(items: SpuItem[]) {
  selectedItems.value = items
  if (props.multiple) {
    emit('update:modelValue', items.map((i) => i.id))
  } else {
    emit('update:modelValue', items[0]?.id)
  }
  dialogVisible.value = false
}
</script>

<style lang="scss" scoped>
.goods-picker {
  width: 100%;
}
.picker-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
.picker-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 2px 8px 2px 4px;
  height: 28px;
}
.picker-tag-thumb {
  width: 22px;
  height: 22px;
  border-radius: 3px;
  object-fit: cover;
}
.picker-tag-text {
  font-size: 13px;
  max-width: 160px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
