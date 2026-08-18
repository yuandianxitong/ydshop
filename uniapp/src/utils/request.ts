import { getToken, removeToken } from './auth'
import { getCurrentFullPath, isLoginPath, redirectToLogin } from './login-redirect'
import type { ApiResponse } from '@/types/api'

let BASE_URL = import.meta.env.VITE_APP_API_URL || ''
// #ifdef H5
// H5 开发模式通过 Vite 代理转发，使用相对路径避免跨域
if (import.meta.env.DEV) BASE_URL = ''
// #endif

function getClientType(): string {
  // #ifdef MP-WEIXIN
  return 'miniapp'
  // #endif
  // #ifdef APP-PLUS
  return 'app'
  // #endif
  // #ifdef H5
  const ua = navigator.userAgent.toLowerCase()
  return ua.includes('micromessenger') ? 'wechat_h5' : 'h5'
  // #endif
}

interface RequestOptions {
  url: string
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE'
  data?: any
  header?: Record<string, string>
  loading?: boolean
}

const PUBLIC_PATH_PATTERNS = [
  /^\/pages\/index\/index$/,
  /^\/pages\/category\/index$/,
  /^\/pages\/cart\/index$/,
  /^\/pages\/my\/index$/,
  /^\/pages\/discover\/index$/,
  /^\/modules\/login\/pages\/(login|register)$/,
  /^\/modules\/goods\/pages\/(list|detail|search|reviews)$/,
  /^\/modules\/article\/pages\//,
  /^\/modules\/announcement\/pages\//,
  /^\/modules\/help\/pages\//,
]

let extraPublicPaths: string[] = []

export function setPluginPublicPaths(paths: unknown) {
  extraPublicPaths = Array.isArray(paths) ? paths.filter((p): p is string => typeof p === 'string') : []
}

function isPublicPath(path: string): boolean {
  const [pathname] = path.split('?')
  if (PUBLIC_PATH_PATTERNS.some(pattern => pattern.test(pathname))) return true
  return extraPublicPaths.some((p) => pathname === p || pathname.startsWith(`${p}?`))
}

function normalizePaginationData(data: any): any {
  if (!data || typeof data !== 'object' || Array.isArray(data)) return data
  const normalized = { ...data }
  if (normalized.page === undefined && normalized.page_no !== undefined) {
    normalized.page = normalized.page_no
  }
  if (normalized.limit === undefined && normalized.page_size !== undefined) {
    normalized.limit = normalized.page_size
  }
  delete normalized.page_no
  delete normalized.page_size
  return normalized
}

function request<T = any>(options: RequestOptions): Promise<T> {
  const { url, method = 'GET', data, header = {}, loading = false } = options
  const requestData = method === 'GET' ? normalizePaginationData(data) : data

  if (loading) {
    uni.showLoading({ title: '加载中...' })
  }

  const token = getToken()
  if (token) {
    header['Authorization'] = `Bearer ${token}`
  }

  return new Promise((resolve, reject) => {
    uni.request({
      url: `${BASE_URL}${url}`,
      method,
      data: requestData,
      header: {
        'Content-Type': 'application/json',
        'X-Client-Type': getClientType(),
        ...header,
      },
      success: (res: any) => {
        if (loading) uni.hideLoading()

        const response = res.data as ApiResponse<T>

        if (response.code === 200) {
          resolve(response.data)
        } else if (response.code === 401 || res.statusCode === 401) {
          removeToken()
          const currentPath = getCurrentFullPath()
          // 公开页（首页/分类/我的/商品/营销等）上的可选登录接口 401 只清 token，不强制跳登录。
          if (!isLoginPath(currentPath) && !isPublicPath(currentPath)) {
            // #ifdef H5
            // 动态导入避免 request ↔ wechat-oauth 循环依赖；wechat-oauth 仅被动态引用以便打成独立 chunk
            import('./wechat-oauth').then(({ canWechatAutoLogin, startWechatOAuth }) => {
              if (canWechatAutoLogin()) {
                // 微信内置浏览器：token 过期后重新走微信授权，而不是打断用户去登录页
                startWechatOAuth()
              } else {
                redirectToLogin(currentPath)
              }
            }).catch(() => {
              redirectToLogin(currentPath)
            })
            // #endif
            // #ifndef H5
            redirectToLogin(currentPath)
            // #endif
          }
          reject(new Error(response.message || '请先登录'))
        } else {
          uni.showToast({ title: response.message || '请求失败', icon: 'none' })
          reject(new Error(response.message))
        }
      },
      fail: (err: any) => {
        if (loading) uni.hideLoading()
        uni.showToast({ title: '网络异常', icon: 'none' })
        reject(err)
      },
    })
  })
}

export const http = {
  get: <T = any>(url: string, data?: any) => request<T>({ url, method: 'GET', data }),
  post: <T = any>(url: string, data?: any) => request<T>({ url, method: 'POST', data }),
  put: <T = any>(url: string, data?: any) => request<T>({ url, method: 'PUT', data }),
  delete: <T = any>(url: string, data?: any) => request<T>({ url, method: 'DELETE', data }),
}

export default http
