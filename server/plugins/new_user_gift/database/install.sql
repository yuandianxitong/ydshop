-- plugins/new_user_gift/database/install.sql
-- 裸表名；幂等
-- users.new_user_gift_claimed_at：用 INFORMATION_SCHEMA + PREPARE（同 v2.10.0）避免重复 ADD

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

CREATE TABLE IF NOT EXISTS `new_user_gift_logs` (
  `id`              int unsigned NOT NULL AUTO_INCREMENT,
  `user_id`         int unsigned NOT NULL COMMENT '用户ID',
  `gift_id`         int unsigned NOT NULL COMMENT '礼包ID',
  `gift_name`       varchar(60) NOT NULL COMMENT '礼包名快照',
  `points_awarded`  int unsigned NOT NULL DEFAULT 0 COMMENT '赠送积分',
  `balance_awarded` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '赠送余额',
  `coupon_ids`      json DEFAULT NULL COMMENT '优惠券快照',
  `created_at`      datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_gift_id` (`gift_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='新人礼包发放记录';

-- 幂等添加核心表扩展列（SqlRunner 不支持 DELIMITER；沿用框架 updates/v2.10.0 模式）
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='new_user_gift_claimed_at');
SET @sql := IF(@col=0,
  'ALTER TABLE `users` ADD COLUMN `new_user_gift_claimed_at` datetime DEFAULT NULL COMMENT ''新人礼包发放时间'' AFTER `created_at`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
