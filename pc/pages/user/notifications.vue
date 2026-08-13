<template>
  <div class="notification-page">
    <div class="page-heading">
      <div>
        <div class="eyebrow">MESSAGE CENTER</div>
        <h2>消息通知</h2>
        <p>订单、支付、反馈与系统动态集中在这里。</p>
      </div>
      <button
        class="read-all-btn"
        :disabled="unreadCount === 0 || markingAll"
        @click="markAllRead"
      >
        <span class="i-carbon-checkmark-outline" />
        {{ markingAll ? '处理中...' : `全部已读${unreadCount ? ` · ${unreadCount}` : ''}` }}
      </button>
    </div>

    <div v-if="loading" class="message-list">
      <div v-for="i in 4" :key="i" class="message-skeleton" />
    </div>

    <div v-else-if="!messages.length" class="empty-state">
      <span class="i-carbon-notification-off" />
      <strong>暂时没有新消息</strong>
      <p>订单状态、退款结果和平台通知会第一时间出现在这里。</p>
    </div>

    <div v-else class="message-list">
      <article
        v-for="item in messages"
        :key="item.id"
        class="message-card"
        :class="{ unread: !isRead(item), expanded: expandedIds.has(item.id) }"
      >
        <button class="message-main" @click="toggleMessage(item)">
          <span class="message-icon" :class="typeMeta(item.type).tone">
            <i :class="typeMeta(item.type).icon" />
          </span>
          <span class="message-copy">
            <span class="message-meta">
              <span>{{ item.type_text || typeMeta(item.type).label }}</span>
              <time>{{ formatTime(item.created_at) }}</time>
            </span>
            <span class="message-title">
              <i v-if="!isRead(item)" class="unread-dot" />
              {{ item.title }}
            </span>
            <span class="message-preview">{{ item.content || '暂无详细内容' }}</span>
          </span>
          <span class="expand-icon" :class="expandedIds.has(item.id) ? 'i-carbon-chevron-up' : 'i-carbon-chevron-down'" />
        </button>

        <div v-if="expandedIds.has(item.id)" class="message-detail">
          <p>{{ item.content || '暂无详细内容' }}</p>
          <NuxtLink v-if="actionPath(item)" :to="actionPath(item)!" class="message-action">
            查看相关内容 <span class="i-carbon-arrow-right" />
          </NuxtLink>
        </div>
      </article>
    </div>

    <div v-if="totalPages > 1" class="pagination">
      <button :disabled="page <= 1" @click="goPage(page - 1)">上一页</button>
      <span>{{ page }} / {{ totalPages }}</span>
      <button :disabled="page >= totalPages" @click="goPage(page + 1)">下一页</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useMessage } from 'naive-ui'
import { notificationApi, type UserNotificationItem } from '~/api/notification'

const toast = useMessage()
const messages = ref<UserNotificationItem[]>([])
const loading = ref(true)
const markingAll = ref(false)
const unreadCount = ref(0)
const page = ref(1)
const pageSize = 10
const total = ref(0)
const expandedIds = ref(new Set<number>())

const totalPages = computed(() => Math.max(1, Math.ceil(total.value / pageSize)))

const typeMeta = (type: string) => ({
  order: { label: '订单消息', icon: 'i-carbon-shopping-bag', tone: 'tone-blue' },
  payment: { label: '支付消息', icon: 'i-carbon-wallet', tone: 'tone-green' },
  feedback: { label: '反馈消息', icon: 'i-carbon-chat', tone: 'tone-amber' },
  system: { label: '系统通知', icon: 'i-carbon-notification', tone: 'tone-gray' },
}[type] || { label: '消息通知', icon: 'i-carbon-notification', tone: 'tone-gray' })

const isRead = (item: UserNotificationItem) => Boolean(item.is_read)

const formatTime = (value: string) => {
  if (!value) return '—'
  return value.replace('T', ' ').slice(0, 16)
}

const actionPath = (item: UserNotificationItem): string | null => {
  const raw = item.extra?.pc_path || item.extra?.path || item.extra?.url
  if (typeof raw === 'string' && raw.startsWith('/') && !raw.startsWith('//')) return raw
  if (item.type === 'order' && Number(item.biz_id || 0) > 0) return `/order/${item.biz_id}`
  if (item.type === 'feedback') return '/user/feedback'
  return null
}

const fetchMessages = async () => {
  loading.value = true
  try {
    const [listRes, countRes] = await Promise.all([
      notificationApi.getList({ page_no: page.value, page_size: pageSize }),
      notificationApi.getUnreadCount(),
    ])
    if (listRes.code === 200) {
      messages.value = listRes.data.list || []
      total.value = Number(listRes.data.pagination?.total || 0)
    }
    if (countRes.code === 200) unreadCount.value = Number(countRes.data.count || 0)
  } finally {
    loading.value = false
  }
}

const toggleMessage = async (item: UserNotificationItem) => {
  const next = new Set(expandedIds.value)
  if (next.has(item.id)) next.delete(item.id)
  else next.add(item.id)
  expandedIds.value = next

  if (!isRead(item)) {
    const res = await notificationApi.markRead([item.id])
    if (res.code === 200) {
      item.is_read = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  }
}

const markAllRead = async () => {
  markingAll.value = true
  try {
    const res = await notificationApi.markRead()
    if (res.code === 200) {
      messages.value.forEach(item => { item.is_read = true })
      unreadCount.value = 0
      toast.success('全部消息已标记为已读')
    }
  } finally {
    markingAll.value = false
  }
}

const goPage = (target: number) => {
  if (target < 1 || target > totalPages.value || target === page.value) return
  page.value = target
  expandedIds.value = new Set()
  fetchMessages()
}

onMounted(fetchMessages)
</script>

<style scoped>
.notification-page { color: #182230; }
.page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; margin-bottom: 22px; }
.eyebrow { margin-bottom: 5px; color: var(--color-primary); font-size: 10px; font-weight: 800; letter-spacing: .18em; }
.page-heading h2 { margin: 0; font-size: 24px; font-weight: 750; letter-spacing: -.02em; }
.page-heading p { margin: 6px 0 0; color: #87909f; font-size: 13px; }
.read-all-btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 13px; color: var(--color-primary); font-size: 13px; font-weight: 600; background: color-mix(in srgb, var(--color-primary) 8%, white); border: 1px solid color-mix(in srgb, var(--color-primary) 24%, white); border-radius: 7px; }
.read-all-btn:disabled { color: #aeb5c0; background: #f7f8fa; border-color: #e9ebef; cursor: not-allowed; }
.message-list { display: flex; flex-direction: column; gap: 10px; }
.message-card { overflow: hidden; background: #fff; border: 1px solid #e8ebf0; border-radius: 10px; transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease; }
.message-card:hover { border-color: #d8dde6; box-shadow: 0 8px 22px rgba(20, 31, 48, .06); transform: translateY(-1px); }
.message-card.unread { border-left: 3px solid var(--color-primary); }
.message-main { display: grid; grid-template-columns: 42px minmax(0, 1fr) 18px; gap: 14px; align-items: center; width: 100%; padding: 16px 18px; text-align: left; }
.message-icon { display: grid; place-items: center; width: 42px; height: 42px; font-size: 19px; border-radius: 10px; }
.tone-blue { color: #2563eb; background: #eff6ff; }.tone-green { color: #0f9f6e; background: #ecfdf5; }.tone-amber { color: #d97706; background: #fff7ed; }.tone-gray { color: #64748b; background: #f1f5f9; }
.message-copy { display: block; min-width: 0; }
.message-meta { display: flex; align-items: center; justify-content: space-between; gap: 12px; color: #9aa2ae; font-size: 11px; }
.message-title { display: flex; align-items: center; gap: 7px; margin-top: 3px; color: #253044; font-size: 14px; font-weight: 650; }
.unread-dot { flex: 0 0 auto; width: 6px; height: 6px; background: var(--color-primary); border-radius: 50%; box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 13%, transparent); }
.message-preview { display: block; overflow: hidden; margin-top: 4px; color: #7c8695; font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }
.expand-icon { color: #a0a8b4; }
.message-detail { padding: 0 18px 17px 74px; border-top: 1px solid #f0f2f5; }
.message-detail p { margin: 14px 0 0; color: #586273; font-size: 13px; line-height: 1.75; white-space: pre-wrap; }
.message-action { display: inline-flex; align-items: center; gap: 5px; margin-top: 12px; color: var(--color-primary); font-size: 12px; font-weight: 650; }
.message-skeleton { height: 76px; background: linear-gradient(90deg, #f5f6f8 25%, #fafbfc 50%, #f5f6f8 75%); background-size: 200% 100%; border-radius: 10px; animation: shimmer 1.35s infinite; }
.empty-state { display: flex; flex-direction: column; align-items: center; padding: 70px 20px; color: #9aa3af; background: #fff; border: 1px dashed #dfe3e9; border-radius: 12px; }
.empty-state > span { margin-bottom: 13px; font-size: 44px; color: #c5cbd4; }.empty-state strong { color: #626d7c; font-size: 15px; }.empty-state p { margin-top: 7px; font-size: 12px; }
.pagination { display: flex; align-items: center; justify-content: center; gap: 14px; margin-top: 20px; color: #8b94a2; font-size: 12px; }.pagination button { padding: 7px 12px; color: #536071; background: #fff; border: 1px solid #e0e4ea; border-radius: 6px; }.pagination button:disabled { opacity: .42; cursor: not-allowed; }
@keyframes shimmer { to { background-position: -200% 0; } }
</style>
