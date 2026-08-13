<template>
    <el-dialog
        v-model="visible"
        title="订单改价"
        width="860px"
        :close-on-click-modal="false"
        top="6vh"
    >
        <div v-loading="loading" class="price-adjust-body">
            <el-table :data="rows" border size="small">
                <el-table-column label="商品" min-width="220">
                    <template #default="{ row }">
                        <div class="goods-cell">
                            <el-image
                                v-if="row.goods_image"
                                :src="row.goods_image"
                                class="goods-cell__img"
                                fit="cover"
                            />
                            <div v-else class="goods-cell__img goods-cell__img--empty">
                                <i class="i-lucide:image" />
                            </div>
                            <div class="goods-cell__meta">
                                <div class="goods-cell__name">{{ row.goods_name }}</div>
                                <div v-if="row.spec_text" class="goods-cell__spec">
                                    {{ row.spec_text }}
                                </div>
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="原单价" width="100" align="right">
                    <template #default="{ row }">
                        <span class="num">¥{{ formatFen(row.orig_price_fen) }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="数量" width="70" align="center">
                    <template #default="{ row }">
                        <span class="num">×{{ row.quantity }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="新单价(元)" width="150">
                    <template #default="{ row }">
                        <el-input-number
                            v-model="row.new_price"
                            :min="0.01"
                            :precision="2"
                            controls-position="right"
                            size="small"
                            style="width: 100%"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="新小计" width="110" align="right">
                    <template #default="{ row }">
                        <span class="num">¥{{ formatFen(rowSubtotalFen(row)) }}</span>
                    </template>
                </el-table-column>
            </el-table>

            <el-form
                ref="formRef"
                :model="form"
                :rules="rules"
                label-width="90px"
                class="adjust-form"
            >
                <el-form-item label="运费(元)" prop="freight_amount">
                    <el-input-number
                        v-model="form.freight_amount"
                        :min="0"
                        :precision="2"
                        controls-position="right"
                        style="width: 200px"
                    />
                </el-form-item>
                <el-form-item label="优惠(元)" prop="discount_amount">
                    <el-input-number
                        v-model="form.discount_amount"
                        :min="0"
                        :precision="2"
                        controls-position="right"
                        style="width: 200px"
                    />
                </el-form-item>
                <el-form-item label="改价原因" prop="remark">
                    <el-input
                        v-model="form.remark"
                        type="textarea"
                        :rows="2"
                        maxlength="200"
                        show-word-limit
                        placeholder="请填写改价原因（必填）"
                    />
                </el-form-item>
            </el-form>

            <!-- 金额对比预览 -->
            <div class="adjust-preview">
                <div
                    v-for="p in previewItems"
                    :key="p.label"
                    class="adjust-preview__item"
                    :class="{ 'is-pay': p.highlight }"
                >
                    <div class="lb">{{ p.label }}</div>
                    <div class="vv num">
                        <span class="before">¥{{ formatFen(p.before) }}</span>
                        <span class="arrow">→</span>
                        <span class="after" :class="p.highlight ? payTrendClass : ''"
                            >¥{{ formatFen(p.after) }}</span
                        >
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <el-button @click="visible = false">取消</el-button>
            <el-button
                type="primary"
                :loading="confirmLoading"
                :disabled="loading || !rows.length || !hasChanges"
                @click="handleConfirm"
            >
                确认改价
            </el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import type { FormInstance } from 'element-plus'
import { ElMessage } from 'element-plus'
import { computed, reactive, ref, watch } from 'vue'

import { orderApi } from '@/api/order'
import type { OrderInfo } from '@/types/api'
import { fenToYuan, formatFen, yuanToFen } from '@/utils/money'

interface AdjustRow {
    item_id: number
    goods_name: string
    goods_image: string
    spec_text: string
    quantity: number
    /** 原单价（分） */
    orig_price_fen: number
    /** 新单价（元，输入框绑定值） */
    new_price: number
}

const props = defineProps<{
    modelValue: boolean
    orderId: number
}>()
const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
    (e: 'success'): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v)
})

const loading = ref(false)
const confirmLoading = ref(false)
const order = ref<OrderInfo | null>(null)
const rows = ref<AdjustRow[]>([])
const formRef = ref<FormInstance>()
const form = reactive({
    freight_amount: 0,
    discount_amount: 0,
    remark: ''
})

watch(visible, (v) => {
    if (v) {
        load()
    } else {
        reset()
    }
})

async function load() {
    if (!props.orderId) return
    loading.value = true
    try {
        const res = await orderApi.getOrderDetail(props.orderId)
        const detail = res.data
        if (!detail) {
            ElMessage.error('订单不存在')
            visible.value = false
            return
        }
        // 防御：仅待付款订单可改价
        if (detail.status !== 'pending') {
            ElMessage.warning('仅待付款订单支持改价')
            visible.value = false
            return
        }
        order.value = detail
        rows.value = (detail.items || []).map((it: Record<string, any>) => ({
            item_id: it.id,
            goods_name: it.goods_name || it.spu_name || '—',
            goods_image: it.goods_image || it.image || '',
            spec_text: it.spec_text || it.sku_name || '',
            quantity: Number(it.quantity || 0),
            orig_price_fen: yuanToFen(it.price),
            new_price: fenToYuan(yuanToFen(it.price))
        }))
        form.freight_amount = fenToYuan(yuanToFen(detail.freight_amount))
        form.discount_amount = fenToYuan(yuanToFen(detail.discount_amount))
        form.remark = ''
    } catch (e) {
        console.error('加载订单详情失败:', e)
        visible.value = false
    } finally {
        loading.value = false
    }
}

function reset() {
    order.value = null
    rows.value = []
    form.freight_amount = 0
    form.discount_amount = 0
    form.remark = ''
    formRef.value?.clearValidate()
}

// ─── 分整数运算 ────────────────────────────────────────────────
const rowSubtotalFen = (row: Pick<AdjustRow, 'new_price' | 'quantity'> | Record<string, any>) =>
    yuanToFen(row.new_price) * Number(row.quantity || 0)

const newGoodsFen = computed(() => rows.value.reduce((s, r) => s + rowSubtotalFen(r), 0))
const newFreightFen = computed(() => yuanToFen(form.freight_amount))
const newDiscountFen = computed(() => yuanToFen(form.discount_amount))
const newPayFen = computed(() => newGoodsFen.value + newFreightFen.value - newDiscountFen.value)

const origGoodsFen = computed(() => yuanToFen(order.value?.goods_amount))
const origFreightFen = computed(() => yuanToFen(order.value?.freight_amount))
const origDiscountFen = computed(() => yuanToFen(order.value?.discount_amount))
const origPayFen = computed(() => yuanToFen(order.value?.pay_amount))

const hasChanges = computed(
    () =>
        rows.value.some((r) => yuanToFen(r.new_price) !== r.orig_price_fen) ||
        newFreightFen.value !== origFreightFen.value ||
        newDiscountFen.value !== origDiscountFen.value
)

const previewItems = computed(() => [
    { label: '商品金额', before: origGoodsFen.value, after: newGoodsFen.value, highlight: false },
    { label: '运费', before: origFreightFen.value, after: newFreightFen.value, highlight: false },
    { label: '优惠', before: origDiscountFen.value, after: newDiscountFen.value, highlight: false },
    { label: '实付金额', before: origPayFen.value, after: newPayFen.value, highlight: true }
])

const payTrendClass = computed(() => {
    if (newPayFen.value > origPayFen.value) return 'trend-up'
    if (newPayFen.value < origPayFen.value) return 'trend-down'
    return ''
})

const rules = {
    discount_amount: [
        {
            validator: (_rule: unknown, value: number, callback: (e?: Error) => void) => {
                if (yuanToFen(value) > newGoodsFen.value) {
                    callback(new Error('优惠金额不能超过新商品合计'))
                } else {
                    callback()
                }
            },
            trigger: 'change'
        }
    ],
    remark: [{ required: true, message: '请填写改价原因', trigger: 'blur' }]
}

const handleConfirm = async () => {
    if (!formRef.value) return
    const valid = await formRef.value.validate().catch(() => false)
    if (!valid) return
    confirmLoading.value = true
    try {
        await orderApi.priceAdjust(props.orderId, {
            items: rows.value.map((r) => ({
                item_id: r.item_id,
                price: fenToYuan(yuanToFen(r.new_price))
            })),
            freight_amount: fenToYuan(newFreightFen.value),
            discount_amount: fenToYuan(newDiscountFen.value),
            remark: form.remark
        })
        ElMessage.success('改价成功')
        emit('success')
        visible.value = false
    } catch (e) {
        console.error('改价失败:', e)
    } finally {
        confirmLoading.value = false
    }
}
</script>

<style lang="scss" scoped>
.price-adjust-body {
    .goods-cell {
        display: flex;
        align-items: flex-start;
        gap: 8px;

        &__img {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            flex-shrink: 0;
            background: var(--el-fill-color-light);

            &--empty {
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--el-text-color-secondary);
            }
        }

        &__meta {
            min-width: 0;
        }

        &__name {
            font-size: 12.5px;
            color: var(--el-text-color-primary);
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        &__spec {
            font-size: 11px;
            color: var(--el-text-color-secondary);
            margin-top: 2px;
        }
    }

    .adjust-form {
        margin-top: 16px;
    }

    .adjust-preview {
        display: flex;
        gap: 12px;
        margin-top: 4px;
        padding: 12px;
        border-radius: 6px;
        background: var(--el-fill-color-light);

        &__item {
            flex: 1;
            min-width: 0;

            .lb {
                font-size: 12px;
                color: var(--el-text-color-secondary);
                margin-bottom: 4px;
            }

            .vv {
                font-size: 13px;
                color: var(--el-text-color-primary);

                .before {
                    color: var(--el-text-color-secondary);
                }

                .arrow {
                    margin: 0 6px;
                    color: var(--el-text-color-placeholder);
                }
            }

            &.is-pay .vv .after {
                font-size: 15px;
                font-weight: 600;
            }
        }
    }

    .trend-up {
        color: var(--el-color-danger);
    }

    .trend-down {
        color: var(--el-color-success);
    }

    .num {
        font-variant-numeric: tabular-nums;
    }
}
</style>
