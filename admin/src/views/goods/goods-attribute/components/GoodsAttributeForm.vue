<template>
  <el-dialog
    v-model="visible"
    :title="isEdit ? '编辑属性' : '新增属性'"
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
      <el-form-item label="所属分组" prop="group_id">
        <el-select
          v-model="form.group_id"
          placeholder="请选择所属分组"
          clearable
          filterable
          style="width: 100%"
        >
          <el-option
            v-for="item in groupList"
            :key="item.id"
            :label="groupLabel(item)"
            :value="item.id"
          />
        </el-select>
      </el-form-item>
      <el-form-item label="属性名称" prop="name">
        <el-input v-model="form.name" placeholder="请输入属性名称" />
      </el-form-item>
      <el-form-item label="属性类型" prop="type">
        <el-select v-model="form.type" placeholder="请选择属性类型" style="width: 100%">
          <el-option label="输入框" value="input" />
          <el-option label="单选" value="select" />
          <el-option label="多选" value="multi_select" />
        </el-select>
      </el-form-item>
      <el-form-item
        v-if="form.type === 'select' || form.type === 'multi_select'"
        label="预设值"
        prop="options"
      >
        <el-select
          v-model="form.options"
          multiple
          filterable
          allow-create
          default-first-option
          collapse-tags
          collapse-tags-tooltip
          placeholder="输入后回车添加预设值"
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

<script setup lang="ts" name="GoodsAttributeForm">
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { ElMessage, ElForm } from 'element-plus'
import { goodsAttributeApi } from '@/api/goods-attribute'
import { goodsAttributeGroupApi } from '@/api/goods-attribute-group'
import { goodsCategoryApi } from '@/api/goods-category'

const groupList = ref<Record<string, any>[]>([])
const categoryMap = ref<Record<number, string>>({})

const loadCategoryMap = async () => {
  try {
    const res = await goodsCategoryApi.getTree()
    const map: Record<number, string> = {}
    const flatten = (nodes: any[]) => {
      for (const n of nodes || []) {
        map[n.id] = n.name
        if (n.children?.length) flatten(n.children)
      }
    }
    flatten(res.data || [])
    categoryMap.value = map
  } catch (error) {
    console.error('加载商品分类失败:', error)
  }
}

const loadGroupList = async () => {
  try {
    const res = await goodsAttributeGroupApi.getList({ limit: 200 })
    groupList.value = res.data?.list || []
  } catch (error) {
    console.error('加载属性分组失败:', error)
  }
}

const groupLabel = (item: Record<string, any>) => {
  const cat = categoryMap.value[item.category_id]
  return cat ? `${cat} / ${item.name}` : item.name
}

const parseOptions = (raw: unknown): string[] => {
  if (Array.isArray(raw)) {
    return raw.map((v) => String(v).trim()).filter(Boolean)
  }
  if (typeof raw === 'string' && raw.trim()) {
    try {
      const decoded = JSON.parse(raw)
      if (Array.isArray(decoded)) {
        return decoded.map((v) => String(v).trim()).filter(Boolean)
      }
    } catch {
      // ignore
    }
    return raw
      .split(/[,，]/)
      .map((v) => v.trim())
      .filter(Boolean)
  }
  return []
}

onMounted(async () => {
  await loadCategoryMap()
  await loadGroupList()
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

const formRef = ref<InstanceType<typeof ElForm>>()

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const isEdit = computed(() => !!props.formData?.id)

const form = reactive({
  group_id: undefined as number | undefined,
  name: '',
  type: '',
  options: [] as string[],
  sort: 0,
})

const rules = {
  group_id: [{ required: true, message: '请选择所属分组', trigger: 'change' }],
  name: [{ required: true, message: '请输入属性名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择属性类型', trigger: 'change' }],
}

const submitLoading = ref(false)

watch(
  () => props.formData,
  (newData) => {
    if (newData) {
      Object.assign(form, {
        group_id: newData.group_id || undefined,
        name: newData.name ?? '',
        type: newData.type ?? '',
        options: parseOptions(newData.options),
        sort: newData.sort ?? 0,
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

    const payload = {
      group_id: form.group_id,
      name: form.name,
      type: form.type,
      options:
        form.type === 'select' || form.type === 'multi_select' ? form.options : [],
      sort: form.sort,
    }

    if (isEdit.value && props.formData.id) {
      await goodsAttributeApi.update(props.formData.id, payload)
      ElMessage.success('编辑成功')
    } else {
      await goodsAttributeApi.create(payload)
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
  form.options = []
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
