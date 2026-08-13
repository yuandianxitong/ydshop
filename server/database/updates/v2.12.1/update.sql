-- ============================================================
-- v2.12.1 补齐 lottery 插件业务表
-- 适用于 plugins 表已有 lottery，但营销抽奖业务表缺失的环境。
-- ============================================================

CREATE TABLE IF NOT EXISTS `marketing_lottery_activities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL COMMENT '活动名称',
  `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图',
  `description` text COMMENT '活动规则说明',
  `start_at` datetime NOT NULL COMMENT '开始时间',
  `end_at` datetime NOT NULL COMMENT '结束时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1启用 0禁用',
  `daily_free_count` int NOT NULL DEFAULT '1' COMMENT '每日免费次数',
  `points_per_draw` int NOT NULL DEFAULT '0' COMMENT '单次抽奖消耗积分（超出免费次数后）',
  `address_expire_days` int NOT NULL DEFAULT '7' COMMENT '中奖实物奖品填写地址的有效天数',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `status_start_end_idx` (`status`, `start_at`, `end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='抽奖活动表';

DROP PROCEDURE IF EXISTS add_lottery_address_expire_days;
DELIMITER $$
CREATE PROCEDURE add_lottery_address_expire_days()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'marketing_lottery_activities'
      AND column_name = 'address_expire_days'
  ) THEN
    ALTER TABLE `marketing_lottery_activities`
      ADD COLUMN `address_expire_days` int NOT NULL DEFAULT '7'
      COMMENT '中奖实物奖品填写地址的有效天数' AFTER `points_per_draw`;
  END IF;
END$$
DELIMITER ;
CALL add_lottery_address_expire_days();
DROP PROCEDURE IF EXISTS add_lottery_address_expire_days;

CREATE TABLE IF NOT EXISTS `marketing_lottery_prizes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `activity_id` int NOT NULL COMMENT '活动ID',
  `position` int NOT NULL COMMENT '九宫格位置 1-8',
  `name` varchar(100) NOT NULL COMMENT '奖品名称',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '奖品图片',
  `type` int NOT NULL COMMENT '奖品类型 1=优惠券 2=积分 3=谢谢参与 4=实物商品',
  `reference_id` int NOT NULL DEFAULT '0' COMMENT '关联ID（type=1 时为优惠券ID）',
  `value` int NOT NULL DEFAULT '0' COMMENT '数值（type=2 时为积分数量）',
  `weight` int NOT NULL DEFAULT '0' COMMENT '权重（用于按权重抽奖）',
  `stock` int NOT NULL DEFAULT '0' COMMENT '剩余库存',
  `original_stock` int NOT NULL DEFAULT '0' COMMENT '初始库存（统计用）',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `activity_position_unique` (`activity_id`, `position`),
  KEY `activity_id_idx` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='抽奖奖品表';

CREATE TABLE IF NOT EXISTS `marketing_lottery_records` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT '用户ID',
  `activity_id` int NOT NULL COMMENT '活动ID',
  `prize_id` int NOT NULL DEFAULT '0' COMMENT '奖品ID',
  `prize_type` int NOT NULL DEFAULT '3' COMMENT '奖品类型快照 1券 2积分 3谢谢参与 4实物',
  `prize_name` varchar(100) NOT NULL DEFAULT '' COMMENT '奖品名称快照',
  `prize_value` int NOT NULL DEFAULT '0' COMMENT '奖品数值（积分数 / 优惠券ID）',
  `is_free` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否本日免费 1是 0付费',
  `cost_points` int NOT NULL DEFAULT '0' COMMENT '消耗的积分（is_free=0 时记录）',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `user_activity_created_idx` (`user_id`, `activity_id`, `created_at`),
  KEY `activity_id_idx` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='抽奖记录表';

CREATE TABLE IF NOT EXISTS `marketing_lottery_shipments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL COMMENT '发货单号 LT+YmdHis+4',
  `user_id` int NOT NULL COMMENT '用户ID',
  `activity_id` int NOT NULL COMMENT '活动ID',
  `record_id` int NOT NULL COMMENT '抽奖记录ID',
  `prize_id` int NOT NULL COMMENT '奖品ID快照',
  `prize_name` varchar(100) NOT NULL DEFAULT '' COMMENT '奖品名称快照',
  `prize_image` varchar(255) NOT NULL DEFAULT '' COMMENT '奖品图片快照',
  `address_snapshot` json DEFAULT NULL COMMENT '收货地址快照',
  `express_company` varchar(50) NOT NULL DEFAULT '' COMMENT '快递公司',
  `express_no` varchar(50) NOT NULL DEFAULT '' COMMENT '快递单号',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending/shipped/completed/cancelled/expired',
  `expire_at` datetime DEFAULT NULL COMMENT '过期时间',
  `shipped_at` datetime DEFAULT NULL COMMENT '发货时间',
  `completed_at` datetime DEFAULT NULL COMMENT '确认收货时间',
  `cancelled_at` datetime DEFAULT NULL COMMENT '取消/过期时间',
  `cancel_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '取消原因',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no_unique` (`order_no`),
  UNIQUE KEY `record_id_unique` (`record_id`),
  KEY `user_status_idx` (`user_id`, `status`),
  KEY `activity_status_idx` (`activity_id`, `status`),
  KEY `status_expire_idx` (`status`, `expire_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='抽奖实物奖品发货单';

INSERT IGNORE INTO `plugin_migrations` (`plugin_code`, `version`, `executed_at`) VALUES
  ('lottery', '20260509120000', NOW()),
  ('lottery', '20260509120001', NOW()),
  ('lottery', '20260509120002', NOW()),
  ('lottery', '20260509130000', NOW()),
  ('lottery', '20260509130001', NOW()),
  ('lottery', '1.0.0', NOW());
