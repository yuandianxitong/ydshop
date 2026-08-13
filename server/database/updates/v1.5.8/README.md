# v1.5.8 升级说明

## 变更
- 新建 `new_user_gifts` 表（多礼包持久化）
- `users` 表加 `new_user_gift_claimed_at` 列（Listener 幂等标记）
- 数据迁移：旧 SystemConfig new_user_gift.enabled='1' 时自动 seed 一条默认礼包
- 删除 4 条 SystemConfig 老 key（new_user_gift.enabled/points/balance/coupon_ids）
- 新增 4 条 system_configs rules seed（new_user_gift.rules.*）
- 新增 5 个权限（marketing.new_user_gift.list/view/create/update/delete，IDs 486-490）+ 角色关联
- NewUserGiftController 重写为 RESTful CRUD + rules 双端点
- NewUserGiftListener 走表查询发放，幂等通过 users.new_user_gift_claimed_at

## 升级方式
```bash
mysql -u <user> -p <database> < server/database/updates/v1.5.8/update.sql
```
