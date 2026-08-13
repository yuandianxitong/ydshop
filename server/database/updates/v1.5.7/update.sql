-- ===============================================
-- v1.5.7 签到配置数据补齐（SP-A）
-- ===============================================

-- 1. member_sign_logs 加 is_makeup / source 字段 + 索引
ALTER TABLE `member_sign_logs`
  ADD COLUMN `is_makeup` TINYINT NOT NULL DEFAULT 0 COMMENT '是否补签 0否 1是' AFTER `points_awarded`,
  ADD COLUMN `source` VARCHAR(20) NOT NULL DEFAULT 'unknown' COMMENT '签到来源 mp_weixin/h5/app/unknown' AFTER `is_makeup`,
  ADD INDEX `idx_sign_date` (`sign_date`);

-- 2. 补签配置 seed
INSERT IGNORE INTO `system_configs`
  (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
VALUES
  ('sign.makeup_enabled',     '0',      'sign', 'string', '补签开关',     '是否允许用户补签 0关 1开',         NULL, NULL, 10, 1, NOW(), NOW()),
  ('sign.makeup_currency',    'points', 'sign', 'string', '补签消耗类型', 'points=积分 / balance=余额',       NULL, NULL, 11, 1, NOW(), NOW()),
  ('sign.makeup_price',       '5',      'sign', 'number', '补签单价',     '每补 1 天消耗的积分或余额',        NULL, NULL, 12, 1, NOW(), NOW()),
  ('sign.makeup_days_limit',  '7',      'sign', 'number', '补签时限',     '允许补签的最大天数（距今）',       NULL, NULL, 13, 1, NOW(), NOW());

-- 3. 后台签到记录查看权限
INSERT IGNORE INTO `permissions`
  (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (485, 'marketing.sign.logs.view', '签到记录查看', '营销中心', '查看签到记录列表与统计', 'admin', 1, 485, NOW(), NOW());

-- 关联超级管理员角色
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`)
VALUES (1, 485, NOW(), NOW());
