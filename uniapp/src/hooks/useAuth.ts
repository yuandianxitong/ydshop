import { useUserStore } from '@/store/user.store'
import { redirectToLogin } from '@/utils/login-redirect'

export function useAuth() {
  const userStore = useUserStore()

  function checkLogin(): boolean {
    if (!userStore.isLoggedIn) {
      redirectToLogin()
      return false
    }
    return true
  }

  return { checkLogin, isLoggedIn: userStore.isLoggedIn }
}
