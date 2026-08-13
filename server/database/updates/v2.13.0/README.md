# v2.13.0 升级说明

## 版本内容

1. **三方同城配送平台接入**：达达配送 / 蜂鸟配送 / UU跑腿 / 闪送 / 顺丰同城，统一适配器架构，支持后台参数配置、手动平台发单、支付后自动发单、状态回调与轨迹留痕。
2. **定时任务调度补齐**：新增 `schedule:run-due` 常驻调度（supervisord `scheduler` 进程每分钟轮询 `cron_jobs` 表），后台定时任务白名单扩至 14 条，订单超时取消 / 自动确认收货 / 拼团过期 / 用户标签分群刷新等任务开始真正自动运行。
3. **售后闭环补齐**：C 端新增「我的售后」列表 / 详情接口与页面，退货物流单号填写打通 `approved → returning → received → refunded` 全流程；拼团失败自动整单退款。
4. **PC 端补齐**：结算页配送方式选择（快递 / 同城 / 自提）、售后页面、物流轨迹页。

## 数据库变更

- `delivery_orders`：新增 `platform_order_id` / `platform_status` / `rider_name` / `rider_phone` / `rider_lat` / `rider_lng` / `dispatched_at` / `dispatch_fail_reason` / `callback_raw` 9 列与 `uk_platform_order`、`idx_platform_status` 索引。
- 新表 `delivery_order_tracks`（配送轨迹点，append-only）。
- `system_configs`：`local_delivery` 分组新增 30 项三方平台配置 + `local_delivery_per_km_fee` 兜底补齐；`local_delivery_platform` 选项扩展为 6 个平台。
- `cron_jobs`：删除幽灵命令 `clear:cache` / `clear:temp`（对应命令不存在）；修正 `refund:reconcile` 等 5 个对账任务的执行频率（仅未被自定义过的行）；补齐 8 条缺失任务。

## 升级步骤

1. 备份数据库。
2. 执行 `update.sql`（脚本幂等，可重复执行）。
3. 重新构建/重启容器以加载 supervisord 新增的 `scheduler` 进程（非 Docker 部署见下方「定时任务启停」）。
4. 后台「配送管理 → 配送设置 → 同城配送配置」中按需配置三方平台密钥，并在各平台开放平台后台配置回调地址 `https://你的域名/api/delivery/callback/{dada|fengniao|uupt|shansong|sfsc}`。

## 定时任务启停（非 Docker / 宝塔）

**生产推荐用系统 crontab**（每分钟新进程，避免长驻 PDO 被 MySQL `wait_timeout` 掐断后出现 `MySQL server has gone away`）：

```bash
# 先停掉可能在跑的 schedule:work，避免与 crontab 双调度
cd /path/to/Shop/server
php think schedule:work stop

# crontab -e 增加一行（路径改成实际 server 目录）
* * * * * cd /path/to/Shop/server && php think schedule:run-due >> runtime/schedule-run.log 2>&1
```

可选：不便配 crontab 时再用常驻工人（周期调用 `schedule:run-due`）。工人每轮 tick 前会重连 DB（避免 `MySQL server has gone away`）：

```bash
# 宝塔需解除禁用：proc_open pcntl_signal pcntl_signal_dispatch pcntl_fork pcntl_wait pcntl_alarm
cd /path/to/Shop/server
php think schedule:work start --d
php think schedule:work status
php think schedule:work restart --d
php think schedule:work stop
```

后台改任务后无需重启调度器，每轮都会读库。

## 注意事项

- 三方平台签名与报文已按各官方开放平台文档实现，**上线前必须在各平台沙箱环境联调核实**（尤其闪送签名算法）。
- 自动发单开关（`local_delivery_auto_dispatch_enabled`）默认关闭；开启前请确认默认发单平台已配置且余额充足。
- 拼团失败自动退款仅对状态为已支付（paid）的订单生效，已发货订单需人工处理。
