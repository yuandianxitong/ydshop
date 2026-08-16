<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-bold text-gray-900">收货地址</h2>
      <button class="btn-primary text-sm" @click="openAdd">
        <span class="i-carbon-add mr-1" />
        添加地址
      </button>
    </div>

    <div v-if="loading" class="text-center py-10 text-gray-400">加载中...</div>

    <template v-else>
      <div v-if="addresses.length === 0" class="card p-10 text-center">
        <div class="i-carbon-location text-5xl text-gray-300 mx-auto mb-3" />
        <p class="text-gray-400 text-sm mb-4">暂无收货地址</p>
        <button class="btn-primary text-sm" @click="openAdd">添加收货地址</button>
      </div>

      <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div
          v-for="addr in addresses"
          :key="addr.id"
          class="card p-5 relative"
        >
          <!-- Default badge -->
          <span
            v-if="addr.is_default === 1"
            class="absolute top-4 right-4 text-xs px-2 py-0.5 bg-[var(--color-primary)] text-white rounded-sm leading-none"
          >
            默认
          </span>

          <div class="pr-12">
            <div class="flex items-center gap-3 mb-1">
              <span class="font-semibold text-gray-800">{{ addr.name }}</span>
              <span class="text-sm text-gray-500">{{ addr.phone }}</span>
            </div>
            <p class="text-sm text-gray-500 leading-relaxed">
              {{ addr.province }} {{ addr.city }} {{ addr.district }} {{ addr.detail }}
            </p>
          </div>

          <div class="flex items-center gap-3 mt-4 pt-3 border-t border-gray-100">
            <button
              v-if="addr.is_default !== 1"
              class="text-xs text-gray-500 hover:text-[var(--color-primary)] transition-colors"
              @click="handleSetDefault(addr)"
            >
              设为默认
            </button>
            <button
              class="text-xs text-gray-500 hover:text-[var(--color-primary)] transition-colors"
              @click="openEdit(addr)"
            >
              编辑
            </button>
            <button
              class="text-xs text-gray-500 hover:text-red-500 transition-colors ml-auto"
              @click="handleDelete(addr)"
            >
              删除
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- Add / Edit Dialog -->
    <NModal
      v-model:show="dialogVisible"
      preset="card"
      :title="editingId ? '编辑收货地址' : '添加收货地址'"
      class="max-w-lg"
      :mask-closable="false"
    >
      <NForm
        ref="formRef"
        :model="form"
        :rules="rules"
        label-placement="left"
        label-width="80px"
        require-mark-placement="right-hanging"
      >
        <NFormItem label="收货人" path="name">
          <NInput v-model:value="form.name" placeholder="请输入收货人姓名" />
        </NFormItem>
        <NFormItem label="手机号" path="phone">
          <NInput v-model:value="form.phone" placeholder="请输入手机号" maxlength="11" />
        </NFormItem>
        <NFormItem label="所在地区" path="province">
          <div class="flex gap-2 w-full">
            <NSelect
              v-model:value="form.province"
              :options="provinceOptions"
              placeholder="省份"
              class="flex-1"
              filterable
              @update:value="onProvinceChange"
            />
            <NSelect
              v-model:value="form.city"
              :options="cityOptions"
              placeholder="城市"
              class="flex-1"
              filterable
              :disabled="!form.province"
              @update:value="onCityChange"
            />
            <NSelect
              v-model:value="form.district"
              :options="districtOptions"
              placeholder="区县"
              class="flex-1"
              filterable
              :disabled="!form.city"
            />
          </div>
        </NFormItem>
        <NFormItem label="详细地址" path="detail">
          <NInput
            v-model:value="form.detail"
            placeholder="街道、楼号、门牌号等"
            type="textarea"
            :rows="2"
          />
        </NFormItem>
        <NFormItem label="设为默认">
          <NSwitch v-model:value="formIsDefault" />
        </NFormItem>
      </NForm>

      <template #footer>
        <div class="flex justify-end gap-3">
          <button type="button" class="btn-outline" @click="dialogVisible = false">取消</button>
          <button type="button" class="btn-primary" :disabled="saving" @click="handleSave">
            {{ saving ? '保存中...' : '保存' }}
          </button>
        </div>
      </template>
    </NModal>

    <!-- Delete confirm dialog -->
    <NModal
      v-model:show="deleteConfirmVisible"
      preset="dialog"
      type="warning"
      title="确认删除"
      content="确定要删除该收货地址吗？"
      positive-text="删除"
      negative-text="取消"
      :loading="deleting"
      @positive-click="confirmDelete"
      @negative-click="deleteConfirmVisible = false"
    />
  </div>
</template>

<script setup lang="ts">
import {
  NModal, NForm, NFormItem, NInput, NSelect, NSwitch,
  type FormInst, type FormRules, type SelectOption,
  useMessage,
} from 'naive-ui'
import { memberApi, type AddressItem } from '~/api/member'
import { get } from '~/composables/useRequest'

const message = useMessage()

const loading = ref(true)
const saving = ref(false)
const deleting = ref(false)
const addresses = ref<AddressItem[]>([])

// Dialog state
const dialogVisible = ref(false)
const editingId = ref<number | null>(null)
const deleteConfirmVisible = ref(false)
const deletingAddr = ref<AddressItem | null>(null)

// Form
const formRef = ref<FormInst | null>(null)
const formIsDefault = ref(false)
const form = reactive({
  name: '',
  phone: '',
  province: '',
  city: '',
  district: '',
  detail: '',
})

const rules: FormRules = {
  name: [{ required: true, message: '请输入收货人姓名', trigger: 'blur' }],
  phone: [
    { required: true, message: '请输入手机号', trigger: 'blur' },
    { pattern: /^1[3-9]\d{9}$/, message: '手机号格式不正确', trigger: 'blur' },
  ],
  province: [{ required: true, message: '请选择所在地区', trigger: 'change' }],
  detail: [{ required: true, message: '请输入详细地址', trigger: 'blur' }],
}

// Region data
interface RegionNode {
  value: number
  label: string
  code: string
  children?: RegionNode[]
}

const regionTree = ref<RegionNode[]>([])

const provinceOptions = computed<SelectOption[]>(() =>
  regionTree.value.map(p => ({ label: p.label, value: p.label }))
)

const cityOptions = computed<SelectOption[]>(() => {
  const province = regionTree.value.find(p => p.label === form.province)
  return (province?.children || []).map(c => ({ label: c.label, value: c.label }))
})

const districtOptions = computed<SelectOption[]>(() => {
  const province = regionTree.value.find(p => p.label === form.province)
  const city = (province?.children || []).find(c => c.label === form.city)
  return (city?.children || []).map(d => ({ label: d.label, value: d.label }))
})

function onProvinceChange() {
  form.city = ''
  form.district = ''
}

function onCityChange() {
  form.district = ''
}

async function loadRegionTree() {
  try {
    const res = await get<RegionNode[]>('/api/region/tree')
    if (res.code === 200) {
      regionTree.value = res.data
    }
  } catch {
    // ignore — region tree is optional enhancement
  }
}

async function loadAddresses() {
  loading.value = true
  try {
    const res = await memberApi.getAddressList()
    if (res.code === 200) {
      addresses.value = res.data
    }
  } finally {
    loading.value = false
  }
}

function resetForm() {
  Object.assign(form, { name: '', phone: '', province: '', city: '', district: '', detail: '' })
  formIsDefault.value = false
  formRef.value?.restoreValidation()
}

function openAdd() {
  editingId.value = null
  resetForm()
  dialogVisible.value = true
}

function openEdit(addr: AddressItem) {
  editingId.value = addr.id
  form.name = addr.name
  form.phone = addr.phone
  form.province = addr.province
  form.city = addr.city
  form.district = addr.district
  form.detail = addr.detail
  formIsDefault.value = addr.is_default === 1
  formRef.value?.restoreValidation()
  dialogVisible.value = true
}

async function handleSave() {
  try {
    await formRef.value?.validate()
  } catch {
    return
  }

  saving.value = true
  try {
    const province = regionTree.value.find(p => p.label === form.province)
    const city = province?.children?.find(c => c.label === form.city)
    const district = city?.children?.find(d => d.label === form.district)
    const payload = {
      name: form.name,
      phone: form.phone,
      province: form.province,
      city: form.city,
      district: form.district,
      detail: form.detail,
      region_code: district?.code || city?.code || province?.code,
      is_default: formIsDefault.value ? 1 : 0,
    }

    let res
    if (editingId.value) {
      res = await memberApi.updateAddress(editingId.value, payload)
    } else {
      res = await memberApi.createAddress(payload)
    }

    if (res.code === 200) {
      message.success(editingId.value ? '修改成功' : '添加成功')
      dialogVisible.value = false
      await loadAddresses()
    } else {
      message.error(res.message || '操作失败')
    }
  } finally {
    saving.value = false
  }
}

async function handleSetDefault(addr: AddressItem) {
  try {
    const res = await memberApi.updateAddress(addr.id, {
      name: addr.name,
      phone: addr.phone,
      province: addr.province,
      city: addr.city,
      district: addr.district,
      detail: addr.detail,
      region_code: addr.region_code,
      lng: addr.lng,
      lat: addr.lat,
      is_default: 1,
    })
    if (res.code === 200) {
      message.success('已设为默认地址')
      await loadAddresses()
    }
  } catch {
    message.error('操作失败')
  }
}

function handleDelete(addr: AddressItem) {
  deletingAddr.value = addr
  deleteConfirmVisible.value = true
}

async function confirmDelete() {
  if (!deletingAddr.value) return
  deleting.value = true
  try {
    const res = await memberApi.deleteAddress(deletingAddr.value.id)
    if (res.code === 200) {
      message.success('删除成功')
      deleteConfirmVisible.value = false
      await loadAddresses()
    } else {
      message.error(res.message || '删除失败')
    }
  } finally {
    deleting.value = false
  }
}

onMounted(() => {
  loadAddresses()
  loadRegionTree()
})
</script>
