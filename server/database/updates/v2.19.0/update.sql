-- v2.19.0：插件云编译队列 + 客户端发布

CREATE TABLE IF NOT EXISTS `plugin_builds` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `target` varchar(20) NOT NULL COMMENT 'admin|pc',
  `trigger` varchar(30) NOT NULL COMMENT 'install|upgrade|uninstall|manual',
  `plugin_code` varchar(64) DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '0queued 1running 2success 3failed 5skipped',
  `log` longtext,
  `artifact_path` varchar(255) NOT NULL DEFAULT '',
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `operator_id` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_plugin` (`plugin_code`),
  KEY `idx_target_status` (`target`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='后台/PC 云编译任务';

CREATE TABLE IF NOT EXISTS `mobile_builds` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `platform` varchar(20) NOT NULL COMMENT 'h5|mp-weixin',
  `trigger` varchar(30) NOT NULL,
  `plugin_code` varchar(64) DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '0queued 1running 2success 3failed 4uploaded 5skipped',
  `log` longtext,
  `artifact_path` varchar(255) NOT NULL DEFAULT '',
  `upload_result_json` longtext,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `operator_id` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_plugin` (`plugin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='H5/小程序渠道编译任务';

CREATE TABLE IF NOT EXISTS `mobile_channel_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `wechat_appid` varchar(64) NOT NULL DEFAULT '',
  `wechat_upload_key` text,
  `wechat_upload_version` varchar(32) NOT NULL DEFAULT '1.0.0',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='小程序 CI 上传配置（单店一份）';

INSERT INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
SELECT 1822, 'plugin.build', '插件管理-云编译', '插件管理', '查看后台/PC 云编译任务', 'admin', 1, 1822, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `id` = 1822 OR `name` = 'plugin.build');

INSERT INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
SELECT 1823, 'plugin.build.rebuild', '插件管理-重建前端', '插件管理', '手动重建 admin/PC', 'admin', 1, 1823, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `id` = 1823 OR `name` = 'plugin.build.rebuild');

INSERT INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
SELECT 1824, 'mobile.build', '插件管理-客户端发布', '插件管理', '查看 H5/小程序编译', 'admin', 1, 1824, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `id` = 1824 OR `name` = 'mobile.build');

INSERT INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
SELECT 1825, 'mobile.build.upload', '插件管理-上传小程序', '插件管理', 'miniprogram-ci 上传开发版', 'admin', 1, 1825, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `id` = 1825 OR `name` = 'mobile.build.upload');

INSERT INTO `menus`
  (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`,
   `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`,
   `plugin_code`, `status`, `sort`, `created_at`, `updated_at`)
SELECT 1822, 8, 2, '云编译', 'PluginBuilds', '/plugins/builds', '/plugins/builds/index', NULL, 'i-lucide:cloud', 'plugin.build',
  0, 1, 0, 0, NULL, 1, NULL, NULL, NULL, 1, 3, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `id` = 1822 OR `name` = 'PluginBuilds');

INSERT INTO `menus`
  (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`,
   `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`,
   `plugin_code`, `status`, `sort`, `created_at`, `updated_at`)
SELECT 1824, 8, 2, '客户端发布', 'PluginMobileBuilds', '/plugins/mobile-builds', '/plugins/mobile-builds/index', NULL, 'i-lucide:smartphone', 'mobile.build',
  0, 1, 0, 0, NULL, 1, NULL, NULL, NULL, 1, 4, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `id` = 1824 OR `name` = 'PluginMobileBuilds');

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`)
SELECT 1, p.id, NOW(), NOW() FROM `permissions` p
WHERE p.`name` IN ('plugin.build', 'plugin.build.rebuild', 'mobile.build', 'mobile.build.upload')
  AND NOT EXISTS (SELECT 1 FROM `role_permissions` rp WHERE rp.role_id = 1 AND rp.permission_id = p.id);

INSERT INTO `role_menus` (`role_id`, `menu_id`, `created_at`, `updated_at`)
SELECT 1, m.id, NOW(), NOW() FROM `menus` m
WHERE m.`name` IN ('PluginBuilds', 'PluginMobileBuilds')
  AND NOT EXISTS (SELECT 1 FROM `role_menus` rm WHERE rm.role_id = 1 AND rm.menu_id = m.id);
