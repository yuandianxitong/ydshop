<template>
  <el-dialog
    v-model="visible"
    :title="isEdit ? '编辑满减活动' : '新增满减活动'"
    width="620px"
    :close-on-click-modal="false"
    @closed="resetForm"
  >
    <el-form ref="formRef" :model="form" :rules="formRules" label-width="100px">
      <el-form-item label="活动名称" prop="name">
        <el-input v-model="form.name" placeholder="请输入活动名称" style="width: 360px" />
      </el-form-item>

      <el-form-item label="优惠类型" prop="type">
        <el-radio-group v-model="form.type">
          <el-radio value="reduce">满减</el-radio>
          <el-radio value="percent">满折</el-radio>
          <el-radio value="freight">包邮</el-radio>
        </el-radio-group>
      </el-form-item>

      <el-form-item label="优惠规则" prop="rules">
        <div class="rules-editor">
          <div v-for="(rule, idx) in (form.rules || [])" :key="idx" class="rule-row">
            <span class="rule-label">满</span>
            <el-input-number
              v-model="rule.min_amount"
              :min="0.01"
              :precision="2"
              placeholder="金额"
              style="width: 130px"
            />
            <span class="rule-label">元</span>
            <template v-if="form.type === 'reduce'">
              <span class="rule-label">减</span>
              <el-input-number
                v-model="rule.value"
                :min="0.01"
                :precision="2"
                placeholder="减少金额"
                style="width: 130px"
              />
              <span class="rule-label">元</span>
            </template>
            <template v-else-if="form.type === 'percent'">
              <span class="rule-label">打</span>
              <el-input-number
                v-model="rule.value"
                :min="1"
                :max="99"
                :precision="1"
                placeholder="折扣"
                style="width: 130px"
              />
              <span class="rule-label">折</span>
            </template>
            <template v-else>
              <span class="rule-label rule-freight">包邮</span>
            </template>
            <el-button
              type="danger"
              size="small"
              text
              :disabled="(form.rules?.length ?? 0) <= 1"
              @click="removeRule(idx)"
            >
              <i class="i-svg:trash-2" />
            </el-button>
          </div>
          <el-button type="primary" text @click="addRule">
            <i class="i-lucide:plus mr-1" />添加规则梯度
          </el-button>
        </div>
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

      <el-form-item label="叠加使用">
        <el-switch v-model="form.stackable" />
        <span class="form-hint">开启后可与其他优惠叠加使用</span>
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

      <el-form-item label="状态">
        <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="handleClose">取消</el-button>
      <el-button type="primary" :loading="submitting" @click="handleSubmit">确定</el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts" name="FullDiscountForm">
import { computed, ref, watch } from 'vue'
import { fullDiscountApi } from '@/api/marketing'
import { useFormDialog } from '@/hooks/useFormDialog'
import type { FullDiscountInfo, FullDiscountReq } from '@/types/api'
import GoodsPicker from '@/components/GoodsPicker/index.vue'
import CategoryPicker from '@/components/CategoryPicker/index.vue'

interface Props {
  modelValue: boolean
  formData: Partial<FullDiscountInfo>
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'success'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const isEdit = computed(() => !!props.formData?.id)

const dateRange = ref<string[]>([])

const defaultRule = () => ({ min_amount: undefined as number | undefined, value: undefined as number | undefined })

const defaultForm: FullDiscountReq & { id?: number } = {
  id: undefined,
  name: '',
  type: 'reduce',
  rules: [defaultRule()],
  use_scope: 'all',
  scope_ids: [],
  stackable: false,
  start_at: '',
  end_at: '',
  status: 1,
}

const formRules = {
  name: [{ required: true, message: '请输入活动名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择优惠类型', trigger: 'change' }],
}

const {
  form,
  formRef,
  submitting,
  visible,
  handleSubmit,
  handleClose,
  resetForm,
} = useFormDialog<FullDiscountReq & { id?: number }>({
  defaultForm,
  modelValue: () => props.modelValue,
  onUpdate: (v) => emit('update:modelValue', v),
  onSuccess: () => emit('success'),
  createFn: (data) => fullDiscountApi.createFullDiscount(data),
  updateFn: (id, data) => fullDiscountApi.updateFullDiscount(id, data),
  sourceData: () => props.formData,
})

// 同步外部 formData 到派生状态
watch(
  () => props.formData,
  (val) => {
    if (!form.rules || !form.rules.length) form.rules = [defaultRule()]
    if (val && val.start_at && val.end_at) {
      dateRange.value = [val.start_at, val.end_at]
    } else {
      dateRange.value = []
    }
  },
  { immediate: true, deep: true }
)

const addRule = () => {
  if (!form.rules) form.rules = []
  form.rules.push(defaultRule())
}
const removeRule = (idx: number) => form.rules?.splice(idx, 1)

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
.rules-editor {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
}

.rule-row {
  display: flex;
  align-items: center;
  gap: 6px;
}

.rule-label {
  font-size: 13px;
  color: var(--ink-700);
}

.rule-freight {
  color: var(--brand-500);
  font-weight: 500;
}

.form-hint {
  margin-left: 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
</style>
