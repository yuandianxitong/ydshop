# v2.6.0 升级说明

发布日期：2026-05-09

## 主要变更

### 新增功能

- **浏览记录**：用户在 uniapp 进入商品详情自动记录，会员中心可查看 / 删除 / 清空。
- **抽奖活动**（lottery 插件）：九宫格抽奖，支持每日免费 + 积分消耗、按权重抽取、库存控制；奖品支持优惠券 / 积分 / 谢谢参与 / **实物商品**四类，中奖即时发放（实物除外）。
- **抽奖实物奖品发货闭环**：中奖后用户填写收货地址 → 创建发货单 → admin 录单号发货 → 用户确认收货；超期未填地址自动作废并退还奖品库存。
- **DIY 链接**：DIY 编辑器营销组新增「抽奖活动」入口。

## 升级步骤

> 老用户从 v2.5.0 升级到 v2.6.0。全新安装请直接使用 `install/data/schema.sql + init.sql`，不需要执行本目录的 SQL。

### 1. 拉取代码 + 重新生成 composer 自动加载

```bash
git pull
cd server
composer dump-autoload -o
```

`composer.json` 已新增 `plugins\\lottery\\` 命名空间映射，必须重建 classmap。

### 2. 执行升级 SQL

将 `update.sql` 整个文件应用到生产库：

```bash
mysql -uroot -p your_database < server/database/updates/v2.6.0/update.sql
```

包含：
- 新增 `member_browse_histories` 表
- 新增 `marketing_lottery_activities` / `marketing_lottery_prizes` / `marketing_lottery_records` 三张表
- 新增 `marketing_lottery_shipments` 表（实物奖品发货）
- 给 `marketing_lottery_activities` 增加 `address_expire_days` 列
- 新增 `marketing.lottery.*` 5 条权限（550-554）+ 「抽奖活动」菜单（1056-1059）
- 新增 `marketing.lottery_shipment.*` 4 条权限（555-558）+ 「抽奖发货」菜单（1170-1172）
- 把全部 9 条新权限分配给超级管理员角色

### 备选：使用插件命令安装抽奖三张表（与上方 update.sql 第 2 段等效）

```bash
php think plugin:install lottery
```

只创建 lottery 插件的 3 张表（基于 Phinx 迁移），不创建 `member_browse_histories`、不写菜单/权限——因此**仍需手动执行 update.sql 中的浏览记录 + 菜单/权限部分**。

### 3. 重新构建前端

```bash
cd admin && npm run build
cd ../uniapp && pnpm run build:h5  # 或对应的小程序构建命令
```

### 4. 重新登录管理后台

新菜单 / 权限需重新登录后从 `/adminapi/system/config/global` 刷新菜单缓存。

## 回滚

若需回滚抽奖功能：

```sql
DROP TABLE IF EXISTS `marketing_lottery_shipments`;
DROP TABLE IF EXISTS `marketing_lottery_records`;
DROP TABLE IF EXISTS `marketing_lottery_prizes`;
DROP TABLE IF EXISTS `marketing_lottery_activities`;

DELETE FROM `menus` WHERE id IN (1056, 1057, 1058, 1059, 1170, 1171, 1172);
DELETE FROM `role_permissions` WHERE permission_id IN (550, 551, 552, 553, 554, 555, 556, 557, 558);
DELETE FROM `permissions` WHERE id IN (550, 551, 552, 553, 554, 555, 556, 557, 558);
```

或者：

```bash
php think plugin:uninstall lottery
```

后再删除菜单/权限。
