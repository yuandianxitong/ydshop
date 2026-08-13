<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { memberApi, type IssuableCoupon } from '@/api/member'

interface Props {
  modelValue: boolean
  userId: number | null
}
interface Emits {
  (e: 'update:modelValue', v: boolean): void
  (e: 'issued'): void
}
const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const visible = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v),
})

const coupons = ref<IssuableCoupon[]>([])
const loading = ref(false)
const submitting = ref(false)
const couponId = ref<number>()
const count = ref(1)

const fetch = async () => {
  loading.value = true
  try {
    const res = await memberApi.listIssuableCoupons()
    coupons.value = (res.data?.list as any) || []
  } finally {
    loading.value = false
  }
}

watch(() => props.modelValue, (v) => {
  if (v) {
    couponId.value = undefined
    count.value = 1
    fetch()
  }
})

const couponLabel = (c: IssuableCoupon) => {
  if (c.type === 'fixed')   return `${c.name} · 减 ¥${c.value} (满 ¥${c.min_amount})`
  if (c.type === 'percent') return `${c.name} · ${c.value} 折`
  return `${c.name} · 无门槛`
}

const submit = async () => {
  if (!props.userId) return
  if (!couponId.value) { ElMessage.warning('请选择优惠券'); return }
  submitting.value = true
  try {
    await memberApi.issueCoupon(props.userId, { coupon_id: couponId.value, count: count.value })
    ElMessage.success(`已发放 ${count.value} 张`)
    emit('issued')
    visible.value = false
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <el-dialog v-model="visible" title="发放优惠券" width="480px" append-to-body>
    <el-form v-loading="loading" label-width="80px">
      <el-form-item label="优惠券">
        <el-select v-model="couponId" placeholder="请选择优惠券" filterable style="width: 100%">
          <el-option v-for="c in coupons" :key="c.id" :label="couponLabel(c)" :value="c.id" />
        </el-select>
        <div v-if="!coupons.length && !loading" class="text-[12px] text-ink-400 mt-1">暂无可发放的优惠券</div>
      </el-form-item>
      <el-form-item label="数量">
        <el-input-number v-model="count" :min="1" :max="100" />
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="visible = false">取消</el-button>
      <el-button type="primary" :loading="submitting" @click="submit">发放</el-button>
    </template>
  </el-dialog>
</template>
