-- plugins/sign/database/uninstall.sql
-- 仅 purge 调用

DROP TABLE IF EXISTS `member_sign_logs`;
DELETE FROM `system_configs` WHERE `config_group` = 'sign';
