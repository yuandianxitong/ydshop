-- =============================================================================
-- v2.6.0 升级 SQL（老用户使用，全新安装请用 install/data/schema.sql + init.sql）
--
-- 主要变更：
--   1. 新增「浏览记录」表 member_browse_histories
--   2. 新增「抽奖活动」三张表 marketing_lottery_*
--   3. 新增 marketing.lottery 权限 + 菜单
--
-- 抽奖三张表也可以用 `php think plugin:install lottery` 命令自动执行 Phinx 迁移
-- （仅当 lottery 插件目录存在时），效果与本 SQL 等效。
-- =============================================================================

-- ─── 1. 浏览记录（v2.6.0 新增） ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `member_browse_histories` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL COMMENT '用户ID',
    `spu_id` int NOT NULL COMMENT '商品SPU ID',
    `viewed_at` datetime NOT NULL COMMENT '最近浏览时间',
    `created_at` datetime DEFAULT NULL COMMENT '创建时间',
    `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_spu_unique` (`user_id`, `spu_id`),
    KEY `user_viewed_idx` (`user_id`, `viewed_at`),
    KEY `spu_id` (`spu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='浏览记录';


-- ─── 2. 抽奖活动（v2.6.0 新增 / lottery 插件） ─────────────────────────────────

CREATE TABLE IF NOT EXISTS `marketing_lottery_activities` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(100) NOT NULL COMMENT '活动名称',
    `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图',
    `description` text COMMENT '活动规则说明',
    `start_at` datetime NOT NULL COMMENT '开始时间',
    `end_at` datetime NOT NULL COMMENT '结束时间',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1启用 0禁用',
    `daily_free_count` int NOT NULL DEFAULT '1' COMMENT '每日免费次数',
    `points_per_draw` int NOT NULL DEFAULT '0' COMMENT '单次抽奖消耗积分（超出免费次数后）',
    `created_at` datetime DEFAULT NULL COMMENT '创建时间',
    `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
    `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `status_start_end_idx` (`status`, `start_at`, `end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='抽奖活动表';

CREATE TABLE IF NOT EXISTS `marketing_lottery_prizes` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `activity_id` int NOT NULL COMMENT '活动ID',
    `position` int NOT NULL COMMENT '九宫格位置 1-8',
    `name` varchar(100) NOT NULL COMMENT '奖品名称',
    `image` varchar(255) NOT NULL DEFAULT '' COMMENT '奖品图片',
    `type` int NOT NULL COMMENT '奖品类型 1=优惠券 2=积分 3=谢谢参与',
    `reference_id` int NOT NULL DEFAULT '0' COMMENT '关联ID（type=1 时为优惠券ID）',
    `value` int NOT NULL DEFAULT '0' COMMENT '数值（type=2 时为积分数量）',
    `weight` int NOT NULL DEFAULT '0' COMMENT '权重（用于按权重抽奖）',
    `stock` int NOT NULL DEFAULT '0' COMMENT '剩余库存（type=3 不消耗）',
    `original_stock` int NOT NULL DEFAULT '0' COMMENT '初始库存（统计用）',
    `created_at` datetime DEFAULT NULL COMMENT '创建时间',
    `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `activity_position_unique` (`activity_id`, `position`),
    KEY `activity_id_idx` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='抽奖奖品表';

CREATE TABLE IF NOT EXISTS `marketing_lottery_records` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL COMMENT '用户ID',
    `activity_id` int NOT NULL COMMENT '活动ID',
    `prize_id` int NOT NULL DEFAULT '0' COMMENT '奖品ID',
    `prize_type` int NOT NULL DEFAULT '3' COMMENT '奖品类型快照 1券 2积分 3谢谢参与',
    `prize_name` varchar(100) NOT NULL DEFAULT '' COMMENT '奖品名称快照',
    `prize_value` int NOT NULL DEFAULT '0' COMMENT '奖品数值（积分数 / 优惠券ID）',
    `is_free` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否本日免费 1是 0付费',
    `cost_points` int NOT NULL DEFAULT '0' COMMENT '消耗的积分（is_free=0 时记录）',
    `created_at` datetime DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `user_activity_created_idx` (`user_id`, `activity_id`, `created_at`),
    KEY `activity_id_idx` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='抽奖记录表';


-- ─── 3. 抽奖菜单 + 权限 ────────────────────────────────────────────────────────

INSERT IGNORE INTO `permissions`
    (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
    (550, 'marketing.lottery',         '抽奖活动',   '营销管理', '抽奖活动管理权限',     'admin', 1, 550, NOW(), NOW()),
    (551, 'marketing.lottery.list',    '抽奖列表',   '营销管理', '查看抽奖活动列表',     'admin', 1, 551, NOW(), NOW()),
    (552, 'marketing.lottery.create',  '创建抽奖',   '营销管理', '创建抽奖活动',         'admin', 1, 552, NOW(), NOW()),
    (553, 'marketing.lottery.update',  '编辑抽奖',   '营销管理', '编辑抽奖活动',         'admin', 1, 553, NOW(), NOW()),
    (554, 'marketing.lottery.delete',  '删除抽奖',   '营销管理', '删除抽奖活动',         'admin', 1, 554, NOW(), NOW());

INSERT IGNORE INTO `menus`
    (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
    (1056, 1000, 2, '抽奖活动', 'MarketingLottery', '/marketing/lottery', 'marketing/lottery/index', NULL, 'i-lucide:dice-5', 'marketing.lottery',        0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 9, NOW(), NOW()),
    (1057, 1056, 3, '新增',     NULL,                NULL,                NULL,                       NULL, NULL,             'marketing.lottery.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
    (1058, 1056, 3, '编辑',     NULL,                NULL,                NULL,                       NULL, NULL,             'marketing.lottery.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
    (1059, 1056, 3, '删除',     NULL,                NULL,                NULL,                       NULL, NULL,             'marketing.lottery.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW());

-- 默认把 5 条权限分配给超级管理员角色（id=1）
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, p.id FROM `permissions` p WHERE p.id IN (550, 551, 552, 553, 554);


-- ─── 4. 抽奖实物奖品发货闭环（v2.6.0 后追加） ─────────────────────────────────

-- 4.1 抽奖活动表新增「地址有效天数」字段
ALTER TABLE `marketing_lottery_activities`
    ADD COLUMN `address_expire_days` INT NOT NULL DEFAULT 7
    COMMENT '中奖实物奖品填写地址的有效天数' AFTER `points_per_draw`;

-- 4.2 实物奖品发货单表
CREATE TABLE IF NOT EXISTS `marketing_lottery_shipments` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `order_no` varchar(32) NOT NULL COMMENT '发货单号 LT+YmdHis+4',
    `user_id` int NOT NULL COMMENT '用户ID',
    `activity_id` int NOT NULL COMMENT '活动ID',
    `record_id` int NOT NULL COMMENT '抽奖记录ID',
    `prize_id` int NOT NULL COMMENT '奖品ID快照',
    `prize_name` varchar(100) NOT NULL DEFAULT '' COMMENT '奖品名称快照',
    `prize_image` varchar(255) NOT NULL DEFAULT '' COMMENT '奖品图片快照',
    `address_snapshot` json DEFAULT NULL COMMENT '收货地址快照',
    `express_company` varchar(50) NOT NULL DEFAULT '' COMMENT '快递公司',
    `express_no` varchar(50) NOT NULL DEFAULT '' COMMENT '快递单号',
    `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending/shipped/completed/cancelled/expired',
    `expire_at` datetime DEFAULT NULL COMMENT '过期时间',
    `shipped_at` datetime DEFAULT NULL COMMENT '发货时间',
    `completed_at` datetime DEFAULT NULL COMMENT '确认收货时间',
    `cancelled_at` datetime DEFAULT NULL COMMENT '取消/过期时间',
    `cancel_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '取消原因',
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `order_no_unique` (`order_no`),
    UNIQUE KEY `record_id_unique` (`record_id`),
    KEY `user_status_idx` (`user_id`, `status`),
    KEY `activity_status_idx` (`activity_id`, `status`),
    KEY `status_expire_idx` (`status`, `expire_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='抽奖实物奖品发货单';

-- 4.3 发货管理权限
INSERT IGNORE INTO `permissions`
    (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
    (555, 'marketing.lottery_shipment',         '抽奖发货',     '营销管理', '抽奖实物奖品发货管理',     'admin', 1, 555, NOW(), NOW()),
    (556, 'marketing.lottery_shipment.list',    '发货列表',     '营销管理', '查看发货单列表',           'admin', 1, 556, NOW(), NOW()),
    (557, 'marketing.lottery_shipment.ship',    '发货',         '营销管理', '执行发货',                 'admin', 1, 557, NOW(), NOW()),
    (558, 'marketing.lottery_shipment.cancel',  '取消发货',     '营销管理', '取消发货并退还库存',       'admin', 1, 558, NOW(), NOW());

-- 4.4 发货管理菜单（注意：避开 1080 已被 DIY 占用，使用 1170-1172）
INSERT IGNORE INTO `menus`
    (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
    (1170, 1000, 2, '抽奖发货', 'MarketingLotteryShipment', '/marketing/lottery-shipments', 'marketing/lottery-shipments/index', NULL, 'i-lucide:truck', 'marketing.lottery_shipment',        0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 10, NOW(), NOW()),
    (1171, 1170, 3, '发货',     NULL,                        NULL,                          NULL,                                NULL, NULL,             'marketing.lottery_shipment.ship',   0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
    (1172, 1170, 3, '取消发货', NULL,                        NULL,                          NULL,                                NULL, NULL,             'marketing.lottery_shipment.cancel', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW());

-- 4.5 把 4 条新权限授予超级管理员
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, p.id FROM `permissions` p WHERE p.id IN (555, 556, 557, 558);
