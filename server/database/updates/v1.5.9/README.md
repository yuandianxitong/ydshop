# v1.5.9 升级说明

## 变更
- 新建 `new_user_gift_logs` 表（每张发出去的礼包写 1 行）
- NewUserGiftListener 在每张礼包发放后追加日志写入
- 后台 NewUserGiftController 新增 `GET /stats` 和 `GET /logs` 端点
- 礼包列表 API 响应每条带 `claimed_count` 字段
- 前端 KPI 接通真实数据；新增"领取记录" tab；礼包卡"领取明细"按钮接入

## 升级方式
```bash
mysql -u <user> -p <database> < server/database/updates/v1.5.9/update.sql
```

## 备注
- 历史礼包发放（v1.5.8 时代）不会回填 logs 表，"已领取"计数从 v1.5.9 上线后开始
- 复用既有权限 `marketing.new_user_gift.list`，无新增权限
