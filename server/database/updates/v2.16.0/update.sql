-- v2.16.0 电子面单模版 + Lodop 打印机配置

CREATE TABLE IF NOT EXISTS `waybill_templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '模版名称',
  `express_code` varchar(50) NOT NULL DEFAULT '' COMMENT '快递鸟 ShipperCode',
  `express_name` varchar(50) NOT NULL DEFAULT '' COMMENT '物流公司名称',
  `exp_type` varchar(20) NOT NULL DEFAULT '1' COMMENT '业务类型 ExpType',
  `exp_type_name` varchar(50) NOT NULL DEFAULT '' COMMENT '业务类型名称',
  `template_size` varchar(20) NOT NULL DEFAULT '' COMMENT '模版尺寸 TemplateSize，空=默认',
  `template_size_label` varchar(50) NOT NULL DEFAULT '' COMMENT '模版尺寸展示名',
  `pay_type` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '邮费支付方式 PayType:1现付,2到付,3月结',
  `need_pickup` tinyint(1) NOT NULL DEFAULT 0 COMMENT '快递员上门揽件:1是,0否（映射 IsNotice 0/1）',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认模版:1是,0否',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:1启用,0禁用',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_express_code` (`express_code`),
  KEY `idx_status_sort` (`status`, `sort`),
  KEY `idx_is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='电子面单模版';

-- 校正快递鸟凭证文案：waybill_app_key = EBusinessID，waybill_app_secret = AppKey
UPDATE `system_configs`
SET
  `config_name` = '用户 ID（EBusinessID）',
  `config_desc` = '快递鸟用户 ID（EBusinessID）',
  `updated_at` = NOW()
WHERE `config_key` = 'waybill_app_key';

UPDATE `system_configs`
SET
  `config_name` = 'API Key（AppKey）',
  `config_desc` = '快递鸟 API Key（AppKey），用于签名',
  `updated_at` = NOW()
WHERE `config_key` = 'waybill_app_secret';

INSERT INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
SELECT 'waybill_lodop_enabled', '1', 'waybill', 'boolean', '启用 Lodop 打印', '启用后优先通过 C-Lodop 打印电子面单 HTML', NULL, NULL, 20, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `system_configs` WHERE `config_key` = 'waybill_lodop_enabled');

INSERT INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
SELECT 'waybill_lodop_http_port', '8000', 'waybill', 'string', 'Lodop HTTP 端口', 'C-Lodop HTTP 服务端口，默认 8000（备用可试 18000）', NULL, NULL, 21, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `system_configs` WHERE `config_key` = 'waybill_lodop_http_port');

INSERT INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
SELECT 'waybill_lodop_https_port', '8443', 'waybill', 'string', 'Lodop HTTPS 端口', 'C-Lodop HTTPS 服务端口，默认 8443', NULL, NULL, 22, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `system_configs` WHERE `config_key` = 'waybill_lodop_https_port');
