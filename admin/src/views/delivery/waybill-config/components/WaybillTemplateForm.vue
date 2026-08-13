<template>
  <el-dialog
    v-model="visible"
    :title="isEdit ? '编辑面单模版' : '新增面单模版'"
    width="560px"
    :close-on-click-modal="false"
    @close="handleClose"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-width="120px">
      <el-form-item label="模版名称" prop="name">
        <el-input v-model="form.name" placeholder="如：顺丰标快一联" maxlength="100" />
      </el-form-item>
      <el-form-item label="物流公司" prop="express_code">
        <el-select
          v-model="form.express_code"
          filterable
          placeholder="请选择物流公司"
          style="width: 100%"
          @change="onCarrierChange"
        >
          <el-option
            v-for="(item, code) in catalog"
            :key="code"
            :label="`${item.name}（${code}）`"
            :value="code"
          />
        </el-select>
      </el-form-item>
      <el-form-item label="业务类型" prop="exp_type">
        <el-select
          v-model="form.exp_type"
          placeholder="请选择业务类型"
          style="width: 100%"
          :disabled="!form.express_code"
          @change="onExpTypeChange"
        >
          <el-option
            v-for="item in expTypeOptions"
            :key="item.value"
            :label="item.label"
            :value="item.value"
          />
        </el-select>
      </el-form-item>
      <el-form-item label="模版样式" prop="template_size">
        <el-select
          v-model="form.template_size"
          placeholder="请选择模版样式"
          style="width: 100%"
          :disabled="!form.express_code"
          @change="onTemplateSizeChange"
        >
          <el-option
            v-for="item in templateSizeOptions"
            :key="item.value || 'default'"
            :label="item.label"
            :value="item.value"
          />
        </el-select>
      </el-form-item>
      <el-form-item label="邮费支付方式" prop="pay_type">
        <el-radio-group v-model="form.pay_type">
          <el-radio :value="1">现付</el-radio>
          <el-radio :value="2">到付</el-radio>
          <el-radio :value="3">月结</el-radio>
        </el-radio-group>
      </el-form-item>
      <el-form-item label="上门揽件" prop="need_pickup">
        <el-radio-group v-model="form.need_pickup">
          <el-radio :value="1">是</el-radio>
          <el-radio :value="0">否</el-radio>
        </el-radio-group>
      </el-form-item>
      <el-form-item label="是否默认" prop="is_default">
        <el-radio-group v-model="form.is_default">
          <el-radio :value="1">是</el-radio>
          <el-radio :value="0">否</el-radio>
        </el-radio-group>
      </el-form-item>
      <el-form-item label="排序" prop="sort">
        <el-input-number v-model="form.sort" :min="0" controls-position="right" />
      </el-form-item>
      <el-form-item label="状态" prop="status">
        <el-radio-group v-model="form.status">
          <el-radio :value="1">启用</el-radio>
          <el-radio :value="0">禁用</el-radio>
        </el-radio-group>
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="handleClose">取消</el-button>
      <el-button type="primary" :loading="submitLoading" @click="handleSubmit">确定</el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ElMessage, type FormInstance } from 'element-plus'
import { computed, reactive, ref, watch } from 'vue'

import { waybillTemplateApi } from '@/api/waybill-template'
import type { WaybillCatalog, WaybillTemplateInfo } from '@/types/waybill'

const props = defineProps<{
  modelValue: boolean
  formData: Partial<WaybillTemplateInfo>
  catalog: WaybillCatalog
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'success'): void
}>()

const formRef = ref<FormInstance>()
const submitLoading = ref(false)
const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})
const isEdit = computed(() => !!props.formData?.id)

const form = reactive({
  name: '',
  express_code: '',
  express_name: '',
  exp_type: '1',
  exp_type_name: '',
  template_size: '',
  template_size_label: '默认模版',
  pay_type: 1,
  need_pickup: 0,
  is_default: 0,
  sort: 0,
  status: 1,
})

const rules = {
  name: [{ required: true, message: '请输入模版名称', trigger: 'blur' }],
  express_code: [{ required: true, message: '请选择物流公司', trigger: 'change' }],
  exp_type: [{ required: true, message: '请选择业务类型', trigger: 'change' }],
  pay_type: [{ required: true, message: '请选择邮费支付方式', trigger: 'change' }],
}

const expTypeOptions = computed(() => props.catalog[form.express_code]?.exp_types || [])
const templateSizeOptions = computed(() => props.catalog[form.express_code]?.template_sizes || [])

watch(
  () => props.formData,
  (data) => {
    Object.assign(form, {
      name: data.name || '',
      express_code: data.express_code || '',
      express_name: data.express_name || '',
      exp_type: data.exp_type || '1',
      exp_type_name: data.exp_type_name || '',
      template_size: data.template_size ?? '',
      template_size_label: data.template_size_label || '默认模版',
      pay_type: data.pay_type ?? 1,
      need_pickup: data.need_pickup ?? 0,
      is_default: data.is_default ?? 0,
      sort: data.sort ?? 0,
      status: data.status ?? 1,
    })
  },
  { deep: true, immediate: true }
)

function onCarrierChange(code: string) {
  const carrier = props.catalog[code]
  form.express_name = carrier?.name || code
  form.exp_type = carrier?.exp_types?.[0]?.value || '1'
  form.exp_type_name = carrier?.exp_types?.[0]?.label || form.exp_type
  form.template_size = carrier?.template_sizes?.[0]?.value ?? ''
  form.template_size_label = carrier?.template_sizes?.[0]?.label || '默认模版'
}

function onExpTypeChange(value: string) {
  const found = expTypeOptions.value.find((i) => i.value === value)
  form.exp_type_name = found?.label || value
}

function onTemplateSizeChange(value: string) {
  const found = templateSizeOptions.value.find((i) => i.value === value)
  form.template_size_label = found?.label || (value ? value : '默认模版')
}

function handleClose() {
  visible.value = false
}

async function handleSubmit() {
  if (!formRef.value) return
  await formRef.value.validate()
  submitLoading.value = true
  try {
    const payload = { ...form }
    if (isEdit.value && props.formData.id) {
      await waybillTemplateApi.update(props.formData.id, payload)
      ElMessage.success('更新成功')
    } else {
      await waybillTemplateApi.create(payload)
      ElMessage.success('创建成功')
    }
    emit('success')
    handleClose()
  } catch (e) {
    console.error(e)
  } finally {
    submitLoading.value = false
  }
}
</script>
