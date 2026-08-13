-- v1.6.4 升级脚本
-- 1. 分类页配置 菜单名改为 4 字 (分类页配置 → 分类配置)
UPDATE `menus` SET `title` = '分类配置' WHERE `id` = 1080;
