import { defineStore } from 'pinia'
import { commonApi } from '~/api/common'

export const useAppStore = defineStore('app', {
  state: () => ({
    config: null as Record<string, any> | null,
    loaded: false,
  }),

  actions: {
    /**
     * 进站时拉一次全站配置；进程内单次（loaded 后不再重复请求）。
     * 任何失败静默 —— 由 composable 层的 fallback 兜底，不阻塞 UI。
     */
    async fetchConfig() {
      if (this.loaded) return
      try {
        const res = await commonApi.getConfig()
        if (res.code === 200) {
          this.config = res.data || {}
        }
      } catch {
        /* silent: 让 composable 走默认值 */
      }
      this.loaded = true
    },
  },
})
