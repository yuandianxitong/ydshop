-- ============================================================
-- 新增签到日志表 member_sign_logs
-- ============================================================

CREATE TABLE IF NOT EXISTS `member_sign_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `sign_date` date NOT NULL COMMENT '签到日期',
  `continuous_days` int(11) NOT NULL DEFAULT '1' COMMENT '连续签到天数',
  `points_awarded` int(11) NOT NULL DEFAULT '0' COMMENT '本次获得积分',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_sign_date_unique` (`user_id`,`sign_date`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户签到日志';
