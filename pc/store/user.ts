import { computed, ref } from 'vue'
import { defineStore, skipHydrate } from 'pinia'
import { authApi, type LoginResult, type UserInfo } from '~/api/auth'
import {
  getCachedUser,
  getToken,
  removeCachedUser,
  removeToken,
  setCachedUser,
  setToken,
} from '~/composables/useRequest'

function pickAuthPayload(payload: unknown): { token: string; user_info?: UserInfo } | null {
  if (!payload || typeof payload !== 'object') return null
  const rec = payload as Record<string, unknown>
  if (typeof rec.token === 'string' && rec.token) {
    return rec as { token: string; user_info?: UserInfo }
  }
  if (rec.data && typeof rec.data === 'object') {
    return pickAuthPayload(rec.data)
  }
  return null
}

export const useUserStore = defineStore('user', () => {
  // skipHydrate：避免 Nuxt/Pinia 用空 payload 把 localStorage 恢复的登录态冲掉
  const token = skipHydrate(ref(getToken() || ''))
  const userInfo = skipHydrate(ref<UserInfo | null>(getCachedUser<UserInfo>()))

  const isLoggedIn = computed(() => !!token.value)

  function applyAuth(payload: LoginResult | unknown) {
    const data = pickAuthPayload(payload)
    if (!data) return
    token.value = data.token
    setToken(data.token)
    if (data.user_info) {
      userInfo.value = data.user_info
      setCachedUser(data.user_info)
    }
  }

  function restoreSession(nextToken?: string) {
    const t = nextToken || getToken() || ''
    if (!t) {
      clearSession()
      return
    }
    token.value = t
    setToken(t)
    if (!userInfo.value) {
      userInfo.value = getCachedUser<UserInfo>()
    }
    if (!userInfo.value) {
      void fetchUserInfo()
    }
  }

  function syncFromStorage() {
    const t = getToken()
    if (!t) return
    if (token.value !== t) {
      restoreSession(t)
      return
    }
    if (!userInfo.value) {
      userInfo.value = getCachedUser<UserInfo>()
      if (!userInfo.value) {
        void fetchUserInfo()
      }
    }
  }

  function clearSession() {
    token.value = ''
    userInfo.value = null
    removeToken()
    removeCachedUser()
  }

  async function login(data: { account: string; password: string }) {
    const res = await authApi.login(data)
    if (res.code === 200) {
      applyAuth(res.data ?? res)
    }
    return res
  }

  async function smsLogin(data: { mobile: string; code: string }) {
    const res = await authApi.smsLogin(data)
    if (res.code === 200) {
      applyAuth(res.data ?? res)
    }
    return res
  }

  let fetchingInfo = false
  async function fetchUserInfo() {
    if (fetchingInfo) return
    if (!token.value && !getToken()) return
    fetchingInfo = true
    try {
      const res = await authApi.getUserInfo()
      if (res.code === 200) {
        userInfo.value = res.data
        setCachedUser(res.data)
      }
      return res
    } finally {
      fetchingInfo = false
    }
  }

  async function logout() {
    try {
      await authApi.logout()
    } finally {
      clearSession()
    }
  }

  return {
    token,
    userInfo,
    isLoggedIn,
    applyAuth,
    restoreSession,
    syncFromStorage,
    clearSession,
    login,
    smsLogin,
    fetchUserInfo,
    logout,
  }
})
