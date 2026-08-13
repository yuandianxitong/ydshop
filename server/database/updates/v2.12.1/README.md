# v2.12.1 升级说明（补齐 lottery 表）

执行：

```bash
mysql -u账号 -p 数据库名 < server/database/updates/v2.12.1/update.sql
cd server && php think clear
```

用于修复已安装系统中 `plugins` 表有 lottery 插件，但缺少 `marketing_lottery_*` 业务表的问题。
