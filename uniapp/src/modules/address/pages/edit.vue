<template>
  <view class="address-edit-page">

    <scroll-view scroll-y class="address-edit-page__scroll">
      <view class="address-edit-page__content">

        <view class="form-group">
          <view class="form-row">
            <text class="form-label">收货人</text>
            <input
              v-model="form.name"
              class="form-input"
              placeholder="请输入收货人姓名"
              placeholder-class="form-placeholder"
            />
          </view>

          <view class="form-row">
            <text class="form-label">手机号</text>
            <input
              v-model="form.phone"
              class="form-input"
              placeholder="请输入手机号"
              placeholder-class="form-placeholder"
              type="number"
              maxlength="11"
            />
          </view>

          <view class="form-row" @tap="showRegionPicker = true">
            <text class="form-label">所在地区</text>
            <view class="form-select">
              <text :class="regionText ? 'form-value' : 'form-placeholder-text'">
                {{ regionText || '请选择省市区' }}
              </text>
              <d-icon name="arrow-right" size="28rpx" color="#a1a1aa" />
            </view>
          </view>

          <view class="form-row form-row--textarea">
            <text class="form-label">详细地址</text>
            <textarea
              v-model="form.detail"
              class="form-textarea"
              placeholder="请输入街道、楼号、门牌号等"
              placeholder-class="form-placeholder"
              auto-height
            />
          </view>

          <view class="form-row form-row--map" @tap="pickLocation">
            <view class="form-row__main">
              <text class="form-label">地图选点</text>
              <view class="form-select">
                <text :class="form.lng && form.lat ? 'form-value' : 'form-placeholder-text'">
                  {{ locationText }}
                </text>
                <d-icon name="arrow-right" size="28rpx" color="#a1a1aa" />
              </view>
            </view>
            <text class="form-help">同城配送需精确位置，请选择地图坐标</text>
          </view>

          <view class="form-row">
            <text class="form-label">设为默认地址</text>
            <view class="form-switch">
              <u-switch v-model="form.is_default" :activeValue="1" :inactiveValue="0" />
            </view>
          </view>
        </view>

        <!-- Delete button (edit mode only) -->
        <view v-if="editId" class="delete-btn-wrap">
          <u-button
            type="error"
            plain
            :customStyle="{ width: '100%', borderRadius: '16rpx' }"
            :loading="deleting"
            @click="handleDelete"
          >
            删除该地址
          </u-button>
        </view>

        <view style="height: 160rpx" />
      </view>
    </scroll-view>

    <!-- Save button -->
    <view class="bottom-bar">
      <u-button
        type="primary"
        :customStyle="{ width: '100%', borderRadius: '16rpx', height: '88rpx', fontSize: '30rpx' }"
        :loading="saving"
        @click="handleSave"
      >
        保存
      </u-button>
    </view>

    <!-- Region picker -->
    <u-popup :show="showRegionPicker" mode="bottom" safeAreaInsetBottom @close="showRegionPicker = false">
      <view class="region-picker-header">
        <text class="region-picker-cancel" @tap="showRegionPicker = false">取消</text>
        <text class="region-picker-title">选择地区</text>
        <text class="region-picker-confirm" @tap="confirmRegion">确认</text>
      </view>
      <!-- v-if：弹层动画完成后再挂载，避免 H5 首次量高错误；indicator 高度须与 .picker-item 一致 -->
      <picker-view
        v-if="showRegionPicker"
        class="region-picker-view"
        :value="pickerValue"
        :indicator-style="pickerIndicatorStyle"
        @change="onPickerChange"
      >
        <picker-view-column>
          <view v-for="p in provinces" :key="p.value" class="picker-item">{{ p.label }}</view>
        </picker-view-column>
        <picker-view-column>
          <view v-for="c in currentCities" :key="c.value" class="picker-item">{{ c.label }}</view>
        </picker-view-column>
        <picker-view-column>
          <view v-for="d in currentDistricts" :key="d.value" class="picker-item">{{ d.label }}</view>
        </picker-view-column>
      </picker-view>
    </u-popup>

  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, nextTick, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { memberApi, type AddressItem } from '@/api/member'
import http from '@/utils/request'

/** H5 picker-view 按 indicator 高度换算滚动位置，必须与选项行高一致（用 px，避免 rpx 换算偏差） */
const PICKER_ITEM_HEIGHT_PX = 40
const pickerIndicatorStyle = `height: ${PICKER_ITEM_HEIGHT_PX}px;`

const editId = ref<number | null>(null)
const saving = ref(false)
const deleting = ref(false)

const form = reactive({
  name: '',
  phone: '',
  province: '',
  city: '',
  district: '',
  region_code: '',
  detail: '',
  lng: 0 as number,
  lat: 0 as number,
  is_default: 0 as 0 | 1,
})

const locationText = computed(() => {
  if (!form.lng || !form.lat) return '点击选择经纬度'
  return `${form.lng.toFixed(6)}, ${form.lat.toFixed(6)}`
})

function pickLocation() {
  uni.chooseLocation({
    success: (res: any) => {
      form.lng = Number(res.longitude) || 0
      form.lat = Number(res.latitude) || 0
      // 用户在 chooseLocation 选完点后，把回填的地址写到 detail 字段（如果当前为空）
      if (res.address && !form.detail.trim()) {
        form.detail = res.address
      }
    },
    fail: (e: any) => {
      // 用户取消不弹错误
      if (!e?.errMsg || /cancel/i.test(e.errMsg)) return
      console.error('[address] chooseLocation 失败', e)
      // 真机 mp / h5 常见失败：未配 requiredPrivateInfos / 隐私协议未授权 / qqmap key 未配 / 用户拒绝授权
      let title = '获取位置失败'
      const msg = String(e.errMsg)
      if (/privacy/i.test(msg)) {
        title = '请先同意隐私协议'
      } else if (/auth.*deny|deny.*auth/i.test(msg)) {
        title = '已拒绝定位权限，请到设置开启'
      } else if (/key/i.test(msg)) {
        title = '地图 key 未配置，联系管理员'
      }
      uni.showModal({ title, content: msg, showCancel: false, confirmText: '我知道了' })
    },
  })
}

// Region picker state
const showRegionPicker = ref(false)

// 后端 RegionRepository::buildTree 返回格式
interface RegionNode {
  value: number    // 地区 id
  label: string    // 地区名（省/市/区）
  code?: string
  children?: RegionNode[]
}

const regionTree = ref<RegionNode[]>([])

const provinces = computed(() => regionTree.value)
const currentProvinceIdx = ref(0)
const currentCityIdx = ref(0)
const currentDistrictIdx = ref(0)
/** 使用 ref 而非 computed：H5 在级联重置二三级时需显式回写 value 才能归位 */
const pickerValue = ref<number[]>([0, 0, 0])

const currentCities = computed(() => provinces.value[currentProvinceIdx.value]?.children || [])
const currentDistricts = computed(() => currentCities.value[currentCityIdx.value]?.children || [])

const regionText = computed(() => {
  if (!form.province) return ''
  return [form.province, form.city, form.district].filter(Boolean).join(' ')
})

function syncPickerValue() {
  pickerValue.value = [
    currentProvinceIdx.value,
    currentCityIdx.value,
    currentDistrictIdx.value,
  ]
}

function clampIndex(index: number, length: number): number {
  if (length <= 0) return 0
  if (index < 0) return 0
  if (index >= length) return length - 1
  return index
}

function onPickerChange(e: any) {
  const raw = (e?.detail?.value || [0, 0, 0]) as number[]
  let pi = clampIndex(Number(raw[0]) || 0, provinces.value.length)
  let ci = Number(raw[1]) || 0
  let di = Number(raw[2]) || 0

  if (pi !== currentProvinceIdx.value) {
    currentProvinceIdx.value = pi
    currentCityIdx.value = 0
    currentDistrictIdx.value = 0
  } else if (ci !== currentCityIdx.value) {
    const cities = provinces.value[pi]?.children || []
    currentCityIdx.value = clampIndex(ci, cities.length)
    currentDistrictIdx.value = 0
  } else {
    const cities = provinces.value[pi]?.children || []
    const districts = cities[currentCityIdx.value]?.children || []
    currentDistrictIdx.value = clampIndex(di, districts.length)
  }

  // 列数据切换后强制同步 value，避免 H5 二三级停在旧滚动偏移
  nextTick(() => {
    syncPickerValue()
  })
}

function confirmRegion() {
  const p = provinces.value[currentProvinceIdx.value]
  const c = currentCities.value[currentCityIdx.value]
  const d = currentDistricts.value[currentDistrictIdx.value]
  form.province = p?.label || ''
  form.city = c?.label || ''
  form.district = d?.label || ''
  form.region_code = d?.code || c?.code || p?.code || ''
  showRegionPicker.value = false
}

function syncPickerIndexFromForm() {
  if (!form.province || regionTree.value.length === 0) {
    currentProvinceIdx.value = 0
    currentCityIdx.value = 0
    currentDistrictIdx.value = 0
    syncPickerValue()
    return
  }
  const pi = regionTree.value.findIndex(p => p.label === form.province)
  if (pi < 0) return
  currentProvinceIdx.value = pi
  const cities = regionTree.value[pi].children || []
  const ci = cities.findIndex(c => c.label === form.city)
  currentCityIdx.value = ci < 0 ? 0 : ci
  const districts = cities[currentCityIdx.value]?.children || []
  const di = districts.findIndex(d => d.label === form.district)
  currentDistrictIdx.value = di < 0 ? 0 : di
  syncPickerValue()
}

watch(showRegionPicker, (show) => {
  if (show) syncPickerIndexFromForm()
})

async function loadRegionTree() {
  try {
    const res = await http.get<RegionNode[]>('/api/region/tree')
    if (Array.isArray(res)) {
      regionTree.value = res
    }
    syncPickerIndexFromForm()
  } catch {
    // region tree optional
  }
}

async function loadAddress(id: number) {
  try {
    const list = await memberApi.getAddressList()
    const addr = (Array.isArray(list) ? list : []).find((a: AddressItem) => a.id === id)
    if (addr) {
      form.name = addr.name
      form.phone = addr.phone
      form.province = addr.province
      form.city = addr.city
      form.district = addr.district
      form.region_code = addr.region_code || ''
      form.detail = addr.detail
      form.lng = Number((addr as any).lng) || 0
      form.lat = Number((addr as any).lat) || 0
      form.is_default = addr.is_default as 0 | 1
      syncPickerIndexFromForm()
    }
  } catch {
    uni.showToast({ title: '加载地址失败', icon: 'none' })
  }
}

function validate(): boolean {
  if (!form.name.trim()) {
    uni.showToast({ title: '请输入收货人姓名', icon: 'none' })
    return false
  }
  if (!/^1[3-9]\d{9}$/.test(form.phone)) {
    uni.showToast({ title: '请输入正确的手机号', icon: 'none' })
    return false
  }
  if (!form.province) {
    uni.showToast({ title: '请选择所在地区', icon: 'none' })
    return false
  }
  if (!form.detail.trim()) {
    uni.showToast({ title: '请输入详细地址', icon: 'none' })
    return false
  }
  return true
}

async function handleSave() {
  if (!validate()) return
  saving.value = true
  try {
    const payload = {
      name: form.name.trim(),
      phone: form.phone,
      province: form.province,
      city: form.city,
      district: form.district,
      region_code: form.region_code,
      detail: form.detail.trim(),
      lng: form.lng || undefined,
      lat: form.lat || undefined,
      is_default: form.is_default,
    }

    if (editId.value) {
      await memberApi.updateAddress(editId.value, payload)
    } else {
      await memberApi.createAddress(payload)
    }

    uni.showToast({ title: '保存成功', icon: 'success' })
    setTimeout(() => uni.navigateBack(), 500)
  } catch {
    uni.showToast({ title: '保存失败', icon: 'none' })
  } finally {
    saving.value = false
  }
}

function handleDelete() {
  if (!editId.value) return
  uni.showModal({
    title: '确认删除',
    content: '确定要删除该收货地址吗？',
    confirmText: '删除',
    confirmColor: '#fa3534',
    success: async (res) => {
      if (!res.confirm) return
      deleting.value = true
      try {
        await memberApi.deleteAddress(editId.value!)
        uni.showToast({ title: '删除成功', icon: 'success' })
        setTimeout(() => uni.navigateBack(), 500)
      } catch {
        uni.showToast({ title: '删除失败', icon: 'none' })
      } finally {
        deleting.value = false
      }
    },
  })
}

onLoad(async (options) => {
  const id = options?.id ? Number(options.id) : null
  editId.value = id

  uni.setNavigationBarTitle({ title: id ? '编辑地址' : '新增地址' })

  await loadRegionTree()

  if (id) {
    await loadAddress(id)
  }
})

</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.address-edit-page {
  min-height: 100vh;
  background: var(--color-bg, #{$bg-color});

  &__scroll {
    height: 100vh;
  }

  &__content {
    padding: 0;
  }
}

.form-group {
  background: #fff;
  margin-bottom: 24rpx;
}

.form-row {
  min-height: 104rpx;
  display: flex;
  align-items: center;
  padding: 0 30rpx;
  border-bottom: 1rpx solid var(--color-border, #{$border-color});
  box-sizing: border-box;

  &:last-child {
    border-bottom: 0;
  }
}

.form-row--textarea {
  align-items: flex-start;
  padding-top: 30rpx;
  padding-bottom: 30rpx;
}

.form-row--map {
  display: block;
  padding-top: 28rpx;
  padding-bottom: 24rpx;
}

.form-row__main {
  display: flex;
  align-items: center;
}

.form-label {
  width: 168rpx;
  flex-shrink: 0;
  font-size: 28rpx;
  color: var(--color-text, #{$text-color});
}

.form-input,
.form-textarea {
  flex: 1;
  min-width: 0;
  font-size: 28rpx;
  color: var(--color-text, #{$text-color});
  text-align: right;
}

.form-textarea {
  min-height: 92rpx;
  line-height: 1.5;
}

.form-select,
.form-switch {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8rpx;
}

.form-value,
.form-placeholder-text {
  flex: 1;
  min-width: 0;
  font-size: 28rpx;
  text-align: right;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.form-value {
  color: var(--color-text, #{$text-color});
}

.form-placeholder,
.form-placeholder-text {
  color: var(--color-text-tertiary, #aaa);
}

.form-help {
  display: block;
  margin-top: 12rpx;
  padding-left: 168rpx;
  font-size: 24rpx;
  line-height: 1.4;
  text-align: right;
  color: var(--color-text-tertiary, #aaa);
}

.delete-btn-wrap {
  margin: 24rpx $page-padding 0;
}

.bottom-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #fff;
  padding: 20rpx 32rpx;
  padding-bottom: calc(20rpx + constant(safe-area-inset-bottom));
  padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
  box-shadow: 0 -2rpx 12rpx rgba(0, 0, 0, 0.06);
  box-sizing: border-box;
}

// Region picker
.region-picker-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24rpx 32rpx;
  border-bottom: 1rpx solid #f0f0f0;
}

.region-picker-cancel {
  font-size: 28rpx;
  color: $text-color-secondary;
}

.region-picker-title {
  font-size: 30rpx;
  font-weight: 600;
  color: var(--color-text, #{$text-color});
}

.region-picker-confirm {
  font-size: 28rpx;
  color: var(--color-primary, #{$primary-color});
  font-weight: 600;
}

.region-picker-view {
  height: 240px;
  width: 100%;
}

.picker-item {
  height: 40px;
  line-height: 40px;
  font-size: 14px;
  color: var(--color-text, #{$text-color});
  text-align: center;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  padding: 0 4px;
  box-sizing: border-box;
}
</style>
