# Shop 插件说明

公开仓只包含 **Apache 核心** 与免费获客插件。付费商城组件通过官网市场交付签名 zip，**不要**推进公开远程。

## 完整包目录

每个插件是独立包，业务与三端源码都在插件内；主工程只是编译落点。

```
server/plugins/{code}/
  plugin.json
  database/
  admin/          # 镜像 Shop/admin/，安装软链到宿主 admin/
  pc/             # 镜像 Shop/pc/
  uniapp/         # 镜像 Shop/uniapp/
  app/            # PSR-4 plugins\{code}\
    adminapi/controller|route.php|validate
    api/controller|route.php|validate
    service/ repository/ model/ hook/ listener/ command/ event.php
```

- 命名空间示例：`plugins\coupon\adminapi\controller\CouponController`
- 无 `app/` 的旧 zip 仍从插件根加载（boot 时 PSR-4 回退）
- `plugin.json` 可声明 `hooks`、`commands`、`diy`、`c_end`、`uniapp.subPackages`
- HTTP 路由由 `app/adminapi/route/plugin.php` 与 `app/api/route/plugin.php` 在 MultiApp 定应用后按端加载
- 安装/入册/升级软链或拷贝同步三端并合并 `pages.json`（不入队云编译）
- 开发机：`admin`/`pc` 的 `predev` 跑 `scripts/sync-plugins.mjs`
- 生产部署多为 `server` + `uniapp`：后台页需已打进 `public/admin`；新付费后台页要在完整仓编完再部署产物
- 官方小程序页已在发行包则跳过 C 端编；新原生页走「客户端发布」
- 手动只同步：`php think plugin:frontend-deploy {code|--all}`

## 宿主 hook（实现写在插件 hook/）

| hook | 用途 |
|---|---|
| `order.calc_discount` | 满减 / 优惠券折扣 |
| `order.quote_line` | 试算改价 |
| `order.prepare_line` | 下单改价、扣插件库存 |
| `order.after_create` | 事务内核销券 / 开团参团 |
| `order.return_coupon` / `order.rebind_coupon` | 取消回券、合单改绑 |
| `order.restore_stock_item` / `order.restore_flash_item` | 取消/退款还库存 |
| `order.freight_benefit` | 满减包邮 |
| `order.has_plugin_constraint` | 禁止拆合改价 |
| `diy.hydrate` | 装修组件数据 |
| `goods.detail_promo` | 商品详情促销条 |
| `finance.*` | 提现仓储/对账/新人礼积分概览 |

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

Shop 后台「插件市场」通过官网授权页连接账号（实例 PKCE），按 entitlements 一键下载安装；也可继续本地上传 zip。

发版到官网：把 `runtime/plugin-packages/<code>-<version>.zip` 上传到 Site 管理后台对应应用的「版本」并发布（`market_apps.runtime=shop`）。
