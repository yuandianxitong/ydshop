-- v2.4.0 阶段 2 交易主路径重做 + 自提全栈
-- 新增 stores 表
CREATE TABLE IF NOT EXISTS `stores` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '门店名',
  `code` varchar(32) NOT NULL DEFAULT '' COMMENT '门店编码',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '地址',
  `lng` decimal(10,7) NOT NULL DEFAULT 0 COMMENT '经度',
  `lat` decimal(10,7) NOT NULL DEFAULT 0 COMMENT '纬度',
  `phone` varchar(20) DEFAULT NULL COMMENT '电话',
  `business_hours` json DEFAULT NULL COMMENT '营业时间 JSON',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
  `sort` int NOT NULL DEFAULT 100 COMMENT '排序',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='门店';

-- 订单表加自提字段
ALTER TABLE `order_orders`
  ADD COLUMN `delivery_type` varchar(20) NOT NULL DEFAULT 'express' COMMENT '配送方式 express|merchant|pickup' AFTER `status`,
  ADD COLUMN `pickup_store_id` int unsigned DEFAULT NULL COMMENT '自提门店 ID' AFTER `delivery_type`,
  ADD COLUMN `pickup_code` varchar(8) DEFAULT NULL COMMENT '自提码' AFTER `pickup_store_id`,
  ADD COLUMN `pickup_at` datetime DEFAULT NULL COMMENT '核销时间' AFTER `pickup_code`,
  ADD COLUMN `pickup_verified_by` int unsigned DEFAULT NULL COMMENT '核销人 admin id' AFTER `pickup_at`,
  ADD COLUMN `pickup_status` varchar(20) DEFAULT NULL COMMENT 'pending|verified|timeout|cancelled' AFTER `pickup_verified_by`,
  ADD COLUMN `pickup_timeout_at` datetime DEFAULT NULL COMMENT '超时时间点' AFTER `pickup_status`,
  ADD KEY `idx_pickup` (`pickup_store_id`, `pickup_status`, `pickup_at`);

-- 商品表加配送方式字段（实际表名为 goods_spu）
ALTER TABLE `goods_spu`
  ADD COLUMN `delivery_modes` json DEFAULT NULL COMMENT '支持的配送方式数组，NULL 视为 [express]' AFTER `status`;

-- 系统配置：自提超时天数 + 默认城市坐标
INSERT IGNORE INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
VALUES
  ('pickup_timeout_days', '7', 'pickup', 'number', '自提超时天数', '下单后多少天未核销自动置超时', NULL, NULL, 100, 1, NOW(), NOW()),
  ('default_city_lng', '116.4074', 'pickup', 'string', '默认城市经度', '用户拒绝定位时降级使用', NULL, NULL, 200, 1, NOW(), NOW()),
  ('default_city_lat', '39.9042', 'pickup', 'string', '默认城市纬度', '用户拒绝定位时降级使用', NULL, NULL, 300, 1, NOW(), NOW());

-- 配送方式总开关（local-config 页用，原项目漏 seed）
INSERT IGNORE INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
  ('delivery_mode_express_enabled', '1', 'local_delivery', 'boolean', '启用快递配送', '系统内置快递方式总开关', NULL, NULL, 0, 1, NOW(), NOW()),
  ('delivery_mode_pickup_enabled',  '1', 'local_delivery', 'boolean', '启用到店自提', '系统内置自提方式总开关（与 v2.4.0 自提门店模块联动）', NULL, NULL, 0, 1, NOW(), NOW()),
  ('delivery_mode_offline_enabled', '0', 'local_delivery', 'boolean', '启用线下自提', '系统内置仓库自取总开关（B 端预约制）', NULL, NULL, 0, 1, NOW(), NOW());

-- 门店管理菜单（升级老库用，新装走 init.sql）
INSERT IGNORE INTO `menus` (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`) VALUES
  (1400, 0, 1, '门店管理', 'Store', '/store', 'LAYOUT', '/store/index', 'i-svg:store', 'store', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 32, NOW(), NOW()),
  (1410, 1400, 2, '门店列表', 'StoreIndex', '/store/index', 'store/index', NULL, 'i-svg:layout-list', 'store.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1411, 1410, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'store.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1412, 1410, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'store.edit', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (1413, 1410, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'store.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (1414, 1410, 3, '订单核销', NULL, NULL, NULL, NULL, NULL, 'order.pickup.verify', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW());

-- 同步给现有所有角色（参照 v1.7.0 模式）
INSERT IGNORE INTO `role_menus` (`role_id`, `menu_id`)
SELECT DISTINCT rm.role_id, m.id
FROM `role_menus` rm
CROSS JOIN `menus` m
WHERE m.id IN (1400, 1410, 1411, 1412, 1413, 1414);

-- 菜单层级调整：内容管理 + 文章资讯 下沉到应用管理（id=8）下
UPDATE `menus` SET `parent_id`=8, `sort`=10 WHERE `id`=7;
UPDATE `menus` SET `parent_id`=8, `sort`=11 WHERE `id`=1725;

-- ─── 2026-05-05 同城配送闭环：地址加经纬度 + 配送配置补公里费 ───────────────────────

-- 收货地址加经纬度（为同城配送距离计算服务）
ALTER TABLE `member_addresses`
    ADD COLUMN `lng` DECIMAL(10,6) NULL COMMENT '经度' AFTER `detail`,
    ADD COLUMN `lat` DECIMAL(10,6) NULL COMMENT '纬度' AFTER `lng`;

-- 同城配送：每公里费率配置
INSERT INTO `system_configs` (`config_group`, `config_key`, `config_value`, `config_name`, `config_desc`, `config_type`, `sort_order`)
VALUES ('local_delivery', 'local_delivery_per_km_fee', '1', '每公里费', '同城配送在配送范围内每公里追加的费用（元）', 'number', 30)
ON DUPLICATE KEY UPDATE `config_value`=`config_value`;
