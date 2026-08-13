<template>
    <el-dialog v-model="visible" title="订单合并" width="760px" :close-on-click-modal="false">
        <div class="order-merge-body">
            <el-alert
                title="合并后保留下单时间最早的订单，其余订单将被关闭，商品与金额并入保留订单。"
                type="warning"
                :closable="false"
                class="merge-tip"
            />

            <el-table :data="orders" border size="small">
                <el-table-column prop="order_no" label="订单号" min-width="180">
                    <template #default="{ row }">
                        <span class="num">{{ row.order_no }}</span>
                    </template>
                </el-table-column>
                <el-table-column prop="created_at" label="下单时间" width="165" />
                <el-table-column label="商品金额" width="100" align="right">
                    <template #default="{ row }">
                        <span class="num">¥{{ formatFen(yuanToFen(row.goods_amount)) }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="实付金额" width="100" align="right">
                    <template #default="{ row }">
                        <span class="num">¥{{ formatFen(yuanToFen(row.pay_amount)) }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="合并处理" width="110" align="center">
                    <template #default="{ row }">
                        <el-tag v-if="row.id === survivorId" type="success" size="small"
                            >合并后保留</el-tag
                        >
                        <el-tag v-else type="info" size="small">将被关闭</el-tag>
                    </template>
                </el-table-column>
            </el-table>

            <!-- 合并后金额预览（Σ 前端估算） -->
            <div class="merge-preview">
                <div class="merge-preview__item">
                    <div class="lb">商品金额</div>
                    <div class="vv num">¥{{ formatFen(sumGoodsFen) }}</div>
                </div>
                <div class="merge-preview__item">
                    <div class="lb">运费</div>
                    <div class="vv num">¥{{ formatFen(sumFreightFen) }}</div>
                </div>
                <div class="merge-preview__item">
                    <div class="lb">优惠</div>
                    <div class="vv num">-¥{{ formatFen(sumDiscountFen) }}</div>
                </div>
                <div class="merge-preview__item is-pay">
                    <div class="lb">合并后实付</div>
                    <div class="vv num">¥{{ formatFen(sumPayFen) }}</div>
                </div>
            </div>
            <div class="merge-note">* 各项为参与订单金额求和的前端估算，以提交结果为准。</div>

            <el-form label-width="60px" class="merge-form">
                <el-form-item label="备注">
                    <el-input
                        v-model="remark"
                        type="textarea"
                        :rows="2"
                        maxlength="200"
                        show-word-limit
                        placeholder="选填，说明合单原因"
                    />
                </el-form-item>
            </el-form>
        </div>

        <template #footer>
            <el-button @click="visible = false">取消</el-button>
            <el-button
                type="primary"
                :loading="confirmLoading"
                :disabled="orders.length < 2"
                @click="handleConfirm"
            >
                确认合并（{{ orders.length }} 单）
            </el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { computed, ref, watch } from 'vue'

import { orderApi } from '@/api/order'
import { formatFen, yuanToFen } from '@/utils/money'

const props = defineProps<{
    modelValue: boolean
    orders: Record<string, any>[]
}>()
const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
    (e: 'success'): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v)
})

const confirmLoading = ref(false)
const remark = ref('')

const userIdOf = (row: Record<string, any>) => row.user?.id ?? row.user_id

// 二次校验：不满足合并条件时给出错误并自动关闭
function validateOrders(): string {
    const rows = props.orders
    if (rows.length < 2) return '请选择至少 2 笔订单进行合并'
    if (rows.some((r) => r.status !== 'pending')) return '仅待付款订单可合并'
    const uid = userIdOf(rows[0])
    if (rows.some((r) => userIdOf(r) !== uid)) return '仅同一用户的订单可合并'
    return ''
}

watch(visible, (v) => {
    if (!v) {
        remark.value = ''
        return
    }
    const reason = validateOrders()
    if (reason) {
        ElMessage.error(reason)
        visible.value = false
    }
})

// 存活单 = created_at 最早的订单
const survivorId = computed(() => {
    let best: Record<string, any> | null = null
    for (const r of props.orders) {
        if (!best || String(r.created_at || '') < String(best.created_at || '')) {
            best = r
        }
    }
    return best?.id
})

// 合并后金额预览（分整数求和）
const sumGoodsFen = computed(() => props.orders.reduce((s, r) => s + yuanToFen(r.goods_amount), 0))
const sumFreightFen = computed(() =>
    props.orders.reduce((s, r) => s + yuanToFen(r.freight_amount), 0)
)
const sumDiscountFen = computed(() =>
    props.orders.reduce((s, r) => s + yuanToFen(r.discount_amount), 0)
)
const sumPayFen = computed(() => sumGoodsFen.value + sumFreightFen.value - sumDiscountFen.value)

const handleConfirm = async () => {
    const reason = validateOrders()
    if (reason) {
        ElMessage.error(reason)
        return
    }
    confirmLoading.value = true
    try {
        const res = await orderApi.mergeOrders({
            order_ids: props.orders.map((r) => r.id),
            remark: remark.value || undefined
        })
        ElMessage.success(`合并成功，保留订单：${res.data?.survivor_order_no || '—'}`)
        emit('success')
        visible.value = false
    } catch (e) {
        console.error('合并订单失败:', e)
    } finally {
        confirmLoading.value = false
    }
}
</script>

<style lang="scss" scoped>
.order-merge-body {
    .merge-tip {
        margin-bottom: 12px;
    }

    .merge-preview {
        display: flex;
        gap: 12px;
        margin-top: 16px;
        padding: 12px;
        border-radius: 6px;
        background: var(--el-fill-color-light);

        &__item {
            flex: 1;

            .lb {
                font-size: 12px;
                color: var(--el-text-color-secondary);
                margin-bottom: 4px;
            }

            .vv {
                font-size: 13px;
                color: var(--el-text-color-primary);
            }

            &.is-pay .vv {
                font-size: 15px;
                font-weight: 600;
                color: var(--el-color-danger);
            }
        }
    }

    .merge-note {
        margin-top: 6px;
        font-size: 11.5px;
        color: var(--el-text-color-secondary);
    }

    .merge-form {
        margin-top: 12px;
    }

    .num {
        font-variant-numeric: tabular-nums;
    }
}
</style>
