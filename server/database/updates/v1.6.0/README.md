# v1.6.0 升级说明

## 变更
- DIY 模块整体重构
  - `diy_pages` 新增 `is_default` 字段,支持每端多个首页 + 一个生效
  - 删除「专题管理 / 链接管理」菜单(原专题能力合入「页面管理」专题 tab)
  - 「主题管理」改名「模板中心」,路由从 `/diy/theme` 迁移到 `/diy/template`
  - 「分类管理」改名「分类页配置」,移动端 3 种骨架由 `category_page_style_uniapp` 配置控制
  - 模板中心新增 8 套系统预设
  - PC 端首页接入 DIY 渲染

## 升级方式
1. 拉取代码并部署
2. 执行 `update.sql`:
   ```bash
   mysql -uroot -p数据库名 < server/database/updates/v1.6.0/update.sql
   ```
3. 重启后端服务

## 行为变化(重要)
- 旧版本每端 home/category 只能有 1 条,升级后该条自动置 `is_default=1`,行为不变
- 配置 `category_page_style` 已重命名为 `category_page_style_uniapp`(脚本自动迁移值)
- 老 `diy.topic.*` 权限不再保留,角色自动获得 `diy.page.*` 与 `diy.page.set_default` 权限
- 系统预设模板首次部署后会替换历史 is_system=1 的预设记录(仅影响系统预设,不影响用户保存的模板)

## 回退
1. 回滚代码到 v1.5.10 tag
2. 执行回滚 SQL:
   ```sql
   ALTER TABLE `diy_pages` DROP INDEX `idx_default`;
   ALTER TABLE `diy_pages` DROP COLUMN `is_default`;

   INSERT IGNORE INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `sort_order`, `status`, `created_at`, `updated_at`)
     VALUES ('category_page_style', 'style1', 'diy', 'string', '分类页样式', '分类页样式(style1/style2/style3)', 1, 1, NOW(), NOW());
   DELETE FROM `system_configs` WHERE `config_key` = 'category_page_style_uniapp';
   ```
3. 老菜单删除后无自动恢复脚本,如需运营继续使用专题管理需手动 INSERT
