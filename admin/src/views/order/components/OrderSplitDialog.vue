<template>
    <el-dialog
        v-model="visible"
        title="订单拆单"
        width="860px"
        :close-on-click-modal="false"
        top="6vh"
    >
        <div v-loading="loading" class="order-split-body">
            <el-alert
                title="勾选要拆出到新订单的商品行，并设置拆出数量；原订单至少需保留一件商品。"
                type="info"
                :closable="false"
                class="split-tip"
            />

            <el-table :data="rows" border size="small">
                <el-table-column width="50" align="center">
                    <template #default="{ row }">
                        <el-checkbox
                            v-model="row.checked"
                            @change="(v: unknown) => onCheckChange(row, Boolean(v))"
                        />
                    </template>
                </el-table-column>
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
                <el-table-column label="单价" width="100" align="right">
                    <template #default="{ row }">
                        <span class="num">¥{{ formatFen(row.price_fen) }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="总数量" width="80" align="center">
                    <template #default="{ row }">
                        <span class="num">×{{ row.quantity }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="拆出数量" width="150">
                    <template #default="{ row }">
                        <el-input-number
                            v-model="row.split_qty"
                            :min="1"
                            :max="row.quantity"
                            :step="1"
                            step-strictly
                            :disabled="!row.checked"
                            controls-position="right"
                            size="small"
                            style="width: 100%"
                        />
                    </template>
                </el-table-column>
            </el-table>

            <!-- 父 / 子单金额预览（前端估算） -->
            <div class="split-preview">
                <div class="split-preview__col">
                    <div class="col-title">原订单（保留）</div>
                    <div class="line">
                        <span class="lb">商品金额</span
                        ><span class="num">¥{{ formatFen(parentGoodsFen) }}</span>
                    </div>
                    <div class="line">
                        <span class="lb">运费</span
                        ><span class="num">¥{{ formatFen(parentFreightFen) }}</span>
                    </div>
                    <div class="line">
                        <span class="lb">优惠</span
                        ><span class="num">-¥{{ formatFen(parentDiscountFen) }}</span>
                    </div>
                    <div class="line total">
                        <span class="lb">预计实付</span
                        ><span class="num">¥{{ formatFen(parentPayFen) }}</span>
                    </div>
                </div>
                <div class="split-preview__col">
                    <div class="col-title">新订单（拆出）</div>
                    <div class="line">
                        <span class="lb">商品金额</span
                        ><span class="num">¥{{ formatFen(childGoodsFen) }}</span>
                    </div>
                    <div class="line">
                        <span class="lb">运费</span
                        ><span class="num">¥{{ formatFen(childFreightFen) }}</span>
                    </div>
                    <div class="line">
                        <span class="lb">优惠</span
                        ><span class="num">-¥{{ formatFen(childDiscountFen) }}</span>
                    </div>
                    <div class="line total">
                        <span class="lb">预计实付</span
                        ><span class="num">¥{{ formatFen(childPayFen) }}</span>
                    </div>
                </div>
            </div>
            <div class="split-note">
                * 运费 / 优惠按商品金额比例分摊（余数归原订单），此处为前端估算，以提交结果为准。
            </div>

            <el-form label-width="60px" class="split-form">
                <el-form-item label="备注">
                    <el-input
                        v-model="remark"
                        type="textarea"
                        :rows="2"
                        maxlength="200"
                        show-word-limit
                        placeholder="选填，说明拆单原因"
                    />
                </el-form-item>
            </el-form>
        </div>

        <template #footer>
            <el-button @click="visible = false">取消</el-button>
            <el-button
                type="primary"
                :loading="confirmLoading"
                :disabled="loading || !rows.length"
                @click="handleConfirm"
            >
                确认拆单
            </el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { computed, ref, watch } from 'vue'

import { orderApi } from '@/api/order'
import type { OrderInfo } from '@/types/api'
import { formatFen, yuanToFen } from '@/utils/money'

interface SplitRow {
    item_id: number
    goods_name: string
    goods_image: string
    spec_text: string
    /** 单价（分） */
    price_fen: number
    quantity: number
    checked: boolean
    split_qty: number
}

const props = defineProps<{
    modelValue: boolean
    orderId: number
}>()
const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
    (e: 'split-success', childOrderId: number): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v)
})

const loading = ref(false)
const confirmLoading = ref(false)
const order = ref<OrderInfo | null>(null)
const rows = ref<SplitRow[]>([])
const remark = ref('')

watch(visible, (v) => {
    if (v) {
        load()
    } else {
        order.value = null
        rows.value = []
        remark.value = ''
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
        // 防御：仅已支付且未发货订单可拆单
        if (detail.status !== 'paid') {
            ElMessage.warning('仅已支付且未发货的订单支持拆单')
            visible.value = false
            return
        }
        order.value = detail
        rows.value = (detail.items || []).map((it: Record<string, any>) => ({
            item_id: it.id,
            goods_name: it.goods_name || it.spu_name || '—',
            goods_image: it.goods_image || it.image || '',
            spec_text: it.spec_text || it.sku_name || '',
            price_fen: yuanToFen(it.price),
            quantity: Number(it.quantity || 0),
            checked: false,
            split_qty: Number(it.quantity || 0)
        }))
    } catch (e) {
        console.error('加载订单详情失败:', e)
        visible.value = false
    } finally {
        loading.value = false
    }
}

// 勾选时默认拆出全量
function onCheckChange(row: SplitRow | Record<string, any>, checked: boolean) {
    if (checked) {
        row.split_qty = row.quantity
    }
}

// ─── 金额预览（分整数运算，按商品金额比例分摊，余数归父单） ──────
const freightFen = computed(() => yuanToFen(order.value?.freight_amount))
const discountFen = computed(() => yuanToFen(order.value?.discount_amount))

const totalGoodsFen = computed(() => rows.value.reduce((s, r) => s + r.price_fen * r.quantity, 0))
const childGoodsFen = computed(() =>
    rows.value.reduce((s, r) => s + (r.checked ? r.price_fen * r.split_qty : 0), 0)
)
const parentGoodsFen = computed(() => totalGoodsFen.value - childGoodsFen.value)

const childFreightFen = computed(() =>
    totalGoodsFen.value > 0
        ? Math.floor((freightFen.value * childGoodsFen.value) / totalGoodsFen.value)
        : 0
)
const parentFreightFen = computed(() => freightFen.value - childFreightFen.value)

const childDiscountFen = computed(() =>
    totalGoodsFen.value > 0
        ? Math.floor((discountFen.value * childGoodsFen.value) / totalGoodsFen.value)
        : 0
)
const parentDiscountFen = computed(() => discountFen.value - childDiscountFen.value)

const childPayFen = computed(
    () => childGoodsFen.value + childFreightFen.value - childDiscountFen.value
)
const parentPayFen = computed(
    () => parentGoodsFen.value + parentFreightFen.value - parentDiscountFen.value
)

const handleConfirm = async () => {
    const checkedRows = rows.value.filter((r) => r.checked && r.split_qty > 0)
    if (!checkedRows.length) {
        ElMessage.warning('请至少勾选 1 件要拆出的商品')
        return
    }
    // 禁止订单级全拆
    const remainQty = rows.value.reduce((s, r) => s + r.quantity - (r.checked ? r.split_qty : 0), 0)
    if (remainQty <= 0) {
        ElMessage.warning('至少保留一件商品在原订单')
        return
    }
    confirmLoading.value = true
    try {
        const res = await orderApi.splitOrder(props.orderId, {
            items: checkedRows.map((r) => ({ item_id: r.item_id, quantity: r.split_qty })),
            remark: remark.value || undefined
        })
        const result = res.data
        ElMessage.success(`拆单成功，新订单号：${result?.child_order_no || '—'}`)
        if (result?.child_order_id) {
            emit('split-success', result.child_order_id)
        }
        visible.value = false
    } catch (e) {
        console.error('拆单失败:', e)
    } finally {
        confirmLoading.value = false
    }
}
</script>

<style lang="scss" scoped>
.order-split-body {
    .split-tip {
        margin-bottom: 12px;
    }

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

    .split-preview {
        display: flex;
        gap: 12px;
        margin-top: 16px;

        &__col {
            flex: 1;
            padding: 12px;
            border-radius: 6px;
            background: var(--el-fill-color-light);

            .col-title {
                font-size: 13px;
                font-weight: 600;
                color: var(--el-text-color-primary);
                margin-bottom: 8px;
            }

            .line {
                display: flex;
                justify-content: space-between;
                font-size: 12.5px;
                color: var(--el-text-color-regular);
                padding: 2px 0;

                .lb {
                    color: var(--el-text-color-secondary);
                }

                &.total {
                    margin-top: 4px;
                    padding-top: 6px;
                    border-top: 1px dashed var(--el-border-color);
                    font-weight: 600;
                    color: var(--el-text-color-primary);
                }
            }
        }
    }

    .split-note {
        margin-top: 6px;
        font-size: 11.5px;
        color: var(--el-text-color-secondary);
    }

    .split-form {
        margin-top: 12px;
    }

    .num {
        font-variant-numeric: tabular-nums;
    }
}
</style>
