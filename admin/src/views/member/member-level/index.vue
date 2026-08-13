<template>
  <div class="member-level-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">会员体系</h2>
        <p class="page-desc">等级、积分、成长值与会员权益</p>
      </div>
      <div class="page-actions">
        <el-button type="primary" @click="handleAdd">
          <i class="i-lucide:plus mr-1" /> 新增等级
        </el-button>
      </div>
    </div>

    <!-- 等级分布 -->
    <div class="card dist-card" v-if="tableData.length">
      <div class="card-head">
        <div class="card-title">会员等级分布</div>
        <div class="card-meta">共 {{ formatCount(memberTotal) }} 人</div>
      </div>
      <div class="card-body">
        <div class="dist-bar">
          <div
            v-for="(lv, i) in tableData"
            :key="lv.id"
            class="dist-seg"
            :style="{ width: distPct(lv) + '%', background: levelColor(i) }"
          >
            <span v-if="distPct(lv) > 6">{{ lv.name }} {{ distPct(lv) }}%</span>
          </div>
          <div v-if="!memberTotal" class="dist-empty">暂无会员分布数据</div>
        </div>
        <div class="flex flex-wrap gap-3.5">
          <span v-for="(lv, i) in tableData" :key="lv.id" class="inline-flex items-center gap-1.5 text-[12.5px]">
            <span class="dist-dot" :style="{ background: levelColor(i) }" />
            <span class="dist-name">{{ lv.name }}</span>
            <span class="dist-cnt num">· {{ formatCount(lv.member_count || 0) }} 人</span>
          </span>
        </div>
      </div>
    </div>

    <!-- 等级卡片网格 -->
    <div v-loading="loading" class="lvl-grid">
      <div
        v-for="(lv, i) in tableData"
        :key="lv.id"
        class="card lvl-card"
      >
        <div class="lvl-bar" :style="{ background: levelColor(i) }" />
        <div class="card-body lvl-body">
          <div class="flex justify-between items-start gap-2.5">
            <div>
              <div class="lvl-tag num">V{{ i + 1 }}</div>
              <div class="lvl-name">{{ lv.name }}</div>
            </div>
            <div class="lvl-cnt num" :style="{ color: levelColor(i) }">
              {{ formatCount(lv.member_count || 0) }} 人
            </div>
          </div>
          <div class="lvl-section">
            <div class="lvl-section-label">成长值门槛</div>
            <div class="lvl-section-value num">{{ formatCount(lv.growth_min || 0) }}</div>
          </div>
          <div class="lvl-rights">
            <div class="lvl-section-label">会员权益</div>
            <div v-if="!buildRights(lv).length" class="lvl-empty">未设置</div>
            <div
              v-for="(r, j) in buildRights(lv)"
              :key="j"
              class="lvl-right-item flex items-center gap-1.5"
            >
              <span class="lvl-right-dot" :style="{ background: levelColor(i) }" />
              {{ r }}
            </div>
          </div>
          <div class="lvl-foot flex items-center justify-between mt-3.5 pt-3">
            <el-tag :type="lv.status ? 'success' : 'info'" size="small">
              {{ lv.status ? '启用' : '禁用' }}
            </el-tag>
            <div class="tbl-acts">
              <el-button type="primary" size="small" text @click="handleEdit(lv)">编辑</el-button>
              <el-button type="danger"  size="small" text @click="handleDelete(lv)">删除</el-button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="!loading && !tableData.length" class="lvl-empty-all">
        <el-empty description="暂无会员等级，点击右上角新增" />
      </div>
    </div>

    <!-- 新增/编辑 对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑等级' : '新增等级'"
      width="520px"
      @close="handleDialogClose"
    >
      <el-form
        ref="formRef"
        :model="formData"
        :rules="formRules"
        label-width="100px"
      >
        <el-form-item label="等级名称" prop="name">
          <el-input v-model="formData.name" placeholder="请输入等级名称" />
        </el-form-item>

        <el-form-item label="等级图标" prop="icon">
          <div class="image-uploader-wrapper">
            <ImageSelect
              class="lvl-icon-uploader"
              :model-value="formData.icon || ''"
              @update:model-value="(v) => formData.icon = v as string"
            />
            <div class="upload-tip">建议尺寸 128×128，PNG 透明背景</div>
          </div>
        </el-form-item>

        <el-form-item label="成长值下限" prop="growth_min">
          <el-input-number
            v-model="formData.growth_min"
            :min="0"
            style="width: 100%"
          />
        </el-form-item>

        <el-form-item label="折扣率(%)" prop="discount">
          <el-input-number
            v-model="formData.discount"
            :min="0"
            :max="100"
            :precision="2"
            style="width: 100%"
          />
        </el-form-item>

        <el-form-item label="积分倍率" prop="points_rate">
          <el-input-number
            v-model="formData.points_rate"
            :min="0"
            :precision="2"
            style="width: 100%"
          />
        </el-form-item>

        <el-form-item label="免运费" prop="free_freight">
          <el-switch v-model="formData.free_freight" />
        </el-form-item>

        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="formData.sort" :min="0" style="width: 100%" />
        </el-form-item>

        <el-form-item label="状态" prop="status">
          <el-switch v-model="formData.status" />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="MemberLevelList">
import type { FormInstance } from 'element-plus'
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted, reactive, ref } from 'vue'
import { memberLevelApi } from '@/api/member-level'
import ImageSelect from '@/components/ImageSelect/index.vue'
import type { MemberLevelInfo, MemberLevelReq } from '@/types/api'

// 列表数据（卡片视图，无后端分页）
const tableData = ref<MemberLevelInfo[]>([])
const loading = ref(false)

// 等级颜色调色（按索引取）
const PALETTE = ['#94a3b8', '#10b981', '#f59e0b', '#8b5cf6', '#f43f5e', '#0ea5e9', '#4f6bff']
const levelColor = (i: number) => PALETTE[i % PALETTE.length]

// 会员总数（基于各等级 member_count 累加）
const memberTotal = computed(() =>
  tableData.value.reduce((sum, lv: any) => sum + (Number(lv.member_count) || 0), 0)
)

const distPct = (lv: MemberLevelInfo & { member_count?: number }) => {
  if (!memberTotal.value) return 100 / Math.max(tableData.value.length, 1)
  return Math.round(((Number(lv.member_count) || 0) / memberTotal.value) * 100)
}

const buildRights = (lv: MemberLevelInfo & { points_rate?: number; free_freight?: boolean }): string[] => {
  const rights: string[] = []
  if (lv.discount != null && Number(lv.discount) > 0 && Number(lv.discount) < 100) {
    rights.push(`${lv.discount}% 折扣`)
  }
  if (lv.points_rate != null && Number(lv.points_rate) > 1) {
    rights.push(`积分 ${lv.points_rate}x 加速`)
  }
  if (lv.free_freight) {
    rights.push('全场包邮')
  }
  return rights
}

const formatCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')

// 对话框
const dialogVisible = ref(false)
const isEdit = ref(false)
const submitLoading = ref(false)
const formRef = ref<FormInstance>()

const defaultForm = (): MemberLevelReq & { points_rate?: number; free_freight?: boolean; growth_min?: number } => ({
  id: undefined,
  name: '',
  icon: '',
  growth_min: 0,
  discount: undefined,
  points_rate: 1,
  free_freight: false,
  sort: 0,
  status: 1,
})

const formData = reactive<MemberLevelReq & { points_rate?: number; free_freight?: boolean; growth_min?: number }>(defaultForm())
let editId = 0

const formRules = {
  name: [{ required: true, message: '请输入等级名称', trigger: 'blur' }],
  growth_min: [{ required: true, message: '请输入成长值下限', trigger: 'blur' }],
}

// 获取列表（拉全部，用于卡片网格展示）
const fetchList = async () => {
  try {
    loading.value = true
    const response = await memberLevelApi.getList({ page: 1, limit: 100 })
    tableData.value = response.data.list || []
  } catch (error) {
    console.error('获取列表失败:', error)
  } finally {
    loading.value = false
  }
}

const handleAdd = () => {
  isEdit.value = false
  editId = 0
  Object.assign(formData, defaultForm())
  dialogVisible.value = true
}

const handleEdit = (row: MemberLevelInfo & { points_rate?: number; free_freight?: boolean; growth_min?: number }) => {
  isEdit.value = true
  editId = row.id
  Object.assign(formData, {
    name: row.name,
    icon: row.icon || '',
    growth_min: row.growth_min,
    discount: row.discount,
    points_rate: row.points_rate,
    free_freight: !!row.free_freight,
    sort: row.sort,
    status: !!row.status,
  })
  dialogVisible.value = true
}

const handleSubmit = async () => {
  if (!formRef.value) return
  await formRef.value.validate()
  try {
    submitLoading.value = true
    const payload = {
      ...formData,
      free_freight: formData.free_freight ? 1 : 0,
      status: formData.status ? 1 : 0,
    }
    if (isEdit.value) {
      await memberLevelApi.update(editId, payload)
      ElMessage.success('更新成功')
    } else {
      await memberLevelApi.create(payload)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    fetchList()
  } catch (error) {
    console.error('提交失败:', error)
  } finally {
    submitLoading.value = false
  }
}

const handleDelete = async (row: Record<string, any>) => {
  try {
    await ElMessageBox.confirm(
      '确定要删除该会员等级吗？删除后不可恢复！',
      '删除确认',
      { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' }
    )
    await memberLevelApi.delete(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
    }
  }
}

const handleDialogClose = () => {
  formRef.value?.resetFields()
}

onMounted(() => {
  fetchList()
})
</script>

<style lang="scss" scoped>
.form-tip {
  margin-top: 4px;
  font-size: 12px;
  color: var(--ink-500);
}

/* 与 /admin/marketing/points-product 商品图片上传风格一致 */
.image-uploader-wrapper {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.lvl-icon-uploader :deep(.image-select-single),
.lvl-icon-uploader :deep(.image-select-add) {
  width: 100px;
  height: 100px;
}

.upload-tip {
  font-size: 11.5px;
  color: var(--el-text-color-secondary);
  line-height: 1.4;
}

.dist-card {
  margin-bottom: 14px;
  border-radius: 6px;
}

.dist-bar {
  display: flex;
  height: 30px;
  border-radius: 6px;
  overflow: hidden;
  margin-bottom: 12px;
  position: relative;
}

.dist-seg {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 12px;
  font-weight: 500;
  white-space: nowrap;
  transition: width 0.3s;
}

.dist-empty {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ink-50);
  color: var(--ink-400);
  font-size: 12px;
}

.dist-dot {
  width: 10px;
  height: 10px;
  border-radius: 2px;
}

.dist-name {
  color: var(--ink-700);
}

.dist-cnt {
  color: var(--ink-500);
}

.lvl-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 14px;

  @media (max-width: 1280px) {
    grid-template-columns: repeat(3, 1fr);
  }
  @media (max-width: 768px) {
    grid-template-columns: repeat(2, 1fr);
  }
}

.lvl-card {
  padding: 0;
  position: relative;
  overflow: hidden;
  border-radius: 6px;
}

.lvl-bar {
  height: 6px;
}

.lvl-body {
  padding: 14px 16px 12px;
}

.lvl-tag {
  font-size: 11.5px;
  color: var(--ink-400);
}

.lvl-name {
  font-size: 16px;
  font-weight: 700;
  color: var(--ink-900);
  margin-top: 2px;
}

.lvl-cnt {
  font-size: 11.5px;
  font-weight: 600;
  white-space: nowrap;
}

.lvl-section {
  margin-top: 12px;
}

.lvl-section-label {
  font-size: 11px;
  color: var(--ink-400);
}

.lvl-section-value {
  font-size: 18px;
  font-weight: 700;
  color: var(--ink-900);
}

.lvl-rights {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid var(--ink-100);

  .lvl-section-label {
    margin-bottom: 6px;
  }
}

.lvl-empty {
  font-size: 12px;
  color: var(--ink-400);
  padding: 4px 0;
}

.lvl-right-item {
  font-size: 12px;
  color: var(--ink-700);
  padding: 4px 0;
}

.lvl-right-dot {
  width: 4px;
  height: 4px;
  border-radius: 2px;
  flex-shrink: 0;
}

.lvl-foot {
  border-top: 1px solid var(--ink-100);
}

.lvl-empty-all {
  grid-column: 1 / -1;
  padding: 40px 0;
}
</style>
