# v2.7.1 升级说明

## 变更摘要

PC 端头部导航 / 底部菜单从硬编码下放到 system_configs，新增 admin 配置入口。

## 数据库变更

- **system_configs**：新增 2 个 json 配置
  - `pc_header_menu` —— PC 头部导航 8 项默认值
  - `pc_footer_config` —— PC 底部 4 列 + 版权
- **menus**：新增 1 条 admin 菜单
  - id=1095, parent=1060(Diy), path=`/diy/pc-menu`, name='PC 头部/底部'
- **role_menus**：给 super_admin（role_id=1）授权 menu_id=1095

## 升级步骤

```bash
mysql -u<user> -p<pass> <db> < server/database/updates/v2.7.1/update.sql
```

所有 INSERT 均使用 `INSERT IGNORE`，重复执行不报错。

## 升级后验证

1. 登录 admin，左侧「页面装修」下应出现「PC 头部/底部」
2. 进入该页面，能看到默认的头部 8 项 + 底部 4 列配置
3. PC 商城首页 `/pc/` 头部 / 底部展示与改造前一致
4. 在 admin 修改文案后保存，PC 刷新生效

## 回滚

如果出现问题，可执行：

```sql
DELETE FROM system_configs WHERE config_key IN ('pc_header_menu', 'pc_footer_config');
DELETE FROM role_menus WHERE menu_id = 1095;
DELETE FROM menus WHERE id = 1095;
```

PC 端代码会自动回退到内置默认值，UI 不受影响。
