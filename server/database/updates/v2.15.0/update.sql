-- v2.15.0 微信支付公钥模式完善（保留服务器文件路径配置）

-- 校正路径/公钥相关文案（强调 PUB_KEY_ID 与文件路径，非粘贴 PEM）
UPDATE `system_configs`
SET
  `config_name` = '商户API证书序列号',
  `config_desc` = '商户API证书序列号（请求 Authorization 签名使用，非微信支付公钥ID）',
  `updated_at` = NOW()
WHERE `config_key` = 'pay_wechat_serial_no';

UPDATE `system_configs`
SET
  `config_name` = '商户API私钥文件',
  `config_desc` = '商户API私钥 PEM 文件路径（apiclient_key.pem，相对 server/ 或绝对路径）',
  `updated_at` = NOW()
WHERE `config_key` = 'pay_wechat_private_key_path';

UPDATE `system_configs`
SET
  `config_name` = '微信支付公钥ID',
  `config_desc` = '微信支付公钥ID（必须以 PUB_KEY_ID_ 开头，用于应答/回调验签；请勿填平台证书序列号）',
  `updated_at` = NOW()
WHERE `config_key` = 'pay_wechat_public_key_id';

UPDATE `system_configs`
SET
  `config_name` = '微信支付公钥文件',
  `config_desc` = '微信支付公钥 PEM 文件路径（pub_key.pem，相对 server/ 或绝对路径）',
  `updated_at` = NOW()
WHERE `config_key` = 'pay_wechat_public_key_path';

-- 停用历史平台证书路径与无用的 APIv2/重复密钥项，避免运营误填
UPDATE `system_configs`
SET
  `config_name` = '商户API证书文件（已废弃）',
  `config_desc` = '公钥模式不再读取平台/商户证书文件，请配置微信支付公钥文件与公钥ID',
  `status` = 0,
  `updated_at` = NOW()
WHERE `config_key` = 'pay_wechat_cert_path';

UPDATE `system_configs`
SET
  `config_name` = '微信API密钥（已废弃）',
  `config_desc` = 'V3 支付请使用「微信APIv3密钥」，本项运行时不再读取',
  `status` = 0,
  `updated_at` = NOW()
WHERE `config_key` = 'pay_wechat_api_key';
