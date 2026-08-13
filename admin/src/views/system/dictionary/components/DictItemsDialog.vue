<template>
    <el-dialog
        v-model="visible"
        width="860px"
        :close-on-click-modal="false"
        destroy-on-close
        class="di-dialog"
        @close="handleClose"
    >
        <template #header>
            <div>
                <span class="el-dialog__title">数据项 · {{ dictName }}</span>
                <div class="di-subtitle">
                    字典编码
                    <code>{{ dictCode }}</code>
                    · 共 <b>{{ items.length }}</b> 项
                </div>
            </div>
        </template>

        <!-- Toolbar -->
        <div class="di-bar">
            <div class="di-search">
                <i class="i-svg:search text-13px" />
                <input v-model="keyword" placeholder="搜索标签或值" />
            </div>
            <div class="di-bar-sp" />
            <template v-if="selectedIds.size > 0">
                <span class="di-sel">已选 <b>{{ selectedIds.size }}</b> 项</span>
                <el-button size="small" @click="handleBatchDisable">批量停用</el-button>
                <el-button size="small" @click="handleBatchDelete">批量删除</el-button>
                <button class="di-link-btn" @click="selectedIds.clear()">取消</button>
            </template>
            <template v-else>
                <el-button size="small" @click="handleExport">导出</el-button>
                <el-button size="small" type="primary" @click="startAdd">
                    <i class="i-svg:plus" />
                    新增数据项
                </el-button>
            </template>
        </div>

        <!-- Table -->
        <div class="di-tbl">
            <div class="di-head">
                <div class="di-c-drag" />
                <div class="di-c-chk">
                    <input type="checkbox" :checked="allChecked" @change="toggleAll" />
                </div>
                <div class="di-c-label">标签</div>
                <div class="di-c-value">键值</div>
                <div class="di-c-tag">标签预览</div>
                <div class="di-c-sort">排序</div>
                <div class="di-c-status">状态</div>
                <div class="di-c-remark">备注</div>
                <div class="di-c-act">操作</div>
            </div>
            <div v-loading="loading" class="di-body">
                <!-- Empty -->
                <div v-if="!loading && filtered.length === 0" class="di-empty">
                    <div class="ic">
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="4" rx="1"/><rect x="3" y="10" width="18" height="4" rx="1"/><rect x="3" y="16" width="18" height="4" rx="1"/></svg>
                    </div>
                    <div class="t">{{ keyword.trim() ? `未找到匹配「${keyword}」的数据项` : '暂无数据项' }}</div>
                    <button v-if="!keyword.trim()" class="di-link-btn" @click="startAdd">点击新增</button>
                </div>

                <!-- Rows -->
                <div
                    v-for="item in filtered"
                    :key="item.id"
                    class="di-row"
                    :class="{
                        on: selectedIds.has(item.id),
                        drag: dragIdx === getItemRealIndex(item),
                        over: overIdx === getItemRealIndex(item) && dragIdx !== null && dragIdx !== getItemRealIndex(item),
                    }"
                    :draggable="!keyword.trim()"
                    @dragstart="onDragStart(getItemRealIndex(item))"
                    @dragover.prevent="onDragOver(getItemRealIndex(item))"
                    @drop="onDrop(getItemRealIndex(item))"
                    @dragend="onDragEnd"
                >
                    <div class="di-c-drag">
                        <span class="di-handle" :title="keyword.trim() ? '清空搜索后可拖拽排序' : '拖动排序'">
                            <i class="i-svg:grip-vertical text-14px" />
                        </span>
                    </div>
                    <div class="di-c-chk">
                        <input type="checkbox" :checked="selectedIds.has(item.id)" @change="toggleItem(item.id)" />
                    </div>
                    <div class="di-c-label"><b>{{ item.label }}</b></div>
                    <div class="di-c-value"><code>{{ item.value }}</code></div>
                    <div class="di-c-tag">
                        <span
                            class="di-preview"
                            :style="{
                                background: colorOf(item.tag_type) + '18',
                                color: colorOf(item.tag_type),
                                borderColor: colorOf(item.tag_type) + '40',
                            }"
                        >
                            ● {{ item.label }}
                        </span>
                    </div>
                    <div class="di-c-sort">
                        <span class="di-sort-n">{{ item.sort ?? 0 }}</span>
                    </div>
                    <div class="di-c-status">
                        <button class="di-sw" :class="{ on: item.status === 1 }" @click="handleToggleStatus(item)">
                            <span class="di-sw-dot" />
                        </button>
                    </div>
                    <div class="di-c-remark">
                        <template v-if="item.description">{{ item.description }}</template>
                        <span v-else class="di-muted">—</span>
                    </div>
                    <div class="di-c-act">
                        <button class="di-act" @click="startEdit(item)">编辑</button>
                        <button class="di-act danger" @click="handleDeleteSingle(item)">删除</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inline Editor Overlay -->
        <div v-if="editing !== null" class="di-editor">
            <div class="di-editor-hd">
                <div class="t">{{ editing === 'new' ? '新增数据项' : '编辑数据项' }}</div>
                <button class="di-editor-close" @click="editing = null">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="di-editor-body">
                <div class="di-form-row">
                    <label>标签 <span class="req">*</span></label>
                    <input v-model="editForm.label" class="di-input" placeholder="用户可见的显示文本" />
                </div>
                <div class="di-form-row">
                    <label>键值 <span class="req">*</span></label>
                    <input v-model="editForm.value" class="di-input" placeholder="业务存储值，如 1 / active" />
                </div>
                <div class="di-form-row">
                    <label>排序</label>
                    <input v-model.number="editForm.sort" class="di-input" type="number" style="width: 100px" />
                </div>
                <div class="di-form-row">
                    <label>标签颜色</label>
                    <div class="di-colors">
                        <button
                            v-for="c in TAG_COLORS"
                            :key="c.key"
                            class="di-color"
                            :class="{ on: editForm.tag_type === c.key }"
                            :style="{ background: c.hex }"
                            :title="c.key"
                            @click="editForm.tag_type = c.key"
                        />
                    </div>
                </div>
                <div class="di-form-row">
                    <label>状态</label>
                    <div class="di-radio">
                        <button :class="{ on: editForm.status === 1 }" @click="editForm.status = 1">启用</button>
                        <button :class="{ on: editForm.status === 0 }" @click="editForm.status = 0">停用</button>
                    </div>
                </div>
                <div class="di-form-row">
                    <label>备注</label>
                    <input v-model="editForm.description" class="di-input" placeholder="可选说明" />
                </div>
            </div>
            <div class="di-editor-foot">
                <el-button @click="editing = null">取消</el-button>
                <el-button type="primary" :disabled="!editFormValid" :loading="saving" @click="handleSaveItem">
                    {{ editing === 'new' ? '新增' : '保存' }}
                </el-button>
            </div>
        </div>

        <template #footer>
            <div class="di-footer">
                <div class="di-footer-hint">拖拽行首 ⋮⋮ 图标可调整顺序</div>
                <el-button @click="handleClose">关闭</el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, reactive, ref, watch } from 'vue'

import { dictionaryApi } from '@/api/dictionary'
import type { DictionaryItemInfo } from '@/types/system'

// ─── Tag color map ───
const TAG_COLORS = [
    { key: 'primary', hex: '#4f6bff' },
    { key: 'success', hex: '#10b981' },
    { key: 'warning', hex: '#f59e0b' },
    { key: 'danger', hex: '#f43f5e' },
    { key: 'info', hex: '#8b5cf6' },
    { key: '', hex: '#64748b' },
]

function colorOf(tagType?: string): string {
    return TAG_COLORS.find((c) => c.key === (tagType || ''))?.hex || '#64748b'
}

// ─── Props / Emits ───
interface Props {
    modelValue: boolean
    dictId: number
    dictName: string
    dictCode: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
})

function handleClose() {
    visible.value = false
    editing.value = null
    selectedIds.clear()
    keyword.value = ''
}

// ─── Data ───
const loading = ref(false)
const items = ref<DictionaryItemInfo[]>([])
const keyword = ref('')
const selectedIds = reactive(new Set<number>())

const filtered = computed(() => {
    if (!keyword.value.trim()) return items.value
    const q = keyword.value.toLowerCase()
    return items.value.filter(
        (i) => i.label.toLowerCase().includes(q) || i.value.toLowerCase().includes(q)
    )
})

const allChecked = computed(() => {
    return filtered.value.length > 0 && filtered.value.every((i) => selectedIds.has(i.id))
})

function toggleAll() {
    if (allChecked.value) {
        filtered.value.forEach((i) => selectedIds.delete(i.id))
    } else {
        filtered.value.forEach((i) => selectedIds.add(i.id))
    }
}

function toggleItem(id: number) {
    if (selectedIds.has(id)) selectedIds.delete(id)
    else selectedIds.add(id)
}

function getItemRealIndex(item: DictionaryItemInfo): number {
    return items.value.indexOf(item)
}

// ─── Fetch ───
async function fetchItems() {
    if (!props.dictId) return
    loading.value = true
    try {
        const res = await dictionaryApi.getItems(props.dictId)
        const data = res.data
        items.value = Array.isArray(data) ? data : data?.list || []
    } catch {
        console.error('获取字典项失败')
    } finally {
        loading.value = false
    }
}

watch(
    () => props.modelValue,
    (v) => {
        if (v && props.dictId) fetchItems()
    }
)

// ─── Drag & Drop ───
const dragIdx = ref<number | null>(null)
const overIdx = ref<number | null>(null)

function onDragStart(idx: number) {
    dragIdx.value = idx
}
function onDragOver(idx: number) {
    overIdx.value = idx
}
async function onDrop(targetIdx: number) {
    if (dragIdx.value === null || dragIdx.value === targetIdx) {
        dragIdx.value = null
        overIdx.value = null
        return
    }
    const arr = [...items.value]
    const [moved] = arr.splice(dragIdx.value, 1)
    arr.splice(targetIdx, 0, moved)
    items.value = arr.map((it, i) => ({ ...it, sort: i + 1 }))
    dragIdx.value = null
    overIdx.value = null
    try {
        await Promise.all(items.value.map((item) => dictionaryApi.updateItem(item.id, { sort: item.sort })))
        ElMessage.success('排序已保存')
    } catch {
        ElMessage.error('排序保存失败')
        fetchItems()
    }
}
function onDragEnd() {
    dragIdx.value = null
    overIdx.value = null
}

// ─── Status Toggle ───
async function handleToggleStatus(item: DictionaryItemInfo) {
    const newStatus = item.status === 1 ? 0 : 1
    try {
        await dictionaryApi.updateItem(item.id, { status: newStatus })
        item.status = newStatus
    } catch {
        ElMessage.error('状态更新失败')
    }
}

// ─── Delete ───
async function handleDeleteSingle(item: DictionaryItemInfo) {
    try {
        await ElMessageBox.confirm(
            `即将删除「${item.label}」，引用该值的业务数据可能会显示为空，请谨慎操作。`,
            '确认删除数据项？',
            { type: 'warning', confirmButtonText: '确认删除', cancelButtonText: '取消' }
        )
    } catch {
        return
    }
    await dictionaryApi.deleteItem(item.id)
    ElMessage.success('删除成功')
    selectedIds.delete(item.id)
    fetchItems()
}

async function handleBatchDelete() {
    const ids = [...selectedIds]
    try {
        await ElMessageBox.confirm(
            `即将删除 ${ids.length} 项，引用该值的业务数据可能会显示为空，请谨慎操作。`,
            '确认删除数据项？',
            { type: 'warning', confirmButtonText: '确认删除', cancelButtonText: '取消' }
        )
    } catch {
        return
    }
    for (const id of ids) {
        await dictionaryApi.deleteItem(id)
    }
    ElMessage.success('批量删除成功')
    selectedIds.clear()
    fetchItems()
}

async function handleBatchDisable() {
    const ids = [...selectedIds]
    if (!ids.length) return
    await Promise.all(ids.map((id) => dictionaryApi.updateItem(id, { status: 0 })))
    items.value.forEach((item) => {
        if (selectedIds.has(item.id)) item.status = 0
    })
    selectedIds.clear()
    ElMessage.success('批量停用成功')
}

function handleExport() {
    const escape = (value: unknown) => `"${String(value ?? '').replaceAll('"', '""')}"`
    const rows = [
        ['标签', '键值', '标签颜色', '排序', '状态', '备注'],
        ...filtered.value.map((item) => [
            item.label,
            item.value,
            item.tag_type || '',
            item.sort ?? 0,
            item.status === 1 ? '启用' : '停用',
            item.description || '',
        ]),
    ]
    const csv = '\uFEFF' + rows.map((row) => row.map(escape).join(',')).join('\r\n')
    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }))
    const anchor = document.createElement('a')
    anchor.href = url
    anchor.download = `${props.dictCode || 'dictionary'}-items.csv`
    anchor.click()
    URL.revokeObjectURL(url)
}

// ─── Inline Editor ───
const editing = ref<'new' | DictionaryItemInfo | null>(null)
const saving = ref(false)

const editForm = reactive({
    label: '',
    value: '',
    sort: 0,
    tag_type: '' as string,
    status: 1 as number,
    description: '',
})

const editFormValid = computed(() => editForm.label.trim() && editForm.value.trim())

function startAdd() {
    const maxSort = items.value.reduce((max, i) => Math.max(max, i.sort ?? 0), 0)
    Object.assign(editForm, {
        label: '',
        value: '',
        sort: maxSort + 1,
        tag_type: '',
        status: 1,
        description: '',
    })
    editing.value = 'new'
}

function startEdit(item: DictionaryItemInfo) {
    Object.assign(editForm, {
        label: item.label,
        value: item.value,
        sort: item.sort ?? 0,
        tag_type: item.tag_type || '',
        status: item.status,
        description: item.description || '',
    })
    editing.value = item
}

async function handleSaveItem() {
    if (!editFormValid.value) return
    saving.value = true
    try {
        const data = {
            label: editForm.label,
            value: editForm.value,
            sort: editForm.sort,
            tag_type: editForm.tag_type,
            status: editForm.status,
            description: editForm.description,
        }
        if (editing.value === 'new') {
            await dictionaryApi.createItem({ dictionary_id: props.dictId, ...data })
            ElMessage.success('新增成功')
        } else {
            await dictionaryApi.updateItem((editing.value as DictionaryItemInfo).id, data)
            ElMessage.success('保存成功')
        }
        editing.value = null
        fetchItems()
    } catch {
        ElMessage.error('保存失败')
    } finally {
        saving.value = false
    }
}
</script>

<style lang="scss" scoped>
/* ─── Dialog overrides ─── */
:deep(.el-dialog__body) {
    padding: 20px !important;
    position: relative;
}

.di-subtitle {
    font-size: 12.5px;
    color: var(--ink-500);
    margin-top: 4px;

    code {
        font-family: var(--font-num);
        font-size: 11.5px;
        background: var(--ink-100);
        padding: 1px 6px;
        border-radius: 3px;
        color: var(--ink-600);
    }

    b {
        font-family: var(--font-num);
        color: var(--ink-700);
    }
}

/* ─── Toolbar ─── */
.di-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    background: var(--ink-50);
    border: 1px solid var(--ink-100);
    border-radius: 5px;
    margin-bottom: 12px;
}

.di-search {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #fff;
    border: 1px solid var(--ink-200);
    border-radius: 4px;
    padding: 4px 8px;
    width: 220px;
    color: var(--ink-400);

    &:focus-within {
        border-color: var(--brand-400);
    }

    input {
        border: 0;
        outline: 0;
        background: transparent;
        flex: 1;
        font-size: 12.5px;
        color: var(--ink-800);
        min-width: 0;
    }
}

.di-bar-sp {
    flex: 1;
}

.di-sel {
    font-size: 12.5px;
    color: var(--ink-600);

    b {
        color: var(--brand-500);
        font-family: var(--font-num);
    }
}

.di-link-btn {
    background: none;
    border: 0;
    color: var(--brand-500);
    cursor: pointer;
    font-size: 12.5px;
    padding: 2px 4px;

    &:hover {
        text-decoration: underline;
    }
}

/* ─── Table ─── */
.di-tbl {
    border: 1px solid var(--ink-100);
    border-radius: 5px;
    overflow: hidden;
    background: #fff;
}

.di-head,
.di-row {
    display: grid;
    grid-template-columns: 28px 30px 120px 110px 130px 60px 60px 1fr 110px;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    font-size: 12.5px;
}

.di-head {
    background: var(--ink-50);
    border-bottom: 1px solid var(--ink-100);
    color: var(--ink-600);
    font-weight: 500;
}

.di-body {
    max-height: 360px;
    overflow: auto;
    min-height: 100px;
}

.di-row {
    border-bottom: 1px solid var(--ink-50);
    color: var(--ink-800);
    transition: background 0.12s;

    &:last-child {
        border-bottom: 0;
    }

    &:hover {
        background: var(--ink-50);
    }

    &.on {
        background: rgba(79, 107, 255, 0.05);
    }

    &.drag {
        opacity: 0.45;
    }

    &.over {
        box-shadow: inset 0 2px 0 var(--brand-500);
    }
}

.di-c-label b {
    font-weight: 600;
    color: var(--ink-900);
}

.di-c-value code {
    font-family: var(--font-num);
    font-size: 12px;
    background: var(--ink-50);
    padding: 1px 6px;
    border-radius: 3px;
    color: var(--ink-700);
}

.di-preview {
    display: inline-flex;
    align-items: center;
    font-size: 11.5px;
    padding: 2px 8px;
    border-radius: 10px;
    border: 1px solid;
    line-height: 1.4;
}

.di-sort-n {
    display: inline-block;
    font-family: var(--font-num);
    font-size: 11.5px;
    background: var(--ink-100);
    color: var(--ink-600);
    padding: 1px 8px;
    border-radius: 9px;
}

.di-muted {
    color: var(--ink-400);
}

/* ─── Drag handle ─── */
.di-handle {
    display: inline-flex;
    cursor: grab;
    color: var(--ink-300);
    transition: color 0.15s;

    &:hover {
        color: var(--ink-600);
    }
}

.di-row.drag .di-handle {
    cursor: grabbing;
}

/* ─── Status switch ─── */
.di-sw {
    width: 30px;
    height: 16px;
    border-radius: 8px;
    background: var(--ink-200);
    border: 0;
    cursor: pointer;
    position: relative;
    padding: 0;
    transition: background 0.15s;

    &.on {
        background: var(--brand-500);
    }
}

.di-sw-dot {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #fff;
    transition: transform 0.15s;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);

    .di-sw.on & {
        transform: translateX(14px);
    }
}

/* ─── Row actions ─── */
.di-c-act {
    display: flex;
    gap: 4px;
    justify-content: flex-end;
}

.di-act {
    background: none;
    border: 0;
    cursor: pointer;
    font-size: 12px;
    color: var(--brand-500);
    padding: 2px 4px;

    &:hover {
        text-decoration: underline;
    }

    &.danger {
        color: var(--rose-500);
    }
}

/* ─── Empty state ─── */
.di-empty {
    padding: 50px 0;
    text-align: center;
    color: var(--ink-400);

    .ic {
        margin-bottom: 8px;
        color: var(--ink-300);
    }

    .t {
        font-size: 13px;
        margin-bottom: 6px;
    }
}

/* ─── Editor overlay ─── */
.di-editor {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    top: 0;
    background: rgba(255, 255, 255, 0.96);
    display: flex;
    flex-direction: column;
    backdrop-filter: blur(6px);
    animation: diSlideUp 0.2s ease;
    z-index: 10;
}

@keyframes diSlideUp {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: none;
    }
}

.di-editor-hd {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 22px;
    border-bottom: 1px solid var(--ink-100);

    .t {
        font-size: 14px;
        font-weight: 600;
        color: var(--ink-900);
    }
}

.di-editor-close {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    background: transparent;
    border: 0;
    cursor: pointer;
    color: var(--ink-500);
    display: flex;
    align-items: center;
    justify-content: center;

    &:hover {
        background: var(--ink-100);
        color: var(--ink-800);
    }
}

.di-editor-body {
    flex: 1;
    overflow: auto;
    padding: 20px 22px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.di-editor-foot {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 12px 22px;
    border-top: 1px solid var(--ink-100);
    background: var(--ink-50);
}

.di-form-row {
    display: grid;
    grid-template-columns: 88px 1fr;
    align-items: center;
    gap: 14px;

    label {
        font-size: 12.5px;
        color: var(--ink-600);
        text-align: right;

        .req {
            color: var(--rose-500);
            margin-left: 2px;
        }
    }
}

.di-input {
    width: 100%;
    border: 1px solid var(--ink-200);
    border-radius: 4px;
    padding: 7px 10px;
    font-size: 13px;
    outline: 0;
    background: #fff;
    color: var(--ink-800);
    transition: border-color 0.15s;

    &:focus {
        border-color: var(--brand-400);
        box-shadow: 0 0 0 3px rgba(79, 107, 255, 0.08);
    }
}

/* ─── Color picker ─── */
.di-colors {
    display: flex;
    gap: 6px;
}

.di-color {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid #fff;
    cursor: pointer;
    box-shadow: 0 0 0 1px var(--ink-200);
    transition: transform 0.12s;

    &:hover {
        transform: scale(1.1);
    }

    &.on {
        box-shadow: 0 0 0 2px var(--brand-500);
    }
}

/* ─── Status radio ─── */
.di-radio {
    display: inline-flex;
    background: var(--ink-100);
    border-radius: 4px;
    padding: 2px;

    button {
        border: 0;
        background: transparent;
        cursor: pointer;
        padding: 5px 14px;
        font-size: 12.5px;
        color: var(--ink-600);
        border-radius: 3px;
        transition: all 0.15s;

        &.on {
            background: #fff;
            color: var(--brand-500);
            font-weight: 500;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
        }
    }
}

/* ─── Footer ─── */
:deep(.el-dialog__footer) {
    text-align: left;
}

.di-footer {
    display: flex;
    align-items: center;
    width: 100%;
}

.di-footer-hint {
    flex: 1;
    font-size: 12.5px;
    color: var(--ink-500);
    text-align: left;
}
</style>
