<template>
    <el-dialog
        :model-value="modelValue"
        :title="isEdit ? '编辑广告位' : '新建广告位'"
        width="560px"
        top="6vh"
        destroy-on-close
        :close-on-click-modal="false"
        @update:model-value="(v) => $emit('update:modelValue', v)"
        @opened="onOpened"
    >
        <el-form ref="formRef" v-loading="loading" :model="form" :rules="rules" label-width="100px">
            <el-form-item label="位置名称" prop="name">
                <el-input v-model="form.name" maxlength="100" show-word-limit placeholder="例如：首页顶部 Banner" />
            </el-form-item>
            <el-form-item label="位置编码" prop="code">
                <el-input v-model="form.code" maxlength="64" :disabled="isEdit" placeholder="home_top_banner" />
                <span class="field-hint">C 端按此取广告位，创建后不可修改</span>
            </el-form-item>
            <el-form-item label="描述">
                <el-input v-model="form.description" type="textarea" :rows="2" maxlength="255" show-word-limit />
            </el-form-item>
            <el-form-item label="推荐尺寸">
                <div class="flex items-center gap-2">
                    <el-input-number v-model="form.recommended_width" :min="0" :max="9999" :step="10" /> ×
                    <el-input-number v-model="form.recommended_height" :min="0" :max="9999" :step="10" />
                    <span class="field-hint">px，0=不限</span>
                </div>
            </el-form-item>
            <el-form-item label="是否轮播">
                <el-radio-group v-model="form.is_carousel">
                    <el-radio :value="1">是（多广告轮播）</el-radio>
                    <el-radio :value="0">否（仅取 sort 最大）</el-radio>
                </el-radio-group>
            </el-form-item>
            <el-form-item label="排序">
                <el-input-number v-model="form.sort" :min="0" :max="9999" />
            </el-form-item>
            <el-form-item label="状态">
                <el-radio-group v-model="form.status">
                    <el-radio :value="1">启用</el-radio>
                    <el-radio :value="0">停用</el-radio>
                </el-radio-group>
            </el-form-item>
        </el-form>
        <template #footer>
            <el-button @click="$emit('update:modelValue', false)">取消</el-button>
            <el-button type="primary" :loading="submitting" @click="handleSubmit">确定</el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts" name="AdPositionForm">
import { ref, reactive, computed } from 'vue'
import { ElMessage, type FormInstance } from 'element-plus'
import { adPositionApi, type AdPositionInfo, type AdPositionReq } from '@/api/marketing/adPosition'

const props = defineProps<{
    modelValue: boolean
    formData: Partial<AdPositionInfo> & { id?: number }
}>()

const emit = defineEmits<{
    'update:modelValue': [boolean]
    success: []
}>()

const formRef = ref<FormInstance>()
const loading = ref(false)
const submitting = ref(false)

const isEdit = computed(() => !!props.formData?.id)

const form = reactive<AdPositionReq>({
    code: '',
    name: '',
    description: '',
    recommended_width: 0,
    recommended_height: 0,
    is_carousel: 1,
    sort: 0,
    status: 1,
})

const rules = {
    name: [{ required: true, message: '位置名称必填', trigger: 'blur' }],
    code: [
        { required: true, message: '位置编码必填', trigger: 'blur' },
        { pattern: /^[a-zA-Z0-9_]+$/, message: '只能字母数字下划线', trigger: 'blur' },
    ],
}

function resetForm() {
    Object.assign(form, {
        code: '',
        name: '',
        description: '',
        recommended_width: 0,
        recommended_height: 0,
        is_carousel: 1,
        sort: 0,
        status: 1,
    })
}

async function onOpened() {
    resetForm()
    if (!isEdit.value || !props.formData.id) return
    loading.value = true
    try {
        const res = await adPositionApi.getDetail(props.formData.id)
        if (res.code === 200) {
            Object.assign(form, {
                code: res.data.code,
                name: res.data.name,
                description: res.data.description || '',
                recommended_width: res.data.recommended_width,
                recommended_height: res.data.recommended_height,
                is_carousel: res.data.is_carousel,
                sort: res.data.sort,
                status: res.data.status,
            })
        }
    } finally {
        loading.value = false
    }
}

async function handleSubmit() {
    if (!formRef.value) return
    await formRef.value.validate()
    submitting.value = true
    try {
        if (isEdit.value && props.formData.id) {
            const { code, ...payload } = form
            await adPositionApi.update(props.formData.id, payload)
            ElMessage.success('更新成功')
        } else {
            await adPositionApi.create(form)
            ElMessage.success('创建成功')
        }
        emit('success')
        emit('update:modelValue', false)
    } finally {
        submitting.value = false
    }
}
</script>

<style scoped lang="scss">
.field-hint {
    margin-left: 8px;
    font-size: 12px;
    color: var(--ink-400, #909399);
}
</style>
