<template>
    <el-card class="table-card" shadow="never">
        <!-- Table Header -->
        <div class="table-header">
            <div v-if="selectionCount === 0" class="table-title">
                {{ title }}
                <span class="table-count">共 {{ pagination.total }} 条</span>
            </div>
            <div v-else class="table-sel-info">
                已选 <b>{{ selectionCount }}</b> 项
                <button class="table-sel-clear" @click="clearSelection">取消选择</button>
            </div>
            <div class="table-actions">
                <slot
                    name="headerExtra"
                    :selected-ids="selectedIds"
                    :selected-rows="selectedRows"
                    :selection-count="selectionCount"
                    :clear-selection="clearSelection"
                />
                <slot
                    v-if="selectionCount > 0"
                    name="batchActions"
                    :selected-ids="selectedIds"
                    :selected-rows="selectedRows"
                    :clear-selection="clearSelection"
                />
                <el-button v-if="onExport !== undefined" @click="handleExport">导出</el-button>
                <el-button
                    v-if="showColumnConfig && selectionCount === 0"
                    @click="colCfgVisible = true"
                >
                    <i class="i-svg:sliders-horizontal" />
                    列配置
                </el-button>
                <el-button
                    v-if="showBatchDelete && batchDeleteFn !== undefined && selectionCount > 0"
                    size="small"
                    type="danger"
                    @click="onBatchDelete"
                >
                    <i class="i-svg:trash-2" />
                    批量删除
                </el-button>
            </div>
        </div>

        <!-- Table -->
        <el-table
            ref="tableRef"
            v-loading="loading"
            :data="data"
            :row-key="rowKey"
            @selection-change="onSelectionChange"
        >
            <el-table-column
                v-if="selectable || batchDeleteFn !== undefined"
                type="selection"
                width="50"
            />
            <el-table-column
                v-for="col in visibleColumns"
                :key="col.key"
                :prop="col.prop || col.key"
                :label="col.label"
                :width="col.width"
                :min-width="col.minWidth"
                :fixed="col.fixed || undefined"
                :show-overflow-tooltip="col.showOverflowTooltip"
                :align="col.align"
                :sortable="col.sortable"
            >
                <template #default="scope">
                    <slot :name="col.key" v-bind="scope">
                        {{ scope.row[col.prop || col.key] }}
                    </slot>
                </template>
            </el-table-column>
        </el-table>

        <!-- Pagination Footer -->
        <div class="table-footer">
            <div class="table-footer-total">
                共 <b>{{ pagination.total }}</b> 条记录
            </div>
            <el-pagination
                :current-page="pagination.page"
                :page-size="pagination.limit"
                :total="pagination.total"
                :page-sizes="[10, 20, 50, 100]"
                layout="prev, pager, next, sizes"
                small
                @current-change="$emit('page-change', $event)"
                @size-change="$emit('size-change', $event)"
            />
        </div>

        <!-- Column Config Dialog -->
        <ColumnConfig
            v-model="colCfgVisible"
            :columns="columnConfigItems"
            @update:columns="onColumnConfigChange"
        />
    </el-card>
</template>

<script setup lang="ts">
import type { TableInstance } from 'element-plus'
import { computed, ref } from 'vue'

import ColumnConfig from '@/components/ColumnConfig/index.vue'

import type { ProColumn } from './types'
import { useColumnConfig } from './useColumnConfig'

defineSlots<{
    [name: string]: (props: any) => any
}>()

const props = withDefaults(
    defineProps<{
        title: string
        storageKey: string
        columns: ProColumn[]
        data: any[]
        loading?: boolean
        pagination: { page: number; limit: number; total: number }
        /** 是否显示多选列；仅用于自定义批量操作时无需传入空的 batchDeleteFn。 */
        selectable?: boolean
        batchDeleteFn?: (ids: number[]) => Promise<any>
        onExport?: () => void
        rowKey?: string
        /** 是否显示列配置按钮（默认 true） */
        showColumnConfig?: boolean
        /** 是否显示自带的批量删除按钮（默认 true）。关闭后可在 batchActions 插槽中自定义批量操作。 */
        showBatchDelete?: boolean
    }>(),
    {
        loading: false,
        rowKey: 'id',
        selectable: false,
        batchDeleteFn: undefined,
        onExport: undefined,
        showColumnConfig: true,
        showBatchDelete: true,
    }
)

const emit = defineEmits<{
    'page-change': [page: number]
    'size-change': [size: number]
    'selection-change': [rows: any[]]
}>()

// Column config
const { colCfgVisible, visibleColumns, columnConfigItems, onColumnConfigChange } = useColumnConfig(
    props.storageKey,
    () => props.columns
)

// Selection state
const tableRef = ref<TableInstance>()
const selectedRows = ref<any[]>([])

const selectionCount = computed(() => selectedRows.value.length)
const selectedIds = computed(() => selectedRows.value.map((r) => r[props.rowKey]))

function onSelectionChange(rows: any[]) {
    selectedRows.value = rows
    emit('selection-change', rows)
}

function clearSelection() {
    tableRef.value?.clearSelection()
}

// Batch delete
function onBatchDelete() {
    if (!props.batchDeleteFn) return
    props.batchDeleteFn(selectedIds.value)
}

// Export
function handleExport() {
    props.onExport?.()
}
</script>
