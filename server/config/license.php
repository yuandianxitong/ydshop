<?php
// +----------------------------------------------------------------------
// | 产品授权（对接官网 Site）
// +----------------------------------------------------------------------

return [
    // 官网授权中心（服务器到服务器：开放 API）
    'site_base_url' => rtrim((string) env('LICENSE_SITE_BASE_URL', 'https://www.dev007.cn'), '/'),

    // 浏览器授权页基址。留空 = 与 site_base_url 相同。
    // 本地 Site 前端若挂在 /pc，则 LICENSE_SITE_WEB_BASE=http://localhost:xxxx/pc
    'site_web_base_url' => rtrim((string) env('LICENSE_SITE_WEB_BASE', ''), '/'),

    // 产品标识，须与 Site products.slug 一致
    'product_slug' => (string) env('LICENSE_PRODUCT_SLUG', 'shop'),

    // 部署域名（空则自动取 HTTP_HOST）
    'domain' => (string) env('LICENSE_DOMAIN', ''),

    // 本地缓存宽限天数（官网不可达时仍视为有效）
    'grace_days' => (int) env('LICENSE_GRACE_DAYS', 14),

    // 心跳间隔（秒），命令 license:heartbeat 使用
    'heartbeat_interval' => (int) env('LICENSE_HEARTBEAT_INTERVAL', 86400),

    // 本地状态文件（相对 runtime 路径）
    'state_file' => 'license/state.json',
];
