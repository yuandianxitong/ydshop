# v2.12.0 升级说明（插件命名统一）

执行 `update.sql` 后清除缓存：

```bash
php think clear
```

本升级会将 `applications` 重命名为 `plugins`，将 `application_install_logs` 重命名为 `plugin_install_logs`，并把插件管理菜单的内部 `name` 从 `Application` 调整为 `Plugin`。

如果升级或安装后 `plugins` 表为空，可在项目 `server` 目录手动执行一次：

```bash
php think plugin:enroll-bundled
```
