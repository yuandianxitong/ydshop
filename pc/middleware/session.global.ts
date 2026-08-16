import { getToken } from '~/composables/useRequest'
import { useUserStore } from '~/store/user'

/**
 * 每次路由切换都把 localStorage 里的会话同步回 Pinia。
 * 登录页是 blank layout，跳转首页会挂上默认布局；若 Pinia 被空状态重水合，这里赶在渲染前补回去。
 */
export default defineNuxtRouteMiddleware(() => {
  if (import.meta.server) return
  if (!getToken()) return
  useUserStore().syncFromStorage()
})
