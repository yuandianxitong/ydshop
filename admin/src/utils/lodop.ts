/**
 * C-Lodop 双端口探测与 HTML 打印。
 * HTTPS 优先 8443；HTTP 默认 8000，失败再试 18000。
 */

export type LodopStatus = 'idle' | 'loading' | 'ready' | 'unavailable'

declare global {
    interface Window {
        getCLodop?: () => any
        LODOP?: any
        CLODOP?: any
    }
}

let loadPromise: Promise<boolean> | null = null
let lastStatus: LodopStatus = 'idle'
let lastMessage = ''

export function getLodopStatus(): { status: LodopStatus; message: string } {
    return { status: lastStatus, message: lastMessage }
}

function resolveLodop(): any | null {
    try {
        if (typeof window.getCLodop === 'function') {
            return window.getCLodop()
        }
    } catch {
        // ignore
    }
    return window.LODOP || window.CLODOP || null
}

function loadScript(src: string, timeoutMs = 2500): Promise<boolean> {
    return new Promise((resolve) => {
        const existing = document.querySelector(`script[data-lodop-src="${src}"]`)
        if (existing) {
            resolve(!!resolveLodop())
            return
        }
        const script = document.createElement('script')
        script.src = src
        script.async = true
        script.dataset.lodopSrc = src
        let done = false
        const finish = (ok: boolean) => {
            if (done) return
            done = true
            resolve(ok)
        }
        const timer = window.setTimeout(() => finish(!!resolveLodop()), timeoutMs)
        script.onload = () => {
            window.clearTimeout(timer)
            // CLodop 注入可能略晚于 onload
            window.setTimeout(() => finish(!!resolveLodop()), 200)
        }
        script.onerror = () => {
            window.clearTimeout(timer)
            finish(false)
        }
        document.head.appendChild(script)
    })
}

/** 按端口尝试引入 CLodopfuncs.js / Lodopfuncs.js */
export async function ensureLodop(options?: {
    httpPort?: number | string
    httpsPort?: number | string
    enabled?: boolean
}): Promise<boolean> {
    if (options?.enabled === false) {
        lastStatus = 'unavailable'
        lastMessage = '已关闭 Lodop 打印'
        return false
    }
    const existing = resolveLodop()
    if (existing) {
        lastStatus = 'ready'
        lastMessage = 'Lodop 已就绪'
        return true
    }
    if (loadPromise) return loadPromise

    lastStatus = 'loading'
    lastMessage = '正在连接 Lodop…'
    loadPromise = (async () => {
        const isHttps = window.location.protocol === 'https:'
        const httpsPort = Number(options?.httpsPort || 8443) || 8443
        const httpPort = Number(options?.httpPort || 8000) || 8000
        const candidates: string[] = []
        if (isHttps) {
            candidates.push(`https://localhost:${httpsPort}/CLodopfuncs.js`)
            candidates.push(`https://localhost:${httpsPort}/Lodopfuncs.js`)
        }
        candidates.push(`http://localhost:${httpPort}/CLodopfuncs.js`)
        candidates.push(`http://localhost:${httpPort}/Lodopfuncs.js`)
        if (httpPort !== 18000) {
            candidates.push('http://localhost:18000/CLodopfuncs.js')
            candidates.push('http://localhost:18000/Lodopfuncs.js')
        }

        for (const src of candidates) {
            const ok = await loadScript(src)
            if (ok || resolveLodop()) {
                lastStatus = 'ready'
                lastMessage = `Lodop 已连接（${src}）`
                return true
            }
        }
        lastStatus = 'unavailable'
        lastMessage =
            '未检测到 C-Lodop。请在本机安装并启动 C-Lodop，默认端口 HTTP 8000 / HTTPS 8443'
        return false
    })().finally(() => {
        loadPromise = null
    })

    return loadPromise
}

/** 打印 HTML 面单；失败返回 false，由调用方回退 print-js */
export async function printHtmlWithLodop(
    html: string,
    options?: { title?: string; preview?: boolean }
): Promise<boolean> {
    const lodop = resolveLodop()
    if (!lodop) return false
    try {
        lodop.PRINT_INIT(options?.title || '电子面单')
        lodop.ADD_PRINT_HTM(0, 0, '100%', '100%', html)
        if (options?.preview) {
            lodop.PREVIEW()
        } else {
            lodop.PRINT()
        }
        return true
    } catch {
        return false
    }
}
