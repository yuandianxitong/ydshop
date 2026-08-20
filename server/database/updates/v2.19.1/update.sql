-- v2.19.1：隐藏「云编译」菜单（安装不再入队，页面保留供 CI 手动重建）

UPDATE `menus`
SET `is_hidden` = 1, `status` = 0, `updated_at` = NOW()
WHERE `name` = 'PluginBuilds' OR `id` = 1822;
