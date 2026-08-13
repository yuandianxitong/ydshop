import { myRequest } from '@/utils/request'

export interface PluginInfo {
    code: string
    name: string
    version: string
    category: 'core' | 'channel_market' | 'value_added'
    parent_menu: string
    description: string
    author: string
    icon: string
    icon_url?: string | null
    palette: [string, string] | null
    recommended: number
    source: 'bundled' | 'downloaded'
    status: 'installed' | 'disabled'
    installed_at: string
    upgraded_at: string | null
    has_upgrade: boolean
    disk_version?: string | null
    entry_path?: string | null
}

export interface PluginLog {
    id: number
    plugin_code: string
    action: 'install' | 'uninstall' | 'upgrade' | 'enable' | 'disable'
    version_from: string | null
    version_to: string | null
    status: 'success' | 'failed'
    message: string | null
    operator_id: number | null
    created_at: string
}

export const pluginApi = {
    list() {
        return myRequest.get<PluginInfo[]>('/adminapi/plugins/list')
    },
    logs(params?: { code?: string; page_no?: number; page_size?: number }) {
        return myRequest.get('/adminapi/plugins/logs', { params })
    },
    /**
     * Uninstall a plugin. Default keeps business tables.
     * Pass purge=true to run database/uninstall.sql (destructive).
     */
    uninstall(code: string, purge = false) {
        return myRequest.delete<void>(`/adminapi/plugins/${code}`, {
            params: purge ? { purge: 1 } : undefined,
        })
    },
    upgrade(code: string) {
        return myRequest.post<void>(`/adminapi/plugins/${code}/upgrade`)
    },
    enable(code: string) {
        return myRequest.post<void>(`/adminapi/plugins/${code}/enable`)
    },
    disable(code: string) {
        return myRequest.post<void>(`/adminapi/plugins/${code}/disable`)
    },
    catalog(params?: { page?: number; limit?: number; keyword?: string }) {
        return myRequest.get<{ list: Array<Record<string, any>>; pagination?: Record<string, any>; site_base?: string }>(
            '/adminapi/market/catalog',
            { params },
        )
    },
    uploadInstall(file: File, onProgress?: (p: number) => void) {
        const form = new FormData()
        form.append('file', file)
        return myRequest.post<{ code: string }>('/adminapi/market/upload', form, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: (e: { total?: number; loaded: number }) => {
                if (e.total && onProgress) onProgress(Math.round((e.loaded / e.total) * 100))
            },
        })
    },
}
