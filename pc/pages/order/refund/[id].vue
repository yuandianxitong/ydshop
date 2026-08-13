<template>
  <div class="bg-gray-50 min-h-screen pb-16">
    <div class="mx-auto max-w-800px px-4 pt-6">

      <!-- Back -->
      <div class="flex items-center gap-3 mb-6">
        <button class="text-gray-400 hover:text-gray-600" @click="router.back()">
          <span class="i-carbon-arrow-left text-xl" />
        </button>
        <h1 class="text-xl font-bold text-gray-800">申请退款</h1>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="bg-white rounded-sm p-6 animate-pulse space-y-4">
        <div class="h-20 bg-gray-100 rounded" />
        <div class="h-8 bg-gray-100 rounded w-1/2" />
        <div class="h-8 bg-gray-100 rounded w-3/4" />
      </div>

      <template v-else-if="goodsItem">

        <!-- Item info card -->
        <div class="bg-white rounded-sm p-5 mb-4">
          <h2 class="text-sm font-semibold text-gray-700 mb-3">退款商品</h2>
          <div class="flex items-start gap-3">
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
              <p class="text-sm text-[var(--color-primary)] font-medium mt-1">
                ¥{{ formatPrice(goodsItem.price) }}
                <span class="text-gray-400 font-normal ml-1">× {{ goodsItem.quantity }}</span>
              </p>
            </div>
            <div class="text-right flex-shrink-0">
              <p class="text-xs text-gray-400">小计</p>
              <p class="text-sm font-semibold text-gray-800 mt-0.5">¥{{ formatPrice(goodsItem.total_amount) }}</p>
              <p class="text-xs text-gray-400 mt-0.5">¥{{ formatPrice(goodsItem.price) }} × {{ goodsItem.quantity }}</p>
            </div>
          </div>
        </div>

        <!-- Refund form -->
        <div class="bg-white rounded-sm p-5 mb-4">
          <NForm
            ref="formRef"
            :model="form"
            :rules="rules"
            label-placement="top"
            require-mark-placement="right-hanging"
          >
            <!-- Refund type -->
            <NFormItem label="退款类型" path="type">
              <NRadioGroup v-model:value="form.type">
                <NSpace>
                  <NRadio value="refund_only">仅退款</NRadio>
                  <NRadio value="refund_with_goods">退货退款</NRadio>
                </NSpace>
              </NRadioGroup>
            </NFormItem>

            <!-- Refund amount -->
            <NFormItem label="退款金额" path="refund_amount">
              <NInputNumber
                v-model:value="form.refund_amount"
                :min="0.01"
                :max="maxAmount"
                :precision="2"
                :step="0.01"
                class="w-full"
                placeholder="请输入退款金额"
              >
                <template #prefix>¥</template>
                <template #suffix>
                  <span class="text-xs text-gray-400">最多 ¥{{ formatPrice(goodsItem.total_amount) }}</span>
                </template>
              </NInputNumber>
            </NFormItem>

            <!-- Reason -->
            <NFormItem label="退款原因" path="reason">
              <NSelect
                v-model:value="form.reason"
                :options="reasonOptions"
                placeholder="请选择退款原因"
              />
            </NFormItem>

            <!-- Description -->
            <NFormItem label="问题描述" path="description">
              <NInput
                v-model:value="form.description"
                type="textarea"
                placeholder="请详细描述退款原因（选填）"
                :maxlength="500"
                show-count
                :rows="4"
              />
            </NFormItem>

            <!-- Images -->
            <NFormItem label="凭证图片">
              <div class="w-full">
                <div class="flex flex-wrap gap-3">
                  <!-- Uploaded images -->
                  <div
                    v-for="(img, index) in form.images"
                    :key="index"
                    class="relative w-20 h-20 rounded overflow-hidden border border-gray-200"
                  >
                    <img :src="img" class="w-full h-full object-cover" />
                    <button
                      class="absolute top-0 right-0 w-5 h-5 bg-black/50 flex items-center justify-center text-white text-xs rounded-bl"
                      @click="removeImage(index)"
                    >
                      <span class="i-carbon-close text-xs" />
                    </button>
                  </div>

                  <!-- Upload button -->
                  <label
                    v-if="form.images.length < 5"
                    class="w-20 h-20 border border-dashed border-gray-300 rounded flex flex-col items-center justify-center cursor-pointer hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] text-gray-400 transition-colors"
                  >
                    <span class="i-carbon-add text-2xl" />
                    <span class="text-xs mt-1">上传</span>
                    <input
                      type="file"
                      accept="image/*"
                      class="hidden"
                      :disabled="uploading"
                      @change="handleFileChange"
                    />
                  </label>
                </div>
                <p class="text-xs text-gray-400 mt-2">最多上传 5 张图片</p>
              </div>
            </NFormItem>
          </NForm>
        </div>

        <!-- Submit -->
        <div class="bg-white rounded-sm p-5 flex justify-end gap-3">
          <button class="btn-outline" @click="router.back()">取消</button>
          <button
            class="btn-primary"
            :disabled="submitting"
            @click="handleSubmit"
          >
            {{ submitting ? '提交中...' : '提交申请' }}
          </button>
        </div>

      </template>

      <!-- Not found -->
      <div v-else class="bg-white rounded-sm py-20 text-center text-gray-400">
        <span class="i-carbon-warning text-4xl block mb-3 mx-auto" />
        <p>商品信息不存在</p>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import {
  NForm, NFormItem, NInput, NInputNumber, NRadioGroup, NRadio, NSpace, NSelect,
  useMessage, type FormInst, type FormRules, type SelectOption,
} from 'naive-ui'
import { orderApi, type OrderItemRow } from '~/api/order'
import { getToken } from '~/composables/useRequest'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const router = useRouter()
const message = useMessage()

const orderId = computed(() => route.params.id as string)
const goodsId = computed(() => route.query.goods_id as string)

const loading = ref(true)
const submitting = ref(false)
const uploading = ref(false)
const goodsItem = ref<OrderItemRow | null>(null)

const defaultImg = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"><rect fill="%23f3f4f6" width="64" height="64"/></svg>'

// Max refund amount in yuan
const maxAmount = computed(() =>
  goodsItem.value ? Number(goodsItem.value.total_amount) : 0,
)

const formRef = ref<FormInst | null>(null)

const form = reactive({
  type: 'refund_only' as 'refund_only' | 'refund_with_goods',
  refund_amount: null as number | null,
  reason: null as string | null,
  description: '',
  images: [] as string[],
})

const reasonOptions: SelectOption[] = [
  { label: '不想要了', value: '不想要了' },
  { label: '商品质量问题', value: '商品质量问题' },
  { label: '商品与描述不符', value: '商品与描述不符' },
  { label: '发错货', value: '发错货' },
  { label: '商品损坏/破损', value: '商品损坏/破损' },
  { label: '未收到货', value: '未收到货' },
  { label: '其他原因', value: '其他原因' },
]

const rules: FormRules = {
  type: [{ required: true, message: '请选择退款类型' }],
  refund_amount: [{ required: true, type: 'number', message: '请输入退款金额' }],
  reason: [{ required: true, message: '请选择退款原因', trigger: ['change', 'blur'] }],
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

function removeImage(index: number) {
  form.images.splice(index, 1)
}

async function handleFileChange(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  if (file.size > 10 * 1024 * 1024) {
    message.warning('图片大小不能超过 10MB')
    input.value = ''
    return
  }

  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('file', file)
    const token = getToken()
    const res = await fetch('/api/common/upload/image', {
      method: 'POST',
      headers: token ? { Authorization: `Bearer ${token}` } : {},
      body: formData,
    })
    const json = await res.json()
    if (json.code === 200) {
      form.images.push(json.data.url)
    } else {
      message.error(json.message || '上传失败')
    }
  } catch {
    message.error('上传失败，请重试')
  } finally {
    uploading.value = false
    input.value = ''
  }
}

async function handleSubmit() {
  try {
    await formRef.value?.validate()
  } catch {
    return
  }

  if (!goodsItem.value) return

  submitting.value = true
  try {
    const res = await orderApi.applyRefund({
      order_id: Number(orderId.value),
      order_item_id: goodsItem.value.id,
      type: form.type,
      refund_amount: form.refund_amount ?? undefined,
      reason: form.reason!,
      description: form.description || undefined,
      images: form.images.length > 0 ? form.images : undefined,
    })

    if (res.code === 200) {
      message.success('退款申请已提交')
      router.replace(`/order/${orderId.value}`)
    }
  } finally {
    submitting.value = false
  }
}

async function loadItem() {
  loading.value = true
  try {
    const res = await orderApi.getOrderDetail(orderId.value)
    if (res.code === 200) {
      const item = res.data.items.find(g => String(g.id) === goodsId.value)
      goodsItem.value = item || null
      if (item) {
        // default amount = full item total
        form.refund_amount = Number(item.total_amount)
      }
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadItem()
})
</script>

<style scoped>
.btn-outline {
  padding: 7px 20px;
  font-size: 0.875rem;
  border-radius: 2px;
  border: 1px solid #d1d5db;
  color: #374151;
  background: #fff;
  cursor: pointer;
  transition: all 0.15s;
}
.btn-outline:hover { border-color: var(--color-primary); color: var(--color-primary); }

.btn-primary {
  padding: 7px 24px;
  font-size: 0.875rem;
  border-radius: 2px;
  border: 1px solid var(--color-primary);
  color: #fff;
  background: var(--color-primary);
  cursor: pointer;
  transition: opacity 0.15s;
}
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-primary:not(:disabled):hover { opacity: 0.85; }
</style>
