<template>
  <el-dialog
    v-model="visible"
    :title="isEdit ? '编辑规格模板' : '新增规格模板'"
    width="680px"
    :close-on-click-modal="false"
    destroy-on-close
    @close="handleClose"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
      <el-form-item label="模板名称" prop="name">
        <el-input v-model="form.name" placeholder="如：服装通用（颜色 + 尺码）" maxlength="60" show-word-limit />
      </el-form-item>
      <el-form-item label="备注">
        <el-input v-model="form.description" placeholder="选填" maxlength="255" show-word-limit />
      </el-form-item>

      <el-form-item label="规格项">
        <div class="items-editor">
          <div v-for="(item, idx) in form.items" :key="idx" class="item-row">
            <div class="item-head">
              <el-input
                v-model="item.name"
                placeholder="规格名，如：颜色 / 尺寸"
                style="width: 220px"
              />
              <el-button type="danger" size="small" text @click="removeItem(idx)">删除规格</el-button>
            </div>
            <div class="item-values">
              <el-tag
                v-for="(v, vi) in item.values"
                :key="vi"
                closable
                class="value-tag"
                @close="removeValue(idx, vi)"
              >
                {{ v }}
              </el-tag>
              <el-input
                v-if="valueInputVisible[idx]"
                :ref="(el) => setValueInputRef(el, idx)"
                v-model="valueInputs[idx]"
                size="small"
                style="width: 120px"
                @keyup.enter="confirmValue(idx)"
                @blur="confirmValue(idx)"
              />
              <el-button v-else size="small" @click="showValueInput(idx)">+ 添加值</el-button>
            </div>
          </div>
          <el-button plain style="margin-top: 8px" @click="addItem">
            <i class="i-lucide:plus mr-1" /> 添加规格
          </el-button>
        </div>
      </el-form-item>

      <el-form-item label="排序">
        <el-input-number v-model="form.sort" :min="0" :max="9999" controls-position="right" />
      </el-form-item>
      <el-form-item label="状态">
        <el-switch
          :model-value="form.status === 1"
          @update:model-value="(v: string | number | boolean) => form.status = v ? 1 : 0"
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <span class="dialog-footer">
        <el-button @click="handleClose">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">确定</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup lang="ts" name="GoodsSpecTemplateForm">
import { ref, reactive, computed, watch, nextTick } from 'vue'
import { ElMessage, ElForm } from 'element-plus'
import { goodsSpecTemplateApi, type GoodsSpecTemplateItem } from '@/api/goods-spec-template'

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
  set: (v) => emit('update:modelValue', v),
})
const isEdit = computed(() => !!props.formData?.id)
const submitLoading = ref(false)

const form = reactive({
  name: '',
  description: '',
  items: [] as GoodsSpecTemplateItem[],
  sort: 0,
  status: 1,
})

const rules = {
  name: [{ required: true, message: '请输入模板名称', trigger: 'blur' }],
}

// 值输入状态（每行独立）
const valueInputVisible = ref<Record<number, boolean>>({})
const valueInputs = ref<Record<number, string>>({})
const valueInputRefs = ref<Record<number, any>>({})

watch(
  () => props.formData,
  (data) => {
    if (data) {
      Object.assign(form, {
        name: data.name ?? '',
        description: data.description ?? '',
        items: (data.items || []).map((i: GoodsSpecTemplateItem) => ({
          name: i.name,
          values: [...(i.values || [])],
        })),
        sort: data.sort ?? 0,
        status: data.status ?? 1,
      })
    }
  },
  { deep: true, immediate: true },
)

const addItem = () => {
  form.items.push({ name: '', values: [] })
}
const removeItem = (idx: number) => {
  form.items.splice(idx, 1)
}
const removeValue = (idx: number, vi: number) => {
  form.items[idx].values.splice(vi, 1)
}
const showValueInput = (idx: number) => {
  valueInputVisible.value[idx] = true
  valueInputs.value[idx] = ''
  nextTick(() => valueInputRefs.value[idx]?.focus?.())
}
const setValueInputRef = (el: any, idx: number) => {
  if (el) valueInputRefs.value[idx] = el
}
const confirmValue = (idx: number) => {
  const v = (valueInputs.value[idx] || '').trim()
  if (v && !form.items[idx].values.includes(v)) {
    form.items[idx].values.push(v)
  }
  valueInputVisible.value[idx] = false
  valueInputs.value[idx] = ''
}

const handleSubmit = async () => {
  if (!formRef.value) return
  await formRef.value.validate()
  // 前端先粗清洗（后端会再 normalize 一次）
  const cleanItems = form.items
    .map(i => ({ name: i.name.trim(), values: i.values.map(v => v.trim()).filter(Boolean) }))
    .filter(i => i.name && i.values.length)
  if (!cleanItems.length) {
    ElMessage.warning('至少添加一个规格项及其值')
    return
  }

  submitLoading.value = true
  try {
    const payload = {
      name: form.name,
      description: form.description,
      items: cleanItems,
      sort: form.sort,
      status: form.status,
    }
    if (isEdit.value && props.formData.id) {
      await goodsSpecTemplateApi.update(props.formData.id, payload)
      ElMessage.success('更新成功')
    } else {
      await goodsSpecTemplateApi.create(payload)
      ElMessage.success('创建成功')
    }
    emit('success')
    handleClose()
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

.items-editor {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.item-row {
  padding: 12px 14px;
  border: 1px solid var(--ink-100);
  border-radius: 6px;
  background: var(--ink-50);
}

.item-head {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}

.item-values {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
}

.value-tag { margin: 0; }
</style>
