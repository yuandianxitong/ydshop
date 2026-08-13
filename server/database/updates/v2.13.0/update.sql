-- v2.13.0 三方同城配送平台接入 + 定时任务调度补齐 + 售后闭环
-- 幂等设计：可重复执行不产生副作用

-- ============================================================
-- 1) delivery_orders 三方配送字段（逐列判断，幂等）
-- ============================================================
DROP PROCEDURE IF EXISTS upgrade_delivery_orders_v2_13_0;

DELIMITER $$
CREATE PROCEDURE upgrade_delivery_orders_v2_13_0()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'delivery_orders' AND column_name = 'platform_order_id') THEN
    ALTER TABLE `delivery_orders`
      ADD COLUMN `platform_order_id` varchar(64) DEFAULT NULL COMMENT '三方平台运单号' AFTER `platform`,
      ADD COLUMN `platform_status` varchar(32) NOT NULL DEFAULT '' COMMENT '三方平台原始状态码（透传）' AFTER `platform_order_id`,
      ADD COLUMN `rider_name` varchar(50) NOT NULL DEFAULT '' COMMENT '三方骑手姓名' AFTER `platform_status`,
      ADD COLUMN `rider_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '三方骑手电话' AFTER `rider_name`,
      ADD COLUMN `rider_lat` decimal(10, 7) DEFAULT NULL COMMENT '骑手实时纬度' AFTER `rider_phone`,
      ADD COLUMN `rider_lng` decimal(10, 7) DEFAULT NULL COMMENT '骑手实时经度' AFTER `rider_lat`,
      ADD COLUMN `dispatched_at` datetime DEFAULT NULL COMMENT '三方发单成功时间' AFTER `assigned_at`,
      ADD COLUMN `dispatch_fail_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '最近一次发单失败原因' AFTER `dispatched_at`,
      ADD COLUMN `callback_raw` json DEFAULT NULL COMMENT '最近一次三方回调原始报文（排障用）' AFTER `dispatch_fail_reason`;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'delivery_orders' AND index_name = 'uk_platform_order') THEN
    ALTER TABLE `delivery_orders` ADD UNIQUE KEY `uk_platform_order` (`platform`, `platform_order_id`);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'delivery_orders' AND index_name = 'idx_platform_status') THEN
    ALTER TABLE `delivery_orders` ADD KEY `idx_platform_status` (`platform`, `status`);
  END IF;

  -- 字段注释同步（platform 枚举扩展 / status 增加 picked）
  ALTER TABLE `delivery_orders`
    MODIFY COLUMN `platform` varchar(20) NOT NULL DEFAULT 'merchant' COMMENT '配送平台:merchant/dada/fengniao/uupt/shansong/sfsc',
    MODIFY COLUMN `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '状态:pending/assigned/picking/picked/delivering/completed/cancelled';
END$$
DELIMITER ;

CALL upgrade_delivery_orders_v2_13_0();
DROP PROCEDURE IF EXISTS upgrade_delivery_orders_v2_13_0;

-- ============================================================
-- 2) 配送轨迹点表
-- ============================================================
CREATE TABLE IF NOT EXISTS `delivery_order_tracks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `delivery_order_id` int unsigned NOT NULL COMMENT '关联配送记录ID',
  `lat` decimal(10, 7) NOT NULL COMMENT '纬度',
  `lng` decimal(10, 7) NOT NULL COMMENT '经度',
  `platform_status` varchar(32) NOT NULL DEFAULT '' COMMENT '上报时的平台原始状态',
  `reported_at` datetime NOT NULL COMMENT '轨迹点时间',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_delivery_order` (`delivery_order_id`, `reported_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='配送轨迹点（三方回调/同步追加）';

-- ============================================================
-- 3) system_configs：三方配送配置项（INSERT IGNORE 幂等，config_key 唯一键兜底）
--    含 v2.4.0 后新装环境可能缺失的 local_delivery_per_km_fee 兜底补齐
-- ============================================================
INSERT IGNORE INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
('local_delivery_per_km_fee', '1', 'local_delivery', 'number', '每公里费(元)', '同城配送每公里追加费用，实际运费=起步费+ceil(距离)×每公里费', NULL, NULL, 8, 1, NOW(), NOW()),
('local_delivery_auto_dispatch_enabled', '0', 'local_delivery', 'boolean', '支付后自动发单', '开启后订单支付成功自动向默认发单平台下单（仅默认平台为三方平台时生效）', NULL, NULL, 10, 1, NOW(), NOW()),
('local_delivery_shop_name', '', 'local_delivery', 'string', '寄件门店名称', '三方配送取件门店名称', NULL, NULL, 11, 1, NOW(), NOW()),
('local_delivery_shop_phone', '', 'local_delivery', 'string', '寄件门店电话', '三方配送取件联系电话', NULL, NULL, 12, 1, NOW(), NOW()),
('local_delivery_shop_province', '', 'local_delivery', 'string', '寄件门店省份', '三方配送取件地址省份', NULL, NULL, 13, 1, NOW(), NOW()),
('local_delivery_shop_city', '', 'local_delivery', 'string', '寄件门店城市', '三方配送取件地址城市', NULL, NULL, 14, 1, NOW(), NOW()),
('local_delivery_shop_district', '', 'local_delivery', 'string', '寄件门店区县', '三方配送取件地址区县', NULL, NULL, 15, 1, NOW(), NOW()),
('local_delivery_shop_address', '', 'local_delivery', 'string', '寄件门店详细地址', '三方配送取件详细地址', NULL, NULL, 16, 1, NOW(), NOW()),
('local_delivery_shop_lat', '', 'local_delivery', 'string', '寄件门店纬度', '三方配送取件坐标（骑手取件与计费需要）', NULL, NULL, 17, 1, NOW(), NOW()),
('local_delivery_shop_lng', '', 'local_delivery', 'string', '寄件门店经度', '三方配送取件坐标（骑手取件与计费需要）', NULL, NULL, 18, 1, NOW(), NOW()),
('local_delivery_notify_domain', '', 'local_delivery', 'string', '回调通知域名', '三方配送回调地址域名（含协议，如 https://shop.example.com），留空时使用当前请求域名', NULL, NULL, 19, 1, NOW(), NOW()),
('local_delivery_dada_enabled', '0', 'local_delivery', 'boolean', '启用达达配送', '启用达达配送三方平台', NULL, NULL, 20, 1, NOW(), NOW()),
('local_delivery_dada_app_key', '', 'local_delivery', 'string', '达达 AppKey', '达达开放平台 AppKey', NULL, NULL, 21, 1, NOW(), NOW()),
('local_delivery_dada_app_secret', '', 'local_delivery', 'string', '达达 AppSecret', '达达开放平台 AppSecret', NULL, NULL, 22, 1, NOW(), NOW()),
('local_delivery_dada_source_id', '', 'local_delivery', 'string', '达达商户编号', '达达开放平台商户 source_id', NULL, NULL, 23, 1, NOW(), NOW()),
('local_delivery_dada_shop_no', '', 'local_delivery', 'string', '达达门店编号', '达达平台门店编号 shop_no', NULL, NULL, 24, 1, NOW(), NOW()),
('local_delivery_fengniao_enabled', '0', 'local_delivery', 'boolean', '启用蜂鸟配送', '启用蜂鸟即配三方平台', NULL, NULL, 30, 1, NOW(), NOW()),
('local_delivery_fengniao_app_key', '', 'local_delivery', 'string', '蜂鸟 AppKey', '蜂鸟开放平台 AppId', NULL, NULL, 31, 1, NOW(), NOW()),
('local_delivery_fengniao_app_secret', '', 'local_delivery', 'string', '蜂鸟 AppSecret', '蜂鸟开放平台 SecretKey', NULL, NULL, 32, 1, NOW(), NOW()),
('local_delivery_fengniao_shop_id', '', 'local_delivery', 'string', '蜂鸟门店编号', '蜂鸟平台门店 ID', NULL, NULL, 33, 1, NOW(), NOW()),
('local_delivery_uupt_enabled', '0', 'local_delivery', 'boolean', '启用UU跑腿', '启用UU跑腿三方平台', NULL, NULL, 40, 1, NOW(), NOW()),
('local_delivery_uupt_app_key', '', 'local_delivery', 'string', 'UU跑腿 AppKey', 'UU跑腿开放平台 AppKey', NULL, NULL, 41, 1, NOW(), NOW()),
('local_delivery_uupt_app_secret', '', 'local_delivery', 'string', 'UU跑腿 AppSecret', 'UU跑腿开放平台 AppSecret', NULL, NULL, 42, 1, NOW(), NOW()),
('local_delivery_uupt_shop_id', '', 'local_delivery', 'string', 'UU跑腿门店编号', 'UU跑腿平台门店/账号编号', NULL, NULL, 43, 1, NOW(), NOW()),
('local_delivery_shansong_enabled', '0', 'local_delivery', 'boolean', '启用闪送', '启用闪送三方平台', NULL, NULL, 50, 1, NOW(), NOW()),
('local_delivery_shansong_app_key', '', 'local_delivery', 'string', '闪送 AppKey', '闪送开放平台 ClientId', NULL, NULL, 51, 1, NOW(), NOW()),
('local_delivery_shansong_app_secret', '', 'local_delivery', 'string', '闪送 AppSecret', '闪送开放平台 AppSecret', NULL, NULL, 52, 1, NOW(), NOW()),
('local_delivery_shansong_shop_id', '', 'local_delivery', 'string', '闪送门店编号', '闪送平台门店 ID（可空）', NULL, NULL, 53, 1, NOW(), NOW()),
('local_delivery_sfsc_enabled', '0', 'local_delivery', 'boolean', '启用顺丰同城', '启用顺丰同城急送三方平台', NULL, NULL, 60, 1, NOW(), NOW()),
('local_delivery_sfsc_app_key', '', 'local_delivery', 'string', '顺丰同城 开发者ID', '顺丰同城开放平台 partnerID(dev_id)', NULL, NULL, 61, 1, NOW(), NOW()),
('local_delivery_sfsc_app_secret', '', 'local_delivery', 'string', '顺丰同城 校验码', '顺丰同城开放平台 checkWord', NULL, NULL, 62, 1, NOW(), NOW()),
('local_delivery_sfsc_shop_id', '', 'local_delivery', 'string', '顺丰同城门店编号', '顺丰同城平台门店 ID', NULL, NULL, 63, 1, NOW(), NOW());

-- 默认发单平台下拉选项扩展（覆盖式更新 options 与名称，不动用户已选值）
UPDATE `system_configs`
SET `config_name` = '默认发单平台',
    `config_desc` = '同城配送默认发单平台（商家配送或三方平台）',
    `config_options` = '{"merchant":"商家配送","dada":"达达配送","fengniao":"蜂鸟配送","uupt":"UU跑腿","shansong":"闪送","sfsc":"顺丰同城"}'
WHERE `config_key` = 'local_delivery_platform';

-- ============================================================
-- 4) cron_jobs：清理幽灵命令、修正对账频率、补齐订单生命周期任务
-- ============================================================
-- clear:cache / clear:temp 在 console.php 中不存在对应命令，属历史幽灵种子数据
DELETE FROM `cron_jobs` WHERE `command` IN ('clear:cache', 'clear:temp');

-- refund:reconcile 语义是"查询静默超过10分钟的退款"，每天一次违背设计意图（仅修正未被用户自定义过的行）
UPDATE `cron_jobs` SET `expression` = '*/5 * * * *' WHERE `command` = 'refund:reconcile' AND `expression` = '10 3 * * *';
UPDATE `cron_jobs` SET `expression` = '*/15 * * * *' WHERE `command` = 'payment:reconcile' AND `expression` = '5 3 * * *';
UPDATE `cron_jobs` SET `expression` = '*/15 * * * *' WHERE `command` = 'finance:reconcile' AND `expression` = '20 3 * * *';
UPDATE `cron_jobs` SET `expression` = '*/30 * * * *' WHERE `command` = 'distribution:reconcile-refunds' AND `expression` = '30 3 * * *';
UPDATE `cron_jobs` SET `expression` = '*/30 * * * *' WHERE `command` = 'member:reconcile-order-rewards' AND `expression` = '45 3 * * *';

-- 补齐缺失的任务（按 command 判存幂等）
INSERT INTO `cron_jobs` (`name`, `command`, `expression`, `description`, `status`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT '超时订单自动取消' AS `name`, 'order:auto-cancel' AS `command`, '*/5 * * * *' AS `expression`, '超时未支付订单自动取消并释放库存' AS `description`, 1 AS `status`, NOW() AS `created_at`, NOW() AS `updated_at`
  UNION ALL SELECT '发货订单自动确认', 'order:auto-confirm', '0 1 * * *', '发货超期订单自动确认收货', 1, NOW(), NOW()
  UNION ALL SELECT '完成订单自动好评', 'order:auto-review', '30 1 * * *', '完成超期订单自动默认好评', 1, NOW(), NOW()
  UNION ALL SELECT '自提核销超时扫描', 'pickup:scan-timeout', '*/10 * * * *', '自提订单核销超时扫描处理', 1, NOW(), NOW()
  UNION ALL SELECT '拼团超时自动失败', 'group-buy:expire', '*/5 * * * *', '拼团超时未成团自动标记失败并触发退款补偿', 1, NOW(), NOW()
  UNION ALL SELECT '用户标签自动刷新', 'user-tag:refresh', '0 * * * *', '按规则重新计算自动用户标签', 1, NOW(), NOW()
  UNION ALL SELECT '用户分群自动刷新', 'user-group:refresh', '15 * * * *', '按规则重新计算自动用户分群', 1, NOW(), NOW()
  UNION ALL SELECT '日志归档清理', 'log:archive', '0 2 * * *', '清理过期管理员操作与登录日志', 1, NOW(), NOW()
  -- 以下 6 条为历史版本任务，老库可能从未播种，一并防御性补齐
  UNION ALL SELECT '支付成功消费者对账', 'payment:reconcile', '*/15 * * * *', '仅重放发布边界后的本地已支付单，补齐订单、充值和财务消费者', 1, NOW(), NOW()
  UNION ALL SELECT '退款渠道状态对账', 'refund:reconcile', '*/5 * * * *', '查询长时间退款中的渠道状态，仅在明确成功后完成本地结算', 1, NOW(), NOW()
  UNION ALL SELECT '财务流水对账', 'finance:reconcile', '*/15 * * * *', '按游标补齐支付、退款和提现财务流水', 1, NOW(), NOW()
  UNION ALL SELECT '分销退款佣金对账', 'distribution:reconcile-refunds', '*/30 * * * *', '重放已退款订单并补齐佣金冲正账本', 1, NOW(), NOW()
  UNION ALL SELECT '订单会员权益对账', 'member:reconcile-order-rewards', '*/30 * * * *', '补齐完成订单奖励快照与退款冲正', 1, NOW(), NOW()
  UNION ALL SELECT '分销佣金自动结算', 'distribution:settle', '10 4 * * *', '补偿完成事件漏佣并结算已过冻结期佣金', 1, NOW(), NOW()
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `cron_jobs` c WHERE c.`command` = seed.`command`);
