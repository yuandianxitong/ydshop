<script setup lang="ts">
import { ref, watch, computed, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import { addressBookApi, type AddressInfo } from '@/api/address-book'
import RegionCascader from '@/components/Region/index.vue'

interface Props {
  modelValue: boolean
  userId: number | null
  initial?: AddressInfo | null
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
const isEdit = computed(() => !!props.initial?.id)

const form = reactive({
  name: '',
  phone: '',
  province: '',
  city: '',
  district: '',
  region_code: '',
  detail: '',
  is_default: 0 as 0 | 1,
})
const submitting = ref(false)

// 地区联动：v-model 名称数组，change 事件取 leaf code
const regionNames = ref<string[]>([])

watch(() => props.modelValue, (v) => {
  if (!v) return
  if (props.initial) {
    Object.assign(form, {
      name: props.initial.name || '',
      phone: props.initial.phone || '',
      province: props.initial.province || '',
      city: props.initial.city || '',
      district: props.initial.district || '',
      region_code: props.initial.region_code || '',
      detail: props.initial.detail || '',
      is_default: props.initial.is_default || 0,
    })
    regionNames.value = [form.province, form.city, form.district].filter(Boolean)
  } else {
    Object.assign(form, { name: '', phone: '', province: '', city: '', district: '', region_code: '', detail: '', is_default: 0 })
    regionNames.value = []
  }
})

const onRegionChange = (payload: { ids: number[]; names: string[]; codes: string[] }) => {
  form.province = payload.names[0] || ''
  form.city     = payload.names[1] || ''
  form.district = payload.names[2] || ''
  // leaf code（最深一级），找不到回退到前一级
  form.region_code = payload.codes[payload.codes.length - 1] || ''
}

const submit = async () => {
  if (!props.userId) return
  if (!form.name)  { ElMessage.warning('请填写收货人'); return }
  if (!form.phone) { ElMessage.warning('请填写手机号'); return }
  if (!form.province || !form.city || !form.district) { ElMessage.warning('请选择所在地'); return }
  submitting.value = true
  try {
    if (isEdit.value && props.initial) {
      await addressBookApi.update(props.initial.id, { ...form })
    } else {
      await addressBookApi.create({ ...form, user_id: props.userId })
    }
    ElMessage.success('保存成功')
    emit('saved')
    visible.value = false
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <el-dialog v-model="visible" :title="isEdit ? '编辑地址' : '新增地址'" width="540px" append-to-body>
    <el-form label-width="80px">
      <el-form-item label="收货人">
        <el-input v-model="form.name" placeholder="姓名" />
      </el-form-item>
      <el-form-item label="手机号">
        <el-input v-model="form.phone" placeholder="手机号" />
      </el-form-item>
      <el-form-item label="所在地">
        <RegionCascader v-model="regionNames" placeholder="选择省 / 市 / 区" style="width: 100%" @change="onRegionChange" />
      </el-form-item>
      <el-form-item label="详细地址">
        <el-input v-model="form.detail" type="textarea" :rows="2" placeholder="街道、楼栋、门牌号" />
      </el-form-item>
      <el-form-item label="设为默认">
        <el-switch v-model="form.is_default" :active-value="1" :inactive-value="0" />
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="visible = false">取消</el-button>
      <el-button type="primary" :loading="submitting" @click="submit">保存</el-button>
    </template>
  </el-dialog>
</template>
