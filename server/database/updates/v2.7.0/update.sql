-- ============================================================
-- v2.7.0 升级脚本（插件管理 / 插件运行时重构）
-- 第一部分：schema 变更
-- ============================================================

-- 1.1 给 menus 加 plugin_code
ALTER TABLE `menus`
  ADD COLUMN `plugin_code` varchar(64) DEFAULT NULL COMMENT '所属插件；NULL=系统菜单' AFTER `meta`,
  ADD KEY `idx_plugin_code` (`plugin_code`);

-- 1.2 给 permissions 加 plugin_code
ALTER TABLE `permissions`
  ADD COLUMN `plugin_code` varchar(64) DEFAULT NULL COMMENT '所属插件；NULL=系统权限' AFTER `description`,
  ADD KEY `idx_plugin_code` (`plugin_code`);

-- 1.3 plugins 表
CREATE TABLE IF NOT EXISTS `plugins` (
  `code`         varchar(64)  NOT NULL COMMENT '插件唯一标识（与目录名一致）',
  `name`         varchar(60)  NOT NULL COMMENT '显示名',
  `version`      varchar(32)  NOT NULL COMMENT '当前已安装版本',
  `category`     varchar(32)  NOT NULL COMMENT 'core|channel_market|value_added',
  `parent_menu`  varchar(60)  NOT NULL COMMENT '父级菜单 name',
  `description`  varchar(500) DEFAULT '',
  `author`       varchar(60)  DEFAULT '',
  `icon`         varchar(255) DEFAULT '' COMMENT '相对插件目录的图标路径',
  `palette`      json         DEFAULT NULL COMMENT '[hex, hex] 渐变色',
  `recommended`  tinyint(1)   NOT NULL DEFAULT 0,
  `source`       varchar(16)  NOT NULL COMMENT 'bundled|downloaded',
  `status`       varchar(16)  NOT NULL DEFAULT 'installed' COMMENT 'installed|disabled',
  `manifest`     json         NOT NULL COMMENT '完整清单快照',
  `installed_at` datetime     NOT NULL,
  `upgraded_at`  datetime     DEFAULT NULL,
  `created_at`   datetime     NOT NULL,
  `updated_at`   datetime     NOT NULL,
  PRIMARY KEY (`code`),
  KEY `idx_category` (`category`),
  KEY `idx_status`   (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='已安装插件注册表';

-- 1.4 plugin_install_logs 表（只追加）
CREATE TABLE IF NOT EXISTS `plugin_install_logs` (
  `id`           bigint unsigned NOT NULL AUTO_INCREMENT,
  `plugin_code`  varchar(64)   NOT NULL,
  `action`       varchar(16)   NOT NULL COMMENT 'install|uninstall|upgrade|enable|disable',
  `version_from` varchar(32)   DEFAULT NULL,
  `version_to`   varchar(32)   DEFAULT NULL,
  `status`       varchar(16)   NOT NULL COMMENT 'success|failed',
  `message`      text          DEFAULT NULL,
  `operator_id`  bigint unsigned DEFAULT NULL COMMENT '操作管理员ID',
  `created_at`   datetime      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_plugin` (`plugin_code`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='插件安装审计日志';

-- 1.5 plugin_migrations 表
CREATE TABLE IF NOT EXISTS `plugin_migrations` (
  `id`          bigint unsigned NOT NULL AUTO_INCREMENT,
  `plugin_code` varchar(64) NOT NULL,
  `version`     varchar(32) NOT NULL,
  `executed_at` datetime    NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plugin_version` (`plugin_code`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='插件 migration 执行记录';

-- ============================================================
-- 第二部分：菜单重排（一级菜单 + 插件管理子菜单）
-- ============================================================

-- 2.1 新建「渠道管理」一级菜单 (id=16)
INSERT INTO `menus`
  (id, parent_id, type, title, name, path, component, redirect, icon, permission, is_hidden, is_cache, is_affix, is_iframe, external_link, breadcrumb, active_menu, meta, plugin_code, status, sort, created_at, updated_at)
VALUES
  (16, 0, 1, '渠道管理', 'Channel', '/channel', 'LAYOUT', '/channel/official', 'i-lucide:radio-tower', NULL, 0, 1, 0, 0, NULL, 1, NULL, NULL, NULL, 1, 70, NOW(), NOW());

-- 2.2 5 项归位：公众号/小程序/开放平台 → 渠道管理；区域管理 → 物流配送；应用版本 → 系统管理
UPDATE `menus` SET `parent_id` = 16   WHERE `id` IN (5, 6, 15);
UPDATE `menus` SET `parent_id` = 1300 WHERE `id` = 1800;
UPDATE `menus` SET `parent_id` = 2    WHERE `id` = 1810;

-- 2.3 一级菜单 sort 调整
UPDATE `menus` SET `sort` = 50 WHERE `id` = 1000;
UPDATE `menus` SET `sort` = 60 WHERE `id` = 8;
UPDATE `menus` SET `sort` = 70 WHERE `id` = 16;
UPDATE `menus` SET `sort` = 80 WHERE `id` = 1060;
UPDATE `menus` SET `sort` = 90 WHERE `id` = 1100;

-- 2.4 插件管理下新增 2 个固定子菜单：已安装插件 / 插件市场
-- 使用 1820/1821（50/51 被 SystemMenu 占用）
INSERT INTO `menus`
  (id, parent_id, type, title, name, path, component, redirect, icon, permission, is_hidden, is_cache, is_affix, is_iframe, external_link, breadcrumb, active_menu, meta, plugin_code, status, sort, created_at, updated_at)
VALUES
  (1820, 8, 2, '已安装插件', 'PluginInstalled', '/plugins/installed', '/plugins/installed/index', NULL, 'i-lucide:layout-grid',   'plugin.installed', 0, 1, 0, 0, NULL, 1, NULL, NULL, NULL, 1, 1, NOW(), NOW()),
  (1821, 8, 2, '插件市场',   'PluginMarket',    '/plugins/market',    '/plugins/market/index',    NULL, 'i-lucide:shopping-bag', 'plugin.market',    0, 1, 0, 0, NULL, 1, NULL, NULL, NULL, 1, 2, NOW(), NOW());

-- 2.5 插件管理 name/redirect 改新落点
UPDATE `menus` SET `title` = '插件管理', `name` = 'Plugin', `path` = '/plugins', `redirect` = '/plugins/installed', `permission` = 'plugin.installed' WHERE `id` = 8;

-- 2.6 给两个新菜单的 permission 插入 permissions 表（不属任何插件）
INSERT INTO `permissions` (id, name, title, `group`, description, guard_name, status, sort, created_at, updated_at) VALUES
  (1820, 'plugin.installed', '插件管理-已安装', 'system', '插件管理-已安装', 'admin', 1, 1, NOW(), NOW()),
  (1821, 'plugin.market',    '插件管理-市场',   'system', '插件管理-市场',   'admin', 1, 2, NOW(), NOW());

-- 2.7 授予超级管理员（role_id=1）新菜单和新权限
INSERT IGNORE INTO `role_menus` (role_id, menu_id, created_at, updated_at)
  SELECT 1, id, NOW(), NOW() FROM `menus` WHERE `id` IN (16, 1820, 1821);

INSERT IGNORE INTO `role_permissions` (role_id, permission_id, created_at, updated_at)
  SELECT 1, id, NOW(), NOW() FROM `permissions` WHERE `name` IN ('plugin.installed', 'plugin.market');
