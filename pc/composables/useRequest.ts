import { ofetch } from 'ofetch'

const TOKEN_KEY = 'pc_token'

export function getToken(): string | null {
  if (import.meta.server) return null
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token: string) {
  localStorage.setItem(TOKEN_KEY, token)
}

export function removeToken() {
  localStorage.removeItem(TOKEN_KEY)
}

// 公开页面：游客可自由浏览，401 时只清 token 不跳转登录页
// 注意：匹配的是"应用内路径"（已剥离 baseURL），保持与 vue-router 的 to.fullPath 一致
const PUBLIC_PATH_PATTERNS: RegExp[] = [
  /^\/$/,
  /^\/login(\/|$)/,
  /^\/register(\/|$)/,
  /^\/goods(\/|$)/,
  /^\/category(\/|$)/,
  /^\/search(\/|$)/,
  /^\/article(\/|$)/,
  /^\/announcement(\/|$)/,
  /^\/help(\/|$)/,
  /^\/marketing(\/|$)/,
]

function isPublicPath(appPath: string): boolean {
  return PUBLIC_PATH_PATTERNS.some((p) => p.test(appPath))
}

// 与 nuxt.config.ts 的 app.baseURL 保持一致（结尾不含 /）
const BASE_PATH = '/pc'

function stripBase(pathname: string): string {
  if (pathname === BASE_PATH) return '/'
  if (pathname.startsWith(BASE_PATH + '/')) return pathname.slice(BASE_PATH.length) || '/'
  return pathname
}

function redirectToLogin() {
  if (!import.meta.client) return
  const appPath = stripBase(window.location.pathname)
  const { search } = window.location
  if (appPath === '/login') return
  if (isPublicPath(appPath)) return
  // redirect 用应用内路径，登录后 router.push 会自动补回 baseURL
  navigateTo(`/login?redirect=${encodeURIComponent(appPath + search)}`)
}

export const request = ofetch.create({
  onRequest({ options }) {
    const token = getToken()
    const headers = options.headers instanceof Headers
      ? options.headers
      : new Headers(options.headers as HeadersInit | undefined)
    if (token) {
      headers.set('Authorization', `Bearer ${token}`)
    }
    headers.set('X-Client-Type', 'pc')
    options.headers = headers
  },

  onResponseError({ response }) {
    if (response.status === 401) {
      removeToken()
      redirectToLogin()
    }
  },
})

// Typed helpers matching backend response format: { code, message, data, timestamp }
export interface ApiResponse<T = any> {
  code: number
  message: string
  data: T
}

export interface Pagination {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface PageResult<T> {
  list: T[]
  pagination: Pagination
}

function normalizePaginationParams(params?: Record<string, any>): Record<string, any> | undefined {
  if (!params) return params
  const normalized = { ...params }
  if (normalized.page === undefined && normalized.page_no !== undefined) {
    normalized.page = normalized.page_no
  } else if (normalized.page_no === undefined && normalized.page !== undefined) {
    normalized.page_no = normalized.page
  }
  if (normalized.limit === undefined && normalized.page_size !== undefined) {
    normalized.limit = normalized.page_size
  } else if (normalized.page_size === undefined && normalized.limit !== undefined) {
    normalized.page_size = normalized.limit
  }
  return normalized
}

/**
 * 统一处理业务错误码：非 200 时自动弹出错误提示
 * 传入 showError: false 可跳过自动提示（调用方自行处理）
 */
async function handleResponse<T>(promise: Promise<ApiResponse<T>>, showError = true): Promise<ApiResponse<T>> {
  const res = await promise
  if (res.code === 401 && import.meta.client) {
    // 业务码 401：token 失效，清 token；公开页静默，受保护页跳登录并带 redirect
    removeToken()
    redirectToLogin()
    return res
  }
  if (res.code !== 200 && showError && import.meta.client) {
    // 使用 window.alert 的轻量替代，避免 useMessage 需要 setup 上下文
    import('naive-ui').then(({ createDiscreteApi }) => {
      const { message } = createDiscreteApi(['message'])
      message.error(res.message || '请求失败')
    }).catch(() => {
      console.error(res.message || '请求失败')
    })
  }
  return res
}

export function get<T = any>(url: string, params?: Record<string, any>, showError = true): Promise<ApiResponse<T>> {
  return handleResponse(request<ApiResponse<T>>(url, { method: 'GET', params: normalizePaginationParams(params) }), showError)
}

export function post<T = any>(url: string, body?: Record<string, any>, showError = true): Promise<ApiResponse<T>> {
  return handleResponse(request<ApiResponse<T>>(url, { method: 'POST', body }), showError)
}

export function put<T = any>(url: string, body?: Record<string, any>, showError = true): Promise<ApiResponse<T>> {
  return handleResponse(request<ApiResponse<T>>(url, { method: 'PUT', body }), showError)
}

export function del<T = any>(url: string, body?: Record<string, any>, showError = true): Promise<ApiResponse<T>> {
  return handleResponse(request<ApiResponse<T>>(url, { method: 'DELETE', body }), showError)
}
