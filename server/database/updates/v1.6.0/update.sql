-- v1.6.0 升级脚本
-- 1. diy_pages 加 is_default
ALTER TABLE `diy_pages`
  ADD COLUMN `is_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否为生效页面' AFTER `is_published`;
ALTER TABLE `diy_pages`
  ADD INDEX `idx_default` (`page_type`, `platform`, `is_default`);

-- 2. 老用户每端 home/category 仅一条,直接置 is_default=1
UPDATE `diy_pages` SET `is_default` = 1 WHERE `page_type` IN ('home', 'category');

-- 3. system_configs 替换 key (保留老用户已有 category_page_style 的值)
INSERT INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
SELECT 'category_page_style_uniapp',
       COALESCE((SELECT `config_value` FROM (SELECT `config_value` FROM `system_configs` WHERE `config_key` = 'category_page_style' LIMIT 1) AS sub), 'style1'),
       'diy', 'string', '移动端分类页骨架', '移动端分类页骨架(style1/style2/style3)', NULL, NULL, 1, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `system_configs` WHERE `config_key` = 'category_page_style_uniapp');

DELETE FROM `system_configs` WHERE `config_key` = 'category_page_style';

-- 4. 菜单清理
DELETE FROM `role_menus` WHERE `menu_id` IN (1063, 1065, 1066, 1067, 1068, 1069);
DELETE FROM `menus` WHERE `id` IN (1063, 1065, 1066, 1067, 1068, 1069);

-- 5. 主题管理 → 模板中心 (schema: title=显示名, name=路由名)
UPDATE `menus`
SET `title` = '模板中心', `name` = 'DiyTemplate', `path` = '/diy/template', `component` = 'diy/template/index', `permission` = 'diy.template.list'
WHERE `id` = 1064;

UPDATE `menus` SET `permission` = 'diy.template.create' WHERE `id` = 1075;
UPDATE `menus` SET `permission` = 'diy.template.delete' WHERE `id` = 1076;

INSERT IGNORE INTO `menus` (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`)
VALUES (1077, 1064, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'diy.template.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW());

-- 6. 分类管理 → 分类页配置 (title 是显示名)
UPDATE `menus` SET `title` = '分类页配置' WHERE `id` = 1080;

-- 7. 首页设为默认权限
INSERT IGNORE INTO `menus` (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`)
VALUES (1073, 1061, 3, '设为默认', NULL, NULL, NULL, NULL, NULL, 'diy.page.set_default', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW());

-- 8a. 老 diy.topic.* 角色补 diy.page.* 权限,避免运营登录后看不到菜单
INSERT IGNORE INTO `role_menus` (`role_id`, `menu_id`)
SELECT DISTINCT rm.role_id, m.menu_id
FROM `role_menus` rm
CROSS JOIN (SELECT 1061 AS menu_id UNION SELECT 1073) m
WHERE rm.menu_id IN (1063, 1066, 1067, 1068, 1069);

-- 8b. 补齐 diy.page.create / diy.page.delete 权限菜单 (历史遗漏)
INSERT IGNORE INTO `menus` (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`) VALUES
(1078, 1061, 3, '新建', NULL, NULL, NULL, NULL, NULL, 'diy.page.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
(1079, 1061, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'diy.page.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW());

-- 8c. 给已有 1061 (页面管理) 权限的角色补 1078/1079
INSERT IGNORE INTO `role_menus` (`role_id`, `menu_id`)
SELECT DISTINCT rm.role_id, m.menu_id
FROM `role_menus` rm
CROSS JOIN (SELECT 1078 AS menu_id UNION SELECT 1079) m
WHERE rm.menu_id = 1061;

-- 9. 8 套系统预设模板 (清理旧 is_system=1 后重建;不指定 id,避免与用户保存的模板 id 冲突)
DELETE FROM `diy_themes` WHERE `is_system` = 1;
INSERT INTO `diy_themes` (`name`, `cover`, `platform`, `page_type`, `components`, `is_system`, `sort`, `status`, `created_at`, `updated_at`) VALUES
('综合首页', '/storage/diy-themes/uniapp-home-1.png', 'uniapp', 'home', '[]', 1, 1, 1, NOW(), NOW()),
('简洁首页', '/storage/diy-themes/uniapp-home-2.png', 'uniapp', 'home', '[]', 1, 2, 1, NOW(), NOW()),
('活动专题', '/storage/diy-themes/uniapp-custom-1.png', 'uniapp', 'custom', '[]', 1, 3, 1, NOW(), NOW()),
('长图文专题', '/storage/diy-themes/uniapp-custom-2.png', 'uniapp', 'custom', '[]', 1, 4, 1, NOW(), NOW()),
('综合首页', '/storage/diy-themes/pc-home-1.png', 'pc', 'home', '[]', 1, 5, 1, NOW(), NOW()),
('简洁首页', '/storage/diy-themes/pc-home-2.png', 'pc', 'home', '[]', 1, 6, 1, NOW(), NOW()),
('活动专题', '/storage/diy-themes/pc-custom-1.png', 'pc', 'custom', '[]', 1, 7, 1, NOW(), NOW()),
('长图文专题', '/storage/diy-themes/pc-custom-2.png', 'pc', 'custom', '[]', 1, 8, 1, NOW(), NOW());
