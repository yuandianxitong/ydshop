import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'
import { diyApi } from '@/api/diy'

/**
 * DIY 页面数据结构
 * components 为空数组表示后台未配置 DIY
 */
export interface DiyPageData {
  components: any[]
  title: string
  page_settings: Record<string, any>
}

interface CacheEntry {
  data: DiyPageData
  ts: number
}

const CACHE_PREFIX = 'diy_cache:'
const CACHE_TTL_MS = 24 * 60 * 60 * 1000 // 24h

function cacheKey(type: string, platform: string): string {
  return `${CACHE_PREFIX}${type}:${platform}`
}

function readCache(type: string, platform: string): DiyPageData | null {
  try {
    const raw = uni.getStorageSync(cacheKey(type, platform)) as string | CacheEntry | ''
    if (!raw) return null
    const entry: CacheEntry = typeof raw === 'string' ? JSON.parse(raw) : raw
    if (!entry || !entry.ts || !entry.data) return null
    if (Date.now() - entry.ts > CACHE_TTL_MS) return null
    return entry.data
  } catch {
    return null
  }
}

function writeCache(type: string, platform: string, data: DiyPageData): void {
  try {
    const entry: CacheEntry = { data, ts: Date.now() }
    uni.setStorageSync(cacheKey(type, platform), entry)
  } catch {
    // 容错：存储满或权限失败，下次重新拉
  }
}

export const useDiyStore = defineStore('diy', () => {
  // 按 type:platform 维度缓存（首页、专题页可能并存）
  const pages = reactive<Record<string, DiyPageData>>({})
  // 是否处于"冷启动加载中"状态（缓存命中后不显示骨架）
  const loading = ref<Record<string, boolean>>({})
  // 最近一次拉取是否失败
  const failed = ref<Record<string, boolean>>({})

  /**
   * 获取 DIY 页面
   * - 缓存命中：立刻返回缓存数据，后台静默拉新覆盖（不闪 loading）
   * - 缓存未命中：标记 loading=true，等待拉新；失败时 loading=false 且 failed=true
   */
  async function loadPage(type: string, platform: string): Promise<DiyPageData | null> {
    const key = `${type}:${platform}`

    // 1. 缓存命中：立刻写入响应式 state（首屏立刻可见），但仍触发后台刷新
    const cached = readCache(type, platform)
    if (cached && !pages[key]) {
      pages[key] = cached
    }

    // 2. 触发网络拉取
    const hadInitialData = !!pages[key]
    if (!hadInitialData) loading.value[key] = true
    failed.value[key] = false

    try {
      const res = await diyApi.getPage({ type, platform })
      const data: DiyPageData = {
        components: Array.isArray(res?.components) ? res.components : [],
        title: typeof res?.title === 'string' ? res.title : '',
        page_settings: res?.page_settings || {},
      }
      pages[key] = data
      writeCache(type, platform, data)
      return data
    } catch {
      failed.value[key] = true
      return pages[key] || null
    } finally {
      loading.value[key] = false
    }
  }

  function getPage(type: string, platform: string): DiyPageData | null {
    return pages[`${type}:${platform}`] || null
  }

  function isLoading(type: string, platform: string): boolean {
    return !!loading.value[`${type}:${platform}`]
  }

  function isFailed(type: string, platform: string): boolean {
    return !!failed.value[`${type}:${platform}`]
  }

  return { pages, loadPage, getPage, isLoading, isFailed }
})
