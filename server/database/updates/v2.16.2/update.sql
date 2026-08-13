-- v2.16.2 订单列表：删除订单权限与菜单按钮

INSERT IGNORE INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`) VALUES
(336, 'order.delete', '删除订单', '订单中心', '软删除已取消/已关闭订单', 'admin', 1, 336, NOW(), NOW());

INSERT IGNORE INTO `menus` (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`) VALUES
(818, 810, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'order.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 7, NOW(), NOW());

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`)
SELECT 1, p.id, NOW(), NOW() FROM `permissions` p
WHERE p.id IN (336)
  AND NOT EXISTS (SELECT 1 FROM `role_permissions` rp WHERE rp.role_id = 1 AND rp.permission_id = p.id);

INSERT INTO `role_menus` (`role_id`, `menu_id`, `created_at`, `updated_at`)
SELECT 1, m.id, NOW(), NOW() FROM `menus` m
WHERE m.id IN (818)
  AND NOT EXISTS (SELECT 1 FROM `role_menus` rm WHERE rm.role_id = 1 AND rm.menu_id = m.id);
