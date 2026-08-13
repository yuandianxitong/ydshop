# v2.7.0 升级说明（插件管理 / 插件运行时）

## ⚠️ 重要

本版本引入插件运行时机制：
- 营销 9 个（优惠券 / 满减 / 秒杀 / 拼团 / 抽奖 / 新人礼包 / 签到 / 积分商品 / 兑换订单）
- 内容管理、文章资讯

共 11 个模块改造为「可装可卸」的插件。**老用户升级需要手动跑一次数据迁移**，不会破坏现有数据。

## 升级步骤

### 1. 备份数据库

```bash
mysqldump -uroot -p shop > shop_backup_$(date +%Y%m%d).sql
```

### 2. 拉新代码

```bash
git pull origin main
cd server && composer install
cd ../admin && npm install
```

### 3. 执行 SQL 升级

```bash
mysql -uroot -p shop < server/database/updates/v2.7.0/update.sql
```

包含：
- 给 `menus` / `permissions` 表加 `plugin_code` 列
- 新建 `plugins` / `plugin_install_logs` / `plugin_migrations` 表
- 菜单重排（新增「渠道管理」一级菜单 + 5 项归位 + 插件管理新增 2 个子菜单）

### 4. 跑插件入册命令

```bash
cd server && php think plugin:enroll-bundled
```

预期输出 11 行「已入册」。命令幂等，多次执行无副作用。

### 5. 重启 PHP-FPM / Web Server

```bash
sudo systemctl reload php8.0-fpm
sudo systemctl reload nginx
```

### 6. 验证

登录后台：
- 「插件管理 → 已安装插件」应看到 11 个插件
- 「营销管理」下有 9 个营销子菜单
- 「渠道管理」一级菜单存在，下有公众号 / 小程序 / 开放平台
- 「物流配送」下有「区域管理」
- 「系统管理」下有「应用版本」

## 不需要做的事

- 不需要重新分配权限（plugin_code 是回填，原有 role_permissions / role_menus 关联保持不变）
- 不需要导出导入业务数据（优惠券、订单、签到记录等业务表本身不动）

## 出错回滚

```bash
mysql -uroot -p shop < shop_backup_YYYYMMDD.sql
git checkout v<previous-version>
```
