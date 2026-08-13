<template>
    <el-dialog
        v-model="visible"
        title="导入表结构"
        width="600px"
        :close-on-click-modal="false"
        destroy-on-close
    >
        <el-upload
            ref="uploadRef"
            drag
            :auto-upload="false"
            accept=".sql"
            :limit="1"
            :on-change="handleFileChange"
            :on-exceed="handleExceed"
        >
            <i class="i-svg:upload text-48px text-ink-400" />
            <div class="el-upload__text">拖拽 SQL 文件到此处，或<em>点击上传</em></div>
            <template #tip>
                <div class="el-upload__tip">仅支持 .sql 文件，包含 CREATE TABLE 语句</div>
            </template>
        </el-upload>

        <template #footer>
            <el-button @click="handleClose">取消</el-button>
            <el-button type="primary" :loading="uploading" :disabled="!selectedFile" @click="handleImport">
                导入
            </el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import type { UploadFile, UploadInstance } from 'element-plus'
import { computed, ref } from 'vue'

import { generatorApi } from '@/api/generator'

interface Props {
    modelValue: boolean
}

interface Emits {
    (e: 'update:modelValue', v: boolean): void
    (e: 'success'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const visible = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
})

const uploadRef = ref<UploadInstance>()
const selectedFile = ref<File | null>(null)
const uploading = ref(false)

function handleFileChange(file: UploadFile) {
    selectedFile.value = file.raw || null
}

function handleExceed() {
    ElMessage.warning('只能上传一个 SQL 文件')
}

async function handleImport() {
    if (!selectedFile.value) return
    uploading.value = true
    try {
        const res = await generatorApi.importSql(selectedFile.value)
        const count = res.data?.length || 0
        ElMessage.success(`导入成功，共解析 ${count} 张表`)
        emit('success')
        handleClose()
    } catch {
        ElMessage.error('导入失败，请检查 SQL 文件格式')
    } finally {
        uploading.value = false
    }
}

function handleClose() {
    selectedFile.value = null
    visible.value = false
}
</script>
