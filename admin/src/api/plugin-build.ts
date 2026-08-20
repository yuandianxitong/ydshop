import { myRequest } from '@/utils/request'

export interface PluginBuildInfo {
    id: number
    target: string
    trigger: string
    plugin_code: string | null
    status: number
    log: string
    artifact_path: string
    started_at: string | null
    finished_at: string | null
    created_at: string
}

export interface MobileBuildInfo {
    id: number
    platform: string
    trigger: string
    plugin_code: string | null
    status: number
    log: string
    artifact_path: string
    started_at: string | null
    finished_at: string | null
    created_at: string
}

export const pluginBuildApi = {
    list(params?: { page_no?: number; page_size?: number; code?: string; ids?: string }) {
        return myRequest.get<{ list: PluginBuildInfo[]; total: number }>('/adminapi/plugin-builds/list', { params })
    },
    rebuild(target: 'admin' | 'pc') {
        return myRequest.post<PluginBuildInfo>('/adminapi/plugin-builds/rebuild', { target })
    },
}

export const mobileBuildApi = {
    list(params?: { page_no?: number; page_size?: number; code?: string; ids?: string }) {
        return myRequest.get<{ list: MobileBuildInfo[]; total: number }>('/adminapi/mobile-builds/list', { params })
    },
    create(platform: 'h5' | 'mp-weixin') {
        return myRequest.post<MobileBuildInfo>('/adminapi/mobile-builds/create', { platform })
    },
    channel() {
        return myRequest.get<{ wechat_appid: string; has_key: boolean; wechat_upload_version: string }>(
            '/adminapi/mobile-builds/channel'
        )
    },
    saveChannel(data: { wechat_appid: string; wechat_upload_key?: string; wechat_upload_version?: string }) {
        return myRequest.post<void>('/adminapi/mobile-builds/channel', data)
    },
    clearChannel() {
        return myRequest.delete<void>('/adminapi/mobile-builds/channel')
    },
    upload(id: number) {
        return myRequest.post<{ ok: boolean; output: string; version: string }>(`/adminapi/mobile-builds/${id}/upload`)
    },
    cancel(id: number) {
        return myRequest.post<void>(`/adminapi/mobile-builds/${id}/cancel`)
    },
    delete(id: number) {
        return myRequest.delete<void>(`/adminapi/mobile-builds/${id}`)
    },
}

export function buildStatusLabel(status: number, kind: 'web' | 'mobile' = 'web') {
    const map: Record<number, string> = {
        0: '排队中',
        1: '编译中',
        2: '成功',
        3: '失败',
        4: kind === 'mobile' ? '已上传开发版' : '未知',
        5: kind === 'mobile' ? '已跳过（预置页）' : '已跳过（开发机软链）',
        6: '已取消',
    }
    return map[status] ?? String(status)
}

export async function waitForBuilds(
    webIds: number[],
    mobileIds: number[],
    onTick?: (web: PluginBuildInfo[], mobile: MobileBuildInfo[]) => void
) {
    const done = (s: number) => s === 2 || s === 3 || s === 4 || s === 5 || s === 6
    for (let i = 0; i < 180; i++) {
        const [web, mobile] = await Promise.all([
            webIds.length ? pluginBuildApi.list({ ids: webIds.join(',') }) : Promise.resolve({ data: { list: [] } }),
            mobileIds.length
                ? mobileBuildApi.list({ ids: mobileIds.join(',') })
                : Promise.resolve({ data: { list: [] } }),
        ])
        const w = web.data?.list || []
        const m = mobile.data?.list || []
        onTick?.(w, m)
        const webDone = webIds.length === 0 || (w.length >= webIds.length && w.every((r) => done(r.status)))
        const mobileDone = mobileIds.length === 0 || (m.length >= mobileIds.length && m.every((r) => done(r.status)))
        if (webDone && mobileDone) {
            return { web: w, mobile: m }
        }
        await new Promise((r) => setTimeout(r, 5000))
    }
    throw new Error('等待云编译超时，请到「云编译 / 客户端发布」查看')
}
