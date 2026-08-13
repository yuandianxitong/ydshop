# v2.10.0 升级说明

## 变更

- 新增：商品编辑页 5 处字段内嵌 AI 按钮（标题/副标题/主图/场景图/商品详情）
- 新增：通义万相图像生成（dashscope wanx2.1-t2i-turbo）
- 调整：ai_tasks.type enum 改为 analysis|text|image；ai_prompt_templates 加 scene 列
- 移除：智能补货推荐（菜单/权限/路由/Controller/Service/前端全部删除；历史任务清空）

## 升级步骤

1. 备份数据库：`mysqldump -uroot -p shop > shop_pre_v2.10.0.sql`
2. 执行升级 SQL：`mysql -uroot -p shop < server/database/updates/v2.10.0/update.sql`
3. 拉新代码：`git pull`
4. composer install
5. 前端构建：`cd admin && npm install && npm run build`
6. 在 AI 配置页确认 qwen 密钥已填
7. 旧管理员需重新登录刷新菜单

## 回滚

1. 恢复数据库：`mysql -uroot -p shop < shop_pre_v2.10.0.sql`
2. git revert <merge-commit>

## 注意

- 历史 type='description' 任务记录会自动迁到 type='text'
- 历史 type='restock' 任务记录会被删除（按规范）
- 旧前端缓存若仍调 /adminapi/ai/description/stream 会 404，请要求管理员强制刷新浏览器
