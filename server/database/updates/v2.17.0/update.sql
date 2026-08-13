-- ============================================================
-- v2.17.0：产品授权管理后台菜单与权限
-- ============================================================

INSERT INTO `permissions` (`name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
SELECT 'system.license', '产品授权', '系统管理', '产品授权管理权限', 'admin', 1, 67, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'system.license');

INSERT INTO `permissions` (`name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
SELECT 'system.license.list', '查看授权', '系统管理', '查看产品授权状态', 'admin', 1, 68, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'system.license.list');

INSERT INTO `permissions` (`name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
SELECT 'system.license.activate', '激活授权', '系统管理', '激活/校验/清除产品授权', 'admin', 1, 69, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'system.license.activate');

INSERT INTO `menus`
  (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`,
   `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`,
   `plugin_code`, `status`, `sort`, `created_at`, `updated_at`)
SELECT
  102, 2, 2, '产品授权', 'SystemLicense', '/system/license', '/system/license/index', NULL, 'i-svg:lock', 'system.license.list',
  0, 1, 0, 0, NULL, 1, NULL, NULL, NULL, 1, 10, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `id` = 102 OR `permission` = 'system.license.list');

INSERT INTO `menus`
  (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`,
   `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`,
   `plugin_code`, `status`, `sort`, `created_at`, `updated_at`)
SELECT
  103,
  COALESCE((SELECT `id` FROM `menus` WHERE `permission` = 'system.license.list' LIMIT 1), 102),
  3, '激活', NULL, NULL, NULL, NULL, NULL, 'system.license.activate',
  0, 1, 0, 0, NULL, 1, NULL, NULL, NULL, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `id` = 103 OR `permission` = 'system.license.activate');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`)
SELECT 1, p.`id`, NOW(), NOW()
FROM `permissions` p
WHERE p.`name` IN ('system.license', 'system.license.list', 'system.license.activate');

INSERT IGNORE INTO `role_menus` (`role_id`, `menu_id`, `created_at`, `updated_at`)
SELECT 1, m.`id`, NOW(), NOW()
FROM `menus` m
WHERE m.`permission` IN ('system.license.list', 'system.license.activate');
