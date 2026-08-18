# v2.19.0 升级说明

## 变更

- 插件安装改为软链同步宿主前端；生产入队 `frontend-builds` 云编译 admin/PC，开发机（`app_debug`）只软链
- 官方已预置的小程序页安装时跳过 C 端编译；新原生页入队 H5 / mp-weixin
- 新增「云编译」「客户端发布」后台页；小程序上传走 miniprogram-ci（渠道发布，不是安装步骤）

## 数据库

```bash
cd server && php think yd:update
```

升级后重新登录以刷新菜单。生产环境请启动带 Node 的 worker：

```bash
php think queue:work --queue=frontend-builds --tries=1 --timeout=900 --sleep=3
```

PHP-FPM 容器不要跑该队列。
