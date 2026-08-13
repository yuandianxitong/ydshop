import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { configApi } from '@/api/config'
import { applyThemeVars as applyVarsToDocument } from '@/styles/theme-vars'

export const useAppStore = defineStore('app', () => {
  const config = ref<Record<string, any>>({})
  const isConfigLoaded = ref(false)
  let configPromise: Promise<Record<string, any>> | null = null

  const themeVars = computed(() => {
    const c = config.value
    return {
      '--color-primary': c?.theme_primary_color || '#2979ff',
      '--color-auxiliary': c?.theme_auxiliary_color || '#ff9900',
      '--color-text': c?.theme_text_color || '#18181b',
      '--color-bg': c?.theme_bg_color || '#f5f5f5',
    }
  })

  function applyThemeVars() {
    applyVarsToDocument(themeVars.value)
  }

  async function getConfig() {
    if (isConfigLoaded.value) return config.value
    if (configPromise) return configPromise
    configPromise = configApi.getGlobalConfig().then((result) => {
      config.value = result
      isConfigLoaded.value = true
      applyThemeVars()
      return result
    }).finally(() => {
      configPromise = null
    })
    return configPromise
  }

  function getImageUrl(url: string): string {
    if (!url) return ''
    if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('data:')) {
      return url
    }
    const baseUrl = config.value.site_url || config.value.oss_domain || ''
    return baseUrl + url
  }

  return { config, isConfigLoaded, getConfig, getImageUrl, themeVars, applyThemeVars }
})
