<template>
  <el-dialog
    v-model="visible"
    :title="isEdit ? '编辑优惠券' : '新增优惠券'"
    width="560px"
    :close-on-click-modal="false"
    @closed="resetForm"
  >
    <el-form ref="formRef" :model="form" :rules="formRules" label-width="100px">
      <el-form-item label="优惠券名称" prop="name">
        <el-input v-model="form.name" placeholder="请输入优惠券名称" style="width: 320px" />
      </el-form-item>

      <el-form-item label="优惠类型" prop="type">
        <el-radio-group v-model="form.type">
          <el-radio value="fixed">满减券</el-radio>
          <el-radio value="percent">折扣券</el-radio>
          <el-radio value="no_threshold">无门槛</el-radio>
        </el-radio-group>
      </el-form-item>

      <el-form-item label="面值" prop="value">
        <el-input-number
          v-if="form.type === 'percent'"
          v-model="form.value"
          :min="1"
          :max="99"
          :precision="1"
          placeholder="折扣（如8.5折）"
          style="width: 200px"
        />
        <el-input-number
          v-else
          v-model="form.value"
          :min="0.01"
          :precision="2"
          placeholder="优惠金额"
          style="width: 200px"
        />
        <span class="form-hint">{{ form.type === 'percent' ? '折（如8.5折）' : '元' }}</span>
      </el-form-item>

      <el-form-item label="使用门槛" prop="min_amount">
        <el-input-number
          v-model="form.min_amount"
          :min="0"
          :precision="2"
          placeholder="0 表示无门槛"
          style="width: 200px"
        />
        <span class="form-hint">元（0表示无门槛）</span>
      </el-form-item>

      <el-form-item v-if="form.type === 'percent'" label="最高优惠" prop="max_discount">
        <el-input-number
          v-model="form.max_discount"
          :min="0"
          :precision="2"
          placeholder="0 表示不限"
          style="width: 200px"
        />
        <span class="form-hint">元（0表示不限）</span>
      </el-form-item>

      <el-form-item label="发放总量" prop="total_count">
        <el-input-number
          v-model="form.total_count"
          :min="0"
          placeholder="0 表示不限"
          style="width: 200px"
        />
        <span class="form-hint">张（0表示不限）</span>
      </el-form-item>

      <el-form-item label="每人限领" prop="per_user_limit">
        <el-input-number
          v-model="form.per_user_limit"
          :min="0"
          placeholder="0 表示不限"
          style="width: 200px"
        />
        <span class="form-hint">张（0表示不限）</span>
      </el-form-item>

      <el-form-item label="适用范围" prop="use_scope">
        <el-radio-group v-model="form.use_scope" @change="handleScopeChange">
          <el-radio value="all">全部商品</el-radio>
          <el-radio value="category">指定分类</el-radio>
          <el-radio value="spu">指定商品</el-radio>
        </el-radio-group>
      </el-form-item>

      <el-form-item v-if="form.use_scope === 'category'" label="选择分类" prop="scope_ids">
        <CategoryPicker v-model="form.scope_ids" />
      </el-form-item>
      <el-form-item v-if="form.use_scope === 'spu'" label="选择商品" prop="scope_ids">
        <GoodsPicker v-model="form.scope_ids" />
      </el-form-item>

      <el-form-item label="有效期" prop="dateRange">
        <el-date-picker
          v-model="dateRange"
          type="daterange"
          range-separator="至"
          start-placeholder="开始日期"
          end-placeholder="结束日期"
          value-format="YYYY-MM-DD"
          style="width: 320px"
          @change="handleDateChange"
        />
      </el-form-item>

      <el-form-item label="状态" prop="status">
        <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="handleClose">取消</el-button>
      <el-button type="primary" :loading="submitting" @click="handleSubmit">确定</el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts" name="CouponForm">
import { computed, ref, watch } from 'vue'
import { couponApi } from '@/api/marketing'
import { useFormDialog } from '@/hooks/useFormDialog'
import type { CouponInfo, CouponReq } from '@/types/api'
import GoodsPicker from '@/components/GoodsPicker/index.vue'
import CategoryPicker from '@/components/CategoryPicker/index.vue'

interface Props {
  modelValue: boolean
  formData: Partial<CouponInfo>
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'success'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const isEdit = computed(() => !!props.formData?.id)

const dateRange = ref<string[]>([])

const defaultForm: CouponReq & { id?: number } = {
  id: undefined,
  name: '',
  type: 'fixed',
  value: 0,
  min_amount: 0,
  max_discount: 0,
  total_count: 0,
  per_user_limit: 0,
  use_scope: 'all',
  scope_ids: [],
  start_at: '',
  end_at: '',
  status: 1,
}

const formRules = {
  name: [{ required: true, message: '请输入优惠券名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择优惠类型', trigger: 'change' }],
  value: [{ required: true, message: '请输入面值', trigger: 'blur' }],
}

const {
  form,
  formRef,
  submitting,
  visible,
  handleSubmit,
  handleClose,
  resetForm,
} = useFormDialog<CouponReq & { id?: number }>({
  defaultForm,
  modelValue: () => props.modelValue,
  onUpdate: (v) => emit('update:modelValue', v),
  onSuccess: () => emit('success'),
  createFn: (data) => couponApi.createCoupon(data),
  updateFn: (id, data) => couponApi.updateCoupon(id, data),
  sourceData: () => props.formData,
})

// 监听外部 formData 变化时同步 dateRange
watch(
  () => props.formData,
  (val) => {
    if (val && val.start_at && val.end_at) {
      dateRange.value = [val.start_at, val.end_at]
    } else {
      dateRange.value = []
    }
  },
  { immediate: true, deep: true }
)

const handleScopeChange = () => {
  form.scope_ids = []
}

const handleDateChange = (val: [string, string] | null) => {
  if (val && val.length === 2) {
    form.start_at = val[0]
    form.end_at = val[1]
  } else {
    form.start_at = ''
    form.end_at = ''
  }
}
</script>

<style lang="scss" scoped>
.form-hint {
  margin-left: 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
</style>
