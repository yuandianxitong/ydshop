import { myRequest } from '@/utils/request'

export interface LicenseStateInfo {
    license_key_masked?: string
    domain?: string
    product_slug?: string
    remote_status?: string
    features?: string[]
    checked_at?: number
    activated_at?: string | null
    updated_at?: string
    last_action?: string
}

export interface LicenseStatusResult {
    status: 'active' | 'grace' | 'expired' | 'revoked' | 'inactive' | string
    pro_enabled: boolean
    message: string
    product_slug: string
    site_base_url: string
    state: LicenseStateInfo
}

export const licenseApi = {
    status: () => myRequest.get<LicenseStatusResult>('/adminapi/system/license/status'),
    activate: (data: { license_key: string; domain?: string }) =>
        myRequest.post<{ license: Record<string, unknown>; status: LicenseStatusResult }>(
            '/adminapi/system/license/activate',
            data
        ),
    heartbeat: () =>
        myRequest.post<{ license: Record<string, unknown>; status: LicenseStatusResult }>(
            '/adminapi/system/license/heartbeat'
        ),
    clear: () => myRequest.post<void>('/adminapi/system/license/clear'),
}
