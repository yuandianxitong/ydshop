-- ===============================================
-- v1.5.9 新人礼包 KPI + 领取记录（SP-B2）
-- ===============================================

-- 1. 新建 new_user_gift_logs 表
CREATE TABLE IF NOT EXISTS `new_user_gift_logs` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`         INT UNSIGNED NOT NULL                            COMMENT '用户ID',
  `gift_id`         INT UNSIGNED NOT NULL                            COMMENT '礼包ID',
  `gift_name`       VARCHAR(60) NOT NULL                             COMMENT '礼包名快照',
  `points_awarded`  INT UNSIGNED NOT NULL DEFAULT 0                  COMMENT '赠送积分',
  `balance_awarded` DECIMAL(10,2) NOT NULL DEFAULT 0.00              COMMENT '赠送余额',
  `coupon_ids`      JSON NULL                                        COMMENT '优惠券快照',
  `created_at`      DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id`    (`user_id`),
  KEY `idx_gift_id`    (`gift_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='新人礼包发放记录';
