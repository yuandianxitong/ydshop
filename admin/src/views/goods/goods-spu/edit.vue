<template>
  <div class="goods-spu-edit-container">
    <!-- 页面头部 -->
    <div class="page-header">
      <div class="page-title">{{ isEdit ? '编辑商品' : '新增商品' }}</div>
    </div>

    <!-- 标签页 -->
    <el-card shadow="never" class="content-card">
      <el-tabs v-model="activeTab">
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
              <el-input v-model="form.name" placeholder="请输入商品名称" style="width: 400px" />
            </el-form-item>

            <el-form-item label="副标题" prop="subtitle">
              <el-input v-model="form.subtitle" placeholder="请输入副标题" style="width: 400px" />
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
                :props="({ label: 'name', value: 'id', children: 'children' } as any)"
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

            <el-form-item v-if="form.type === 'physical'" label="运费模板" prop="freight_template_id">
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

            <el-form-item label="商品图片" prop="images">
              <div class="images-upload">
                <div
                  v-for="(img, idx) in form.images"
                  :key="idx"
                  class="image-item"
                >
                  <el-image :src="img" fit="cover" class="image-preview" />
                  <div class="image-remove" @click="removeImage(idx)">
                    <i class="i-svg:x" />
                  </div>
                </div>
                <div class="image-add" @click="handleAddImage">
                  <i class="i-lucide:plus" />
                  <span>添加图片</span>
                </div>
              </div>
              <div class="form-tip">支持拖拽排序，第一张为主图（当前为简化版，输入URL添加）</div>
              <!-- 简易图片URL输入 -->
              <el-input
                v-model="tempImageUrl"
                placeholder="输入图片URL后回车添加"
                style="width: 400px; margin-top: 8px"
                @keyup.enter="confirmAddImage"
              >
                <template #append>
                  <el-button @click="confirmAddImage">添加</el-button>
                </template>
              </el-input>
            </el-form-item>

            <el-form-item label="排序" prop="sort">
              <el-input-number v-model="form.sort" :min="0" controls-position="right" />
              <span class="form-tip" style="margin-left: 8px">数值越大越靠前</span>
            </el-form-item>

            <el-form-item label="商品标签">
              <div class="switch-group">
                <div class="switch-item">
                  <span class="switch-label">推荐</span>
                  <el-switch v-model="form.is_recommend" :active-value="1" :inactive-value="0" />
                </div>
                <div class="switch-item">
                  <span class="switch-label">新品</span>
                  <el-switch v-model="form.is_new" :active-value="1" :inactive-value="0" />
                </div>
                <div class="switch-item">
                  <span class="switch-label">热卖</span>
                  <el-switch v-model="form.is_hot" :active-value="1" :inactive-value="0" />
                </div>
              </div>
            </el-form-item>
          </el-form>
        </el-tab-pane>

        <!-- Tab 2: 规格/SKU -->
        <el-tab-pane
          v-if="form.type !== 'combo'"
          label="规格/SKU"
          name="specs"
        >
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
                  @close="removeSpecValue(specIdx, valIdx)"
                  class="spec-tag"
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

            <el-button plain @click="addSpec" style="margin-top: 8px">
              <i class="i-lucide:plus mr-1" />
              添加规格
            </el-button>
          </div>

          <!-- SKU 表格 -->
          <div class="sku-section" v-if="form.skus.length > 0">
            <div class="section-title">SKU 列表</div>
            <el-table :data="form.skus" border>
              <el-table-column
                v-for="spec in form.specs"
                :key="spec.name"
                :label="spec.name"
                :prop="`spec_values.${spec.name}`"
                width="120"
              >
                <template #default="{ row }">
                  {{ row.spec_values?.[spec.name] || '-' }}
                </template>
              </el-table-column>

              <el-table-column label="售价(分)" width="130">
                <template #default="{ row }">
                  <el-input-number
                    v-model="row.price"
                    :min="0"
                    controls-position="right"
                    size="small"
                    style="width: 110px"
                  />
                </template>
              </el-table-column>

              <el-table-column label="成本价(分)" width="130">
                <template #default="{ row }">
                  <el-input-number
                    v-model="row.cost_price"
                    :min="0"
                    controls-position="right"
                    size="small"
                    style="width: 110px"
                  />
                </template>
              </el-table-column>

              <el-table-column label="市场价(分)" width="130">
                <template #default="{ row }">
                  <el-input-number
                    v-model="row.market_price"
                    :min="0"
                    controls-position="right"
                    size="small"
                    style="width: 110px"
                  />
                </template>
              </el-table-column>

              <el-table-column label="库存" width="110">
                <template #default="{ row }">
                  <el-input-number
                    v-model="row.stock"
                    :min="0"
                    controls-position="right"
                    size="small"
                    style="width: 90px"
                  />
                </template>
              </el-table-column>

              <el-table-column label="重量(g)" width="110">
                <template #default="{ row }">
                  <el-input-number
                    v-model="row.weight"
                    :min="0"
                    controls-position="right"
                    size="small"
                    style="width: 90px"
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
            <div
              v-for="group in attrGroups"
              :key="group.id"
              class="attr-group"
            >
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
                      v-for="opt in (attr.options || [])"
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
                      v-for="opt in (attr.options || [])"
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
        <el-tab-pane label="商品详情" name="detail">
          <div class="detail-editor">
            <Editor v-model="form.detail" height="500px" mode="default" />
          </div>
        </el-tab-pane>
      </el-tabs>

      <!-- 底部按钮 -->
      <div class="form-footer">
        <el-button @click="handleCancel">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSave">
          保存
        </el-button>
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts" name="GoodsSpuEdit">
import { ref, reactive, computed, onMounted, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import Editor from '@/components/Editor/index.vue'
import { goodsSpuApi } from '@/api/goods-spu'
import { goodsCategoryApi } from '@/api/goods-category'
import { goodsBrandApi } from '@/api/goods-brand'
import { goodsUnitApi } from '@/api/goods-unit'
import { goodsFreightTemplateApi } from '@/api/goods-freight-template'
import { goodsAttributeGroupApi } from '@/api/goods-attribute-group'
import { goodsAttributeApi } from '@/api/goods-attribute'

const route = useRoute()
const router = useRouter()

// 当前编辑的 SPU ID
const spuId = computed(() => route.query.id ? Number(route.query.id) : null)
const isEdit = computed(() => !!spuId.value)

// 当前激活的 Tab
const activeTab = ref('basic')

// 表单 Ref
const basicFormRef = ref()

// 表单数据
const form = reactive({
  name: '',
  subtitle: '',
  type: 'physical' as 'physical' | 'virtual' | 'combo',
  category_id: undefined as number | undefined,
  brand_id: undefined as number | undefined,
  unit_id: undefined as number | undefined,
  freight_template_id: undefined as number | undefined,
  images: [] as string[],
  sort: 0,
  is_recommend: 0,
  is_new: 0,
  is_hot: 0,
  specs: [] as { name: string; values: string[] }[],
  skus: [] as Record<string, any>[],
  detail: '',
})

// 基本信息验证规则
const basicRules = {
  name: [{ required: true, message: '请输入商品名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择商品类型', trigger: 'change' }],
  category_id: [{ required: true, message: '请选择商品分类', trigger: 'change' }],
}

// 图片临时输入
const tempImageUrl = ref('')

// 选项数据
const categoryTree = ref<Record<string, any>[]>([])
const brandList = ref<Record<string, any>[]>([])
const unitList = ref<Record<string, any>[]>([])
const freightTemplateList = ref<Record<string, any>[]>([])

// 属性组和属性
const attrGroups = ref<Record<string, any>[]>([])
const attrValues = reactive<Record<number, any>>({})

// 规格值输入控制
const specValueInputVisible = ref<Record<number, boolean>>({})
const specValueInputs = ref<Record<number, string>>({})
const specValueInputRefs = ref<Record<number, any>>({})

// 提交状态
const submitLoading = ref(false)

// ==============================
// 初始化数据加载
// ==============================

const loadOptions = async () => {
  try {
    const [catRes, brandRes, unitRes, freightRes] = await Promise.all([
      goodsCategoryApi.getTree(),
      goodsBrandApi.getList({ limit: 200, status: 1 }),
      goodsUnitApi.getList({ limit: 200, status: 1 }),
      goodsFreightTemplateApi.getList({ limit: 200 }),
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
  if (!spuId.value) return
  try {
    const res = await goodsSpuApi.getDetail(spuId.value)
    const data = res.data
    Object.assign(form, {
      name: data.name || '',
      subtitle: data.subtitle || '',
      type: data.type || 'physical',
      category_id: data.category_id || undefined,
      brand_id: data.brand_id || undefined,
      unit_id: data.unit_id || undefined,
      freight_template_id: data.freight_template_id || undefined,
      images: data.images || [],
      sort: data.sort || 0,
      is_recommend: data.is_recommend || 0,
      is_new: data.is_new || 0,
      is_hot: data.is_hot || 0,
      specs: data.specs || [],
      skus: data.skus || [],
      detail: data.detail || '',
    })
    // 加载分类属性
    if (data.category_id) {
      await loadAttrsByCategoryId(data.category_id)
    }
    // 回填属性值
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
    const groupRes = await goodsAttributeGroupApi.getList({ category_id: categoryId, limit: 200 })
    const groups = groupRes.data?.list || []
    // 为每个分组加载属性
    for (const group of groups) {
      const attrRes = await goodsAttributeApi.getList({ group_id: group.id, limit: 200 })
      group.attributes = attrRes.data?.list || []
    }
    attrGroups.value = groups
  } catch (error) {
    console.error('加载属性失败:', error)
  }
}

// ==============================
// 图片操作
// ==============================

const handleAddImage = () => {
  // 聚焦到 URL 输入框
}

const confirmAddImage = () => {
  const url = tempImageUrl.value.trim()
  if (url) {
    form.images.push(url)
    tempImageUrl.value = ''
  }
}

const removeImage = (idx: number) => {
  form.images.splice(idx, 1)
}

// ==============================
// 商品类型变化
// ==============================

const handleTypeChange = () => {
  // combo 类型不支持规格
  if (form.type === 'combo') {
    form.specs = []
    form.skus = []
  }
}

// ==============================
// 分类变化 -> 加载属性
// ==============================

const handleCategoryChange = (val: number | undefined) => {
  attrGroups.value = []
  if (val) {
    loadAttrsByCategoryId(val)
  }
}

// ==============================
// 规格/SKU 操作
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

// 笛卡尔积生成 SKU 组合
const cartesianProduct = (arrays: string[][]): string[][] => {
  if (arrays.length === 0) return [[]]
  return arrays.reduce<string[][]>(
    (acc, curr) => acc.flatMap((a) => curr.map((b) => [...a, b])),
    [[]]
  )
}

const regenerateSkus = () => {
  const validSpecs = form.specs.filter(s => s.name && s.values.length > 0)
  if (validSpecs.length === 0) {
    form.skus = []
    return
  }

  const specNames = validSpecs.map(s => s.name)
  const specValues = validSpecs.map(s => s.values)
  const combinations = cartesianProduct(specValues)

  // 旧 SKU 数据索引（用规格值组合作为 key）
  const oldSkuMap: Record<string, Record<string, any>> = {}
  form.skus.forEach(sku => {
    const key = specNames.map(n => sku.spec_values?.[n] || '').join('|')
    oldSkuMap[key] = sku
  })

  form.skus = combinations.map(combo => {
    const specValues: Record<string, string> = {}
    specNames.forEach((name, i) => {
      specValues[name] = combo[i]
    })
    const key = combo.join('|')
    const old = oldSkuMap[key]
    return {
      spec_values: specValues,
      price: old?.price ?? 0,
      cost_price: old?.cost_price ?? 0,
      market_price: old?.market_price ?? 0,
      stock: old?.stock ?? 0,
      weight: old?.weight ?? 0,
    }
  })
}

// ==============================
// 提交表单
// ==============================

const buildAttrs = () => {
  const attrs: { attr_id: number; value: any }[] = []
  attrGroups.value.forEach(group => {
    ;(group.attributes || []).forEach((attr: Record<string, any>) => {
      const val = attrValues[attr.id]
      if (val !== undefined && val !== null && val !== '') {
        attrs.push({ attr_id: attr.id, value: Array.isArray(val) ? val.join(',') : String(val) })
      }
    })
  })
  return attrs
}

const handleSave = async () => {
  // 验证基本信息
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
      images: form.images,
      sort: form.sort,
      is_recommend: form.is_recommend,
      is_new: form.is_new,
      is_hot: form.is_hot,
      specs: form.type !== 'combo' ? form.specs : [],
      skus: form.type !== 'combo' ? form.skus : [],
      attrs: buildAttrs(),
      detail: form.detail,
    }

    if (isEdit.value && spuId.value) {
      await goodsSpuApi.update(spuId.value, payload)
      ElMessage.success('保存成功')
    } else {
      await goodsSpuApi.create(payload)
      ElMessage.success('创建成功')
      router.push('/goods/goods-spu')
    }
  } catch (error) {
    console.error('保存失败:', error)
  } finally {
    submitLoading.value = false
  }
}

const handleCancel = () => {
  router.push('/goods/goods-spu')
}

// ==============================
// 生命周期
// ==============================

onMounted(async () => {
  await loadOptions()
  if (isEdit.value) {
    await loadSpuDetail()
  }
})
</script>

<style lang="scss" scoped>
.goods-spu-edit-container {
  .page-header {
    margin-bottom: 16px;

    .page-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--el-text-color-primary);
    }
  }

  .content-card {
    min-height: calc(100vh - 180px);
  }

  .spu-form {
    max-width: 760px;
    padding-top: 16px;

    .form-tip {
      font-size: 12px;
      color: var(--el-text-color-secondary);
    }
  }

  .images-upload {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;

    .image-item {
      position: relative;
      width: 80px;
      height: 80px;
      border-radius: 4px;
      overflow: hidden;
      border: 1px solid var(--el-border-color);

      .image-preview {
        width: 100%;
        height: 100%;
      }

      .image-remove {
        position: absolute;
        top: 0;
        right: 0;
        width: 20px;
        height: 20px;
        background: rgba(0, 0, 0, 0.5);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border-bottom-left-radius: 4px;
        font-size: 12px;
      }
    }

    .image-add {
      width: 80px;
      height: 80px;
      border: 1px dashed var(--el-border-color);
      border-radius: 4px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: var(--el-text-color-secondary);
      font-size: 12px;
      gap: 4px;
      transition: border-color 0.2s;

      &:hover {
        border-color: var(--el-color-primary);
        color: var(--el-color-primary);
      }
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
    padding-top: 8px;
  }

  .form-footer {
    position: sticky;
    bottom: 0;
    z-index: 10;
    display: flex;
    justify-content: center;
    gap: 12px;
    padding: 12px 0;
    border-top: 1px solid var(--el-border-color-lighter);
    margin: 16px -20px 0;
    padding-left: 20px;
    padding-right: 20px;
    background: #fff;
  }
}
</style>
