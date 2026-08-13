# v2.15.0 升级说明

## 微信支付公钥模式完善（保留文件路径）

本版本完善微信支付「公钥模式」运行时行为，**配置仍使用服务器文件路径**（不把 PEM 正文写入数据库）：

- 预下单等 APIv3 请求自动声明 `Wechatpay-Serial: PUB_KEY_ID_...`，避免微信改用平台证书签名应答导致验签失败。
- 凭证装配抽离为 `WechatPayCredential`，强制校验公钥 ID 以 `PUB_KEY_ID_` 开头。
- 停用无用配置项 `pay_wechat_api_key`、历史 `pay_wechat_cert_path`（若库中仍存在）。

## 升级后请核对（一般无需重填）

后台 → 系统配置 → 支付配置，确认：

1. **商户API私钥文件**：`apiclient_key.pem` 路径（相对 `server/` 或绝对路径）
2. **微信支付公钥文件**：商户平台下载的 `pub_key.pem` 路径
3. **微信支付公钥ID**：`PUB_KEY_ID_` 开头（公钥详情页查看）
4. **商户API证书序列号**：商户平台 API 安全中的商户证书序列号（用于请求签名，不是公钥 ID）

申请指引：<https://pay.weixin.qq.com/docs/merchant/products/update-pub-key/wxp-pub-key-guide.html>

## 数据库变更

执行 `update.sql`（幂等）：

- 校正 `pay_wechat_serial_no` / `*_path` / `public_key_id` 名称与说明
- `pay_wechat_cert_path`、`pay_wechat_api_key` 设为 `status=0`

无表结构变更。

## 升级步骤

1. 备份数据库。
2. 执行 `php think yd:update`。
3. 确认私钥/公钥 PEM 文件在服务器上可读，后台路径配置正确。
