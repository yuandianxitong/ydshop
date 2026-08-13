<template>
    <el-dialog
        :model-value="modelValue"
        title="修改收货地址"
        width="520px"
        destroy-on-close
        @update:model-value="emit('update:modelValue', $event)"
    >
        <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
            <el-form-item label="收件人" prop="name">
                <el-input v-model="form.name" maxlength="50" placeholder="请输入收件人" />
            </el-form-item>
            <el-form-item label="手机号" prop="phone">
                <el-input v-model="form.phone" maxlength="20" placeholder="请输入手机号" />
            </el-form-item>
            <el-form-item label="所在地区" prop="province">
                <RegionCascader
                    v-model="regionNames"
                    placeholder="选择省 / 市 / 区"
                    style="width: 100%"
                    @change="onRegionChange"
                />
            </el-form-item>
            <el-form-item label="详细地址" prop="detail">
                <el-input
                    v-model="form.detail"
                    type="textarea"
                    :rows="3"
                    maxlength="255"
                    placeholder="街道、门牌号等"
                />
            </el-form-item>
        </el-form>
        <template #footer>
            <el-button @click="emit('update:modelValue', false)">取消</el-button>
            <el-button type="primary" :loading="loading" @click="submit">保存</el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { reactive, ref, watch } from 'vue'

import { orderApi } from '@/api/order'
import RegionCascader from '@/components/Region/index.vue'
import type { OrderAddressUpdateReq } from '@/types/api'

const props = defineProps<{
    modelValue: boolean
    orderId: number | null
    address?: Partial<OrderAddressUpdateReq> | null
}>()

const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    success: []
}>()

const formRef = ref<FormInstance>()
const loading = ref(false)
const regionNames = ref<string[]>([])
const form = reactive<OrderAddressUpdateReq>({
    name: '',
    phone: '',
    province: '',
    city: '',
    district: '',
    detail: ''
})

const rules: FormRules = {
    name: [{ required: true, message: '请输入收件人', trigger: 'blur' }],
    phone: [{ required: true, message: '请输入手机号', trigger: 'blur' }],
    province: [
        {
            required: true,
            validator: (_rule, _value, callback) => {
                if (!form.province || !form.city || !form.district) {
                    callback(new Error('请选择所在地区'))
                    return
                }
                callback()
            },
            trigger: 'change'
        }
    ],
    detail: [{ required: true, message: '请输入详细地址', trigger: 'blur' }]
}

const onRegionChange = (payload: { ids: number[]; names: string[]; codes: string[] }) => {
    form.province = payload.names[0] || ''
    form.city = payload.names[1] || ''
    form.district = payload.names[2] || ''
    formRef.value?.validateField('province').catch(() => undefined)
}

const fillForm = () => {
    const snap = props.address || {}
    form.name = String(snap.name || '')
    form.phone = String(snap.phone || '')
    form.province = String(snap.province || '')
    form.city = String(snap.city || '')
    form.district = String(snap.district || '')
    form.detail = String(snap.detail || '')
    regionNames.value = [form.province, form.city, form.district].filter(Boolean)
}

watch(
    () => props.modelValue,
    (visible) => {
        if (visible) {
            fillForm()
        }
    }
)

const submit = async () => {
    if (!props.orderId) return
    const ok = await formRef.value?.validate().catch(() => false)
    if (!ok) return
    loading.value = true
    try {
        await orderApi.updateOrderAddress(props.orderId, { ...form })
        ElMessage.success('地址已更新')
        emit('update:modelValue', false)
        emit('success')
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}
</script>
