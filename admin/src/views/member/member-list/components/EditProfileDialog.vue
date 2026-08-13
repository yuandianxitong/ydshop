<script setup lang="ts">
import { ref, watch, computed, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import { memberApi } from '@/api/member'

interface Props {
  modelValue: boolean
  userId: number | null
  initial?: Record<string, any> | null
}
interface Emits {
  (e: 'update:modelValue', v: boolean): void
  (e: 'saved'): void
}
const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const visible = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v),
})

const form = reactive({
  nickname: '',
  mobile: '',
  email: '',
  gender: 0 as 0 | 1 | 2,
  birthday: '',
})
const submitting = ref(false)

watch(() => props.modelValue, (v) => {
  if (v && props.initial) {
    form.nickname = props.initial.nickname || ''
    form.mobile   = props.initial.mobile || ''
    form.email    = props.initial.email || ''
    form.gender   = (props.initial.gender ?? 0) as 0 | 1 | 2
    form.birthday = props.initial.birthday || ''
  }
})

const submit = async () => {
  if (!props.userId) return
  submitting.value = true
  try {
    await memberApi.updateProfile(props.userId, { ...form })
    ElMessage.success('保存成功')
    emit('saved')
    visible.value = false
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <el-dialog v-model="visible" title="编辑资料" width="480px" append-to-body>
    <el-form label-width="80px">
      <el-form-item label="昵称">
        <el-input v-model="form.nickname" placeholder="昵称" />
      </el-form-item>
      <el-form-item label="手机号">
        <el-input v-model="form.mobile" placeholder="手机号" />
      </el-form-item>
      <el-form-item label="邮箱">
        <el-input v-model="form.email" placeholder="邮箱" />
      </el-form-item>
      <el-form-item label="性别">
        <el-radio-group v-model="form.gender">
          <el-radio :value="0">未知</el-radio>
          <el-radio :value="1">男</el-radio>
          <el-radio :value="2">女</el-radio>
        </el-radio-group>
      </el-form-item>
      <el-form-item label="生日">
        <el-date-picker v-model="form.birthday" type="date" value-format="YYYY-MM-DD" placeholder="选择生日" style="width: 100%" />
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="visible = false">取消</el-button>
      <el-button type="primary" :loading="submitting" @click="submit">保存</el-button>
    </template>
  </el-dialog>
</template>
