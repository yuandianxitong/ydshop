import type { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios'
import axios from 'axios'
import { ElMessage } from 'element-plus'

import { PageEnum } from '@/constants/page'
import { getLocale } from '@/locales/setupI18n'
import router from '@/router'
import type { ApiResponse } from '@/types/api'
import { clearAuthInfo, getToken } from '@/utils/auth'
import { t } from '@/utils/i18n'

// 创建axios实例
// 开发环境：留空，通过 vite proxy 代理
// 生产环境：读取 VITE_APP_API_URL，留空则请求当前域名（同域部署），填写则请求指定域名（跨域部署）
const request: AxiosInstance = axios.create({
    baseURL: import.meta.env.DEV ? '' : import.meta.env.VITE_APP_API_URL || '',
    timeout: 30000,
    headers: {
        'Content-Type': 'application/json;charset=UTF-8',
        Accept: 'application/json'
    }
})

// ========== Token 静默刷新 ==========
const REFRESH_URL = '/adminapi/auth/refresh'
let refreshingPromise: Promise<void> | null = null

function parseJwtPayload(token: string): Record<string, any> | null {
    try {
        const parts = token.split('.')
        if (parts.length !== 3) return null
        const base64 = parts[1].replace(/-/g, '+').replace(/_/g, '/')
        return JSON.parse(atob(base64))
    } catch {
        return null
    }
}

// 剩余有效期 < 总有效期的 50% 时触发刷新
function shouldRefresh(token: string): boolean {
    const payload = parseJwtPayload(token)
    if (!payload?.exp || !payload?.iat) return false
    const now = Math.floor(Date.now() / 1000)
    const remaining = payload.exp - now
    const total = payload.exp - payload.iat
    return remaining > 0 && remaining < total * 0.5
}

async function doRefresh(): Promise<void> {
    if (refreshingPromise) return refreshingPromise

    refreshingPromise = (async () => {
        try {
            const { useUserStore } = await import('@/store/modules/user.store')
            const userStore = useUserStore()
            await userStore.refreshToken()
        } finally {
            refreshingPromise = null
        }
    })()

    return refreshingPromise
}

// 请求拦截器
request.interceptors.request.use(
    async (config: any) => {
        // 添加Token（从统一缓存读取）
        let token = getToken()

        // 静默刷新：非 refresh 请求且 token 即将过期时自动刷新
        if (token && config.url !== REFRESH_URL && shouldRefresh(token)) {
            try {
                await doRefresh()
                token = getToken()
            } catch {
                // 刷新失败，继续使用旧 token（响应拦截器会处理 401）
            }
        }

        if (token && config.headers) {
            config.headers.Authorization = `Bearer ${token}`
        }

        // 添加当前语言（同步前后端多语言）
        const locale = getLocale()
        if (config.headers) {
            const thinkLang = locale === 'zh-CN' ? 'zh-cn' : 'en'
            config.headers['think-lang'] = thinkLang
        }

        // 添加traceId
        const traceId = generateTraceId()
        if (config.headers) {
            config.headers['X-Trace-Id'] = traceId
        }

        return config
    },
    (error) => {
        console.error('Request error:', error)
        return Promise.reject(error)
    }
)

// 响应拦截器
request.interceptors.response.use(
    (response: AxiosResponse<ApiResponse>) => {
        const { data } = response
        // 检查业务状态码
        if (data.code === 200 || data.code === 0) {
            return data as any
        }

        // 处理token验证失败的业务错误码
        if (
            data.code === 401 ||
            data.message?.includes('Token验证失败') ||
            data.message?.includes('Expired token')
        ) {
            // 使用统一的认证清理函数
            clearAuthInfo()
            // 避免在登录页面时重复跳转
            if (router.currentRoute.value.path !== PageEnum.LOGIN) {
                router.push(PageEnum.LOGIN)
            }
            const err = new Error(t('http.loginExpired'))
            ;(err as any).__handled = true
            return Promise.reject(err)
        }

        // 其他业务错误处理
        ElMessage.error(data.message || t('http.operationFailed'))
        const err = new Error(data.message || t('http.operationFailed'))
        ;(err as any).__handled = true
        return Promise.reject(err)
    },
    (error) => {
        let message = t('http.requestFailed')

        if (error.response) {
            const { status, data } = error.response

            switch (status) {
                case 401:
                    message = t('http.loginExpired')
                    // 使用统一的认证清理函数
                    clearAuthInfo()
                    // 避免在登录页面时重复跳转
                    if (router.currentRoute.value.path !== PageEnum.LOGIN) {
                        router.push(PageEnum.LOGIN)
                    }
                    break
                case 403:
                    message = t('http.forbidden')
                    break
                case 404:
                    message = t('http.notFound')
                    break
                case 422:
                    message = data?.message || t('http.validationFailed')
                    break
                case 400:
                    message = data?.message || t('http.badRequest')
                    break
                case 500:
                    message = data?.message || t('http.serverError')
                    break
                case 503:
                    // 系统未安装，提示用户访问后端安装页面
                    if (data?.data?.installed === false) {
                        message = t('http.notInstalled')
                    } else {
                        message = t('http.serviceUnavailable')
                    }
                    break
                default:
                    message = data?.message || `${t('http.requestFailed')} (${status})`
            }
        } else if (error.request) {
            message = t('http.networkError')
        }

        ElMessage.error(message)
        return Promise.reject(error)
    }
)

// 生成traceId
function generateTraceId(): string {
    return 'trace_' + Date.now() + '_' + Math.random().toString(36).substring(2, 11)
}

// ========== 飞行中请求去重 ==========
// GET：纯幂等，按 URL+params 去重
// POST/PUT/PATCH/DELETE：按 URL+method+body 去重，防止双击重复提交
const pendingRequests = new Map<string, Promise<any>>()

function safeStringify(value: unknown): string {
    if (value === undefined || value === null) return ''
    if (value instanceof FormData) {
        // FormData 无法序列化，仅用 URL+method 维度去重（短窗口防双击）
        return '<formdata>'
    }
    try {
        return JSON.stringify(value)
    } catch {
        return ''
    }
}

function buildDedupKey(method: string, url: string, params?: unknown, data?: unknown): string {
    return `${method}:${url}:${safeStringify(params)}:${safeStringify(data)}`
}

function dedupedRequest<T>(key: string, runner: () => Promise<T>): Promise<T> {
    const pending = pendingRequests.get(key)
    if (pending) return pending as Promise<T>
    const req = runner().finally(() => {
        pendingRequests.delete(key)
    })
    pendingRequests.set(key, req)
    return req
}

// 封装请求方法
export const myRequest = {
    get<T = any>(url: string, config?: AxiosRequestConfig): Promise<ApiResponse<T>> {
        const key = buildDedupKey('GET', url, config?.params)
        return dedupedRequest(key, () => request.get(url, config) as unknown as Promise<ApiResponse<T>>)
    },

    post<T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<ApiResponse<T>> {
        const key = buildDedupKey('POST', url, config?.params, data)
        return dedupedRequest(key, () => request.post(url, data, config) as unknown as Promise<ApiResponse<T>>)
    },

    put<T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<ApiResponse<T>> {
        const key = buildDedupKey('PUT', url, config?.params, data)
        return dedupedRequest(key, () => request.put(url, data, config) as unknown as Promise<ApiResponse<T>>)
    },

    delete<T = any>(url: string, config?: AxiosRequestConfig): Promise<ApiResponse<T>> {
        const key = buildDedupKey('DELETE', url, config?.params)
        return dedupedRequest(key, () => request.delete(url, config) as unknown as Promise<ApiResponse<T>>)
    },

    patch<T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<ApiResponse<T>> {
        const key = buildDedupKey('PATCH', url, config?.params, data)
        return dedupedRequest(key, () => request.patch(url, data, config) as unknown as Promise<ApiResponse<T>>)
    }
}

export default request
