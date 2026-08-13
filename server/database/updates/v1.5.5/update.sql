-- ===============================================
-- v1.5.5 订单发票模块 + 发货/发票菜单
-- ===============================================

-- 发货管理 + 发票管理菜单（订单中心下）
INSERT IGNORE INTO `menus`
  (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (850, 800, 2, '发货管理', 'OrderShip', '/order/order-ship', 'order/order-ship/index', NULL, 'i-svg:truck', 'order.order', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 6, NOW(), NOW()),
  (860, 800, 2, '发票管理', 'OrderInvoice', '/order/order-invoice', 'order/order-invoice/index', NULL, 'i-svg:file-text', 'order.invoice.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 7, NOW(), NOW()),
  (861, 860, 3, '受理/开票/作废', NULL, NULL, NULL, NULL, NULL, 'order.invoice.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (862, 860, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'order.invoice.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW());

-- 发票管理权限
INSERT IGNORE INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`) VALUES
  (330, 'order.invoice.list', '发票列表', '订单中心', '查看发票列表', 'admin', 1, 330, NOW(), NOW()),
  (331, 'order.invoice.update', '发票处理', '订单中心', '受理/开票/作废', 'admin', 1, 331, NOW(), NOW()),
  (332, 'order.invoice.delete', '发票删除', '订单中心', '删除发票', 'admin', 1, 332, NOW(), NOW());

CREATE TABLE IF NOT EXISTS `order_invoices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL DEFAULT 0 COMMENT '订单ID',
  `order_no` varchar(40) NOT NULL DEFAULT '' COMMENT '订单号（冗余）',
  `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `type` varchar(20) NOT NULL DEFAULT 'personal' COMMENT '抬头类型: personal/company/vat',
  `invoice_type` varchar(20) NOT NULL DEFAULT 'electronic' COMMENT '发票形式: electronic/paper',
  `title` varchar(120) NOT NULL DEFAULT '' COMMENT '发票抬头',
  `tax_no` varchar(40) NOT NULL DEFAULT '' COMMENT '纳税人识别号',
  `bank_name` varchar(80) NOT NULL DEFAULT '' COMMENT '开户银行',
  `bank_account` varchar(80) NOT NULL DEFAULT '' COMMENT '银行账号',
  `company_address` varchar(200) NOT NULL DEFAULT '' COMMENT '注册地址',
  `company_phone` varchar(40) NOT NULL DEFAULT '' COMMENT '注册电话',
  `recipient_name` varchar(40) NOT NULL DEFAULT '' COMMENT '收件人',
  `recipient_phone` varchar(40) NOT NULL DEFAULT '' COMMENT '联系电话',
  `recipient_email` varchar(80) NOT NULL DEFAULT '' COMMENT '收件邮箱',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '开票金额',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '发票内容',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '状态: pending/processing/issued/cancelled',
  `file_url` varchar(255) NOT NULL DEFAULT '' COMMENT '开票文件 URL',
  `admin_remark` varchar(255) NOT NULL DEFAULT '' COMMENT '管理员备注',
  `issued_at` datetime DEFAULT NULL COMMENT '开票时间',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单发票';
