# v2.8.0 升级说明

## 变更摘要

新增「广告 / 广告位」管理子系统，挂在营销中心一级菜单下。

## 数据库变更

- **新表**：
  - `marketing_ad_positions` —— 广告位定义（code/name/尺寸/轮播开关/状态）
  - `marketing_ads` —— 广告物料（图/链接/时间窗/状态/排序）
- **system_configs**：无变更
- **menus**：新增 3 条
  - id=1050 广告管理（type=1 LAYOUT 分组）
  - id=1051 广告位（/marketing/ad-position）
  - id=1052 广告（/marketing/ad）
- **role_menus**：super_admin 自动授权 3 条新菜单

## 升级步骤

```bash
mysql -u<user> -p<pass> <db> < server/database/updates/v2.8.0/update.sql
```

所有 INSERT 均使用 `INSERT IGNORE`/`CREATE TABLE IF NOT EXISTS`，重复执行不报错。

## 升级后验证

1. 登录 admin，左侧「营销中心」展开应看到「广告管理」分组
2. 分组下有「广告位」「广告」两个子页面
3. 进入「广告位」能看到 3 个默认广告位（首页顶部 Banner / 商品列表页侧栏 1 / 购物车页底部推荐）
4. DIY 编辑器组件列表里有「广告位」组件（ad-slot type）

## 接口契约

- `GET /api/ad/by-position/home_top_banner` —— 返回 `{ position, ads }`，无生效广告时 ads=[]
- code 不存在时返回 `{ position: null, ads: [] }`，永不抛错

## 回滚

```sql
DELETE FROM role_menus WHERE menu_id IN (1050, 1051, 1052);
DELETE FROM menus WHERE id IN (1050, 1051, 1052);
DROP TABLE IF EXISTS `marketing_ads`;
DROP TABLE IF EXISTS `marketing_ad_positions`;
```

注意：DIY 页面 components 中若已有 ad-slot 组件，前端 hydrate 后会收到空 ad_list，组件静默不渲染，不影响其他组件。
