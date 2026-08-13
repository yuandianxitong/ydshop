# v2.12.2 升级说明

本版本将微信支付 APIv3 配置统一为“微信支付公钥模式”。

## 数据库变更

- 新增系统配置 `pay_wechat_public_key_id`，填写商户平台中的微信支付公钥 ID，格式为 `PUB_KEY_ID_...`。
- 新增系统配置 `pay_wechat_public_key_path`，填写微信支付公钥 PEM 文件路径。
- 将 `pay_wechat_serial_no` 的说明修正为“商户 API 证书序列号”，该字段仍用于请求签名。
- 停用旧配置 `pay_wechat_cert_path`，运行时不再读取证书文件。

## 升级后配置

后台「系统配置 / 支付配置」中，微信支付至少需要填写：

- 微信商户号
- 微信 APIv3 密钥
- 商户 API 证书序列号
- 商户 API 私钥文件路径
- 微信支付公钥 ID
- 微信支付公钥文件路径
