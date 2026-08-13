# v2.18.0 升级说明

## 变更

- 分销裂变、AI 商品助手从核心抽出为付费插件（`server/plugins/distribution`、`server/plugins/ai_assistant`）
- 核心去掉对应菜单 / 权限 / 定时任务种子；`ai_tasks`、`ai_prompt_templates`、`distribution_*` 表**保留**，由 `php think plugin:install` 认领（不 DROP）
- 三方同城配送、电子面单仍在核心，不拆包
- `users` 分销列与余额类型 5–8 仍在核心

## 数据库

```bash
cd server && php think yd:update
```

升级后分销 / AI 菜单消失。若本地已有插件目录，再执行：

```bash
php think plugin:install distribution
php think plugin:install ai_assistant
```

老库已有表时 install.sql 使用 `CREATE TABLE IF NOT EXISTS`，不会清空佣金 / 任务数据。升级后重新登录后台以刷新菜单与权限。
