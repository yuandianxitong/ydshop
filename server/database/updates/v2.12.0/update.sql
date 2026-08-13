-- v2.12.0 插件命名统一
-- 将旧的 applications / application_install_logs 表升级为 plugins / plugin_install_logs。

DROP PROCEDURE IF EXISTS rename_plugin_runtime_tables;

DELIMITER $$
CREATE PROCEDURE rename_plugin_runtime_tables()
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'applications'
  ) AND NOT EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'plugins'
  ) THEN
    RENAME TABLE `applications` TO `plugins`;
  END IF;

  IF EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'application_install_logs'
  ) AND NOT EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'plugin_install_logs'
  ) THEN
    RENAME TABLE `application_install_logs` TO `plugin_install_logs`;
  END IF;
END$$
DELIMITER ;

CALL rename_plugin_runtime_tables();
DROP PROCEDURE IF EXISTS rename_plugin_runtime_tables;

UPDATE `menus`
SET `title` = '插件管理',
    `name` = 'Plugin',
    `path` = '/plugins',
    `redirect` = '/plugins/installed',
    `permission` = 'plugin.installed'
WHERE `id` = 8;

UPDATE `menus`
SET `title` = '已安装插件',
    `name` = 'PluginInstalled',
    `path` = '/plugins/installed',
    `component` = '/plugins/installed/index',
    `permission` = 'plugin.installed'
WHERE `id` = 1820;

UPDATE `menus`
SET `title` = '插件市场',
    `name` = 'PluginMarket',
    `path` = '/plugins/market',
    `component` = '/plugins/market/index',
    `permission` = 'plugin.market'
WHERE `id` = 1821;

UPDATE `permissions`
SET `name` = 'plugin.installed'
WHERE `name` = 'app.installed'
  AND NOT EXISTS (
    SELECT 1 FROM (SELECT `id` FROM `permissions` WHERE `name` = 'plugin.installed') AS existing_permission
  );

UPDATE `permissions`
SET `title` = '插件管理-已安装',
    `group` = '插件管理',
    `description` = '插件管理-已安装'
WHERE `name` = 'plugin.installed';

UPDATE `permissions`
SET `name` = 'plugin.market'
WHERE `name` = 'app.market'
  AND NOT EXISTS (
    SELECT 1 FROM (SELECT `id` FROM `permissions` WHERE `name` = 'plugin.market') AS existing_permission
  );

UPDATE `permissions`
SET `title` = '插件管理-市场',
    `group` = '插件管理',
    `description` = '插件管理-市场'
WHERE `name` = 'plugin.market';

UPDATE `plugins`
SET `parent_menu` = 'Plugin'
WHERE `parent_menu` = 'Application';
