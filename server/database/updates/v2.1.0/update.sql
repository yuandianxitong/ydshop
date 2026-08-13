-- v2.1.0 增量升级脚本
-- 适用：从 v2.0.x 升级到 v2.1.0

-- ─────────────────────────────────────────────────────────────────────────
-- order_invoices: 一单一票，加 UNIQUE KEY 兜底（防止 race condition 重复开票）
-- ─────────────────────────────────────────────────────────────────────────
ALTER TABLE `order_invoices` DROP INDEX `idx_order_id`;
ALTER TABLE `order_invoices` ADD UNIQUE KEY `uk_order_id` (`order_id`);
