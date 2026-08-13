# v2.17.0 升级说明

## 变更

- 后台新增「系统管理 → 产品授权」页面：录入授权码、绑定域名、激活/心跳/清除
- 权限：`system.license` / `system.license.list` / `system.license.activate`
- 菜单：id `102`（页面）、`103`（激活按钮）

## 数据库

```bash
cd server && php think yd:update
```

升级后重新登录后台以刷新菜单与权限。
