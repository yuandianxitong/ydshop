<script lang="ts" setup>
/**
 * RegionCascader — 省/市/区联动选择
 *
 * 设计：
 *  - v-model 绑定名称数组 string[]（如 ['上海', '上海市', '浦东新区']）
 *  - 内部 cascader 用 ID 操作；加载完树后按名称路径反查 ID 回填
 *  - change 事件携带完整信息：{ ids, names, codes }，方便调用方写 region_code
 *  - 树数据请求一次后进程级缓存，多个组件实例复用
 *
 * 数据来源：GET /adminapi/region/tree（节点形如 { value, label, code, children? }）
 */
import { computed, onMounted, ref, watch } from 'vue'
import { regionApi } from '@/api/region'

interface RegionNode {
    value: number
    label: string
    code: string
    children?: RegionNode[]
}

const props = withDefaults(
    defineProps<{
        modelValue?: string[]
        placeholder?: string
        level?: 2 | 3
        disabled?: boolean
        clearable?: boolean
        size?: 'large' | 'default' | 'small'
    }>(),
    {
        modelValue: () => [],
        placeholder: '请选择地区',
        level: 3,
        disabled: false,
        clearable: true,
        size: 'default',
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: string[]): void
    (e: 'change', payload: { ids: number[]; names: string[]; codes: string[] }): void
}>()

const treeRaw = ref<RegionNode[]>([])
const loading = ref(false)

// 进程级缓存（同一会话内多个组件实例共享同一树）
let treeCache: Promise<RegionNode[]> | null = null

const cascaderProps = {
    value: 'value',
    label: 'label',
    children: 'children',
    checkStrictly: false,
    expandTrigger: 'hover' as const,
}

// 内部 cascader 选中（ID 数组）
const innerIds = ref<number[]>([])

// 计算可见 options（按 level 截断）
const options = computed(() => {
    if (props.level >= 3) return treeRaw.value
    return trimToLevel(treeRaw.value, props.level)
})

function trimToLevel(items: RegionNode[], maxLevel: number, current = 1): RegionNode[] {
    return items.map((item) => {
        const node: RegionNode = { value: item.value, label: item.label, code: item.code }
        if (current < maxLevel && item.children?.length) {
            node.children = trimToLevel(item.children, maxLevel, current + 1)
        }
        return node
    })
}

/**
 * 按名称路径反查节点 ID 路径。任一层未匹配则返回空数组。
 * 用于 modelValue (名称数组) → 内部 cascader 的 ID 数组
 */
function namesToIds(names: string[]): number[] {
    if (!names?.length || !treeRaw.value.length) return []
    const ids: number[] = []
    let level: RegionNode[] = treeRaw.value
    for (const name of names) {
        if (!name) break
        const hit = level.find((n) => n.label === name)
        if (!hit) return []
        ids.push(hit.value)
        level = hit.children || []
    }
    return ids
}

/**
 * 按 ID 路径取 { ids, names, codes }
 */
function idsToPayload(ids: number[]): { ids: number[]; names: string[]; codes: string[] } {
    const names: string[] = []
    const codes: string[] = []
    let level: RegionNode[] = treeRaw.value
    for (const id of ids) {
        const hit = level.find((n) => n.value === id)
        if (!hit) break
        names.push(hit.label)
        codes.push(hit.code || '')
        level = hit.children || []
    }
    return { ids: [...ids], names, codes }
}

async function loadTree(): Promise<RegionNode[]> {
    if (treeCache) return treeCache
    treeCache = (async () => {
        try {
            loading.value = true
            const res = await regionApi.getTree()
            return ((res.data as unknown) as RegionNode[]) || []
        } catch {
            return []
        } finally {
            loading.value = false
        }
    })()
    return treeCache
}

async function init() {
    treeRaw.value = await loadTree()
    syncFromModel()
}

function syncFromModel() {
    innerIds.value = namesToIds(props.modelValue)
}

function handleChange(value: number[] | null) {
    const ids = (value || []).filter((v) => v != null)
    innerIds.value = ids
    const payload = idsToPayload(ids)
    emit('update:modelValue', payload.names)
    emit('change', payload)
}

// 树未加载完时 modelValue 变化也要重新尝试
watch(() => props.modelValue, () => {
    if (treeRaw.value.length) syncFromModel()
})

onMounted(init)
</script>

<template>
    <el-cascader
        v-model="innerIds"
        :options="options"
        :props="cascaderProps"
        :placeholder="placeholder"
        :disabled="disabled"
        :clearable="clearable"
        :size="size"
        filterable
        @change="handleChange"
    />
</template>
