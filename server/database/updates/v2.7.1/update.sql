-- v2.7.1: PC 头部 / 底部菜单后台化
-- 1. 给 system_configs 加 2 个 json key 默认值
-- 2. 给 menus 加 admin 入口 DiyPcMenu (id=1095)
-- 3. 给 super_admin 角色授权该菜单
-- 所有 INSERT 均幂等，重复执行不报错

-- 1. system_configs
INSERT IGNORE INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
('pc_header_menu', '[{"label":"首页","path":"/"},{"label":"热销榜单","path":"/goods?sort=sales"},{"label":"新品推荐","path":"/goods?sort=newest"},{"label":"好物优选","path":"/goods?is_recommend=1"},{"label":"限时秒杀","path":"/marketing/flash-sale"},{"label":"领券中心","path":"/marketing/coupon"},{"label":"商城资讯","path":"/article"},{"label":"帮助中心","path":"/article?category_id=help"}]', 'diy', 'json', 'PC 头部导航菜单', 'PC 商城头部导航 8 项默认配置', NULL, NULL, 4, 1, NOW(), NOW()),
('pc_footer_config', '{"columns":[{"title":"关于我们","links":[{"label":"关于元点","path":"/about"},{"label":"联系我们","path":"/contact"}]},{"title":"帮助中心","links":[{"label":"用户协议","path":"/article/agreement"},{"label":"隐私政策","path":"/article/privacy"}]},{"title":"友情链接","links":[{"label":"管理后台","path":"/admin/"}]},{"title":"联系方式","links":[{"label":"邮箱：642508814@qq.com","path":""},{"label":"微信：Vince_Dorian","path":""}]}],"copyright":"© {YEAR} 元点Shop. All rights reserved. Powered by yd-admin"}', 'diy', 'json', 'PC 底部菜单 / 版权', 'PC 商城底部 4 列 + 版权文本默认配置', NULL, NULL, 5, 1, NOW(), NOW());

-- 2. menus
INSERT IGNORE INTO `menus` (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`) VALUES
(1095, 1060, 2, 'PC 头部/底部', 'DiyPcMenu', '/diy/pc-menu', 'diy/pc-menu/index', NULL, 'i-lucide:layout-panel-top', 'diy.pc_menu.manage', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 10, NOW(), NOW());

-- 3. role_menus（super_admin）
INSERT IGNORE INTO `role_menus` (`role_id`, `menu_id`, `created_at`, `updated_at`)
VALUES (1, 1095, NOW(), NOW());
