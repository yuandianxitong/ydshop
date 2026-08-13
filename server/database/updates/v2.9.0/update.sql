-- v2.9.0: 帮助中心
-- 1. 新增 2 张表
-- 2. 预置 5 个默认分类
-- 3. 新增 3 条 menus（帮助管理分组 + 帮助分类 + 帮助列表）
-- 4. super_admin 角色授权这 3 条菜单
-- 5. pc_header_menu 默认值守卫式 UPDATE：把 /article?category_id=help 改成 /help
-- 所有 INSERT 均幂等，重复执行不报错

-- 1. 建表
CREATE TABLE IF NOT EXISTS `help_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(255) DEFAULT '',
  `sort` int NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='帮助分类';

CREATE TABLE IF NOT EXISTS `helps` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `summary` varchar(500) DEFAULT '',
  `content` longtext NOT NULL,
  `view_count` int unsigned NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `admin_id` int unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='帮助';

-- 2. 预置 5 个默认分类
INSERT IGNORE INTO `help_categories` (`id`, `name`, `icon`, `sort`, `status`, `created_at`, `updated_at`) VALUES
(1, '购物问题', '', 1, 1, NOW(), NOW()),
(2, '退换货',   '', 2, 1, NOW(), NOW()),
(3, '账号问题', '', 3, 1, NOW(), NOW()),
(4, '支付问题', '', 4, 1, NOW(), NOW()),
(5, '物流配送', '', 5, 1, NOW(), NOW());

-- 3. 新增 menus（named columns，避免列数不匹配 hard fail）
INSERT IGNORE INTO `menus` (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`) VALUES
(1730, 2, 1, '帮助管理', 'ContentHelpManage', '/content/help-manage', 'LAYOUT', '/content/help', 'i-lucide:circle-help', 'content.help_manage', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 25, NOW(), NOW()),
(1731, 1730, 2, '帮助分类', 'ContentHelpCategory', '/content/help-category', '/content/help-category/index', NULL, 'i-lucide:folder-tree', 'content.help_category.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
(1732, 1730, 2, '帮助列表', 'ContentHelp', '/content/help', '/content/help/index', NULL, 'i-lucide:file-question', 'content.help.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW());

-- 4. role_menus super_admin 授权
INSERT IGNORE INTO `role_menus` (`role_id`, `menu_id`, `created_at`, `updated_at`)
VALUES
(1, 1730, NOW(), NOW()),
(1, 1731, NOW(), NOW()),
(1, 1732, NOW(), NOW());

-- 5. pc_header_menu 守卫式 UPDATE：仅老用户未自定义时（仍含旧路径）替换
UPDATE `system_configs`
SET `config_value` = REPLACE(`config_value`, '/article?category_id=help', '/help')
WHERE `config_key` = 'pc_header_menu'
  AND `config_value` LIKE '%/article?category_id=help%';
