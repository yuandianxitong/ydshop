<template>
  <div class="user-tag-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">用户标签</h2>
        <p class="page-desc">按消费力 / 行为偏好 / 生命周期 / 社交属性圈定人群</p>
      </div>
      <div class="page-actions">
        <el-button v-has-perm="['member.tag.create']" type="primary" @click="handleAdd()">
          <i class="i-lucide:plus mr-1" /> 新增标签
        </el-button>
      </div>
    </div>

    <!-- 4 列分组卡片网格 -->
    <div class="group-grid">
      <el-card v-for="g in GROUP_TYPES" :key="g.key" class="group-card" shadow="never">
        <div class="group-head">
          <span class="group-name" :style="{ color: g.color }">{{ g.label }}</span>
          <span class="group-count">{{ tagsByGroup[g.key].length }} 个</span>
          <span class="filter-sp" />
          <el-button v-has-perm="['member.tag.create']" link type="primary" size="small" @click="handleAdd(g.key)">+ 添加</el-button>
        </div>
        <div v-if="tagsByGroup[g.key].length" class="chip-grid">
          <span
            v-for="t in tagsByGroup[g.key]"
            :key="t.id"
            class="tag-chip"
            :style="{ background: t.color + '14', borderColor: t.color + '55', color: t.color }"
            @click="handleEdit(t)"
          >
            {{ t.name }}
            <span class="chip-count num">· {{ t.user_count }}</span>
            <el-tag v-if="t.auto_update" size="small" effect="plain" class="auto-flag">自动</el-tag>
          </span>
        </div>
        <div v-else class="chip-empty">暂无标签</div>
      </el-card>
    </div>

    <!-- 搜索 -->
    <div class="filter-bar">
      <el-input v-model="searchForm.keyword" placeholder="搜索标签名称" clearable style="width: 240px" @keyup.enter="handleSearch" />
      <span class="filter-label">分组：</span>
      <el-select v-model="searchForm.group_type" placeholder="全部" clearable style="width: 130px" @change="handleSearch">
        <el-option v-for="g in GROUP_TYPES" :key="g.key" :label="g.label" :value="g.key" />
      </el-select>
      <span class="filter-label">打标方式：</span>
      <el-select v-model="searchForm.auto_update" placeholder="全部" clearable style="width: 110px" @change="handleSearch">
        <el-option label="规则自动" :value="1" />
        <el-option label="手工打标" :value="0" />
      </el-select>
      <span class="filter-sp" />
      <el-button @click="handleReset">重置</el-button>
      <el-button type="primary" @click="handleSearch">查询</el-button>
    </div>

    <el-card class="table-card" shadow="never">
      <div class="table-header">
        <div class="table-title">标签明细 <span class="table-count">共 {{ pagination.total }} 条</span></div>
      </div>

      <el-table v-loading="loading" :data="tableData">
        <el-table-column label="标签" min-width="200">
          <template #default="{ row }">
            <span class="tag-chip-sm" :style="{ background: row.color + '14', borderColor: row.color + '55', color: row.color }">{{ row.name }}</span>
            <div v-if="row.description" class="text-secondary tag-desc">{{ row.description }}</div>
          </template>
        </el-table-column>
        <el-table-column label="分组" width="120">
          <template #default="{ row }">{{ row.group_type_text || row.group_type }}</template>
        </el-table-column>
        <el-table-column label="打标方式" width="110">
          <template #default="{ row }">
            <el-tag :class="['tag-tone-' + (row.auto_update ? 'blue' : 'gray')]" size="small" effect="light">{{ row.auto_update ? '规则自动' : '手工' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="规则摘要" min-width="240">
          <template #default="{ row }">
            <span v-if="row.rules?.conditions?.length" class="text-secondary rule-summary">{{ summarize(row.rules) }}</span>
            <span v-else class="text-secondary">—</span>
          </template>
        </el-table-column>
        <el-table-column label="覆盖人数" width="110" align="right">
          <template #default="{ row }"><span class="num">{{ formatCount(row.user_count) }}</span></template>
        </el-table-column>
        <el-table-column label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :class="['tag-tone-' + (row.status === 1 ? 'green' : 'gray')]" size="small" effect="light">{{ row.status === 1 ? '启用' : '停用' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button v-if="row.auto_update" v-has-perm="['member.tag.refresh']" type="primary" size="small" text :loading="refreshingId === row.id" @click="handleRefresh(row)">立即刷新</el-button>
            <el-button v-has-perm="['member.tag.update']" type="primary" size="small" text @click="handleEdit(row)">编辑</el-button>
            <el-button v-has-perm="['member.tag.delete']" type="danger" size="small" text @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.limit"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        class="pagination"
        @size-change="fetchList"
        @current-change="fetchList"
      />
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <el-dialog
      v-model="formVisible"
      :title="isEdit ? '编辑标签' : '新增标签'"
      width="720px"
      :close-on-click-modal="false"
      destroy-on-close
      @close="handleClose"
    >
      <el-form ref="formRef" :model="form" :rules="formRules" label-width="100px">
        <el-form-item label="标签名称" prop="name">
          <el-input v-model="form.name" placeholder="如：高消费用户 / 沉睡客" maxlength="30" show-word-limit />
        </el-form-item>
        <el-form-item label="分组" prop="group_type">
          <el-radio-group v-model="form.group_type">
            <el-radio v-for="g in GROUP_TYPES" :key="g.key" :value="g.key">{{ g.label }}</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="form.description" placeholder="选填" maxlength="255" show-word-limit />
        </el-form-item>
        <el-form-item label="颜色">
          <el-color-picker v-model="form.color" show-alpha />
        </el-form-item>

        <el-form-item label="自动打标">
          <el-switch
            :model-value="form.auto_update === 1"
            @update:model-value="(v: any) => form.auto_update = v ? 1 : 0"
          />
          <span class="form-tip">开启后定时任务（每 30 分钟）按规则重算覆盖用户</span>
        </el-form-item>

        <el-form-item v-if="form.auto_update" label="规则">
          <RuleConditionEditor v-model="form.rules" />
        </el-form-item>

        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" :max="9999" controls-position="right" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch
            :model-value="form.status === 1"
            @update:model-value="(v: any) => form.status = v ? 1 : 0"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <span class="dialog-footer">
          <el-button @click="handleClose">取消</el-button>
          <el-button type="primary" :loading="submitLoading" @click="handleSubmit">确定</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="UserTagList">
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox, ElForm } from 'element-plus'
import { userTagApi, type UserTagInfo, type TagGroupType } from '@/api/user-tag'
import RuleConditionEditor, { type Rules } from '@/components/RuleConditionEditor/index.vue'

const GROUP_TYPES: Array<{ key: TagGroupType; label: string; color: string }> = [
  { key: 'consume',   label: '消费力',   color: '#f43f5e' },
  { key: 'behavior',  label: '行为偏好', color: '#0ea5e9' },
  { key: 'lifecycle', label: '生命周期', color: '#10b981' },
  { key: 'social',    label: '社交属性', color: '#a855f7' },
]

const FIELD_LABEL: Record<string, string> = {
  created_at: '注册时间',
  total_consume: '累计消费',
  order_count: '订单数',
  points: '积分',
  balance: '余额',
  member_level_id: '会员等级',
  last_login_time: '最后登录',
  is_distributor: '分销员',
}

const summarize = (rules: Rules): string => {
  const conds = (rules.conditions || []).slice(0, 3).map((c) => {
    const lbl = FIELD_LABEL[c.field] || c.field
    return `${c.exclude ? '非 ' : ''}${lbl} ${c.op} ${c.value}`
  })
  const more = (rules.conditions?.length ?? 0) > 3 ? ` 等 ${rules.conditions.length} 项` : ''
  return conds.join(rules.logic === 'OR' ? ' 或 ' : ' 且 ') + more
}

const formatCount = (n: number) => Number(n ?? 0).toLocaleString('zh-CN')

// ── 4 列分组卡片网格数据
const allTags = ref<UserTagInfo[]>([])
const tagsByGroup = computed<Record<TagGroupType, UserTagInfo[]>>(() => {
  const m: Record<TagGroupType, UserTagInfo[]> = { consume: [], behavior: [], lifecycle: [], social: [] }
  for (const t of allTags.value) {
    const k = (t.group_type || 'social') as TagGroupType
    if (m[k]) m[k].push(t)
  }
  return m
})

const fetchAllTags = async () => {
  try {
    const res = await userTagApi.getAll()
    allTags.value = res.data || []
  } catch (e) {
    console.error('加载标签失败:', e)
  }
}

// ── 明细分页
const searchForm = reactive({
  keyword: '',
  group_type: undefined as TagGroupType | undefined,
  auto_update: undefined as number | undefined,
})
const tableData = ref<UserTagInfo[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, limit: 20, total: 0 })

const fetchList = async () => {
  try {
    loading.value = true
    const res = await userTagApi.getList({
      ...searchForm,
      page: pagination.page,
      limit: pagination.limit,
    })
    tableData.value = res.data.list
    pagination.total = res.data.pagination.total
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  pagination.page = 1
  fetchList()
}
const handleReset = () => {
  Object.assign(searchForm, { keyword: '', group_type: undefined, auto_update: undefined })
  pagination.page = 1
  fetchList()
}

// ── 弹窗（新增/编辑）
const formVisible = ref(false)
const formRef = ref<InstanceType<typeof ElForm>>()
const editingId = ref<number>(0)
const submitLoading = ref(false)
const isEdit = computed(() => editingId.value > 0)

const defaultForm = (groupType: TagGroupType = 'social') => ({
  name: '',
  description: '',
  color: '#409eff',
  group_type: groupType,
  rules: null as Rules | null,
  auto_update: 0,
  sort: 0,
  status: 1,
})
const form = reactive(defaultForm())

const formRules = {
  name: [{ required: true, message: '请输入标签名称', trigger: 'blur' }],
}

const handleAdd = (groupType: TagGroupType = 'social') => {
  editingId.value = 0
  Object.assign(form, defaultForm(groupType))
  formVisible.value = true
}

const handleEdit = (row: any) => {
  editingId.value = row.id
  Object.assign(form, {
    name: row.name,
    description: row.description || '',
    color: row.color || '#409eff',
    group_type: row.group_type || 'social',
    rules: row.rules || null,
    auto_update: row.auto_update ? 1 : 0,
    sort: row.sort || 0,
    status: row.status ?? 1,
  })
  formVisible.value = true
}

const handleClose = () => {
  formRef.value?.resetFields()
  formVisible.value = false
}

const handleSubmit = async () => {
  if (!formRef.value) return
  await formRef.value.validate()
  submitLoading.value = true
  try {
    const payload = {
      name: form.name,
      description: form.description,
      color: form.color,
      group_type: form.group_type,
      rules: form.auto_update ? form.rules : null,
      auto_update: form.auto_update,
      sort: form.sort,
      status: form.status,
    }
    if (isEdit.value) {
      await userTagApi.update(editingId.value, payload)
      ElMessage.success('更新成功')
    } else {
      await userTagApi.create(payload)
      ElMessage.success('创建成功')
    }
    formVisible.value = false
    await Promise.all([fetchList(), fetchAllTags()])
  } finally {
    submitLoading.value = false
  }
}

// ── 删除 / 立即刷新
const handleDelete = async (row: any) => {
  try {
    await ElMessageBox.confirm(`确定要删除标签「${row.name}」？已打的标记会一并清除。`, '删除确认', {
      confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning',
    })
    await userTagApi.delete(row.id)
    ElMessage.success('删除成功')
    await Promise.all([fetchList(), fetchAllTags()])
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

const refreshingId = ref<number>(0)
const handleRefresh = async (row: any) => {
  refreshingId.value = row.id
  try {
    const res = await userTagApi.refresh(row.id)
    ElMessage.success(`刷新完成，匹配 ${res.data?.count ?? 0} 位用户`)
    await Promise.all([fetchList(), fetchAllTags()])
  } finally {
    refreshingId.value = 0
  }
}

onMounted(() => {
  fetchList()
  fetchAllTags()
})
</script>

<style lang="scss" scoped>
.user-tag-container {
  .group-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
    margin-bottom: 14px;
    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .group-card {
    :deep(.el-card__body) { padding: 16px 18px; }
  }
  .group-head {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
  }
  .group-name { font-size: 14px; font-weight: 600; }
  .group-count { font-size: 12px; color: var(--ink-500); }

  .chip-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }
  .chip-empty {
    font-size: 12px;
    color: var(--ink-400);
    padding: 12px 0;
  }

  .tag-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 12px;
    border: 1px solid;
    font-size: 12.5px;
    cursor: pointer;
    transition: all .15s;

    &:hover { transform: translateY(-1px); }

    .chip-count { font-size: 11px; opacity: 0.85; }
    .auto-flag {
      margin-left: 4px;
      transform: scale(0.85);
      transform-origin: center;
    }
  }

  .tag-chip-sm {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    border: 1px solid;
    font-size: 12px;
  }

  .tag-desc {
    margin-top: 4px;
  }

  .rule-summary { font-size: 12.5px; }
  .text-secondary { color: var(--ink-500); }

  .form-tip {
    margin-left: 10px;
    font-size: 12px;
    color: var(--ink-500);
  }
}
.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
