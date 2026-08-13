<template>
    <el-dialog
        v-model="visible"
        :title="dialogTitle"
        width="520px"
        :close-on-click-modal="false"
        @close="resetForm"
    >
        <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
            <el-form-item label="配送方式" prop="delivery_mode">
                <el-radio-group v-model="form.delivery_mode">
                    <el-radio value="express">物流配送</el-radio>
                    <el-radio value="none">无需物流</el-radio>
                </el-radio-group>
            </el-form-item>

            <template v-if="form.delivery_mode === 'express'">
                <el-form-item label="发货方式" prop="ship_mode">
                    <el-radio-group v-model="form.ship_mode">
                        <el-radio value="manual">手动填写</el-radio>
                        <el-radio value="waybill">电子面单</el-radio>
                    </el-radio-group>
                </el-form-item>

                <template v-if="form.ship_mode === 'manual'">
                    <el-form-item label="快递公司" prop="express_company">
                        <el-select
                            v-model="form.express_company"
                            filterable
                            placeholder="请选择快递公司"
                            style="width: 100%"
                            :loading="expressLoading"
                        >
                            <el-option
                                v-for="opt in expressOptions"
                                :key="String(opt.value)"
                                :label="opt.label"
                                :value="opt.label"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="快递单号" prop="express_no">
                        <el-input
                            v-model="form.express_no"
                            :placeholder="
                                mode === 'batch' ? '请输入快递单号（批量将统一使用）' : '请输入快递单号'
                            "
                        />
                    </el-form-item>
                </template>

                <template v-else>
                    <el-form-item label="面单模版" prop="waybill_template_id">
                        <el-select
                            v-model="form.waybill_template_id"
                            filterable
                            placeholder="请选择启用中的面单模版"
                            style="width: 100%"
                            :loading="templateLoading"
                        >
                            <el-option
                                v-for="tpl in templateOptions"
                                :key="tpl.id"
                                :label="`${tpl.name}（${tpl.express_name} / ${tpl.exp_type_name}）`"
                                :value="tpl.id"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="打印">
                        <el-checkbox v-model="printAfterShip">发货成功后立即打印面单</el-checkbox>
                    </el-form-item>
                </template>
            </template>

            <el-alert
                v-else
                type="info"
                :closable="false"
                show-icon
                title="无需物流将直接标记为已发货，不填写快递单号"
            />
        </el-form>

        <template #footer>
            <el-button @click="visible = false">取消</el-button>
            <el-button type="primary" :loading="loading" @click="handleSubmit">确认发货</el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import printJS from 'print-js'
import { computed, reactive, ref, watch } from 'vue'

import { expressCompanyApi } from '@/api/express-company'
import { orderApi } from '@/api/order'
import { waybillTemplateApi } from '@/api/waybill-template'
import type { WaybillTemplateOption } from '@/types/waybill'
import { ensureLodop, printHtmlWithLodop } from '@/utils/lodop'
import { getConfigsByGroup } from '@/api/system/config'

const props = defineProps<{
    modelValue: boolean
    mode?: 'single' | 'batch'
    orderId?: number | null
    orderIds?: number[]
    title?: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
    (e: 'success'): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v)
})

const mode = computed(() => props.mode || 'single')
const dialogTitle = computed(() => {
    if (props.title) return props.title
    if (mode.value === 'batch') return `批量发货（${(props.orderIds || []).length} 单）`
    return '订单发货'
})

const formRef = ref<FormInstance>()
const loading = ref(false)
const printAfterShip = ref(true)
const expressLoading = ref(false)
const templateLoading = ref(false)
const expressOptions = ref<Array<{ label: string; value: string | number }>>([])
const templateOptions = ref<WaybillTemplateOption[]>([])
const lodopPorts = reactive({ http: '8000', https: '8443', enabled: true })

const form = reactive({
    delivery_mode: 'express' as 'express' | 'none',
    ship_mode: 'manual' as 'manual' | 'waybill',
    express_company: '',
    express_no: '',
    waybill_template_id: undefined as number | undefined
})

const rules = computed<FormRules>(() => {
    const base: FormRules = {
        delivery_mode: [{ required: true, message: '请选择配送方式', trigger: 'change' }]
    }
    if (form.delivery_mode === 'express') {
        base.ship_mode = [{ required: true, message: '请选择发货方式', trigger: 'change' }]
        if (form.ship_mode === 'manual') {
            base.express_company = [{ required: true, message: '请选择快递公司', trigger: 'change' }]
            base.express_no = [{ required: true, message: '请输入快递单号', trigger: 'blur' }]
        } else {
            base.waybill_template_id = [
                { required: true, message: '请选择面单模版', trigger: 'change' }
            ]
        }
    }
    return base
})

async function loadExpressOptions() {
    if (expressOptions.value.length) return
    expressLoading.value = true
    try {
        const { data } = await expressCompanyApi.getOptions()
        expressOptions.value = (data || []).map((opt: any) => ({
            label: opt.name || opt.label,
            value: opt.id ?? opt.value ?? opt.code ?? opt.name
        }))
    } finally {
        expressLoading.value = false
    }
}

async function loadTemplateOptions() {
    templateLoading.value = true
    try {
        const { data } = await waybillTemplateApi.getOptions()
        templateOptions.value = data || []
        // 后端已按 is_default desc 排序；打开时预选默认模版
        if (form.waybill_template_id == null && templateOptions.value.length) {
            const def = templateOptions.value.find((t) => t.is_default === 1)
            form.waybill_template_id = (def || templateOptions.value[0]).id
        }
    } finally {
        templateLoading.value = false
    }
}

async function loadLodopConfig() {
    try {
        const res = await getConfigsByGroup('waybill')
        const configs = res.data || []
        configs.forEach((c: any) => {
            if (c.config_key === 'waybill_lodop_http_port') lodopPorts.http = c.config_value || '8000'
            if (c.config_key === 'waybill_lodop_https_port')
                lodopPorts.https = c.config_value || '8443'
            if (c.config_key === 'waybill_lodop_enabled')
                lodopPorts.enabled = String(c.config_value) !== '0'
        })
        if (lodopPorts.enabled) {
            await ensureLodop({
                httpPort: lodopPorts.http,
                httpsPort: lodopPorts.https,
                enabled: true
            })
        }
    } catch {
        // ignore
    }
}

watch(
    () => props.modelValue,
    (open) => {
        if (!open) return
        resetForm()
        loadExpressOptions()
        loadTemplateOptions()
        loadLodopConfig()
    }
)

watch(
    () => form.delivery_mode,
    (modeVal) => {
        if (modeVal === 'none') form.ship_mode = 'manual'
    }
)

function resetForm() {
    form.delivery_mode = 'express'
    form.ship_mode = 'manual'
    form.express_company = ''
    form.express_no = ''
    form.waybill_template_id = undefined
    printAfterShip.value = true
    formRef.value?.clearValidate()
}

async function printWaybillHtml(html: string) {
    if (!html) return
    const wrapped = `<div class="waybill-page">${html}</div>`
    const ok = await printHtmlWithLodop(wrapped, { title: '电子面单', preview: true })
    if (ok) return
    printJS({
        printable: `<div id="waybill-print-root">${wrapped}</div>`,
        type: 'raw-html',
        style: `
      @page { size: A6; margin: 0; }
      .waybill-page { page-break-after: always; }
    `,
        scanStyles: false
    })
}

async function handleSubmit() {
    if (!formRef.value) return
    await formRef.value.validate()
    loading.value = true
    try {
        const ids =
            mode.value === 'batch'
                ? [...(props.orderIds || [])]
                : props.orderId
                  ? [props.orderId]
                  : []
        if (!ids.length) {
            ElMessage.warning('请选择订单')
            return
        }

        const printHtmls: string[] = []
        for (const id of ids) {
            const payload: Record<string, any> = {
                order_id: id,
                delivery_mode: form.delivery_mode,
                ship_mode: form.delivery_mode === 'express' ? form.ship_mode : 'manual'
            }
            if (form.delivery_mode === 'express' && form.ship_mode === 'manual') {
                payload.express_company = form.express_company
                payload.express_no = form.express_no
            }
            if (form.delivery_mode === 'express' && form.ship_mode === 'waybill') {
                payload.waybill_template_id = form.waybill_template_id
            }
            const { data } = await orderApi.shipOrder(payload as any)
            if (data?.print_template_html) {
                printHtmls.push(data.print_template_html)
            }
        }

        ElMessage.success(mode.value === 'batch' ? `成功发货 ${ids.length} 单` : '发货成功')
        visible.value = false
        emit('success')

        if (printAfterShip.value && printHtmls.length) {
            await printWaybillHtml(printHtmls.join('<div style="page-break-after:always"></div>'))
        }
    } catch (e) {
        console.error('发货失败:', e)
    } finally {
        loading.value = false
    }
}

defineExpose({ resetForm })
</script>
