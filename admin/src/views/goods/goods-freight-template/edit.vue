<template>
  <div class="freight-template-edit">
    <!-- Card 1: 基本信息 -->
    <el-card shadow="never">
      <template #header><span class="card-title">基本信息</span></template>
      <el-form ref="basicFormRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="模板名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入模板名称" style="width: 300px" />
        </el-form-item>
        <el-form-item label="计费方式" prop="charge_type">
          <el-radio-group v-model="form.charge_type">
            <el-radio value="piece">按件数</el-radio>
            <el-radio value="weight">按重量</el-radio>
            <el-radio value="volume">按体积</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="是否免运费" prop="is_free">
          <el-radio-group v-model="form.is_free">
            <el-radio :value="1">是</el-radio>
            <el-radio :value="0">否</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- Card 2: 配送区域及运费 -->
    <el-card shadow="never" v-if="!form.is_free">
      <template #header>
        <div class="card-header">
          <span class="card-title">配送区域及运费</span>
          <el-button type="primary" size="small" @click="openRuleDialog(-1)">
            <i class="i-lucide:plus mr-1" /> 添加配送区域
          </el-button>
        </div>
      </template>
      <el-table :data="form.rules" border>
        <el-table-column label="配送区域" min-width="200">
          <template #default="{ row }">
            {{ getRegionNames(row.region_ids) }}
          </template>
        </el-table-column>
        <el-table-column :label="unitLabel('first')" width="120" prop="first_unit" />
        <el-table-column label="运费(元)" width="120" prop="first_price" />
        <el-table-column :label="unitLabel('continue')" width="120" prop="continue_unit" />
        <el-table-column label="续费(元)" width="120" prop="continue_price" />
        <el-table-column label="操作" width="140" fixed="right">
          <template #default="{ $index }">
            <el-button type="primary" text size="small" @click="openRuleDialog($index)">编辑</el-button>
            <el-button type="danger" text size="small" @click="removeRule($index)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <el-empty v-if="form.rules.length === 0" description="请添加配送区域" :image-size="60" />
    </el-card>

    <!-- Card 3: 指定包邮 -->
    <el-card shadow="never" v-if="!form.is_free">
      <template #header>
        <div class="card-header">
          <span class="card-title">指定包邮</span>
          <el-button type="primary" size="small" @click="openFreeRuleDialog(-1)">
            <i class="i-lucide:plus mr-1" /> 添加包邮区域
          </el-button>
        </div>
      </template>
      <el-table :data="form.free_rules" border>
        <el-table-column label="包邮区域" min-width="200">
          <template #default="{ row }">
            {{ getRegionNames(row.region_ids) }}
          </template>
        </el-table-column>
        <el-table-column label="包邮件数" width="120" prop="free_num" />
        <el-table-column label="包邮金额(元)" width="140" prop="free_amount" />
        <el-table-column label="操作" width="140" fixed="right">
          <template #default="{ $index }">
            <el-button type="primary" text size="small" @click="openFreeRuleDialog($index)">编辑</el-button>
            <el-button type="danger" text size="small" @click="removeFreeRule($index)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <el-empty v-if="form.free_rules.length === 0" description="未设置包邮规则" :image-size="60" />
    </el-card>

    <!-- Card 4: 指定不送达 -->
    <el-card shadow="never">
      <template #header><span class="card-title">指定不送达</span></template>
      <div class="province-grid">
        <el-checkbox-group v-model="form.no_delivery_region_ids">
          <el-checkbox
            v-for="p in provinces"
            :key="p.id"
            :value="p.id"
            :label="p.name"
          />
        </el-checkbox-group>
      </div>
    </el-card>

    <!-- Sticky footer -->
    <div class="form-footer">
      <el-button @click="router.back()">取消</el-button>
      <el-button type="primary" :loading="submitLoading" @click="handleSave">保存</el-button>
    </div>

    <!-- Rule Dialog (for delivery rules) -->
    <el-dialog v-model="ruleDialogVisible" title="配送区域设置" width="700px">
      <div class="province-grid">
        <el-checkbox-group v-model="ruleForm.region_ids">
          <el-checkbox
            v-for="p in provinces"
            :key="p.id"
            :value="p.id"
            :label="p.name"
            :disabled="isProvinceUsedInRules(p.id)"
          />
        </el-checkbox-group>
      </div>
      <el-form label-width="120px" style="margin-top: 16px">
        <el-form-item :label="unitLabel('first')">
          <el-input-number v-model="ruleForm.first_unit" :min="0" :precision="2" controls-position="right" style="width: 200px" />
        </el-form-item>
        <el-form-item label="运费(元)">
          <el-input-number v-model="ruleForm.first_price" :min="0" :precision="2" controls-position="right" style="width: 200px" />
        </el-form-item>
        <el-form-item :label="unitLabel('continue')">
          <el-input-number v-model="ruleForm.continue_unit" :min="0" :precision="2" controls-position="right" style="width: 200px" />
        </el-form-item>
        <el-form-item label="续费(元)">
          <el-input-number v-model="ruleForm.continue_price" :min="0" :precision="2" controls-position="right" style="width: 200px" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="ruleDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmRule">确定</el-button>
      </template>
    </el-dialog>

    <!-- Free Rule Dialog (for free shipping rules) -->
    <el-dialog v-model="freeRuleDialogVisible" title="包邮区域设置" width="700px">
      <div class="province-grid">
        <el-checkbox-group v-model="freeRuleForm.region_ids">
          <el-checkbox
            v-for="p in provinces"
            :key="p.id"
            :value="p.id"
            :label="p.name"
            :disabled="isProvinceUsedInFreeRules(p.id)"
          />
        </el-checkbox-group>
      </div>
      <el-form label-width="120px" style="margin-top: 16px">
        <el-form-item label="包邮件数">
          <el-input-number v-model="freeRuleForm.free_num" :min="0" controls-position="right" style="width: 200px" />
          <span style="margin-left: 8px; color: var(--el-text-color-secondary); font-size: 12px">0表示不限</span>
        </el-form-item>
        <el-form-item label="包邮金额(元)">
          <el-input-number v-model="freeRuleForm.free_amount" :min="0" :precision="2" controls-position="right" style="width: 200px" />
          <span style="margin-left: 8px; color: var(--el-text-color-secondary); font-size: 12px">0表示不限</span>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="freeRuleDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmFreeRule">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="FreightTemplateEdit">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { goodsFreightTemplateApi } from '@/api/goods-freight-template'
import { regionApi } from '@/api/region'

const router = useRouter()
const route = useRoute()
const isEdit = computed(() => !!route.query.id)
const templateId = computed(() => Number(route.query.id) || 0)

const provinces = ref<{ id: number; name: string }[]>([])
const submitLoading = ref(false)
const basicFormRef = ref()

const form = reactive({
  name: '',
  charge_type: 'piece' as 'piece' | 'weight' | 'volume',
  is_free: 0,
  sort: 0,
  rules: [] as any[],
  free_rules: [] as any[],
  no_delivery_region_ids: [] as number[],
})

const rules = {
  name: [{ required: true, message: '请输入模板名称', trigger: 'blur' }],
}

// Unit label helper
const unitLabel = (type: 'first' | 'continue') => {
  const map = {
    piece: { first: '首件', continue: '续件' },
    weight: { first: '首重(kg)', continue: '续重(kg)' },
    volume: { first: '首体积(m³)', continue: '续体积(m³)' },
  }
  return map[form.charge_type][type]
}

// Province name helper
const provinceMap = computed(() => {
  const map: Record<number, string> = {}
  provinces.value.forEach((p) => {
    map[p.id] = p.name
  })
  return map
})

const getRegionNames = (ids: number[]) => {
  if (!ids?.length) return '-'
  return ids.map((id) => provinceMap.value[id] || id).join('、')
}

// Load provinces
const loadProvinces = async () => {
  const res = await regionApi.getTree()
  // getTree 返回 { value, label, children } 结构，只取第一层省级节点
  provinces.value = (res.data || []).map((r: any) => ({ id: r.value, name: r.label }))
}

// Load template detail for edit mode
const loadDetail = async () => {
  if (!isEdit.value) return
  const res = await goodsFreightTemplateApi.getDetail(templateId.value)
  const data = res.data
  Object.assign(form, {
    name: data.name || '',
    charge_type: data.charge_type || 'piece',
    is_free: data.is_free ?? 0,
    sort: data.sort ?? 0,
    rules: data.rules || [],
    free_rules: data.free_rules || [],
    no_delivery_region_ids: data.no_delivery_region_ids || [],
  })
}

// ===== Rule Dialog =====
const ruleDialogVisible = ref(false)
const editingRuleIndex = ref(-1)
const ruleForm = reactive({
  region_ids: [] as number[],
  first_unit: 1,
  first_price: 0,
  continue_unit: 1,
  continue_price: 0,
})

const isProvinceUsedInRules = (provinceId: number) => {
  return form.rules.some(
    (r, idx) => idx !== editingRuleIndex.value && r.region_ids?.includes(provinceId)
  )
}

const openRuleDialog = (index: number) => {
  editingRuleIndex.value = index
  if (index >= 0) {
    const rule = form.rules[index]
    Object.assign(ruleForm, {
      region_ids: [...(rule.region_ids || [])],
      first_unit: rule.first_unit ?? 1,
      first_price: rule.first_price ?? 0,
      continue_unit: rule.continue_unit ?? 1,
      continue_price: rule.continue_price ?? 0,
    })
  } else {
    Object.assign(ruleForm, {
      region_ids: [],
      first_unit: 1,
      first_price: 0,
      continue_unit: 1,
      continue_price: 0,
    })
  }
  ruleDialogVisible.value = true
}

const confirmRule = () => {
  if (ruleForm.region_ids.length === 0) {
    ElMessage.warning('请选择配送区域')
    return
  }
  const ruleData = { ...ruleForm, region_ids: [...ruleForm.region_ids] }
  if (editingRuleIndex.value >= 0) {
    form.rules[editingRuleIndex.value] = ruleData
  } else {
    form.rules.push(ruleData)
  }
  ruleDialogVisible.value = false
}

const removeRule = (index: number) => {
  form.rules.splice(index, 1)
}

// ===== Free Rule Dialog =====
const freeRuleDialogVisible = ref(false)
const editingFreeRuleIndex = ref(-1)
const freeRuleForm = reactive({
  region_ids: [] as number[],
  free_num: 0,
  free_amount: 0,
})

const isProvinceUsedInFreeRules = (provinceId: number) => {
  return form.free_rules.some(
    (r, idx) => idx !== editingFreeRuleIndex.value && r.region_ids?.includes(provinceId)
  )
}

const openFreeRuleDialog = (index: number) => {
  editingFreeRuleIndex.value = index
  if (index >= 0) {
    const rule = form.free_rules[index]
    Object.assign(freeRuleForm, {
      region_ids: [...(rule.region_ids || [])],
      free_num: rule.free_num ?? 0,
      free_amount: rule.free_amount ?? 0,
    })
  } else {
    Object.assign(freeRuleForm, { region_ids: [], free_num: 0, free_amount: 0 })
  }
  freeRuleDialogVisible.value = true
}

const confirmFreeRule = () => {
  if (freeRuleForm.region_ids.length === 0) {
    ElMessage.warning('请选择包邮区域')
    return
  }
  const ruleData = { ...freeRuleForm, region_ids: [...freeRuleForm.region_ids] }
  if (editingFreeRuleIndex.value >= 0) {
    form.free_rules[editingFreeRuleIndex.value] = ruleData
  } else {
    form.free_rules.push(ruleData)
  }
  freeRuleDialogVisible.value = false
}

const removeFreeRule = (index: number) => {
  form.free_rules.splice(index, 1)
}

// ===== Save =====
const handleSave = async () => {
  try {
    await basicFormRef.value?.validate()
  } catch {
    ElMessage.warning('请完善基本信息')
    return
  }

  submitLoading.value = true
  try {
    const payload = {
      name: form.name,
      charge_type: form.charge_type,
      is_free: form.is_free,
      sort: form.sort,
      rules: form.is_free ? [] : form.rules,
      free_rules: form.is_free ? [] : form.free_rules,
      no_delivery_region_ids: form.no_delivery_region_ids,
    }
    if (isEdit.value) {
      await goodsFreightTemplateApi.update(templateId.value, payload)
      ElMessage.success('保存成功')
    } else {
      await goodsFreightTemplateApi.create(payload)
      ElMessage.success('创建成功')
    }
    router.back()
  } catch (e) {
    console.error('保存失败:', e)
  } finally {
    submitLoading.value = false
  }
}

onMounted(async () => {
  await loadProvinces()
  if (isEdit.value) {
    await loadDetail()
  }
})
</script>

<style lang="scss" scoped>
.freight-template-edit {
  padding: 16px;

  .el-card {
    margin-bottom: 16px;
  }

  .card-title {
    font-size: 16px;
    font-weight: 600;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .province-grid {
    .el-checkbox-group {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px 0;
    }
  }

  .form-footer {
    position: sticky;
    bottom: 0;
    z-index: 10;
    display: flex;
    justify-content: center;
    gap: 12px;
    padding: 12px 0;
    background: var(--el-bg-color);
    border-top: 1px solid var(--el-border-color-lighter);
  }
}
</style>
