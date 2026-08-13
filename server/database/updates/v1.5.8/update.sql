-- ===============================================
-- v1.5.8 新人礼包多礼包持久化（SP-B1）
-- ===============================================

-- 1. 新建 new_user_gifts 表
CREATE TABLE IF NOT EXISTS `new_user_gifts` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(60) NOT NULL                              COMMENT '礼包名称',
  `description`   VARCHAR(255) NOT NULL DEFAULT ''                  COMMENT '运营备注',
  `status`        TINYINT NOT NULL DEFAULT 0                        COMMENT '0禁用 1启用',
  `sort_order`    INT NOT NULL DEFAULT 0                            COMMENT '排序，小在前',
  `conditions`    JSON NULL                                         COMMENT '受众标签 JSON 数组',
  `points`        INT UNSIGNED NOT NULL DEFAULT 0                   COMMENT '赠送积分',
  `balance`       DECIMAL(10,2) NOT NULL DEFAULT 0.00               COMMENT '赠送余额',
  `coupon_ids`    JSON NULL                                         COMMENT '赠送优惠券 ID 列表',
  `valid_start`   DATETIME NULL                                     COMMENT '生效起 NULL=长期',
  `valid_end`     DATETIME NULL                                     COMMENT '生效止 NULL=长期',
  `created_at`    DATETIME DEFAULT NULL,
  `updated_at`    DATETIME DEFAULT NULL,
  `deleted_at`    DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`, `sort_order`),
  KEY `idx_valid` (`valid_start`, `valid_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='新人礼包';

-- 2. users 表加幂等标记字段
ALTER TABLE `users`
  ADD COLUMN `new_user_gift_claimed_at` DATETIME NULL DEFAULT NULL COMMENT '新人礼包发放时间' AFTER `created_at`;

-- 3. 数据迁移：seed 默认礼包（仅当老 SystemConfig new_user_gift.enabled='1' 时）
INSERT INTO `new_user_gifts` (name, description, status, sort_order, conditions, points, balance, coupon_ids, created_at, updated_at)
SELECT
  '默认新人礼包',
  '从旧版 SystemConfig 迁移',
  1,
  0,
  '["new_register"]',
  COALESCE((SELECT CAST(config_value AS UNSIGNED) FROM system_configs WHERE config_key='new_user_gift.points'), 0),
  COALESCE((SELECT CAST(config_value AS DECIMAL(10,2)) FROM system_configs WHERE config_key='new_user_gift.balance'), 0),
  COALESCE((SELECT config_value FROM system_configs WHERE config_key='new_user_gift.coupon_ids'), '[]'),
  NOW(),
  NOW()
WHERE EXISTS (SELECT 1 FROM system_configs WHERE config_key='new_user_gift.enabled' AND config_value='1');

-- 4. 删除老 SystemConfig 4 个 key
DELETE FROM `system_configs` WHERE config_key IN (
  'new_user_gift.enabled',
  'new_user_gift.points',
  'new_user_gift.balance',
  'new_user_gift.coupon_ids'
);

-- 5. 新增全局规则 seed
INSERT IGNORE INTO `system_configs` (config_key, config_value, config_group, config_type, config_name, config_desc, config_options, config_depends, sort_order, status, created_at, updated_at) VALUES
  ('new_user_gift.rules.limit_count',    '1',                                                                       'new_user_gift_rules', 'number', '领取上限', '同一用户最多领取次数',     NULL, NULL, 1, 1, NOW(), NOW()),
  ('new_user_gift.rules.scenes',         '["register_success","home_floating","campaign_landing","cart_empty"]',     'new_user_gift_rules', 'json',   '触发场景', '展示位列表',               NULL, NULL, 2, 1, NOW(), NOW()),
  ('new_user_gift.rules.delivery_mode',  'immediate',                                                                'new_user_gift_rules', 'string', '到账方式', 'immediate/claim/order',    NULL, NULL, 3, 1, NOW(), NOW()),
  ('new_user_gift.rules.risk_controls',  '["device_monthly","ip_daily","sms_verify"]',                                'new_user_gift_rules', 'json',   '风控策略', '风控开关列表',             NULL, NULL, 4, 1, NOW(), NOW());

-- 6. 新增权限
INSERT IGNORE INTO `permissions`
  (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (486, 'marketing.new_user_gift.list',   '新人礼包列表', '营销中心', '查看新人礼包列表',   'admin', 1, 486, NOW(), NOW()),
  (487, 'marketing.new_user_gift.view',   '查看新人礼包', '营销中心', '查看新人礼包配置',   'admin', 1, 487, NOW(), NOW()),
  (488, 'marketing.new_user_gift.create', '新增新人礼包', '营销中心', '新增新人礼包',       'admin', 1, 488, NOW(), NOW()),
  (489, 'marketing.new_user_gift.update', '编辑新人礼包', '营销中心', '编辑新人礼包',       'admin', 1, 489, NOW(), NOW()),
  (490, 'marketing.new_user_gift.delete', '删除新人礼包', '营销中心', '删除新人礼包',       'admin', 1, 490, NOW(), NOW());

-- 7. 关联超级管理员
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`) VALUES
  (1, 486, NOW(), NOW()),
  (1, 487, NOW(), NOW()),
  (1, 488, NOW(), NOW()),
  (1, 489, NOW(), NOW()),
  (1, 490, NOW(), NOW());
