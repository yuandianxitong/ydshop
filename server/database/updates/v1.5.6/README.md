# v1.5.6 升级说明

## 变更
- 用户管理新增菜单：地址簿（970）、账户资金（975）。
- 新增权限：`member.address.*`、`member.fund.*`。
- **不新增表**：账户资金页复用现有 `balance_logs` / `member_recharge_orders` / `distribution_withdrawals`。
- 补齐 `system_configs` 缺失的 17 行 `ai.*` seed（`ai.default_driver` / `ai.enabled_drivers` + 5 驱动 × 3 字段）。修复 `SystemConfig::setConfigValue` 对不存在 key 返回 false 导致的 AI 配置保存静默失败；新增 `enabled_drivers` 字段支持启用/暂停状态持久化。
- 新增财务导出权限：`finance.balance.export` / `finance.transaction.export` / `finance.withdrawal.export` / `finance.overview.export` / `finance.points.export`（子项目 3）。
- 新增会员模块导出权限：`member.address.export` / `member.fund.export`（子项目 4）。
- 新增物流配送/分销佣金导出权限：`delivery.order.export` / `delivery.staff.export` / `distribution.commission.export`（子项目 5）。
- 新增财务模块"积分规则"菜单与权限：`finance.points.rules`（ID 473）+ 菜单 ID 1245，路径 `/finance/points-rules`（子项目 8）。
- 新建 `user_login_logs` 表（id / user_id / login_at / login_ip）用于会员留存计算（子项目 10）。
- 新建 `delivery_exception_tickets` 表 + 4 个权限（IDs 474-477：`delivery.exception.{list,create,update,delete}`）+ 菜单 ID 1360 + 3 个子按钮（子项目 11：异常工单不含地图）。
- delivery_orders 加 dest_lat/dest_lng 字段（地图坐标）；delivery_staff 加 current_lat/current_lng/location_updated_at 字段（埋数据基础，留 follow-up "配送员端"）；system_configs 新增 4 条 amap.* seed；菜单 ID 1370 实时地图（沿用 delivery.order.list 权限）（子项目 12）。
- 新建 `delivery_shifts` 表（班次：staff_id/weekday/start_time/end_time/remark）+ 2 个权限（IDs 478-479：`delivery.shift.{list,manage}`）+ 菜单 ID 1380 + 子按钮 1381（排班管理）（子项目 13）。
- 新增权限 `order.waybill.print`（ID 484）：调用快递鸟 API 批量生成电子面单（子项目 14）。

## 升级方式
```bash
mysql -u <user> -p <database> < server/database/updates/v1.5.6/update.sql
```
