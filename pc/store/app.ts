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
          this.applyTheme()
        }
      } catch {
        /* silent: 让 composable 走默认值 */
      }
      this.loaded = true
    },

    applyTheme() {
      const primary = this.config?.theme_primary_color
      if (primary && typeof document !== 'undefined') {
        document.documentElement.style.setProperty('--color-primary', primary)
      }
    },

    getImageUrl(url: string): string {
      if (!url) return ''
      if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('data:')) {
        return url
      }
      if (url.startsWith('/')) return url
      const base = String(this.config?.site_url || '').replace(/\/$/, '')
      return base ? `${base}/${url.replace(/^\//, '')}` : `/${url.replace(/^\//, '')}`
    },
  },
})
