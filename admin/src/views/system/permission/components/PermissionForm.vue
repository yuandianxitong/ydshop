<template>
    <el-dialog
        v-model="visible"
        :title="form.id ? $t('permission.editPermission') : $t('permission.addPermission')"
        width="500px"
        :close-on-click-modal="false"
        @closed="resetForm"
    >
        <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
            <el-form-item :label="$t('permission.permCode')" prop="name">
                <el-input
                    v-model="form.name"
                    :placeholder="$t('permission.codePlaceholder')"
                />
            </el-form-item>
            <el-form-item :label="$t('permission.permName')" prop="title">
                <el-input
                    v-model="form.title"
                    :placeholder="$t('permission.namePlaceholder')"
                />
            </el-form-item>
            <el-form-item :label="$t('permission.group')" prop="group">
                <el-input
                    v-model="form.group"
                    :placeholder="$t('permission.groupInputPlaceholder')"
                />
            </el-form-item>
            <el-form-item :label="$t('permission.description')" prop="description">
                <el-input
                    v-model="form.description"
                    type="textarea"
                    :rows="2"
                    :placeholder="$t('permission.descPlaceholder')"
                />
            </el-form-item>
            <el-form-item :label="$t('common.sort')" prop="sort">
                <el-input-number v-model="form.sort" :min="0" :max="9999" />
            </el-form-item>
            <el-form-item :label="$t('common.status')" prop="status">
                <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
            </el-form-item>
        </el-form>
        <template #footer>
            <el-button @click="handleClose">{{ $t('common.cancel') }}</el-button>
            <el-button type="primary" :loading="submitting" @click="handleSubmit">
                {{ $t('common.confirm') }}
            </el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import type { FormRules } from 'element-plus'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import { permissionApi } from '@/api/permission'
import { useFormDialog } from '@/hooks/useFormDialog'
import type { PermissionInfo, PermissionReq } from '@/types/system'

const { t } = useI18n()

type PermissionFormData = PermissionReq & { id?: number; sort?: number; status?: number }

interface Props {
    modelValue: boolean
    formData: Partial<PermissionInfo>
}

interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'success'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const { form, formRef, submitting, visible, handleSubmit, handleClose, resetForm } =
    useFormDialog<PermissionFormData>({
        defaultForm: {
            id: undefined,
            name: '',
            title: '',
            group: '',
            description: '',
            sort: 0,
            status: 1,
        },
        modelValue: () => props.modelValue,
        onUpdate: (v) => emit('update:modelValue', v),
        onSuccess: () => emit('success'),
        createFn: (data) => permissionApi.createPermission(data),
        updateFn: (id, data) => permissionApi.updatePermission(id, data),
        sourceData: () => props.formData as Partial<PermissionFormData>,
    })

const rules = computed<FormRules>(() => ({
    name: [
        { required: true, message: t('permission.validate.codeRequired'), trigger: 'blur' },
    ],
    title: [
        { required: true, message: t('permission.validate.nameRequired'), trigger: 'blur' },
    ],
    group: [
        { required: true, message: t('permission.validate.groupRequired'), trigger: 'blur' },
    ],
}))
</script>
