export interface OptionItem {
    value: string
    label: string
}

export const CONDITION_OPTIONS: OptionItem[] = [
    { value: 'new_register',     label: '新注册用户' },
    { value: 'no_order_7d',      label: '注册时尚未下单' },
    { value: 'invited',          label: '通过邀请注册' },
    { value: 'profile_complete', label: '完善资料' },
]

export const SCENE_OPTIONS: OptionItem[] = [
    { value: 'register_success', label: '注册成功页' },
    { value: 'home_floating',    label: '首页悬浮入口' },
    { value: 'campaign_landing', label: '活动落地页' },
    { value: 'cart_empty',       label: '购物车空态' },
    { value: 'support_push',     label: '客服推送' },
]

export const RISK_OPTIONS: OptionItem[] = [
    { value: 'device_monthly',  label: '同设备每月最多 1 次' },
    { value: 'ip_daily',        label: '同 IP 每天最多 5 次' },
    { value: 'sms_verify',      label: '异常设备需短信验证' },
    { value: 'invite_required', label: '需要邀请关系' },
]
