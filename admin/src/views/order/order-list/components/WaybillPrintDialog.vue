<template>
    <el-dialog
        v-model="visible"
        title="电子面单打印"
        width="720px"
        :close-on-click-modal="false"
        @close="handleClose"
    >
        <div class="waybill-print">
            <div class="summary">
                <el-tag type="success" size="large">成功 {{ successList.length }} 单</el-tag>
                <el-tag v-if="failedList.length" type="danger" size="large" class="ml-2"
                    >失败 {{ failedList.length }} 单</el-tag
                >
            </div>

            <el-table v-if="failedList.length" :data="failedList" size="small" class="mt-3" border>
                <el-table-column prop="order_id" label="订单 ID" width="120" />
                <el-table-column prop="reason" label="失败原因" />
            </el-table>

            <div v-if="successList.length" class="hint mt-3">
                已生成 {{ successList.length }} 张电子面单，点击「打印面单」可批量打印。
            </div>
        </div>

        <template #footer>
            <el-button @click="visible = false">关闭</el-button>
            <el-button type="primary" :disabled="!successList.length" @click="handlePrint">
                打印面单（{{ successList.length }}）
            </el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import printJS from 'print-js'
import { computed, watch } from 'vue'

import type { WaybillBatchResult } from '@/types/api'
import { ensureLodop, printHtmlWithLodop } from '@/utils/lodop'

const props = defineProps<{
    modelValue: boolean
    result: WaybillBatchResult | null
}>()
const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v)
})

const successList = computed(() => props.result?.success ?? [])
const failedList = computed(() => props.result?.failed ?? [])

const handlePrint = async () => {
    if (!successList.value.length) return
    const html = successList.value
        .map((w) => `<div class="waybill-page">${w.print_template_html || ''}</div>`)
        .join('')
    await ensureLodop()
    const lodopOk = await printHtmlWithLodop(html, { title: '电子面单', preview: true })
    if (lodopOk) return
    const wrapper = `<div id="waybill-print-root">${html}</div>`
    printJS({
        printable: wrapper,
        type: 'raw-html',
        style: `
      @page { size: A6; margin: 0; }
      .waybill-page { page-break-after: always; }
      .waybill-page:last-child { page-break-after: auto; }
    `,
        scanStyles: false
    })
}

const handleClose = () => {
    // 关闭时不重置 result，由父组件控制
}

// 防止旧数据残留：每次打开重置内部状态
watch(visible, (v) => {
    if (!v) {
        // nothing
    }
})
</script>

<style lang="scss" scoped>
.waybill-print {
    .summary {
        display: flex;
        align-items: center;
    }
    .hint {
        font-size: 12px;
        color: var(--el-text-color-secondary);
    }
}
</style>
