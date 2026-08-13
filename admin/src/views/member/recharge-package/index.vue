<template>
  <div class="recharge-package-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">充值套餐</h2>
        <p class="page-desc">预存款充值方案与赠送规则</p>
      </div>
      <div class="page-actions">
        <el-button v-has-perm="['member.recharge-package.create']" type="primary" @click="handleAdd">
          <i class="i-svg:plus" /> 新建套餐
        </el-button>
      </div>
    </div>

    <!-- KPI -->
    <div class="row-14">
      <div class="kpi-mini">
        <div class="lb">本月充值</div>
        <div class="nm num">¥ {{ formatPrice(kpi.rechargeMonth) }}</div>
        <div class="tr"><span class="tr-success">{{ kpi.rechargeMonth ? '已支付' : '—' }}</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">充值人数</div>
        <div class="nm num">{{ formatCount(kpi.rechargeUsers) }}</div>
        <div class="tr"><span>本月</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">平均客单</div>
        <div class="nm num">¥ {{ formatPrice(kpi.arpu) }}</div>
        <div class="tr"><span>近期</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">套餐总数</div>
        <div class="nm num">{{ formatCount(pagination.total) }}</div>
        <div class="tr"><span>启用 {{ stats.enabled }} · 禁用 {{ stats.disabled }}</span></div>
      </div>
    </div>

    <!-- 套餐卡片网格 -->
    <div v-loading="loading" class="row-13 pkg-grid">
      <div
        v-for="row in tableData"
        :key="row.id"
        class="card pkg-card"
        :class="{ 'pkg-hot': isHot(row) }"
      >
        <div v-if="isHot(row)" class="pkg-hot-badge">热销</div>
        <div class="card-body pkg-body">
          <div class="pkg-head">
            <div class="pkg-icon" :class="{ 'pkg-icon-hot': isHot(row) }">
              <i class="i-lucide:coins" />
            </div>
            <div>
              <div class="pkg-name">{{ row.name || `套餐 #${row.id}` }}</div>
              <div class="pkg-id num">PK-{{ String(row.id).padStart(2, '0') }}</div>
            </div>
          </div>

          <div class="pkg-amount-row">
            <span class="pkg-amount-label">充</span>
            <span class="pkg-amount-value num">¥{{ formatInt(row.amount ?? 0) }}</span>
            <span v-if="Number(row.gift_amount) > 0" class="pkg-gift-tag num">
              + ¥{{ formatInt(row.gift_amount) }}
            </span>
          </div>

          <div class="pkg-bonus">
            <span v-if="bonusText(row)">{{ bonusText(row) }}</span>
            <span v-else class="pkg-bonus-empty">无附加奖励</span>
          </div>

          <div class="pkg-foot-stat">
            <span class="pkg-stat-label">本月销售</span>
            <span class="pkg-stat-value num">{{ formatCount(row.sales_count || 0) }} 笔</span>
          </div>

          <div class="tbl-acts pkg-acts">
            <el-button v-has-perm="['member.recharge-package.update']" type="primary" size="small" text @click="handleEdit(row)">
              编辑
            </el-button>
            <el-button v-has-perm="['member.recharge-package.update']" size="small" text @click="handleToggleStatus(row)">
              {{ row.status ? '下架' : '上架' }}
            </el-button>
            <el-button v-has-perm="['member.recharge-package.delete']" type="danger" size="small" text @click="handleDelete(row.id, row.name)">
              删除
            </el-button>
          </div>
        </div>
      </div>

      <div v-if="!loading && !tableData.length" class="pkg-empty">
        <el-empty description="暂无套餐，点击右上角新建" />
      </div>
    </div>

    <!-- 分页 -->
    <div v-if="pagination.total > pagination.limit" class="pkg-pagination">
      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.limit"
        :total="pagination.total"
        :page-sizes="[12, 24, 48]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
      />
    </div>

    <!-- 最近充值流水 -->
    <el-card class="trans-card" shadow="never">
      <div class="card-head">
        <div class="card-title">最近充值流水</div>
      </div>
      <el-table :data="recentTransactions" v-loading="recentLoading" empty-text="暂无充值记录">
        <el-table-column label="会员" prop="user_nickname" min-width="120" />
        <el-table-column label="套餐" prop="package_name" width="140">
          <template #default="{ row }">{{ row.package_name || '—' }}</template>
        </el-table-column>
        <el-table-column label="支付金额" width="120">
          <template #default="{ row }">
            <span class="num pay-amount">¥{{ formatPrice(row.amount) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="到账金额" width="120">
          <template #default="{ row }">
            <span class="num arrive-amount">¥{{ formatPrice(Number(row.amount) + Number(row.gift_amount || 0)) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="赠送" width="110">
          <template #default="{ row }">
            <span v-if="Number(row.gift_amount) > 0" class="num gift-amount">¥{{ formatPrice(row.gift_amount) }}</span>
            <span v-else class="text-ink-400">—</span>
          </template>
        </el-table-column>
        <el-table-column label="支付方式" width="110">
          <template #default="{ row }">{{ payMethodMap[row.pay_type] || row.pay_type || '—' }}</template>
        </el-table-column>
        <el-table-column label="时间" width="180">
          <template #default="{ row }">
            <span class="num text-ink-500">{{ row.paid_at || row.created_at }}</span>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 新增/编辑 对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑套餐' : '新增套餐'"
      width="480px"
      @close="handleDialogClose"
    >
      <el-form ref="formRef" :model="formData" :rules="formRules" label-width="110px">
        <el-form-item label="套餐名称" prop="name">
          <el-input v-model="formData.name" placeholder="选填，例如：尊享套餐" />
        </el-form-item>
        <el-form-item label="充值金额(元)" prop="amount">
          <el-input-number
            v-model="formData.amount"
            :min="0.01"
            :precision="2"
            :step="10"
            style="width: 100%"
            placeholder="请输入充值金额"
          />
        </el-form-item>
        <el-form-item label="赠送金额(元)" prop="gift_amount">
          <el-input-number
            v-model="formData.gift_amount"
            :min="0"
            :precision="2"
            :step="1"
            style="width: 100%"
            placeholder="0 表示不赠送"
          />
        </el-form-item>
        <el-form-item label="赠送积分" prop="gift_points">
          <el-input-number
            v-model="formData.gift_points"
            :min="0"
            :step="10"
            style="width: 100%"
            placeholder="0 表示不赠送"
          />
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
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="RechargePackageList">
import type { FormInstance } from 'element-plus'
import { ElMessage } from 'element-plus'
import { computed, onMounted, reactive, ref } from 'vue'
import { accountFundApi } from '@/api/account-fund'
import { rechargePackageApi } from '@/api/recharge-package'
import { useListPage } from '@/hooks/useListPage'
import type { RechargePackageInfo, RechargePackageQuery } from '@/types/api'

// 列表
const {
  list: tableData,
  loading,
  pagination,
  getList: fetchList,
  handlePageChange,
  handleSizeChange,
  handleDelete,
} = useListPage<RechargePackageInfo, RechargePackageQuery>({
  fetchFn: (params) => rechargePackageApi.getList(params),
  deleteFn: (id) => rechargePackageApi.delete(id),
  pageSize: 12,
})

// 当前页统计概览
const stats = computed(() => {
  let enabled = 0, disabled = 0, giftTotal = 0
  for (const row of tableData.value) {
    if (row.status === 1 || row.status === true) enabled += 1
    else disabled += 1
    giftTotal += Number(row.gift_amount || 0)
  }
  return { enabled, disabled, giftTotal }
})

// 顶部 KPI（取自后端 fund stats + recent recharges 推算）
const kpi = reactive({
  rechargeMonth: 0,
  rechargeUsers: 0,
  arpu: 0,
})

const formatCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')
const formatPrice = (n: number | string) => (n == null ? '0.00' : Number(n).toFixed(2))
const formatInt = (n: number | string) => Math.round(Number(n) || 0).toString()

// 套餐是否标记为热销（金额 ≥ 300 视作主推套餐）
const isHot = (row: Record<string, any>) => Number(row.amount) >= 300 && Number(row.amount) < 1000

const bonusText = (row: Record<string, any>) => {
  const parts: string[] = []
  if (Number(row.gift_amount) > 0) parts.push(`+ ¥${formatInt(row.gift_amount)} 代金`)
  if (Number(row.gift_points) > 0) parts.push(`+ ${row.gift_points} 积分`)
  return parts.join('、')
}

const payMethodMap: Record<string, string> = {
  wechat: '微信', alipay: '支付宝', balance: '余额', bank: '银行卡',
}

// 对话框
const dialogVisible = ref(false)
const isEdit = ref(false)
const submitLoading = ref(false)
const formRef = ref<FormInstance>()

const defaultForm = () => ({
  name: '',
  amount: 100,
  gift_amount: 0,
  gift_points: 0,
  sort: 0,
  status: true,
})

const formData = reactive<Record<string, any>>(defaultForm())
let editId = 0

const formRules = {
  amount: [{ required: true, message: '请输入充值金额', trigger: 'blur' }],
}

// 最近充值流水
const recentTransactions = ref<Record<string, any>[]>([])
const recentLoading = ref(false)
const fetchRecent = async () => {
  try {
    recentLoading.value = true
    const res = await accountFundApi.getRechargeOrders({ page: 1, limit: 8 })
    recentTransactions.value = (res as any).data?.list || []

    // 推算 KPI
    const list = recentTransactions.value
    const sumAmount = list.reduce((sum, r) => sum + Number(r.amount || 0), 0)
    kpi.rechargeMonth = sumAmount
    kpi.rechargeUsers = new Set(list.map((r) => r.user_id)).size
    kpi.arpu = list.length ? Math.round(sumAmount / list.length) : 0
  } catch (e) {
    console.error('获取充值流水失败:', e)
  } finally {
    recentLoading.value = false
  }
}

// 新增
const handleAdd = () => {
  isEdit.value = false
  editId = 0
  Object.assign(formData, defaultForm())
  dialogVisible.value = true
}

// 编辑
const handleEdit = (row: Record<string, any>) => {
  isEdit.value = true
  editId = row.id
  Object.assign(formData, {
    name: row.name || '',
    amount: Number(row.amount),
    gift_amount: Number(row.gift_amount),
    gift_points: Number(row.gift_points),
    sort: row.sort,
    status: !!row.status,
  })
  dialogVisible.value = true
}

// 上下架（toggle status）
const handleToggleStatus = async (row: Record<string, any>) => {
  try {
    await rechargePackageApi.update(row.id, { ...row, status: row.status ? 0 : 1 })
    ElMessage.success(row.status ? '已下架' : '已上架')
    fetchList()
  } catch (e) {
    console.error('切换状态失败:', e)
  }
}

// 提交
const handleSubmit = async () => {
  if (!formRef.value) return
  await formRef.value.validate()
  try {
    submitLoading.value = true
    const payload = { ...formData, status: formData.status ? 1 : 0 }
    if (isEdit.value) {
      await rechargePackageApi.update(editId, payload)
      ElMessage.success('更新成功')
    } else {
      await rechargePackageApi.create(payload)
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

const handleDialogClose = () => {
  formRef.value?.resetFields()
}

onMounted(() => {
  // useListPage 已在 mounted 自动加载列表
  fetchRecent()
})
</script>

<style lang="scss" scoped>
.recharge-package-container {
  // pkg-grid 即 .row-13，间距由全局 row-13 mb:14 + row-14 mb:14 提供

  .pkg-card {
    position: relative;
    overflow: hidden;
    padding: 0;
    border: 1px solid var(--ink-100, #eef0f5);
    border-radius: 8px;
    background: #fff;
    transition: transform .15s ease, box-shadow .15s ease;
    &:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 22px rgba(0,0,0,.06);
    }
  }
  .pkg-hot {
    border: 1.5px solid var(--rose-500, #f43f5e);
  }
  .pkg-hot-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 3px 10px;
    background: linear-gradient(135deg, #f43f5e, #ec4899);
    color: #fff;
    font-size: 11px;
    border-radius: 10px;
    font-weight: 600;
    z-index: 1;
  }
  .pkg-body { padding: 22px 24px; }

  .pkg-head { display: flex; align-items: center; gap: 10px; }
  .pkg-icon {
    width: 36px;
    height: 36px;
    border-radius: 18px;
    background: var(--brand-50, #eef2ff);
    color: var(--brand-500, #4f6bff);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    i { width: 18px; height: 18px; }
  }
  .pkg-icon-hot {
    background: linear-gradient(135deg, #f43f5e, #ec4899);
    color: #fff;
  }
  .pkg-name { font-size: 14px; font-weight: 600; color: var(--ink-900, #1f2937); }
  .pkg-id { font-size: 11px; color: var(--ink-400, #a3acba); margin-top: 2px; }

  .pkg-amount-row {
    margin-top: 18px;
    display: flex;
    align-items: baseline;
    gap: 6px;
    flex-wrap: wrap;
  }
  .pkg-amount-label { font-size: 14px; color: var(--ink-500, #8b95a7); }
  .pkg-amount-value {
    font-size: 36px;
    font-weight: 800;
    color: var(--ink-900, #1f2937);
    line-height: 1;
  }
  .pkg-gift-tag {
    padding: 3px 10px;
    background: var(--rose-500, #f43f5e);
    color: #fff;
    font-size: 11px;
    border-radius: 10px;
    font-weight: 600;
    margin-left: 6px;
  }

  .pkg-bonus {
    margin-top: 8px;
    font-size: 12px;
    color: var(--ink-500, #8b95a7);
    min-height: 18px;
  }
  .pkg-bonus-empty { color: var(--ink-300, #cbd0d9); }

  .pkg-foot-stat {
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid var(--ink-100, #eef0f5);
    display: flex;
    justify-content: space-between;
    font-size: 12px;
  }
  .pkg-stat-label { color: var(--ink-500, #8b95a7); }
  .pkg-stat-value { font-weight: 600; color: var(--ink-900, #1f2937); }

  .pkg-acts {
    margin-top: 10px;
    justify-content: flex-end;
  }

  .pkg-empty {
    grid-column: 1 / -1;
  }

  .pkg-pagination {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 14px;
  }

  .trans-card {
    .card-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 20px 12px;
      border-bottom: 1px solid var(--ink-100, #eef0f5);
    }
    .card-title { font-size: 15px; font-weight: 600; }
    :deep(.el-card__body) { padding: 0; }
  }

  .pay-amount { font-weight: 600; }
  .arrive-amount { font-weight: 600; color: var(--el-color-success); }
  .gift-amount { color: var(--rose-500, #f43f5e); }
  .text-ink-400 { color: var(--ink-400, #a3acba); }
  .text-ink-500 { color: var(--ink-500, #8b95a7); font-size: 12.5px; }
  .tr-success { color: var(--el-color-success); }
}
</style>
