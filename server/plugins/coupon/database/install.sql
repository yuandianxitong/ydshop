-- plugins/coupon/database/install.sql
-- 裸表名；幂等

CREATE TABLE IF NOT EXISTS `marketing_coupons` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL COMMENT '优惠券名称',
  `type` enum('fixed','percent','no_threshold') DEFAULT NULL COMMENT '类型:fixed固定减/percent折扣/no_threshold无门槛',
  `value` decimal(10,2) DEFAULT NULL COMMENT '优惠值(减多少/折扣率)',
  `min_amount` decimal(10,2) DEFAULT '0.00' COMMENT '最低使用金额',
  `max_discount` decimal(10,2) DEFAULT '0.00' COMMENT '最大优惠金额(percent类型有效)',
  `total_count` int DEFAULT NULL COMMENT '发放总数量',
  `used_count` int DEFAULT '0' COMMENT '已领取数量',
  `per_user_limit` int DEFAULT '1' COMMENT '每人限领数量',
  `use_scope` enum('all','category','spu') DEFAULT 'all' COMMENT '使用范围',
  `scope_ids` text COMMENT '范围ID列表(JSON)',
  `start_at` datetime DEFAULT NULL COMMENT '开始时间',
  `end_at` datetime DEFAULT NULL COMMENT '结束时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态:1启用,0禁用',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  KEY `start_end` (`start_at`,`end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='优惠券表';

CREATE TABLE IF NOT EXISTS `marketing_coupon_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` int DEFAULT NULL COMMENT '优惠券ID',
  `user_id` int DEFAULT NULL COMMENT '用户ID',
  `status` enum('unused','used','expired') DEFAULT 'unused' COMMENT '状态',
  `used_order_id` int DEFAULT '0' COMMENT '使用订单ID',
  `used_at` datetime DEFAULT NULL COMMENT '使用时间',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `coupon_id` (`coupon_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `coupon_user` (`coupon_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户优惠券领取记录表';
