-- plugins/article/database/uninstall.sql
-- 仅 purge 调用；依赖逆序

DROP TABLE IF EXISTS `articles`;
DROP TABLE IF EXISTS `article_categories`;
