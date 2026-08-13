-- ============================================================
-- v2.5.0 升级脚本（合并 vNext 三批 + 会员详情全闭环）
--
-- 包含变更：
--   A. 商品规格模板（vNext-spec-template）
--   B. 分销等级 CRUD + 三级佣金率（vNext-distribution-level）
--   C. 用户标签规则引擎 + cron 自动打标（vNext-user-tag-rules）
--   D. 会员详情全闭环：用户操作日志 + 运营备注
-- ============================================================

-- ============================================================
-- A. 商品规格模板
-- ============================================================
CREATE TABLE IF NOT EXISTS `goods_spec_templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL COMMENT '模板名称',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '模板备注',
  `items` json DEFAULT NULL COMMENT '规格项 [{name, values:[]}]',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1启用 0禁用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='规格模板';

INSERT IGNORE INTO `permissions`
  (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (265, 'goods.goods-spec-template',        '规格模板', '商品中心', '规格模板管理权限', 'admin', 1, 265, NOW(), NOW()),
  (266, 'goods.goods-spec-template.list',   '模板列表', '商品中心', '查看规格模板列表', 'admin', 1, 266, NOW(), NOW()),
  (267, 'goods.goods-spec-template.create', '创建模板', '商品中心', '创建规格模板',     'admin', 1, 267, NOW(), NOW()),
  (268, 'goods.goods-spec-template.update', '编辑模板', '商品中心', '编辑规格模板',     'admin', 1, 268, NOW(), NOW()),
  (269, 'goods.goods-spec-template.delete', '删除模板', '商品中心', '删除规格模板',     'admin', 1, 269, NOW(), NOW());

INSERT IGNORE INTO `menus`
  (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (780, 700, 2, '规格模板', 'GoodsSpecTemplate', '/goods/goods-spec-template', 'goods/goods-spec-template/index', NULL, 'i-svg:layers', 'goods.goods-spec-template', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 8, NOW(), NOW()),
  (781, 780, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'goods.goods-spec-template.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (782, 780, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'goods.goods-spec-template.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (783, 780, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'goods.goods-spec-template.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW());

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`)
SELECT 1, id, NOW(), NOW() FROM `permissions` WHERE `id` BETWEEN 265 AND 269;

-- ============================================================
-- B. 分销等级 CRUD + 三级佣金率
--    （ALTER TABLE 包在 PROCEDURE 中以兼容已应用过 vNext-distribution-level 的库）
-- ============================================================
DROP PROCEDURE IF EXISTS `__v250_add_third_rate`;
DELIMITER $$
CREATE PROCEDURE `__v250_add_third_rate`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'distribution_levels' AND column_name = 'third_rate'
  ) THEN
    ALTER TABLE `distribution_levels`
      ADD COLUMN `third_rate` decimal(5,4) DEFAULT '0.0000' COMMENT '三级佣金比例' AFTER `second_rate`;
  END IF;
END$$
DELIMITER ;
CALL `__v250_add_third_rate`();
DROP PROCEDURE `__v250_add_third_rate`;

INSERT IGNORE INTO `distribution_levels`
  (`id`, `name`, `first_rate`, `second_rate`, `third_rate`, `upgrade_condition`, `sort`, `status`, `created_at`, `updated_at`)
VALUES
  (1, '普通', 0.1500, 0.0500, 0.0200, NULL,                                                    1, 1, NOW(), NOW()),
  (2, '银牌', 0.1800, 0.0700, 0.0300, JSON_OBJECT('field','team_count','op','>=','value',10),  2, 1, NOW(), NOW()),
  (3, '金牌', 0.2000, 0.1000, 0.0500, JSON_OBJECT('field','team_count','op','>=','value',50),  3, 1, NOW(), NOW());

INSERT IGNORE INTO `permissions`
  (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (492, 'distribution.distribution-level',        '分销等级',     '用户管理', '分销等级管理权限',     'admin', 1, 492, NOW(), NOW()),
  (493, 'distribution.distribution-level.list',   '等级列表',     '用户管理', '查看分销等级列表',     'admin', 1, 493, NOW(), NOW()),
  (494, 'distribution.distribution-level.create', '创建等级',     '用户管理', '创建分销等级',         'admin', 1, 494, NOW(), NOW()),
  (495, 'distribution.distribution-level.update', '编辑等级',     '用户管理', '编辑分销等级',         'admin', 1, 495, NOW(), NOW()),
  (496, 'distribution.distribution-level.delete', '删除等级',     '用户管理', '删除分销等级',         'admin', 1, 496, NOW(), NOW()),
  (497, 'distribution.commission.settle',         '批量结算佣金', '用户管理', '将待结算佣金批量结算', 'admin', 1, 497, NOW(), NOW());

INSERT IGNORE INTO `menus`
  (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (980, 930, 2, '分销等级', 'DistributionLevel', '/distribution/distribution-level', 'distribution/distribution-level/index', NULL, 'i-lucide:medal', 'distribution.distribution-level', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (981, 980, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'distribution.distribution-level.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (982, 980, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'distribution.distribution-level.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (983, 980, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'distribution.distribution-level.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW());

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`)
SELECT 1, id, NOW(), NOW() FROM `permissions` WHERE `id` BETWEEN 492 AND 497;

-- ============================================================
-- C. 用户标签规则引擎 + cron 自动打标
--    （包在 PROCEDURE 中以兼容已应用过 vNext-user-tag-rules 的库）
-- ============================================================
DROP PROCEDURE IF EXISTS `__v250_extend_user_tags`;
DELIMITER $$
CREATE PROCEDURE `__v250_extend_user_tags`()
BEGIN
  DECLARE tbl_schema VARCHAR(64);
  SET tbl_schema = DATABASE();

  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = tbl_schema AND table_name = 'user_tags' AND column_name = 'description') THEN
    ALTER TABLE `user_tags` ADD COLUMN `description` varchar(255) NOT NULL DEFAULT '' COMMENT '标签描述' AFTER `name`;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = tbl_schema AND table_name = 'user_tags' AND column_name = 'group_type') THEN
    ALTER TABLE `user_tags` ADD COLUMN `group_type` enum('consume','behavior','lifecycle','social') NOT NULL DEFAULT 'social' COMMENT '分组：消费力/行为偏好/生命周期/社交属性' AFTER `color`;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = tbl_schema AND table_name = 'user_tags' AND column_name = 'rules') THEN
    ALTER TABLE `user_tags` ADD COLUMN `rules` json DEFAULT NULL COMMENT '规则定义 {logic, conditions:[]}' AFTER `group_type`;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = tbl_schema AND table_name = 'user_tags' AND column_name = 'auto_update') THEN
    ALTER TABLE `user_tags` ADD COLUMN `auto_update` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动更新（cron 重算）' AFTER `rules`;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = tbl_schema AND table_name = 'user_tags' AND column_name = 'user_count') THEN
    ALTER TABLE `user_tags` ADD COLUMN `user_count` int NOT NULL DEFAULT '0' COMMENT '覆盖用户数（缓存）' AFTER `auto_update`;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = tbl_schema AND table_name = 'user_tags' AND column_name = 'status') THEN
    ALTER TABLE `user_tags` ADD COLUMN `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1启用 0停用' AFTER `user_count`;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = tbl_schema AND table_name = 'user_tags' AND column_name = 'deleted_at') THEN
    ALTER TABLE `user_tags` ADD COLUMN `deleted_at` datetime DEFAULT NULL COMMENT '删除时间' AFTER `updated_at`;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = tbl_schema AND table_name = 'user_tags' AND index_name = 'idx_group_type') THEN
    ALTER TABLE `user_tags` ADD INDEX `idx_group_type` (`group_type`, `sort`);
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = tbl_schema AND table_name = 'user_tags' AND index_name = 'idx_auto_update') THEN
    ALTER TABLE `user_tags` ADD INDEX `idx_auto_update` (`auto_update`, `status`);
  END IF;
END$$
DELIMITER ;
CALL `__v250_extend_user_tags`();
DROP PROCEDURE `__v250_extend_user_tags`;

INSERT IGNORE INTO `permissions`
  (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (498, 'member.tag.refresh', '刷新标签', '用户管理', '按规则立即重算标签覆盖', 'admin', 1, 498, NOW(), NOW());

INSERT IGNORE INTO `menus`
  (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (985, 924, 3, '刷新标签', NULL, NULL, NULL, NULL, NULL, 'member.tag.refresh', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 6, NOW(), NOW());

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`)
SELECT 1, id, NOW(), NOW() FROM `permissions` WHERE `id` = 498;

-- ============================================================
-- D. 门店表加结构化地址字段（省/市/区/详细/区域编码）
--    与 member_addresses 字段对齐，便于按区域筛选门店
-- ============================================================
DROP PROCEDURE IF EXISTS `__v250_extend_stores_address`;
DELIMITER $$
CREATE PROCEDURE `__v250_extend_stores_address`()
BEGIN
  DECLARE tbl_schema VARCHAR(64);
  SET tbl_schema = DATABASE();

  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = tbl_schema AND table_name = 'stores' AND column_name = 'province') THEN
    ALTER TABLE `stores` ADD COLUMN `province` varchar(50) NOT NULL DEFAULT '' COMMENT '省' AFTER `address`;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = tbl_schema AND table_name = 'stores' AND column_name = 'city') THEN
    ALTER TABLE `stores` ADD COLUMN `city` varchar(50) NOT NULL DEFAULT '' COMMENT '市' AFTER `province`;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = tbl_schema AND table_name = 'stores' AND column_name = 'district') THEN
    ALTER TABLE `stores` ADD COLUMN `district` varchar(50) NOT NULL DEFAULT '' COMMENT '区/县' AFTER `city`;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = tbl_schema AND table_name = 'stores' AND column_name = 'detail') THEN
    ALTER TABLE `stores` ADD COLUMN `detail` varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址（街道门牌）' AFTER `district`;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = tbl_schema AND table_name = 'stores' AND column_name = 'region_code') THEN
    ALTER TABLE `stores` ADD COLUMN `region_code` varchar(20) NOT NULL DEFAULT '' COMMENT '区/县编码（regions.code）' AFTER `detail`;
  END IF;
END$$
DELIMITER ;
CALL `__v250_extend_stores_address`();
DROP PROCEDURE `__v250_extend_stores_address`;

-- ============================================================
-- E. 会员详情全闭环：用户操作日志 + 运营备注 + 详情页权限
-- ============================================================

-- 用户操作日志（统一聚合：登录/资产/等级/订单/客服/资料）
CREATE TABLE IF NOT EXISTS `user_operation_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL COMMENT '用户ID',
  `category` varchar(20) NOT NULL DEFAULT 'other' COMMENT '分类:login/asset/level/order/service/profile/other',
  `event_code` varchar(64) NOT NULL DEFAULT '' COMMENT '事件标识 e.g. order.placed',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '事件标题',
  `description` varchar(500) DEFAULT '' COMMENT '事件描述',
  `icon` varchar(64) DEFAULT '' COMMENT '前端 lucide 图标类',
  `tone` varchar(20) DEFAULT '' COMMENT '前端色调（如 #10b981）',
  `ref_type` varchar(40) DEFAULT '' COMMENT '关联实体类型 order/balance_log/...',
  `ref_id` bigint unsigned DEFAULT NULL COMMENT '关联实体ID',
  `meta` json DEFAULT NULL COMMENT '扩展元数据',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_created` (`user_id`, `created_at`),
  KEY `idx_user_category` (`user_id`, `category`),
  KEY `idx_event_code` (`event_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户操作日志（会员详情聚合）';

-- 运营备注
CREATE TABLE IF NOT EXISTS `member_remarks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL COMMENT '用户ID',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '备注内容',
  `operator_id` int unsigned DEFAULT NULL COMMENT '操作管理员ID',
  `operator_name` varchar(50) DEFAULT '' COMMENT '操作人冗余昵称',
  `created_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员运营备注';

-- 会员详情新增按钮权限
-- 注意：ID 403 已被 member.statistics 占用，sms 改用 407
INSERT IGNORE INTO `permissions`
  (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (404, 'member.coupon', '发放优惠券', '用户管理', '会员详情发券',           'admin', 1, 404, NOW(), NOW()),
  (405, 'member.remark', '运营备注', '用户管理', '会员详情备注 CRUD',       'admin', 1, 405, NOW(), NOW()),
  (406, 'member.address.update', '修改地址', '用户管理', '会员详情地址 CRUD', 'admin', 1, 406, NOW(), NOW()),
  (407, 'member.sms',    '发送短信', '用户管理', '会员详情发送短信',         'admin', 1, 407, NOW(), NOW());

INSERT IGNORE INTO `menus`
  (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (912, 910, 3, '发短信', NULL, NULL, NULL, NULL, NULL, 'member.sms',    0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (913, 910, 3, '送优惠券', NULL, NULL, NULL, NULL, NULL, 'member.coupon', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (914, 910, 3, '运营备注', NULL, NULL, NULL, NULL, NULL, 'member.remark', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (915, 910, 3, '修改地址', NULL, NULL, NULL, NULL, NULL, 'member.address.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW());

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`)
SELECT 1, id, NOW(), NOW() FROM `permissions` WHERE `id` IN (404, 405, 406, 407);
