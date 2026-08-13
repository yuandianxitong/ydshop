<template>
    <el-dialog
        :model-value="modelValue"
        title=""
        width="820px"
        :show-close="false"
        :close-on-click-modal="false"
        append-to-body
        class="import-dialog"
        @update:model-value="emit('update:modelValue', $event)"
        @close="handleClose"
    >
        <template #header>
            <div class="modal-head">
                <div class="modal-title">
                    {{ title }}
                    <span class="sub">通过 Excel 模板一次导入多个记录</span>
                </div>
                <button class="modal-close" @click="handleClose">
                    <i class="i-svg:x" />
                </button>
            </div>
        </template>

        <!-- Step indicator -->
        <div class="imp-steps">
            <template v-for="(s, i) in steps" :key="s.n">
                <div
                    class="imp-step"
                    :class="{ on: step === s.n, done: step > s.n }"
                >
                    <div class="imp-step-n">
                        <i v-if="step > s.n" class="i-svg:check" />
                        <template v-else>{{ s.n }}</template>
                    </div>
                    <div class="imp-step-txt">
                        <div class="t">{{ s.t }}</div>
                        <div class="d">{{ s.d }}</div>
                    </div>
                </div>
                <div
                    v-if="i < steps.length - 1"
                    class="imp-step-line"
                    :class="{ done: step > s.n }"
                />
            </template>
        </div>

        <!-- Step 1: Download template + Upload file -->
        <div v-if="step === 1" class="imp-body">
            <div class="imp-tpl">
                <div class="imp-tpl-ic">
                    <i class="i-svg:file-text" />
                </div>
                <div class="imp-tpl-txt">
                    <div class="t">第一步 · 下载导入模板</div>
                    <div class="d">模板包含必填字段说明与示例数据，请严格按照格式填写</div>
                </div>
                <div class="imp-tpl-btns">
                    <el-button v-if="templateUrl" size="small" type="primary" @click="downloadTemplate">
                        <i class="i-svg:download" />
                        下载导入模板
                    </el-button>
                    <el-button v-else size="small" type="primary" disabled>
                        <i class="i-svg:download" />
                        模板暂不可用
                    </el-button>
                </div>
            </div>

            <div class="imp-sec-label">第二步 · 上传填写好的文件</div>
            <label
                class="imp-drop"
                :class="{ over: dragOver, 'has-file': !!fileMeta }"
                @dragover.prevent="dragOver = true"
                @dragleave="dragOver = false"
                @drop.prevent="onDrop"
            >
                <input
                    ref="fileInputRef"
                    type="file"
                    hidden
                    accept=".xlsx,.xls,.csv"
                    @change="onFileChange"
                />
                <template v-if="!fileMeta">
                    <div class="imp-drop-ic">
                        <i class="i-svg:upload" />
                    </div>
                    <div class="imp-drop-t">
                        拖拽 Excel/CSV 文件到此处，或 <span class="lk">点击选择文件</span>
                    </div>
                    <div class="imp-drop-d">
                        支持 .xlsx / .xls / .csv 格式，单个文件不超过 5MB，最多 1000 条
                    </div>
                </template>
                <template v-else>
                    <div class="imp-file">
                        <div class="imp-file-ic">
                            <i class="i-svg:file-text" />
                        </div>
                        <div class="imp-file-txt">
                            <div class="nm">{{ fileMeta.name }}</div>
                            <div class="mt">{{ fileMeta.size }} · 已选择</div>
                        </div>
                        <button class="imp-file-x" @click.prevent="clearFile">
                            <i class="i-svg:x" />
                        </button>
                    </div>
                </template>
            </label>

            <div class="imp-note">
                <div class="t">注意事项</div>
                <ul v-if="notes.length">
                    <li v-for="(n, i) in notes" :key="i" v-html="n" />
                </ul>
                <ul v-else>
                    <li>
                        标记 <em class="req">*</em> 的字段为必填：姓名、登录账号、手机号、所属部门
                    </li>
                    <li>角色、部门字段需与系统中已存在的名称完全一致</li>
                    <li>初始密码为空时将自动生成，首次登录要求修改</li>
                </ul>
            </div>
        </div>

        <!-- Step 2: Validation preview -->
        <div v-if="step === 2" class="imp-body">
            <div class="imp-sum">
                <div class="imp-sum-cell">
                    <div class="l">文件</div>
                    <div class="v">{{ fileMeta?.name || '示例数据.xlsx' }}</div>
                </div>
                <div class="imp-sum-cell">
                    <div class="l">总记录</div>
                    <div class="v num">{{ previewList.length }}</div>
                </div>
                <div class="imp-sum-cell ok">
                    <div class="l">可导入</div>
                    <div class="v num">{{ okCount }}</div>
                </div>
                <div class="imp-sum-cell err">
                    <div class="l">异常行</div>
                    <div class="v num">{{ errCount }}</div>
                </div>
            </div>

            <div class="imp-tbl-wrap">
                <div class="imp-tbl-head">
                    <div class="l">数据预览</div>
                    <div class="r">
                        <span class="legend-ok"><i />正常</span>
                        <span class="legend-err"><i />异常</span>
                    </div>
                </div>
                <div class="imp-tbl-scroll">
                    <table class="imp-tbl">
                        <thead>
                            <tr>
                                <th style="width: 48px">行号</th>
                                <th v-for="col in resolvedColumns" :key="col.key">{{ col.label }}</th>
                                <th style="width: 60px">状态</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="p in previewList"
                                :key="p.row"
                                :class="{ 'has-err': !p.ok }"
                            >
                                <td class="rn">{{ p.row }}</td>
                                <td
                                    v-for="col in resolvedColumns"
                                    :key="col.key"
                                    :class="{
                                        'err-cell':
                                            !p.ok && p.err && p.err.includes(col.label),
                                    }"
                                >
                                    {{ p[col.key] ?? '' }}
                                </td>
                                <td>
                                    <span
                                        v-if="p.ok"
                                        class="dot-ok"
                                        title="可导入"
                                    />
                                    <span
                                        v-else
                                        class="dot-err"
                                        :title="p.err"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="errCount > 0" class="imp-err-bar">
                    <i class="i-svg:triangle-alert" />
                    检测到 <b>{{ errCount }}</b> 条异常数据，确认导入后将<b>自动跳过</b>。
                </div>
            </div>
        </div>

        <!-- Step 3: Import complete -->
        <div v-if="step === 3" class="imp-body imp-done">
            <div class="imp-done-ic">
                <i class="i-svg:check" />
            </div>
            <div class="imp-done-t">导入完成</div>
            <div class="imp-done-d">
                本次共处理 {{ doneStats.total }} 条记录
            </div>

            <div class="imp-done-stats">
                <div class="cell ok">
                    <div class="n">{{ doneStats.imported }}</div>
                    <div class="l">成功导入</div>
                </div>
                <div class="cell warn">
                    <div class="n">0</div>
                    <div class="l">已覆盖</div>
                </div>
                <div class="cell err">
                    <div class="n">{{ doneStats.skipped }}</div>
                    <div class="l">跳过异常</div>
                </div>
            </div>

            <div v-if="doneStats.errors?.length" class="imp-done-errors">
                <div class="t">部分行导入失败：</div>
                <ul>
                    <li v-for="(err, i) in (doneStats.errors || [])" :key="i">
                        第 {{ err.row }} 行：{{ err.msg }}
                    </li>
                </ul>
            </div>

            <div class="imp-done-acts">
                <el-button @click="resetToStep1">
                    继续导入
                </el-button>
            </div>
        </div>

        <template #footer>
            <!-- Step 1 footer -->
            <div v-if="step === 1" class="modal-foot">
                <el-button @click="handleClose">取消</el-button>
                <el-button
                    type="primary"
                    :loading="loading"
                    :disabled="previewFn != null ? !uploadFile : false"
                    @click="goStep2"
                >
                    {{ uploadFile ? '下一步 · 校验数据' : (previewFn != null ? '请先选择文件' : '使用示例数据继续') }}
                </el-button>
            </div>
            <!-- Step 2 footer -->
            <div v-else-if="step === 2" class="modal-foot">
                <el-button @click="step = 1">上一步</el-button>
                <div style="flex: 1" />
                <el-button @click="handleClose">取消</el-button>
                <el-button
                    type="primary"
                    :loading="loading"
                    :disabled="okCount === 0"
                    @click="goStep3"
                >
                    确认导入 {{ okCount }} 条
                </el-button>
            </div>
            <!-- Step 3 footer -->
            <div v-else class="modal-foot">
                <el-button @click="handleClose">关闭</el-button>
                <el-button type="primary" @click="handleDone">
                    返回列表
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { computed, ref, watch } from 'vue'

import { getToken } from '@/utils/auth'

export interface PreviewColumn {
    key: string
    label: string
}

export interface PreviewRow {
    row: number
    ok: boolean
    err?: string
    [key: string]: any
}

export interface PreviewResult {
    list: PreviewRow[]
    summary?: { total: number; ok: number; err: number }
    file_path?: string
}

export interface ConfirmResult {
    imported: number
    skipped: number
    errors?: { row: number; msg: string }[]
    total?: number
}

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        title?: string
        /** 模板下载 URL；若为相对路径会自动追加 token 查询参数。 */
        templateUrl?: string
        /** 上传文件 → 服务端校验函数。 */
        previewFn?: (file: File) => Promise<{ data: PreviewResult }>
        /** 确认导入函数。filePath 为 previewFn 返回的服务端临时路径。 */
        confirmFn?: (filePath: string) => Promise<{ data: ConfirmResult }>
        /** 预览表格列。未提供时使用默认（用户导入示例）。 */
        previewColumns?: PreviewColumn[]
        /** 注意事项条目（HTML 文本）。 */
        notes?: string[]
    }>(),
    {
        title: '批量导入',
        templateUrl: '',
        previewFn: undefined,
        confirmFn: undefined,
        previewColumns: () => [],
        notes: () => [],
    }
)

const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    success: []
}>()

const step = ref(1)
const loading = ref(false)
const uploadFile = ref<File | null>(null)
const fileMeta = ref<{ name: string; size: string } | null>(null)
const dragOver = ref(false)
const fileInputRef = ref<HTMLInputElement>()

// Server side temp path returned from previewFn (for confirmFn)
const serverFilePath = ref<string>('')

// Preview state
const previewList = ref<PreviewRow[]>([])
const previewSummary = ref<{ total: number; ok: number; err: number }>({ total: 0, ok: 0, err: 0 })

// Done stats
const doneStats = ref<ConfirmResult & { total: number }>({ total: 0, imported: 0, skipped: 0, errors: [] })

const steps = [
    { n: 1, t: '上传文件', d: '下载模板并上传文件' },
    { n: 2, t: '预览与校验', d: '核对数据、处理异常' },
    { n: 3, t: '导入完成', d: '查看导入结果' },
]

const DEFAULT_COLUMNS: PreviewColumn[] = [
    { key: 'name', label: '姓名' },
    { key: 'acc', label: '登录账号' },
    { key: 'phone', label: '手机号' },
    { key: 'email', label: '邮箱' },
    { key: 'dept', label: '所属部门' },
    { key: 'role', label: '角色' },
]

const resolvedColumns = computed(() =>
    props.previewColumns.length ? props.previewColumns : DEFAULT_COLUMNS
)

const okCount = computed(() => previewList.value.filter((p) => p.ok).length)
const errCount = computed(() => previewList.value.length - okCount.value)

watch(
    () => props.modelValue,
    (val) => {
        if (val) {
            resetToStep1()
        }
    }
)

function resetToStep1() {
    step.value = 1
    uploadFile.value = null
    fileMeta.value = null
    dragOver.value = false
    serverFilePath.value = ''
    previewList.value = []
    previewSummary.value = { total: 0, ok: 0, err: 0 }
    doneStats.value = { total: 0, imported: 0, skipped: 0, errors: [] }
    if (fileInputRef.value) fileInputRef.value.value = ''
}

function clearFile() {
    uploadFile.value = null
    fileMeta.value = null
    if (fileInputRef.value) fileInputRef.value.value = ''
}

function attachFile(f: File) {
    uploadFile.value = f
    fileMeta.value = {
        name: f.name,
        size: (f.size / 1024).toFixed(1) + ' KB',
    }
}

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement
    const f = input.files?.[0]
    if (f) attachFile(f)
}

function onDrop(e: DragEvent) {
    dragOver.value = false
    const f = e.dataTransfer?.files?.[0]
    if (f) attachFile(f)
}

function downloadTemplate() {
    if (!props.templateUrl) return
    // 相对 URL 自动加 token query（与后端 admin_auth 中间件兼容）
    let url = props.templateUrl
    if (url.startsWith('/')) {
        const token = getToken()
        if (token) url += (url.includes('?') ? '&' : '?') + 'token=' + encodeURIComponent(token)
    }
    window.open(url, '_blank')
}

async function goStep2() {
    if (props.previewFn) {
        if (!uploadFile.value) {
            ElMessage.warning('请先选择文件')
            return
        }
        loading.value = true
        try {
            const res = await props.previewFn(uploadFile.value)
            const payload = (res as any)?.data ?? res
            previewList.value = payload?.list ?? []
            previewSummary.value = payload?.summary ?? {
                total: previewList.value.length,
                ok: previewList.value.filter((r: PreviewRow) => r.ok).length,
                err: previewList.value.filter((r: PreviewRow) => !r.ok).length,
            }
            serverFilePath.value = payload?.file_path ?? ''
            step.value = 2
        } catch (e) {
            console.error('预览失败:', e)
        } finally {
            loading.value = false
        }
        return
    }

    ElMessage.error('当前业务未配置导入预览接口')
}

async function goStep3() {
    if (props.confirmFn) {
        loading.value = true
        try {
            const res = await props.confirmFn(serverFilePath.value)
            const payload = (res as any)?.data ?? res
            doneStats.value = {
                total: payload?.total ?? previewList.value.length,
                imported: payload?.imported ?? 0,
                skipped: payload?.skipped ?? 0,
                errors: payload?.errors ?? [],
            }
            step.value = 3
        } catch (e) {
            console.error('导入失败:', e)
        } finally {
            loading.value = false
        }
        return
    }

    ElMessage.error('当前业务未配置确认导入接口')
}

function handleClose() {
    emit('update:modelValue', false)
}

function handleDone() {
    emit('success')
    emit('update:modelValue', false)
}
</script>

<style lang="scss" scoped>
/* Modal head */
.modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}

.modal-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 600;
    color: var(--ink-900);

    &::before {
        content: "";
        width: 3px;
        height: 14px;
        background: var(--brand-500);
        border-radius: 2px;
    }

    .sub {
        font-size: 12px;
        font-weight: 400;
        color: var(--ink-400);
        margin-left: 4px;
    }
}

.modal-close {
    width: 26px;
    height: 26px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ink-400);
    background: transparent;
    border: none;
    cursor: pointer;

    &:hover {
        background: var(--ink-100);
        color: var(--ink-700);
    }
}

/* Modal foot */
.modal-foot {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
}

/* Steps */
.imp-steps {
    display: flex;
    align-items: center;
    padding: 6px 8px 18px;
    border-bottom: 1px solid var(--ink-100);
    margin-bottom: 18px;
}

.imp-step {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}

.imp-step-n {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 12px;
    background: var(--ink-100);
    color: var(--ink-400);
    transition: all 0.2s;
}

.imp-step.on .imp-step-n {
    background: var(--brand-500);
    color: #fff;
    box-shadow: 0 0 0 4px var(--brand-50);
}

.imp-step.done .imp-step-n {
    background: var(--brand-50);
    color: var(--brand-500);
}

.imp-step-txt {
    .t {
        font-size: 13px;
        font-weight: 500;
        color: var(--ink-500);
        line-height: 1.3;
    }

    .d {
        font-size: 11px;
        color: var(--ink-400);
        margin-top: 2px;
    }
}

.imp-step.on .imp-step-txt .t {
    color: var(--ink-900);
}

.imp-step.done .imp-step-txt .t {
    color: var(--ink-700);
}

.imp-step-line {
    flex: 1;
    height: 2px;
    margin: 0 14px;
    background: var(--ink-100);
    border-radius: 1px;
    position: relative;

    &.done {
        background: var(--brand-200, var(--brand-100));

        &::after {
            content: "";
            position: absolute;
            inset: 0;
            background: var(--brand-500);
            border-radius: 1px;
        }
    }
}

.imp-body {
    padding: 0 2px 8px;
}

/* Template download card */
.imp-tpl {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    background: linear-gradient(135deg, var(--brand-50) 0%, #f3edff 100%);
    border: 1px solid var(--brand-100);
    border-radius: 6px;
    margin-bottom: 18px;
}

.imp-tpl-ic {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    background: #fff;
    color: var(--brand-500);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(79, 107, 255, 0.15);
    flex-shrink: 0;
}

.imp-tpl-txt {
    flex: 1;
    min-width: 0;

    .t {
        font-size: 13px;
        font-weight: 600;
        color: var(--ink-900);
        margin-bottom: 2px;
    }

    .d {
        font-size: 12px;
        color: var(--ink-500);
    }
}

.imp-tpl-btns {
    display: flex;
    gap: 6px;
    flex-shrink: 0;
}

.imp-sec-label {
    font-size: 12.5px;
    color: var(--ink-600);
    font-weight: 500;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;

    &::before {
        content: "";
        width: 2px;
        height: 10px;
        background: var(--brand-500);
        border-radius: 1px;
    }
}

/* Drag & drop upload area */
.imp-drop {
    display: block;
    border: 1px dashed var(--ink-300);
    border-radius: 6px;
    background: var(--ink-50);
    padding: 32px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.15s;

    &:hover,
    &.over {
        border-color: var(--brand-500);
        background: var(--brand-50);
    }

    &.has-file {
        background: #fff;
        border-style: solid;
        border-color: var(--ink-200);
        padding: 14px 16px;
        text-align: left;
    }
}

.imp-drop-ic {
    color: var(--ink-400);
    margin-bottom: 10px;
    display: flex;
    justify-content: center;
}

.imp-drop:hover .imp-drop-ic,
.imp-drop.over .imp-drop-ic {
    color: var(--brand-500);
}

.imp-drop-t {
    font-size: 13px;
    color: var(--ink-700);

    .lk {
        color: var(--brand-500);
        font-weight: 500;
    }
}

.imp-drop-d {
    font-size: 11.5px;
    color: var(--ink-400);
    margin-top: 6px;
}

/* Uploaded file display */
.imp-file {
    display: flex;
    align-items: center;
    gap: 12px;
}

.imp-file-ic {
    width: 40px;
    height: 40px;
    border-radius: 4px;
    background: var(--teal-50);
    color: var(--teal-500);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.imp-file-txt {
    flex: 1;
    min-width: 0;

    .nm {
        font-size: 13px;
        font-weight: 500;
        color: var(--ink-900);
    }

    .mt {
        font-size: 11.5px;
        color: var(--ink-400);
        margin-top: 2px;
    }
}

.imp-file-x {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    background: var(--ink-50);
    color: var(--ink-500);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;

    &:hover {
        background: var(--rose-50);
        color: var(--rose-500);
    }
}

/* Notes */
.imp-note {
    margin-top: 18px;
    padding: 12px 14px;
    background: var(--amber-50);
    border-radius: 4px;
    border-left: 3px solid var(--amber-500);

    .t {
        font-size: 12.5px;
        font-weight: 600;
        color: var(--amber-500);
        margin-bottom: 6px;
    }

    ul {
        margin: 0;
        padding-left: 18px;
        font-size: 12px;
        color: var(--ink-700);
        line-height: 1.8;

        li :deep(em) {
            font-style: normal;
        }
    }

    .req {
        color: var(--danger);
        font-style: normal;
        margin: 0 2px;
    }
}

/* Validation summary */
.imp-sum {
    display: grid;
    grid-template-columns: 2.5fr 1fr 1fr 1fr;
    gap: 1px;
    background: var(--ink-100);
    border: 1px solid var(--ink-100);
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 14px;
}

.imp-sum-cell {
    background: #fff;
    padding: 10px 14px;

    .l {
        font-size: 11.5px;
        color: var(--ink-400);
    }

    .v {
        font-size: 13px;
        color: var(--ink-800);
        font-weight: 500;
        margin-top: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;

        &.num {
            font-size: 20px;
            font-weight: 700;
            line-height: 1.2;
        }
    }

    &.ok .v {
        color: var(--teal-500);
    }

    &.err .v {
        color: var(--rose-500);
    }
}

/* Preview table */
.imp-tbl-wrap {
    border: 1px solid var(--ink-100);
    border-radius: 4px;
    overflow: hidden;
}

.imp-tbl-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 14px;
    background: var(--ink-50);
    border-bottom: 1px solid var(--ink-100);
    font-size: 12.5px;

    .l {
        font-weight: 500;
        color: var(--ink-700);
    }

    .r {
        display: flex;
        gap: 14px;
        font-size: 11.5px;
        color: var(--ink-500);

        i {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: 1px;
        }
    }

    .legend-ok i {
        background: var(--teal-500);
    }

    .legend-err i {
        background: var(--rose-500);
    }
}

.imp-tbl-scroll {
    max-height: 260px;
    overflow: auto;
}

.imp-tbl {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;

    th {
        position: sticky;
        top: 0;
        z-index: 1;
        text-align: left;
        font-weight: 500;
        color: var(--ink-500);
        padding: 8px 12px;
        background: #fff;
        border-bottom: 1px solid var(--ink-100);
        font-size: 11.5px;
        white-space: nowrap;
    }

    td {
        padding: 8px 12px;
        color: var(--ink-700);
        border-bottom: 1px solid var(--ink-100);
        white-space: nowrap;
    }

    tr:last-child td {
        border-bottom: 0;
    }

    tr.has-err td {
        background: #fff9fa;
    }

    td.rn {
        color: var(--ink-400);
    }

    td.err-cell {
        color: var(--rose-500);
        text-decoration: underline wavy var(--rose-500);
        text-underline-offset: 2px;
    }
}

.dot-ok,
.dot-err {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.dot-ok {
    background: var(--teal-500);
}

.dot-err {
    background: var(--rose-500);
}

.imp-err-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: #fff9fa;
    border-top: 1px solid var(--ink-100);
    font-size: 12px;
    color: var(--rose-500);

    b {
        font-weight: 700;
        margin: 0 2px;
    }
}

/* Complete page */
.imp-done {
    text-align: center;
    padding: 28px 20px 12px;
}

.imp-done-ic {
    width: 66px;
    height: 66px;
    margin: 0 auto 14px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--teal-500), #0ea5e9);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 24px rgba(20, 184, 166, 0.3);
}

.imp-done-t {
    font-size: 18px;
    font-weight: 600;
    color: var(--ink-900);
    margin-bottom: 4px;
}

.imp-done-d {
    font-size: 12.5px;
    color: var(--ink-500);
    margin-bottom: 20px;
}

.imp-done-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    max-width: 440px;
    margin: 0 auto 20px;

    .cell {
        padding: 14px 10px;
        border-radius: 6px;
        background: var(--ink-50);
        border: 1px solid var(--ink-100);

        .n {
            font-size: 26px;
            font-weight: 700;
            color: var(--ink-900);
            line-height: 1.1;
        }

        .l {
            font-size: 12px;
            color: var(--ink-500);
            margin-top: 4px;
        }

        &.ok .n {
            color: var(--teal-500);
        }

        &.warn .n {
            color: var(--amber-500);
        }

        &.err .n {
            color: var(--rose-500);
        }
    }
}

.imp-done-errors {
    text-align: left;
    max-width: 600px;
    margin: 0 auto 20px;
    padding: 12px 14px;
    background: #fff9fa;
    border-radius: 6px;
    border: 1px solid var(--rose-500);

    .t {
        font-size: 12.5px;
        font-weight: 600;
        color: var(--rose-500);
        margin-bottom: 6px;
    }

    ul {
        margin: 0;
        padding-left: 18px;
        font-size: 12px;
        color: var(--ink-700);
        line-height: 1.8;
    }
}

.imp-done-acts {
    display: flex;
    gap: 8px;
    justify-content: center;
}
</style>

<style lang="scss">
/* Unscoped overrides for el-dialog */
.import-dialog {
    .el-dialog__header {
        padding: 14px 20px;
        margin: 0;
        border-bottom: 1px solid var(--ink-100);
    }

    .el-dialog__body {
        padding: 20px 24px 4px;
        max-height: calc(100vh - 220px);
        overflow-y: auto;
    }

    .el-dialog__footer {
        padding: 14px 20px;
        border-top: 1px solid var(--ink-100);
        background: var(--ink-50);
    }
}
</style>
