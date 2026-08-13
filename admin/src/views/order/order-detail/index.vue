<template>
    <div class="order-detail-page" v-loading="loading">
        <div class="page-head">
            <div class="page-head__left">
                <el-button text class="back-btn" @click="handleBack">
                    <i class="i-svg:arrow-left mr-1" />
                    返回列表
                </el-button>
                <div v-if="order" class="page-head__title-wrap">
                    <h2 class="page-title">
                        <span class="num">{{ order.order_no }}</span>
                        <el-tag
                            :type="statusTagMap[order.status]?.type"
                            size="small"
                            effect="plain"
                            class="status-tag"
                        >
                            {{ statusTagMap[order.status]?.label || order.status }}
                        </el-tag>
                    </h2>
                    <p class="page-desc">
                        {{ deliveryTypeLabel }} · 下单于 {{ order.created_at || '-' }}
                    </p>
                </div>
                <h2 v-else class="page-title">订单详情</h2>
            </div>
            <div v-if="order" class="page-actions">
                <el-button
                    v-if="order.status === 'paid'"
                    type="primary"
                    @click="handleShip"
                >
                    发货
                </el-button>
                <el-button
                    v-if="order.status === 'pending'"
                    v-has-perm="['order.price-adjust']"
                    type="warning"
                    plain
                    @click="priceAdjustVisible = true"
                >
                    改价
                </el-button>
                <el-button
                    v-if="order.status === 'paid'"
                    v-has-perm="['order.split']"
                    plain
                    @click="splitVisible = true"
                >
                    拆单
                </el-button>
                <el-button
                    v-if="canEditAddress"
                    v-has-perm="['order.update']"
                    plain
                    @click="addressVisible = true"
                >
                    修改地址
                </el-button>
                <el-button
                    v-if="order.status === 'pending'"
                    type="danger"
                    plain
                    @click="handleCancel"
                >
                    取消订单
                </el-button>
                <el-button
                    v-if="order.status === 'cancelled' || order.status === 'closed'"
                    v-has-perm="['order.delete']"
                    type="danger"
                    plain
                    @click="handleDelete"
                >
                    删除订单
                </el-button>
            </div>
        </div>

        <template v-if="order">
            <!-- 摘要 KPI -->
            <div class="hero-strip">
                <div class="hero-kpi">
                    <div class="hero-kpi__label">实付金额</div>
                    <div class="hero-kpi__value price num">¥{{ formatPrice(order.pay_amount) }}</div>
                </div>
                <div class="hero-kpi">
                    <div class="hero-kpi__label">商品金额</div>
                    <div class="hero-kpi__value num">¥{{ formatPrice(order.goods_amount) }}</div>
                </div>
                <div class="hero-kpi">
                    <div class="hero-kpi__label">运费 / 优惠</div>
                    <div class="hero-kpi__value num">
                        ¥{{ formatPrice(order.freight_amount) }}
                        <span class="hero-kpi__sub">/ -¥{{ formatPrice(order.discount_amount) }}</span>
                    </div>
                </div>
                <div class="hero-kpi">
                    <div class="hero-kpi__label">买家</div>
                    <div class="hero-kpi__value hero-kpi__value--text">
                        {{ order.user_nickname || order.user_name || `用户 #${order.user_id}` }}
                    </div>
                </div>
            </div>

            <div class="detail-grid">
                <div class="detail-col">
                    <section class="od-card">
                        <div class="od-card__hd">
                            <span class="card-title">订单信息</span>
                        </div>
                        <div class="od-card__bd">
                            <div class="field-grid field-grid--3">
                                <div class="field">
                                    <div class="field__l">订单号</div>
                                    <div class="field__v num">{{ order.order_no }}</div>
                                </div>
                                <div class="field">
                                    <div class="field__l">订单状态</div>
                                    <div class="field__v">
                                        <el-tag
                                            :type="statusTagMap[order.status]?.type"
                                            size="small"
                                        >
                                            {{ statusTagMap[order.status]?.label || order.status }}
                                        </el-tag>
                                    </div>
                                </div>
                                <div class="field">
                                    <div class="field__l">配送方式</div>
                                    <div class="field__v">{{ deliveryTypeLabel }}</div>
                                </div>
                                <div class="field">
                                    <div class="field__l">下单时间</div>
                                    <div class="field__v">{{ order.created_at || '-' }}</div>
                                </div>
                                <div class="field">
                                    <div class="field__l">付款时间</div>
                                    <div class="field__v">{{ order.paid_at || '-' }}</div>
                                </div>
                                <div class="field">
                                    <div class="field__l">发货时间</div>
                                    <div class="field__v">{{ order.shipped_at || '-' }}</div>
                                </div>
                                <div class="field">
                                    <div class="field__l">收货时间</div>
                                    <div class="field__v">{{ order.received_at || '-' }}</div>
                                </div>
                                <div class="field">
                                    <div class="field__l">支付方式</div>
                                    <div class="field__v">{{ order.pay_type || '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section v-if="hasLineage" class="od-card">
                        <div class="od-card__hd">
                            <span class="card-title">订单关系</span>
                        </div>
                        <div class="od-card__bd">
                            <div class="field-grid field-grid--1">
                                <div v-if="order.split_from_order_id" class="field">
                                    <div class="field__l">拆分来源</div>
                                    <div class="field__v">
                                        <el-link
                                            type="primary"
                                            @click="goToOrder(order.split_from_order_id)"
                                        >
                                            {{
                                                order.split_from_order_no ||
                                                `订单 #${order.split_from_order_id}`
                                            }}
                                        </el-link>
                                    </div>
                                </div>
                                <div v-if="order.split_child_orders?.length" class="field">
                                    <div class="field__l">拆出的子订单</div>
                                    <div class="field__v">
                                        <el-link
                                            v-for="child in order.split_child_orders"
                                            :key="child.id"
                                            type="primary"
                                            class="lineage-link"
                                            @click="goToOrder(child.id)"
                                        >
                                            {{ child.order_no }}
                                        </el-link>
                                    </div>
                                </div>
                                <div v-if="order.merged_into_order_id" class="field">
                                    <div class="field__l">已合并至</div>
                                    <div class="field__v">
                                        <el-link
                                            type="primary"
                                            @click="goToOrder(order.merged_into_order_id)"
                                        >
                                            {{
                                                order.merged_into_order_no ||
                                                `订单 #${order.merged_into_order_id}`
                                            }}
                                        </el-link>
                                    </div>
                                </div>
                                <div v-if="order.merged_from_orders?.length" class="field">
                                    <div class="field__l">合并来源</div>
                                    <div class="field__v">
                                        <el-link
                                            v-for="src in order.merged_from_orders"
                                            :key="src.id"
                                            type="primary"
                                            class="lineage-link"
                                            @click="goToOrder(src.id)"
                                        >
                                            {{ src.order_no }}
                                        </el-link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="od-card">
                        <div class="od-card__hd">
                            <span class="card-title">金额明细</span>
                        </div>
                        <div class="od-card__bd">
                            <div class="amount-rows">
                                <div class="amount-row">
                                    <span>商品金额</span>
                                    <span class="num">¥{{ formatPrice(order.goods_amount) }}</span>
                                </div>
                                <div class="amount-row">
                                    <span>运费</span>
                                    <span class="num">¥{{ formatPrice(order.freight_amount) }}</span>
                                </div>
                                <div class="amount-row">
                                    <span>优惠金额</span>
                                    <span class="num">-¥{{ formatPrice(order.discount_amount) }}</span>
                                </div>
                                <div class="amount-row amount-row--total">
                                    <span>实付金额</span>
                                    <span class="num price">¥{{ formatPrice(order.pay_amount) }}</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section v-if="order.delivery_type !== 'pickup'" class="od-card">
                        <div class="od-card__hd">
                            <span class="card-title">收货信息</span>
                            <el-button
                                v-if="canEditAddress"
                                v-has-perm="['order.update']"
                                type="primary"
                                size="small"
                                text
                                @click="addressVisible = true"
                            >
                                修改地址
                            </el-button>
                        </div>
                        <div class="od-card__bd">
                            <template v-if="order.address_snapshot">
                                <div class="field-grid field-grid--2">
                                    <div class="field">
                                        <div class="field__l">收货人</div>
                                        <div class="field__v">{{ order.address_snapshot.name }}</div>
                                    </div>
                                    <div class="field">
                                        <div class="field__l">联系电话</div>
                                        <div class="field__v num">
                                            {{ order.address_snapshot.phone }}
                                        </div>
                                    </div>
                                    <div class="field field--full">
                                        <div class="field__l">收货地址</div>
                                        <div class="field__v">{{ fullAddress }}</div>
                                    </div>
                                </div>
                            </template>
                            <span v-else class="text-secondary">暂无收货地址</span>
                        </div>
                    </section>

                    <section v-if="order.delivery_type === 'pickup'" class="od-card">
                        <div class="od-card__hd">
                            <span class="card-title">自提信息</span>
                            <el-button
                                v-if="order.pickup_status === 'pending'"
                                type="primary"
                                size="small"
                                @click="onVerifyPickup"
                            >
                                手动核销
                            </el-button>
                        </div>
                        <div class="od-card__bd">
                            <div class="field-grid field-grid--2">
                                <div class="field">
                                    <div class="field__l">自提码</div>
                                    <div class="field__v">
                                        <span class="pickup-code num">{{
                                            formatPickupCode(order.pickup_code)
                                        }}</span>
                                        <el-button
                                            text
                                            type="primary"
                                            class="ml-2"
                                            @click="copyPickupCode"
                                            >复制</el-button
                                        >
                                    </div>
                                </div>
                                <div class="field">
                                    <div class="field__l">取货门店</div>
                                    <div class="field__v">门店 #{{ order.pickup_store_id || '-' }}</div>
                                </div>
                                <div class="field">
                                    <div class="field__l">自提状态</div>
                                    <div class="field__v">
                                        <el-tag :type="pickupStatusTag" size="small">{{
                                            pickupStatusText
                                        }}</el-tag>
                                    </div>
                                </div>
                                <div class="field">
                                    <div class="field__l">超时时间</div>
                                    <div class="field__v">{{ order.pickup_timeout_at || '-' }}</div>
                                </div>
                                <div v-if="order.pickup_at" class="field">
                                    <div class="field__l">核销时间</div>
                                    <div class="field__v">{{ order.pickup_at }}</div>
                                </div>
                                <div v-if="order.pickup_verified_by" class="field">
                                    <div class="field__l">核销人</div>
                                    <div class="field__v">Admin #{{ order.pickup_verified_by }}</div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="detail-col">
                    <section
                        v-if="order.status === 'shipped' || order.status === 'completed'"
                        class="od-card"
                    >
                        <div class="od-card__hd">
                            <span class="card-title">物流信息</span>
                            <el-button
                                type="primary"
                                text
                                size="small"
                                :loading="trackingLoading"
                                @click="fetchTracking"
                            >
                                刷新物流
                            </el-button>
                        </div>
                        <div class="od-card__bd">
                            <div class="field-grid field-grid--2">
                                <div class="field">
                                    <div class="field__l">快递公司</div>
                                    <div class="field__v">{{ expressCompanyText }}</div>
                                </div>
                                <div class="field">
                                    <div class="field__l">快递单号</div>
                                    <div class="field__v">
                                        <template v-if="expressNoText && expressNoText !== '无需物流'">
                                            <span class="num">{{ expressNoText }}</span>
                                            <el-button
                                                text
                                                type="primary"
                                                size="small"
                                                class="ml-1"
                                                @click="copyText(expressNoText)"
                                                >复制</el-button
                                            >
                                        </template>
                                        <span v-else>{{ expressNoText }}</span>
                                    </div>
                                </div>
                                <div v-if="logistics?.waybill_no" class="field field--full">
                                    <div class="field__l">电子面单号</div>
                                    <div class="field__v num">{{ logistics.waybill_no }}</div>
                                </div>
                            </div>

                            <div v-if="trackingTraces.length > 0" class="tracking-timeline">
                                <div class="tracking-title">物流轨迹</div>
                                <el-timeline>
                                    <el-timeline-item
                                        v-for="(trace, idx) in trackingTraces"
                                        :key="idx"
                                        :type="idx === 0 ? 'primary' : ''"
                                        :hollow="idx !== 0"
                                        :timestamp="trace.time"
                                        placement="top"
                                    >
                                        {{ trace.desc }}
                                    </el-timeline-item>
                                </el-timeline>
                            </div>
                            <el-empty
                                v-else-if="!trackingLoading"
                                description="暂无物流轨迹信息"
                                :image-size="48"
                            />
                        </div>
                    </section>

                    <section class="od-card">
                        <div class="od-card__hd">
                            <span class="card-title">备注信息</span>
                        </div>
                        <div class="od-card__bd">
                            <div class="field-grid field-grid--1">
                                <div class="field">
                                    <div class="field__l">买家备注</div>
                                    <div class="field__v">{{ order.buyer_remark || '无' }}</div>
                                </div>
                                <div class="field">
                                    <div class="field__l">卖家备注</div>
                                    <div class="field__v">
                                        <div class="remark-row">
                                            <span v-if="!editingRemark" class="remark-text">
                                                {{ order.seller_remark || '无' }}
                                            </span>
                                            <el-input
                                                v-else
                                                v-model="sellerRemarkInput"
                                                type="textarea"
                                                :rows="3"
                                                placeholder="请输入卖家备注"
                                            />
                                            <div class="remark-actions">
                                                <el-button
                                                    v-if="!editingRemark"
                                                    size="small"
                                                    text
                                                    type="primary"
                                                    @click="startEditRemark"
                                                >
                                                    编辑
                                                </el-button>
                                                <template v-else>
                                                    <el-button
                                                        size="small"
                                                        type="primary"
                                                        :loading="remarkLoading"
                                                        @click="saveRemark"
                                                    >
                                                        保存
                                                    </el-button>
                                                    <el-button size="small" @click="cancelEditRemark"
                                                        >取消</el-button
                                                    >
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section v-if="priceAdjustLogs.length" class="od-card">
                        <div class="od-card__hd">
                            <span class="card-title">改价记录</span>
                        </div>
                        <div class="od-card__bd">
                            <el-timeline>
                                <el-timeline-item
                                    v-for="log in priceAdjustLogs"
                                    :key="log.id"
                                    :timestamp="log.created_at"
                                    placement="top"
                                >
                                    <div class="adjust-log">
                                        <div class="adjust-log__admin">
                                            操作人：{{
                                                log.admin_name || `Admin #${log.admin_id}`
                                            }}
                                        </div>
                                        <div class="adjust-log__amounts">
                                            <span
                                                >商品 ¥{{
                                                    formatPrice(log.before_snapshot?.goods_amount)
                                                }}
                                                →
                                                ¥{{
                                                    formatPrice(log.after_snapshot?.goods_amount)
                                                }}</span
                                            >
                                            <span
                                                >运费 ¥{{
                                                    formatPrice(log.before_snapshot?.freight_amount)
                                                }}
                                                →
                                                ¥{{
                                                    formatPrice(log.after_snapshot?.freight_amount)
                                                }}</span
                                            >
                                            <span
                                                >优惠 ¥{{
                                                    formatPrice(log.before_snapshot?.discount_amount)
                                                }}
                                                →
                                                ¥{{
                                                    formatPrice(log.after_snapshot?.discount_amount)
                                                }}</span
                                            >
                                            <span class="pay"
                                                >实付 ¥{{
                                                    formatPrice(log.before_snapshot?.pay_amount)
                                                }}
                                                →
                                                ¥{{
                                                    formatPrice(log.after_snapshot?.pay_amount)
                                                }}</span
                                            >
                                        </div>
                                        <div v-if="log.remark" class="adjust-log__remark">
                                            原因：{{ log.remark }}
                                        </div>
                                    </div>
                                </el-timeline-item>
                            </el-timeline>
                        </div>
                    </section>
                </div>
            </div>

            <!-- 商品通栏 -->
            <section class="od-card od-card--table">
                <div class="od-card__hd table-header">
                    <span class="card-title">商品信息</span>
                    <span class="table-header__meta">共 {{ orderItems.length }} 件</span>
                </div>
                <el-table :data="orderItems" class="goods-table">
                    <el-table-column label="商品" min-width="240">
                        <template #default="{ row }">
                            <div class="goods-info">
                                <el-image
                                    v-if="row.goods_image || row.image"
                                    :src="row.goods_image || row.image"
                                    class="goods-thumb"
                                    fit="cover"
                                />
                                <div v-else class="goods-thumb goods-thumb--empty">
                                    <i class="i-lucide:image" />
                                </div>
                                <div class="goods-meta">
                                    <div class="goods-name">{{ row.goods_name }}</div>
                                    <div v-if="row.spec_text" class="goods-spec">
                                        {{ row.spec_text }}
                                    </div>
                                </div>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="单价" width="110" align="right">
                        <template #default="{ row }">
                            <span class="num">¥{{ formatPrice(row.price) }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="数量" prop="quantity" width="90" align="center" />
                    <el-table-column label="小计" width="120" align="right">
                        <template #default="{ row }">
                            <span class="num">¥{{ formatPrice(row.total_amount) }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="退款状态" width="110" align="center">
                        <template #default="{ row }">
                            <el-tag
                                v-if="row.refund_status"
                                :type="refundStatusTagMap[row.refund_status]?.type"
                                size="small"
                            >
                                {{
                                    refundStatusTagMap[row.refund_status]?.label ||
                                    row.refund_status
                                }}
                            </el-tag>
                            <span v-else class="text-secondary">-</span>
                        </template>
                    </el-table-column>
                </el-table>
            </section>
        </template>

        <OrderShipDialog
            v-model="shipDialogVisible"
            mode="single"
            :order-id="orderId"
            @success="fetchDetail"
        />

        <OrderAddressDialog
            v-model="addressVisible"
            :order-id="orderId"
            :address="order?.address_snapshot"
            @success="fetchDetail"
        />

        <PriceAdjustDialog
            v-model="priceAdjustVisible"
            :order-id="orderId"
            @success="onPriceAdjustSuccess"
        />

        <OrderSplitDialog
            v-model="splitVisible"
            :order-id="orderId"
            @split-success="onSplitSuccess"
        />
    </div>
</template>

<script setup lang="ts" name="OrderDetail">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { orderApi } from '@/api/order'
import { storeApi } from '@/api/store'
import type { OrderAdjustLog } from '@/types/api'
import OrderAddressDialog from '@/views/order/components/OrderAddressDialog.vue'
import OrderShipDialog from '@/views/order/components/OrderShipDialog.vue'
import OrderSplitDialog from '@/views/order/components/OrderSplitDialog.vue'
import PriceAdjustDialog from '@/views/order/components/PriceAdjustDialog.vue'

const router = useRouter()
const route = useRoute()

const orderId = ref<number>(Number(route.query.id))
const order = ref<Record<string, any> | null>(null)
const loading = ref(false)

const statusTagMap: Record<
    string,
    { label: string; type: 'primary' | 'success' | 'warning' | 'info' | 'danger' }
> = {
    pending: { label: '待付款', type: 'warning' },
    paid: { label: '待发货', type: 'primary' },
    shipped: { label: '待收货', type: 'info' },
    completed: { label: '已完成', type: 'success' },
    cancelled: { label: '已取消', type: 'danger' },
    closed: { label: '已关闭', type: 'info' }
}

const refundStatusTagMap: Record<
    string,
    { label: string; type: 'primary' | 'success' | 'warning' | 'info' | 'danger' }
> = {
    none: { label: '无', type: 'info' },
    pending: { label: '待审核', type: 'warning' },
    approved: { label: '已同意', type: 'primary' },
    rejected: { label: '已拒绝', type: 'danger' },
    refunded: { label: '已退款', type: 'success' }
}

const deliveryTypeLabel = computed(() => {
    const map: Record<string, string> = {
        express: '快递配送',
        merchant: '同城配送',
        pickup: '到店自提'
    }
    return map[order.value?.delivery_type ?? 'express'] || '快递配送'
})

const logistics = computed(() => order.value?.logistics ?? null)

const expressCompanyText = computed(() => {
    const company = String(logistics.value?.express_company || '').trim()
    const no = String(logistics.value?.express_no || '').trim()
    if (!company && !no) return '无需物流'
    return company || '-'
})

const expressNoText = computed(() => {
    const company = String(logistics.value?.express_company || '').trim()
    const no = String(logistics.value?.express_no || '').trim()
    if (!company && !no) return '无需物流'
    return no || '-'
})

const fullAddress = computed(() => {
    const snap = order.value?.address_snapshot
    if (!snap) return '-'
    return [snap.province, snap.city, snap.district, snap.detail].filter(Boolean).join(' ')
})

const editingRemark = ref(false)
const sellerRemarkInput = ref('')
const remarkLoading = ref(false)

const trackingTraces = ref<{ time: string; desc: string }[]>([])
const trackingLoading = ref(false)

const fetchTracking = async () => {
    if (!orderId.value) return
    try {
        trackingLoading.value = true
        const res = await orderApi.getTracking(orderId.value)
        trackingTraces.value = res.data?.traces || []
        // 轨迹接口若带回 logistics，可补全展示（不覆盖已有详情）
        if (res.data?.logistics && order.value && !order.value.logistics) {
            order.value.logistics = res.data.logistics
        }
    } catch (e) {
        console.error('获取物流轨迹失败:', e)
    } finally {
        trackingLoading.value = false
    }
}

const shipDialogVisible = ref(false)

const formatPrice = (price?: number | string | null) => {
    if (price == null) return '0.00'
    return Number(price).toFixed(2)
}

const pickupStatusText = computed(() => {
    const map: Record<string, string> = {
        pending: '待自提',
        verified: '已核销',
        timeout: '已超时',
        cancelled: '已取消'
    }
    return map[order.value?.pickup_status as string] || '-'
})

const pickupStatusTag = computed<'success' | 'warning' | 'danger' | 'info' | undefined>(() => {
    const map: Record<string, 'success' | 'warning' | 'danger' | 'info'> = {
        pending: 'warning',
        verified: 'success',
        timeout: 'danger',
        cancelled: 'info'
    }
    return map[order.value?.pickup_status as string] ?? undefined
})

const formatPickupCode = (code?: string) => {
    if (!code) return '-'
    return code.replace(/^(\d{3})(\d{3})$/, '$1 $2')
}

const copyText = async (text: string) => {
    try {
        await navigator.clipboard.writeText(text)
        ElMessage.success('已复制')
    } catch {
        ElMessage.error('复制失败')
    }
}

const copyPickupCode = async () => {
    const code = order.value?.pickup_code
    if (!code) return
    await copyText(code)
}

const onVerifyPickup = async () => {
    const id = orderId.value
    if (!id) return
    try {
        const { value: inputCode } = await ElMessageBox.prompt('请输入 6 位自提码', '手动核销', {
            confirmButtonText: '确认核销',
            cancelButtonText: '取消',
            inputPattern: /^\d{6}$/,
            inputErrorMessage: '请输入 6 位数字'
        })
        await storeApi.pickupVerify(id, inputCode)
        ElMessage.success('核销成功')
        await fetchDetail()
    } catch (error) {
        if (error !== 'cancel') {
            console.error('核销失败:', error)
        }
    }
}

const fetchDetail = async () => {
    if (!orderId.value) return
    try {
        loading.value = true
        const response = await orderApi.getOrderDetail(orderId.value)
        order.value = response.data
    } catch (error) {
        console.error('获取订单详情失败:', error)
    } finally {
        loading.value = false
    }
}

const priceAdjustVisible = ref(false)
const splitVisible = ref(false)

const hasLineage = computed(() => {
    const o = order.value
    if (!o) return false
    return Boolean(
        o.split_from_order_id ||
            o.merged_into_order_id ||
            o.split_child_orders?.length ||
            o.merged_from_orders?.length
    )
})

const goToOrder = (id?: number | null) => {
    if (!id) return
    router.push({ path: '/order/order-detail', query: { id } })
}

const orderItems = computed(() => (Array.isArray(order.value?.items) ? order.value.items : []))

const adjustLogs = ref<OrderAdjustLog[]>([])
const priceAdjustLogs = computed(() =>
    (Array.isArray(adjustLogs.value) ? adjustLogs.value : []).filter(
        (l) => l.action === 'price_adjust'
    )
)

const fetchAdjustLogs = async () => {
    if (!orderId.value) return
    try {
        const res = await orderApi.getAdjustLogs(orderId.value)
        adjustLogs.value = Array.isArray(res.data) ? res.data : []
    } catch {
        adjustLogs.value = []
    }
}

const onPriceAdjustSuccess = () => {
    fetchDetail()
    fetchAdjustLogs()
}

const onSplitSuccess = () => {
    fetchDetail()
    fetchAdjustLogs()
}

watch(
    () => route.query.id,
    async (val) => {
        const id = Number(val)
        if (!id || id === orderId.value) return
        orderId.value = id
        trackingTraces.value = []
        await fetchDetail()
        fetchAdjustLogs()
        if (order.value?.status === 'shipped' || order.value?.status === 'completed') {
            fetchTracking()
        }
    }
)

const handleBack = () => {
    router.push('/order/order-list')
}

const startEditRemark = () => {
    sellerRemarkInput.value = order.value?.seller_remark || ''
    editingRemark.value = true
}

const cancelEditRemark = () => {
    editingRemark.value = false
}

const saveRemark = async () => {
    try {
        remarkLoading.value = true
        await orderApi.addRemark(orderId.value, { seller_remark: sellerRemarkInput.value })
        if (order.value) {
            order.value.seller_remark = sellerRemarkInput.value
        }
        editingRemark.value = false
        ElMessage.success('备注已保存')
    } catch (error) {
        console.error('保存备注失败:', error)
    } finally {
        remarkLoading.value = false
    }
}

const handleShip = () => {
    shipDialogVisible.value = true
}

const handleCancel = async () => {
    try {
        await ElMessageBox.confirm('确定要取消该订单吗？', '取消订单', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        })
        await orderApi.cancelOrder(orderId.value, { reason: '管理员取消' })
        ElMessage.success('订单已取消')
        fetchDetail()
    } catch (error) {
        if (error !== 'cancel') {
            console.error('取消订单失败:', error)
        }
    }
}

const canEditAddress = computed(
    () =>
        !!order.value &&
        (order.value.status === 'pending' || order.value.status === 'paid') &&
        (order.value.delivery_type ?? 'express') !== 'pickup'
)

const addressVisible = ref(false)

const handleDelete = async () => {
    try {
        await ElMessageBox.confirm('确定删除该订单吗？删除后列表中将不再显示。', '删除订单', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        })
        await orderApi.deleteOrder(orderId.value)
        ElMessage.success('订单已删除')
        router.push('/order/order-list')
    } catch (error) {
        if (error !== 'cancel') {
            console.error('删除订单失败:', error)
        }
    }
}

onMounted(async () => {
    await fetchDetail()
    fetchAdjustLogs()
    if (order.value?.status === 'shipped' || order.value?.status === 'completed') {
        fetchTracking()
    }
})
</script>

<style lang="scss" scoped>
.order-detail-page {
    min-height: 100%;
    margin: -20px;
    padding: 20px;
    background: var(--page-bg, var(--ink-50, #f4f6fb));
}

.page-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;

    &__left {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        min-width: 0;
    }

    &__title-wrap {
        min-width: 0;
    }

    .back-btn {
        margin-top: 2px;
        color: var(--ink-500);
    }

    .page-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: var(--ink-900);
        line-height: 1.3;
    }

    .status-tag {
        flex-shrink: 0;
    }

    .page-desc {
        margin: 4px 0 0;
        font-size: 13px;
        color: var(--ink-500);
    }

    .page-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }
}

.hero-strip {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 16px;

    @media (max-width: 1100px) {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

.hero-kpi {
    padding: 16px 18px;
    background: var(--card-bg, #fff);
    border: 1px solid var(--ink-100);
    border-radius: var(--r-lg, 12px);
    box-shadow: var(--shadow-xs, 0 1px 2px rgba(16, 24, 40, 0.04));

    &__label {
        font-size: 12px;
        color: var(--ink-500);
        margin-bottom: 6px;
    }

    &__value {
        font-size: 22px;
        font-weight: 700;
        color: var(--ink-900);
        line-height: 1.2;

        &--text {
            font-size: 16px;
            font-weight: 600;
        }
    }

    &__sub {
        font-size: 13px;
        font-weight: 500;
        color: var(--ink-500);
        margin-left: 4px;
    }
}

.price {
    color: var(--danger, var(--rose-500, #f5222d));
}

.detail-grid {
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: 16px;
    margin-bottom: 16px;

    @media (max-width: 1100px) {
        grid-template-columns: 1fr;
    }
}

.detail-col {
    display: flex;
    flex-direction: column;
    gap: 16px;
    min-width: 0;
}

.od-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--ink-100);
    border-radius: var(--r-lg, 12px);
    box-shadow: var(--shadow-sm, 0 1px 3px rgba(16, 24, 40, 0.06));
    overflow: hidden;

    &__hd {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 18px;
        border-bottom: 1px solid var(--ink-100);
    }

    &__bd {
        padding: 16px 18px;
    }

    &--table {
        .od-card__hd {
            border-bottom: 1px solid var(--ink-100);
        }

        .goods-table {
            width: 100%;

            :deep(.el-table__inner-wrapper::before) {
                display: none;
            }
        }
    }
}

.table-header {
    &__meta {
        font-size: 12px;
        color: var(--ink-500);
    }
}

.field-grid {
    display: grid;
    gap: 14px 20px;

    &--1 {
        grid-template-columns: 1fr;
    }
    &--2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    &--3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    @media (max-width: 768px) {
        &--2,
        &--3 {
            grid-template-columns: 1fr;
        }
    }
}

.field {
    min-width: 0;

    &--full {
        grid-column: 1 / -1;
    }

    &__l {
        font-size: 12px;
        color: var(--ink-500);
        margin-bottom: 4px;
    }

    &__v {
        font-size: 14px;
        color: var(--ink-900);
        word-break: break-all;
    }
}

.amount-rows {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.amount-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    color: var(--ink-700);

    &--total {
        margin-top: 4px;
        padding-top: 12px;
        border-top: 1px dashed var(--ink-200);
        font-weight: 600;
        color: var(--ink-900);
    }
}

.pickup-code {
    font-size: 18px;
    letter-spacing: 0.12em;
    font-weight: 700;
}

.remark-row {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.remark-actions {
    display: flex;
    gap: 8px;
}

.lineage-link {
    margin-right: 12px;
}

.goods-info {
    display: flex;
    align-items: center;
    gap: 10px;

    .goods-thumb {
        width: 52px;
        height: 52px;
        border-radius: 8px;
        flex-shrink: 0;
        border: 1px solid var(--ink-100);

        &--empty {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--ink-50);
            color: var(--ink-400);
        }
    }

    .goods-meta {
        min-width: 0;

        .goods-name {
            font-size: 14px;
            font-weight: 500;
            color: var(--ink-900);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .goods-spec {
            font-size: 12px;
            color: var(--ink-500);
            margin-top: 2px;
        }
    }
}

.adjust-log {
    &__admin {
        font-size: 13px;
        font-weight: 500;
        color: var(--ink-900);
    }

    &__amounts {
        display: flex;
        flex-wrap: wrap;
        gap: 4px 14px;
        margin-top: 4px;
        font-size: 12.5px;
        color: var(--ink-600);

        .pay {
            color: var(--danger, var(--rose-500, #f5222d));
            font-weight: 600;
        }
    }

    &__remark {
        margin-top: 4px;
        font-size: 12px;
        color: var(--ink-500);
    }
}

.text-secondary {
    color: var(--ink-500);
}

.tracking-timeline {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px dashed var(--ink-200);

    .tracking-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--ink-900);
        margin-bottom: 12px;
    }
}
</style>
