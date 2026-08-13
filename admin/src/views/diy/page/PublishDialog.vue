<script setup lang="ts">
import { ref } from 'vue'

defineProps<{ pageTitle: string }>()
const emit = defineEmits<{
  confirm: [note: string]
  close: []
}>()

const visible = ref(true)
const note = ref('')
const submitting = ref(false)

const onConfirm = async () => {
  submitting.value = true
  try {
    emit('confirm', note.value.trim())
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <el-dialog
    v-model="visible"
    title="发布页面"
    width="420px"
    @close="emit('close')"
    :close-on-click-modal="false"
    append-to-body
  >
    <p class="mb-3 text-sm">
      将「<b>{{ pageTitle }}</b>」发布到线上,并保存为一个历史版本。
    </p>
    <el-input
      v-model="note"
      type="textarea"
      :rows="3"
      placeholder="备注(可选,如:春季首页改版)"
      maxlength="255"
      show-word-limit
    />
    <template #footer>
      <el-button @click="emit('close')">取消</el-button>
      <el-button type="primary" :loading="submitting" @click="onConfirm">确定发布</el-button>
    </template>
  </el-dialog>
</template>
