<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { memberApi, type SmsTemplate } from '@/api/member'

interface Props {
  modelValue: boolean
  userId: number | null
  mobile?: string
}
interface Emits {
  (e: 'update:modelValue', v: boolean): void
  (e: 'sent'): void
}
const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const visible = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v),
})

const templates = ref<SmsTemplate[]>([])
const loading = ref(false)
const submitting = ref(false)
const selectedCode = ref<string>('')
const variables = ref<Record<string, string>>({})

const currentTpl = computed(() => templates.value.find(t => t.code === selectedCode.value))

const fetchTemplates = async () => {
  loading.value = true
  try {
    const res = await memberApi.listSmsTemplates()
    templates.value = (res.data?.list as any) || []
  } finally {
    loading.value = false
  }
}

watch(() => props.modelValue, (v) => {
  if (v) {
    selectedCode.value = ''
    variables.value = {}
    fetchTemplates()
  }
})

watch(selectedCode, () => {
  variables.value = {}
  const tpl = currentTpl.value as any
  const list = (tpl?.variables || []) as string[]
  list.forEach(k => { variables.value[k] = '' })
})

const submit = async () => {
  if (!props.userId) return
  if (!selectedCode.value) {
    ElMessage.warning('请选择短信模板')
    return
  }
  submitting.value = true
  try {
    await memberApi.sendSms(props.userId, { template_code: selectedCode.value, variables: variables.value })
    ElMessage.success('已发送')
    emit('sent')
    visible.value = false
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <el-dialog v-model="visible" title="发送短信" width="480px" append-to-body>
    <el-form v-loading="loading" label-width="80px">
      <el-form-item label="接收方">
        <span class="num">{{ mobile || '—' }}</span>
        <span v-if="!mobile" class="text-[12px] text-ink-400 ml-2">该用户未绑定手机号</span>
      </el-form-item>
      <el-form-item label="短信模板">
        <el-select v-model="selectedCode" placeholder="请选择模板" style="width: 100%">
          <el-option v-for="t in templates" :key="t.code" :label="`${t.name} (${t.code})`" :value="t.code" />
        </el-select>
      </el-form-item>
      <template v-if="currentTpl && (currentTpl as any).variables?.length">
        <el-form-item v-for="key in ((currentTpl as any).variables as string[])" :key="key" :label="key">
          <el-input v-model="variables[key]" :placeholder="`输入 ${key}`" />
        </el-form-item>
      </template>
      <div v-if="!templates.length && !loading" class="text-[12px] text-ink-400 pl-[80px]">暂无可用短信模板，请先在「消息推送 - 模板管理」配置</div>
    </el-form>
    <template #footer>
      <el-button @click="visible = false">取消</el-button>
      <el-button type="primary" :loading="submitting" :disabled="!mobile" @click="submit">发送</el-button>
    </template>
  </el-dialog>
</template>
