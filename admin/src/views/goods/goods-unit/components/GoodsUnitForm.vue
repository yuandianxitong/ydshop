<template>
  <el-dialog
    :model-value="modelValue"
    :title="isEdit ? '编辑单位' : '新建单位'"
    width="540px"
    :close-on-click-modal="false"
    @update:model-value="emit('update:modelValue', $event)"
    @close="handleClose"
  >
    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-width="100px"
    >
      <el-form-item label="单位编码" prop="code">
        <el-input
          v-model="form.code"
          placeholder="如 kg / g / pc"
          maxlength="40"
          :disabled="isEdit && !!form.code"
          style="width: 240px"
        />
        <div class="field-hint">唯一编码，建议使用国际标准缩写</div>
      </el-form-item>

      <el-form-item label="单位名称" prop="name">
        <el-input
          v-model="form.name"
          placeholder="如 千克 / 克 / 件"
          maxlength="20"
          style="width: 240px"
        />
      </el-form-item>

      <el-form-item label="英文名">
        <el-input
          v-model="form.name_en"
          placeholder="kilogram / gram"
          maxlength="40"
          style="width: 240px"
        />
      </el-form-item>

      <el-form-item label="所属分组" prop="group_id">
        <el-select
          v-model="form.group_id"
          placeholder="请选择分组"
          style="width: 240px"
        >
          <el-option
            v-for="g in groupOptions"
            :key="g.id"
            :label="g.name"
            :value="g.id"
          />
        </el-select>
      </el-form-item>

      <el-form-item label="基准单位">
        <el-switch v-model="form.is_base" :active-value="1" :inactive-value="0" />
        <span class="field-hint">每个分组只能有一个基准单位</span>
      </el-form-item>

      <el-form-item v-if="!form.is_base" label="换算系数">
        <el-input-number
          v-model="form.ratio"
          :min="0"
          :precision="6"
          :step="1"
          style="width: 240px"
        />
        <div class="field-hint">1 当前单位 = X 基准单位</div>
      </el-form-item>

      <el-form-item label="小数位">
        <el-input-number
          v-model="form.decimal_places"
          :min="0"
          :max="6"
          :precision="0"
          style="width: 160px"
        />
      </el-form-item>

      <el-form-item label="排序">
        <el-input-number
          v-model="form.sort"
          :min="0"
          :precision="0"
          style="width: 160px"
        />
      </el-form-item>

      <el-form-item label="状态">
        <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="handleClose">取消</el-button>
      <el-button type="primary" :loading="loading" @click="handleSubmit">确定</el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { reactive, ref, watch } from 'vue'

import { goodsUnitApi, type GoodsUnitGroupInfo } from '@/api/goods-unit'

const props = defineProps<{
  modelValue: boolean
  formData: Record<string, any>
  groupOptions: GoodsUnitGroupInfo[]
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  success: []
}>()

const formRef = ref<FormInstance>()
const loading = ref(false)
const isEdit = ref(false)

const defaultForm = () => ({
  id: 0,
  code: '',
  name: '',
  name_en: '',
  group_id: undefined as number | undefined,
  decimal_places: 2,
  is_base: 0,
  ratio: 1,
  sort: 0,
  status: 1,
})

const form = reactive(defaultForm())

const rules: FormRules = {
  code: [{ required: true, message: '请输入单位编码', trigger: 'blur' }],
  name: [{ required: true, message: '请输入单位名称', trigger: 'blur' }],
  group_id: [{ required: true, message: '请选择所属分组', trigger: 'change' }],
}

watch(
  () => props.modelValue,
  (val) => {
    if (val) {
      Object.assign(form, defaultForm(), props.formData)
      isEdit.value = !!props.formData.id
      formRef.value?.clearValidate()
    }
  }
)

const handleClose = () => {
  emit('update:modelValue', false)
}

const handleSubmit = async () => {
  if (!formRef.value) return
  await formRef.value.validate()
  loading.value = true
  try {
    const payload = {
      code: form.code,
      name: form.name,
      name_en: form.name_en,
      group_id: form.group_id,
      decimal_places: form.decimal_places,
      is_base: form.is_base,
      ratio: form.ratio,
      sort: form.sort,
      status: form.status,
    }
    if (isEdit.value) {
      await goodsUnitApi.update(form.id, payload)
      ElMessage.success('更新成功')
    } else {
      await goodsUnitApi.create(payload)
      ElMessage.success('创建成功')
    }
    emit('success')
    handleClose()
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.field-hint {
  margin-left: 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
</style>
