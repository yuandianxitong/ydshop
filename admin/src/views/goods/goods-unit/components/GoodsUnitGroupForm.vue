<template>
  <el-dialog
    :model-value="modelValue"
    :title="isEdit ? '编辑分组' : '新建分组'"
    width="460px"
    :close-on-click-modal="false"
    @update:model-value="emit('update:modelValue', $event)"
    @close="handleClose"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
      <el-form-item label="分组编码" prop="code">
        <el-input
          v-model="form.code"
          placeholder="如 weight / length"
          maxlength="40"
          :disabled="isEdit && !!form.code"
          style="width: 240px"
        />
      </el-form-item>

      <el-form-item label="分组名称" prop="name">
        <el-input v-model="form.name" placeholder="如 重量 / 长度" maxlength="40" style="width: 240px" />
      </el-form-item>

      <el-form-item label="色调">
        <div class="tone-picker">
          <span
            v-for="t in toneOptions"
            :key="t.value"
            class="tone-swatch"
            :class="{ on: form.tone === t.value }"
            :style="{ background: t.color }"
            :title="t.label"
            @click="form.tone = t.value"
          />
        </div>
      </el-form-item>

      <el-form-item label="排序">
        <el-input-number v-model="form.sort" :min="0" :precision="0" style="width: 160px" />
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

import { goodsUnitGroupApi } from '@/api/goods-unit'

const props = defineProps<{
  modelValue: boolean
  formData: Record<string, any>
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  success: []
}>()

const formRef = ref<FormInstance>()
const loading = ref(false)
const isEdit = ref(false)

const toneOptions = [
  { value: 'rose', label: '玫瑰', color: '#f43f5e' },
  { value: 'blue', label: '蓝', color: '#3b82f6' },
  { value: 'cyan', label: '青', color: '#0891b2' },
  { value: 'violet', label: '紫', color: '#7c3aed' },
  { value: 'amber', label: '琥珀', color: '#f59e0b' },
  { value: 'teal', label: '青绿', color: '#14b8a6' },
]

const defaultForm = () => ({
  id: 0,
  code: '',
  name: '',
  tone: 'blue',
  sort: 0,
  status: 1,
})

const form = reactive(defaultForm())

const rules: FormRules = {
  code: [{ required: true, message: '请输入分组编码', trigger: 'blur' }],
  name: [{ required: true, message: '请输入分组名称', trigger: 'blur' }],
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
      tone: form.tone,
      sort: form.sort,
      status: form.status,
    }
    if (isEdit.value) {
      await goodsUnitGroupApi.update(form.id, payload)
      ElMessage.success('更新成功')
    } else {
      await goodsUnitGroupApi.create(payload)
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
.tone-picker {
  display: flex;
  gap: 8px;
  align-items: center;
}

.tone-swatch {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  cursor: pointer;
  border: 2px solid transparent;
  transition: all 0.15s;

  &:hover {
    transform: scale(1.08);
  }

  &.on {
    border-color: #fff;
    box-shadow: 0 0 0 2px var(--ink-800);
  }
}
</style>
