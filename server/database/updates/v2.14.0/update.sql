-- v2.14.0 订单拆单/合单/改价
-- 幂等设计：可重复执行不产生副作用

-- ============================================================
-- 1) order_orders 血缘字段（逐列判断，幂等）
-- ============================================================
DROP PROCEDURE IF EXISTS upgrade_order_orders_v2_14_0;

DELIMITER $$
CREATE PROCEDURE upgrade_order_orders_v2_14_0()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'order_orders' AND column_name = 'parent_order_id') THEN
    ALTER TABLE `order_orders`
      ADD COLUMN `parent_order_id` int unsigned DEFAULT NULL COMMENT '直接父订单ID：拆单=原订单；合单=存活订单（仅被合并订单设置）' AFTER `cancel_effects_error`,
      ADD COLUMN `relation_type` varchar(20) NOT NULL DEFAULT 'none' COMMENT '订单血缘：none/split_child/merge_absorbed' AFTER `parent_order_id`,
      ADD COLUMN `payment_root_order_id` int unsigned DEFAULT NULL COMMENT '支付权威根订单ID（拆单链扁平化指针，NULL=自身即根）' AFTER `relation_type`;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'order_orders' AND index_name = 'idx_parent_order_id') THEN
    ALTER TABLE `order_orders` ADD KEY `idx_parent_order_id` (`parent_order_id`);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'order_orders' AND index_name = 'idx_payment_root_order_id') THEN
    ALTER TABLE `order_orders` ADD KEY `idx_payment_root_order_id` (`payment_root_order_id`);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'order_items' AND column_name = 'split_from_item_id') THEN
    ALTER TABLE `order_items`
      ADD COLUMN `split_from_item_id` int unsigned DEFAULT NULL COMMENT '数量拆分时的来源订单项ID（整行迁移/普通行为NULL）' AFTER `refund_status`,
      ADD KEY `idx_split_from_item_id` (`split_from_item_id`);
  END IF;
END$$
DELIMITER ;

CALL upgrade_order_orders_v2_14_0();
DROP PROCEDURE IF EXISTS upgrade_order_orders_v2_14_0;

-- ============================================================
-- 2) 订单调整审计流水表
-- ============================================================
CREATE TABLE IF NOT EXISTS `order_adjust_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL COMMENT '主订单：改价=目标订单；拆单=父订单；合单=存活订单',
  `action` varchar(20) NOT NULL COMMENT '操作类型：split/merge/price_adjust',
  `related_order_ids` json DEFAULT NULL COMMENT '关联订单：拆单=[子单ID]；合单=[被合并订单ID...]',
  `admin_id` int unsigned NOT NULL COMMENT '操作管理员ID',
  `before_snapshot` json NOT NULL COMMENT '操作前关键字段快照',
  `after_snapshot` json NOT NULL COMMENT '操作后关键字段快照',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '操作原因/备注',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_action` (`action`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单拆合/改价审计流水（append-only）';

-- ============================================================
-- 3) 权限与菜单（幂等）
-- ============================================================
INSERT IGNORE INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`) VALUES
(333, 'order.split', '拆分订单', '订单中心', '已支付未发货订单拆分为多单', 'admin', 1, 333, NOW(), NOW()),
(334, 'order.merge', '合并订单', '订单中心', '同用户多笔待付款订单合并', 'admin', 1, 334, NOW(), NOW()),
(335, 'order.price-adjust', '订单改价', '订单中心', '待付款订单改价（商品单价/运费/优惠）', 'admin', 1, 335, NOW(), NOW());

INSERT IGNORE INTO `menus` (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`) VALUES
(815, 810, 3, '拆单', NULL, NULL, NULL, NULL, NULL, 'order.split', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
(816, 810, 3, '合单', NULL, NULL, NULL, NULL, NULL, 'order.merge', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW()),
(817, 810, 3, '改价', NULL, NULL, NULL, NULL, NULL, 'order.price-adjust', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 6, NOW(), NOW());

-- 授权超级管理员角色（幂等）
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`)
SELECT 1, p.id, NOW(), NOW() FROM `permissions` p
WHERE p.id IN (333, 334, 335)
  AND NOT EXISTS (SELECT 1 FROM `role_permissions` rp WHERE rp.role_id = 1 AND rp.permission_id = p.id);

INSERT INTO `role_menus` (`role_id`, `menu_id`, `created_at`, `updated_at`)
SELECT 1, m.id, NOW(), NOW() FROM `menus` m
WHERE m.id IN (815, 816, 817)
  AND NOT EXISTS (SELECT 1 FROM `role_menus` rm WHERE rm.role_id = 1 AND rm.menu_id = m.id);
