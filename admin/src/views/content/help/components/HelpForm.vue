<template>
  <el-dialog
    :model-value="modelValue"
    :title="isEdit ? '编辑帮助' : '新建帮助'"
    width="820px"
    top="4vh"
    destroy-on-close
    :close-on-click-modal="false"
    @update:model-value="(v) => $emit('update:modelValue', v)"
    @opened="onOpened"
  >
    <el-form ref="formRef" v-loading="loading" :model="form" :rules="rules" label-width="100px">
      <el-form-item label="分类" prop="category_id">
        <el-select v-model="form.category_id" placeholder="请选择分类" style="width:320px">
          <el-option v-for="c in categories" :key="c.id" :label="c.name" :value="c.id" />
        </el-select>
      </el-form-item>

      <el-form-item label="标题" prop="title">
        <el-input v-model="form.title" maxlength="200" show-word-limit placeholder="例如：如何修改收货地址？" />
      </el-form-item>

      <el-form-item label="摘要">
        <el-input
          v-model="form.summary"
          type="textarea"
          :rows="2"
          maxlength="500"
          show-word-limit
          placeholder="列表展示用，可空"
        />
      </el-form-item>

      <el-form-item label="内容" prop="content">
        <div class="editor-wrapper">
          <Editor v-model="form.content" height="400px" style="width: 100%" />
        </div>
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="$emit('update:modelValue', false)">取消</el-button>
      <el-button :loading="submitting" @click="handleSubmit(0)">存草稿</el-button>
      <el-button type="primary" :loading="submitting" @click="handleSubmit(1)">发布</el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts" name="HelpForm">
import { ref, reactive, computed } from 'vue'
import { ElMessage, type FormInstance } from 'element-plus'
import Editor from '@/components/Editor/index.vue'
import { helpApi, type HelpInfo, type HelpReq } from '@/api/content/help'
import { helpCategoryApi, type HelpCategoryInfo } from '@/api/content/helpCategory'

const props = defineProps<{
  modelValue: boolean
  formData: Partial<HelpInfo> & { id?: number }
}>()
const emit = defineEmits<{ 'update:modelValue': [boolean]; success: [] }>()

const formRef = ref<FormInstance>()
const loading = ref(false)
const submitting = ref(false)
const categories = ref<HelpCategoryInfo[]>([])

const isEdit = computed(() => !!props.formData?.id)

const form = reactive<HelpReq>({
  category_id: 0,
  title: '',
  summary: '',
  content: '',
  status: 0,
})

const rules = {
  category_id: [{ required: true, message: '请选择分类', trigger: 'change' }],
  title: [
    { required: true, message: '标题必填', trigger: 'blur' },
    { max: 200, message: '最长 200 字符', trigger: 'blur' },
  ],
  content: [{ required: true, message: '内容必填', trigger: 'blur' }],
}

function resetForm() {
  Object.assign(form, { category_id: 0, title: '', summary: '', content: '', status: 0 })
}

async function loadCategories() {
  const res = await helpCategoryApi.getAll()
  if (res.code === 200) categories.value = res.data || []
}

async function onOpened() {
  resetForm()
  if (!categories.value.length) await loadCategories()
  if (!isEdit.value || !props.formData.id) return
  loading.value = true
  try {
    const res = await helpApi.getDetail(props.formData.id)
    if (res.code === 200) {
      Object.assign(form, {
        category_id: res.data.category_id,
        title: res.data.title,
        summary: res.data.summary || '',
        content: res.data.content || '',
        status: res.data.status,
      })
    }
  } finally {
    loading.value = false
  }
}

async function handleSubmit(saveStatus: 0 | 1) {
  form.status = saveStatus
  if (!formRef.value) return
  await formRef.value.validate()
  submitting.value = true
  try {
    if (isEdit.value && props.formData.id) {
      await helpApi.update(props.formData.id, form)
      ElMessage.success('已保存')
    } else {
      await helpApi.create(form)
      ElMessage.success('已创建')
    }
    emit('success')
    emit('update:modelValue', false)
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped lang="scss">
.editor-wrapper {
  width: 100%;
  border: 1px solid var(--el-border-color, #dcdfe6);
  border-radius: 4px;
  overflow: hidden;
}
</style>
