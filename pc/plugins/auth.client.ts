import { getToken } from '~/composables/useRequest'
import { useUserStore } from '~/store/user'

/**
 * 启动 / 水合 / 翻页后都从 localStorage 把登录态同步回 Pinia。
 * 必须排在 pinia 插件之后，否则会写到尚未挂上的 store。
 */
export default defineNuxtPlugin({
  name: 'pc-auth',
  dependsOn: ['pinia'],
  setup(nuxtApp) {
    const userStore = useUserStore()
    const sync = () => {
      if (getToken()) {
        userStore.syncFromStorage()
      }
    }
    sync()
    nuxtApp.hook('app:mounted', sync)
    nuxtApp.hook('page:finish', sync)
  },
})
