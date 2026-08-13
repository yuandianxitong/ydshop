<template>
    <el-dialog
        :model-value="modelValue"
        :title="isEdit ? '编辑广告' : '新建广告'"
        width="640px"
        top="6vh"
        destroy-on-close
        :close-on-click-modal="false"
        @update:model-value="(v) => $emit('update:modelValue', v)"
        @opened="onOpened"
    >
        <el-form ref="formRef" v-loading="loading" :model="form" :rules="rules" label-width="100px">
            <el-form-item label="广告位" prop="position_id">
                <el-select v-model="form.position_id" placeholder="请选择" style="width: 100%">
                    <el-option
                        v-for="p in positions"
                        :key="p.id"
                        :label="`${p.name} (${p.code})`"
                        :value="p.id"
                    />
                </el-select>
                <span v-if="positionHint" class="field-hint">{{ positionHint }}</span>
            </el-form-item>
            <el-form-item label="广告名" prop="title">
                <el-input
                    v-model="form.title"
                    maxlength="100"
                    show-word-limit
                    placeholder="admin 标识用，C 端不展示"
                />
            </el-form-item>
            <el-form-item label="图片" prop="image">
                <div class="image-uploader-wrapper">
                    <ImageSelect
                        class="ad-image-uploader"
                        :model-value="form.image"
                        @update:model-value="(v) => form.image = v as string"
                    />
                    <div class="upload-tip">建议参考广告位推荐尺寸，PNG / JPG</div>
                </div>
            </el-form-item>
            <el-form-item label="跳转链接">
                <el-input
                    v-model="form.link"
                    maxlength="500"
                    placeholder="留空 = 无跳转。站内 /goods/123、外链 https://..."
                />
            </el-form-item>
            <el-form-item label="上架时间">
                <el-date-picker
                    v-model="form.start_at"
                    type="datetime"
                    placeholder="留空 = 立即生效"
                    value-format="YYYY-MM-DD HH:mm:ss"
                    style="width: 100%"
                />
            </el-form-item>
            <el-form-item label="下架时间">
                <el-date-picker
                    v-model="form.end_at"
                    type="datetime"
                    placeholder="留空 = 永久"
                    value-format="YYYY-MM-DD HH:mm:ss"
                    style="width: 100%"
                />
            </el-form-item>
            <el-form-item label="排序">
                <el-input-number v-model="form.sort" :min="0" :max="9999" />
                <span class="field-hint">同位置内 desc 先出</span>
            </el-form-item>
            <el-form-item label="状态">
                <el-radio-group v-model="form.status">
                    <el-radio :value="1">启用</el-radio>
                    <el-radio :value="0">禁用</el-radio>
                </el-radio-group>
            </el-form-item>
        </el-form>
        <template #footer>
            <el-button @click="$emit('update:modelValue', false)">取消</el-button>
            <el-button type="primary" :loading="submitting" @click="handleSubmit">确定</el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts" name="AdForm">
import { ref, reactive, computed } from 'vue'
import { ElMessage, type FormInstance } from 'element-plus'
import { adApi, type AdInfo, type AdReq } from '@/api/marketing/ad'
import type { AdPositionInfo } from '@/api/marketing/adPosition'
import ImageSelect from '@/components/ImageSelect/index.vue'

const props = defineProps<{
    modelValue: boolean
    formData: Partial<AdInfo> & { id?: number }
    positions: AdPositionInfo[]
}>()

const emit = defineEmits<{
    'update:modelValue': [boolean]
    success: []
}>()

const formRef = ref<FormInstance>()
const loading = ref(false)
const submitting = ref(false)

const isEdit = computed(() => !!props.formData?.id)

const form = reactive<AdReq>({
    position_id: 0,
    title: '',
    image: '',
    link: '',
    start_at: null,
    end_at: null,
    sort: 0,
    status: 1,
})

const rules = {
    position_id: [{ required: true, message: '请选择广告位', trigger: 'change' }],
    title: [
        { required: true, message: '广告名必填', trigger: 'blur' },
        { max: 100, message: '最长 100 字符', trigger: 'blur' },
    ],
    image: [{ required: true, message: '请上传图片', trigger: 'change' }],
}

const currentPosition = computed(() => props.positions.find((p) => p.id === form.position_id))
const positionHint = computed(() => {
    const p = currentPosition.value
    if (!p || (!p.recommended_width && !p.recommended_height)) return ''
    return `建议尺寸 ${p.recommended_width}×${p.recommended_height}`
})

function resetForm() {
    Object.assign(form, {
        position_id: props.positions[0]?.id ?? 0,
        title: '',
        image: '',
        link: '',
        start_at: null,
        end_at: null,
        sort: 0,
        status: 1,
    })
}

async function onOpened() {
    resetForm()
    if (!isEdit.value || !props.formData.id) return
    loading.value = true
    try {
        const res = await adApi.getDetail(props.formData.id)
        if (res.code === 200) {
            Object.assign(form, {
                position_id: res.data.position_id,
                title: res.data.title,
                image: res.data.image,
                link: res.data.link || '',
                start_at: res.data.start_at,
                end_at: res.data.end_at,
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
    if (form.start_at && form.end_at && new Date(form.start_at) >= new Date(form.end_at)) {
        ElMessage.warning('上架时间不能晚于下架时间')
        return
    }
    submitting.value = true
    try {
        if (isEdit.value && props.formData.id) {
            await adApi.update(props.formData.id, form)
            ElMessage.success('更新成功')
        } else {
            await adApi.create(form)
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

.image-uploader-wrapper {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ad-image-uploader :deep(.image-select-single),
.ad-image-uploader :deep(.image-select-add) {
    width: 120px;
    height: 120px;
}

.upload-tip {
    font-size: 11.5px;
    color: var(--el-text-color-secondary);
    line-height: 1.4;
}
</style>
