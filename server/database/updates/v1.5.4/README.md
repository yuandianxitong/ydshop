# v1.5.4 升级说明

## 变更
- 新增 `goods_unit_groups` 表（计量单位分组）。
- 扩展 `goods_units` 表字段：`code / name_en / group_id / decimal_places / is_base / ratio / sort`。
- 初始化 5 个默认分组（重量 / 长度 / 容量 / 计件 / 时间）。

## 升级方式
执行 `update.sql`：
```bash
mysql -u <user> -p <database> < server/database/updates/v1.5.4/update.sql
```

## 兼容性
- `goods_units` 表保留 `name / status` 字段，旧数据不受影响。
- 已有商品（goods_spu）继续通过 `unit_id` 关联，**未指定 group_id 的旧单位 group_id 默认为 0**，建议升级后人工分组。
- 没有任何分组数据时会插入 5 个默认分组；已有分组则跳过。
