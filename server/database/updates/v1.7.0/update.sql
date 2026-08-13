-- v1.7.0 升级脚本

-- 1. 新表
CREATE TABLE IF NOT EXISTS `diy_page_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL COMMENT '关联 diy_pages.id',
  `version_no` int(11) NOT NULL COMMENT '页内版本号 (1,2,3...)',
  `title` varchar(100) NOT NULL COMMENT '快照: 页面标题',
  `components` json NOT NULL COMMENT '快照: 组件树',
  `page_settings` json DEFAULT NULL COMMENT '快照: 背景设置',
  `note` varchar(255) DEFAULT NULL COMMENT '发布备注 (可选)',
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT '操作管理员 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_page` (`page_id`, `created_at`),
  KEY `idx_page_version` (`page_id`, `version_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='页面历史版本';

-- 2. 新增菜单子项 (parent_id=1061 页面装修)
INSERT IGNORE INTO `menus` (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`) VALUES
(1074, 1061, 3, '历史版本', NULL, NULL, NULL, NULL, NULL, 'diy.page.version.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 6, NOW(), NOW()),
(1081, 1061, 3, '恢复版本', NULL, NULL, NULL, NULL, NULL, 'diy.page.version.restore', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 7, NOW(), NOW());

-- 3. 给已有 diy.page.publish 角色补新权限 (1072 = diy.page.publish 子菜单)
INSERT IGNORE INTO `role_menus` (`role_id`, `menu_id`)
SELECT DISTINCT rm.role_id, m.menu_id
FROM `role_menus` rm
CROSS JOIN (SELECT 1074 AS menu_id UNION SELECT 1081) m
WHERE rm.menu_id = 1072;
