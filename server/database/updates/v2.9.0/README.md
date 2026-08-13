# v2.9.0 升级说明

## 变更摘要

新增「帮助中心」子系统，挂在系统管理一级菜单下「帮助管理」分组。同步更新 PC 头部导航默认值。

## 数据库变更

- **新表**：
  - `help_categories` —— 帮助分类（单级平铺）
  - `helps` —— 帮助文章（精简 articles，含富文本）
- **预置数据**：5 个默认分类（购物问题 / 退换货 / 账号问题 / 支付问题 / 物流配送）
- **menus**：新增 3 条
  - id=1730 帮助管理（type=1 LAYOUT 分组）
  - id=1731 帮助分类（/content/help-category）
  - id=1732 帮助列表（/content/help）
- **role_menus**：super_admin 自动授权 3 条新菜单
- **system_configs**：`pc_header_menu` 中「帮助中心」path 由 `/article?category_id=help` 守卫式 UPDATE 为 `/help`（仅老用户未自定义过的；自定义过的不动）

## 升级步骤

```bash
mysql -u<user> -p<pass> <db> < server/database/updates/v2.9.0/update.sql
```

所有 INSERT 均使用 `INSERT IGNORE`/`CREATE TABLE IF NOT EXISTS`，重复执行不报错。`pc_header_menu` UPDATE 用 `REPLACE` 函数局部替换，保护用户其他自定义。

## 升级后验证

1. 登录 admin，左侧「系统管理」下应出现「帮助管理」分组
2. 进入「帮助分类」能看到 5 个默认分类
3. 「帮助列表」能新建/编辑帮助（带富文本编辑器）
4. PC 商城 `/pc/` 顶部导航的「帮助中心」点击跳 `/pc/help` 而非 `/pc/article?category_id=help`
5. uniapp 端访问 `/modules/help/pages/list` 能看到分类 + 列表

## 接口契约

- `GET /api/help/categories` —— 返回启用分类
- `GET /api/help/list?category_id=&keyword=&page_no=&page_size=` —— 分页列表
- `GET /api/help/:id` —— 详情 + view_count 自增

## 回滚

```sql
DELETE FROM role_menus WHERE menu_id IN (1730, 1731, 1732);
DELETE FROM menus WHERE id IN (1730, 1731, 1732);
DROP TABLE IF EXISTS `helps`;
DROP TABLE IF EXISTS `help_categories`;

-- 还原 pc_header_menu（仅在用户未对 /help 链接做进一步自定义时安全）
UPDATE `system_configs`
SET `config_value` = REPLACE(`config_value`, '"path":"/help"', '"path":"/article?category_id=help"')
WHERE `config_key` = 'pc_header_menu'
  AND `config_value` LIKE '%"label":"帮助中心","path":"/help"%';
```
