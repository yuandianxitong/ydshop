<script setup lang="ts" name="LocalDeliveryConfig">
import { ElMessage } from 'element-plus'
import { computed, onMounted, reactive, ref } from 'vue'

import { type Store, storeApi } from '@/api/store'
import { batchUpdateConfigs, getConfigsByGroup } from '@/api/system/config'

const loading = ref(false)
const cfgDialogVisible = ref(false)

// 三方同城配送平台（与后端契约统一）
const PLATFORMS = [
    { code: 'dada', label: '达达配送' },
    { code: 'fengniao', label: '蜂鸟配送' },
    { code: 'uupt', label: 'UU跑腿' },
    { code: 'shansong', label: '闪送' },
    { code: 'sfsc', label: '顺丰同城' }
]

const platformLabelMap: Record<string, string> = {
    merchant: '商家配送',
    ...Object.fromEntries(PLATFORMS.map((p) => [p.code, p.label]))
}

// 每平台的门店编号字段（dada 特殊：source_id + shop_no，其余为 shop_id）
const platformExtraFields = (code: string) =>
    code === 'dada'
        ? [
              { key: `local_delivery_${code}_source_id`, label: '商户编号 (source_id)' },
              { key: `local_delivery_${code}_shop_no`, label: '门店编号 (shop_no)' }
          ]
        : [{ key: `local_delivery_${code}_shop_id`, label: '门店编号 (shop_id)' }]

// 各平台凭证配置 key（启用开关 / AppKey / AppSecret / 门店编号）
const platformConfigKeys = PLATFORMS.flatMap((p) => [
    `local_delivery_${p.code}_enabled`,
    `local_delivery_${p.code}_app_key`,
    `local_delivery_${p.code}_app_secret`,
    ...platformExtraFields(p.code).map((f) => f.key)
])

// system_configs 字段类型异构（数字范围 / 字符串 / 时间），统一用 any 简化双向绑定
const formData = reactive<Record<string, any>>({
    local_delivery_enabled: '0',
    local_delivery_platform: 'merchant',
    local_delivery_radius: undefined,
    local_delivery_fee: undefined,
    local_delivery_per_km_fee: undefined,
    local_delivery_free_amount: undefined,
    local_delivery_time_start: '',
    local_delivery_time_end: '',
    // 支付后自动发单
    local_delivery_auto_dispatch_enabled: '0',
    // 寄件门店信息（三方平台计费与骑手取件需要）
    local_delivery_shop_name: '',
    local_delivery_shop_phone: '',
    local_delivery_shop_province: '',
    local_delivery_shop_city: '',
    local_delivery_shop_district: '',
    local_delivery_shop_address: '',
    local_delivery_shop_lat: undefined,
    local_delivery_shop_lng: undefined,
    local_delivery_notify_domain: '',
    // 各三方平台凭证
    ...Object.fromEntries(platformConfigKeys.map((k) => [k, k.endsWith('_enabled') ? '0' : '']))
})

// 系统内置配送方式启用开关（持久化到 system_configs / local_delivery 分组）
const builtinEnabled = reactive({
    EXPRESS: true,
    PICKUP: true,
    OFFLINE: true
})

const builtinKeyMap: Record<keyof typeof builtinEnabled, string> = {
    EXPRESS: 'delivery_mode_express_enabled',
    PICKUP: 'delivery_mode_pickup_enabled',
    OFFLINE: 'delivery_mode_offline_enabled'
}

// 4 种配送方式（系统内置）
const modes = computed(() => [
    {
        code: 'EXPRESS',
        name: '快递',
        color: '#4f6bff',
        desc: '第三方物流配送，全国通达',
        enabled: builtinEnabled.EXPRESS,
        cfg: '由「物流公司」页配置承运商',
        configurable: false
    },
    {
        code: 'CITY',
        name: '同城配送',
        color: '#10b981',
        desc: '商家自有配送员，同城范围内送达',
        enabled: formData.local_delivery_enabled === '1',
        cfg: `范围 ${formData.local_delivery_radius || '-'} km · 起步 ¥${formData.local_delivery_fee || '-'} · 默认 ${platformLabelMap[formData.local_delivery_platform] || formData.local_delivery_platform}`,
        configurable: true
    },
    {
        code: 'PICKUP',
        name: '到店自提',
        color: '#f59e0b',
        desc: '门店仓现取，无需运费',
        enabled: builtinEnabled.PICKUP,
        cfg: '门店自提点维护中',
        configurable: false
    },
    {
        code: 'OFFLINE',
        name: '线下自提',
        color: '#8b5cf6',
        desc: '仓库自取，B 端客户',
        enabled: builtinEnabled.OFFLINE,
        cfg: '预约制 · 仓库自取',
        configurable: false
    }
])

const onModeSwitch = async (code: string, val: boolean) => {
    if (code === 'CITY') {
        formData.local_delivery_enabled = val ? '1' : '0'
        try {
            await batchUpdateConfigs([
                {
                    config_key: 'local_delivery_enabled',
                    config_value: formData.local_delivery_enabled
                }
            ])
            ElMessage.success(val ? '已启用同城配送' : '已停用同城配送')
        } catch {
            formData.local_delivery_enabled = val ? '0' : '1'
            ElMessage.error('状态更新失败')
        }
        return
    }

    const key = builtinKeyMap[code as keyof typeof builtinEnabled]
    if (!key) return
    ;(builtinEnabled as any)[code] = val
    try {
        await batchUpdateConfigs([{ config_key: key, config_value: val ? 1 : 0 }])
        ElMessage.success(val ? '已启用' : '已停用')
    } catch {
        ;(builtinEnabled as any)[code] = !val
        ElMessage.error('状态更新失败')
    }
}

// 门店自提点（从 storeApi.list 拉真实数据）
const stores = ref<Store[]>([])
const storesLoading = ref(false)

async function loadStores() {
    storesLoading.value = true
    try {
        const { data } = await storeApi.list({ limit: 100 })
        stores.value = data.list ?? []
    } catch {
        stores.value = []
    } finally {
        storesLoading.value = false
    }
}

function storeStatus(s: Store): { text: string; color: string } {
    if (s.status === 0) return { text: '已禁用', color: '#a1a1aa' }
    if (s.is_open_now === false) return { text: '已歇业', color: '#f59e0b' }
    return { text: '营业中', color: '#10b981' }
}

onMounted(async () => {
    try {
        loading.value = true
        const res = await getConfigsByGroup('local_delivery')
        const configs = res.data || []
        const reverseKeyMap: Record<string, keyof typeof builtinEnabled> = {}
        for (const k in builtinKeyMap)
            reverseKeyMap[builtinKeyMap[k as keyof typeof builtinEnabled]] =
                k as keyof typeof builtinEnabled
        // el-input-number 绑定的字段需转 Number，config_value 存储为字符串
        const numericKeys = new Set([
            'local_delivery_radius',
            'local_delivery_fee',
            'local_delivery_per_km_fee',
            'local_delivery_free_amount',
            'local_delivery_shop_lat',
            'local_delivery_shop_lng'
        ])
        configs.forEach((c: any) => {
            if (c.config_key in formData) {
                if (numericKeys.has(c.config_key)) {
                    const n = Number(c.config_value)
                    formData[c.config_key] = c.config_value === '' || c.config_value === null || Number.isNaN(n) ? undefined : n
                } else {
                    formData[c.config_key] = c.config_value || ''
                }
            } else if (reverseKeyMap[c.config_key]) {
                builtinEnabled[reverseKeyMap[c.config_key]] = String(c.config_value) !== '0'
            }
        })
    } finally {
        loading.value = false
    }
    loadStores()
})

const handleSaveCfg = async () => {
    try {
        loading.value = true
        const configs = Object.entries(formData).map(([key, value]) => ({
            config_key: key,
            config_value: value
        }))
        await batchUpdateConfigs(configs)
        ElMessage.success('保存成功')
        cfgDialogVisible.value = false
    } catch {
        ElMessage.error('保存配置失败')
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div v-loading="loading" class="local-config-container">
        <!-- 页头 -->
        <div class="page-head">
            <div>
                <h2 class="page-title">配送设置</h2>
                <p class="page-desc">系统内置四种配送方式，可针对每种方式进行配置</p>
            </div>
        </div>

        <!-- 配送方式卡片 -->
        <div class="row-14 modes-grid">
            <el-card v-for="m in modes" :key="m.code" class="mode-card" shadow="never">
                <div class="mode-bar" :style="{ background: m.color }" />
                <div class="flex flex-col gap-3 p-3.5 px-4.5 flex-1">
                    <div class="flex justify-between items-start gap-2.5">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="mode-name">{{ m.name }}</span>
                                <span class="mode-code num">{{ m.code }}</span>
                            </div>
                            <el-tag class="tag-tone-gray" size="small" effect="light"
                                >系统内置</el-tag
                            >
                            <div class="mode-desc">{{ m.desc }}</div>
                        </div>
                        <el-switch
                            :model-value="m.enabled"
                            @change="(val) => onModeSwitch(m.code, Boolean(val))"
                        />
                    </div>

                    <div class="mode-foot mt-auto pt-2.5 flex justify-between items-center">
                        <span class="mode-cfg">{{ m.cfg }}</span>
                        <el-button
                            v-if="m.configurable"
                            type="primary"
                            size="small"
                            @click="cfgDialogVisible = true"
                        >
                            <i class="i-svg:settings mr-1" /> 配置
                        </el-button>
                    </div>
                </div>
            </el-card>
        </div>

        <!-- 门店自提点（v2.4.0 自提模块数据） -->
        <el-card v-loading="storesLoading" class="store-card" shadow="never">
            <div class="card-head">
                <div class="card-title">门店自提点</div>
                <div class="card-meta">
                    {{ stores.length }} 家门店 ·
                    <router-link to="/store/index" class="store-link">前往门店管理</router-link>
                </div>
            </div>
            <div class="card-body">
                <el-empty
                    v-if="!storesLoading && stores.length === 0"
                    description="暂无门店，前往门店管理添加"
                    :image-size="80"
                />
                <div v-else class="stores-grid">
                    <div
                        v-for="s in stores"
                        :key="s.id"
                        class="store-cell"
                        @click="$router.push('/store/index')"
                    >
                        <div class="store-head">
                            <div class="store-name">{{ s.name }}</div>
                            <span class="store-status" :style="{ color: storeStatus(s).color }">
                                <i
                                    class="store-dot"
                                    :style="{ background: storeStatus(s).color }"
                                />
                                {{ storeStatus(s).text }}
                            </span>
                        </div>
                        <div class="store-addr">
                            <i class="i-svg:map-pin" />
                            {{ s.address }}
                        </div>
                    </div>
                </div>
            </div>
        </el-card>

        <!-- 同城配送 配置弹窗 -->
        <el-dialog v-model="cfgDialogVisible" title="同城配送配置" width="720px" destroy-on-close>
            <el-tabs class="cfg-tabs">
                <el-tab-pane label="同城配送">
                    <el-form :model="formData" label-width="140px">
                        <el-form-item label="启用同城配送">
                            <el-switch
                                v-model="formData.local_delivery_enabled"
                                active-value="1"
                                inactive-value="0"
                            />
                        </el-form-item>
                        <el-form-item label="默认发单平台">
                            <el-select
                                v-model="formData.local_delivery_platform"
                                style="width: 100%"
                            >
                                <el-option label="商家配送" value="merchant" />
                                <el-option
                                    v-for="p in PLATFORMS"
                                    :key="p.code"
                                    :label="p.label"
                                    :value="p.code"
                                />
                            </el-select>
                            <div class="form-tip">
                                商家配送使用后台配送员、排班与自动派单能力；选择三方平台后可配合「支付后自动发单」自动向该平台下单。
                            </div>
                        </el-form-item>
                        <el-form-item label="支付后自动发单">
                            <el-switch
                                v-model="formData.local_delivery_auto_dispatch_enabled"
                                active-value="1"
                                inactive-value="0"
                            />
                            <div class="form-tip">
                                开启后订单支付成功自动向默认发单平台下单，仅默认平台为三方平台时生效
                            </div>
                        </el-form-item>
                        <el-form-item label="配送范围 (km)">
                            <el-input-number
                                v-model="formData.local_delivery_radius"
                                :min="0"
                                controls-position="right"
                            />
                        </el-form-item>
                        <el-form-item label="起步费 (元)">
                            <el-input-number
                                v-model="formData.local_delivery_fee"
                                :min="0"
                                :precision="2"
                                controls-position="right"
                            />
                            <div class="form-tip">不论距离都收取的固定费用</div>
                        </el-form-item>
                        <el-form-item label="每公里费 (元)">
                            <el-input-number
                                v-model="formData.local_delivery_per_km_fee"
                                :min="0"
                                :precision="2"
                                controls-position="right"
                            />
                            <div class="form-tip">
                                实际运费 = 起步费 + ceil(距离公里) × 每公里费
                            </div>
                        </el-form-item>
                        <el-form-item label="免配送费金额">
                            <el-input-number
                                v-model="formData.local_delivery_free_amount"
                                :min="0"
                                :precision="2"
                                controls-position="right"
                            />
                            <div class="form-tip">订单金额满 X 元免配送费，0 表示不启用</div>
                        </el-form-item>
                        <el-form-item label="配送开始时间">
                            <el-time-select
                                v-model="formData.local_delivery_time_start"
                                start="00:00"
                                step="00:30"
                                end="23:30"
                                placeholder="开始时间"
                                style="width: 100%"
                            />
                        </el-form-item>
                        <el-form-item label="配送结束时间">
                            <el-time-select
                                v-model="formData.local_delivery_time_end"
                                start="00:00"
                                step="00:30"
                                end="23:30"
                                placeholder="结束时间"
                                style="width: 100%"
                            />
                        </el-form-item>

                        <el-divider content-position="left">寄件门店信息</el-divider>
                        <div class="form-tip section-tip">
                            三方平台计费与骑手取件需要，请如实填写
                        </div>
                        <el-form-item label="门店名称">
                            <el-input
                                v-model="formData.local_delivery_shop_name"
                                placeholder="请输入门店名称"
                            />
                        </el-form-item>
                        <el-form-item label="门店电话">
                            <el-input
                                v-model="formData.local_delivery_shop_phone"
                                placeholder="请输入门店联系电话"
                            />
                        </el-form-item>
                        <el-form-item label="省份">
                            <el-input
                                v-model="formData.local_delivery_shop_province"
                                placeholder="如：广东省"
                            />
                        </el-form-item>
                        <el-form-item label="城市">
                            <el-input
                                v-model="formData.local_delivery_shop_city"
                                placeholder="如：深圳市"
                            />
                        </el-form-item>
                        <el-form-item label="区县">
                            <el-input
                                v-model="formData.local_delivery_shop_district"
                                placeholder="如：南山区"
                            />
                        </el-form-item>
                        <el-form-item label="详细地址">
                            <el-input
                                v-model="formData.local_delivery_shop_address"
                                placeholder="街道门牌号等详细地址"
                            />
                        </el-form-item>
                        <el-form-item label="门店纬度">
                            <el-input-number
                                v-model="formData.local_delivery_shop_lat"
                                :min="-90"
                                :max="90"
                                :precision="6"
                                controls-position="right"
                                style="width: 100%"
                            />
                        </el-form-item>
                        <el-form-item label="门店经度">
                            <el-input-number
                                v-model="formData.local_delivery_shop_lng"
                                :min="-180"
                                :max="180"
                                :precision="6"
                                controls-position="right"
                                style="width: 100%"
                            />
                            <div class="form-tip">经纬度用于三方平台距离计费与骑手取件定位</div>
                        </el-form-item>
                        <el-form-item label="回调通知域名">
                            <el-input
                                v-model="formData.local_delivery_notify_domain"
                                placeholder="https://shop.example.com"
                            />
                            <div class="form-tip">
                                三方平台回调地址域名（含协议），留空时使用当前请求域名；自动发单等后台场景建议填写
                            </div>
                        </el-form-item>
                    </el-form>
                </el-tab-pane>

                <el-tab-pane v-for="p in PLATFORMS" :key="p.code" :label="p.label">
                    <div class="form-tip platform-tip">
                        {{ p.label }} · 回调地址：https://你的域名/api/delivery/callback/{{
                            p.code
                        }}，请在平台开放平台后台配置
                    </div>
                    <el-form :model="formData" label-width="140px">
                        <el-form-item label="启用">
                            <el-switch
                                v-model="formData[`local_delivery_${p.code}_enabled`]"
                                active-value="1"
                                inactive-value="0"
                            />
                        </el-form-item>
                        <el-form-item label="AppKey">
                            <el-input
                                v-model="formData[`local_delivery_${p.code}_app_key`]"
                                placeholder="平台开放平台申请的 AppKey"
                            />
                        </el-form-item>
                        <el-form-item label="AppSecret">
                            <el-input
                                v-model="formData[`local_delivery_${p.code}_app_secret`]"
                                type="password"
                                show-password
                                placeholder="平台开放平台申请的 AppSecret"
                            />
                        </el-form-item>
                        <el-form-item
                            v-for="f in platformExtraFields(p.code)"
                            :key="f.key"
                            :label="f.label"
                        >
                            <el-input v-model="formData[f.key]" :placeholder="`请输入${f.label}`" />
                        </el-form-item>
                    </el-form>
                </el-tab-pane>
            </el-tabs>

            <template #footer>
                <el-button @click="cfgDialogVisible = false">取消</el-button>
                <el-button type="primary" :loading="loading" @click="handleSaveCfg"
                    >保存配置</el-button
                >
            </template>
        </el-dialog>
    </div>
</template>

<style lang="scss" scoped>
.local-config-container {
    padding: 0;
}

:deep(.el-card__body) {
    padding: 0;
}

// ── 配送方式卡片（布局走 UnoCSS，仅保留视觉/排版相关样式）──
.mode-card {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.mode-bar {
    height: 6px;
}

.mode-name {
    font-size: 16px;
    font-weight: 600;
    color: var(--ink-900);
}

.mode-code {
    font-size: 10.5px;
    color: var(--ink-400);
    background: var(--ink-50);
    padding: 2px 6px;
    border-radius: 4px;
    letter-spacing: 0.4px;
}

.mode-desc {
    font-size: 11.5px;
    color: var(--ink-500);
    margin-top: 8px;
    line-height: 1.5;
}

.mode-foot {
    border-top: 1px dashed var(--ink-100);
    gap: 10px;
}

.mode-cfg {
    font-size: 11.5px;
    color: var(--ink-500);
    flex: 1;
    min-width: 0;
}

// ── 弹窗 tab ──
.cfg-tabs {
    margin-top: -10px;
}

.form-tip {
    font-size: 12px;
    color: var(--ink-400);
    margin-top: 4px;
}

.section-tip {
    margin: -4px 0 12px;
}

.platform-tip {
    margin: 0 0 14px;
    padding: 8px 12px;
    background: var(--ink-50);
    border-radius: 6px;
    line-height: 1.6;
    word-break: break-all;
}

// ── 门店自提点 ──
.stores-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}

@media (max-width: 1280px) {
    .stores-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.store-cell {
    padding: 12px 14px;
    background: var(--ink-50);
    border: 1px solid var(--ink-100);
    border-radius: 8px;
    transition:
        border-color 0.15s,
        box-shadow 0.15s;

    &:hover {
        border-color: var(--brand-200);
        box-shadow: var(--shadow-xs);
    }
}

.store-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.store-name {
    font-size: 13px;
    font-weight: 500;
    color: var(--ink-900);
}

.store-status {
    font-size: 11px;
    display: flex;
    align-items: center;
    gap: 4px;
    font-weight: 500;
}

.store-dot {
    width: 6px;
    height: 6px;
    border-radius: 3px;
}

.store-addr {
    font-size: 11.5px;
    color: var(--ink-500);
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 4px;
}

:deep(.el-card) {
    --el-card-border-color: var(--ink-100);
    border-radius: var(--r-lg);
    box-shadow: var(--shadow-sm) !important;
}
</style>
