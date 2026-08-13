import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { getToken, setToken, removeToken } from '@/utils/auth'
import { redirectToLogin } from '@/utils/login-redirect'
import { authApi } from '@/api/auth'
import type { UserInfo, LoginResult } from '@/types/api'

export const useUserStore = defineStore('user', () => {
  const token = ref(getToken())
  const userInfo = ref<UserInfo | null>(null)

  const isLoggedIn = computed(() => !!token.value)
  const nickname = computed(() => userInfo.value?.nickname || '')
  const avatar = computed(() => userInfo.value?.avatar || '')

  async function login(params: { mobile: string; password: string }): Promise<LoginResult> {
    const result = await authApi.login(params)
    token.value = result.token
    userInfo.value = result.user
    setToken(result.token)
    await afterLogin()
    return result
  }

  async function smsLogin(params: { mobile: string; code: string }): Promise<LoginResult> {
    const result = await authApi.smsLogin(params)
    token.value = result.token
    userInfo.value = result.user
    setToken(result.token)
    await afterLogin()
    return result
  }

  /** 登录成功后的通用钩子 */
  async function afterLogin() {
    // H5 微信浏览器：绑定 oa_openid 到当前用户，并恢复微信自动登录
    // #ifdef H5
    import('@/utils/wechat-oauth').then(({ bindOaOpenidAfterLogin, suppressOaAutoLogin }) => {
      suppressOaAutoLogin(false)
      bindOaOpenidAfterLogin()
    }).catch(() => {})
    // #endif
  }

  async function getUserInfo(): Promise<UserInfo> {
    const result = await authApi.getUserInfo()
    userInfo.value = result
    return result
  }

  async function setSession(newToken: string, user?: UserInfo | null) {
    token.value = newToken
    setToken(newToken)
    if (user) {
      userInfo.value = user
    } else {
      try {
        await getUserInfo()
      } catch {
        userInfo.value = null
      }
    }
    await afterLogin()
  }

  function logout(options?: { redirect?: boolean }) {
    authApi.logout().catch(() => {})
    token.value = ''
    userInfo.value = null
    removeToken()
    // H5 微信浏览器：主动退出后不要被微信授权立刻自动登录回来
    // #ifdef H5
    import('@/utils/wechat-oauth').then(({ suppressOaAutoLogin }) => {
      suppressOaAutoLogin(true)
    }).catch(() => {})
    // #endif
    if (options?.redirect !== false) {
      redirectToLogin('', true)
    }
  }

  return { token, userInfo, isLoggedIn, nickname, avatar, login, smsLogin, getUserInfo, setSession, logout }
})
