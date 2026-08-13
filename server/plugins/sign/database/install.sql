-- plugins/sign/database/install.sql
-- 裸表名；幂等

CREATE TABLE IF NOT EXISTS `member_sign_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT '用户ID',
  `sign_date` date NOT NULL COMMENT '签到日期',
  `continuous_days` int NOT NULL DEFAULT '1' COMMENT '连续签到天数',
  `points_awarded` int NOT NULL DEFAULT '0' COMMENT '本次获得积分',
  `is_makeup` tinyint NOT NULL DEFAULT 0 COMMENT '是否补签 0否 1是',
  `source` varchar(20) NOT NULL DEFAULT 'unknown' COMMENT '签到来源 mp_weixin/h5/app/unknown',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_sign_date_unique` (`user_id`,`sign_date`),
  KEY `user_id` (`user_id`),
  KEY `idx_sign_date` (`sign_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户签到日志';

-- sign.* 配置种子（自 init.sql 迁入）
INSERT IGNORE INTO `system_configs`
  (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
VALUES
  ('sign.makeup_enabled',     '0',      'sign', 'string', '补签开关',     '是否允许用户补签 0关 1开',         NULL, NULL, 10, 1, NOW(), NOW()),
  ('sign.makeup_currency',    'points', 'sign', 'string', '补签消耗类型', 'points=积分 / balance=余额',       NULL, NULL, 11, 1, NOW(), NOW()),
  ('sign.makeup_price',       '5',      'sign', 'number', '补签单价',     '每补 1 天消耗的积分或余额',        NULL, NULL, 12, 1, NOW(), NOW()),
  ('sign.makeup_days_limit',  '7',      'sign', 'number', '补签时限',     '允许补签的最大天数（距今）',       NULL, NULL, 13, 1, NOW(), NOW());
