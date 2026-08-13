# v2.4.0 升级说明

## 数据库变更

1. 新增 `stores` 表（门店）
2. `order_orders` 加 7 个自提字段：`delivery_type` / `pickup_store_id` / `pickup_code` / `pickup_at` / `pickup_verified_by` / `pickup_status` / `pickup_timeout_at`，加索引 `idx_pickup`
3. `goods_spu` 加 `delivery_modes` JSON（注：代码库中商品主表为 `goods_spu`，而非 `goods`）
4. `system_configs` 加 3 个 pickup 相关配置项

## 数据兼容

- 老订单 `delivery_type` 默认填 `express`（ALTER 自动）
- 老商品 `delivery_modes IS NULL`，Repository 兜底视为 `["express"]`

## 升级步骤

```bash
mysql -u <user> -p <db> < server/database/updates/v2.4.0/update.sql
```

## 回滚

```sql
DELETE FROM `role_menus` WHERE `menu_id` IN (1400, 1410, 1411, 1412, 1413, 1414);
DELETE FROM `menus` WHERE `id` IN (1400, 1410, 1411, 1412, 1413, 1414);
DROP TABLE IF EXISTS stores;
ALTER TABLE order_orders
  DROP COLUMN delivery_type,
  DROP COLUMN pickup_store_id,
  DROP COLUMN pickup_code,
  DROP COLUMN pickup_at,
  DROP COLUMN pickup_verified_by,
  DROP COLUMN pickup_status,
  DROP COLUMN pickup_timeout_at,
  DROP KEY idx_pickup;
ALTER TABLE goods_spu DROP COLUMN delivery_modes;
DELETE FROM system_configs WHERE `config_group` = 'pickup';
```
