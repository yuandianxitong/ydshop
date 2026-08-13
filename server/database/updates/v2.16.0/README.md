# v2.16.0

## 变更说明

- 新增 `waybill_templates` 电子面单模版表（业务类型 ExpType / 模版尺寸 TemplateSize）
- 模版字段：`pay_type` 邮费支付（现付/到付/月结）、`need_pickup` 上门揽件、`is_default` 默认模版
- 校正快递鸟配置文案：`waybill_app_key` → 用户 ID（EBusinessID），`waybill_app_secret` → API Key（AppKey）
- 新增 Lodop 打印机配置：`waybill_lodop_enabled` / `waybill_lodop_http_port` / `waybill_lodop_https_port`
- `update.php` 幂等补充旧表缺失列

## 升级

```bash
cd server && php think yd:update
```

