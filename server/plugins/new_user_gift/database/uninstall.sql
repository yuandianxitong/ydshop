-- plugins/new_user_gift/database/uninstall.sql
-- 仅 purge 调用
-- 可移除 users.new_user_gift_claimed_at；不得触及 order_items.flash_item_id

DROP TABLE IF EXISTS `new_user_gift_logs`;
DROP TABLE IF EXISTS `new_user_gifts`;

-- 幂等删除插件专属扩展列（同 install 的 PREPARE 模式）
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='new_user_gift_claimed_at');
SET @sql := IF(@col>0,
  'ALTER TABLE `users` DROP COLUMN `new_user_gift_claimed_at`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
