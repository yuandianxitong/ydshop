# v2.12.3 升级说明

## 必须使用维护窗口

本版本同时切换订单行金额分摊、支付绑定、佣金唯一身份和会员权益证据账本，不允许旧代码与新结构滚动混跑。旧 `OrderCompletedListener` 在新结构上继续消费事件时，可能写入 `identity_key=NULL` 佣金；零积分订单也无法仅凭旧流水证明成长值、累计消费和订单数是否已发放。

严格按以下顺序操作：

1. 停止 Web 流量、队列 worker 和 cron runner，确认没有订单完成、支付成功或退款事件在执行。
2. 备份数据库。
3. 如当前是 v2.12.0，先依次执行 v2.12.1、v2.12.2，再执行本目录 `update.sql`。
4. 部署完整 v2.12.3 代码并清理框架缓存。
5. 确认新命令可被 `php think list` 发现，再恢复 worker、cron 和 Web 流量。

> 禁止在未停队列/未停 Web 的情况下执行 SQL，也禁止“先执行 SQL、继续跑旧代码、再逐台发布”。

## 升级前置检查

`update.sql` 的第一段会在任何表结构修改前检查历史多义数据：

- 一个业务订单存在多条 `order_payments`；
- 同一真实 `payment_order_id` 被多条订单支付或充值单占用；
- 同一支付单号候选反向命中多个订单/充值单，或同时命中订单和充值单；
- 旧商户支付单号可映射到多个商城业务订单，或同一业务订单存在多笔已支付/已退款渠道单；
- 已绑定支付单与业务单号、用户、金额、渠道或交易号冲突；
- 已成功退款缺少有效订单、商品行或真实支付单，存在同商品行多次成功退款，或成功退款累计额超过支付额；
- `payment_orders.refund_amount` 既不等于成功退款累计额、也不符合旧版“最后一笔退款额”语义；
- `status=refunded` 但未全额退款的支付单无法由唯一绑定的成功 `order_refunds` 证明；
- 同一订单存在多条 `delivery_orders`。

这些冲突无法通过自动删除一行安全确定经济事实。检查命中时 SQL 会以 `SQLSTATE 45000` 停止，且尚未执行任何 `ALTER TABLE`。请先导出冲突行，根据支付渠道账单人工确认后再重新执行完整 SQL；不要为了通过唯一索引盲目删除已支付或已结算行。

旧 `payment_order_id=0` 不代表真实支付单。升级只会绑定能由业务单号、用户、金额、渠道和交易号唯一证明的记录；其余 `0` 统一转为 `NULL`，保留给 `payment:resync` 或人工对账，不猜测关联。

v2.12.0 的退款字段有两项旧语义：任意金额退款成功都会立即把支付单标记为 `refunded`，而 `refund_amount` 只保存当次金额。升级以身份完整、未软删除且 `status=refunded` 的 `order_refunds` 累计额作为成功退款证据；累计额小于支付额时把 `payment_orders` 恢复为 `paid`、把 `order_payments` 恢复为状态 `1`，只有累计额等于支付额时才保留全额退款状态。累计超额、同商品行重复成功、旧字段无法解释或缺少唯一支付绑定时一律在任何 DDL 前中止，必须结合支付渠道退款账单审计，不能直接改状态或凑金额。

## 数据库变更

- `user_notification_reads.notification_id` 与 `user_notifications.biz_id` 升级为 unsigned bigint；用户站内通知新增事件幂等键，支付、退款等事件重放不会重复生成消息。
- `order_items` 新增优惠、运费、实付分摊和秒杀活动商品 ID。历史订单在首次需要时由共享整数分算法惰性分摊，不在 SQL 中用浮点比例破坏尾差。
- `order_payments.payment_order_id` 和 `member_recharge_orders.payment_order_id` 升级为 nullable unsigned bigint 唯一绑定；充值单新增 `settled_at`，并持久化历史成长值的理论值、待复核状态、人工结论与操作人。若旧充值的余额/赠送流水齐全但缺少订单级成长流水，系统只标记待复核，不会猜测补发；管理员可确认“历史已发放”（只留痕）或确认“历史未发放”（使用唯一事件键精确补发一次）。
- `payment_orders.status` 的状态契约增加创建屏障 `creating` 和取消屏障 `closing`，升级库与新安装库统一为 `creating/pending/closing/paid/closed/refunded`；历史成功退款额按上述证据归一为累计值。
  升级预检会拒绝仍处于 `pending/creating/closing` 的旧支付单；这些记录缺少升级前固化的收款主体与过期快照，必须先人工核对渠道账单后再执行升级。
- `payment_orders.business_order_no` 把商城业务订单与渠道商户单号解耦；同一业务可保留多条已关闭尝试，数据库生成列 `active_business_key` 保证任意时刻最多一条创建中、待支付、关闭中或已支付尝试。丢失调起凭据的旧渠道单经对账确认关闭后，可使用全新的商户单号重新发起。
- `balance_logs`、`points_logs`、`finance_transactions`、`message_logs`、`user_operation_logs` 新增 nullable `event_key` 和对应唯一索引，依赖数据库裁决并发幂等。
- `points_logs.source` 新增索引，避免历史奖励证据查询扩大扫描和锁范围。
- `delivery_orders.order_id` 改为唯一索引，防止重复建配送单。
- `order_refunds` 持久化真实退款标识、渠道状态、请求/检查时间和 `refunded_at`；微信记录 `refund_id`，支付宝记录商户退款请求号。
- 用户新增 `commission_debt` 和 `points_debt`；新增佣金冲正、积分债务、成长值、订单权益快照与调整账本。
- 历史订单权益新增复核结论、依据、操作管理员和结案时间；后台“历史权益复核”只能确认未验证聚合权益不归属于该订单，不会按理论快照增减会员资产，结案同时追加不可变调整流水。
- 分销佣金新增计佣基数、实际入账、累计冲正和原因。v2.12.0 旧佣金保留当时按商品行原小计计算的真实历史基数，不追溯改写已形成的佣金金额；新佣金才使用净额基数。历史重复行的 canonical 优先选择存在真实正数 `distribution_settle` 流水的经济实现行；无入账证据的非 canonical 行才会被取消。
- 历史部分退款不能按新版本“整行退款”假设直接全额冲佣。补偿逻辑按可信商品行基数和累计退款比例计算目标冲正额；无法证明基数或累计额的记录持久化为人工复核，禁止自动全反转。
- 分销可提现额度只采信唯一且用户、类型、金额、余额前后值全部一致的结算流水；重复、错用户、错类型或余额不连续的结算/提现冻结证据会持久隔离。后台“佣金记录”可提交已核实的商品行实付、累计退款、原始入账、结算和提现资金事实，系统把提现结论、依据、操作人与时间同步固化在提现单，并在后续系统补冻结/退回时推进事实状态；冲正仍沿用原退款事件键，禁止直接手改余额或佣金状态。
- 新老安装统一提现限额、手续费和渠道默认配置。

## 补偿与发布边界

升级 SQL 用实际执行时的 `NOW()` 写入下列边界，没有硬编码发布日期：

- `payment.reconcile_from`；
- `distribution.commission_reconcile_from`；
- `member_reward.snapshot_started_at`。

边界前的完成订单不会按当前邀请链/费率追溯补佣；会员权益只导入可证明快照，未验证的成长值、累计消费和订单数不自动冲正。历史已支付但 `settled_at IS NULL` 的充值单也不会被自动边界任务追溯，需在维护窗口内使用 `payment:resync` 人工审计重放。

本版本写入并启用以下调度配置：

- `payment:reconcile`；
- `refund:reconcile`；
- `finance:reconcile`；
- `distribution:reconcile-refunds`；
- `member:reconcile-order-rewards`；
- `distribution:settle`。

`payment:reconcile` 每轮先在 `payment.provider_reconcile_from` 起始边界内，扫描
`pending/creating/closing` 且更新时间早于安全延迟（默认 300 秒）的到期批次。
每条记录使用独立 `next_at` 和指数退避重试，单条渠道故障不会阻塞后续支付单。
这用于发现回调丢失导致的“渠道已扣款、本地仍 pending”，不允许以未知响应自动关单；
若渠道已存在但本地调起凭据丢失，必须先确认关单，随后才能为同一业务签发新商户单号。
旧支付单若没有可验证的签发主体或支付宝凭据失效时间，必须在维护窗口人工核对，
不能把 `TRADE_NOT_EXIST` 当作关闭证明。

`cron_jobs` 只是调度配置；部署环境仍必须启动统一 cron runner，否则这些命令不会自动执行。

## 执行与验证

```bash
mysql -u<user> -p <database> < server/database/updates/v2.12.3/update.sql
php think list
php think payment:resync
php think finance:reconcile --dry-run

# 核对 dry-run 列表和支付渠道账单后再执行：
php think payment:resync --apply
php think payment:reconcile
php think refund:reconcile
php think finance:reconcile
php think distribution:reconcile-refunds
php think member:reconcile-order-rewards
```

先查看命令输出与对账差异，再恢复外部流量。
