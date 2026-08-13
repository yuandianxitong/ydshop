-- ===============================================
-- v1.5.4 商品计量单位 + 分组扩展
-- ===============================================

-- 1. 计量单位分组表
CREATE TABLE IF NOT EXISTS `goods_unit_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL DEFAULT '' COMMENT '分组编码（唯一）',
  `name` varchar(40) NOT NULL DEFAULT '' COMMENT '分组名称',
  `tone` varchar(20) NOT NULL DEFAULT 'blue' COMMENT '色调（blue/rose/cyan/violet/amber/teal）',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:1启用,0禁用',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='计量单位分组';

-- 2. 扩展 goods_units 字段（先不加 UNIQUE，等回填 code 后再加）
ALTER TABLE `goods_units`
  ADD COLUMN `code` varchar(40) NOT NULL DEFAULT '' COMMENT '单位编码（唯一）' AFTER `id`,
  ADD COLUMN `name_en` varchar(40) NOT NULL DEFAULT '' COMMENT '英文名' AFTER `name`,
  ADD COLUMN `group_id` int unsigned NOT NULL DEFAULT 0 COMMENT '所属分组ID' AFTER `name_en`,
  ADD COLUMN `decimal_places` tinyint NOT NULL DEFAULT 2 COMMENT '小数位数' AFTER `group_id`,
  ADD COLUMN `is_base` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否基准单位:1是,0否' AFTER `decimal_places`,
  ADD COLUMN `ratio` decimal(20,6) NOT NULL DEFAULT 1.000000 COMMENT '相对基准单位的换算系数（基准为 1）' AFTER `is_base`,
  ADD COLUMN `sort` int NOT NULL DEFAULT 0 COMMENT '排序' AFTER `ratio`,
  ADD KEY `idx_group_id` (`group_id`);

-- 3. 回填旧数据的 code（避免 UNIQUE 冲突）
UPDATE `goods_units` SET `code` = CONCAT('unit_', `id`) WHERE `code` = '' OR `code` IS NULL;

-- 4. 现在再加唯一索引
ALTER TABLE `goods_units` ADD UNIQUE KEY `uk_code` (`code`);

-- 5. 初始化分组与基础单位（仅在还没有分组时插入）
INSERT INTO `goods_unit_groups` (`code`, `name`, `tone`, `sort`, `status`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'weight' AS code, '重量'  AS name, 'rose'   AS tone, 1 AS sort, 1 AS status, NOW() AS created_at, NOW() AS updated_at UNION ALL
  SELECT 'length',         '长度',       'blue',           2,         1,             NOW(),                NOW() UNION ALL
  SELECT 'volume',         '容量',       'cyan',           3,         1,             NOW(),                NOW() UNION ALL
  SELECT 'count',          '计件',       'violet',         4,         1,             NOW(),                NOW() UNION ALL
  SELECT 'time',           '时间',       'amber',          5,         1,             NOW(),                NOW()
) seed
WHERE NOT EXISTS (SELECT 1 FROM `goods_unit_groups` LIMIT 1);
