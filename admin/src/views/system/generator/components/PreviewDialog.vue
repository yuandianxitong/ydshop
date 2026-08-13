<template>
    <el-dialog
        v-model="visible"
        :title="`表结构预览 · ${tableName}`"
        width="880px"
        :close-on-click-modal="false"
        class="cg-preview-dialog"
        @close="handleClose"
    >
        <!-- Meta Grid -->
        <div class="cg-prev-meta">
            <div class="cg-prev-meta-cell">
                <span class="cg-prev-meta-label">表名</span>
                <code class="cg-prev-meta-val">{{ tableName }}</code>
            </div>
            <div class="cg-prev-meta-cell">
                <span class="cg-prev-meta-label">注释</span>
                <span class="cg-prev-meta-val">{{ tableComment || '-' }}</span>
            </div>
            <div class="cg-prev-meta-cell">
                <span class="cg-prev-meta-label">模型类</span>
                <code class="cg-prev-meta-val" style="color: var(--brand-500)">{{ entityName }}</code>
            </div>
            <div class="cg-prev-meta-cell">
                <span class="cg-prev-meta-label">字段数</span>
                <span class="cg-prev-meta-val font-num">{{ columns.length }}</span>
            </div>
            <div class="cg-prev-meta-cell">
                <span class="cg-prev-meta-label">数据行</span>
                <span class="cg-prev-meta-val font-num">{{ tableRows }}</span>
            </div>
            <div class="cg-prev-meta-cell">
                <span class="cg-prev-meta-label">引擎/字符集</span>
                <span class="cg-prev-meta-val">{{ tableEngine || 'InnoDB' }} / utf8mb4</span>
            </div>
        </div>

        <!-- Field List Section -->
        <div class="cg-sec-h">
            <span class="cg-sec-bar"></span>
            字段列表
        </div>
        <div class="cg-tbl-wrap">
            <table class="cg-tbl">
                <thead>
                    <tr>
                        <th>字段名</th>
                        <th>类型</th>
                        <th style="text-align: center">主键</th>
                        <th style="text-align: center">非空</th>
                        <th>注释</th>
                        <th>PHP属性</th>
                        <th>PHP类型</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="col in columns" :key="col.name">
                        <td><code>{{ col.name }}</code></td>
                        <td>{{ col.raw_type || col.type }}</td>
                        <td style="text-align: center">
                            <span v-if="col.key === 'PRI'" class="pk-badge">PK</span>
                            <span v-else class="dot-dim"></span>
                        </td>
                        <td style="text-align: center">
                            <span :class="col.nullable ? 'dot-dim' : 'dot-ok'"></span>
                        </td>
                        <td>{{ col.comment || '-' }}</td>
                        <td><code>{{ col.name }}</code></td>
                        <td>{{ mapPhpType(col.raw_type || col.type) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Generated Files Section -->
        <div class="cg-sec-h">
            <span class="cg-sec-bar"></span>
            将生成的文件（预估）
        </div>
        <div class="cg-files">
            <div class="cg-files-col">
                <div class="cg-files-t">后端</div>
                <div v-for="f in backendFiles" :key="f.name" class="cg-file-row">
                    <i :class="['cg-file-ic', f.ic]" />
                    <span class="cg-file-nm">{{ f.name }}</span>
                    <span class="cg-file-p">{{ f.path }}</span>
                </div>
            </div>
            <div class="cg-files-col">
                <div class="cg-files-t">前端</div>
                <div v-for="f in frontendFiles" :key="f.name" class="cg-file-row">
                    <i :class="['cg-file-ic', f.ic]" />
                    <span class="cg-file-nm">{{ f.name }}</span>
                    <span class="cg-file-p">{{ f.path }}</span>
                </div>
            </div>
            <div class="cg-files-col">
                <div class="cg-files-t">数据库</div>
                <div v-for="f in sqlFiles" :key="f.name" class="cg-file-row">
                    <i :class="['cg-file-ic', f.ic]" />
                    <span class="cg-file-nm">{{ f.name }}</span>
                    <span class="cg-file-p">{{ f.path }}</span>
                </div>
            </div>
        </div>

        <template #footer>
            <span class="dialog-footer">
                <el-button @click="handleClose">关闭</el-button>
                <el-button @click="$emit('edit-config')">编辑配置</el-button>
                <el-button type="primary" @click="$emit('generate')">生成代码</el-button>
            </span>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import { generatorApi } from '@/api/generator'
import type { GeneratorColumnInfo } from '@/types/generator'

interface Props {
    modelValue: boolean
    tableName: string
    tableComment?: string
    tableRows?: number
    tableEngine?: string
    entityName: string
}

interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'edit-config'): void
    (e: 'generate'): void
}

const props = withDefaults(defineProps<Props>(), {
    tableComment: '',
    tableRows: 0,
    tableEngine: 'InnoDB',
})
const emit = defineEmits<Emits>()

const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
})

const columns = ref<GeneratorColumnInfo[]>([])
const loading = ref(false)

watch(
    () => props.modelValue,
    async (val) => {
        if (val && props.tableName) {
            loading.value = true
            try {
                const res = await generatorApi.getColumns(props.tableName)
                columns.value = res.data || []
            } catch {
                columns.value = []
            } finally {
                loading.value = false
            }
        }
    }
)

function mapPhpType(sqlType: string): string {
    const t = (sqlType || '').toUpperCase().split('(')[0].trim()
    const map: Record<string, string> = {
        BIGINT: 'int', INT: 'int', INTEGER: 'int', TINYINT: 'int',
        SMALLINT: 'int', MEDIUMINT: 'int',
        VARCHAR: 'string', CHAR: 'string', TEXT: 'string',
        LONGTEXT: 'string', MEDIUMTEXT: 'string', TINYTEXT: 'string',
        ENUM: 'string', JSON: 'string',
        DECIMAL: 'float', NUMERIC: 'float', FLOAT: 'float', DOUBLE: 'float',
        BOOLEAN: 'bool', BIT: 'bool',
        DATETIME: 'string', TIMESTAMP: 'string', DATE: 'string', TIME: 'string',
        BLOB: 'string',
    }
    return map[t] || 'string'
}

// Computed file lists based on entity name
const backendFiles = computed(() => {
    const e = props.entityName
    const module = 'business'
    return [
        { name: `${e}.php`, path: `app/model/${module}/${e}.php`, ic: 'i-svg:file-php' },
        { name: `${e}Repository.php`, path: `app/repository/${module}/${e}Repository.php`, ic: 'i-svg:file-php' },
        { name: `${e}Service.php`, path: `app/service/${module}/${e}Service.php`, ic: 'i-svg:file-php' },
        { name: `${e}Controller.php`, path: `app/adminapi/controller/v1/${module}/${e}Controller.php`, ic: 'i-svg:file-php' },
        { name: `${e}Validate.php`, path: `app/adminapi/validate/v1/${module}/${e}Validate.php`, ic: 'i-svg:file-php' },
        { name: `${module}.php`, path: `app/adminapi/route/${module}.php`, ic: 'i-svg:file-php' },
    ]
})

const frontendFiles = computed(() => {
    const e = props.entityName
    const kebab = e.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase()
    const module = 'business'
    return [
        { name: 'index.vue', path: `admin/src/views/${module}/${kebab}/index.vue`, ic: 'i-svg:file-vue' },
        { name: `${e}Form.vue`, path: `admin/src/views/${module}/${kebab}/components/${e}Form.vue`, ic: 'i-svg:file-vue' },
        { name: `${kebab}.ts`, path: `admin/src/api/${kebab}.ts`, ic: 'i-svg:file-ts' },
    ]
})

const sqlFiles = computed(() => {
    // 代码生成器不再产出迁移文件；表结构变更走 database/updates/vX.Y.Z + php think yd:update
    return [
        { name: 'database/updates/vX.Y.Z', path: '手写 update.sql 后执行 php think yd:update', ic: 'i-svg:file-php' },
    ]
})

function handleClose() {
    visible.value = false
}
</script>

<style lang="scss" scoped>
.font-num {
    font-family: var(--font-num);
}

.cg-prev-meta {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0;
    border: 1px solid var(--ink-200);
    border-radius: var(--r-md);
    overflow: hidden;
    margin-bottom: 20px;
}

.cg-prev-meta-cell {
    padding: 12px 16px;
    border-right: 1px solid var(--ink-200);
    border-bottom: 1px solid var(--ink-200);
    display: flex;
    flex-direction: column;
    gap: 4px;

    &:nth-child(3n) {
        border-right: none;
    }

    &:nth-child(n + 4) {
        border-bottom: none;
    }
}

.cg-prev-meta-label {
    font-size: 12px;
    color: var(--ink-400);
}

.cg-prev-meta-val {
    font-size: 13px;
    color: var(--ink-900);
    font-weight: 500;

    code {
        font-family: "Consolas", "Monaco", monospace;
        font-size: 12.5px;
    }
}

.cg-sec-h {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--ink-800);
    margin: 20px 0 12px;
}

.cg-sec-bar {
    width: 3px;
    height: 16px;
    background: var(--brand-500);
    border-radius: 2px;
}

.cg-tbl-wrap {
    max-height: 320px;
    overflow: auto;
    border: 1px solid var(--ink-200);
    border-radius: var(--r-md);
}

.cg-tbl {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;

    th,
    td {
        padding: 8px 12px;
        border-bottom: 1px solid var(--ink-100);
    }

    th {
        background: var(--ink-50);
        font-weight: 500;
        color: var(--ink-600);
        font-size: 12px;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    td code {
        font-family: "Consolas", "Monaco", monospace;
        font-size: 12px;
        color: var(--ink-700);
    }

    tbody tr:hover {
        background: var(--ink-50);
    }

    tbody tr:last-child td {
        border-bottom: none;
    }
}

.pk-badge {
    display: inline-block;
    padding: 1px 6px;
    background: var(--amber-500);
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    border-radius: 3px;
    letter-spacing: 0.5px;
}

.dot-ok,
.dot-dim {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.dot-ok {
    background: var(--success);
}

.dot-dim {
    background: var(--ink-200);
}

.cg-files {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 8px;
}

.cg-files-col {
    border: 1px solid var(--ink-200);
    border-radius: var(--r-md);
    padding: 12px;
}

.cg-files-t {
    font-size: 12px;
    font-weight: 600;
    color: var(--ink-500);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
}

.cg-file-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
}

.cg-file-ic {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    font-size: 18px;
}

.cg-file-nm {
    font-size: 12.5px;
    font-weight: 500;
    color: var(--ink-800);
    white-space: nowrap;
}

.cg-file-p {
    font-size: 11px;
    color: var(--ink-400);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
</style>

<style lang="scss">
.cg-preview-dialog {
    .el-dialog__body {
        padding: 16px 24px;
        max-height: 70vh;
        overflow-y: auto;
    }
}
</style>
