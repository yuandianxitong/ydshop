<template>
  <div class="bg-gray-50 min-h-screen pb-16">
    <div class="mx-auto max-w-800px px-4 pt-6">
      <div class="flex items-center gap-3 mb-6">
        <button class="text-gray-400 hover:text-gray-600" @click="router.back()">
          <span class="i-carbon-arrow-left text-xl" />
        </button>
        <h1 class="text-xl font-bold text-gray-800">发表评价</h1>
      </div>

      <div v-if="loading" class="bg-white rounded-sm p-6 h-64 animate-pulse" />
      <template v-else-if="goodsItem">
        <div class="bg-white rounded-sm p-5 mb-4 flex items-center gap-4">
          <img
            :src="goodsItem.goods_image || '/placeholder.png'"
            :alt="goodsItem.goods_name"
            class="w-18 h-18 object-cover rounded border border-gray-100"
          />
          <div>
            <p class="font-medium text-gray-800">{{ goodsItem.goods_name }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ goodsItem.spec_text || '默认规格' }}</p>
          </div>
        </div>

        <div class="bg-white rounded-sm p-5 mb-4">
          <NForm label-placement="top">
            <NFormItem label="商品评分" required>
              <NRate v-model:value="rating" size="large" />
            </NFormItem>
            <NFormItem label="评价内容">
              <NInput
                v-model:value="content"
                type="textarea"
                :rows="6"
                :maxlength="500"
                show-count
                placeholder="分享商品体验，帮助其他顾客做出选择"
              />
            </NFormItem>
            <NCheckbox v-model:checked="anonymous">匿名评价</NCheckbox>
          </NForm>
        </div>

        <div class="bg-white rounded-sm p-5 flex justify-end gap-3">
          <button class="btn-outline" @click="router.back()">取消</button>
          <NButton type="primary" :loading="submitting" @click="submitReview">提交评价</NButton>
        </div>
      </template>

      <div v-else class="bg-white rounded-sm py-20 text-center text-gray-400">
        评价商品不存在或已评价
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { NButton, NCheckbox, NForm, NFormItem, NInput, NRate, useMessage } from 'naive-ui'
import { orderApi, type OrderItemRow } from '~/api/order'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const router = useRouter()
const message = useMessage()
const orderId = computed(() => String(route.params.id))
const goodsId = computed(() => String(route.query.goods_id || ''))

const loading = ref(true)
const submitting = ref(false)
const goodsItem = ref<OrderItemRow | null>(null)
const rating = ref(5)
const content = ref('')
const anonymous = ref(false)

async function loadItem() {
  loading.value = true
  try {
    const res = await orderApi.getOrderDetail(orderId.value)
    if (res.code !== 200 || res.data.status !== 'completed') return
    goodsItem.value = res.data.items.find(item =>
      String(item.id) === goodsId.value && !item.is_reviewed,
    ) || null
  } finally {
    loading.value = false
  }
}

async function submitReview() {
  if (!goodsItem.value) return
  if (rating.value < 1) {
    message.warning('请选择评分')
    return
  }
  submitting.value = true
  try {
    const res = await orderApi.createReview({
      order_id: Number(orderId.value),
      order_item_id: goodsItem.value.id,
      spu_id: goodsItem.value.spu_id,
      rating: rating.value,
      content: content.value.trim() || undefined,
      is_anonymous: anonymous.value ? 1 : 0,
    })
    if (res.code === 200) {
      message.success('评价成功')
      router.replace(`/order/${orderId.value}`)
    }
  } finally {
    submitting.value = false
  }
}

onMounted(loadItem)
</script>
