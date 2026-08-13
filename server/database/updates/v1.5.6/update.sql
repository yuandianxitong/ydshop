-- ===============================================
-- v1.5.6 用户管理：地址簿 + 账户资金
-- ===============================================
-- 注意：本版本不新增表，账户资金页复用现有 balance_logs / member_recharge_orders / distribution_withdrawals。

-- 用户管理新增菜单：地址簿、账户资金
INSERT IGNORE INTO `menus`
  (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (970, 900, 2, '地址簿',   'AddressBook',  '/member/address-book',  'member/address-book/index',  NULL, 'i-svg:map-pin',     'member.address.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 10, NOW(), NOW()),
  (971, 970, 3, '删除',      NULL, NULL, NULL, NULL, NULL, 'member.address.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (975, 900, 2, '账户资金', 'AccountFund', '/member/account-fund', 'member/account-fund/index', NULL, 'i-svg:wallet', 'member.fund.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 11, NOW(), NOW()),
  (976, 975, 3, '审核提现', NULL, NULL, NULL, NULL, NULL, 'member.fund.withdraw.approve', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (977, 975, 3, '提现打款', NULL, NULL, NULL, NULL, NULL, 'member.fund.withdraw.pay', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW());

-- 新增权限
INSERT IGNORE INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`) VALUES
  (450, 'member.address.list',          '地址簿列表',  '用户管理', '查看会员地址簿', 'admin', 1, 450, NOW(), NOW()),
  (451, 'member.address.delete',        '删除地址',    '用户管理', '删除会员地址',   'admin', 1, 451, NOW(), NOW()),
  (460, 'member.fund.list',             '账户资金',    '用户管理', '查看账户资金',   'admin', 1, 460, NOW(), NOW()),
  (461, 'member.fund.withdraw.approve', '审核提现',    '用户管理', '审核会员提现',   'admin', 1, 461, NOW(), NOW()),
  (462, 'member.fund.withdraw.pay',     '提现打款',    '用户管理', '会员提现打款',   'admin', 1, 462, NOW(), NOW());

-- ============================================================
-- AI 配置默认数据（修复：SystemConfig::setConfigValue 对不存在 key 返回 false，
-- 缺 seed 导致 AI 配置保存静默失败；同时支持新增的 enabled_drivers 持久化）
-- ============================================================
INSERT IGNORE INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
('ai.default_driver',      'openai', 'ai', 'string', 'AI 默认驱动',          'AI 默认驱动名称（openai/claude/qwen/deepseek/gemini）', NULL, NULL, 1, 1, NOW(), NOW()),
('ai.enabled_drivers',     '[]',     'ai', 'json',   'AI 启用驱动列表',      'AI 启用驱动名单（json 数组），空表示按 api_key 自动判定',     NULL, NULL, 2, 1, NOW(), NOW()),

('ai.openai.api_key',      '', 'ai', 'string', 'OpenAI API Key',  'OpenAI API Key',  NULL, NULL, 10, 1, NOW(), NOW()),
('ai.openai.model',        '', 'ai', 'string', 'OpenAI 模型',     'OpenAI 模型名称', NULL, NULL, 11, 1, NOW(), NOW()),
('ai.openai.base_url',     '', 'ai', 'string', 'OpenAI Base URL', 'OpenAI 接口地址', NULL, NULL, 12, 1, NOW(), NOW()),

('ai.claude.api_key',      '', 'ai', 'string', 'Claude API Key',  'Anthropic Claude API Key',  NULL, NULL, 20, 1, NOW(), NOW()),
('ai.claude.model',        '', 'ai', 'string', 'Claude 模型',     'Claude 模型名称', NULL, NULL, 21, 1, NOW(), NOW()),
('ai.claude.base_url',     '', 'ai', 'string', 'Claude Base URL', 'Claude 接口地址', NULL, NULL, 22, 1, NOW(), NOW()),

('ai.qwen.api_key',        '', 'ai', 'string', '通义千问 API Key',  '阿里云通义千问 API Key',  NULL, NULL, 30, 1, NOW(), NOW()),
('ai.qwen.model',          '', 'ai', 'string', '通义千问 模型',     '通义千问模型名称', NULL, NULL, 31, 1, NOW(), NOW()),
('ai.qwen.base_url',       '', 'ai', 'string', '通义千问 Base URL', '通义千问接口地址', NULL, NULL, 32, 1, NOW(), NOW()),

('ai.deepseek.api_key',    '', 'ai', 'string', 'DeepSeek API Key',  'DeepSeek API Key',  NULL, NULL, 40, 1, NOW(), NOW()),
('ai.deepseek.model',      '', 'ai', 'string', 'DeepSeek 模型',     'DeepSeek 模型名称', NULL, NULL, 41, 1, NOW(), NOW()),
('ai.deepseek.base_url',   '', 'ai', 'string', 'DeepSeek Base URL', 'DeepSeek 接口地址', NULL, NULL, 42, 1, NOW(), NOW()),

('ai.gemini.api_key',      '', 'ai', 'string', 'Gemini API Key',  'Google Gemini API Key',  NULL, NULL, 50, 1, NOW(), NOW()),
('ai.gemini.model',        '', 'ai', 'string', 'Gemini 模型',     'Gemini 模型名称', NULL, NULL, 51, 1, NOW(), NOW()),
('ai.gemini.base_url',     '', 'ai', 'string', 'Gemini Base URL', 'Gemini 接口地址', NULL, NULL, 52, 1, NOW(), NOW());

-- ============================================================
-- 财务导出权限（子项目 3）
-- ============================================================
INSERT IGNORE INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`) VALUES
  (463, 'finance.balance.export',     '导出余额流水', '财务管理', '导出会员余额流水', 'admin', 1, 463, NOW(), NOW()),
  (464, 'finance.transaction.export', '导出资金流水', '财务管理', '导出资金流水',     'admin', 1, 464, NOW(), NOW()),
  (465, 'finance.withdrawal.export',  '导出提现单',   '财务管理', '导出提现申请',     'admin', 1, 465, NOW(), NOW()),
  (466, 'finance.overview.export',    '导出月报',     '财务管理', '导出财务月报',     'admin', 1, 466, NOW(), NOW()),
  (467, 'finance.points.export',      '导出积分流水', '财务管理', '导出积分流水',     'admin', 1, 467, NOW(), NOW());

-- 关联到超级管理员角色
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`) VALUES
  (1, 463, NOW(), NOW()),
  (1, 464, NOW(), NOW()),
  (1, 465, NOW(), NOW()),
  (1, 466, NOW(), NOW()),
  (1, 467, NOW(), NOW());

-- ============================================================
-- 会员模块导出权限（子项目 4）
-- ============================================================
INSERT IGNORE INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`) VALUES
  (468, 'member.address.export', '导出地址簿',   '用户管理', '导出会员地址',         'admin', 1, 468, NOW(), NOW()),
  (469, 'member.fund.export',    '导出对账单',   '用户管理', '导出账户资金对账单',   'admin', 1, 469, NOW(), NOW());

-- 关联到超级管理员
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`) VALUES
  (1, 468, NOW(), NOW()),
  (1, 469, NOW(), NOW());

-- ============================================================
-- 物流配送 + 分销佣金导出权限（子项目 5）
-- ============================================================
INSERT IGNORE INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`) VALUES
  (470, 'delivery.order.export',          '导出配送记录', '物流配送', '导出配送记录列表', 'admin', 1, 470, NOW(), NOW()),
  (471, 'delivery.staff.export',          '导出配送员',   '物流配送', '导出配送员花名册', 'admin', 1, 471, NOW(), NOW()),
  (472, 'distribution.commission.export', '导出佣金记录', '用户管理', '导出分销佣金记录', 'admin', 1, 472, NOW(), NOW());

-- 关联到超级管理员
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`) VALUES
  (1, 470, NOW(), NOW()),
  (1, 471, NOW(), NOW()),
  (1, 472, NOW(), NOW());

-- ============================================================
-- 积分规则总览页（子项目 8）
-- ============================================================
INSERT IGNORE INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`) VALUES
  (473, 'finance.points.rules', '积分规则查看', '财务管理', '查看积分规则总览', 'admin', 1, 473, NOW(), NOW());

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`) VALUES
  (1, 473, NOW(), NOW());

INSERT IGNORE INTO `menus`
  (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (1245, 1200, 2, '积分规则', 'FinancePointsRules', '/finance/points-rules', 'finance/points-rules/index', NULL, 'i-lucide:gift', 'finance.points.rules', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW());

-- ============================================================
-- 子项目 10: 会员留存计算 — 新建 user_login_logs 表
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_login_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL COMMENT '用户ID',
  `login_at` datetime NOT NULL COMMENT '登录时间',
  `login_ip` varchar(45) DEFAULT NULL COMMENT '登录IP',
  PRIMARY KEY (`id`),
  KEY `idx_user_login_at` (`user_id`, `login_at`),
  KEY `idx_login_at` (`login_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户登录日志（用于留存计算）';

-- ============================================================
-- 子项目 11: Delivery 异常工单
-- ============================================================
CREATE TABLE IF NOT EXISTS `delivery_exception_tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_no` varchar(20) NOT NULL COMMENT '业务单号 EX20260429001',
  `type` enum('delay','wrong_delivery','complaint','reassign','weather') NOT NULL COMMENT '类型',
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open' COMMENT '状态',
  `delivery_order_id` int unsigned DEFAULT NULL COMMENT '关联配送单（可空，泛事件无关联）',
  `title` varchar(100) NOT NULL COMMENT '简述',
  `description` text DEFAULT NULL COMMENT '详情',
  `evidence` json DEFAULT NULL COMMENT '图片附件 URL 数组',
  `reporter` varchar(50) DEFAULT NULL COMMENT '报告人姓名',
  `contact` varchar(100) DEFAULT NULL COMMENT '联系方式',
  `resolution_note` text DEFAULT NULL COMMENT '解决说明',
  `handled_by` int unsigned DEFAULT NULL COMMENT '处理 admin id',
  `handled_at` datetime DEFAULT NULL COMMENT '处理时间',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ticket_no` (`ticket_no`),
  KEY `idx_type_status` (`type`, `status`),
  KEY `idx_delivery_order` (`delivery_order_id`),
  KEY `idx_handled_by` (`handled_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='配送异常工单';

INSERT IGNORE INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`) VALUES
  (474, 'delivery.exception.list',   '异常工单列表', '物流配送', '查看异常工单',     'admin', 1, 474, NOW(), NOW()),
  (475, 'delivery.exception.create', '创建异常工单', '物流配送', '创建异常工单',     'admin', 1, 475, NOW(), NOW()),
  (476, 'delivery.exception.update', '处理异常工单', '物流配送', '编辑/状态流转',   'admin', 1, 476, NOW(), NOW()),
  (477, 'delivery.exception.delete', '删除异常工单', '物流配送', '软删异常工单',     'admin', 1, 477, NOW(), NOW());

INSERT IGNORE INTO `menus`
  (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (1360, 1300, 2, '异常工单', 'DeliveryException', '/delivery/exception', 'delivery/exception/index', NULL, 'i-svg:alert-triangle', 'delivery.exception.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 6, NOW(), NOW()),
  (1361, 1360, 3, '新增',     NULL, NULL, NULL, NULL, NULL, 'delivery.exception.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1362, 1360, 3, '处理',     NULL, NULL, NULL, NULL, NULL, 'delivery.exception.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (1363, 1360, 3, '删除',     NULL, NULL, NULL, NULL, NULL, 'delivery.exception.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW());

-- ============================================================
-- 子项目 12: Delivery 实时地图
-- ============================================================
ALTER TABLE `delivery_orders`
  ADD COLUMN `dest_lat` decimal(10, 7) DEFAULT NULL COMMENT '目的地纬度（geocoded 收货地址）' AFTER `remark`,
  ADD COLUMN `dest_lng` decimal(10, 7) DEFAULT NULL COMMENT '目的地经度' AFTER `dest_lat`,
  ADD KEY `idx_dest_geo` (`dest_lat`, `dest_lng`);

ALTER TABLE `delivery_staff`
  ADD COLUMN `current_lat` decimal(10, 7) DEFAULT NULL COMMENT '当前位置纬度',
  ADD COLUMN `current_lng` decimal(10, 7) DEFAULT NULL COMMENT '当前位置经度',
  ADD COLUMN `location_updated_at` datetime DEFAULT NULL COMMENT '位置上报时间';

INSERT IGNORE INTO `system_configs`
  (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
VALUES
  ('amap.web_api_key',      '',     'amap', 'string', 'AMap Web 服务 API Key',  'AMap REST API Key（geocoding 用，仅后端使用）',     NULL, NULL, 1, 1, NOW(), NOW()),
  ('amap.js_api_key',       '',     'amap', 'string', 'AMap JS API Key',        'AMap JS API Key（前端地图渲染）',                  NULL, NULL, 2, 1, NOW(), NOW()),
  ('amap.js_security_code', '',     'amap', 'string', 'AMap JS 安全密钥',       'AMap JS 安全密钥（v2.x 起 JS API 强制）',          NULL, NULL, 3, 1, NOW(), NOW()),
  ('amap.default_city',     '北京', 'amap', 'string', 'AMap 默认城市',          'Geocoding 默认城市限定（提高识别率）',              NULL, NULL, 4, 1, NOW(), NOW());

INSERT IGNORE INTO `menus`
  (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (1370, 1300, 2, '实时地图', 'DeliveryMap', '/delivery/map', 'delivery/map/index', NULL, 'i-svg:map', 'delivery.order.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 7, NOW(), NOW());

-- ============================================================
-- 子项目 13: Delivery 排班调度
-- ============================================================
CREATE TABLE IF NOT EXISTS `delivery_shifts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int unsigned NOT NULL COMMENT '配送员ID',
  `weekday` tinyint unsigned NOT NULL COMMENT '周几 1=周一..7=周日',
  `start_time` time NOT NULL COMMENT '上班时间',
  `end_time` time NOT NULL COMMENT '下班时间',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_staff_weekday` (`staff_id`, `weekday`),
  KEY `idx_weekday_time` (`weekday`, `start_time`, `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='配送员班次（周模板）';

INSERT IGNORE INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`) VALUES
  (478, 'delivery.shift.list',   '班次列表', '物流配送', '查看配送员班次',         'admin', 1, 478, NOW(), NOW()),
  (479, 'delivery.shift.manage', '班次管理', '物流配送', '新增/编辑/删除班次',     'admin', 1, 479, NOW(), NOW());

INSERT IGNORE INTO `menus`
  (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (1380, 1300, 2, '排班管理', 'DeliveryShift', '/delivery/shift', 'delivery/shift/index', NULL, 'i-svg:calendar-clock', 'delivery.shift.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 8, NOW(), NOW()),
  (1381, 1380, 3, '管理',     NULL, NULL, NULL, NULL, NULL, 'delivery.shift.manage', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW());

-- ============================================================
-- 子项目 14: Order 批量打印面单（快递鸟对接）
-- ============================================================
INSERT IGNORE INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`) VALUES
  (484, 'order.waybill.print', '打印电子面单', '订单管理', '调用面单网关批量生成', 'admin', 1, 484, NOW(), NOW());
