<template>
    <el-dialog
        :model-value="modelValue"
        :title="isEdit ? '编辑门店' : '新建门店'"
        width="800px"
        top="5vh"
        destroy-on-close
        :close-on-click-modal="false"
        @update:model-value="(v) => $emit('update:modelValue', v)"
        @opened="onDialogOpened"
        @closed="onDialogClosed"
    >
        <el-form
            ref="formRef"
            v-loading="loading"
            :model="form"
            :rules="rules"
            label-width="100px"
            class="store-form"
        >
            <!-- ── 基础信息 ── -->
            <div class="section-title">基础信息</div>

            <el-form-item label="门店名称" prop="name">
                <el-input v-model="form.name" maxlength="100" show-word-limit placeholder="请输入门店名称" />
            </el-form-item>

            <el-form-item label="门店编码" prop="code">
                <el-input
                    v-model="form.code"
                    maxlength="32"
                    :disabled="isEdit"
                    placeholder="字母、数字、下划线，创建后不可修改"
                />
            </el-form-item>

            <el-form-item label="联系电话" prop="phone">
                <el-input v-model="form.phone" maxlength="20" placeholder="门店联系电话" />
            </el-form-item>

            <el-form-item label="状态">
                <el-switch v-model="statusOn" />
            </el-form-item>

            <el-form-item label="排序">
                <el-input-number v-model="form.sort" :min="0" :max="9999" />
                <span class="field-hint">数值越小越靠前</span>
            </el-form-item>

            <el-form-item label="备注">
                <el-input
                    v-model="form.remark"
                    type="textarea"
                    :rows="2"
                    maxlength="255"
                    show-word-limit
                    placeholder="选填"
                />
            </el-form-item>

            <!-- ── 位置信息 ── -->
            <div class="section-title">位置信息</div>

            <el-form-item label="所在地区" prop="province">
                <RegionCascader
                    v-model="regionNames"
                    placeholder="选择省 / 市 / 区"
                    style="width: 100%"
                    @change="onRegionChange"
                />
            </el-form-item>

            <el-form-item label="搜索地址">
                <div ref="searchBox" class="search-input-wrapper">
                    <el-input
                        v-model="searchKeyword"
                        placeholder="输入地址或地标关键字（如 人民广场）"
                        clearable
                        :prefix-icon="undefined"
                    >
                        <template #prefix><i class="i-lucide:search text-[14px]" /></template>
                    </el-input>
                    <div v-if="!autoCompleteReady" class="search-tip">高德 SDK 加载中…</div>
                </div>
            </el-form-item>

            <el-form-item label="详细地址" prop="detail">
                <el-input
                    v-model="form.detail"
                    placeholder="街道 / 门牌号"
                    maxlength="200"
                    show-word-limit
                />
            </el-form-item>

            <el-form-item label="经纬度">
                <el-input
                    :model-value="form.lng && form.lat ? `${form.lng}, ${form.lat}` : '—'"
                    readonly
                />
            </el-form-item>

            <el-form-item label="地图选点">
                <div class="map-wrapper">
                    <div ref="mapContainer" class="map-canvas" />
                    <div v-if="initFailed" class="map-error">
                        {{ initFailedReason }}
                    </div>
                </div>
                <div class="map-tip">点击地图选择门店位置 · 选省市区或搜索可自动定位 · 支持手动微调详细地址</div>
            </el-form-item>

            <!-- ── 营业时间 ── -->
            <div class="section-title">营业时间</div>

            <el-form-item label="每日时段">
                <div class="business-hours">
                    <div v-for="k in DAY_KEYS" :key="k" class="day-row">
                        <span class="day-label">{{ dayLabels[k] }}</span>
                        <el-switch
                            v-model="hoursOpen[k]"
                            inline-prompt
                            active-text="营业"
                            inactive-text="歇业"
                            :width="56"
                            class="day-switch"
                        />
                        <div class="day-row__right">
                            <template v-if="hoursOpen[k]">
                                <el-time-picker
                                    v-model="hoursStart[k]"
                                    format="HH:mm"
                                    value-format="HH:mm"
                                    placeholder="开始"
                                    class="day-time"
                                />
                                <span class="separator">—</span>
                                <el-time-picker
                                    v-model="hoursEnd[k]"
                                    format="HH:mm"
                                    value-format="HH:mm"
                                    placeholder="结束"
                                    class="day-time"
                                />
                            </template>
                            <span v-else class="closed-tag">全天歇业</span>
                        </div>
                    </div>
                </div>
            </el-form-item>
        </el-form>

        <template #footer>
            <el-button @click="$emit('update:modelValue', false)">取消</el-button>
            <el-button type="primary" :loading="saving" @click="onSubmit">保存</el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts" name="StoreForm">
import AMapLoader from '@amap/amap-jsapi-loader'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { computed, nextTick, reactive, ref, watch } from 'vue'
import { deliveryMapApi } from '@/api/delivery'
import { storeApi, type BusinessHours, type Store } from '@/api/store'
import RegionCascader from '@/components/Region/index.vue'

const props = defineProps<{
    modelValue: boolean
    formData?: Partial<Store> & { id?: number }
}>()

const emit = defineEmits<{
    'update:modelValue': [v: boolean]
    success: []
}>()

const formRef = ref<FormInstance>()
const mapContainer = ref<HTMLDivElement>()
const searchBox = ref<HTMLDivElement>()
const loading = ref(false)
const saving = ref(false)
const initFailed = ref(false)
const initFailedReason = ref('')
const autoCompleteReady = ref(false)
const searchKeyword = ref('')

const isEdit = computed(() => !!props.formData?.id)

// ─── Form state ────────────────────────────────────────────────────────────
const form = reactive<Store>({
    name: '',
    code: '',
    address: '',
    province: '',
    city: '',
    district: '',
    detail: '',
    region_code: '',
    lng: 0,
    lat: 0,
    phone: '',
    business_hours: undefined,
    status: 1,
    sort: 100,
    remark: '',
})

// 地区联动名称数组（绑给 RegionCascader）
const regionNames = ref<string[]>([])

// ─── Business-hours state ──────────────────────────────────────────────────
const DAY_KEYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as const
type DayKey = typeof DAY_KEYS[number]

const dayLabels: Record<DayKey, string> = {
    mon: '周一', tue: '周二', wed: '周三', thu: '周四',
    fri: '周五', sat: '周六', sun: '周日',
}

const defaultStart = '09:00'
const defaultEnd = '21:00'

const hoursOpen = reactive<Record<DayKey, boolean>>({
    mon: true, tue: true, wed: true, thu: true, fri: true, sat: true, sun: true,
})
const hoursStart = reactive<Record<DayKey, string>>({
    mon: defaultStart, tue: defaultStart, wed: defaultStart, thu: defaultStart,
    fri: defaultStart, sat: defaultStart, sun: defaultStart,
})
const hoursEnd = reactive<Record<DayKey, string>>({
    mon: defaultEnd, tue: defaultEnd, wed: defaultEnd, thu: defaultEnd,
    fri: defaultEnd, sat: defaultEnd, sun: defaultEnd,
})

const statusOn = computed<boolean>({
    get: () => form.status === 1,
    set: (v: boolean) => { form.status = v ? 1 : 0 },
})

const rules: FormRules = {
    name: [{ required: true, message: '请填写门店名称', trigger: 'blur' }],
    code: [
        { required: true, message: '请填写门店编码', trigger: 'blur' },
        { pattern: /^[A-Za-z0-9_]+$/, message: '仅允许字母、数字、下划线', trigger: 'blur' },
    ],
    province: [{ required: true, message: '请选择所在地区', trigger: 'change' }],
    detail: [{ required: true, message: '请填写详细地址', trigger: 'blur' }],
}

// ─── AMap instances ────────────────────────────────────────────────────────
let AMapNS: any = null
let mapInstance: any = null
let markerInstance: any = null
let geocoder: any = null
let autoComplete: any = null

/**
 * 拼装完整地址用于展示与持久化
 */
function joinAddress(): string {
    const parts = [form.province, form.city, form.district, form.detail].filter(Boolean)
    return parts.join('')
}

/**
 * 高德 regeocode 拆解 → 回填 form 省市区+detail
 */
function applyRegeocode(regeocode: any) {
    const ac = regeocode?.addressComponent || {}
    form.province = String(ac.province || '')
    form.city     = typeof ac.city === 'string' && ac.city ? ac.city : form.province  // 直辖市的 city 为空
    form.district = String(ac.district || '')
    form.region_code = String(ac.adcode || '')
    // 详细地址：township + streetNumber.street + streetNumber.number
    const sn = ac.streetNumber || {}
    const tail = [ac.township, sn.street, sn.number].filter(Boolean).join('')
    form.detail = tail || (regeocode.formattedAddress
        ? regeocode.formattedAddress.replace(form.province, '').replace(form.city, '').replace(form.district, '')
        : '')
    form.address = regeocode.formattedAddress || joinAddress()
    regionNames.value = [form.province, form.city, form.district].filter(Boolean) as string[]
}

/**
 * 移动 / 创建 marker，并把地图视野中心定位到 [lng, lat]
 */
function placeMarker(lng: number, lat: number) {
    if (!mapInstance || !AMapNS) return
    if (markerInstance) markerInstance.setMap(null)
    markerInstance = new AMapNS.Marker({ position: [lng, lat] })
    markerInstance.setMap(mapInstance)
    mapInstance.setCenter([lng, lat])
    mapInstance.setZoom(Math.max(mapInstance.getZoom(), 15))
}

/**
 * 用文本（如「上海市浦东新区」）geocode → lnglat → 移动地图
 * 用于：选省市区 / 搜索选中后没有 location 时的兜底
 */
function geocodeAndFly(text: string) {
    if (!geocoder || !text) return
    geocoder.getLocation(text, (status: string, result: any) => {
        if (status === 'complete' && result.geocodes?.length) {
            const loc = result.geocodes[0].location
            const lng = loc.lng ?? loc.getLng?.()
            const lat = loc.lat ?? loc.getLat?.()
            if (lng && lat) {
                form.lng = +lng
                form.lat = +lat
                placeMarker(form.lng, form.lat)
            }
        }
    })
}

// ─── 三方联动 ───────────────────────────────────────────────────────────
function onRegionChange(payload: { ids: number[]; names: string[]; codes: string[] }) {
    form.province = payload.names[0] || ''
    form.city     = payload.names[1] || ''
    form.district = payload.names[2] || ''
    form.region_code = payload.codes[payload.codes.length - 1] || ''
    // 用文本 geocode 飞过去
    const text = [form.province, form.city, form.district].filter(Boolean).join('')
    if (text) geocodeAndFly(text)
}

// ─── AMap 初始化 ───────────────────────────────────────────────────────
async function initMap() {
    let cfg: any
    try {
        const res = await deliveryMapApi.getConfig()
        cfg = res.data
    } catch (e: any) {
        initFailed.value = true
        initFailedReason.value = '获取地图配置失败：' + (e?.message ?? '未知错误')
        return
    }

    if (!cfg || !cfg.js_api_key) {
        initFailed.value = true
        initFailedReason.value = '高德 JS API Key 未配置（系统设置 → 系统配置 → amap 分组）'
        return
    }
    ;(window as any)._AMapSecurityConfig = { securityJsCode: cfg.js_security_code }

    try {
        AMapNS = await AMapLoader.load({
            key: cfg.js_api_key,
            version: '2.0',
            plugins: ['AMap.Geocoder', 'AMap.AutoComplete', 'AMap.PlaceSearch'],
        })

        const center: [number, number] = (form.lng && form.lat)
            ? [form.lng, form.lat]
            : [116.4074, 39.9042]

        mapInstance = new AMapNS.Map(mapContainer.value!, { zoom: 13, center })
        geocoder = new AMapNS.Geocoder()

        if (form.lng && form.lat) {
            markerInstance = new AMapNS.Marker({ position: [form.lng, form.lat] })
            markerInstance.setMap(mapInstance)
        }

        // 编辑老数据：只有 lng/lat、缺省市区，自动反查回填一次
        if (form.lng && form.lat && !form.province) {
            geocoder.getAddress([form.lng, form.lat], (status: string, result: any) => {
                if (status === 'complete' && result.regeocode) {
                    applyRegeocode(result.regeocode)
                }
            })
        }

        // 地图点击 → 反查 → 同步全字段
        mapInstance.on('click', (e: any) => {
            const lng: number = e.lnglat.getLng()
            const lat: number = e.lnglat.getLat()
            form.lng = lng
            form.lat = lat
            placeMarker(lng, lat)
            geocoder.getAddress([lng, lat], (status: string, result: any) => {
                if (status === 'complete' && result.regeocode) {
                    applyRegeocode(result.regeocode)
                }
            })
        })

        // ── 地址搜索（AutoComplete） ──
        if (searchBox.value) {
            // input 元素由 el-input 渲染，找最近的 .el-input__inner 作为 AutoComplete 的输入容器
            const inputEl = searchBox.value.querySelector('.el-input__inner') as HTMLInputElement | null
            if (inputEl) {
                autoComplete = new AMapNS.AutoComplete({
                    input: inputEl,
                    citylimit: false,
                })
                autoComplete.on('select', (e: any) => {
                    const poi = e.poi
                    if (!poi) return
                    searchKeyword.value = poi.name || ''
                    if (poi.location) {
                        form.lng = +(poi.location.lng ?? poi.location.getLng?.())
                        form.lat = +(poi.location.lat ?? poi.location.getLat?.())
                        placeMarker(form.lng, form.lat)
                        // 反查省市区 + detail
                        geocoder.getAddress([form.lng, form.lat], (status: string, result: any) => {
                            if (status === 'complete' && result.regeocode) {
                                applyRegeocode(result.regeocode)
                                // 搜索命中通常 detail 已被覆盖，但如果搜索关键字更精准，用搜索结果
                                if (poi.address || poi.name) {
                                    form.detail = (poi.address || '') + (poi.name || '')
                                    form.address = joinAddress()
                                }
                            }
                        })
                    } else if (poi.adcode || poi.name) {
                        // 没坐标时按名字 geocode
                        geocodeAndFly(poi.district + poi.address + poi.name)
                    }
                })
                autoCompleteReady.value = true
            }
        }
    } catch (e: any) {
        initFailed.value = true
        initFailedReason.value = '高德地图加载失败：' + (e?.message ?? '未知错误')
    }
}

function destroyMap() {
    if (autoComplete) {
        try { autoComplete.destroy?.() } catch (e) { void e }
        autoComplete = null
    }
    if (mapInstance) {
        mapInstance.destroy?.()
        mapInstance = null
        markerInstance = null
        geocoder = null
    }
    AMapNS = null
    autoCompleteReady.value = false
    initFailed.value = false
    initFailedReason.value = ''
    searchKeyword.value = ''
}

// ─── Business-hours helpers ────────────────────────────────────────────────
function loadHoursToForm(hours?: BusinessHours) {
    for (const k of DAY_KEYS) {
        const slot = hours?.[k]
        if (!slot || slot === 'closed' || (Array.isArray(slot) && slot.length === 0)) {
            hoursOpen[k] = false
        } else if (Array.isArray(slot) && slot[0]) {
            hoursOpen[k] = true
            hoursStart[k] = slot[0].open
            hoursEnd[k] = slot[0].close
        }
    }
}

function buildHours(): BusinessHours {
    const hours: BusinessHours = {}
    for (const k of DAY_KEYS) {
        if (hoursOpen[k]) {
            hours[k] = [{ open: hoursStart[k], close: hoursEnd[k] }]
        } else {
            hours[k] = 'closed'
        }
    }
    return hours
}

function resetForm() {
    Object.assign(form, {
        name: '', code: '', address: '',
        province: '', city: '', district: '', detail: '', region_code: '',
        lng: 0, lat: 0, phone: '',
        business_hours: undefined, status: 1, sort: 100, remark: '',
    })
    regionNames.value = []
    for (const k of DAY_KEYS) {
        hoursOpen[k] = true
        hoursStart[k] = defaultStart
        hoursEnd[k] = defaultEnd
    }
    formRef.value?.clearValidate()
}

async function loadDetail(id: number) {
    loading.value = true
    try {
        const { data } = await storeApi.detail(id)
        Object.assign(form, data)
        // 老数据可能没有 province/city/district：优先从字段读，缺时由 initMap 反查 lng/lat 时回填
        regionNames.value = [form.province, form.city, form.district].filter(Boolean) as string[]
        loadHoursToForm(data.business_hours)
    } finally {
        loading.value = false
    }
}

// ─── Submit ────────────────────────────────────────────────────────────────
async function onSubmit() {
    if (!form.lng || !form.lat) {
        ElMessage.error('请在地图上点选门店位置')
        return
    }
    if (!form.province || !form.city || !form.district) {
        ElMessage.error('请选择所在地区')
        return
    }
    try {
        await formRef.value?.validate()
    } catch {
        return
    }
    form.business_hours = buildHours()
    // 提交前确保 address 是最新拼装
    form.address = joinAddress()
    saving.value = true
    try {
        if (isEdit.value) {
            await storeApi.update(props.formData!.id!, form)
        } else {
            await storeApi.create(form)
        }
        ElMessage.success('保存成功')
        emit('update:modelValue', false)
        emit('success')
    } finally {
        saving.value = false
    }
}

// ─── Lifecycle: watch dialog open/close ────────────────────────────────────
watch(() => props.modelValue, async (open) => {
    if (open) {
        resetForm()
        if (props.formData?.id) {
            await loadDetail(props.formData.id)
        }
    }
})

async function onDialogOpened() {
    await nextTick()
    await initMap()
}

function onDialogClosed() {
    destroyMap()
}
</script>

<style scoped lang="scss">
.store-form {
    max-height: 70vh;
    overflow-y: auto;
    padding-right: 8px;

    .section-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--el-text-color-primary);
        margin: 16px 0 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--el-border-color-lighter);

        &:first-child {
            margin-top: 0;
        }
    }
}

.field-hint {
    margin-left: 8px;
    font-size: 12px;
    color: var(--el-text-color-secondary);
}

.search-input-wrapper {
    width: 100%;
    position: relative;
}

.search-tip {
    margin-top: 4px;
    font-size: 11px;
    color: var(--el-text-color-secondary);
}

.map-wrapper {
    width: 100%;
    position: relative;
}

.map-canvas {
    width: 100%;
    height: 320px;
    border: 1px solid var(--el-border-color);
    border-radius: 4px;
    background: var(--el-fill-color-lighter);
}

.map-error {
    margin-top: 8px;
    color: var(--el-color-danger);
    font-size: 13px;
    padding: 8px 12px;
    background: var(--el-color-danger-light-9);
    border-radius: 4px;
    border: 1px solid var(--el-color-danger-light-5);
}

.map-tip {
    margin-top: 6px;
    font-size: 12px;
    color: var(--el-text-color-secondary);
}

.business-hours {
    display: flex;
    flex-direction: column;
    gap: 8px;
    width: 100%;
}

.day-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    flex-wrap: nowrap;

    &:not(:last-child) {
        border-bottom: 1px dashed var(--el-border-color-lighter);
    }
}

.day-label {
    width: 40px;
    flex-shrink: 0;
    font-size: 13px;
    color: var(--el-text-color-regular);
    font-weight: 500;
}

.day-switch {
    flex-shrink: 0;
}

.day-row__right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 0;
    flex-wrap: nowrap;
}

.day-time {
    width: 110px;
    flex-shrink: 0;
}

.separator {
    color: var(--el-text-color-secondary);
    flex-shrink: 0;
}

.closed-tag {
    display: inline-flex;
    align-items: center;
    height: 28px;
    padding: 0 12px;
    font-size: 13px;
    color: var(--el-color-warning);
    background: var(--el-color-warning-light-9);
    border: 1px solid var(--el-color-warning-light-7);
    border-radius: 4px;
    line-height: 1;
}
</style>

<style lang="scss">
/* AMap AutoComplete 下拉浮层 z-index（el-dialog z-index 默认 2001+，需要更高） */
.amap-sug-result {
    z-index: 3000 !important;
}
</style>
