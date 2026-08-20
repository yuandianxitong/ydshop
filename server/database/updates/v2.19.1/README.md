# v2.19.1 升级说明

## 变更

- 插件安装不再入队 admin/PC 云编译；「云编译」菜单隐藏（页面与手动重建接口仍保留）
- 安装时软链失败回退为拷贝，生产 `disable_functions=symlink` 不再 500
- 本地 zip 安装先写入付费组件权益，避免接口 404

## 数据库

```bash
cd server && php think yd:update
```

升级后重新登录以刷新菜单。已装但接口 404 的站点请再执行：

```bash
php think plugin:enroll-bundled
# 如仍异常：删除 runtime/plugins_cache.php 后重载 PHP
```
