import { computed, type ComputedRef } from 'vue'
import { useAppStore } from '~/store/app'

// ============================================================================
// 与 server/public/install/data/init.sql 中的 pc_header_menu / pc_footer_config
// 严格保持一致 —— 这是 fallback 默认值，仅在 API 失败 / 配置缺失 / 数据脏时使用
// ============================================================================

export interface HeaderItem {
  label: string
  path: string
}

export interface FooterLink {
  label: string
  path: string
}

export interface FooterColumn {
  title: string
  links: FooterLink[]
}

export interface FooterConfig {
  columns: FooterColumn[]
  copyright: string
}

export const DEFAULT_HEADER: HeaderItem[] = [
  { label: '首页',     path: '/' },
  { label: '热销榜单', path: '/goods?sort=sales' },
  { label: '新品推荐', path: '/goods?sort=newest' },
  { label: '好物优选', path: '/goods?is_recommend=1' },
  { label: '限时秒杀', path: '/marketing/flash-sale' },
  { label: '领券中心', path: '/marketing/coupon' },
  { label: '商城资讯', path: '/article' },
  { label: '帮助中心', path: '/help' },
]

export const DEFAULT_FOOTER: FooterConfig = {
  columns: [
    {
      title: '关于我们',
      links: [
        { label: '关于元点', path: '/about' },
        { label: '联系我们', path: '/contact' },
      ],
    },
    {
      title: '帮助中心',
      links: [
        { label: '用户协议', path: '/article/agreement' },
        { label: '隐私政策', path: '/article/privacy' },
      ],
    },
    {
      title: '友情链接',
      links: [
        { label: '管理后台', path: '/admin/' },
      ],
    },
    {
      title: '联系方式',
      links: [
        { label: '邮箱：642508814@qq.com', path: '' },
        { label: '微信：Vince_Dorian',     path: '' },
      ],
    },
  ],
  copyright: '© {YEAR} 元点Shop. All rights reserved. Powered by yd-admin',
}

/**
 * 双 JSON 编码自愈：早期 admin 误把 stringify 过的字符串再发后端，导致 DB
 * 里存的是双层编码字符串。这里 parse 一次后若仍是 string 再 parse 一次。
 */
export function parseConfig<T = any>(raw: any): T | null {
  if (raw == null) return null
  if (typeof raw !== 'string') return raw as T
  try {
    const v = JSON.parse(raw)
    if (typeof v === 'string') return JSON.parse(v) as T
    return v as T
  } catch {
    return null
  }
}

export function useHeaderMenu(): ComputedRef<HeaderItem[]> {
  const appStore = useAppStore()
  return computed(() => {
    const arr = parseConfig<HeaderItem[]>(appStore.config?.pc_header_menu)
    return Array.isArray(arr) && arr.length ? arr : DEFAULT_HEADER
  })
}

export function useFooterConfig(): ComputedRef<FooterConfig> {
  const appStore = useAppStore()
  return computed(() => {
    const obj = parseConfig<FooterConfig>(appStore.config?.pc_footer_config)
    if (obj && typeof obj === 'object' && Array.isArray(obj.columns) && obj.columns.length > 0) {
      return {
        columns: obj.columns,
        copyright: obj.copyright || DEFAULT_FOOTER.copyright,
      }
    }
    return DEFAULT_FOOTER
  })
}
