# Shop 插件说明

公开仓只包含 **Apache 核心** 与免费获客插件。付费商城组件通过官网市场交付签名 zip，**不要**推进公开远程。

## 仓库内（免费）

- `coupon` 优惠券
- `full_discount` 满减
- `sign` 签到
- `new_user_gift` 新人礼包
- `article` 文章
- `content_mgmt` 内容/协议

三方同城配送与电子面单留在 Apache 核心，不是独立插件。

## 不进公开仓（付费，`runtime=shop`）

独立插件目录（本地可保留，已被根 `.gitignore` 忽略）：

- `flash_sale` 秒杀
- `group_buy` 拼团
- `lottery` 抽奖
- `points_product` 积分商品（含兑换订单）
- `distribution` 分销裂变
- `ai_assistant` AI 商品助手

打包约定：zip 根目录含 `plugin.json`，`category` 用 `value_added`。付费组件还会打入 `_frontend/`（admin / PC / uniapp 页面），安装时自动落到项目根目录。上架时 Site `market_apps.runtime=shop`。

本地打包输出到 `server/runtime/plugin-packages/<code>-<version>.zip`（runtime 已忽略，不进公开仓）：

```bash
cd server && php think plugin:pack flash_sale
cd server && php think plugin:pack --all --force
```

Shop 后台「插件市场」可连接官网账号，按 entitlements 一键下载安装；也可继续本地上传 zip。

发版到官网：把 `runtime/plugin-packages/<code>-<version>.zip` 上传到 Site 管理后台对应应用的「版本」并发布（`market_apps.runtime=shop`）。
