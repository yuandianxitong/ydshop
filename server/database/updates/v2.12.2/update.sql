-- v2.12.2 微信支付切换为微信支付公钥模式

UPDATE `system_configs`
SET
  `config_name` = '商户API证书序列号',
  `config_desc` = '商户API证书序列号（请求签名使用）',
  `updated_at` = NOW()
WHERE `config_key` = 'pay_wechat_serial_no';

UPDATE `system_configs`
SET
  `config_name` = '商户API证书文件（已废弃）',
  `config_desc` = '微信支付公钥模式不再读取此配置，请直接填写商户API证书序列号',
  `status` = 0,
  `updated_at` = NOW()
WHERE `config_key` = 'pay_wechat_cert_path';

INSERT INTO `system_configs`
(`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
SELECT 'pay_wechat_public_key_id', '', 'payment', 'string', '微信支付公钥ID', '微信支付公钥ID（PUB_KEY_ID_...，用于应答和回调验签）', NULL, '{"field":"pay_wechat_enabled","value":"1"}', 13, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `system_configs` WHERE `config_key` = 'pay_wechat_public_key_id');

INSERT INTO `system_configs`
(`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
SELECT 'pay_wechat_public_key_path', '', 'payment', 'string', '微信支付公钥文件', '微信支付公钥 PEM 文件路径（public_key.pem）', NULL, '{"field":"pay_wechat_enabled","value":"1"}', 14, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `system_configs` WHERE `config_key` = 'pay_wechat_public_key_path');

UPDATE `system_configs`
SET `sort_order` = 15, `updated_at` = NOW()
WHERE `config_key` = 'pay_wechat_notify_url';
