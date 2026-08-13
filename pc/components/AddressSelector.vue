<template>
  <div class="address-selector">
    <!-- Address list -->
    <div v-if="loading" class="py-6 text-center text-gray-400 text-sm">加载中...</div>

    <div v-else-if="addresses.length === 0" class="text-center py-4 text-gray-400 text-sm">
      暂无收货地址，请先添加
    </div>

    <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-2">
      <div
        v-for="addr in addresses"
        :key="addr.id"
        class="address-card"
        :class="{ 'is-selected': selectedId === addr.id }"
        @click="selectAddress(addr)"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <span class="font-medium text-sm text-gray-800">{{ addr.name }}</span>
              <span class="text-sm text-gray-500">{{ addr.phone }}</span>
              <span
                v-if="addr.is_default === 1"
                class="text-xs px-1.5 py-0.5 bg-[var(--color-primary)] text-white rounded-sm leading-none"
              >
                默认
              </span>
            </div>
            <p class="text-sm text-gray-500 leading-relaxed truncate">
              {{ addr.province }} {{ addr.city }} {{ addr.district }} {{ addr.detail }}
            </p>
          </div>
          <span
            v-if="selectedId === addr.id"
            class="i-carbon-checkmark-filled text-[var(--color-primary)] text-lg flex-shrink-0 mt-0.5"
          />
        </div>
      </div>
    </div>

    <!-- Add new address button -->
    <button
      class="mt-4 w-full py-2.5 border border-dashed border-gray-300 rounded text-sm text-gray-500 hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] transition-colors flex items-center justify-center gap-1.5"
      @click="showAddDialog = true"
    >
      <span class="i-carbon-add text-base" />
      添加新地址
    </button>

    <!-- Add Address Dialog -->
    <NModal
      v-model:show="showAddDialog"
      preset="card"
      title="添加收货地址"
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
          <NSwitch v-model:value="isDefault" />
        </NFormItem>
      </NForm>

      <template #footer>
        <div class="flex justify-end gap-3">
          <NButton @click="showAddDialog = false">取消</NButton>
          <NButton type="primary" :loading="saving" @click="handleSave">保存</NButton>
        </div>
      </template>
    </NModal>
  </div>
</template>

<script setup lang="ts">
import {
  NModal, NForm, NFormItem, NInput, NSelect, NButton, NSwitch,
  type FormInst, type FormRules, type SelectOption,
} from 'naive-ui'
import { memberApi, type AddressItem } from '~/api/member'
import { get } from '~/composables/useRequest'

const emit = defineEmits<{
  select: [address: AddressItem]
}>()

// --- State ---
const loading = ref(true)
const saving = ref(false)
const addresses = ref<AddressItem[]>([])
const selectedId = ref<number | null>(null)
const showAddDialog = ref(false)

// --- Form ---
const formRef = ref<FormInst | null>(null)
const isDefault = ref(false)
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

// --- Region data ---
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
    // ignore
  }
}

// --- Load addresses ---
async function loadAddresses() {
  loading.value = true
  try {
    const res = await memberApi.getAddressList()
    if (res.code === 200) {
      addresses.value = res.data
      // Pre-select default address
      const defaultAddr = res.data.find(a => a.is_default === 1) || res.data[0]
      if (defaultAddr) {
        selectedId.value = defaultAddr.id
        emit('select', defaultAddr)
      }
    }
  } finally {
    loading.value = false
  }
}

function selectAddress(addr: AddressItem) {
  selectedId.value = addr.id
  emit('select', addr)
}

// --- Save new address ---
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
    const res = await memberApi.createAddress({
      name: form.name,
      phone: form.phone,
      province: form.province,
      city: form.city,
      district: form.district,
      detail: form.detail,
      region_code: district?.code || city?.code || province?.code,
      is_default: isDefault.value ? 1 : 0,
    })

    if (res.code === 200) {
      showAddDialog.value = false
      // Reset form
      Object.assign(form, { name: '', phone: '', province: '', city: '', district: '', detail: '' })
      isDefault.value = false
      // Reload list
      await loadAddresses()
      // Auto-select the new address
      const newAddr = addresses.value.find(a => a.id === res.data.id)
      if (newAddr) selectAddress(newAddr)
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadAddresses()
  loadRegionTree()
})
</script>

<style scoped>
.address-card {
  padding: 12px 14px;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.address-card:hover {
  border-color: var(--color-primary);
}

.address-card.is-selected {
  border-color: var(--color-primary);
  background-color: rgb(from var(--color-primary) r g b / 0.04);
}
</style>
