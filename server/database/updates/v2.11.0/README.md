# v2.11.0 升级说明

## 变更

- 新增：PC 首页 DIY 预设组件 —— Banner（1 张主图）+ 5 列魔方（image-cube）
- 新增：PC 端 `DiyImageCube` 组件实现（CSS Grid 渲染，与 uniapp 端契约统一）
- 新增：6 张系统预设图（1 banner + 5 cube），路径 `/storage/diy-defaults/home/pc/`
- DIY 渲染器：PC `DiyRenderer.vue` 注册 `image-cube` 组件类型

## 升级步骤

1. 备份数据库：`mysqldump -uroot -p shop > shop_pre_v2.11.0.sql`
2. 拉新代码：`git pull`（包含新预设图片和 PC 组件文件）
3. 执行升级 SQL：`mysql -uroot -p shop < server/database/updates/v2.11.0/update.sql`
4. 前端构建：`cd pc && npm install && npm run build`

## 回滚

1. 恢复数据库：`mysql -uroot -p shop < shop_pre_v2.11.0.sql`
2. `git revert <merge-commit>`

## 注意

- **不会覆盖你自定义过的首页**：升级 SQL 只在 `diy_pages.components` 为空 / NULL / 等于 v2.10.x 默认占位结构时才更新；如果你已经在 `/admin/diy/page` 编辑过 PC 首页，SQL 会跳过
- 如需强制使用新预设：登录后台 → DIY → 装修页面 → 编辑 PC 首页 → 「重置为默认」（或直接清空再保存）
- 预设图片随 git 提交，无需安装时复制；新装站点直接得到完整 PC 首页
