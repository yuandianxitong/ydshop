<template>
  <div class="bg-gray-50 min-h-screen pb-16">
    <div class="mx-auto max-w-900px px-4 pt-6">

      <!-- Back -->
      <div class="flex items-center gap-3 mb-6">
        <button class="text-gray-400 hover:text-gray-600" @click="router.back()">
          <span class="i-carbon-arrow-left text-xl" />
        </button>
        <h1 class="text-xl font-bold text-gray-800">售后详情</h1>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="space-y-4">
        <div v-for="i in 3" :key="i" class="bg-white rounded-sm p-5 h-32 animate-pulse" />
      </div>

      <!-- Not found -->
      <div v-else-if="!refund" class="bg-white rounded-sm py-20 text-center text-gray-400">
        <span class="i-carbon-warning text-4xl block mb-3 mx-auto" />
        <p>售后单不存在</p>
      </div>

      <template v-else>

        <!-- ===== Status + Steps ===== -->
        <div class="bg-white rounded-sm p-6 mb-4">
          <div class="flex items-center justify-between mb-6 flex-wrap gap-2">
            <div class="flex items-center gap-3">
              <NTag :type="statusTagType" size="medium" :bordered="false">
                {{ REFUND_STATUS_TEXT[refund.status] ?? refund.status }}
              </NTag>
              <span class="text-sm text-gray-400">售后单号：{{ refund.refund_no }}</span>
            </div>
            <span class="text-sm text-gray-400">申请时间：{{ refund.created_at?.substring(0, 16) }}</span>
          </div>

          <NSteps :current="currentStep" :status="stepsStatus" size="small">
            <NStep v-for="step in stepLabels" :key="step" :title="step" />
          </NSteps>

          <!-- Rejected -->
          <NAlert v-if="refund.status === 'rejected'" type="error" class="mt-5" title="售后申请已被拒绝">
            {{ refund.refuse_reason || '商家未填写拒绝原因' }}
          </NAlert>

          <!-- Refund exception / manual review -->
          <NAlert
            v-else-if="refund.status === 'retryable_failed' || refund.status === 'manual_review'"
            type="warning"
            class="mt-5"
            :title="refund.status === 'retryable_failed' ? '退款遇到异常' : '退款正在人工处理'"
          >
            退款处理中出现异常，商家/客服正在跟进处理，请耐心等待，无需重复申请。
          </NAlert>
        </div>

        <!-- ===== Return logistics form (approved + need return) ===== -->
        <div v-if="needSubmitLogistics" class="bg-white rounded-sm p-5 mb-4">
          <h2 class="section-title mb-1">填写退货物流</h2>
          <p class="text-xs text-gray-400 mb-4">商家已同意退货退款，请将商品寄回后在此填写物流信息</p>
          <NForm
            ref="logisticsFormRef"
            :model="logisticsForm"
            :rules="logisticsRules"
            label-placement="left"
            label-width="90px"
            class="max-w-md"
          >
            <NFormItem label="快递公司" path="company">
              <NInput v-model:value="logisticsForm.company" placeholder="如：顺丰速运" :maxlength="50" />
            </NFormItem>
            <NFormItem label="快递单号" path="express_no">
              <NInput v-model:value="logisticsForm.express_no" placeholder="请输入快递单号" :maxlength="50" />
            </NFormItem>
          </NForm>
          <div class="flex justify-end">
            <NButton type="primary" :loading="submittingLogistics" @click="handleSubmitLogistics">
              提交物流信息
            </NButton>
          </div>
        </div>

        <!-- ===== Return logistics readonly ===== -->
        <div v-else-if="hasReturnLogistics" class="bg-white rounded-sm p-5 mb-4">
          <h2 class="section-title mb-3">退货物流</h2>
          <div class="text-sm text-gray-700 space-y-1.5">
            <p><span class="text-gray-400 inline-block w-20">快递公司</span>{{ refund.return_express_company }}</p>
            <p><span class="text-gray-400 inline-block w-20">快递单号</span>{{ refund.return_express_no }}</p>
          </div>
        </div>

        <!-- ===== Goods card ===== -->
        <div v-if="goodsItem" class="bg-white rounded-sm p-5 mb-4">
          <h2 class="section-title mb-3">售后商品</h2>
          <div class="flex items-center gap-3">
            <div class="w-16 h-16 flex-shrink-0 rounded overflow-hidden bg-gray-100">
              <img
                :src="goodsItem.goods_image || defaultImg"
                :alt="goodsItem.goods_name"
                class="w-full h-full object-cover"
                @error="onImgError"
              />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800 line-clamp-2">{{ goodsItem.goods_name }}</p>
              <p v-if="goodsItem.spec_text" class="text-xs text-gray-400 mt-0.5">规格：{{ goodsItem.spec_text }}</p>
            </div>
            <div class="text-right flex-shrink-0 text-sm text-gray-600">
              ¥{{ formatPrice(goodsItem.price) }} × {{ goodsItem.quantity }}
            </div>
          </div>
        </div>

        <!-- ===== Refund info ===== -->
        <div class="bg-white rounded-sm p-5 mb-4">
          <h2 class="section-title mb-3">退款信息</h2>
          <div class="text-sm text-gray-700 space-y-2">
            <p>
              <span class="text-gray-400 inline-block w-20">售后类型</span>
              {{ REFUND_TYPE_TEXT[refund.type] ?? refund.type }}
            </p>
            <p>
              <span class="text-gray-400 inline-block w-20">退款金额</span>
              <span class="text-red-500 font-semibold">¥{{ formatPrice(refund.refund_amount) }}</span>
            </p>
            <p>
              <span class="text-gray-400 inline-block w-20">退款原因</span>
              {{ refund.reason }}
            </p>
            <p v-if="refund.description">
              <span class="text-gray-400 inline-block w-20 align-top">问题描述</span>
              <span class="inline-block max-w-[calc(100%-6rem)]">{{ refund.description }}</span>
            </p>
            <div v-if="refund.images?.length" class="flex items-start">
              <span class="text-gray-400 inline-block w-20 flex-shrink-0 pt-1">凭证图片</span>
              <div class="flex flex-wrap gap-2">
                <a
                  v-for="(img, index) in refund.images"
                  :key="index"
                  :href="img"
                  target="_blank"
                  class="w-16 h-16 rounded overflow-hidden border border-gray-200 block"
                >
                  <img :src="img" class="w-full h-full object-cover" />
                </a>
              </div>
            </div>
            <p v-if="refund.order?.order_no">
              <span class="text-gray-400 inline-block w-20">关联订单</span>
              <NuxtLink
                :to="`/order/${refund.order_id}`"
                class="text-[var(--color-primary)] hover:underline"
              >
                {{ refund.order.order_no }}
              </NuxtLink>
            </p>
            <p v-if="refund.refunded_at">
              <span class="text-gray-400 inline-block w-20">退款时间</span>
              {{ refund.refunded_at }}
            </p>
          </div>
        </div>

        <!-- ===== Bottom actions ===== -->
        <div class="bg-white rounded-sm p-5 flex justify-end gap-3">
          <NuxtLink to="/order/refund-list" class="btn-outline">返回售后列表</NuxtLink>
        </div>

      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  NSteps, NStep, NTag, NAlert, NForm, NFormItem, NInput, NButton,
  useMessage, type FormInst, type FormRules,
} from 'naive-ui'
import {
  orderApi,
  refundOrderItemOf,
  REFUND_STATUS_TEXT,
  REFUND_TYPE_TEXT,
  type RefundItem,
  type RefundStatus,
} from '~/api/order'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const router = useRouter()
const message = useMessage()

const refundId = computed(() => route.params.id as string)

const loading = ref(true)
const refund = ref<RefundItem | null>(null)

const defaultImg = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"><rect fill="%23f3f4f6" width="64" height="64"/></svg>'

const goodsItem = computed(() => refundOrderItemOf(refund.value))

/** 是否退货类售后（需要买家寄回商品） */
const needReturn = computed(() => !!refund.value && refund.value.type !== 'refund_only')

/** 待退货状态且是退货类售后 → 展示物流表单 */
const needSubmitLogistics = computed(() =>
  !!refund.value && refund.value.status === 'approved' && needReturn.value,
)

const hasReturnLogistics = computed(() =>
  !!(refund.value?.return_express_no && refund.value?.return_express_company),
)

// ---- 状态时间轴 ----
const stepLabels = computed(() =>
  needReturn.value
    ? ['提交申请', '商家审核', '买家退货', '商家收货', '退款完成']
    : ['提交申请', '商家审核', '退款完成'],
)

/** NSteps current（1-based） */
const currentStep = computed(() => {
  if (!refund.value) return 1
  const status = refund.value.status
  if (needReturn.value) {
    const map: Record<RefundStatus, number> = {
      pending: 2,
      approved: 3,
      returning: 4,
      received: 5,
      refunding: 5,
      retryable_failed: 5,
      manual_review: 5,
      refunded: 5,
      rejected: 2,
    }
    return map[status] ?? 1
  }
  const map: Record<RefundStatus, number> = {
    pending: 2,
    approved: 3,
    returning: 3,
    received: 3,
    refunding: 3,
    retryable_failed: 3,
    manual_review: 3,
    refunded: 3,
    rejected: 2,
  }
  return map[status] ?? 1
})

const stepsStatus = computed<'process' | 'finish' | 'error' | 'wait'>(() => {
  if (!refund.value) return 'process'
  if (refund.value.status === 'rejected') return 'error'
  if (refund.value.status === 'retryable_failed') return 'error'
  if (refund.value.status === 'refunded') return 'finish'
  return 'process'
})

const statusTagType = computed<'default' | 'success' | 'error' | 'warning' | 'info'>(() => {
  if (!refund.value) return 'default'
  const status = refund.value.status
  if (status === 'refunded') return 'success'
  if (status === 'rejected' || status === 'retryable_failed') return 'error'
  if (status === 'pending' || status === 'manual_review') return 'warning'
  return 'info'
})

// ---- 退货物流表单 ----
const logisticsFormRef = ref<FormInst | null>(null)
const submittingLogistics = ref(false)
const logisticsForm = reactive({
  company: '',
  express_no: '',
})

const logisticsRules: FormRules = {
  company: [{ required: true, message: '请输入快递公司', trigger: 'blur' }],
  express_no: [{ required: true, message: '请输入快递单号', trigger: 'blur' }],
}

async function handleSubmitLogistics() {
  try {
    await logisticsFormRef.value?.validate()
  } catch {
    return
  }

  submittingLogistics.value = true
  try {
    const res = await orderApi.submitRefundLogistics(refundId.value, {
      company: logisticsForm.company.trim(),
      express_no: logisticsForm.express_no.trim(),
    })
    if (res.code === 200) {
      message.success('退货物流已提交')
      await fetchDetail()
    }
  } finally {
    submittingLogistics.value = false
  }
}

function formatPrice(val: string | number | undefined | null): string {
  if (val == null || val === '') return '0.00'
  const n = typeof val === 'number' ? val : Number(val)
  if (!Number.isFinite(n)) return '0.00'
  return n.toFixed(2)
}

function onImgError(e: Event) {
  const img = e.target as HTMLImageElement
  img.src = defaultImg
}

async function fetchDetail() {
  loading.value = true
  try {
    const res = await orderApi.getRefundDetail(refundId.value)
    if (res.code === 200) refund.value = res.data
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDetail()
})
</script>

<style scoped>
.section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #111827;
}

.btn-outline {
  padding: 7px 18px;
  font-size: 0.875rem;
  border-radius: 2px;
  border: 1px solid #d1d5db;
  color: #374151;
  background: #fff;
  cursor: pointer;
  transition: all 0.15s;
  text-decoration: none;
  display: inline-block;
}
.btn-outline:hover { border-color: var(--color-primary); color: var(--color-primary); }
</style>
