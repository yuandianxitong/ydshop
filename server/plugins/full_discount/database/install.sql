-- plugins/full_discount/database/install.sql
-- 裸表名；幂等

CREATE TABLE IF NOT EXISTS `marketing_full_discounts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL COMMENT '活动名称',
  `type` enum('reduce','percent','freight') DEFAULT NULL COMMENT '优惠类型',
  `rules` json DEFAULT NULL COMMENT '阶梯规则',
  `use_scope` enum('all','category','spu') DEFAULT 'all' COMMENT '适用范围',
  `scope_ids` json DEFAULT NULL COMMENT '范围ID列表',
  `stackable` tinyint(1) DEFAULT '1' COMMENT '是否可叠加',
  `start_at` datetime DEFAULT NULL COMMENT '活动开始时间',
  `end_at` datetime DEFAULT NULL COMMENT '活动结束时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `start_end` (`start_at`,`end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='满减活动';
