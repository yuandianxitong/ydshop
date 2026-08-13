-- plugins/article/database/install.sql
-- 裸表名；幂等

CREATE TABLE IF NOT EXISTS `article_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父栏目ID',
  `name` varchar(100) NOT NULL COMMENT '栏目名称',
  `icon` varchar(255) DEFAULT '' COMMENT '栏目图标',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:1启用,0禁用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='文章栏目表';

CREATE TABLE IF NOT EXISTS `articles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL COMMENT '栏目ID',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `cover` varchar(255) DEFAULT '' COMMENT '封面图',
  `summary` varchar(500) DEFAULT '' COMMENT '摘要',
  `content` longtext NOT NULL COMMENT '内容',
  `tags` varchar(500) DEFAULT '[]' COMMENT '标签JSON数组',
  `author` varchar(50) DEFAULT '' COMMENT '作者',
  `view_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '阅读量',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0草稿,1已发布',
  `publish_at` datetime DEFAULT NULL COMMENT '发布时间',
  `admin_id` int(10) unsigned NOT NULL COMMENT '创建管理员ID',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_publish_at` (`publish_at`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='文章表';
