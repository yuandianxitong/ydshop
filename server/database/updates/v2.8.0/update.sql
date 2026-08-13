-- v2.8.0: 营销 - 广告 / 广告位管理
-- 1. 新增 2 张表 (CREATE TABLE IF NOT EXISTS)
-- 2. 预置 3 个广告位
-- 3. 新增 3 条 menus (广告管理分组 + 广告位 + 广告)
-- 4. super_admin 角色授权这 3 条菜单
-- 所有 INSERT 均幂等，重复执行不报错

-- 1. 建表
CREATE TABLE IF NOT EXISTS `marketing_ad_positions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL COMMENT '位置编码',
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT '',
  `recommended_width` smallint unsigned DEFAULT 0,
  `recommended_height` smallint unsigned DEFAULT 0,
  `is_carousel` tinyint(1) NOT NULL DEFAULT 1,
  `sort` int NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告位';

CREATE TABLE IF NOT EXISTS `marketing_ads` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `position_id` int unsigned NOT NULL,
  `title` varchar(100) NOT NULL,
  `image` varchar(500) NOT NULL,
  `link` varchar(500) DEFAULT '',
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `sort` int NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_position_status` (`position_id`, `status`),
  KEY `idx_time_range` (`start_at`, `end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告';

-- 2. 预置 3 广告位
INSERT IGNORE INTO `marketing_ad_positions` (`code`, `name`, `description`, `recommended_width`, `recommended_height`, `is_carousel`, `sort`, `status`, `created_at`, `updated_at`) VALUES
('home_top_banner',     '首页顶部 Banner',  '首页大屏轮播，1200×460',     1200, 460, 1, 1, 1, NOW(), NOW()),
('goods_list_side_1',   '商品列表页侧栏 1',  '商品列表页右侧 240×360',      240, 360, 0, 2, 1, NOW(), NOW()),
('cart_footer_promo',   '购物车页底部推荐',  '购物车页底部横幅 1200×120', 1200, 120, 0, 3, 1, NOW(), NOW());

-- 3. 新增 menus（named columns，避免 v2.7.1 列数不匹配 hard fail）
INSERT IGNORE INTO `menus` (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`) VALUES
(1050, 1000, 1, '广告管理', 'AdManage', '/marketing/ad-manage', 'LAYOUT', '/marketing/ad-position', 'i-lucide:image', 'marketing.ad_manage', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 50, NOW(), NOW()),
(1051, 1050, 2, '广告位', 'MarketingAdPosition', '/marketing/ad-position', 'marketing/ad-position/index', NULL, 'i-lucide:layout-grid', 'marketing.ad_position.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
(1052, 1050, 2, '广告', 'MarketingAd', '/marketing/ad', 'marketing/ad/index', NULL, 'i-lucide:image-plus', 'marketing.ad.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW());

-- 4. role_menus super_admin 授权
INSERT IGNORE INTO `role_menus` (`role_id`, `menu_id`, `created_at`, `updated_at`)
VALUES
(1, 1050, NOW(), NOW()),
(1, 1051, NOW(), NOW()),
(1, 1052, NOW(), NOW());
