<template>
    <el-dialog
        v-model="visible"
        :title="`编辑生成配置 · ${tableName} · ${tableComment}`"
        width="900px"
        :close-on-click-modal="false"
        class="cg-config-dialog"
        @close="handleClose"
    >
        <!-- Tab Cards -->
        <div class="cg-tabs">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                :class="['cg-tab', { active: activeTab === tab.key }]"
                @click="activeTab = tab.key"
            >
                <span class="cg-tab-title">{{ tab.label }}</span>
                <span class="cg-tab-desc">{{ tab.desc }}</span>
            </button>
        </div>

        <!-- Tab: 基本信息 -->
        <div v-show="activeTab === 'basic'" class="cg-tab-body">
            <div class="cg-fm-grid">
                <div class="cg-fm-row">
                    <label class="cg-fm-label">实体类名 <em>*</em></label>
                    <el-input v-model="config.model_name" placeholder="如 SysUser" />
                </div>
                <div class="cg-fm-row">
                    <label class="cg-fm-label">模块名（中文）</label>
                    <el-input v-model="config.table_comment" placeholder="如 用户管理" />
                </div>
                <div class="cg-fm-row">
                    <label class="cg-fm-label">模块名（英文）</label>
                    <el-input v-model="config.module_name" placeholder="如 system" />
                </div>
                <div class="cg-fm-row">
                    <label class="cg-fm-label">作者</label>
                    <el-input v-model="config.author" placeholder="开发者名称" />
                </div>
                <div class="cg-fm-row">
                    <label class="cg-fm-label">模板类型</label>
                    <div class="radio-pills">
                        <button
                            v-for="opt in templateTypes"
                            :key="opt.value"
                            :class="['pill', { active: config.template_type === opt.value }]"
                            @click="config.template_type = opt.value"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                </div>
                <div class="cg-fm-row">
                    <label class="cg-fm-label">权限标识前缀</label>
                    <el-input v-model="config.perm_prefix" placeholder="如 system:user" />
                </div>
                <div class="cg-fm-row">
                    <label class="cg-fm-label">接口路径前缀</label>
                    <el-input v-model="config.api_prefix" placeholder="如 /api/system/user" />
                </div>
            </div>

        </div>

        <!-- Tab: 字段配置 -->
        <div v-show="activeTab === 'fields'" class="cg-tab-body">
            <div class="cg-tbl-wrap">
                <table class="cg-tbl">
                    <thead>
                        <tr>
                            <th style="min-width: 140px">字段</th>
                            <th style="width: 110px">PHP类型</th>
                            <th style="width: 120px">表单组件</th>
                            <th style="min-width: 100px">显示名称</th>
                            <th style="width: 60px; text-align: center">列表</th>
                            <th style="width: 60px; text-align: center">表单</th>
                            <th style="width: 60px; text-align: center">查询</th>
                            <th style="width: 60px; text-align: center">必填</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="col in columns" :key="col.name">
                            <td>
                                <code>{{ col.name }}</code>
                                <span class="cg-field-type">{{ col.raw_type || col.type }}</span>
                            </td>
                            <td>
                                <el-select v-model="col._php_type" size="small" style="width: 100%">
                                    <el-option v-for="pt in phpTypes" :key="pt" :label="pt" :value="pt" />
                                </el-select>
                            </td>
                            <td>
                                <el-select
                                    v-model="col.form_type"
                                    size="small"
                                    :disabled="!col.in_form"
                                    style="width: 100%"
                                >
                                    <el-option
                                        v-for="ft in formTypes"
                                        :key="ft.value"
                                        :label="ft.label"
                                        :value="ft.value"
                                    />
                                </el-select>
                            </td>
                            <td>
                                <el-input
                                    v-model="col._display_name"
                                    size="small"
                                    placeholder="显示名称"
                                />
                            </td>
                            <td style="text-align: center">
                                <el-checkbox v-model="col.in_list" />
                            </td>
                            <td style="text-align: center">
                                <el-checkbox v-model="col.in_form" />
                            </td>
                            <td style="text-align: center">
                                <el-checkbox v-model="col.searchable" />
                            </td>
                            <td style="text-align: center">
                                <el-checkbox v-model="col._required" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab: 生成设置 -->
        <div v-show="activeTab === 'settings'" class="cg-tab-body">
            <div class="cg-fm-grid">
                <div class="cg-fm-row">
                    <label class="cg-fm-label">后端路径</label>
                    <el-input v-model="config.backend_path" placeholder="后端代码输出路径" />
                </div>
                <div class="cg-fm-row">
                    <label class="cg-fm-label">前端路径</label>
                    <el-input v-model="config.frontend_path" placeholder="前端代码输出路径" />
                </div>
                <div class="cg-fm-row">
                    <label class="cg-fm-label">上级菜单</label>
                    <el-select
                        v-model="config.parent_menu"
                        placeholder="选择上级菜单"
                        clearable
                        style="width: 100%"
                    >
                        <el-option label="根菜单" :value="0" />
                        <el-option label="系统管理" :value="1" />
                        <el-option label="业务管理" :value="2" />
                    </el-select>
                </div>
                <div class="cg-fm-row">
                    <label class="cg-fm-label">生成方式</label>
                    <div class="radio-pills">
                        <button
                            v-for="opt in genMethods"
                            :key="opt.value"
                            :class="['pill', { active: config.gen_method === opt.value }]"
                            @click="config.gen_method = opt.value"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="cg-sec-h">
                <span class="cg-sec-bar"></span>
                模板选择
            </div>
            <div class="cg-tpl-list">
                <label
                    v-for="tpl in templateOptions"
                    :key="tpl.key"
                    :class="['cg-tpl-cell', { active: config.templates.includes(tpl.key) }]"
                >
                    <el-checkbox
                        :model-value="config.templates.includes(tpl.key)"
                        @change="toggleTemplate(tpl.key)"
                    />
                    <span>{{ tpl.label }}</span>
                </label>
            </div>
        </div>

        <template #footer>
            <span class="dialog-footer">
                <el-button @click="handleClose">取消</el-button>
                <el-button @click="handleSaveAndPreview">保存并预览</el-button>
                <el-button type="primary" @click="handleSave">保存配置</el-button>
            </span>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { computed, reactive, ref, watch } from 'vue'

import { generatorApi } from '@/api/generator'
import type { GeneratorColumnInfo } from '@/types/generator'

interface Props {
    modelValue: boolean
    tableName: string
    tableComment?: string
    entityName: string
}

interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'saved'): void
    (e: 'save-and-preview'): void
}

const props = withDefaults(defineProps<Props>(), {
    tableComment: '',
})
const emit = defineEmits<Emits>()

const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
})

const activeTab = ref('basic')

const tabs = [
    { key: 'basic', label: '基本信息', desc: '命名、模块、作者' },
    { key: 'fields', label: '字段配置', desc: '列表/表单/查询' },
    { key: 'settings', label: '生成设置', desc: '模板、路径、菜单' },
]

const templateTypes = [
    { label: 'CRUD', value: 'crud' },
    { label: '树表', value: 'tree' },
    { label: '主子表', value: 'master-detail' },
]

const genMethods = [
    { label: 'ZIP下载', value: 'zip' },
    { label: '仅预览', value: 'preview' },
    { label: '覆盖项目', value: 'overwrite' },
]

const phpTypes = ['string', 'int', 'float', 'bool', 'array', 'mixed']

const formTypes = [
    { label: '输入框', value: 'input' },
    { label: '文本域', value: 'textarea' },
    { label: '数字', value: 'number' },
    { label: '开关', value: 'switch' },
    { label: '下拉框', value: 'select' },
    { label: '日期', value: 'datepicker' },
    { label: '图片', value: 'image' },
]

const templateOptions = [
    { key: 'model', label: 'Model 数据模型' },
    { key: 'repository', label: 'Repository 数据仓库' },
    { key: 'service', label: 'Service 业务逻辑' },
    { key: 'controller', label: 'Controller API控制器' },
    { key: 'validate', label: 'Validate 验证器' },
    { key: 'route', label: 'Route 路由配置' },
    { key: 'vue', label: 'Vue前端' },
    { key: 'api', label: 'API封装' },
]

interface ExtendedColumn extends GeneratorColumnInfo {
    _php_type: string
    _display_name: string
    _required: boolean
}

const columns = ref<ExtendedColumn[]>([])

const config = reactive({
    table_name: '',
    module_name: '',
    model_name: '',
    table_comment: '',
    author: '',
    template_type: 'crud',
    perm_prefix: '',
    api_prefix: '',
    backend_path: '',
    frontend_path: '',
    parent_menu: 0 as number,
    gen_method: 'zip',
    templates: ['model', 'repository', 'service', 'controller', 'validate', 'route', 'vue', 'api'] as string[],
})

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

watch(
    () => props.modelValue,
    async (val) => {
        if (val && props.tableName) {
            config.table_name = props.tableName
            config.model_name = props.entityName
            config.table_comment = props.tableComment
            config.module_name = config.module_name || 'business'
            activeTab.value = 'basic'

            try {
                const res = await generatorApi.getColumns(props.tableName)
                columns.value = (res.data || []).map((c: GeneratorColumnInfo) => ({
                    ...c,
                    _php_type: mapPhpType(c.raw_type || c.type),
                    _display_name: c.comment || '',
                    _required: !c.nullable,
                }))
            } catch {
                columns.value = []
            }
        }
    }
)

function toggleTemplate(key: string) {
    const idx = config.templates.indexOf(key)
    if (idx === -1) {
        config.templates.push(key)
    } else {
        config.templates.splice(idx, 1)
    }
}

function handleSave() {
    if (!config.model_name) {
        ElMessage.warning('请填写实体类名')
        return
    }
    emit('saved')
    ElMessage.success('配置已保存')
    visible.value = false
}

function handleSaveAndPreview() {
    if (!config.model_name) {
        ElMessage.warning('请填写实体类名')
        return
    }
    emit('save-and-preview')
}

function handleClose() {
    visible.value = false
}

// Expose config and columns for parent to read
defineExpose({ config, columns })
</script>

<style lang="scss" scoped>
.cg-tabs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.cg-tab {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    padding: 12px 16px;
    border: 1px solid var(--ink-200);
    border-radius: var(--r-md);
    background: var(--card-bg);
    cursor: pointer;
    transition: all 0.15s;

    &:hover {
        border-color: var(--brand-400);
    }

    &.active {
        border-color: var(--brand-500);
        background: var(--brand-50);
    }
}

.cg-tab-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--ink-800);
}

.cg-tab-desc {
    font-size: 11.5px;
    color: var(--ink-400);
    margin-top: 2px;
}

.cg-tab-body {
    min-height: 200px;
}

.cg-fm-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px 20px;
}

.cg-fm-row {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.cg-fm-label {
    font-size: 12.5px;
    color: var(--ink-600);
    font-weight: 500;

    em {
        color: var(--danger);
        font-style: normal;
    }
}

.radio-pills {
    display: flex;
    gap: 8px;
}

.pill {
    padding: 6px 16px;
    border: 1px solid var(--ink-200);
    border-radius: var(--r-md);
    background: var(--card-bg);
    font-size: 13px;
    color: var(--ink-600);
    cursor: pointer;
    transition: all 0.15s;

    &:hover {
        border-color: var(--brand-400);
    }

    &.active {
        border-color: var(--brand-500);
        background: var(--brand-50);
        color: var(--brand-500);
        font-weight: 500;
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
    max-height: 380px;
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
        padding: 8px 10px;
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

.cg-field-type {
    display: block;
    font-size: 11px;
    color: var(--ink-400);
    margin-top: 2px;
}

.cg-tpl-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.cg-tpl-cell {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border: 1px solid var(--ink-200);
    border-radius: var(--r-md);
    cursor: pointer;
    font-size: 13px;
    color: var(--ink-700);
    transition: all 0.15s;

    &:hover {
        border-color: var(--brand-400);
    }

    &.active {
        border-color: var(--brand-500);
        background: var(--brand-50);
    }
}

.dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
</style>

<style lang="scss">
.cg-config-dialog {
    .el-dialog__body {
        padding: 16px 24px;
        max-height: 70vh;
        overflow-y: auto;
    }
}
</style>
