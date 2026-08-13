<template>
  <el-dialog
    v-model="visible"
    :title="isEdit ? '编辑品牌' : '新增品牌'"
    width="600px"
    :close-on-click-modal="false"
    @close="handleClose"
  >
    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-width="100px"
    >
      <el-form-item label="品牌名称" prop="name">
        <el-input v-model="form.name" placeholder="请输入品牌名称" />
      </el-form-item>
      <el-form-item label="Logo" prop="logo">
        <ImageSelect v-model="form.logo" />
      </el-form-item>
      <el-form-item label="描述" prop="description">
        <el-input v-model="form.description" type="textarea" :rows="3" placeholder="请输入描述" />
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
      <span class="dialog-footer">
        <el-button @click="handleClose">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">
          确定
        </el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup lang="ts" name="GoodsBrandForm">
import { ref, reactive, computed, watch } from 'vue'
import { ElMessage, ElForm } from 'element-plus'
import { goodsBrandApi } from '@/api/goods-brand'
import ImageSelect from '@/components/ImageSelect/index.vue'

interface Props {
  modelValue: boolean
  formData: Record<string, any>
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'success'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const formRef = ref<InstanceType<typeof ElForm>>()

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const isEdit = computed(() => !!props.formData?.id)

const form = reactive({
  name: '',
  logo: '',
  description: '',
  sort: 0,
  status: 1,
})

const rules = {}

const submitLoading = ref(false)

watch(
  () => props.formData,
  (newData) => {
    if (newData) {
      Object.assign(form, {
        name: newData.name ?? '',
        logo: newData.logo ?? '',
        description: newData.description ?? '',
        sort: newData.sort ?? 0,
        status: newData.status ?? 1,
      })
    }
  },
  { deep: true, immediate: true }
)

const handleSubmit = async () => {
  if (!formRef.value) return
  try {
    await formRef.value.validate()
    submitLoading.value = true
    if (isEdit.value && props.formData.id) {
      await goodsBrandApi.update(props.formData.id, { ...form })
      ElMessage.success('编辑成功')
    } else {
      await goodsBrandApi.create({ ...form })
      ElMessage.success('新增成功')
    }
    emit('success')
    handleClose()
  } catch (error) {
    console.error('提交失败:', error)
  } finally {
    submitLoading.value = false
  }
}

const handleClose = () => {
  formRef.value?.resetFields()
  visible.value = false
}
</script>

<style lang="scss" scoped>
.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
