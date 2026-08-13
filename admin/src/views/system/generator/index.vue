<template>
    <div class="generator-page">
        <div class="page-head">
            <div>
                <div class="page-title">{{ $t('generator.title') }}</div>
                <div class="page-desc">{{ $t('generator.desc') }}</div>
            </div>
            <div class="page-actions">
                <el-button @click="handleSyncTables">同步表结构</el-button>
                <el-button @click="handleImportTable">导入表</el-button>
                <el-button type="primary" @click="handleBatchGenerate">批量生成</el-button>
            </div>
        </div>

        <ProTable
            title="数据库表"
            storage-key="generator-tables"
            row-key="name"
            :columns="columns"
            :data="filteredTables"
            :loading="loading"
            :pagination="pagination"
            selectable
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
            @selection-change="handleSelectionChange"
        >
            <template #headerExtra>
                <el-input
                    v-model="searchKeyword"
                    placeholder="搜索表名/注释"
                    clearable
                    size="small"
                    style="width: 200px"
                >
                    <template #prefix><i class="i-svg:search" /></template>
                </el-input>
            </template>

            <template #name="{ row }">
                <code class="tbl-name">{{ row.name }}</code>
            </template>

            <template #entity="{ row }">
                <code class="tbl-entity">{{ deriveEntityName(row.name) }}</code>
            </template>

            <template #rows="{ row }">
                <span class="font-num">{{ row.rows ?? 0 }}</span>
            </template>

            <template #action="{ row }">
                <el-button type="primary" size="small" text @click="handlePreview(row)">
                    预览
                </el-button>
                <el-button type="success" size="small" text @click="handleEditConfig(row)">
                    编辑配置
                </el-button>
                <el-button type="warning" size="small" text @click="handleGenerate(row)">
                    生成代码
                </el-button>
            </template>
        </ProTable>

        <!-- Preview Dialog -->
        <PreviewDialog
            v-model="previewVisible"
            :table-name="currentTable.name"
            :table-comment="currentTable.comment"
            :table-rows="currentTable.rows"
            :table-engine="currentTable.engine"
            :entity-name="currentEntityName"
            @edit-config="switchToConfig"
            @generate="switchToResult"
        />

        <!-- Config Dialog -->
        <ConfigDialog
            ref="configDialogRef"
            v-model="configVisible"
            :table-name="currentTable.name"
            :table-comment="currentTable.comment"
            :entity-name="currentEntityName"
            @saved="handleConfigSaved"
            @save-and-preview="handleSaveAndPreview"
        />

        <!-- Result Dialog -->
        <ResultDialog
            v-model="resultVisible"
            :entity-name="currentEntityName"
            :table-comment="currentTable.comment"
            :config="generatorConfig"
        />

        <!-- Import SQL Dialog -->
        <ImportSqlDialog
            v-model="importVisible"
            @success="handleImportSuccess"
        />
    </div>
</template>

<script setup lang="ts" name="GeneratorList">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted, ref, watch } from 'vue'

import { generatorApi } from '@/api/generator'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import type { GeneratorConfig, GeneratorTableInfo } from '@/types/generator'

import ConfigDialog from './components/ConfigDialog.vue'
import ImportSqlDialog from './components/ImportSqlDialog.vue'
import PreviewDialog from './components/PreviewDialog.vue'
import ResultDialog from './components/ResultDialog.vue'

// ─── Table data ───
const loading = ref(false)
const tables = ref<GeneratorTableInfo[]>([])
const searchKeyword = ref('')

const pagination = ref({
    page: 1,
    limit: 20,
    total: 0,
})

const matchingTables = computed(() => {
    let data = tables.value
    if (searchKeyword.value) {
        const kw = searchKeyword.value.toLowerCase()
        data = data.filter(
            (t) =>
                t.name.toLowerCase().includes(kw) ||
                (t.comment || '').toLowerCase().includes(kw)
        )
    }
    return data
})

const filteredTables = computed(() => {
    const start = (pagination.value.page - 1) * pagination.value.limit
    return matchingTables.value.slice(start, start + pagination.value.limit)
})

watch(
    matchingTables,
    (data) => {
        pagination.value.total = data.length
    },
    { immediate: true }
)

function handlePageChange(page: number) {
    pagination.value.page = page
}

function handleSizeChange(size: number) {
    pagination.value.limit = size
    pagination.value.page = 1
}

// ─── Column definitions ───
const columns: ProColumn[] = [
    { key: 'name', label: '表名', minWidth: 180 },
    { key: 'comment', label: '注释', minWidth: 160, showOverflowTooltip: true },
    { key: 'entity', label: '实体类', minWidth: 140 },
    { key: 'rows', label: '数据行', width: 100, align: 'center' },
    { key: 'updated_at', label: '最近更新', prop: 'updated_at', width: 160 },
    { key: 'action', label: '操作', width: 280, fixed: 'right', required: true },
]

// ─── Entity name derivation ───
const STRIP_PREFIXES = ['sys_', 'biz_', 'app_', 'cms_', 'ums_', 'pms_', 'oms_', 'sms_', 'wms_']

function deriveEntityName(tableName: string): string {
    let name = tableName
    for (const prefix of STRIP_PREFIXES) {
        if (name.startsWith(prefix)) {
            name = name.slice(prefix.length)
            break
        }
    }
    // PascalCase: also keep original prefix part as PascalCase
    // E.g. biz_order → BizOrder (full table name pascal-cased including prefix)
    return tableName
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase())
        .join('')
}

// ─── Selection state ───
const selectedTables = ref<GeneratorTableInfo[]>([])

function handleSelectionChange(rows: any[]) {
    selectedTables.value = rows
}

// ─── Dialog state ───
const previewVisible = ref(false)
const configVisible = ref(false)
const resultVisible = ref(false)
const configDialogRef = ref<InstanceType<typeof ConfigDialog> | null>(null)

const currentTable = ref<GeneratorTableInfo>({
    name: '',
    comment: '',
    engine: 'InnoDB',
    rows: 0,
})

const currentEntityName = computed(() => deriveEntityName(currentTable.value.name))

const generatorConfig = computed<GeneratorConfig>(() => {
    const cfg = configDialogRef.value?.config
    // 只有当 ConfigDialog 已填入有效数据时才使用其配置
    if (cfg && cfg.table_name && cfg.module_name && cfg.model_name) {
        return {
            table_name: cfg.table_name,
            module_name: cfg.module_name,
            model_name: cfg.model_name,
            table_comment: cfg.table_comment,
            columns: configDialogRef.value?.columns || [],
        }
    }
    return {
        table_name: currentTable.value.name,
        module_name: 'business',
        model_name: currentEntityName.value,
        table_comment: currentTable.value.comment,
    }
})

// ─── Actions ───
function handlePreview(row: GeneratorTableInfo) {
    currentTable.value = { ...row }
    previewVisible.value = true
}

function handleEditConfig(row: GeneratorTableInfo) {
    currentTable.value = { ...row }
    configVisible.value = true
}

function handleGenerate(row: GeneratorTableInfo) {
    currentTable.value = { ...row }
    resultVisible.value = true
}

function switchToConfig() {
    previewVisible.value = false
    configVisible.value = true
}

function switchToResult() {
    previewVisible.value = false
    resultVisible.value = true
}

function handleConfigSaved() {
    // Config saved, refresh table if needed
}

function handleSaveAndPreview() {
    configVisible.value = false
    previewVisible.value = true
}

async function handleSyncTables() {
    await fetchTables()
    ElMessage.success('表结构已同步')
}

const importVisible = ref(false)

function handleImportTable() {
    importVisible.value = true
}

function handleImportSuccess() {
    importVisible.value = false
    fetchTables()
}

async function handleBatchGenerate() {
    if (selectedTables.value.length === 0) {
        ElMessage.warning('请先选择要生成的表')
        return
    }
    try {
        await ElMessageBox.confirm(
            `即将为 ${selectedTables.value.length} 张表生成代码，确认？`,
            '批量生成',
            { type: 'warning' }
        )
    } catch {
        // 用户点了取消（ElMessageBox 会 reject）—— 静默吞掉，不让错误冒泡到 ErrorBoundary
        return
    }

    let successCount = 0
    let failCount = 0
    for (const table of selectedTables.value) {
        try {
            const entity = deriveEntityName(table.name)
            await generatorApi.generate({
                table_name: table.name,
                module_name: 'business',
                model_name: entity,
                table_comment: table.comment,
            })
            successCount++
        } catch {
            failCount++
        }
    }
    if (failCount === 0) {
        ElMessage.success(`批量生成完成，共 ${successCount} 张表`)
    } else {
        ElMessage.warning(`生成完成：成功 ${successCount}，失败 ${failCount}`)
    }
}

// ─── Fetch data ───
async function fetchTables() {
    loading.value = true
    try {
        const res = await generatorApi.getTables()
        tables.value = res.data || []
        pagination.value.total = tables.value.length
    } finally {
        loading.value = false
    }
}

onMounted(fetchTables)
</script>

<style lang="scss" scoped>
.generator-page {
    width: 100%;
}

.tbl-name {
    font-family: "Consolas", "Monaco", monospace;
    font-size: 12.5px;
    color: var(--ink-700);
    padding: 2px 6px;
    background: var(--ink-100);
    border-radius: var(--r-xs);
}

.tbl-entity {
    font-family: "Consolas", "Monaco", monospace;
    font-size: 12.5px;
    color: var(--brand-500);
    font-weight: 500;
}

.font-num {
    font-family: var(--font-num);
}
</style>
