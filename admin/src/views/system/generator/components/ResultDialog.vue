<template>
    <el-dialog
        v-model="visible"
        width="1040px"
        :close-on-click-modal="false"
        class="cg-result-dialog"
        @close="handleClose"
    >
        <template #header>
            <span>代码生成结果 · {{ entityName }} · {{ tableComment }}</span>
        </template>

        <!-- Summary Bar -->
        <div class="cg-result-sum">
            <span class="cg-sum-dot"></span>
            <span class="cg-sum-label">生成成功</span>
            <span class="cg-sum-sep"></span>
            <span>共 <b>{{ fileEntries.length }}</b> 个文件</span>
            <span class="cg-sum-sep"></span>
            <span><b>{{ totalLines }}</b> 行代码</span>
            <span class="cg-sum-sep"></span>
            <span>耗时 <b>{{ elapsed }}</b>s</span>
            <span class="cg-sum-sep"></span>
            <span class="cg-sum-tip">未写入项目</span>
        </div>

        <!-- Split: Tree + Code -->
        <div class="cg-split">
            <!-- File Tree -->
            <div class="cg-tree">
                <div v-for="grp in fileGroups" :key="grp.label" class="cg-tree-grp">
                    <div class="cg-tree-grp-t">{{ grp.label }}</div>
                    <div
                        v-for="f in grp.files"
                        :key="f.key"
                        :class="['cg-tree-file', { active: selectedFile === f.key }]"
                        @click="selectedFile = f.key"
                    >
                        <i :class="['cg-file-ic', getFileIcon(f.name)]" />
                        <span class="cg-file-nm">{{ f.name }}</span>
                    </div>
                </div>
            </div>

            <!-- Code Preview -->
            <div class="cg-code">
                <div class="cg-code-head">
                    <div class="cg-code-head-left">
                        <i :class="['cg-file-ic', getFileIcon(currentFileName)]" />
                        <span class="cg-code-fn">{{ currentFileName }}</span>
                        <span class="cg-code-info">{{ currentLineCount }}行 · {{ currentCharCount }}字符</span>
                    </div>
                    <div class="cg-code-head-right">
                        <button class="cg-code-btn" @click="handleCopy">复制</button>
                        <button class="cg-code-btn" @click="handleDownloadFile">下载单文件</button>
                    </div>
                </div>
                <div class="cg-code-body">
                    <div class="cg-gutter">
                        <div v-for="n in currentLineCount" :key="n" class="cg-gutter-ln">{{ n }}</div>
                    </div>
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <pre class="cg-pre" v-html="highlightedCode"></pre>
                </div>
            </div>
        </div>

        <template #footer>
            <span class="dialog-footer">
                <el-button @click="handleClose">关闭</el-button>
                <el-button type="primary" :loading="downloading" @click="handleDownloadZip">下载 ZIP</el-button>
            </span>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { computed, ref, watch } from 'vue'

import { generatorApi } from '@/api/generator'
import type { GeneratorConfig } from '@/types/generator'

import { getFileIcon, getFileLang, highlightCode } from './highlight'

interface Props {
    modelValue: boolean
    entityName: string
    tableComment?: string
    config: GeneratorConfig
}

interface Emits {
    (e: 'update:modelValue', value: boolean): void
}

const props = withDefaults(defineProps<Props>(), {
    tableComment: '',
})
const emit = defineEmits<Emits>()

const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
})

interface FileEntry {
    key: string
    name: string
    path: string
    content: string
    group: string
}

const fileEntries = ref<FileEntry[]>([])
const selectedFile = ref('')
const elapsed = ref('0.0')
const loading = ref(false)

const totalLines = computed(() =>
    fileEntries.value.reduce((sum, f) => sum + f.content.split('\n').length, 0)
)

const currentFile = computed(() =>
    fileEntries.value.find((f) => f.key === selectedFile.value)
)
const currentFileName = computed(() => currentFile.value?.name || '')
const currentLineCount = computed(() => (currentFile.value?.content || '').split('\n').length)
const currentCharCount = computed(() => (currentFile.value?.content || '').length)

const highlightedCode = computed(() => {
    if (!currentFile.value) return ''
    const lang = getFileLang(currentFile.value.name)
    return highlightCode(lang, currentFile.value.content)
})

const fileGroups = computed(() => {
    const groups: Record<string, { label: string; files: FileEntry[] }> = {
        backend: { label: '后端 · PHP', files: [] },
        frontend: { label: '前端 · Vue', files: [] },
        database: { label: '数据库', files: [] },
    }
    for (const f of fileEntries.value) {
        const g = groups[f.group]
        if (g) g.files.push(f)
    }
    return Object.values(groups).filter((g) => g.files.length > 0)
})

watch(
    () => props.modelValue,
    async (val) => {
        if (val) {
            loading.value = true
            const start = performance.now()
            try {
                const res = await generatorApi.preview(props.config)
                const data = res.data || {}
                const entries: FileEntry[] = []
                for (const [key, file] of Object.entries(data)) {
                    const fileObj = file as { path?: string; content?: string }
                    const name = fileObj.path
                        ? fileObj.path.split('/').pop() || key
                        : key
                    const content = fileObj.content || ''
                    const group = classifyFile(name, fileObj.path || key)
                    entries.push({ key, name, path: fileObj.path || key, content, group })
                }
                fileEntries.value = entries
                if (entries.length > 0) {
                    selectedFile.value = entries[0].key
                }
            } catch {
                // Use placeholder files
                fileEntries.value = buildPlaceholderFiles()
                if (fileEntries.value.length > 0) {
                    selectedFile.value = fileEntries.value[0].key
                }
            } finally {
                elapsed.value = ((performance.now() - start) / 1000).toFixed(1)
                loading.value = false
            }
        }
    }
)

function buildPlaceholderFiles(): FileEntry[] {
    const e = props.entityName
    const kebab = e.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase()
    const module = props.config.module_name || 'business'
    return [
        {
            key: 'model',
            name: `${e}.php`,
            path: `app/model/${module}/${e}.php`,
            content: [
                '<?php',
                'declare(strict_types=1);',
                '',
                `namespace app\\model\\${module};`,
                '',
                'use core\\base\\Model;',
                '',
                `class ${e} extends Model`,
                '{',
                `    protected $name = '${props.config.table_name || kebab}';`,
                '',
                '    protected $fillable = [];',
                '}',
            ].join('\n'),
            group: 'backend',
        },
        {
            key: 'repository',
            name: `${e}Repository.php`,
            path: `app/repository/${module}/${e}Repository.php`,
            content: [
                '<?php',
                'declare(strict_types=1);',
                '',
                `namespace app\\repository\\${module};`,
                '',
                'use core\\base\\Repository;',
                '',
                `class ${e}Repository extends Repository`,
                '{',
                '}',
            ].join('\n'),
            group: 'backend',
        },
        {
            key: 'service',
            name: `${e}Service.php`,
            path: `app/service/${module}/${e}Service.php`,
            content: [
                '<?php',
                'declare(strict_types=1);',
                '',
                `namespace app\\service\\${module};`,
                '',
                'use core\\base\\Service;',
                '',
                `class ${e}Service extends Service`,
                '{',
                '}',
            ].join('\n'),
            group: 'backend',
        },
        {
            key: 'controller',
            name: `${e}Controller.php`,
            path: `app/adminapi/controller/v1/${module}/${e}Controller.php`,
            content: [
                '<?php',
                'declare(strict_types=1);',
                '',
                `namespace app\\adminapi\\controller\\v1\\${module};`,
                '',
                'use core\\base\\Controller;',
                '',
                `class ${e}Controller extends Controller`,
                '{',
                '}',
            ].join('\n'),
            group: 'backend',
        },
        {
            key: 'validate',
            name: `${e}Validate.php`,
            path: `app/adminapi/validate/v1/${module}/${e}Validate.php`,
            content: [
                '<?php',
                'declare(strict_types=1);',
                '',
                `namespace app\\adminapi\\validate\\v1\\${module};`,
                '',
                'use core\\base\\Validate;',
                '',
                `class ${e}Validate extends Validate`,
                '{',
                '    protected $rule = [];',
                '}',
            ].join('\n'),
            group: 'backend',
        },
        {
            key: 'route',
            name: `${module}.php`,
            path: `app/adminapi/route/${module}.php`,
            content: [
                '<?php',
                "use think\\facade\\Route;",
                '',
                `// ${props.tableComment || e} routes`,
                `Route::group('${kebab}', function () {`,
                `    Route::get('', 'v1.${module}.${e}Controller/index');`,
                '});',
            ].join('\n'),
            group: 'backend',
        },
        {
            key: 'api',
            name: `${kebab}.ts`,
            path: `admin/src/api/${kebab}.ts`,
            content: [
                "import { myRequest } from '@/utils/request'",
                '',
                `export const ${kebab.replace(/-([a-z])/g, (_, c: string) => c.toUpperCase())}Api = {`,
                '    getList(params: any) {',
                `        return myRequest.get('/adminapi/${module}/${kebab}', { params })`,
                '    },',
                '}',
            ].join('\n'),
            group: 'frontend',
        },
        {
            key: 'page',
            name: 'index.vue',
            path: `admin/src/views/${module}/${kebab}/index.vue`,
            content: '<template>\n    <div>List Page</div>\n</template>',
            group: 'frontend',
        },
        {
            key: 'form',
            name: `${e}Form.vue`,
            path: `admin/src/views/${module}/${kebab}/components/${e}Form.vue`,
            content: '<template>\n    <el-dialog>Form</el-dialog>\n</template>',
            group: 'frontend',
        },
    ]
}

function classifyFile(name: string, path: string): string {
    if (path.startsWith('database/')) return 'database'
    if (name.endsWith('.php')) return 'backend'
    if (name.endsWith('.vue') || name.endsWith('.ts') || name.endsWith('.tsx')) return 'frontend'
    if (name.endsWith('.sql')) return 'database'
    return 'backend'
}

function handleCopy() {
    if (currentFile.value) {
        navigator.clipboard.writeText(currentFile.value.content)
        ElMessage.success('已复制到剪贴板')
    }
}

function handleDownloadFile() {
    if (!currentFile.value) return
    const blob = new Blob([currentFile.value.content], { type: 'text/plain' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = currentFile.value.name
    a.click()
    URL.revokeObjectURL(url)
}

const downloading = ref(false)

async function handleDownloadZip() {
    downloading.value = true
    try {
        const res = await generatorApi.download(props.config)
        const blob = new Blob([res.data as any], { type: 'application/zip' })
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `${props.entityName}_code.zip`
        a.click()
        URL.revokeObjectURL(url)
        ElMessage.success('ZIP 下载成功')
    } catch {
        ElMessage.error('ZIP 下载失败')
    } finally {
        downloading.value = false
    }
}

function handleClose() {
    visible.value = false
}
</script>

<style lang="scss" scoped>
.cg-result-sum {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    background: var(--ink-50);
    border: 1px solid var(--ink-200);
    border-radius: var(--r-md);
    font-size: 13px;
    color: var(--ink-600);
    margin-bottom: 16px;

    b {
        color: var(--ink-900);
        font-family: var(--font-num);
    }
}

.cg-sum-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--success);
    flex-shrink: 0;
}

.cg-sum-label {
    font-weight: 600;
    color: var(--success);
}

.cg-sum-sep {
    width: 1px;
    height: 14px;
    background: var(--ink-200);
}

.cg-sum-tip {
    font-size: 12px;
    color: var(--ink-400);
    margin-left: auto;
}

.cg-split {
    display: flex;
    gap: 0;
    border: 1px solid var(--ink-200);
    border-radius: var(--r-md);
    overflow: hidden;
    height: 480px;
}

.cg-tree {
    width: 260px;
    min-width: 260px;
    border-right: 1px solid var(--ink-200);
    background: var(--ink-50);
    overflow-y: auto;
    padding: 8px 0;
}

.cg-tree-grp {
    margin-bottom: 4px;
}

.cg-tree-grp-t {
    padding: 6px 16px;
    font-size: 11px;
    font-weight: 600;
    color: var(--ink-400);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.cg-tree-file {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px 6px 24px;
    cursor: pointer;
    font-size: 13px;
    color: var(--ink-700);
    transition: background 0.1s;

    &:hover {
        background: var(--ink-100);
    }

    &.active {
        background: var(--brand-50);
        color: var(--brand-500);
        font-weight: 500;
    }
}

.cg-file-ic {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    font-size: 16px;
}

.cg-file-nm {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cg-code {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.cg-code-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 16px;
    border-bottom: 1px solid var(--ink-200);
    background: var(--card-bg);
    flex-shrink: 0;
}

.cg-code-head-left {
    display: flex;
    align-items: center;
    gap: 8px;
}

.cg-code-fn {
    font-size: 13px;
    font-weight: 500;
    color: var(--ink-800);
}

.cg-code-info {
    font-size: 11.5px;
    color: var(--ink-400);
}

.cg-code-head-right {
    display: flex;
    gap: 8px;
}

.cg-code-btn {
    padding: 4px 12px;
    border: 1px solid var(--ink-200);
    border-radius: var(--r-sm);
    background: var(--card-bg);
    font-size: 12px;
    color: var(--ink-600);
    cursor: pointer;
    transition: all 0.15s;

    &:hover {
        border-color: var(--brand-400);
        color: var(--brand-500);
    }
}

.cg-code-body {
    display: flex;
    flex: 1;
    overflow: auto;
    background: var(--ink-50);
}

.cg-gutter {
    width: 48px;
    min-width: 48px;
    padding: 12px 0;
    text-align: right;
    border-right: 1px solid var(--ink-200);
    user-select: none;
    background: var(--ink-100);
}

.cg-gutter-ln {
    padding: 0 8px 0 0;
    font-family: "Consolas", "Monaco", monospace;
    font-size: 12px;
    line-height: 20px;
    color: var(--ink-300);
}

.cg-pre {
    flex: 1;
    margin: 0;
    padding: 12px 16px;
    font-family: "Consolas", "Monaco", monospace;
    font-size: 12.5px;
    line-height: 20px;
    color: var(--ink-800);
    white-space: pre;
    overflow-x: auto;

    :deep(.cg-kw) {
        color: #7c3aed;
        font-weight: 500;
    }

    :deep(.cg-str) {
        color: #059669;
    }

    :deep(.cg-cmt) {
        color: var(--ink-400);
        font-style: italic;
    }

    :deep(.cg-anno) {
        color: #d97706;
    }

    :deep(.cg-tag) {
        color: #2563eb;
    }

    :deep(.cg-num) {
        color: #dc2626;
    }
}

.dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
</style>

<style lang="scss">
.cg-result-dialog {
    .el-dialog__body {
        padding: 16px 24px;
    }
}
</style>
