<template>
  <div class="member-list-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">会员管理</h2>
        <p class="page-desc">注册、登录方式、第三方授权与实名认证</p>
      </div>
      <div class="page-actions">
        <el-button
          type="warning"
          :disabled="selectedRows.length === 0"
          @click="handleBatchTag"
        >
          <i class="i-svg:tag" /> 批量打标
          <span v-if="selectedRows.length">（{{ selectedRows.length }}）</span>
        </el-button>
      </div>
    </div>

    <!-- KPI -->
    <div class="row-14">
      <div class="kpi-mini">
        <div class="lb">会员总数</div>
        <div class="nm num">{{ formatCount(pagination.total) }}</div>
        <div class="tr"><span>分页统计</span></div>
      </div>
      <div class="kpi-mini" v-if="hasDistribution">
        <div class="lb">分销商</div>
        <div class="nm num">{{ formatCount(stats.distributor) }}</div>
        <div class="tr"><span>当前页内</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">已打标签</div>
        <div class="nm num">{{ formatCount(stats.tagged) }}</div>
        <div class="tr"><span>当前页内</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">含等级</div>
        <div class="nm num">{{ formatCount(stats.leveled) }}</div>
        <div class="tr"><span>当前页内</span></div>
      </div>
    </div>

    <!-- 单行过滤栏 -->
    <div class="filter-bar">
      <el-input
        v-model="searchForm.keyword"
        placeholder="搜索昵称 / 手机号"
        clearable
        style="width: 240px"
        @keyup.enter="handleSearch"
      />
      <span class="filter-label">等级：</span>
      <el-select v-model="searchForm.level_id" placeholder="全部" clearable style="width: 130px" @change="handleSearch">
        <el-option v-for="level in levelList" :key="level.id" :label="level.name" :value="level.id" />
      </el-select>
      <span class="filter-label">标签：</span>
      <el-select
        v-model="searchForm.tag_ids"
        placeholder="全部"
        clearable
        multiple
        collapse-tags
        collapse-tags-tooltip
        style="width: 200px"
        @change="handleSearch"
      >
        <el-option v-for="tag in tagList" :key="tag.id" :label="tag.name" :value="tag.id">
          <div class="tag-option flex items-center gap-1.5">
            <span class="color-dot" :style="{ backgroundColor: tag.color }" />
            <span>{{ tag.name }}</span>
          </div>
        </el-option>
      </el-select>
      <template v-if="hasDistribution">
      <span class="filter-label">分销：</span>
      <el-select v-model="searchForm.is_distributor" placeholder="全部" clearable style="width: 110px" @change="handleSearch">
        <el-option label="是" :value="1" />
        <el-option label="否" :value="0" />
      </el-select>
      </template>
      <span class="filter-sp" />
      <el-button @click="handleReset">重置</el-button>
      <el-button type="primary" @click="handleSearch">查询</el-button>
    </div>


    <!-- 表格 -->
    <el-card class="table-card" shadow="never">
      <div class="table-header">
        <div class="table-title">用户列表</div>
      </div>

      <el-table
        v-loading="loading"
        :data="tableData"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="50" />

        <el-table-column label="用户信息" min-width="180">
          <template #default="{ row }">
            <div class="user-info flex items-center gap-2.5">
              <el-avatar
                :src="row.avatar"
                :size="40"
                class="user-avatar"
              >
                {{ row.nickname?.charAt(0) }}
              </el-avatar>
              <div class="user-meta">
                <div class="user-nickname">{{ row.nickname || '-' }}</div>
                <div class="user-id">ID: {{ row.id }}</div>
              </div>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="手机号" prop="mobile" width="150" />

        <el-table-column label="用户标签" min-width="180">
          <template #default="{ row }">
            <div class="tag-list flex flex-wrap gap-1">
              <template v-if="row.tags && row.tags.length">
                <el-tag
                  v-for="tag in row.tags"
                  :key="tag.id"
                  size="small"
                  :style="{ backgroundColor: tag.color, borderColor: tag.color, color: '#fff' }"
                  class="user-tag-item"
                >
                  {{ tag.name }}
                </el-tag>
              </template>
              <span v-else class="text-secondary">-</span>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="会员等级" width="120">
          <template #default="{ row }">
            <span v-if="row.member_level?.name">{{ row.member_level.name }}</span>
            <span v-else class="text-secondary">未分级</span>
          </template>
        </el-table-column>

        <el-table-column label="余额(元)" width="110">
          <template #default="{ row }">
            ¥{{ formatAmount(row.balance) }}
          </template>
        </el-table-column>

        <el-table-column label="积分" prop="points" width="90" />

        <el-table-column label="累计消费(元)" width="140">
          <template #default="{ row }">
            ¥{{ formatAmount(row.total_consume) }}
          </template>
        </el-table-column>

        <el-table-column label="订单数" prop="order_count" width="100" />

        <el-table-column v-if="hasDistribution" label="分销商" width="100">
          <template #default="{ row }">
            <el-tag
              :type="row.is_distributor ? 'success' : 'info'"
              size="small"
            >
              {{ row.is_distributor ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column label="操作" width="240" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" size="small" text @click="handleViewDetail(row)">
              详情
            </el-button>
            <el-button type="success" size="small" text @click="handleAdjustBalance(row)">
              调余额
            </el-button>
            <el-button type="warning" size="small" text @click="handleAdjustPoints(row)">
              调积分
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.limit"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
        class="pagination"
      />
    </el-card>

    <!-- 调整余额对话框 -->
    <el-dialog
      v-model="balanceDialogVisible"
      title="调整余额"
      width="420px"
      @close="handleBalanceDialogClose"
    >
      <el-form
        ref="balanceFormRef"
        :model="balanceForm"
        :rules="balanceRules"
        label-width="80px"
      >
        <el-form-item label="调整类型">
          <el-radio-group v-model="balanceForm.type">
            <el-radio :value="1">增加</el-radio>
            <el-radio :value="-1">减少</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="金额(元)" prop="amount">
          <el-input-number
            v-model="balanceForm.amount"
            :min="0.01"
            :precision="2"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="备注" prop="remark">
          <el-input
            v-model="balanceForm.remark"
            type="textarea"
            placeholder="请输入备注"
            :rows="3"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="balanceDialogVisible = false">取消</el-button>
        <el-button
          type="primary"
          :loading="balanceSubmitLoading"
          @click="handleBalanceSubmit"
        >
          确定
        </el-button>
      </template>
    </el-dialog>

    <!-- 调整积分对话框 -->
    <el-dialog
      v-model="pointsDialogVisible"
      title="调整积分"
      width="420px"
      @close="handlePointsDialogClose"
    >
      <el-form
        ref="pointsFormRef"
        :model="pointsForm"
        :rules="pointsRules"
        label-width="80px"
      >
        <el-form-item label="调整类型">
          <el-radio-group v-model="pointsForm.type">
            <el-radio :value="1">增加</el-radio>
            <el-radio :value="-1">减少</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="积分数" prop="points">
          <el-input-number
            v-model="pointsForm.points"
            :min="1"
            :precision="0"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="备注" prop="remark">
          <el-input
            v-model="pointsForm.remark"
            type="textarea"
            placeholder="请输入备注"
            :rows="3"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="pointsDialogVisible = false">取消</el-button>
        <el-button
          type="primary"
          :loading="pointsSubmitLoading"
          @click="handlePointsSubmit"
        >
          确定
        </el-button>
      </template>
    </el-dialog>

    <!-- 批量打标签对话框 -->
    <el-dialog
      v-model="batchTagDialogVisible"
      title="批量打标签"
      width="460px"
      @close="handleBatchTagDialogClose"
    >
      <div class="batch-tag-tip">
        已选择 <strong>{{ selectedRows.length }}</strong> 个用户
      </div>
      <el-form
        ref="batchTagFormRef"
        :model="batchTagForm"
        :rules="batchTagRules"
        label-width="80px"
        style="margin-top: 16px"
      >
        <el-form-item label="选择标签" prop="tag_ids">
          <el-select
            v-model="batchTagForm.tag_ids"
            multiple
            collapse-tags
            collapse-tags-tooltip
            placeholder="请选择要打的标签"
            style="width: 100%"
          >
            <el-option
              v-for="tag in tagList"
              :key="tag.id"
              :label="tag.name"
              :value="tag.id"
            >
              <div class="tag-option flex items-center gap-1.5">
                <span class="color-dot" :style="{ backgroundColor: tag.color }" />
                <span>{{ tag.name }}</span>
              </div>
            </el-option>
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="batchTagDialogVisible = false">取消</el-button>
        <el-button
          type="primary"
          :loading="batchTagSubmitLoading"
          @click="handleBatchTagSubmit"
        >
          确定打标
        </el-button>
      </template>
    </el-dialog>

    <UserDetailDrawer v-model="detailDrawerVisible" :user-id="detailUserId" />
  </div>
</template>

<script setup lang="ts" name="MemberList">
import type { FormInstance } from 'element-plus'
import { ElMessage } from 'element-plus'
import { computed, onMounted, reactive, ref } from 'vue'
import { memberApi } from '@/api/member'
import { memberLevelApi } from '@/api/member-level'
import { userTagApi } from '@/api/user-tag'
import { useListPage } from '@/hooks/useListPage'
import { useUserStore } from '@/store/modules/user.store'
import type { MemberLevelInfo, MemberQuery, UserItem } from '@/types/api'
import UserDetailDrawer from './components/UserDetailDrawer.vue'

const userStore = useUserStore()
const hasDistribution = computed(() => userStore.hasPermission('distribution.list'))

// 等级 / 标签筛选选项
const levelList = ref<MemberLevelInfo[]>([])
const tagList = ref<Array<{ id: number; name: string; [k: string]: any }>>([])

// 列表
const {
  list: tableData,
  loading,
  pagination,
  searchForm,
  getList: fetchList,
  handleSearch,
  resetSearch: handleReset,
  handlePageChange,
  handleSizeChange,
} = useListPage<UserItem, MemberQuery & { tag_ids?: number[]; is_distributor?: number; keyword?: string }>({
  fetchFn: (params) => {
    const final: Record<string, any> = { ...params }
    if (Array.isArray(final.tag_ids) && final.tag_ids.length === 0) {
      delete final.tag_ids
    }
    return memberApi.getUserList(final)
  },
  defaultSearchForm: { keyword: '', level_id: undefined, tag_ids: [], is_distributor: undefined },
})

const selectedRows = ref<UserItem[]>([])

// 当前页统计概览（KPI mini）
const stats = computed(() => {
  let distributor = 0, tagged = 0, leveled = 0
  for (const row of tableData.value as any[]) {
    if (row.is_distributor) distributor += 1
    if (Array.isArray(row.tags) && row.tags.length) tagged += 1
    if (row.member_level || Number(row.member_level_id) > 0) leveled += 1
  }
  return { distributor, tagged, leveled }
})

const formatCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')

const formatAmount = (val: number) => {
  if (val == null) return '0.00'
  return Number(val).toFixed(2)
}

const loadLevelList = async () => {
  try {
    const res = await memberLevelApi.getList({ page: 1, limit: 100 })
    levelList.value = res.data.list || []
  } catch (error) {
    console.error('加载等级列表失败:', error)
  }
}

const loadTagList = async () => {
  try {
    // 复用全部启用标签接口（无分页），用于会员列表的批量打标 / 筛选下拉
    const res = await userTagApi.getAll()
    tagList.value = (res.data as any) || []
  } catch (error) {
    console.error('加载标签列表失败:', error)
  }
}

const detailDrawerVisible = ref(false)
const detailUserId = ref<number | null>(null)
const handleViewDetail = (row: any) => {
  detailUserId.value = row.id
  detailDrawerVisible.value = true
}

const handleSelectionChange = (rows: UserItem[]) => {
  selectedRows.value = rows
}

// ---------- 批量打标签 ----------
const batchTagDialogVisible = ref(false)
const batchTagSubmitLoading = ref(false)
const batchTagFormRef = ref<FormInstance>()

const batchTagForm = reactive({
  tag_ids: [] as number[],
})

const batchTagRules = {
  tag_ids: [{ required: true, type: 'array', min: 1, message: '请选择至少一个标签', trigger: 'change' }],
}

const handleBatchTag = () => {
  batchTagForm.tag_ids = []
  batchTagDialogVisible.value = true
}

const handleBatchTagSubmit = async () => {
  if (!batchTagFormRef.value) return
  await batchTagFormRef.value.validate()
  batchTagSubmitLoading.value = true
  try {
    const userIds = selectedRows.value.map((r) => r.id)
    await userTagApi.assign({ user_ids: userIds, tag_ids: batchTagForm.tag_ids })
    ElMessage.success('打标成功')
    batchTagDialogVisible.value = false
    fetchList()
  } catch (e) {
    console.error('打标失败:', e)
  } finally {
    batchTagSubmitLoading.value = false
  }
}

const handleBatchTagDialogClose = () => {
  batchTagFormRef.value?.resetFields()
}

// ---------- 调整余额 ----------
const balanceDialogVisible = ref(false)
const balanceSubmitLoading = ref(false)
const balanceFormRef = ref<FormInstance>()
let currentUserId = 0

const balanceForm = reactive({
  type: 1,
  amount: undefined as number | undefined,
  remark: '',
})

const balanceRules = {
  amount: [{ required: true, message: '请输入调整金额', trigger: 'blur' }],
}

const handleAdjustBalance = (row: Record<string, any>) => {
  currentUserId = row.id
  Object.assign(balanceForm, { type: 1, amount: undefined, remark: '' })
  balanceDialogVisible.value = true
}

const handleBalanceSubmit = async () => {
  if (!balanceFormRef.value) return
  await balanceFormRef.value.validate()
  try {
    balanceSubmitLoading.value = true
    await memberApi.adjustBalance(currentUserId, balanceForm)
    ElMessage.success('余额调整成功')
    balanceDialogVisible.value = false
    fetchList()
  } catch (error) {
    console.error('余额调整失败:', error)
  } finally {
    balanceSubmitLoading.value = false
  }
}

const handleBalanceDialogClose = () => {
  balanceFormRef.value?.resetFields()
}

// ---------- 调整积分 ----------
const pointsDialogVisible = ref(false)
const pointsSubmitLoading = ref(false)
const pointsFormRef = ref<FormInstance>()

const pointsForm = reactive({
  type: 1,
  points: undefined as number | undefined,
  remark: '',
})

const pointsRules = {
  points: [{ required: true, message: '请输入调整积分数', trigger: 'blur' }],
}

const handleAdjustPoints = (row: Record<string, any>) => {
  currentUserId = row.id
  Object.assign(pointsForm, { type: 1, points: undefined, remark: '' })
  pointsDialogVisible.value = true
}

const handlePointsSubmit = async () => {
  if (!pointsFormRef.value) return
  await pointsFormRef.value.validate()
  try {
    pointsSubmitLoading.value = true
    await memberApi.adjustPoints(currentUserId, pointsForm)
    ElMessage.success('积分调整成功')
    pointsDialogVisible.value = false
    fetchList()
  } catch (error) {
    console.error('积分调整失败:', error)
  } finally {
    pointsSubmitLoading.value = false
  }
}

const handlePointsDialogClose = () => {
  pointsFormRef.value?.resetFields()
}

onMounted(() => {
  loadLevelList()
  loadTagList()
  // useListPage 已在 mounted 自动加载主列表
})
</script>

<style lang="scss" scoped>
.member-list-container {
  .stats-row {
    margin-bottom: 14px;
  }

  .user-info {
    .user-avatar {
      flex-shrink: 0;
    }

    .user-meta {
      min-width: 0;

      .user-nickname {
        font-size: 14px;
        font-weight: 500;
        color: var(--el-text-color-primary);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .user-id {
        font-size: 12px;
        color: var(--el-text-color-secondary);
        margin-top: 2px;
      }
    }
  }

  .tag-list {
    .user-tag-item {
      border-radius: 3px;
    }
  }

  .text-secondary {
    color: var(--el-text-color-secondary);
  }

  .batch-tag-tip {
    color: var(--el-text-color-regular);
    font-size: 14px;

    strong {
      color: var(--el-color-primary);
    }
  }
}

.color-dot {
  display: inline-block;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}
</style>
