# v1.5.7 升级说明

## 变更
- `member_sign_logs` 加 `is_makeup`（是否补签）、`source`（签到来源）字段，新增 `idx_sign_date` 索引。
- `system_configs` 新增 4 条 `sign.makeup_*` seed（开关 / 消耗类型 / 单价 / 时限）。
- 新增权限 `marketing.sign.logs.view`（ID 485）。
- 新增用户端补签接口 `POST /api/v1/sign/makeup`。
- 后台签到配置页接入 KPI 统计与最近签到记录列表。

## 升级方式
```bash
mysql -u <user> -p <database> < server/database/updates/v1.5.7/update.sql
```
