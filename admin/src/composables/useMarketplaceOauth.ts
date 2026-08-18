export interface OauthCode {
    state: string
    code: string
}

/**
 * 官方市场 OAuth 弹窗。
 * openPopup() 必须在用户点击的同步调用栈里打开 about:blank，避免被拦截。
 */
export function useMarketplaceOauth() {
    function openPopup(): Window | null {
        return window.open('about:blank', 'marketplace-oauth', 'width=600,height=700')
    }

    function awaitAuthCode(popup: Window, authorizeUrl: string): Promise<OauthCode> {
        popup.location.href = authorizeUrl

        return new Promise<OauthCode>((resolve, reject) => {
            const cleanup = () => {
                window.removeEventListener('message', onMessage)
                window.clearInterval(timer)
            }
            const onMessage = (ev: MessageEvent) => {
                if (ev.origin !== window.location.origin) return
                if (!ev.data || ev.data.type !== 'marketplace-oauth') return
                cleanup()
                if (ev.data.denied || !ev.data.code) {
                    reject(new Error('已取消授权'))
                    return
                }
                resolve({ state: ev.data.state, code: ev.data.code })
            }
            const timer = window.setInterval(() => {
                if (popup.closed) {
                    window.clearInterval(timer)
                    window.setTimeout(() => {
                        cleanup()
                        reject(new Error('授权窗口已关闭，流程取消'))
                    }, 250)
                }
            }, 500)
            window.addEventListener('message', onMessage)
        })
    }

    return { openPopup, awaitAuthCode }
}
