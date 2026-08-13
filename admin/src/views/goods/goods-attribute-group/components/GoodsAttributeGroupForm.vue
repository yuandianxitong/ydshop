<template>
  <el-dialog
    v-model="visible"
    :title="isEdit ? '编辑属性分组' : '新增属性分组'"
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
      <el-form-item label="分组名称" prop="name">
        <el-input v-model="form.name" placeholder="请输入分组名称" />
      </el-form-item>
      <el-form-item label="所属分类" prop="category_id">
        <el-tree-select
          v-model="form.category_id"
          :data="categoryTree"
          :props="({ label: 'name', value: 'id', children: 'children' } as any)"
          placeholder="请选择所属分类"
          clearable
          check-strictly
          :render-after-expand="false"
          style="width: 100%"
        />
      </el-form-item>
      <el-form-item label="排序" prop="sort">
        <el-input-number v-model="form.sort" :min="0" controls-position="right" />
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

<script setup lang="ts" name="GoodsAttributeGroupForm">
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { ElMessage, ElForm } from 'element-plus'
import { goodsAttributeGroupApi } from '@/api/goods-attribute-group'
import { goodsCategoryApi } from '@/api/goods-category'

// 分类树数据
const categoryTree = ref<Record<string, any>[]>([])

const loadCategoryTree = async () => {
  try {
    const res = await goodsCategoryApi.getTree()
    categoryTree.value = res.data || []
  } catch (error) {
    console.error('加载分类树失败:', error)
  }
}

onMounted(() => {
  loadCategoryTree()
})

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

// 表单引用
const formRef = ref<InstanceType<typeof ElForm>>()

// 弹窗显示状态
const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

// 是否编辑模式
const isEdit = computed(() => !!props.formData?.id)

// 表单数据
const form = reactive({
  name: '',
  category_id: undefined as number | undefined,
  sort: 0,
})

// 表单验证规则
const rules = {
}

// 提交加载状态
const submitLoading = ref(false)

// 监听表单数据变化
watch(
  () => props.formData,
  (newData) => {
    if (newData) {
      Object.assign(form, {
        name: newData.name ?? '',
        category_id: newData.category_id || undefined,
        sort: newData.sort ?? 0,
      })
    }
  },
  { deep: true, immediate: true }
)

// 提交表单
const handleSubmit = async () => {
  if (!formRef.value) return

  try {
    await formRef.value.validate()
    submitLoading.value = true

    if (isEdit.value && props.formData.id) {
      await goodsAttributeGroupApi.update(props.formData.id, { ...form })
      ElMessage.success('编辑成功')
    } else {
      await goodsAttributeGroupApi.create({ ...form })
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

// 关闭弹窗
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