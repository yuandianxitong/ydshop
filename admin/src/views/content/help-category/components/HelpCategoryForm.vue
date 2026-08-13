<template>
    <el-dialog
        :model-value="modelValue"
        :title="isEdit ? '编辑分类' : '新建分类'"
        width="520px"
        top="6vh"
        destroy-on-close
        :close-on-click-modal="false"
        @update:model-value="(v) => $emit('update:modelValue', v)"
        @opened="onOpened"
    >
        <el-form ref="formRef" v-loading="loading" :model="form" :rules="rules" label-width="100px">
            <el-form-item label="分类名称" prop="name">
                <el-input v-model="form.name" maxlength="100" show-word-limit placeholder="如：购物问题" />
            </el-form-item>
            <el-form-item label="图标">
                <div class="image-uploader-wrapper">
                    <ImageSelect
                        class="cat-icon-uploader"
                        :model-value="form.icon || ''"
                        @update:model-value="(v) => form.icon = v as string"
                    />
                    <div class="upload-tip">建议尺寸 64×64 PNG，透明背景</div>
                </div>
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

<script setup lang="ts" name="HelpCategoryForm">
import { computed, reactive, ref } from 'vue'
import { ElMessage, type FormInstance } from 'element-plus'
import { helpCategoryApi, type HelpCategoryInfo, type HelpCategoryReq } from '@/api/content/helpCategory'
import ImageSelect from '@/components/ImageSelect/index.vue'

const props = defineProps<{
    modelValue: boolean
    formData: Partial<HelpCategoryInfo> & { id?: number }
}>()
const emit = defineEmits<{ 'update:modelValue': [boolean]; success: [] }>()

const formRef = ref<FormInstance>()
const loading = ref(false)
const submitting = ref(false)

const isEdit = computed(() => !!props.formData?.id)

const form = reactive<HelpCategoryReq>({
    name: '',
    icon: '',
    sort: 0,
    status: 1,
})

const rules = {
    name: [
        { required: true, message: '分类名称必填', trigger: 'blur' },
        { max: 100, message: '最长 100 字符', trigger: 'blur' },
    ],
}

function resetForm() {
    Object.assign(form, { name: '', icon: '', sort: 0, status: 1 })
}

async function onOpened() {
    resetForm()
    if (!isEdit.value || !props.formData.id) return
    loading.value = true
    try {
        const res = await helpCategoryApi.getDetail(props.formData.id)
        if (res.code === 200) {
            Object.assign(form, {
                name: res.data.name,
                icon: res.data.icon,
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
            await helpCategoryApi.update(props.formData.id, form)
            ElMessage.success('更新成功')
        } else {
            await helpCategoryApi.create(form)
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
.image-uploader-wrapper {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.cat-icon-uploader :deep(.image-select-single),
.cat-icon-uploader :deep(.image-select-add) {
    width: 80px;
    height: 80px;
}

.upload-tip {
    font-size: 11.5px;
    color: var(--el-text-color-secondary);
    line-height: 1.4;
}
</style>
