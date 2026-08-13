-- plugins/coupon/database/uninstall.sql
-- 仅 purge 调用

DROP TABLE IF EXISTS `marketing_coupon_users`;
DROP TABLE IF EXISTS `marketing_coupons`;
