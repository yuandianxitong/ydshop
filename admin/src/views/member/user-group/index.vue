<template>
  <div class="user-group-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">用户分组</h2>
        <p class="page-desc">多条件圈人与可复用的运营人群包</p>
      </div>
      <div class="page-actions">
        <el-button @click="handleOpenTemplates">
          <i class="i-svg:layers" /> 分组模板
        </el-button>
        <el-button type="primary" @click="handleNew">
          <i class="i-svg:plus" /> 新建分组
        </el-button>
      </div>
    </div>

    <!-- 分组卡片网格（全部分组渐变卡） -->
    <div v-if="tableData.length" v-loading="loading" class="seg-grid row-12">
      <div
        v-for="(g, i) in tableData"
        :key="g.id"
        class="card seg-card"
      >
        <div class="seg-head" :style="{ background: SEG_GRADIENTS[i % SEG_GRADIENTS.length] }">
          <div class="flex justify-between items-start">
            <div class="seg-meta">
              <div class="seg-name">{{ g.name }}</div>
              <div class="seg-desc">{{ g.description || '—' }}</div>
            </div>
            <i class="i-svg:layers seg-icon" />
          </div>
          <div class="flex gap-4.5 items-baseline mt-3.5">
            <div>
              <div class="seg-stat-l">覆盖人群</div>
              <div class="seg-stat-v num">{{ formatCount(g.user_count) }}</div>
            </div>
            <div v-if="memberTotal">
              <div class="seg-stat-l">占总会员</div>
              <div class="seg-stat-v num">{{ pct(g.user_count) }}%</div>
            </div>
            <div>
              <div class="seg-stat-l">自动刷新</div>
              <div class="seg-stat-v">{{ g.auto_update ? '是' : '否' }}</div>
            </div>
          </div>
        </div>
        <div class="card-body seg-body">
          <div class="flex flex-wrap gap-1.5 mb-2.5">
            <span v-for="(c, j) in conditionChips(g)" :key="j" class="chip">{{ c }}</span>
            <span v-if="!conditionChips(g).length" class="chip seg-chip-empty">无规则</span>
          </div>
          <div class="tbl-acts seg-acts">
            <el-button link type="primary" size="small" @click="handleShowMembers(g)">查看名单</el-button>
            <el-button link type="primary" size="small" @click="handleRefresh(g)">刷新预估</el-button>
            <el-button link type="primary" size="small" @click="handleEdit(g)">编辑</el-button>
          </div>
        </div>
      </div>
    </div>

    <!-- 分组规则构造器（弹窗） -->
    <el-dialog
      v-model="formDialogVisible"
      :title="editingId ? '编辑分组规则' : '新建分组'"
      width="720px"
      :close-on-click-modal="false"
      destroy-on-close
      class="rule-builder-dialog"
    >
      <el-form
        ref="formRef"
        :model="formData"
        :rules="formRules"
        label-width="100px"
        class="rule-builder-body"
      >
        <el-form-item label="分组名" prop="name">
          <el-input v-model="formData.name" placeholder="如：618 大促候选 v2" style="max-width: 360px" />
        </el-form-item>

        <el-form-item label="描述" prop="description">
          <el-input v-model="formData.description" placeholder="选填" style="max-width: 480px" />
        </el-form-item>

        <el-form-item label="条件组合">
          <RuleConditionEditor v-model="rulesModel" />
        </el-form-item>

        <el-form-item label="预估覆盖">
          <div class="estimate-row">
            <span class="estimate-num num">{{ formatCount(estimatedCount) }}</span>
            <span class="estimate-meta">人 · 占总会员 {{ estimatedPct }}%</span>
            <el-button size="small" :loading="estimating" @click="handleEstimate">刷新预估</el-button>
          </div>
        </el-form-item>

        <el-form-item label="自动刷新">
          <el-switch v-model="formData.auto_update" />
          <span class="form-tip">开启后定时任务将按规则自动刷新分组成员</span>
        </el-form-item>

        <el-form-item label="状态">
          <el-switch v-model="formData.status" />
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="rule-builder-footer">
          <el-button v-if="editingId" type="danger" plain @click="handleDelete(currentEditingRow)">删除分组</el-button>
          <span class="footer-spacer" />
          <el-button @click="formDialogVisible = false">取消</el-button>
          <el-button type="primary" :loading="submitLoading" @click="handleSubmit">
            {{ editingId ? '保存修改' : '保存分组' }}
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 成员抽屉 -->
    <el-drawer
      v-model="drawerVisible"
      :title="`分组成员 — ${currentGroupName}`"
      size="640px"
      destroy-on-close
    >
      <div class="drawer-content">
        <el-table v-loading="memberLoading" :data="memberList" size="small">
          <el-table-column label="ID" prop="id" width="70" />
          <el-table-column label="头像" width="60">
            <template #default="{ row }">
              <el-avatar :src="row.avatar" :size="32" />
            </template>
          </el-table-column>
          <el-table-column label="昵称" prop="nickname" min-width="100" show-overflow-tooltip />
          <el-table-column label="手机号" prop="mobile" width="130" />
          <el-table-column label="状态" width="70">
            <template #default="{ row }">
              <el-tag :type="row.status ? 'success' : 'danger'" size="small">
                {{ row.status ? '正常' : '禁用' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="80" fixed="right">
            <template #default="{ row }">
              <el-button type="danger" size="small" text @click="handleRemoveMember(row)">
                移除
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <el-pagination
          v-model:current-page="memberPagination.page"
          v-model:page-size="memberPagination.limit"
          :total="memberPagination.total"
          layout="total, prev, pager, next"
          @current-change="fetchMembers"
          class="drawer-pagination"
        />
      </div>
    </el-drawer>

    <!-- 分组模板选择对话框 -->
    <el-dialog v-model="templateDialogVisible" title="选择分组模板" width="600px">
      <div class="template-grid">
        <div
          v-for="tpl in GROUP_TEMPLATES"
          :key="tpl.name"
          class="template-card"
          @click="handlePickTemplate(tpl)"
        >
          <div class="tpl-name">{{ tpl.name }}</div>
          <div class="tpl-desc">{{ tpl.description }}</div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="UserGroupList">
import { ref, reactive, computed, onMounted } from 'vue'
import type { FormInstance } from 'element-plus'
import { ElMessage, ElMessageBox } from 'element-plus'
import { userGroupApi } from '@/api/user-group'
import RuleConditionEditor, { type Rules } from '@/components/RuleConditionEditor/index.vue'

// 分组卡片渐变调色
const SEG_GRADIENTS = [
  'linear-gradient(135deg,#f43f5e,#ec4899)',
  'linear-gradient(135deg,#f59e0b,#f43f5e)',
  'linear-gradient(135deg,#0ea5e9,#8b5cf6)',
  'linear-gradient(135deg,#10b981,#14b8a6)',
]

const formatCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')

const FIELD_LABELS: Record<string, string> = {
  created_at: '注册时间',
  total_consume: '累计消费',
  order_count: '订单数',
  points: '积分',
  balance: '余额',
  member_level_id: '会员等级',
  last_login_time: '最后登录',
  is_distributor: '分销员',
}

const conditionChips = (g: Record<string, any>): string[] => {
  const rules = g.rules || {}
  const conditions = Array.isArray(rules.conditions) ? rules.conditions : []
  return conditions.slice(0, 4).map((c: any) => {
    const label = FIELD_LABELS[c.field] || c.field || '—'
    const prefix = c.exclude ? '非 ' : ''
    return `${prefix}${label} ${c.op || '='} ${c.value ?? ''}`
  })
}

// ─── 列表 ───
const tableData = ref<Record<string, any>[]>([])
const loading = ref(false)

const memberTotal = computed(() =>
  tableData.value.reduce((s, g) => s + (Number(g.user_count) || 0), 0)
)

const pct = (cnt: number) => {
  if (!memberTotal.value) return 0
  return Math.round(((Number(cnt) || 0) / memberTotal.value) * 1000) / 10
}

const fetchList = async () => {
  try {
    loading.value = true
    const res = await userGroupApi.getList({ page: 1, limit: 200 })
    tableData.value = res.data.list || []
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

// ─── 规则构造器弹窗 ───
const formDialogVisible = ref(false)
const submitLoading = ref(false)
const formRef = ref<FormInstance>()
const editingId = ref<number>(0)
const currentEditingRow = ref<Record<string, any> | null>(null)

interface Condition {
  field: string
  op: string
  value: string
  exclude: boolean
}

const defaultForm = () => ({
  name: '',
  description: '',
  sort: 0,
  auto_update: false,
  status: true,
  logic: 'AND',
  conditions: [] as Condition[],
})

const formData = reactive(defaultForm())

const formRules = {
  name: [{ required: true, message: '请输入分组名称', trigger: 'blur' }],
}

// 把 form 上的 logic + conditions 暴露成 RuleConditionEditor 需要的 Rules 形态
const rulesModel = computed<Rules>({
  get: () => ({
    logic: (formData.logic === 'OR' ? 'OR' : 'AND') as 'AND' | 'OR',
    conditions: formData.conditions,
  }),
  set: (v: Rules) => {
    formData.logic = v.logic
    formData.conditions = v.conditions
  },
})

// ─── 写死的分组模板（前端配置，非后端持久化）───
type GroupTemplate = {
  name: string
  description: string
  buildConditions: () => Array<{ field: string; op: string; value: string; exclude: boolean }>
}

const GROUP_TEMPLATES: GroupTemplate[] = [
  {
    name: '近 30 天活跃',
    description: '最近 30 天有登录的会员',
    buildConditions: () => [{ field: 'last_login_time', op: '>=', value: '30_days_ago', exclude: false }],
  },
  {
    name: '高消费用户',
    description: '累计消费 ¥1000 以上',
    buildConditions: () => [{ field: 'total_consume', op: '>=', value: '1000', exclude: false }],
  },
  {
    name: '沉睡用户',
    description: '90 天未登录的会员',
    buildConditions: () => [{ field: 'last_login_time', op: '<', value: '90_days_ago', exclude: false }],
  },
  {
    name: '新注册（7 天内）',
    description: '近 7 天注册的会员',
    buildConditions: () => [{ field: 'created_at', op: '>=', value: '7_days_ago', exclude: false }],
  },
  {
    name: '高积分用户',
    description: '积分 ≥ 1000 的会员',
    buildConditions: () => [{ field: 'points', op: '>=', value: '1000', exclude: false }],
  },
]

const templateDialogVisible = ref(false)
const handleOpenTemplates = () => {
  templateDialogVisible.value = true
}
const handlePickTemplate = (tpl: GroupTemplate) => {
  templateDialogVisible.value = false
  handleNew() // reset 表单到新建模式
  formData.name = tpl.name
  formData.description = tpl.description
  formData.conditions = tpl.buildConditions()
}

const resetForm = () => {
  editingId.value = 0
  currentEditingRow.value = null
  Object.assign(formData, defaultForm())
  estimatedCount.value = 0
  formRef.value?.clearValidate()
}

const handleNew = () => {
  resetForm()
  formDialogVisible.value = true
}

const handleEdit = (row: Record<string, any>) => {
  editingId.value = row.id
  currentEditingRow.value = row
  const rules = row.rules || {}
  Object.assign(formData, {
    name: row.name,
    description: row.description || '',
    sort: row.sort,
    auto_update: !!row.auto_update,
    status: !!row.status,
    logic: rules.logic || 'AND',
    conditions: (rules.conditions || []).map((c: any) => ({
      field: c.field || '',
      op: c.op || '=',
      value: String(c.value ?? ''),
      exclude: !!c.exclude,
    })),
  })
  estimatedCount.value = Number(row.user_count) || 0
  formDialogVisible.value = true
}

// 预估覆盖（粗略：复用刷新接口；新建时无 id，计算等于"暂未保存"）
const estimating = ref(false)
const estimatedCount = ref(0)
const estimatedPct = computed(() => {
  if (!memberTotal.value) return 0
  return Math.round((estimatedCount.value / memberTotal.value) * 1000) / 10
})
const handleEstimate = async () => {
  if (!editingId.value) {
    ElMessage.info('请先保存分组后再预估覆盖')
    return
  }
  try {
    estimating.value = true
    const res = await userGroupApi.refresh(editingId.value)
    estimatedCount.value = Number(res.data?.matched ?? 0)
    ElMessage.success(`刷新完成，匹配 ${estimatedCount.value} 位用户`)
    fetchList()
  } catch (e) {
    console.error(e)
  } finally {
    estimating.value = false
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return
  await formRef.value.validate()
  try {
    submitLoading.value = true
    const payload = {
      name: formData.name,
      description: formData.description,
      sort: formData.sort,
      auto_update: formData.auto_update ? 1 : 0,
      status: formData.status ? 1 : 0,
      rules: formData.conditions.length > 0
        ? { logic: formData.logic, conditions: formData.conditions }
        : null,
    } as any
    if (editingId.value) {
      await userGroupApi.update(editingId.value, payload)
      ElMessage.success('更新成功')
    } else {
      await userGroupApi.create(payload)
      ElMessage.success('创建成功')
    }
    formDialogVisible.value = false
    resetForm()
    fetchList()
  } catch (e) {
    console.error(e)
  } finally {
    submitLoading.value = false
  }
}

const handleDelete = async (row: Record<string, any> | null) => {
  if (!row) return
  try {
    await ElMessageBox.confirm(`确定要删除分组「${row.name}」？`, '删除确认', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await userGroupApi.delete(row.id)
    ElMessage.success('删除成功')
    formDialogVisible.value = false
    resetForm()
    fetchList()
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

const handleRefresh = async (row: Record<string, any>) => {
  try {
    const res = await userGroupApi.refresh(row.id)
    ElMessage.success(`刷新成功，共匹配 ${res.data.matched} 位用户`)
    fetchList()
  } catch (e) {
    console.error(e)
  }
}

// ─── 成员抽屉 ───
const drawerVisible = ref(false)
const currentGroupId = ref(0)
const currentGroupName = ref('')
const memberList = ref<Record<string, any>[]>([])
const memberLoading = ref(false)
const memberPagination = reactive({ page: 1, limit: 20, total: 0 })

const handleShowMembers = (row: Record<string, any>) => {
  currentGroupId.value = row.id
  currentGroupName.value = row.name
  memberPagination.page = 1
  drawerVisible.value = true
  fetchMembers()
}

const fetchMembers = async () => {
  try {
    memberLoading.value = true
    const res = await userGroupApi.show(currentGroupId.value, {
      page: memberPagination.page,
      limit: memberPagination.limit,
    })
    const members = res.data.members || {}
    memberList.value = members.list || []
    memberPagination.total = members.pagination?.total || 0
  } catch (e) {
    console.error(e)
  } finally {
    memberLoading.value = false
  }
}

const handleRemoveMember = async (row: Record<string, any>) => {
  try {
    await ElMessageBox.confirm(`确定要将 "${row.nickname || row.id}" 从分组中移除吗？`, '移除确认', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await userGroupApi.removeUsers(currentGroupId.value, [row.id])
    ElMessage.success('移除成功')
    fetchMembers()
    fetchList()
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

onMounted(() => {
  fetchList()
})
</script>

<style lang="scss" scoped>
.seg-grid {
  display: grid !important;
  grid-template-columns: repeat(2, 1fr) !important;
  gap: 14px;
  // margin-bottom 由全局 .row-12 提供（14px）

  @media (max-width: 768px) {
    grid-template-columns: 1fr !important;
  }
}

.seg-icon {
  width: 22px;
  height: 22px;
  opacity: 0.7;
  color: #fff;
}

.seg-card {
  padding: 0;
  overflow: hidden;
  border-radius: 6px;
}

.seg-head {
  padding: 18px 20px;
  color: #fff;
}

.seg-name {
  font-size: 16px;
  font-weight: 600;
}

.seg-desc {
  font-size: 12px;
  opacity: 0.85;
  margin-top: 4px;
}

.seg-stat-l {
  font-size: 11px;
  opacity: 0.85;
}

.seg-stat-v {
  font-size: 22px;
  font-weight: 700;
  margin-top: 2px;
}

.seg-body {
  padding: 14px 20px;
}

.seg-chip-empty {
  background: var(--ink-50);
  color: var(--ink-400);
  border-color: var(--ink-100);
}

.seg-acts {
  justify-content: flex-end;
}

.user-group-container {
  .rule-builder-body {
    padding: 4px 4px 0;
    max-height: 65vh;
    overflow-y: auto;
  }

  .cond-block {
    width: 100%;
    padding: 14px;
    background: var(--ink-50, #f6f7fa);
    border-radius: 8px;
    border: 1px solid var(--ink-100, #eef0f5);
  }

  .cond-logic-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    font-size: 12.5px;
    color: var(--ink-500, #8b95a7);
  }

  .cond-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
    & + .cond-row { border-top: 1px dashed var(--ink-200, #dde0e6); }
  }

  .cond-mode {
    flex-shrink: 0;
  }

  .cond-actions {
    margin-top: 12px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .cond-hint {
    margin-top: 10px;
    font-size: 12px;
    color: var(--ink-500, #8b95a7);
    code {
      background: #fff;
      padding: 1px 6px;
      border-radius: 3px;
      border: 1px solid var(--ink-100, #eef0f5);
      font-size: 11.5px;
    }
  }

  .estimate-row {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
  }
  .estimate-num {
    font-size: 28px;
    font-weight: 700;
    color: var(--brand-500, #4f6bff);
    line-height: 1;
  }
  .estimate-meta {
    font-size: 12px;
    color: var(--ink-500, #8b95a7);
  }

  .rule-builder-footer {
    display: flex;
    align-items: center;
    gap: 8px;
    .footer-spacer { flex: 1; }
  }
}

.form-tip {
  margin-left: 10px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.drawer-content {
  padding: 0 4px;

  .drawer-pagination {
    margin-top: 12px;
    display: flex;
    justify-content: flex-end;
  }
}

.template-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.template-card {
  padding: 14px;
  border: 1px solid var(--ink-100);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.18s;
  background: #fff;
}
.template-card:hover {
  border-color: var(--brand-500);
  background: var(--brand-50);
  transform: translateY(-1px);
}
.tpl-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--ink-900);
  margin-bottom: 4px;
}
.tpl-desc {
  font-size: 12.5px;
  color: var(--ink-500);
  line-height: 1.5;
}
</style>
