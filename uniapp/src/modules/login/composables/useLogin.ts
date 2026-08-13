import { ref } from 'vue'
import { useUserStore } from '@/store/user.store'
import { authApi } from '@/api/auth'
import { userApi } from '@/api/user'
import { getToken } from '@/utils/auth'
import { isMobile, isPassword, isVerifyCode } from '@/utils/validate'
import { goAfterLogin } from '@/utils/login-redirect'
import { maskMobile } from '@/components/d-wechat-auth-popup/helpers'

export function useLogin() {
  const userStore = useUserStore()
  const loading = ref(false)
  const loginType = ref<'password' | 'sms'>('password')
  const countdown = ref(0)
  const wechatQuickLoading = ref(false)
  const tempToken = ref('')

  const showAuthPopup = ref(false)
  const authPopupShowPhone = ref(false)
  const authPhoneDisplay = ref('')
  const authSubmitting = ref(false)
  const authPhoneCodeInFlight = ref(false)
  const profileDefaults = ref<{ nickname: string; avatar: string }>({ nickname: '', avatar: '' })

  async function loginByPassword(mobile: string, password: string) {
    if (!isMobile(mobile)) {
      uni.showToast({ title: '请输入正确的手机号', icon: 'none' })
      return
    }
    if (!isPassword(password)) {
      uni.showToast({ title: '密码长度6-20位', icon: 'none' })
      return
    }

    loading.value = true
    try {
      await userStore.login({ mobile, password })
      goAfterLogin()
    } finally {
      loading.value = false
    }
  }

  async function loginBySms(mobile: string, code: string) {
    if (!isMobile(mobile)) {
      uni.showToast({ title: '请输入正确的手机号', icon: 'none' })
      return
    }
    if (!isVerifyCode(code)) {
      uni.showToast({ title: '请输入正确的验证码', icon: 'none' })
      return
    }

    loading.value = true
    try {
      await userStore.smsLogin({ mobile, code })
      goAfterLogin()
    } finally {
      loading.value = false
    }
  }

  async function sendCode(mobile: string) {
    if (!isMobile(mobile)) {
      uni.showToast({ title: '请输入正确的手机号', icon: 'none' })
      return
    }
    if (countdown.value > 0) return

    await authApi.sendSmsCode({ mobile })
    uni.showToast({ title: '验证码已发送', icon: 'none' })
    countdown.value = 60
    const timer = setInterval(() => {
      countdown.value--
      if (countdown.value <= 0) clearInterval(timer)
    }, 1000)
  }

  async function loginByWechatQuick() {
    wechatQuickLoading.value = true
    try {
      const loginRes = await new Promise<UniApp.LoginRes>((resolve, reject) => {
        uni.login({ provider: 'weixin', success: resolve, fail: reject })
      })

      const result = await authApi.wechatQuickLogin({ code: loginRes.code })

      if (result.status === 'logged_in' && result.token) {
        await userStore.setSession(result.token, result.user_info)
        if (result.need_profile) {
          profileDefaults.value = {
            nickname: result.user_info?.nickname ?? '',
            avatar: result.user_info?.avatar ?? '',
          }
          authPopupShowPhone.value = false
          authPhoneDisplay.value = ''
          showAuthPopup.value = true
        } else {
          goAfterLogin()
        }
      } else if (result.status === 'need_bindphone') {
        tempToken.value = result.temp_token ?? ''
        profileDefaults.value = { nickname: '', avatar: '' }
        authPopupShowPhone.value = true
        authPhoneDisplay.value = ''
        showAuthPopup.value = true
      }
    } catch (e: any) {
      uni.showToast({ title: e.message || '登录失败', icon: 'none' })
    } finally {
      wechatQuickLoading.value = false
    }
  }

  /** 弹窗手机号授权：temp_token 换正式 token（不跳转），回显脱敏手机号 */
  async function handleAuthPhoneCode(code: string) {
    if (authPhoneCodeInFlight.value) return
    authPhoneCodeInFlight.value = true
    try {
      const result = await authApi.wechatBindPhone({
        temp_token: tempToken.value,
        phone_code: code,
      })
      if (result.token) {
        await userStore.setSession(result.token, result.user_info)
        authPhoneDisplay.value = maskMobile(result.mobile ?? '')
      }
    } catch (e: any) {
      uni.showToast({ title: e.message || '手机号绑定失败', icon: 'none' })
    } finally {
      authPhoneCodeInFlight.value = false
    }
  }

  /** 弹窗保存：完善资料成功才进首页；失败保持弹窗可重试 */
  async function handleAuthSubmit(payload: { nickname: string; avatar?: string }) {
    authSubmitting.value = true
    try {
      await userApi.updateProfile(payload)
      await userStore.getUserInfo().catch(() => {})
      showAuthPopup.value = false
      goAfterLogin()
    } catch {
      // 拦截器已 toast；弹窗保持，允许重试或关闭放弃
    } finally {
      authSubmitting.value = false
    }
  }

  /** 用户主动关闭=放弃登录：有 token 则 logout，无 token 不强行 logout（避免 401 跳转） */
  function handleAuthClose() {
    showAuthPopup.value = false
    tempToken.value = ''
    if (getToken()) {
      userStore.logout({ redirect: false })
    }
  }

  return {
    loading, loginType, countdown, loginByPassword, loginBySms, sendCode,
    wechatQuickLoading, loginByWechatQuick,
    showAuthPopup, authPopupShowPhone, authPhoneDisplay, authSubmitting, profileDefaults,
    handleAuthPhoneCode, handleAuthSubmit, handleAuthClose,
  }
}
