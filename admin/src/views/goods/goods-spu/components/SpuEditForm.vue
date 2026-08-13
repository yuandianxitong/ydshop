<template>
    <div class="spu-edit-form">
        <!-- 可滚动内容区；详情 Tab 时改为填满高度 -->
        <div class="spu-edit-form__body" :class="{ 'is-detail-tab': activeTab === 'detail' }">
            <!-- 标签页 -->
            <el-tabs v-model="activeTab" class="spu-edit-tabs">
                <!-- Tab 1: 基本信息 -->
                <el-tab-pane label="基本信息" name="basic">
                    <el-form
                        ref="basicFormRef"
                        :model="form"
                        :rules="basicRules"
                        label-width="120px"
                        class="spu-form"
                    >
                        <el-form-item label="商品名称" prop="name">
                            <el-input
                                v-model="form.name"
                                placeholder="请输入商品名称"
                                style="width: 400px"
                            />
                            <AiTrigger
                                scene="title"
                                :context="() => ({ ...buildAiContext(), current_text: form.name })"
                                style="margin-left: 16px; vertical-align: middle"
                                @adopt="onAdoptTitle"
                            />
                        </el-form-item>

                        <el-form-item label="副标题" prop="subtitle">
                            <el-input
                                v-model="form.subtitle"
                                placeholder="请输入副标题"
                                style="width: 400px"
                            />
                            <AiTrigger
                                scene="description"
                                :context="
                                    () => ({ ...buildAiContext(), current_text: form.subtitle })
                                "
                                style="margin-left: 16px; vertical-align: middle"
                                @adopt="onAdoptDescription"
                            />
                        </el-form-item>

                        <el-form-item label="商品类型" prop="type">
                            <el-radio-group v-model="form.type" @change="handleTypeChange">
                                <el-radio value="physical">实物商品</el-radio>
                                <el-radio value="virtual">虚拟商品</el-radio>
                                <el-radio value="combo">组合商品</el-radio>
                            </el-radio-group>
                        </el-form-item>

                        <el-form-item label="商品分类" prop="category_id">
                            <el-tree-select
                                v-model="form.category_id"
                                :data="categoryTree"
                                :props="{ label: 'name', value: 'id', children: 'children' } as any"
                                placeholder="请选择商品分类"
                                clearable
                                check-strictly
                                style="width: 300px"
                                @change="handleCategoryChange"
                            />
                        </el-form-item>

                        <el-form-item label="商品品牌" prop="brand_id">
                            <el-select
                                v-model="form.brand_id"
                                placeholder="请选择品牌"
                                clearable
                                filterable
                                style="width: 240px"
                            >
                                <el-option
                                    v-for="item in brandList"
                                    :key="item.id"
                                    :label="item.name"
                                    :value="item.id"
                                />
                            </el-select>
                        </el-form-item>

                        <el-form-item label="商品单位" prop="unit_id">
                            <el-select
                                v-model="form.unit_id"
                                placeholder="请选择单位"
                                clearable
                                style="width: 160px"
                            >
                                <el-option
                                    v-for="item in unitList"
                                    :key="item.id"
                                    :label="item.name"
                                    :value="item.id"
                                />
                            </el-select>
                        </el-form-item>

                        <el-form-item
                            v-if="form.type === 'physical'"
                            label="运费模板"
                            prop="freight_template_id"
                        >
                            <el-select
                                v-model="form.freight_template_id"
                                placeholder="请选择运费模板"
                                clearable
                                style="width: 240px"
                            >
                                <el-option
                                    v-for="item in freightTemplateList"
                                    :key="item.id"
                                    :label="item.name"
                                    :value="item.id"
                                />
                            </el-select>
                        </el-form-item>

                        <el-form-item label="配送方式">
                            <div>
                                <el-checkbox-group v-model="form.delivery_modes">
                                    <el-checkbox value="express">快递配送</el-checkbox>
                                    <el-checkbox value="merchant">商家即时配送</el-checkbox>
                                    <el-checkbox value="pickup">用户自提门店</el-checkbox>
                                </el-checkbox-group>
                                <div class="form-tip">未勾选时保存后默认仅快递</div>
                            </div>
                        </el-form-item>

                        <el-form-item label="商品图片" prop="images">
                            <div>
                                <ImageSelect v-model="form.images" multiple :limit="9" />
                                <div class="form-tip">
                                    第一张为商品主图，建议尺寸 800x800，最多 9 张
                                </div>
                                <div style="margin-top: 8px; display: flex; gap: 8px">
                                    <AiTrigger
                                        scene="cover"
                                        :context="buildAiContext"
                                        @adopt="(urls) => onAdoptImages(urls, 'cover')"
                                    />
                                    <AiTrigger
                                        scene="gallery"
                                        :context="buildAiContext"
                                        @adopt="(urls) => onAdoptImages(urls, 'gallery')"
                                    />
                                </div>
                            </div>
                        </el-form-item>

                        <el-form-item label="商品状态" prop="status">
                            <el-radio-group v-model="form.status">
                                <el-radio value="on_sale">上架</el-radio>
                                <el-radio value="off_sale">下架</el-radio>
                            </el-radio-group>
                        </el-form-item>

                        <el-form-item label="排序" prop="sort">
                            <el-input-number
                                v-model="form.sort"
                                :min="0"
                                controls-position="right"
                            />
                            <span class="form-tip" style="margin-left: 8px">数值越大越靠前</span>
                        </el-form-item>

                        <el-form-item label="商品标签">
                            <div class="switch-group">
                                <div class="switch-item">
                                    <span class="switch-label">推荐</span>
                                    <el-switch
                                        v-model="form.is_recommend"
                                        :active-value="1"
                                        :inactive-value="0"
                                    />
                                </div>
                                <div class="switch-item">
                                    <span class="switch-label">新品</span>
                                    <el-switch
                                        v-model="form.is_new"
                                        :active-value="1"
                                        :inactive-value="0"
                                    />
                                </div>
                                <div class="switch-item">
                                    <span class="switch-label">热卖</span>
                                    <el-switch
                                        v-model="form.is_hot"
                                        :active-value="1"
                                        :inactive-value="0"
                                    />
                                </div>
                            </div>
                        </el-form-item>
                    </el-form>
                </el-tab-pane>

                <!-- Tab 2: 规格/SKU -->
                <el-tab-pane v-if="form.type !== 'combo'" label="规格/SKU" name="specs">
                    <!-- 规格类型切换 -->
                    <div class="spec-type-switch">
                        <span class="spec-type-label">规格类型</span>
                        <el-radio-group v-model="specType" @change="handleSpecTypeChange">
                            <el-radio value="single">单规格</el-radio>
                            <el-radio value="multi">多规格</el-radio>
                        </el-radio-group>
                    </div>

                    <!-- 单规格模式 -->
                    <div v-if="specType === 'single'" class="single-sku-section">
                        <el-form label-width="100px">
                            <el-form-item label="售价(元)">
                                <el-input-number
                                    v-model="singleSku.price"
                                    :min="0"
                                    :precision="2"
                                    controls-position="right"
                                    style="width: 240px"
                                />
                            </el-form-item>
                            <el-form-item label="成本价(元)">
                                <el-input-number
                                    v-model="singleSku.cost_price"
                                    :min="0"
                                    :precision="2"
                                    controls-position="right"
                                    style="width: 240px"
                                />
                            </el-form-item>
                            <el-form-item label="市场价(元)">
                                <el-input-number
                                    v-model="singleSku.market_price"
                                    :min="0"
                                    :precision="2"
                                    controls-position="right"
                                    style="width: 240px"
                                />
                            </el-form-item>
                            <el-form-item label="库存">
                                <el-input-number
                                    v-model="singleSku.stock"
                                    :min="0"
                                    controls-position="right"
                                    style="width: 240px"
                                />
                            </el-form-item>
                            <el-form-item label="重量(g)">
                                <el-input-number
                                    v-model="singleSku.weight"
                                    :min="0"
                                    :precision="1"
                                    controls-position="right"
                                    style="width: 240px"
                                />
                            </el-form-item>
                        </el-form>
                    </div>

                    <!-- 多规格模式 -->
                    <template v-else>
                        <div class="spec-template-bar">
                            <span class="spec-template-label">应用规格模板</span>
                            <el-select
                                v-model="selectedTemplateId"
                                filterable
                                clearable
                                placeholder="选择已预设的规格模板"
                                style="width: 320px"
                                :loading="specTemplateLoading"
                                @change="handleApplyTemplate"
                            >
                                <el-option
                                    v-for="t in specTemplateOptions"
                                    :key="t.id"
                                    :label="t.name"
                                    :value="t.id"
                                >
                                    <span>{{ t.name }}</span>
                                    <span class="spec-template-meta">
                                        {{ (t.items || []).map((i) => i.name).join(' / ') }}
                                    </span>
                                </el-option>
                            </el-select>
                            <span class="spec-template-hint"
                                >选中后自动填充规格名与值，已有规格会提示是否覆盖。</span
                            >
                        </div>

                        <div class="specs-section">
                            <div class="section-title">规格设置</div>

                            <!-- 规格列表 -->
                            <div
                                v-for="(spec, specIdx) in form.specs"
                                :key="specIdx"
                                class="spec-row"
                            >
                                <div class="spec-header">
                                    <el-input
                                        v-model="spec.name"
                                        placeholder="规格名称，如：颜色、尺寸"
                                        style="width: 200px"
                                        @change="regenerateSkus"
                                    />
                                    <el-button
                                        type="danger"
                                        size="small"
                                        text
                                        @click="removeSpec(specIdx)"
                                    >
                                        删除规格
                                    </el-button>
                                </div>
                                <div class="spec-values">
                                    <el-tag
                                        v-for="(val, valIdx) in spec.values"
                                        :key="valIdx"
                                        closable
                                        class="spec-tag"
                                        @close="removeSpecValue(specIdx, valIdx)"
                                    >
                                        {{ val }}
                                    </el-tag>
                                    <el-input
                                        v-if="specValueInputVisible[specIdx]"
                                        :ref="(el) => setSpecValueInputRef(el, specIdx)"
                                        v-model="specValueInputs[specIdx]"
                                        size="small"
                                        style="width: 120px"
                                        @keyup.enter="confirmSpecValue(specIdx)"
                                        @blur="confirmSpecValue(specIdx)"
                                    />
                                    <el-button
                                        v-else
                                        size="small"
                                        @click="showSpecValueInput(specIdx)"
                                    >
                                        + 添加值
                                    </el-button>
                                </div>
                            </div>

                            <el-button plain style="margin-top: 8px" @click="addSpec">
                                <i class="i-lucide:plus mr-1" />
                                添加规格
                            </el-button>
                        </div>

                        <!-- SKU 表格 -->
                        <div v-if="form.skus.length > 0" class="sku-section">
                            <div class="section-title">SKU 列表</div>

                            <!-- 批量赋值 -->
                            <div class="batch-assign">
                                <span class="batch-label">批量赋值</span>
                                <el-input-number
                                    v-model="batchForm.price"
                                    :min="0"
                                    :precision="2"
                                    controls-position="right"
                                    size="small"
                                    placeholder="售价"
                                    style="width: 110px"
                                />
                                <el-input-number
                                    v-model="batchForm.cost_price"
                                    :min="0"
                                    :precision="2"
                                    controls-position="right"
                                    size="small"
                                    placeholder="成本价"
                                    style="width: 110px"
                                />
                                <el-input-number
                                    v-model="batchForm.market_price"
                                    :min="0"
                                    :precision="2"
                                    controls-position="right"
                                    size="small"
                                    placeholder="市场价"
                                    style="width: 110px"
                                />
                                <el-input-number
                                    v-model="batchForm.stock"
                                    :min="0"
                                    controls-position="right"
                                    size="small"
                                    placeholder="库存"
                                    style="width: 100px"
                                />
                                <el-input-number
                                    v-model="batchForm.weight"
                                    :min="0"
                                    :precision="1"
                                    controls-position="right"
                                    size="small"
                                    placeholder="重量"
                                    style="width: 100px"
                                />
                                <el-button type="primary" size="small" @click="applyBatch"
                                    >批量应用</el-button
                                >
                            </div>

                            <el-table :data="form.skus" border>
                                <el-table-column
                                    v-for="spec in form.specs"
                                    :key="spec.name"
                                    :label="spec.name"
                                    :prop="`spec_values.${spec.name}`"
                                    min-width="100"
                                >
                                    <template #default="{ row }">
                                        {{ row.spec_values?.[spec.name] || '-' }}
                                    </template>
                                </el-table-column>

                                <el-table-column label="SKU图片" width="100" align="center">
                                    <template #default="{ row }">
                                        <ImageSelect v-model="row.image" />
                                    </template>
                                </el-table-column>

                                <el-table-column label="售价(元)" min-width="130">
                                    <template #default="{ row }">
                                        <el-input-number
                                            v-model="row.price"
                                            :min="0"
                                            controls-position="right"
                                            size="small"
                                            style="width: 100%"
                                        />
                                    </template>
                                </el-table-column>

                                <el-table-column label="成本价(元)" min-width="130">
                                    <template #default="{ row }">
                                        <el-input-number
                                            v-model="row.cost_price"
                                            :min="0"
                                            controls-position="right"
                                            size="small"
                                            style="width: 100%"
                                        />
                                    </template>
                                </el-table-column>

                                <el-table-column label="市场价(元)" min-width="130">
                                    <template #default="{ row }">
                                        <el-input-number
                                            v-model="row.market_price"
                                            :min="0"
                                            controls-position="right"
                                            size="small"
                                            style="width: 100%"
                                        />
                                    </template>
                                </el-table-column>

                                <el-table-column label="库存" min-width="110">
                                    <template #default="{ row }">
                                        <el-input-number
                                            v-model="row.stock"
                                            :min="0"
                                            controls-position="right"
                                            size="small"
                                            style="width: 100%"
                                        />
                                    </template>
                                </el-table-column>

                                <el-table-column label="重量(g)" min-width="110">
                                    <template #default="{ row }">
                                        <el-input-number
                                            v-model="row.weight"
                                            :min="0"
                                            controls-position="right"
                                            size="small"
                                            style="width: 100%"
                                        />
                                    </template>
                                </el-table-column>
                            </el-table>
                        </div>

                        <el-empty
                            v-else-if="form.specs.length === 0"
                            description="请先添加规格，系统将自动生成SKU列表"
                            :image-size="80"
                        />
                    </template>
                </el-tab-pane>

                <!-- Tab 3: 商品属性 -->
                <el-tab-pane label="商品属性" name="attrs">
                    <div v-if="attrGroups.length === 0" class="attrs-empty">
                        <el-empty
                            description="请先在基本信息中选择商品分类，以加载对应属性"
                            :image-size="80"
                        />
                    </div>
                    <div v-else>
                        <div v-for="group in attrGroups" :key="group.id" class="attr-group">
                            <div class="attr-group-title">{{ group.name }}</div>
                            <el-form label-width="120px">
                                <el-form-item
                                    v-for="attr in group.attributes"
                                    :key="attr.id"
                                    :label="attr.name"
                                >
                                    <!-- 输入类型 -->
                                    <el-input
                                        v-if="attr.type === 'input'"
                                        v-model="attrValues[attr.id]"
                                        placeholder="请输入属性值"
                                        style="width: 300px"
                                    />
                                    <!-- 单选类型 -->
                                    <el-select
                                        v-else-if="attr.type === 'select'"
                                        v-model="attrValues[attr.id]"
                                        placeholder="请选择"
                                        clearable
                                        style="width: 200px"
                                    >
                                        <el-option
                                            v-for="opt in attr.options || []"
                                            :key="opt"
                                            :label="opt"
                                            :value="opt"
                                        />
                                    </el-select>
                                    <!-- 多选类型 -->
                                    <el-select
                                        v-else-if="attr.type === 'multi_select'"
                                        v-model="attrValues[attr.id]"
                                        placeholder="请选择"
                                        clearable
                                        multiple
                                        style="width: 300px"
                                    >
                                        <el-option
                                            v-for="opt in attr.options || []"
                                            :key="opt"
                                            :label="opt"
                                            :value="opt"
                                        />
                                    </el-select>
                                    <!-- 未知类型降级为输入 -->
                                    <el-input
                                        v-else
                                        v-model="attrValues[attr.id]"
                                        placeholder="请输入属性值"
                                        style="width: 300px"
                                    />
                                </el-form-item>
                            </el-form>
                        </div>
                    </div>
                </el-tab-pane>

                <!-- Tab 4: 商品详情 -->
                <el-tab-pane label="商品详情" name="detail" class="detail-tab-pane">
                    <div class="detail-editor">
                        <WangEditor v-model="form.detail" fill>
                            <template #toolbar-right>
                                <AiTrigger
                                    scene="detail"
                                    :context="
                                        () => ({
                                            ...buildAiContext(),
                                            current_text: form.detail
                                        })
                                    "
                                    @adopt="onAdoptDetail"
                                />
                            </template>
                        </WangEditor>
                    </div>
                </el-tab-pane>
            </el-tabs>
        </div>

        <!-- 底部按钮 - 固定在抽屉底部 -->
        <div class="form-footer">
            <el-button @click="emit('cancel')">取消</el-button>
            <el-button type="primary" :loading="submitLoading" @click="handleSave">
                保存
            </el-button>
        </div>
    </div>
</template>

<script setup lang="ts" name="SpuEditForm">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, defineAsyncComponent, nextTick, onMounted, reactive, ref } from 'vue'

import { goodsAttributeGroupApi } from '@/api/goods-attribute-group'
import { goodsBrandApi } from '@/api/goods-brand'
import { goodsCategoryApi } from '@/api/goods-category'
import { goodsFreightTemplateApi } from '@/api/goods-freight-template'
import { goodsSpecTemplateApi, type GoodsSpecTemplateInfo } from '@/api/goods-spec-template'
import { goodsSpuApi } from '@/api/goods-spu'
import { goodsUnitApi } from '@/api/goods-unit'
import ImageSelect from '@/components/ImageSelect/index.vue'
import WangEditor from '@/components/WangEditor/index.vue'

const aiTriggerModules = import.meta.glob('../../../../components/ai/AiTrigger.vue')
const AiTrigger = defineAsyncComponent(async () => {
    const loader = Object.values(aiTriggerModules)[0]
    if (!loader) {
        return { render: () => null }
    }
    return loader()
})

// ==============================
// Props & Emits
// ==============================

const props = defineProps<{
    spuId: number
}>()

const emit = defineEmits<{
    (e: 'success'): void
    (e: 'cancel'): void
}>()

const isEdit = computed(() => props.spuId > 0)

// ==============================
// Form state
// ==============================

const activeTab = ref('basic')
const basicFormRef = ref()

const form = reactive({
    name: '',
    subtitle: '',
    type: 'physical' as 'physical' | 'virtual' | 'combo',
    category_id: undefined as number | undefined,
    brand_id: undefined as number | undefined,
    unit_id: undefined as number | undefined,
    freight_template_id: undefined as number | undefined,
    delivery_modes: ['express'] as string[],
    images: [] as string[],
    status: 'on_sale' as 'draft' | 'on_sale' | 'off_sale',
    sort: 0,
    is_recommend: 0,
    is_new: 0,
    is_hot: 0,
    specs: [] as { name: string; values: string[] }[],
    skus: [] as Record<string, any>[],
    detail: ''
})

// 规格类型：单规格 / 多规格
const specType = ref<'single' | 'multi'>('single')

const singleSku = reactive({
    price: 0,
    cost_price: 0,
    market_price: 0,
    stock: 0,
    weight: 0
})

const handleSpecTypeChange = async (val: string | number | boolean | undefined) => {
    if (val === 'single' && (form.specs.length > 0 || form.skus.length > 0)) {
        try {
            await ElMessageBox.confirm(
                '切换为单规格将清空已设置的规格和SKU数据，确定切换吗？',
                '切换确认',
                { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' }
            )
            form.specs = []
            form.skus = []
        } catch {
            specType.value = 'multi'
        }
    }
}

const basicRules = {
    name: [{ required: true, message: '请输入商品名称', trigger: 'blur' }],
    type: [{ required: true, message: '请选择商品类型', trigger: 'change' }],
    category_id: [{ required: true, message: '请选择商品分类', trigger: 'change' }]
}

const categoryTree = ref<Record<string, any>[]>([])
const brandList = ref<Record<string, any>[]>([])
const unitList = ref<Record<string, any>[]>([])
const freightTemplateList = ref<Record<string, any>[]>([])
const attrGroups = ref<Record<string, any>[]>([])
const attrValues = reactive<Record<number, any>>({})

const specValueInputVisible = ref<Record<number, boolean>>({})
const specValueInputs = ref<Record<number, string>>({})
const specValueInputRefs = ref<Record<number, any>>({})

// 规格模板下拉
const specTemplateOptions = ref<GoodsSpecTemplateInfo[]>([])
const specTemplateLoading = ref(false)
const selectedTemplateId = ref<number | undefined>(undefined)

const submitLoading = ref(false)

// ==============================
// AI 助手 - 上下文构建与采纳回调
// ==============================

const getCategoryLabel = (): string => {
    if (!form.category_id) return ''
    const findInTree = (nodes: Record<string, any>[]): string => {
        for (const n of nodes) {
            if (n.id === form.category_id) return n.name || ''
            if (n.children?.length) {
                const found = findInTree(n.children)
                if (found) return found
            }
        }
        return ''
    }
    return findInTree(categoryTree.value) || ''
}

const getBrandLabel = (): string => {
    const b = brandList.value?.find((x: any) => x.id === form.brand_id)
    return (b?.name as string) || ''
}

const getAttributesText = (): string => {
    const lines: string[] = []
    for (const grp of attrGroups.value || []) {
        for (const attr of grp.attributes || []) {
            const v = attrValues[attr.id]
            if (v === undefined || v === null || v === '') continue
            const valText = Array.isArray(v) ? v.join('、') : String(v)
            lines.push(`${attr.name}：${valText}`)
        }
    }
    return lines.join('；')
}

const buildAiContext = () => {
    const specsText = (form.specs || [])
        .filter((s) => s.name && s.values?.length)
        .map((s) => `${s.name}：${s.values.join('、')}`)
        .join('；')
    return {
        spu_id: props.spuId > 0 ? props.spuId : undefined,
        name: form.name,
        category: getCategoryLabel(),
        brand: getBrandLabel(),
        specs: specsText,
        attributes: getAttributesText(),
        current_text: ''
    }
}

const onAdoptTitle = (val: string | string[]) => {
    form.name = Array.isArray(val) ? val[0] : val
}

const onAdoptDescription = (val: string | string[]) => {
    form.subtitle = Array.isArray(val) ? val[0] : val
}

const onAdoptDetail = (val: string | string[]) => {
    form.detail = Array.isArray(val) ? val[0] : val
}

const onAdoptImages = (val: string | string[], slot: 'cover' | 'gallery') => {
    const urls = Array.isArray(val) ? val : [val]
    if (!form.images) form.images = []
    if (slot === 'cover') {
        form.images.unshift(...urls)
    } else {
        form.images.push(...urls)
    }
    form.images = Array.from(new Set(form.images)).slice(0, 9)
}

// ==============================
// Data loading
// ==============================

const loadOptions = async () => {
    try {
        const [catRes, brandRes, unitRes, freightRes] = await Promise.all([
            goodsCategoryApi.getTree(),
            goodsBrandApi.getList({ limit: 200, status: 1 }),
            goodsUnitApi.getList({ limit: 200, status: 1 }),
            goodsFreightTemplateApi.getList({ limit: 200 })
        ])
        categoryTree.value = catRes.data || []
        brandList.value = brandRes.data?.list || []
        unitList.value = unitRes.data?.list || []
        freightTemplateList.value = freightRes.data?.list || []
    } catch (error) {
        console.error('加载选项数据失败:', error)
    }
}

const loadSpuDetail = async () => {
    if (!isEdit.value) return
    try {
        const res = await goodsSpuApi.getDetail(props.spuId)
        const data = res.data
        Object.assign(form, {
            name: data.name || '',
            subtitle: data.subtitle || '',
            type: data.type || 'physical',
            category_id: data.category_id || undefined,
            brand_id: data.brand_id || undefined,
            unit_id: data.unit_id || undefined,
            freight_template_id: data.freight_template_id || undefined,
            delivery_modes:
                Array.isArray(data.delivery_modes) && data.delivery_modes.length > 0
                    ? data.delivery_modes
                    : ['express'],
            images: data.images || [],
            status: data.status || 'on_sale',
            sort: data.sort || 0,
            is_recommend: data.is_recommend || 0,
            is_new: data.is_new || 0,
            is_hot: data.is_hot || 0,
            specs: data.specs || [],
            skus: data.skus || [],
            detail: data.detail || ''
        })
        // 根据 specs 判断规格类型
        if (data.specs && data.specs.length > 0) {
            specType.value = 'multi'
        } else {
            specType.value = 'single'
            // 单规格取最新一条 SKU（id 降序）；历史 forceDelete 失效时可能残留多条空规格 SKU
            const skus = [...(data.skus || [])].sort(
                (a, b) => Number(b.id || 0) - Number(a.id || 0)
            )
            const sku = skus[0]
            if (sku) {
                Object.assign(singleSku, {
                    price: sku.price ?? 0,
                    cost_price: sku.cost_price ?? 0,
                    market_price: sku.market_price ?? 0,
                    stock: sku.stock ?? 0,
                    weight: sku.weight ?? 0
                })
            }
        }
        if (data.category_id) {
            await loadAttrsByCategoryId(data.category_id)
        }
        if (data.attrs && data.attrs.length) {
            data.attrs.forEach((a: { attr_id: number; value: any }) => {
                attrValues[a.attr_id] = a.value
            })
        }
    } catch (error) {
        console.error('加载商品详情失败:', error)
        ElMessage.error('加载商品数据失败')
    }
}

const loadAttrsByCategoryId = async (categoryId: number) => {
    try {
        const res = await goodsAttributeGroupApi.getWithAttrs({ category_id: categoryId })
        attrGroups.value = res.data || []
    } catch (error) {
        console.error('加载属性失败:', error)
    }
}

// ==============================
// Type change
// ==============================

const handleTypeChange = () => {
    if (form.type === 'combo') {
        form.specs = []
        form.skus = []
    }
}

// ==============================
// Category change -> load attrs
// ==============================

const handleCategoryChange = (val: number | undefined) => {
    attrGroups.value = []
    if (val) {
        loadAttrsByCategoryId(val)
    }
}

// ==============================
// Spec / SKU operations
// ==============================

const addSpec = () => {
    form.specs.push({ name: '', values: [] })
}

const removeSpec = (idx: number) => {
    form.specs.splice(idx, 1)
    regenerateSkus()
}

const showSpecValueInput = (specIdx: number) => {
    specValueInputVisible.value[specIdx] = true
    specValueInputs.value[specIdx] = ''
    nextTick(() => {
        specValueInputRefs.value[specIdx]?.focus?.()
    })
}

const setSpecValueInputRef = (el: any, specIdx: number) => {
    if (el) {
        specValueInputRefs.value[specIdx] = el
    }
}

const confirmSpecValue = (specIdx: number) => {
    const val = (specValueInputs.value[specIdx] || '').trim()
    if (val && !form.specs[specIdx].values.includes(val)) {
        form.specs[specIdx].values.push(val)
        regenerateSkus()
    }
    specValueInputVisible.value[specIdx] = false
    specValueInputs.value[specIdx] = ''
}

const removeSpecValue = (specIdx: number, valIdx: number) => {
    form.specs[specIdx].values.splice(valIdx, 1)
    regenerateSkus()
}

const cartesianProduct = (arrays: string[][]): string[][] => {
    if (arrays.length === 0) return [[]]
    return arrays.reduce<string[][]>(
        (acc, curr) => acc.flatMap((a) => curr.map((b) => [...a, b])),
        [[]]
    )
}

// 批量赋值
const batchForm = reactive({
    price: undefined as number | undefined,
    cost_price: undefined as number | undefined,
    market_price: undefined as number | undefined,
    stock: undefined as number | undefined,
    weight: undefined as number | undefined
})

const applyBatch = () => {
    if (form.skus.length === 0) return
    const fields: (keyof typeof batchForm)[] = [
        'price',
        'cost_price',
        'market_price',
        'stock',
        'weight'
    ]
    let applied = false
    form.skus.forEach((sku) => {
        fields.forEach((field) => {
            if (batchForm[field] !== undefined && batchForm[field] !== null) {
                sku[field] = batchForm[field]
                applied = true
            }
        })
    })
    if (applied) {
        ElMessage.success('批量赋值成功')
    } else {
        ElMessage.warning('请至少填写一个批量赋值字段')
    }
}

const regenerateSkus = () => {
    const validSpecs = form.specs.filter((s) => s.name && s.values.length > 0)
    if (validSpecs.length === 0) {
        form.skus = []
        return
    }

    const specNames = validSpecs.map((s) => s.name)
    const specValues = validSpecs.map((s) => s.values)
    const combinations = cartesianProduct(specValues)

    const oldSkuMap: Record<string, Record<string, any>> = {}
    form.skus.forEach((sku) => {
        const key = specNames.map((n) => sku.spec_values?.[n] || '').join('|')
        oldSkuMap[key] = sku
    })

    form.skus = combinations.map((combo) => {
        const specValues: Record<string, string> = {}
        specNames.forEach((name, i) => {
            specValues[name] = combo[i]
        })
        const key = combo.join('|')
        const old = oldSkuMap[key]
        return {
            spec_values: specValues,
            image: old?.image ?? '',
            price: old?.price ?? 0,
            cost_price: old?.cost_price ?? 0,
            market_price: old?.market_price ?? 0,
            stock: old?.stock ?? 0,
            weight: old?.weight ?? 0
        }
    })
}

// ==============================
// Save
// ==============================

const buildAttrs = () => {
    const attrs: { attr_id: number; value: any }[] = []
    attrGroups.value.forEach((group) => {
        ;(group.attributes || []).forEach((attr: Record<string, any>) => {
            const val = attrValues[attr.id]
            if (val !== undefined && val !== null && val !== '') {
                attrs.push({
                    attr_id: attr.id,
                    value: Array.isArray(val) ? val.join(',') : String(val)
                })
            }
        })
    })
    return attrs
}

const handleSave = async () => {
    try {
        await basicFormRef.value?.validate()
    } catch {
        activeTab.value = 'basic'
        ElMessage.warning('请完善基本信息')
        return
    }

    submitLoading.value = true
    try {
        const payload = {
            name: form.name,
            subtitle: form.subtitle,
            type: form.type,
            category_id: form.category_id,
            brand_id: form.brand_id,
            unit_id: form.unit_id,
            freight_template_id: form.type === 'physical' ? form.freight_template_id : undefined,
            delivery_modes: form.delivery_modes.length > 0 ? form.delivery_modes : ['express'],
            images: form.images,
            status: form.status,
            sort: form.sort,
            is_recommend: form.is_recommend,
            is_new: form.is_new,
            is_hot: form.is_hot,
            specs: form.type !== 'combo' ? (specType.value === 'single' ? [] : form.specs) : [],
            skus:
                form.type !== 'combo'
                    ? specType.value === 'single'
                        ? [{ spec_values: {}, ...singleSku }]
                        : form.skus
                    : [],
            attrs: buildAttrs(),
            detail: form.detail
        }

        if (isEdit.value) {
            await goodsSpuApi.update(props.spuId, payload)
            ElMessage.success('保存成功')
        } else {
            await goodsSpuApi.create(payload)
            ElMessage.success('创建成功')
        }
        emit('success')
    } catch (error) {
        console.error('保存失败:', error)
    } finally {
        submitLoading.value = false
    }
}

// ==============================
// 规格模板
// ==============================

const loadSpecTemplates = async () => {
    specTemplateLoading.value = true
    try {
        const res = await goodsSpecTemplateApi.getList({ page: 1, limit: 200, status: 1 })
        specTemplateOptions.value = res.data?.list || []
    } catch (e) {
        console.error('加载规格模板失败:', e)
    } finally {
        specTemplateLoading.value = false
    }
}

const handleApplyTemplate = async (id: number | undefined) => {
    if (!id) return
    const tpl = specTemplateOptions.value.find((t) => t.id === id)
    if (!tpl || !tpl.items?.length) {
        selectedTemplateId.value = undefined
        return
    }

    // 已有规格 → 弹窗确认覆盖；为空 → 直接应用
    if (form.specs.length > 0) {
        try {
            await ElMessageBox.confirm(
                '当前已设置的规格和 SKU 数据将被覆盖，确定应用模板吗？',
                '应用模板',
                { confirmButtonText: '确定覆盖', cancelButtonText: '取消', type: 'warning' }
            )
        } catch {
            // 用户取消 → 重置下拉为未选
            selectedTemplateId.value = undefined
            return
        }
    }

    form.specs = tpl.items.map((i) => ({ name: i.name, values: [...(i.values || [])] }))
    // 清掉每行规格值的临时输入态，避免索引错位
    specValueInputVisible.value = {}
    specValueInputs.value = {}
    regenerateSkus()
    // 应用完毕清空选择，方便下次再选同一个模板
    selectedTemplateId.value = undefined
    ElMessage.success(`已应用模板「${tpl.name}」`)
}

// ==============================
// Lifecycle
// ==============================

onMounted(async () => {
    await Promise.all([loadOptions(), loadSpecTemplates()])
    if (isEdit.value) {
        await loadSpuDetail()
    }
})
</script>

<style lang="scss" scoped>
.spu-edit-form {
    display: flex;
    flex-direction: column;
    flex: 1;
    height: 100%;
    min-height: 0;
    overflow: hidden;

    .spu-edit-form__body {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 0 4px;

        &.is-detail-tab {
            overflow: hidden;
            display: flex;
            flex-direction: column;

            .spu-edit-tabs {
                flex: 1;
                min-height: 0;
                display: flex;
                flex-direction: column;
                overflow: hidden;

                :deep(.el-tabs__header) {
                    flex-shrink: 0;
                    margin-bottom: 8px;
                }

                :deep(.el-tabs__content) {
                    flex: 1;
                    min-height: 0;
                    overflow: hidden;
                }

                :deep(.el-tab-pane) {
                    height: 100%;
                }
            }
        }
    }

    .spu-form {
        max-width: 760px;
        padding-top: 16px;

        .form-tip {
            font-size: 12px;
            color: var(--el-text-color-secondary);
        }
    }

    .switch-group {
        display: flex;
        gap: 24px;

        .switch-item {
            display: flex;
            align-items: center;
            gap: 8px;

            .switch-label {
                font-size: 14px;
                color: var(--el-text-color-regular);
            }
        }
    }

    .spec-type-switch {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        padding: 12px 16px;
        background: var(--el-fill-color-extra-light);
        border-radius: 6px;

        .spec-type-label {
            font-size: 14px;
            font-weight: 500;
            color: var(--el-text-color-primary);
        }
    }

    .spec-template-bar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 16px;
        padding: 12px 16px;
        background: var(--brand-50, #f0f4ff);
        border: 1px dashed var(--brand-200, #c7d1ff);
        border-radius: 6px;

        .spec-template-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--ink-700);
        }
        .spec-template-hint {
            font-size: 12px;
            color: var(--ink-500);
        }
        .spec-template-meta {
            float: right;
            color: var(--ink-400);
            font-size: 12px;
            margin-left: 12px;
        }
    }

    .single-sku-section {
        max-width: 500px;
    }

    .specs-section,
    .sku-section {
        margin-bottom: 24px;

        .section-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--el-text-color-primary);
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--el-border-color-lighter);
        }
    }

    .sku-section {
        .batch-assign {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            padding: 10px 12px;
            background: var(--el-fill-color-extra-light);
            border-radius: 6px;

            .batch-label {
                font-size: 13px;
                font-weight: 500;
                color: var(--el-text-color-primary);
                white-space: nowrap;
            }
        }

        // SKU 表格内图片选择缩小为 60x60
        :deep(.image-select-single),
        :deep(.image-select-add) {
            width: 60px;
            height: 60px;

            .add-icon {
                font-size: 20px;
            }
        }

        :deep(.image-select-single .preview-img) {
            width: 60px;
            height: 60px;
        }
    }

    .spec-row {
        margin-bottom: 16px;
        padding: 12px;
        background: var(--el-fill-color-extra-light);
        border-radius: 6px;

        .spec-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .spec-values {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;

            .spec-tag {
                cursor: default;
            }
        }
    }

    .attr-group {
        margin-bottom: 24px;

        .attr-group-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--el-text-color-primary);
            margin-bottom: 12px;
            padding: 6px 12px;
            background: var(--el-fill-color-light);
            border-left: 3px solid var(--el-color-primary);
            border-radius: 0 4px 4px 0;
        }
    }

    .attrs-empty {
        padding: 40px 0;
    }

    .detail-editor {
        width: 100%;
        height: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    .form-footer {
        flex-shrink: 0;
        display: flex;
        justify-content: center;
        gap: 12px;
        padding: 12px 0;
        background: var(--el-bg-color);
        border-top: 1px solid var(--el-border-color-lighter);
    }
}
</style>
