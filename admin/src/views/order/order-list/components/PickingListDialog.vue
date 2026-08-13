<template>
    <el-dialog
        v-model="visible"
        title="配货单"
        width="900px"
        :close-on-click-modal="false"
        top="6vh"
    >
        <div v-loading="loading" class="picking-dialog-body">
            <div v-if="!loading && !orders.length" class="empty">暂无可打印的订单</div>

            <!-- 打印区域（DOM 需要存在以便 print-js 抓取） -->
            <div id="picking-list-root" class="picking-print-root">
                <div v-for="o in orders" :key="o.id" class="picking-page">
                    <div class="picking-head">
                        <div class="title">配货单</div>
                        <div class="meta">
                            <span
                                >订单号：<b class="num">{{ o.order_no }}</b></span
                            >
                            <span>下单时间：{{ o.created_at }}</span>
                            <span>支付时间：{{ o.paid_at || '—' }}</span>
                        </div>
                    </div>

                    <div class="picking-receiver">
                        <div class="row">
                            <span class="lb">收货人</span
                            ><span class="vv">{{ getReceiver(o).name }}</span>
                        </div>
                        <div class="row">
                            <span class="lb">联系电话</span
                            ><span class="vv">{{ getReceiver(o).phone }}</span>
                        </div>
                        <div class="row">
                            <span class="lb">收货地址</span
                            ><span class="vv">{{ getReceiver(o).address }}</span>
                        </div>
                        <div v-if="o.buyer_remark" class="row">
                            <span class="lb">买家备注</span
                            ><span class="vv warn">{{ o.buyer_remark }}</span>
                        </div>
                    </div>

                    <table class="picking-table">
                        <thead>
                            <tr>
                                <th style="width: 36px">#</th>
                                <th>商品</th>
                                <th style="width: 120px">规格</th>
                                <th style="width: 120px">SKU 编码</th>
                                <th style="width: 60px">数量</th>
                                <th style="width: 90px">小计</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(it, i) in o.items || []" :key="it.id">
                                <td>{{ i + 1 }}</td>
                                <td>{{ it.goods_name || it.spu_name || '—' }}</td>
                                <td>{{ it.spec_text || it.sku_name || '—' }}</td>
                                <td class="num">{{ it.sku_code || it.sku_id || '—' }}</td>
                                <td class="num center">×{{ it.quantity }}</td>
                                <td class="num right">
                                    ¥{{
                                        formatPrice(
                                            it.total_amount ??
                                                it.total ??
                                                Number(it.price) * Number(it.quantity)
                                        )
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="picking-foot">
                        <div class="totals">
                            <span
                                >共 <b>{{ countItems(o) }}</b> 件</span
                            >
                            <span class="ml-4"
                                >实付：<b class="amount num"
                                    >¥{{ formatPrice(o.pay_amount) }}</b
                                ></span
                            >
                        </div>
                        <div v-if="o.seller_remark" class="seller-remark">
                            卖家备注：{{ o.seller_remark }}
                        </div>
                        <div class="print-time">打印时间：{{ printTime }}</div>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <el-button @click="visible = false">关闭</el-button>
            <el-button type="primary" :disabled="loading || !orders.length" @click="handlePrint">
                打印（{{ orders.length }}）
            </el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import printJS from 'print-js'
import { computed, ref, watch } from 'vue'

import { orderApi } from '@/api/order'

const props = defineProps<{
    modelValue: boolean
    orderIds: number[]
}>()
const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v)
})

const loading = ref(false)
const orders = ref<Record<string, any>[]>([])
const printTime = ref('')

watch(visible, async (v) => {
    if (v) {
        printTime.value = new Date().toLocaleString('zh-CN', { hour12: false })
        await loadOrders()
    } else {
        orders.value = []
    }
})

async function loadOrders() {
    if (!props.orderIds.length) {
        orders.value = []
        return
    }
    loading.value = true
    try {
        const results = await Promise.all(props.orderIds.map((id) => orderApi.getOrderDetail(id)))
        orders.value = results.map((res) => res.data).filter(Boolean)
    } catch (e) {
        console.error('加载订单详情失败', e)
        ElMessage.error('加载订单详情失败')
    } finally {
        loading.value = false
    }
}

function getReceiver(o: Record<string, any>) {
    const s = o.address_snapshot || {}
    const region = [s.province, s.city, s.district].filter(Boolean).join(' ')
    return {
        name: s.name || o.receiver || '—',
        phone: s.phone || o.phone || '—',
        address: `${region}${s.detail || o.address || ''}`.trim() || '—'
    }
}

function countItems(o: Record<string, any>): number {
    return (o.items || []).reduce((s: number, it: any) => s + Number(it.quantity || 0), 0)
}

function formatPrice(v: any): string {
    const n = Number(v)
    return Number.isFinite(n) ? n.toFixed(2) : '0.00'
}

const handlePrint = () => {
    if (!orders.value.length) return
    printJS({
        printable: 'picking-list-root',
        type: 'html',
        documentTitle: `配货单_${new Date().toISOString().slice(0, 10)}`,
        targetStyles: ['*'],
        style: `
      @page { size: A4; margin: 12mm; }
      .picking-page { page-break-after: always; font-family: -apple-system, "PingFang SC", "Microsoft YaHei", sans-serif; color:#000; font-size:12px; }
      .picking-page:last-child { page-break-after: auto; }
      .picking-head { border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 10px; }
      .picking-head .title { font-size: 20px; font-weight: 700; text-align: center; margin-bottom: 4px; }
      .picking-head .meta { display: flex; justify-content: space-between; font-size: 11px; }
      .picking-receiver { border: 1px solid #999; padding: 8px 10px; margin-bottom: 8px; }
      .picking-receiver .row { display: flex; padding: 2px 0; }
      .picking-receiver .lb { width: 70px; color: #555; }
      .picking-receiver .vv { flex: 1; }
      .picking-receiver .vv.warn { color: #c00; font-weight: 600; }
      table.picking-table { width: 100%; border-collapse: collapse; }
      table.picking-table th, table.picking-table td { border: 1px solid #999; padding: 5px 6px; font-size: 11.5px; }
      table.picking-table th { background: #f0f0f0; text-align: left; }
      table.picking-table .center { text-align: center; }
      table.picking-table .right { text-align: right; }
      .picking-foot { margin-top: 8px; font-size: 11.5px; }
      .picking-foot .totals { display: flex; justify-content: flex-end; }
      .picking-foot .amount { color: #c00; }
      .picking-foot .seller-remark { margin-top: 4px; color: #555; }
      .picking-foot .print-time { margin-top: 4px; text-align: right; color: #888; font-size: 11px; }
    `
    })
}
</script>

<style lang="scss" scoped>
.picking-dialog-body {
    max-height: 70vh;
    overflow-y: auto;
    .empty {
        text-align: center;
        padding: 40px 0;
        color: var(--el-text-color-secondary);
    }
}
.picking-print-root {
    .picking-page {
        border: 1px solid var(--el-border-color-light);
        padding: 14px 16px;
        margin-bottom: 12px;
        border-radius: 6px;
        background: #fff;
        color: var(--el-text-color-primary);
    }
    .picking-head {
        border-bottom: 2px solid var(--el-border-color);
        padding-bottom: 6px;
        margin-bottom: 10px;
        .title {
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 4px;
        }
        .meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--el-text-color-regular);
        }
    }
    .picking-receiver {
        border: 1px solid var(--el-border-color-light);
        padding: 8px 10px;
        margin-bottom: 8px;
        .row {
            display: flex;
            padding: 2px 0;
            font-size: 12px;
        }
        .lb {
            width: 70px;
            color: var(--el-text-color-secondary);
        }
        .vv {
            flex: 1;
        }
        .vv.warn {
            color: var(--el-color-danger);
            font-weight: 600;
        }
    }
    table.picking-table {
        width: 100%;
        border-collapse: collapse;
        th,
        td {
            border: 1px solid var(--el-border-color-light);
            padding: 5px 6px;
            font-size: 12px;
        }
        th {
            background: var(--el-fill-color-light);
            text-align: left;
        }
        .center {
            text-align: center;
        }
        .right {
            text-align: right;
        }
    }
    .picking-foot {
        margin-top: 8px;
        font-size: 12px;
        .totals {
            display: flex;
            justify-content: flex-end;
        }
        .amount {
            color: var(--el-color-danger);
        }
        .seller-remark {
            margin-top: 4px;
            color: var(--el-text-color-regular);
        }
        .print-time {
            margin-top: 4px;
            text-align: right;
            color: var(--el-text-color-secondary);
            font-size: 11px;
        }
    }
}
.ml-4 {
    margin-left: 16px;
}
.num {
    font-variant-numeric: tabular-nums;
}
</style>
