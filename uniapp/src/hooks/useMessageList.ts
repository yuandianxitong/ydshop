import { messageApi, type NotificationInfo } from '@/api/message'
import { usePaging } from '@/hooks/usePaging'
import { formatRelativeTime } from '@/utils/time'

/**
 * 消息列表共享逻辑。详情页只传数字 ID，再从后端按当前用户权限读取，
 * 避免把长消息 JSON 塞进小程序路由参数。
 */
export function useMessageList() {
  const { list, loading, finished, refreshing, total, getList, refresh } = usePaging<NotificationInfo>({
    fetchFun: (params) => messageApi.getList(params),
  })

  /** 将时间戳格式化为相对时间（复用全局工具） */
  const formatTime = formatRelativeTime

  /**
   * 点击消息：静默标记单条已读并跳转详情
   *
   * URL 只传 id，详情页通过受权限保护的 detail API 获取正文。
   */
  function handleTap(item: NotificationInfo) {
    if (!item.is_read) {
      messageApi.markAsRead([item.id]).then(() => {
        item.is_read = true
      }).catch(() => {})
    }
    uni.navigateTo({
      url: `/modules/message/pages/message-detail?id=${item.id}`,
    })
  }

  /** 全部已读 */
  async function handleReadAll() {
    if (total.value === 0) return
    try {
      await messageApi.markAsRead()
      uni.showToast({ title: '全部已读', icon: 'success' })
      list.value.forEach((item) => {
        item.is_read = true
      })
    } catch {
      // error handled by request interceptor
    }
  }

  return {
    list,
    loading,
    finished,
    refreshing,
    total,
    getList,
    refresh,
    formatTime,
    handleTap,
    handleReadAll,
  }
}
