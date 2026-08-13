import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { getToken } from '@/utils/auth'

/**
 * 通用 xlsx 导出：fetch + blob 下载
 *
 * url 应是后端 /adminapi/... 端点路径
 * params 是 query 参数（自动 stringify）
 * filename 含中文，无扩展名
 */
export function useExport() {
    const exporting = ref(false)

    const doExport = async (url: string, params: Record<string, any>, filename: string) => {
        if (exporting.value) return
        exporting.value = true
        try {
            const qs = new URLSearchParams()
            for (const k of Object.keys(params)) {
                const v = params[k]
                if (v === undefined || v === null || v === '') continue
                qs.append(k, String(v))
            }
            // 与 myRequest 保持同样的 baseURL 解析模式：
            // dev 用 Vite proxy 走 ''；prod 走 VITE_APP_API_URL（跨域部署）或 ''（同域）
            const baseURL = import.meta.env.DEV ? '' : (import.meta.env.VITE_APP_API_URL || '')
            const fullUrl = `${baseURL}${url}?${qs.toString()}&_t=${Date.now()}`

            const res = await fetch(fullUrl, {
                headers: { Authorization: `Bearer ${getToken()}` },
            })

            if (!res.ok) {
                let msg = '导出失败'
                try {
                    const j = await res.json()
                    msg = j.message || msg
                } catch { /* not json */ }
                ElMessage.error(msg)
                return
            }

            const blob = await res.blob()
            const objectUrl = URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = objectUrl
            a.download = `${filename}_${new Date().toISOString().slice(0, 10)}.xlsx`
            document.body.appendChild(a)
            a.click()
            document.body.removeChild(a)
            // 异步 revoke 避免与浏览器下载任务竞态
            setTimeout(() => URL.revokeObjectURL(objectUrl), 100)
            ElMessage.success('导出成功')
        } catch (e) {
            ElMessage.error('导出失败')
        } finally {
            exporting.value = false
        }
    }

    return { exporting, doExport }
}
