<template>
  <el-dialog
    v-model="visible"
    :title="isEdit ? '编辑商品分类' : '新增商品分类'"
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
      <el-form-item label="父级分类" prop="parent_id">
        <el-tree-select
          v-model="form.parent_id"
          :data="parentOptions"
          :props="({ label: 'name', value: 'id', children: 'children' } as any)"
          placeholder="请选择父级分类"
          check-strictly
          :render-after-expand="false"
          style="width: 100%"
        />
      </el-form-item>
      <el-form-item label="分类名称" prop="name">
        <el-input v-model="form.name" placeholder="请输入分类名称" />
      </el-form-item>
      <el-form-item label="分类图片" prop="icon">
        <ImageSelect v-model="form.icon" />
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
      <el-form-item label="是否前端展示" prop="is_show">
        <el-radio-group v-model="form.is_show">
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

<script setup lang="ts" name="GoodsCategoryForm">
import { ref, reactive, computed, watch } from 'vue'
import { ElMessage, ElForm } from 'element-plus'
import { goodsCategoryApi } from '@/api/goods-category'
import ImageSelect from '@/components/ImageSelect/index.vue'

interface Props {
  modelValue: boolean
  formData: Record<string, any>
  parentOptions: any[]
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
  parent_id: 0,
  name: '',
  icon: '',
  sort: 0,
  status: 1,
  is_show: 0,
})

const rules = {}

const submitLoading = ref(false)

watch(
  () => props.formData,
  (newData) => {
    if (newData) {
      Object.assign(form, {
        parent_id: newData.parent_id ?? 0,
        name: newData.name ?? '',
        icon: newData.icon ?? '',
        sort: newData.sort ?? 0,
        status: newData.status ?? 1,
        is_show: newData.is_show ?? 0,
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
      await goodsCategoryApi.update(props.formData.id, { ...form })
      ElMessage.success('编辑成功')
    } else {
      await goodsCategoryApi.create({ ...form })
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
