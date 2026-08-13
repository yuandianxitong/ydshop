# v2.5.0 升级说明

本版本聚合先前 3 个 vNext 占位目录 + 会员详情全闭环。

## 一次性升级 SQL

```bash
mysql -u <user> -p <database> < update.sql
```

## 包含的变更

### A. 商品规格模板（合并自 vNext-spec-template）
- 新表 `goods_spec_templates`
- 权限 265-269、菜单 780-783

### B. 分销等级 CRUD + 三级佣金率（合并自 vNext-distribution-level）
- `distribution_levels` 加 `third_rate` 列 + 3 个默认等级
- 权限 492-497、菜单 980-983

### C. 用户标签规则引擎（合并自 vNext-user-tag-rules）
- `user_tags` 加 7 列：`description / group_type / rules / auto_update / user_count / status / deleted_at`
- 权限 498、菜单 985（刷新按钮）
- 部署 cron：`*/30 * * * * cd /path/to/server && php think user-tag:refresh >> /tmp/user-tag-refresh.log 2>&1`

### D. 会员详情全闭环
- 新表 `user_operation_logs`：用户操作日志统一聚合表，由各业务事件 Listener 写入
- 新表 `member_remarks`：会员运营备注（软删除）
- 权限 403-406（发短信 / 送优惠券 / 运营备注 / 修改地址）
- 菜单 912-915

## 兼容性

- 全部为新表 + 新增列（带默认值），不破坏老数据。
- 老用户标签 `group_type` 默认 `social`、`auto_update=0`，cron 跳过。
- 老分销等级未配置 `third_rate` 视为 0，原 2 级佣金计算行为不变。
