<script setup lang="ts">
import { ref, computed, watch, reactive } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { memberApi, type UserPreference, type UserLifecycle, type UserOperationLog, type UserCouponItem, type MemberRemarkItem } from '@/api/member'
import { userTagApi, type UserTagInfo } from '@/api/user-tag'
import { orderApi } from '@/api/order'
import { userManageApi } from '@/api/user'
import { addressBookApi, type AddressInfo } from '@/api/address-book'
import { useUserStore } from '@/store/modules/user.store'
import SendSmsDialog from './SendSmsDialog.vue'
import IssueCouponDialog from './IssueCouponDialog.vue'
import EditProfileDialog from './EditProfileDialog.vue'
import AddressFormDialog from './AddressFormDialog.vue'

interface Props {
  modelValue: boolean
  userId: number | null
}
interface Emits {
  (e: 'update:modelValue', v: boolean): void
  (e: 'changed'): void
}
const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const userStore = useUserStore()
const hasDistribution = computed(() => userStore.hasPermission('distribution.list'))

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

// 主色调（按等级色调切换，先固定 brand 蓝）
const tone = '#4f6bff'

// ─── 真实数据 ───
const detail = ref<Record<string, any> | null>(null)
const stats = ref({ gmv: 0, orders: 0, avg_amount: 0, last_order_at: null as string | null, balance: 0, points: 0 })
const detailLoading = ref(false)

const activeTab = ref<'overview' | 'profile' | 'orders' | 'assets' | 'tags' | 'address' | 'log'>('overview')

const TABS: Array<[typeof activeTab.value, string]> = [
  ['overview', '概览'],
  ['profile',  '基础资料'],
  ['orders',   '订单'],
  ['assets',   '资产'],
  ['tags',     '标签 / 生命周期'],
  ['address',  '地址簿'],
  ['log',      '操作日志'],
]

let seq = 0
watch(
  () => [props.modelValue, props.userId] as const,
  async ([open, id]) => {
    const cur = ++seq
    if (!open || !id) {
      detail.value = null
      activeTab.value = 'overview'
      return
    }
    detailLoading.value = true
    try {
      const [d, s] = await Promise.all([
        memberApi.getUserDetail(Number(id)),
        memberApi.getUserStats(Number(id)),
      ])
      if (cur !== seq) return
      detail.value = (d.data as Record<string, any>) || null
      stats.value = { ...stats.value, ...(s.data as any) }
      // 概览首屏顺手拉偏好 + 标签云
      fetchPreference()
      fetchRemarks()
      if (!allTags.value.length) fetchAllTags()
    } finally {
      if (cur === seq) detailLoading.value = false
    }
  },
)

const fmtMoney = (v: any) => Number(v ?? 0).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const fmtCount = (v: any) => Number(v ?? 0).toLocaleString('zh-CN')
const initial = (s?: string) => (s || '?').charAt(0).toUpperCase()

// ─── 偏好分析（真实接口） ───
const preference = ref<UserPreference | null>(null)
const preferenceLoading = ref(false)
const fetchPreference = async () => {
  if (!props.userId) return
  preferenceLoading.value = true
  try {
    const res = await memberApi.getUserPreference(props.userId)
    preference.value = res.data as any
  } finally {
    preferenceLoading.value = false
  }
}

// 90 天柱状（最大值归一化到 100%）
const trendBars = computed(() => {
  const arr = preference.value?.trend90 || []
  if (!arr.length) return [] as number[]
  const max = Math.max(...arr.map(t => t.gmv), 1)
  return arr.map(t => Math.max(2, Math.round(t.gmv * 100 / max)))
})
const trendDates = computed(() => {
  const arr = preference.value?.trend90 || []
  if (arr.length < 4) return ['', '', '', '']
  return [arr[0]?.date, arr[Math.floor(arr.length / 3)]?.date, arr[Math.floor(arr.length * 2 / 3)]?.date, arr[arr.length - 1]?.date]
})
const CAT_COLORS = ['#f43f5e', '#8b5cf6', '#0ea5e9', '#10b981', '#94a3b8']
const PAY_COLORS: Record<string, string> = { wechat: '#10b981', alipay: '#0ea5e9', balance: '#f59e0b', mock: '#8b5cf6', cash: '#94a3b8' }
const PAY_LABEL: Record<string, string> = { wechat: '微信支付', alipay: '支付宝', balance: '余额支付', mock: '模拟支付', cash: '现金' }

const hourHeat = computed(() => {
  const arr = preference.value?.hour_heat || []
  if (!arr.length) return Array.from({ length: 24 }).map((_, h) => ({ h, v: 0, op: 0.05 }))
  const max = Math.max(...arr.map(c => c.count), 1)
  return arr.map(c => ({ h: c.hour, v: c.count, op: Math.min(c.count / max, 1) }))
})

// ─── 概览 4 资产卡 ───
const assetCards = computed(() => [
  { label: '账户余额', value: '¥ ' + fmtMoney(stats.value.balance), sub: '可用 ' + fmtMoney(stats.value.balance), color: '#4f6bff' },
  { label: '可用积分', value: fmtCount(stats.value.points),          sub: '本月活跃累积',                            color: '#10b981' },
  { label: '优惠券',   value: couponPagination.total + ' 张',         sub: '未使用 + 即将过期',                       color: '#f59e0b' },
  { label: '成长值',   value: fmtCount(detail.value?.growth_value || 0), sub: detail.value?.level_name || '未配置等级', color: '#8b5cf6' },
])

// ─── 概览右侧：标签云 ───
const tagCloud = computed(() => {
  const ts = (detail.value?.tags || []) as Array<{ name: string; color: string }>
  return ts
})

// ─── 备注（真实） ───
const remarks = ref<MemberRemarkItem[]>([])
const remarksLoading = ref(false)
const fetchRemarks = async () => {
  if (!props.userId) return
  remarksLoading.value = true
  try {
    const res = await memberApi.listRemarks(props.userId)
    remarks.value = (res.data?.list as any) || []
  } finally {
    remarksLoading.value = false
  }
}
const addRemark = async () => {
  if (!props.userId) return
  try {
    const { value } = await ElMessageBox.prompt('运营备注', '添加备注', {
      inputType: 'textarea',
      inputPlaceholder: '请输入备注内容（≤ 500 字）',
      confirmButtonText: '保存',
      cancelButtonText: '取消',
      inputValidator: (v) => !!v && v.trim().length > 0,
      inputErrorMessage: '内容不能为空',
    })
    await memberApi.addRemark(props.userId, value)
    ElMessage.success('已添加')
    fetchRemarks()
  } catch (e) {
    if (e !== 'cancel' && e !== 'close') console.error(e)
  }
}
const deleteRemark = async (rid: number) => {
  if (!props.userId) return
  try {
    await ElMessageBox.confirm('确定删除该备注？', '删除确认', { type: 'warning' })
    await memberApi.deleteRemark(props.userId, rid)
    ElMessage.success('已删除')
    fetchRemarks()
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

// ─── 订单（实数据） ───
const ordersData = ref<Record<string, any>[]>([])
const ordersLoading = ref(false)
const ordersPagination = reactive({ page: 1, limit: 10, total: 0 })

const orderSearch = reactive({ keyword: '', status: '', range: '' })
const ORDER_RANGES = [
  { v: '30', l: '近 30 天' },
  { v: '90', l: '近 90 天' },
  { v: '365', l: '近一年' },
]
const ORDER_STATUSES = [
  { v: '',          l: '全部状态' },
  { v: 'pending',   l: '待付款' },
  { v: 'paid',      l: '待发货' },
  { v: 'shipped',   l: '已发货' },
  { v: 'completed', l: '已完成' },
  { v: 'cancelled', l: '已取消' },
]

const fetchOrders = async () => {
  if (!props.userId) return
  ordersLoading.value = true
  try {
    const params: Record<string, any> = {
      user_id: props.userId,
      page: ordersPagination.page,
      limit: ordersPagination.limit,
      keyword: orderSearch.keyword || undefined,
      status: orderSearch.status || undefined,
    }
    if (orderSearch.range) {
      const days = Number(orderSearch.range)
      const end   = new Date()
      const start = new Date(Date.now() - days * 86400000)
      params.start_date = start.toISOString().slice(0, 10)
      params.end_date   = end.toISOString().slice(0, 10)
    }
    const res = await orderApi.getOrderList(params as any)
    ordersData.value = res.data?.list || []
    ordersPagination.total = res.data?.pagination?.total || 0
  } finally {
    ordersLoading.value = false
  }
}
const handleOrderSearch = () => { ordersPagination.page = 1; fetchOrders() }
const handleOrderReset = () => {
  orderSearch.keyword = ''
  orderSearch.status = ''
  orderSearch.range = ''
  ordersPagination.page = 1
  fetchOrders()
}

const ORDER_STATUS_MAP: Record<string, { label: string; tone: string }> = {
  pending:   { label: '待付款', tone: 'amber' },
  paid:      { label: '待发货', tone: 'blue' },
  shipped:   { label: '已发货', tone: 'blue' },
  completed: { label: '已完成', tone: 'green' },
  cancelled: { label: '已取消', tone: 'rose' },
  closed:    { label: '已关闭', tone: 'gray' },
}

const orderKpis = computed(() => [
  { l: '订单总数', v: fmtCount(stats.value.orders),         s: '已完成订单',     c: '#4f6bff' },
  { l: '累计 GMV', v: '¥ ' + fmtMoney(stats.value.gmv),     s: '本年累计',       c: '#10b981' },
  { l: '平均客单', v: '¥ ' + fmtMoney(stats.value.avg_amount), s: '客单价',       c: '#8b5cf6' },
  { l: '退款率',   v: (preference.value?.refund_rate ?? 0) + '%', s: '近 90 天',  c: '#f59e0b' },
  { l: '复购率',   v: (preference.value?.repurchase_rate ?? 0) + '%', s: '已完成订单', c: '#f43f5e' },
])

// ─── 资产：余额流水 / 积分流水 / 优惠券 ───
const assetsTab = ref<'balance' | 'points' | 'coupon'>('balance')
const balanceLogs = ref<Record<string, any>[]>([])
const balanceLoading = ref(false)
const balancePagination = reactive({ page: 1, limit: 10, total: 0 })

const fetchBalanceLogs = async () => {
  if (!props.userId) return
  balanceLoading.value = true
  try {
    const res = await userManageApi.getBalanceLogs({ user_id: props.userId, page: balancePagination.page, limit: balancePagination.limit })
    balanceLogs.value = res.data?.list || []
    balancePagination.total = res.data?.pagination?.total || 0
  } finally { balanceLoading.value = false }
}

const pointsLogs = ref<Record<string, any>[]>([])
const pointsLoading = ref(false)
const pointsPagination = reactive({ page: 1, limit: 10, total: 0 })

const fetchPointsLogs = async () => {
  if (!props.userId) return
  pointsLoading.value = true
  try {
    const res = await userManageApi.getPointsLogs({ user_id: props.userId, page: pointsPagination.page, limit: pointsPagination.limit })
    pointsLogs.value = res.data?.list || []
    pointsPagination.total = res.data?.pagination?.total || 0
  } finally { pointsLoading.value = false }
}

// 用户优惠券（真实接口）
const userCoupons = ref<UserCouponItem[]>([])
const couponLoading = ref(false)
const couponPagination = reactive({ page: 1, limit: 12, total: 0 })
const fetchUserCoupons = async () => {
  if (!props.userId) return
  couponLoading.value = true
  try {
    const res = await memberApi.listUserCoupons(props.userId, { page: couponPagination.page, limit: couponPagination.limit })
    userCoupons.value = (res.data?.list as any) || []
    couponPagination.total = res.data?.pagination?.total || 0
  } finally { couponLoading.value = false }
}
const COUPON_STATE: Record<string, { label: string; tone: string }> = {
  unused:   { label: '未使用',     tone: 'green' },
  used:     { label: '已使用',     tone: 'gray' },
  expired:  { label: '已过期',     tone: 'gray' },
}
const couponDisplayValue = (c: UserCouponItem) => {
  if (c.type === 'fixed')   return `¥ ${c.value}`
  if (c.type === 'percent') return `${c.value} 折`
  return '无门槛'
}

// ─── 标签 / 生命周期 ───
const allTags = ref<UserTagInfo[]>([])
const tagsLoading = ref(false)
const userAddresses = ref<AddressInfo[]>([])
const addressLoading = ref(false)

const fetchAllTags = async () => {
  tagsLoading.value = true
  try {
    const tagRes = await userTagApi.getAll()
    allTags.value = (tagRes.data as any) || []
  } finally {
    tagsLoading.value = false
  }
}
const fetchAddresses = async () => {
  if (!props.userId) return
  addressLoading.value = true
  try {
    const addrRes = await addressBookApi.getList({ user_id: props.userId, page: 1, limit: 50 })
    userAddresses.value = (addrRes.data?.list as any) || []
  } finally {
    addressLoading.value = false
  }
}

const userTagIds = computed<number[]>(() => {
  const tags = (detail.value?.tags || []) as Array<{ id: number }>
  return tags.map((t) => t.id)
})

const tagsByGroup = computed(() => {
  const m: Record<string, UserTagInfo[]> = { consume: [], behavior: [], lifecycle: [], social: [] }
  for (const t of allTags.value) {
    const k = (t.group_type || 'social') as keyof typeof m
    if (m[k]) m[k].push(t)
  }
  return m
})

const GROUP_TYPES: Array<{ key: 'consume' | 'behavior' | 'lifecycle' | 'social'; label: string }> = [
  { key: 'consume',   label: '消费力' },
  { key: 'behavior',  label: '行为偏好' },
  { key: 'lifecycle', label: '生命周期' },
  { key: 'social',    label: '社交属性' },
]

// 生命周期 / 所属用户分组
const lifecycle = ref<UserLifecycle | null>(null)
const lifecycleLoading = ref(false)
const fetchLifecycle = async () => {
  if (!props.userId) return
  lifecycleLoading.value = true
  try {
    const res = await memberApi.getUserLifecycle(props.userId)
    lifecycle.value = res.data as any
  } finally {
    lifecycleLoading.value = false
  }
}
const memberGroups = computed(() => {
  // 用户分组 = 已打的标签按 lifecycle / consume / behavior / social 透出
  const tags = (detail.value?.tags || []) as UserTagInfo[]
  return tags.slice(0, 6).map(t => ({
    name: t.name,
    desc: t.description || (t.group_type === 'consume' ? '消费力分组' : t.group_type === 'behavior' ? '行为分组' : t.group_type === 'lifecycle' ? '生命周期分组' : '社交分组'),
    color: t.color || tone,
  }))
})

const togglingTags = ref<Set<number>>(new Set())
const toggleTag = async (tag: UserTagInfo) => {
  if (!props.userId) return
  if (togglingTags.value.has(tag.id)) return
  togglingTags.value.add(tag.id)
  const has = userTagIds.value.includes(tag.id)
  try {
    if (has) {
      await userTagApi.remove({ user_ids: [props.userId], tag_ids: [tag.id] })
      ElMessage.success(`已移除标签 ${tag.name}`)
    } else {
      await userTagApi.assign({ user_ids: [props.userId], tag_ids: [tag.id] })
      ElMessage.success(`已打上标签 ${tag.name}`)
    }
    const arr = (detail.value?.tags || []) as UserTagInfo[]
    detail.value = {
      ...detail.value,
      tags: has ? arr.filter((t) => t.id !== tag.id) : [...arr, tag],
    }
    emit('changed')
  } finally {
    togglingTags.value.delete(tag.id)
  }
}

// ─── 操作日志（真实） ───
const logCategoriesMap = ref<Record<string, number>>({})
const logEntries = ref<UserOperationLog[]>([])
const logLoading = ref(false)
const logPagination = reactive({ page: 1, limit: 50, total: 0 })
const activeLogCat = ref<'all' | string>('all')

const LOG_CAT_LABEL: Array<[string, string]> = [
  ['all', '全部'],
  ['login', '登录'],
  ['asset', '资产'],
  ['order', '订单'],
  ['level', '等级'],
  ['service', '客服'],
  ['profile', '资料'],
]
const logCats = computed(() => LOG_CAT_LABEL.map(([k, n]) => ({ k, n, c: logCategoriesMap.value[k] ?? 0 })))

const fetchLogs = async () => {
  if (!props.userId) return
  logLoading.value = true
  try {
    const res = await memberApi.getOperationLogs(props.userId, {
      category: activeLogCat.value === 'all' ? undefined : activeLogCat.value,
      page: logPagination.page,
      limit: logPagination.limit,
    })
    logEntries.value = (res.data?.list as any) || []
    logCategoriesMap.value = (res.data?.categories as any) || {}
    logPagination.total = res.data?.pagination?.total || 0
  } finally {
    logLoading.value = false
  }
}
const switchLogCat = (k: string) => {
  activeLogCat.value = k as any
  logPagination.page = 1
  fetchLogs()
}

// 日志条目 fallback icon/tone（事件未带时）
const FALLBACK_LOG: Record<string, { icon: string; tone: string }> = {
  login:   { icon: 'i-lucide:log-in',         tone: '#0ea5e9' },
  asset:   { icon: 'i-lucide:wallet',         tone: '#4f6bff' },
  order:   { icon: 'i-lucide:shopping-cart',  tone: '#10b981' },
  level:   { icon: 'i-lucide:medal',          tone: '#f59e0b' },
  service: { icon: 'i-lucide:message-square', tone: '#8b5cf6' },
  profile: { icon: 'i-lucide:user-circle',    tone: '#6366f1' },
  other:   { icon: 'i-lucide:circle-dot',     tone: '#94a3b8' },
}
const logIcon = (l: UserOperationLog) => l.icon || FALLBACK_LOG[l.category]?.icon || FALLBACK_LOG.other.icon
const logTone = (l: UserOperationLog) => l.tone || FALLBACK_LOG[l.category]?.tone || FALLBACK_LOG.other.tone
const logCatLabel = (c: string) => (LOG_CAT_LABEL.find(([k]) => k === c)?.[1]) || c

// ─── Tab 切换懒加载 ───
const handleTabClick = (k: typeof activeTab.value) => {
  activeTab.value = k
  if (k === 'orders' && !ordersData.value.length) fetchOrders()
  if (k === 'assets') {
    if (assetsTab.value === 'balance' && !balanceLogs.value.length) fetchBalanceLogs()
    if (assetsTab.value === 'points'  && !pointsLogs.value.length)  fetchPointsLogs()
    if (!couponPagination.total)                                    fetchUserCoupons()
  }
  if (k === 'tags') {
    if (!allTags.value.length) fetchAllTags()
    if (!lifecycle.value)      fetchLifecycle()
  }
  if (k === 'address' && !userAddresses.value.length) fetchAddresses()
  if (k === 'log' && !logEntries.value.length) fetchLogs()
}

const handleAssetsSubTab = (k: typeof assetsTab.value) => {
  assetsTab.value = k
  if (k === 'balance' && !balanceLogs.value.length) fetchBalanceLogs()
  if (k === 'points'  && !pointsLogs.value.length)  fetchPointsLogs()
  if (k === 'coupon'  && !userCoupons.value.length) fetchUserCoupons()
}

// ─── 调资产 ───
const adjustVisible = ref(false)
const adjustType = ref<'balance' | 'points'>('balance')
const adjustForm = reactive({ amount: 0, points: 0, remark: '' })
const adjustSubmitting = ref(false)

const openAdjust = (type: 'balance' | 'points') => {
  adjustType.value = type
  adjustForm.amount = 0
  adjustForm.points = 0
  adjustForm.remark = ''
  adjustVisible.value = true
}

const submitAdjust = async () => {
  if (!props.userId) return
  adjustSubmitting.value = true
  try {
    if (adjustType.value === 'balance') {
      if (adjustForm.amount === 0) { ElMessage.warning('金额不能为 0'); return }
      await memberApi.adjustBalance(props.userId, { amount: adjustForm.amount, remark: adjustForm.remark } as any)
    } else {
      if (adjustForm.points === 0) { ElMessage.warning('积分不能为 0'); return }
      await memberApi.adjustPoints(props.userId, { points: adjustForm.points, remark: adjustForm.remark } as any)
    }
    ElMessage.success('调整成功')
    adjustVisible.value = false
    const res = await memberApi.getUserStats(props.userId)
    stats.value = { ...stats.value, ...(res.data as any) }
    if (adjustType.value === 'balance' && balanceLogs.value.length) fetchBalanceLogs()
    if (adjustType.value === 'points' && pointsLogs.value.length) fetchPointsLogs()
    emit('changed')
  } finally {
    adjustSubmitting.value = false
  }
}

// ─── 头部按钮联动 ───
const smsVisible = ref(false)
const couponDialogVisible = ref(false)
const profileDialogVisible = ref(false)
const addressDialogVisible = ref(false)
const editingAddress = ref<AddressInfo | null>(null)

const openSendSms = () => {
  if (!detail.value?.mobile) { ElMessage.warning('该用户未绑定手机号'); return }
  smsVisible.value = true
}
const openIssueCoupon = () => { couponDialogVisible.value = true }
const openEditProfile = () => { profileDialogVisible.value = true }
const onProfileSaved = async () => {
  if (!props.userId) return
  const d = await memberApi.getUserDetail(props.userId)
  detail.value = (d.data as any) || null
  emit('changed')
}
const onCouponIssued = () => { fetchUserCoupons() }

// 地址 CRUD
const openCreateAddress = () => { editingAddress.value = null; addressDialogVisible.value = true }
const openEditAddress = (a: AddressInfo) => { editingAddress.value = a; addressDialogVisible.value = true }
const setDefaultAddress = async (a: AddressInfo) => {
  await addressBookApi.setDefault(a.id)
  ElMessage.success('已设为默认')
  fetchAddresses()
}
const deleteAddress = async (a: AddressInfo) => {
  try {
    await ElMessageBox.confirm(`确定删除「${a.name}」的地址？`, '删除确认', { type: 'warning' })
    await addressBookApi.delete(a.id)
    ElMessage.success('已删除')
    fetchAddresses()
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}
</script>

<template>
  <el-dialog
    v-model="visible"
    width="1280px"
    align-center
    :close-on-click-modal="false"
    destroy-on-close
    :show-close="false"
    class="member-detail-dialog"
  >
    <!-- 自定义头部条 -->
    <template #header="{ close }">
      <div class="flex items-center justify-between gap-3 w-full md-header">
        <div class="flex items-center gap-2.5 text-[14px] font-semibold text-ink-900">
          <span class="w-[3px] h-[14px] rounded-sm" style="background: var(--brand-500)" />
          会员详情
          <span v-if="detail" class="num text-[12px] text-ink-400 font-normal">#{{ detail.id }}</span>
        </div>
        <div class="flex items-center gap-1.5 ml-auto">
          <el-button v-has-perm="['member.sms']"    size="small" @click="openSendSms">发短信</el-button>
          <el-button v-has-perm="['member.coupon']" size="small" @click="openIssueCoupon">送优惠券</el-button>
          <el-button v-has-perm="['member.update']" size="small" @click="openAdjust('balance')">调整余额</el-button>
          <el-button v-has-perm="['member.update']" size="small" @click="openAdjust('points')">调整积分</el-button>
          <el-button v-has-perm="['member.update']" size="small" type="primary" @click="openEditProfile">编辑资料</el-button>
          <button class="md-close" @click="close">×</button>
        </div>
      </div>
    </template>

    <div v-loading="detailLoading" class="md-body">
      <!-- 顶部档案条 -->
      <div v-if="detail" class="md-profile" :style="{ '--tone': tone }">
        <div class="md-avatar" :style="{ background: tone }">
          <img v-if="detail.avatar" :src="detail.avatar" alt="" class="size-full object-cover rounded-full" />
          <span v-else>{{ initial(detail.nickname || detail.mobile) }}</span>
        </div>
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2.5 flex-wrap">
            <span class="text-[18px] font-semibold text-ink-900">{{ detail.nickname || `用户 ${detail.id}` }}</span>
            <span v-if="detail.level_name" class="md-pill" :style="{ background: tone + '22', color: tone }">{{ detail.level_name }}</span>
            <el-tag :class="['tag-tone-' + (detail.status === 1 ? 'green' : 'gray')]" size="small" effect="light">{{ detail.status === 1 ? '正常' : '禁用' }}</el-tag>
            <el-tag v-if="hasDistribution && detail.is_distributor === 1" class="tag-tone-purple" size="small" effect="light">分销员</el-tag>
          </div>
          <div class="num mt-1.5 flex flex-wrap gap-x-[18px] gap-y-1 text-[12px] text-ink-500">
            <span v-if="detail.mobile" class="inline-flex items-center gap-1"><i class="i-lucide:smartphone text-[12px]" /> {{ detail.mobile }}</span>
            <span class="inline-flex items-center gap-1"><i class="i-lucide:hash text-[12px]" /> {{ detail.id }}</span>
            <span v-if="detail.created_at" class="inline-flex items-center gap-1"><i class="i-lucide:calendar text-[12px]" /> 注册于 {{ String(detail.created_at).slice(0, 10) }}</span>
            <span v-if="detail.last_login_time" class="inline-flex items-center gap-1"><i class="i-lucide:clock text-[12px]" /> 最近登录 {{ String(detail.last_login_time).slice(0, 16) }}</span>
          </div>
        </div>
        <div class="md-profile-kpis">
          <div class="text-right">
            <div class="text-[11px] text-ink-400">累计 GMV</div>
            <div class="num mt-0.5 text-[18px] font-bold text-ink-900 whitespace-nowrap">¥ {{ fmtMoney(stats.gmv) }}</div>
          </div>
          <div class="text-right">
            <div class="text-[11px] text-ink-400">累计订单</div>
            <div class="num mt-0.5 text-[18px] font-bold text-ink-900">{{ fmtCount(stats.orders) }}</div>
          </div>
          <div class="text-right">
            <div class="text-[11px] text-ink-400">客单价</div>
            <div class="num mt-0.5 text-[18px] font-bold text-ink-900 whitespace-nowrap">¥ {{ fmtMoney(stats.avg_amount) }}</div>
          </div>
          <div class="text-right">
            <div class="text-[11px] text-ink-400">复购率</div>
            <div class="num mt-0.5 text-[18px] font-bold text-ink-900">{{ preference?.repurchase_rate ?? 0 }}%</div>
          </div>
        </div>
      </div>

      <!-- Tab 条 -->
      <div class="md-tabs">
        <button
          v-for="[k, n] in TABS"
          :key="k"
          :class="['md-tab', activeTab === k && 'on']"
          @click="handleTabClick(k)"
        >{{ n }}</button>
      </div>

      <!-- Tab 内容区：固定高度内滚动，切换 tab 不改变弹窗高度 -->
      <div class="md-panel">
      <!-- ============ 概览 ============ -->
      <div v-if="activeTab === 'overview' && detail" class="grid grid-cols-3 gap-[14px]">
        <div class="col-span-2 grid gap-[14px]">
          <!-- 4 资产卡 -->
          <div class="grid grid-cols-4 gap-[10px]">
            <div v-for="c in assetCards" :key="c.label" class="md-card md-asset-card" :style="{ '--c': c.color }">
              <span class="md-asset-bg" />
              <div class="text-[11.5px] text-ink-500">{{ c.label }}</div>
              <div class="num mt-1.5 text-[20px] font-bold text-ink-900">{{ c.value }}</div>
              <div class="mt-1 text-[11px] text-ink-400">{{ c.sub }}</div>
            </div>
          </div>

          <!-- 90 天消费趋势 -->
          <div v-loading="preferenceLoading" class="md-card p-4">
            <div class="flex items-center justify-between mb-[14px]">
              <div class="text-[13px] font-semibold text-ink-900">近 90 天消费趋势</div>
              <div class="flex gap-[14px] text-[11.5px] text-ink-500">
                <span class="flex items-center gap-[5px]"><span class="w-2 h-2 rounded-full" :style="{ background: tone }" />下单金额</span>
                <span class="flex items-center gap-[5px]"><span class="w-2 h-2 rounded-full" style="background: #94a3b8" />下单频次</span>
              </div>
            </div>
            <div v-if="trendBars.length" class="md-trend-bars h-[120px]">
              <div v-for="(v, i) in trendBars" :key="i" class="md-trend-col" :style="{ height: v + '%', background: 'linear-gradient(180deg,' + tone + ',#8b5cf6)' }" />
            </div>
            <el-empty v-else description="暂无消费记录" :image-size="60" />
            <div v-if="trendBars.length" class="num flex justify-between mt-2 text-[11px] text-ink-400">
              <span>{{ trendDates[0] }}</span><span>{{ trendDates[1] }}</span><span>{{ trendDates[2] }}</span><span>{{ trendDates[3] }}</span>
            </div>
          </div>

          <!-- 偏好分析 -->
          <div v-loading="preferenceLoading" class="md-card p-4">
            <div class="text-[13px] font-semibold text-ink-900 mb-3">偏好分析</div>
            <div class="grid grid-cols-2 gap-9">
              <!-- 品类 -->
              <div>
                <div class="text-[11.5px] text-ink-500 mb-2">偏好品类（按 GMV 占比）</div>
                <div v-if="preference?.categories?.length">
                  <div v-for="(cat, i) in preference.categories" :key="cat.category_id" class="mb-1.5">
                    <div class="flex justify-between text-[11.5px] mb-[3px]">
                      <span>{{ cat.category_name }}</span>
                      <span class="num">¥ {{ fmtMoney(cat.gmv) }} <span class="text-ink-400">· {{ cat.percent }}%</span></span>
                    </div>
                    <div class="h-[5px] rounded-[3px] overflow-hidden" :style="{ background: CAT_COLORS[i % CAT_COLORS.length] + '22' }">
                      <div class="h-full" :style="{ width: cat.percent + '%', background: CAT_COLORS[i % CAT_COLORS.length] }" />
                    </div>
                  </div>
                </div>
                <div v-else class="text-[11.5px] text-ink-400">暂无品类数据</div>
              </div>
              <!-- 时段热力 + 支付偏好 -->
              <div>
                <div class="text-[11.5px] text-ink-500 mb-2">下单时段分布</div>
                <div class="grid grid-cols-24 gap-[2px] mb-1.5">
                  <div
                    v-for="cell in hourHeat"
                    :key="cell.h"
                    class="h-2 rounded-sm"
                    :style="{ background: `rgba(79,107,255,${cell.op || 0.05})` }"
                  />
                </div>
                <div class="num flex justify-between text-[10.5px] text-ink-400 mb-[14px]">
                  <span>0</span><span>6</span><span>12</span><span>18</span><span>23</span>
                </div>
                <div class="pt-[14px] border-t border-ink-100">
                  <div class="text-[11.5px] text-ink-500 mb-1.5">支付方式偏好</div>
                  <div v-if="preference?.payments?.length">
                    <div v-for="p in preference.payments" :key="p.pay_type" class="flex items-center gap-2 text-[11.5px] mb-1">
                      <span class="w-[60px]">{{ PAY_LABEL[p.pay_type] || p.pay_type }}</span>
                      <div class="flex-1 h-[5px] rounded-[3px] overflow-hidden" :style="{ background: (PAY_COLORS[p.pay_type] || '#94a3b8') + '22' }">
                        <div class="h-full" :style="{ width: p.percent + '%', background: PAY_COLORS[p.pay_type] || '#94a3b8' }" />
                      </div>
                      <span class="num w-8 text-right">{{ p.percent }}%</span>
                    </div>
                  </div>
                  <div v-else class="text-[11.5px] text-ink-400">暂无支付数据</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 右栏 -->
        <div class="grid gap-[14px] content-start">
          <!-- 等级进度 -->
          <div class="md-card p-4">
            <div class="text-[13px] font-semibold mb-2.5">会员等级</div>
            <div class="flex items-baseline justify-between mb-2.5">
              <span class="text-[18px] font-bold" :style="{ color: tone }">{{ detail.level_name || 'V—' }}</span>
              <span class="num text-[11px] text-ink-400">{{ fmtCount(detail.growth_value) }} / 100,000</span>
            </div>
            <div class="h-2 rounded-[4px] overflow-hidden bg-ink-100 mb-2">
              <div class="h-full" :style="{ width: Math.min((Number(detail.growth_value) || 0) / 1000, 100) + '%', background: 'linear-gradient(90deg,' + tone + ',#8b5cf6)' }" />
            </div>
            <div class="text-[11.5px] text-ink-500">
              距下一等级还差
              <span class="num font-semibold text-ink-900">{{ fmtCount(Math.max(100000 - (Number(detail.growth_value) || 0), 0)) }}</span> 成长值
            </div>
            <div class="mt-3 pt-3 border-t border-ink-100 flex justify-between text-[11.5px] text-ink-500">
              <span>本年累计</span>
              <span class="num text-ink-900 font-medium">+ {{ fmtCount(stats.gmv) }}</span>
            </div>
          </div>

          <!-- 标签云 -->
          <div class="md-card p-4">
            <div class="flex items-center justify-between mb-2.5">
              <div class="text-[13px] font-semibold">用户标签</div>
              <el-button text type="primary" size="small" @click="handleTabClick('tags')">+ 打标</el-button>
            </div>
            <div v-if="tagCloud.length" class="flex flex-wrap gap-1.5">
              <span
                v-for="t in tagCloud"
                :key="t.name"
                class="md-tag"
                :style="{ background: t.color + '22', color: t.color }"
              >{{ t.name }}</span>
            </div>
            <div v-else class="text-[11.5px] text-ink-400">暂无标签</div>
          </div>

          <!-- 关系网络 -->
          <div v-if="hasDistribution" class="md-card p-4">
            <div class="text-[13px] font-semibold mb-2.5">关系网络</div>
            <div class="grid gap-2">
              <div class="flex items-center gap-2 px-2.5 py-2 rounded-[5px] bg-ink-50">
                <div class="md-rel-av" style="background: linear-gradient(135deg,#0ea5e9,#8b5cf6)">{{ detail.inviter_id ? '邀' : '—' }}</div>
                <div class="flex-1 text-[12px]">
                  <div class="font-medium">邀请人 · {{ detail.inviter_id ? '#' + detail.inviter_id : '无' }}</div>
                  <div class="text-[11px] text-ink-400 mt-0.5">{{ detail.inviter_id ? '上级分销员' : '无邀请关系' }}</div>
                </div>
              </div>
              <div v-if="detail.is_distributor === 1" class="flex items-center gap-2 px-2.5 py-2 rounded-[5px] bg-ink-50">
                <div class="md-rel-av" style="background: linear-gradient(135deg,#f43f5e,#8b5cf6)">D</div>
                <div class="flex-1 text-[12px]">
                  <div class="font-medium">本人为分销员 · L{{ detail.distributor_level_id }}</div>
                  <div class="text-[11px] text-ink-400 mt-0.5">邀请码 {{ detail.invite_code || '—' }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- 运营备注 -->
          <div class="md-card p-4" v-loading="remarksLoading">
            <div class="flex items-center justify-between mb-2.5">
              <div class="text-[13px] font-semibold">运营备注</div>
              <el-button v-has-perm="['member.remark']" text type="primary" size="small" @click="addRemark">+ 添加备注</el-button>
            </div>
            <div v-if="remarks.length" class="grid gap-2 text-[12px]">
              <div v-for="r in remarks" :key="r.id" class="px-2.5 py-2 rounded-[5px] bg-ink-50 group">
                <div class="num flex justify-between text-[11px] text-ink-400 mb-1">
                  <span>@{{ r.operator_name || '系统' }}</span>
                  <span class="flex items-center gap-1">
                    {{ String(r.created_at).slice(0, 10) }}
                    <i v-has-perm="['member.remark']" class="i-lucide:trash-2 text-[12px] cursor-pointer hover:text-red-500 opacity-0 group-hover:opacity-100" @click="deleteRemark(r.id)" />
                  </span>
                </div>
                <div class="text-ink-700">{{ r.content }}</div>
              </div>
            </div>
            <div v-else class="text-[11.5px] text-ink-400">暂无备注</div>
          </div>
        </div>
      </div>

      <!-- ============ 基础资料 ============ -->
      <div v-if="activeTab === 'profile' && detail" class="grid gap-[14px]">
        <div class="md-card p-4">
          <div class="md-block-title">账户信息</div>
          <div class="grid grid-cols-2 gap-[14px] text-[12.5px]">
            <div><div class="md-f-l">会员号</div><div class="md-f-v num">{{ detail.id }}</div></div>
            <div><div class="md-f-l">昵称</div><div class="md-f-v">{{ detail.nickname || '—' }}</div></div>
            <div><div class="md-f-l">性别</div><div class="md-f-v">{{ detail.gender === 1 ? '男' : detail.gender === 2 ? '女' : '—' }}</div></div>
            <div><div class="md-f-l">生日</div><div class="md-f-v num">{{ detail.birthday || '—' }}</div></div>
            <div><div class="md-f-l">注册时间</div><div class="md-f-v num">{{ detail.created_at || '—' }}</div></div>
            <div><div class="md-f-l">最近登录</div><div class="md-f-v num">{{ detail.last_login_time || '—' }}</div></div>
            <div><div class="md-f-l">登录次数</div><div class="md-f-v num">{{ fmtCount(detail.login_count) }}</div></div>
            <div><div class="md-f-l">最近登录 IP</div><div class="md-f-v num">{{ detail.last_login_ip || '—' }}</div></div>
          </div>
        </div>
        <div class="md-card p-4">
          <div class="md-block-title">联系方式</div>
          <div class="grid grid-cols-2 gap-[14px] text-[12.5px]">
            <div><div class="md-f-l">手机号</div><div class="md-f-v"><span class="num">{{ detail.mobile || '—' }}</span> <el-tag v-if="detail.mobile" size="small" class="tag-tone-green ml-1" effect="light">已验证</el-tag></div></div>
            <div><div class="md-f-l">邮箱</div><div class="md-f-v">{{ detail.email || '—' }}</div></div>
            <div><div class="md-f-l">微信 OpenID</div><div class="md-f-v">{{ detail.openid ? detail.openid.slice(0, 12) + '…' : '—' }}</div></div>
            <div><div class="md-f-l">UnionID</div><div class="md-f-v">{{ detail.unionid || '—' }}</div></div>
          </div>
        </div>
        <div class="md-card p-4">
          <div class="md-block-title">资产 / 等级</div>
          <div class="grid grid-cols-2 gap-[14px] text-[12.5px]">
            <div><div class="md-f-l">余额</div><div class="md-f-v num">¥ {{ fmtMoney(detail.balance) }}</div></div>
            <div><div class="md-f-l">积分</div><div class="md-f-v num">{{ fmtCount(detail.points) }}</div></div>
            <div><div class="md-f-l">累计积分</div><div class="md-f-v num">{{ fmtCount(detail.total_points) }}</div></div>
            <div><div class="md-f-l">累计消费</div><div class="md-f-v num">¥ {{ fmtMoney(detail.total_consume) }}</div></div>
            <div><div class="md-f-l">订单数</div><div class="md-f-v num">{{ fmtCount(detail.order_count) }}</div></div>
            <div><div class="md-f-l">成长值</div><div class="md-f-v num">{{ fmtCount(detail.growth_value) }}</div></div>
          </div>
        </div>
        <div class="md-card p-4">
          <div class="md-block-title">安全 / 关系</div>
          <div class="grid grid-cols-2 gap-[14px] text-[12.5px]">
            <div><div class="md-f-l">账户状态</div><div class="md-f-v"><el-tag size="small" :class="['tag-tone-' + (detail.status === 1 ? 'green' : 'gray')]" effect="light">{{ detail.status === 1 ? '正常' : '禁用' }}</el-tag></div></div>
            <div v-if="hasDistribution"><div class="md-f-l">是否分销员</div><div class="md-f-v">{{ detail.is_distributor === 1 ? '是' : '否' }}</div></div>
            <div v-if="hasDistribution"><div class="md-f-l">邀请码</div><div class="md-f-v num">{{ detail.invite_code || '—' }}</div></div>
            <div v-if="hasDistribution"><div class="md-f-l">邀请人 ID</div><div class="md-f-v num">{{ detail.inviter_id || '—' }}</div></div>
          </div>
        </div>
      </div>

      <!-- ============ 订单 ============ -->
      <div v-if="activeTab === 'orders'" class="grid gap-[14px]">
        <div class="grid grid-cols-5 gap-[10px]">
          <div v-for="(k, i) in orderKpis" :key="i" class="md-card md-asset-card" :style="{ '--c': k.c }">
            <span class="md-asset-bg" />
            <div class="text-[11.5px] text-ink-500">{{ k.l }}</div>
            <div class="num mt-1.5 text-[18px] font-bold" :style="{ color: k.c }">{{ k.v }}</div>
            <div class="mt-1 text-[11px] text-ink-400">{{ k.s }}</div>
          </div>
        </div>

        <!-- 搜索筛选栏 -->
        <div class="flex items-center gap-2">
          <el-input v-model="orderSearch.keyword" placeholder="搜索订单号" clearable size="small" style="width: 200px" @keyup.enter="handleOrderSearch" />
          <el-select v-model="orderSearch.status" placeholder="全部状态" clearable size="small" style="width: 130px" @change="handleOrderSearch">
            <el-option v-for="s in ORDER_STATUSES" :key="s.v" :label="s.l" :value="s.v" />
          </el-select>
          <el-select v-model="orderSearch.range" placeholder="时间范围" clearable size="small" style="width: 130px" @change="handleOrderSearch">
            <el-option v-for="r in ORDER_RANGES" :key="r.v" :label="r.l" :value="r.v" />
          </el-select>
          <el-button size="small" @click="handleOrderReset">重置</el-button>
          <el-button size="small" type="primary" @click="handleOrderSearch">查询</el-button>
        </div>

        <div class="md-card overflow-hidden">
          <el-table v-loading="ordersLoading" :data="ordersData" size="small">
            <el-table-column label="订单号" min-width="180">
              <template #default="{ row }"><span class="num text-brand">{{ row.order_no }}</span></template>
            </el-table-column>
            <el-table-column label="下单时间" width="200">
              <template #default="{ row }"><span class="num text-secondary">{{ row.created_at }}</span></template>
            </el-table-column>
            <el-table-column label="商品" min-width="200">
              <template #default="{ row }">
                <span v-if="row.items?.length">{{ row.items[0].goods_name }}<span v-if="row.items.length > 1"> 等 {{ row.items.length }} 件</span></span>
                <span v-else class="text-secondary">—</span>
              </template>
            </el-table-column>
            <el-table-column label="金额" width="110" align="right">
              <template #default="{ row }"><span class="num font-semibold">¥ {{ fmtMoney(row.pay_amount) }}</span></template>
            </el-table-column>
            <el-table-column label="状态" width="100">
              <template #default="{ row }">
                <el-tag :class="['tag-tone-' + (ORDER_STATUS_MAP[row.status]?.tone || 'gray')]" size="small" effect="light">{{ ORDER_STATUS_MAP[row.status]?.label || row.status }}</el-tag>
              </template>
            </el-table-column>
          </el-table>
          <el-pagination
            v-model:current-page="ordersPagination.page"
            v-model:page-size="ordersPagination.limit"
            :total="ordersPagination.total"
            layout="total, prev, pager, next"
            small
            class="md-pagi"
            @current-change="fetchOrders"
            @size-change="fetchOrders"
          />
        </div>
      </div>

      <!-- ============ 资产 ============ -->
      <div v-if="activeTab === 'assets' && detail" class="grid gap-[14px]">
        <div class="grid grid-cols-4 gap-[10px]">
          <div v-for="c in assetCards" :key="c.label" class="md-card md-asset-card" :style="{ '--c': c.color }">
            <span class="md-asset-bg" />
            <div class="text-[11.5px] text-ink-500">{{ c.label }}</div>
            <div class="num mt-1.5 text-[18px] font-bold text-ink-900">{{ c.value }}</div>
            <div class="mt-1 text-[11px] text-ink-400">{{ c.sub }}</div>
          </div>
        </div>

        <div class="md-card overflow-hidden">
          <div class="flex border-b border-ink-100 px-[14px]">
            <button v-for="[k, n] in [['balance','余额流水'],['points','积分流水'],['coupon','优惠券']] as Array<['balance'|'points'|'coupon', string]>"
              :key="k"
              :class="['md-sub-tab', assetsTab === k && 'on']"
              @click="handleAssetsSubTab(k)"
            >{{ n }}</button>
            <div class="flex-1" />
            <div class="flex items-center gap-1.5 py-2.5">
              <el-button v-if="assetsTab === 'balance'" v-has-perm="['member.update']" size="small" @click="openAdjust('balance')">充值</el-button>
              <el-button v-if="assetsTab === 'points'"  v-has-perm="['member.update']" size="small" @click="openAdjust('points')">调整积分</el-button>
              <el-button v-if="assetsTab === 'coupon'"  v-has-perm="['member.coupon']" size="small" @click="openIssueCoupon">发券</el-button>
            </div>
          </div>

          <el-table v-if="assetsTab === 'balance'" v-loading="balanceLoading" :data="balanceLogs" size="small">
            <el-table-column label="金额" width="140" align="right">
              <template #default="{ row }">
                <span :class="['num font-semibold', Number(row.amount) >= 0 ? 'amt-up' : 'amt-dn']">{{ Number(row.amount) >= 0 ? '+' : '' }}¥ {{ fmtMoney(Math.abs(Number(row.amount))) }}</span>
              </template>
            </el-table-column>
            <el-table-column label="类型" prop="type_text" width="120" />
            <el-table-column label="来源 / 备注" min-width="200">
              <template #default="{ row }"><span class="text-[12px] text-ink-500">{{ row.remark || row.source || '—' }}</span></template>
            </el-table-column>
            <el-table-column label="时间" prop="created_at" width="200" />
          </el-table>

          <el-table v-if="assetsTab === 'points'" v-loading="pointsLoading" :data="pointsLogs" size="small">
            <el-table-column label="积分" width="140" align="right">
              <template #default="{ row }">
                <span :class="['num font-semibold', Number(row.points) >= 0 ? 'amt-up' : 'amt-dn']">{{ Number(row.points) >= 0 ? '+' : '' }}{{ fmtCount(row.points) }}</span>
              </template>
            </el-table-column>
            <el-table-column label="类型" prop="type_text" width="120" />
            <el-table-column label="来源 / 备注" min-width="200">
              <template #default="{ row }"><span class="text-[12px] text-ink-500">{{ row.remark || row.source || '—' }}</span></template>
            </el-table-column>
            <el-table-column label="时间" prop="created_at" width="200" />
          </el-table>

          <div v-if="assetsTab === 'coupon'" v-loading="couponLoading" class="p-[14px]">
            <div v-if="userCoupons.length" class="grid grid-cols-2 gap-[10px]">
              <div
                v-for="c in userCoupons"
                :key="c.id"
                class="md-coupon"
                :style="{ borderColor: '#f59e0b55', opacity: c.status === 'used' ? 0.55 : 1 }"
              >
                <div class="md-coupon-l" :style="{ background: '#f59e0b14', color: '#f59e0b', borderRight: '1px dashed #f59e0b66' }">
                  <div class="num text-[18px] font-bold">{{ couponDisplayValue(c) }}</div>
                  <div v-if="c.min_amount > 0" class="text-[10.5px] mt-0.5 opacity-80">满 ¥ {{ c.min_amount }} 可用</div>
                </div>
                <div class="flex-1 px-[14px] py-3">
                  <div class="flex items-start justify-between">
                    <div class="text-[12.5px] font-semibold">{{ c.name }}</div>
                    <el-tag size="small" :class="['tag-tone-' + COUPON_STATE[c.status].tone]" effect="light">{{ COUPON_STATE[c.status].label }}</el-tag>
                  </div>
                  <div v-if="c.end_at" class="num mt-1.5 text-[11px] text-ink-400">有效期至 {{ String(c.end_at).slice(0, 10) }}</div>
                  <div v-if="c.used_at" class="num mt-0.5 text-[11px] text-ink-400">使用 {{ String(c.used_at).slice(0, 10) }}</div>
                </div>
              </div>
            </div>
            <el-empty v-else description="暂无优惠券" :image-size="60" />
          </div>

          <el-pagination
            v-if="assetsTab === 'balance'"
            v-model:current-page="balancePagination.page"
            v-model:page-size="balancePagination.limit"
            :total="balancePagination.total"
            layout="total, prev, pager, next"
            small
            class="md-pagi"
            @current-change="fetchBalanceLogs"
            @size-change="fetchBalanceLogs"
          />
          <el-pagination
            v-if="assetsTab === 'points'"
            v-model:current-page="pointsPagination.page"
            v-model:page-size="pointsPagination.limit"
            :total="pointsPagination.total"
            layout="total, prev, pager, next"
            small
            class="md-pagi"
            @current-change="fetchPointsLogs"
            @size-change="fetchPointsLogs"
          />
          <el-pagination
            v-if="assetsTab === 'coupon'"
            v-model:current-page="couponPagination.page"
            v-model:page-size="couponPagination.limit"
            :total="couponPagination.total"
            layout="total, prev, pager, next"
            small
            class="md-pagi"
            @current-change="fetchUserCoupons"
            @size-change="fetchUserCoupons"
          />
        </div>
      </div>

      <!-- ============ 标签 / 生命周期 ============ -->
      <div v-if="activeTab === 'tags'" v-loading="tagsLoading || lifecycleLoading" class="grid gap-[14px]" :style="{ gridTemplateColumns: '1.1fr 1fr' }">
        <div class="grid gap-[14px]">
          <!-- 用户标签（按分组） -->
          <div class="md-card p-4">
            <div class="flex justify-between items-center mb-[14px]">
              <div class="text-[13px] font-semibold">用户标签</div>
            </div>
            <div v-for="g in GROUP_TYPES" :key="g.key" class="mb-3">
              <div class="text-[11.5px] text-ink-500 mb-1.5">{{ g.label }}</div>
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="t in tagsByGroup[g.key]"
                  :key="t.id"
                  class="md-tag clickable"
                  :style="userTagIds.includes(t.id)
                    ? { background: t.color + '22', color: t.color, fontWeight: 500 }
                    : { background: '#fff', borderColor: 'var(--ink-200)', color: 'var(--ink-500)' }"
                  @click="toggleTag(t)"
                >
                  <i v-if="userTagIds.includes(t.id)" class="i-lucide:check text-[10px]" />
                  {{ t.name }}
                  <span v-if="t.auto_update" class="md-tag-kind">规则</span>
                </span>
                <span v-if="!tagsByGroup[g.key].length" class="text-[11px] text-ink-400">该分组暂无标签</span>
              </div>
            </div>
          </div>
          <!-- 所属用户分组（基于已打的标签透出） -->
          <div class="md-card p-4">
            <div class="text-[13px] font-semibold mb-[14px]">所属用户分组</div>
            <div v-if="memberGroups.length" class="grid gap-2">
              <div
                v-for="g in memberGroups"
                :key="g.name"
                class="flex gap-2.5 px-3 py-2.5 rounded-[5px]"
                :style="{ background: g.color + '0a', border: '1px solid ' + g.color + '22' }"
              >
                <div class="w-1 rounded-sm" :style="{ background: g.color }" />
                <div class="flex-1">
                  <div class="text-[12.5px] font-medium" :style="{ color: g.color }">{{ g.name }}</div>
                  <div class="text-[11.5px] text-ink-500 mt-0.5">{{ g.desc }}</div>
                </div>
              </div>
            </div>
            <div v-else class="text-[11.5px] text-ink-400">暂无分组（请先打标签）</div>
          </div>
        </div>

        <!-- 生命周期轨迹 -->
        <div class="md-card p-4">
          <div class="text-[13px] font-semibold mb-[14px]">生命周期轨迹</div>
          <div v-if="lifecycle?.stages?.length" class="md-timeline">
            <div
              v-for="(s, i) in lifecycle.stages"
              :key="i"
              class="relative"
              :class="i === lifecycle!.stages.length - 1 ? 'pb-0' : 'pb-[14px]'"
            >
              <span
                class="md-tl-dot-sm absolute top-1 w-2.5 h-2.5 rounded-full"
                :style="{ background: i === lifecycle!.stages.length - 1 ? tone : 'var(--brand-500)', border: '2px solid #fff', boxShadow: i === lifecycle!.stages.length - 1 ? '0 0 0 2px ' + tone : '0 0 0 1px var(--brand-500)' }"
              />
              <div class="flex gap-2.5 items-center">
                <span class="text-[12.5px] font-semibold" :style="{ color: i === lifecycle!.stages.length - 1 ? tone : 'var(--ink-900)' }">{{ s.key }}</span>
                <span class="num text-[11px] text-ink-400">{{ s.date }}</span>
              </div>
              <div class="text-[11.5px] text-ink-600 mt-[3px]">{{ s.desc }}</div>
            </div>
          </div>
          <div v-else class="text-[11.5px] text-ink-400">暂无生命周期数据</div>

          <div v-if="lifecycle?.next" class="mt-[14px] p-3 rounded-[6px]" :style="{ background: 'linear-gradient(135deg,' + tone + '12,#fff)', border: '1px dashed ' + tone + '55' }">
            <div class="text-[11.5px] font-semibold mb-1" :style="{ color: tone }">下一阶段建议</div>
            <div class="text-[12.5px] font-semibold text-ink-900 mb-0.5">{{ lifecycle.next.title }}</div>
            <div class="text-[11.5px] text-ink-700 leading-[1.6]">{{ lifecycle.next.desc }}</div>
          </div>
        </div>
      </div>

      <!-- ============ 地址簿 ============ -->
      <div v-if="activeTab === 'address'" v-loading="addressLoading" class="grid gap-[14px]">
        <div class="md-card p-4">
          <div class="flex justify-between items-center mb-[14px]">
            <div class="text-[13px] font-semibold">常用地址 <span class="text-[11px] text-ink-400 ml-1.5 font-normal">共 {{ userAddresses.length }} 条</span></div>
            <el-button v-has-perm="['member.address.update']" size="small" @click="openCreateAddress">+ 新增地址</el-button>
          </div>
          <div v-if="userAddresses.length" class="grid grid-cols-2 gap-[10px]">
            <div
              v-for="a in userAddresses"
              :key="a.id"
              class="md-addr"
              :class="{ 'md-addr-default': a.is_default }"
            >
              <span v-if="a.is_default" class="md-addr-badge">默认</span>
              <div class="flex items-baseline gap-2">
                <div class="text-[13px] font-semibold">{{ a.name }}</div>
                <div class="num text-[12px] text-ink-500">{{ a.phone }}</div>
              </div>
              <div class="text-[12px] text-ink-700 mt-2 leading-[1.5]">{{ [a.province, a.city, a.district].filter(Boolean).join(' · ') }}</div>
              <div class="text-[12px] text-ink-500 mt-0.5 leading-[1.5]">{{ a.detail }}</div>
              <div class="md-addr-actions">
                <el-button v-if="!a.is_default" v-has-perm="['member.address.update']" text size="small" @click="setDefaultAddress(a)">设为默认</el-button>
                <el-button v-has-perm="['member.address.update']" text size="small" @click="openEditAddress(a)">编辑</el-button>
                <el-button v-has-perm="['member.address.delete']" text type="danger" size="small" @click="deleteAddress(a)">删除</el-button>
              </div>
            </div>
          </div>
          <el-empty v-else description="暂无收货地址" :image-size="60" />
        </div>

        <!-- 收货地区分布（真实接口） -->
        <div class="md-card p-4">
          <div class="text-[13px] font-semibold mb-2.5">收货地区分布</div>
          <div v-if="preference?.districts?.length">
            <div v-for="(d, i) in preference.districts" :key="d.name" class="mb-2">
              <div class="flex justify-between text-[12px] mb-1">
                <span>{{ d.name }}</span>
                <span class="num text-ink-500">{{ d.count }} 条 · {{ d.percent }}%</span>
              </div>
              <div class="h-[6px] rounded-[3px] overflow-hidden" :style="{ background: CAT_COLORS[i % CAT_COLORS.length] + '22' }">
                <div class="h-full" :style="{ width: d.percent + '%', background: CAT_COLORS[i % CAT_COLORS.length] }" />
              </div>
            </div>
          </div>
          <div v-else class="text-[11.5px] text-ink-400">暂无收货数据</div>
        </div>
      </div>

      <!-- ============ 操作日志 ============ -->
      <div v-if="activeTab === 'log'" v-loading="logLoading" class="grid gap-[14px]" :style="{ gridTemplateColumns: '180px 1fr' }">
        <div class="md-card p-2.5 self-start">
          <div
            v-for="cat in logCats"
            :key="cat.k"
            :class="['md-log-cat', activeLogCat === cat.k && 'on']"
            @click="switchLogCat(cat.k)"
          >
            <span>{{ cat.n }}</span>
            <span class="num text-[11px] opacity-80">{{ cat.c }}</span>
          </div>
        </div>
        <div class="md-card p-4">
          <div class="flex justify-between items-center mb-[14px]">
            <div class="text-[13px] font-semibold">操作日志</div>
          </div>
          <div v-if="logEntries.length" class="md-timeline">
            <div v-for="l in logEntries" :key="l.id" class="relative pb-[14px]">
              <div
                class="md-tl-dot-lg absolute top-0 w-6 h-6 rounded-full flex items-center justify-center"
                :style="{ background: logTone(l) + '22', border: '2px solid #fff', boxShadow: '0 0 0 1px ' + logTone(l) + '55', color: logTone(l) }"
              ><i :class="logIcon(l)" class="text-[12px]" /></div>
              <div class="flex gap-2.5 items-center">
                <span class="text-[12.5px] font-semibold text-ink-900">{{ l.title }}</span>
                <span class="md-log-cat-pill" :style="{ background: logTone(l) + '22', color: logTone(l) }">{{ logCatLabel(l.category) }}</span>
                <span class="num text-[11px] text-ink-400 ml-auto">{{ l.created_at }}</span>
              </div>
              <div v-if="l.description" class="text-[11.5px] text-ink-600 mt-[3px]">{{ l.description }}</div>
            </div>
          </div>
          <el-empty v-else description="暂无日志" :image-size="60" />

          <el-pagination
            v-if="logEntries.length"
            v-model:current-page="logPagination.page"
            :total="logPagination.total"
            :page-size="logPagination.limit"
            layout="total, prev, pager, next"
            small
            class="md-pagi"
            @current-change="fetchLogs"
          />
        </div>
      </div>
      </div>
    </div>

    <!-- 调资产弹窗 -->
    <el-dialog
      v-model="adjustVisible"
      :title="adjustType === 'balance' ? '调整余额' : '调整积分'"
      width="440px"
      append-to-body
    >
      <el-form label-width="80px">
        <el-form-item :label="adjustType === 'balance' ? '金额' : '积分'">
          <el-input-number v-if="adjustType === 'balance'" v-model="adjustForm.amount" :precision="2" :step="10" controls-position="right" style="width: 100%" />
          <el-input-number v-else v-model="adjustForm.points" :step="100" controls-position="right" style="width: 100%" />
          <div class="text-[11.5px] text-ink-500 mt-1">正数为增加，负数为扣减</div>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="adjustForm.remark" type="textarea" :rows="2" placeholder="选填" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="adjustVisible = false">取消</el-button>
        <el-button type="primary" :loading="adjustSubmitting" @click="submitAdjust">确定</el-button>
      </template>
    </el-dialog>

    <SendSmsDialog v-model="smsVisible" :user-id="userId" :mobile="detail?.mobile" />
    <IssueCouponDialog v-model="couponDialogVisible" :user-id="userId" @issued="onCouponIssued" />
    <EditProfileDialog v-model="profileDialogVisible" :user-id="userId" :initial="detail" @saved="onProfileSaved" />
    <AddressFormDialog v-model="addressDialogVisible" :user-id="userId" :initial="editingAddress" @saved="fetchAddresses" />
  </el-dialog>
</template>

<style lang="scss" scoped>
// ─── 弹窗体本身：灰底 + 18px padding（design 1:1） ───
// 注：el-dialog__body 的默认 padding 已通过下方非 scoped 样式置 0
.md-body {
  display: flex;
  flex-direction: column;
  background: var(--ink-50, #f4f6f9);
  padding: 18px;
  // 固定高度，切换 tab 不抖动；预留上下边距 + 头部
  height: calc(100vh - 160px);
  max-height: calc(100vh - 160px);
  overflow: hidden;
  box-sizing: border-box;
}

.md-panel {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
}

// ─── 自定义关闭按钮 ───
.md-close {
  width: 30px;
  height: 30px;
  margin-left: 6px;
  border-radius: 4px;
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 20px;
  line-height: 1;
  color: var(--ink-500);
  &:hover { background: var(--ink-100); color: var(--ink-700); }
}

.md-profile {
  flex-shrink: 0;
  display: flex;
  gap: 16px;
  padding: 18px 22px;
  border-radius: 10px;
  margin-bottom: 14px;
  background: linear-gradient(135deg, rgba(79, 107, 255, 0.08), #fff 50%);
  border: 1px solid rgba(79, 107, 255, 0.2);
}
.md-avatar {
  width: 64px;
  height: 64px;
  border-radius: 32px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-weight: 600;
  font-size: 24px;
  overflow: hidden;
}
.md-profile-kpis {
  display: grid;
  grid-template-columns: repeat(4, auto);
  gap: 24px;
  align-items: center;
  padding-left: 14px;
  border-left: 1px dashed var(--ink-200);
  flex-shrink: 0;
}
.md-pill {
  padding: 3px 10px;
  border-radius: 10px;
  font-size: 11.5px;
  font-weight: 500;
}

.md-tabs {
  flex-shrink: 0;
  display: flex;
  background: #fff;
  border-radius: 6px;
  padding: 0 4px;
  margin-bottom: 14px;
  box-shadow: 0 1px 0 var(--ink-100);
}
.md-tab {
  padding: 12px 18px;
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 13px;
  color: var(--ink-600);
  border-bottom: 2px solid transparent;
  transition: color .15s;

  &:hover { color: var(--brand-500); }
  &.on {
    font-weight: 600;
    color: var(--brand-500);
    border-bottom-color: var(--brand-500);
  }
}

.md-card {
  background: #fff;
  border: 1px solid var(--ink-100);
  border-radius: 6px;
}

.md-asset-card {
  position: relative;
  padding: 14px 16px;
  overflow: hidden;
}
.md-asset-bg {
  position: absolute;
  right: -12px;
  top: -12px;
  width: 48px;
  height: 48px;
  border-radius: 24px;
  background: var(--c, #4f6bff);
  opacity: 0.12;
  pointer-events: none;
}

.md-trend-bars {
  display: flex;
  align-items: flex-end;
  gap: 2px;
}
.md-trend-col {
  flex: 1;
  border-radius: 2px 2px 0 0;
}

.md-sub-tab {
  padding: 12px 16px;
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 13px;
  color: var(--ink-600);
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;

  &:hover { color: var(--brand-500); }
  &.on {
    font-weight: 600;
    color: var(--brand-500);
    border-bottom-color: var(--brand-500);
  }
}

.md-coupon {
  display: flex;
  border: 1px solid;
  border-radius: 6px;
  overflow: hidden;
}
.md-coupon-l {
  width: 120px;
  padding: 14px 10px;
  text-align: center;
}

.md-tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11.5px;
  border: 1px solid transparent;

  &.clickable {
    cursor: pointer;
    transition: transform .15s;
    &:hover { transform: translateY(-1px); }
  }

  .md-tag-kind {
    font-size: 9px;
    opacity: 0.7;
    padding: 1px 4px;
    background: rgba(255, 255, 255, 0.5);
    border-radius: 3px;
    font-family: var(--font-num);
  }
}

.md-rel-av {
  width: 28px;
  height: 28px;
  border-radius: 14px;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
  flex-shrink: 0;
}

.md-block-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--ink-900);
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  gap: 6px;

  &::before {
    content: '';
    width: 3px;
    height: 12px;
    border-radius: 2px;
    background: var(--brand-500);
  }
}
.md-f-l { font-size: 11.5px; color: var(--ink-500); margin-bottom: 4px; }
.md-f-v { color: var(--ink-900); }

.md-addr {
  position: relative;
  padding: 14px;
  border: 1px solid var(--ink-200);
  border-radius: 6px;
  background: #fff;

  &.md-addr-default {
    border-color: var(--brand-500);
    background: var(--brand-50, #f0f4ff);
  }
}
.md-addr-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 2px 8px;
  border-radius: 9px;
  font-size: 10px;
  background: var(--brand-500);
  color: #fff;
  font-weight: 600;
}
.md-addr-actions {
  display: flex;
  gap: 4px;
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px dashed var(--ink-200, #dde0e6);
}

// 时间线（生命周期 / 操作日志）—— 贯穿线 + dot 中心对齐
.md-timeline {
  position: relative;
  padding-left: 28px;

  &::before {
    content: '';
    position: absolute;
    left: 11px;
    top: 4px;
    bottom: 4px;
    width: 2px;
    background: var(--ink-100, #f1f5f9);
    border-radius: 1px;
  }

  .md-tl-dot-sm { left: -21px !important; }
  .md-tl-dot-lg { left: -28px !important; }
}

.md-log-cat {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 10px;
  border-radius: 5px;
  cursor: pointer;
  font-size: 12.5px;
  color: var(--ink-700);

  &:hover { background: var(--ink-50); }
  &.on {
    background: var(--brand-50, #f0f4ff);
    color: var(--brand-500);
    font-weight: 600;
  }
}
.md-log-cat-pill {
  padding: 1px 7px;
  border-radius: 8px;
  font-size: 10.5px;
  font-weight: 500;
}

.md-pagi {
  margin-top: 12px;
  display: flex;
  justify-content: flex-end;
  padding: 10px 14px;
}
.amt-up { color: var(--success, #10b981); }
.amt-dn { color: var(--rose-500, #f43f5e); }
.text-secondary { color: var(--ink-500); }
.text-brand { color: var(--brand-500); }
</style>

<style lang="scss">
.member-detail-dialog {
  max-width: calc(100vw - 48px);

  .el-dialog__header {
    display: block !important;
    padding: 12px 20px;
    margin: 0;
    border-bottom: 1px solid var(--ink-100);
    background: #fff;
  }
  .el-dialog__body {
    padding: 0;
  }
}
</style>
