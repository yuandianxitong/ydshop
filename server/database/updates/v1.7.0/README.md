# v1.7.0 升级说明

## 变更

- DIY 模块新增「历史版本」能力
  - 发布页面时自动快照,存为 `diy_page_versions` 表行
  - 单页保留最近 50 个版本(超出自动删最旧)
  - 编辑器内可浏览/预览/恢复历史版本
  - 发布按钮可选填备注(写入版本 note 字段)

## 升级方式

```bash
mysql -uroot -p数据库名 < server/database/updates/v1.7.0/update.sql
```

## 行为变化

- 发布动作除写 `is_published=1` 外,**额外**插入一行 `diy_page_versions`(同事务,失败回滚)
- 老角色若拥有 `diy.page.publish` 权限,自动获得 `diy.page.version.list` + `restore`
- 取消发布不产生新版本
- 恢复版本仅覆盖 `title/components/page_settings`,不改 `is_published/is_default`,不创建新版本

## 回退

```sql
DELETE FROM `role_menus` WHERE `menu_id` IN (1074, 1081);
DELETE FROM `menus` WHERE `id` IN (1074, 1081);
DROP TABLE `diy_page_versions`;
```
