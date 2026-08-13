# v2.1.0 升级说明

## 主要变更

- 新增用户端发票申请接口（`POST /api/order/invoice` 等）
- 新增"可领取"优惠券接口（`GET /api/marketing/coupon/receivable`）

## 数据库变更

- `order_invoices` 表：将 `idx_order_id` 普通索引替换为 `uk_order_id` 唯一索引（保证一单一票，避免并发重复开票）

## 升级步骤

```bash
mysql -u<user> -p<pass> <db_name> < update.sql
```

升级前请确认 `order_invoices` 表内不存在同 `order_id` 的多条记录。如有可先去重：

```sql
DELETE FROM order_invoices WHERE id NOT IN (
  SELECT id FROM (
    SELECT MIN(id) AS id FROM order_invoices GROUP BY order_id
  ) t
);
```
