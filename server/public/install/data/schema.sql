-- ============================================================
-- 元点Shop - 数据库结构文件
-- ============================================================

-- 管理员表
CREATE TABLE IF NOT EXISTS `admins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `email` varchar(100) NOT NULL COMMENT '邮箱',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机号',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `department_id` int(10) unsigned DEFAULT NULL COMMENT '部门ID',
  `department` varchar(100) DEFAULT NULL COMMENT '部门（兼容字段）',
  `position` varchar(100) DEFAULT NULL COMMENT '职位',
  `last_login_ip` varchar(45) DEFAULT NULL COMMENT '最后登录IP',
  `last_login_time` timestamp NULL DEFAULT NULL COMMENT '最后登录时间',
  `login_count` int(11) NOT NULL DEFAULT '0' COMMENT '登录次数',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:1正常,0禁用',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT '创建者ID',
  `updated_by` bigint(20) unsigned DEFAULT NULL COMMENT '更新者ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_username_unique` (`username`),
  UNIQUE KEY `admins_email_unique` (`email`),
  KEY `admins_mobile_index` (`mobile`),
  KEY `admins_status_index` (`status`),
  KEY `admins_created_at_index` (`created_at`),
  KEY `idx_department_id` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='管理员表';

-- 角色表
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '角色标识',
  `title` varchar(100) NOT NULL COMMENT '角色名称',
  `description` text COMMENT '角色描述',
  `data_scope` tinyint(1) NOT NULL DEFAULT '1' COMMENT '数据权限:1全部,2自定义,3本部门,4本部门及下级,5仅本人',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统角色:1是,0否',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:1正常,0禁用',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT '创建者ID',
  `updated_by` bigint(20) unsigned DEFAULT NULL COMMENT '更新者ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`),
  KEY `roles_status_index` (`status`),
  KEY `roles_sort_index` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='角色表';

-- 权限表
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '权限标识',
  `title` varchar(100) NOT NULL COMMENT '权限名称',
  `group` varchar(50) NOT NULL COMMENT '权限分组',
  `description` text COMMENT '权限描述',
  `plugin_code` varchar(64) DEFAULT NULL COMMENT '所属插件；NULL=系统权限',
  `guard_name` varchar(50) NOT NULL DEFAULT 'admin' COMMENT '守卫名称',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:1启用,0禁用',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_unique` (`name`),
  KEY `permissions_group_index` (`group`),
  KEY `permissions_guard_name_index` (`guard_name`),
  KEY `permissions_status_index` (`status`),
  KEY `idx_plugin_code` (`plugin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='权限表';

-- 菜单表
CREATE TABLE IF NOT EXISTS `menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '菜单类型:1目录,2菜单,3按钮',
  `title` varchar(100) NOT NULL COMMENT '菜单标题',
  `name` varchar(100) DEFAULT NULL COMMENT '路由名称',
  `path` varchar(200) DEFAULT NULL COMMENT '路由地址',
  `component` varchar(255) DEFAULT NULL COMMENT '组件地址',
  `redirect` varchar(200) DEFAULT NULL COMMENT '重定向地址',
  `icon` varchar(100) DEFAULT NULL COMMENT '菜单图标',
  `permission` varchar(100) DEFAULT NULL COMMENT '权限标识',
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否隐藏:1是,0否',
  `is_cache` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否缓存:1是,0否',
  `is_affix` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否固定标签:1是,0否',
  `is_iframe` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否内嵌:1是,0否',
  `external_link` varchar(255) DEFAULT NULL COMMENT '外链地址',
  `breadcrumb` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否显示面包屑:1是,0否',
  `active_menu` varchar(200) DEFAULT NULL COMMENT '高亮菜单',
  `meta` json DEFAULT NULL COMMENT '扩展元数据',
  `plugin_code` varchar(64) DEFAULT NULL COMMENT '所属插件；NULL=系统菜单',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:1启用,0禁用',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT '创建者ID',
  `updated_by` bigint(20) unsigned DEFAULT NULL COMMENT '更新者ID',
  PRIMARY KEY (`id`),
  KEY `menus_parent_id_index` (`parent_id`),
  KEY `menus_type_index` (`type`),
  KEY `menus_name_index` (`name`),
  KEY `menus_path_index` (`path`),
  KEY `menus_permission_index` (`permission`),
  KEY `menus_status_index` (`status`),
  KEY `menus_sort_index` (`sort`),
  KEY `idx_plugin_code` (`plugin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='菜单表';

-- 管理员角色关联表
CREATE TABLE IF NOT EXISTS `admin_roles` (
  `admin_id` bigint(20) unsigned NOT NULL COMMENT '管理员ID',
  `role_id` bigint(20) unsigned NOT NULL COMMENT '角色ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`admin_id`,`role_id`),
  KEY `admin_roles_admin_id_index` (`admin_id`),
  KEY `admin_roles_role_id_index` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='管理员角色关联表';

-- 角色权限关联表
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` bigint(20) unsigned NOT NULL COMMENT '角色ID',
  `permission_id` bigint(20) unsigned NOT NULL COMMENT '权限ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `role_permissions_role_id_index` (`role_id`),
  KEY `role_permissions_permission_id_index` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='角色权限关联表';

-- 角色菜单关联表
CREATE TABLE IF NOT EXISTS `role_menus` (
  `role_id` bigint(20) unsigned NOT NULL COMMENT '角色ID',
  `menu_id` bigint(20) unsigned NOT NULL COMMENT '菜单ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`role_id`,`menu_id`),
  KEY `role_menus_role_id_index` (`role_id`),
  KEY `role_menus_menu_id_index` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='角色菜单关联表';

-- 管理员登录日志表
CREATE TABLE IF NOT EXISTS `admin_login_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned NOT NULL COMMENT '管理员ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `ip` varchar(45) NOT NULL COMMENT '登录IP',
  `user_agent` text COMMENT '用户代理',
  `login_time` timestamp NOT NULL COMMENT '登录时间',
  `login_result` tinyint(1) NOT NULL COMMENT '登录结果:1成功,0失败',
  `login_message` varchar(255) DEFAULT NULL COMMENT '登录消息',
  `browser` varchar(100) DEFAULT NULL COMMENT '浏览器',
  `os` varchar(100) DEFAULT NULL COMMENT '操作系统',
  PRIMARY KEY (`id`),
  KEY `admin_login_logs_admin_id_index` (`admin_id`),
  KEY `idx_admin_login_time` (`admin_id`, `login_time`),
  KEY `admin_login_logs_username_index` (`username`),
  KEY `admin_login_logs_ip_index` (`ip`),
  KEY `admin_login_logs_login_time_index` (`login_time`),
  KEY `admin_login_logs_login_result_index` (`login_result`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='管理员登录日志表';

-- 管理员操作日志表
CREATE TABLE IF NOT EXISTS `admin_operation_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned NOT NULL COMMENT '管理员ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `method` varchar(10) NOT NULL COMMENT '请求方法',
  `path` varchar(255) NOT NULL COMMENT '请求路径',
  `ip` varchar(45) NOT NULL COMMENT '操作IP',
  `user_agent` text COMMENT '用户代理',
  `action` varchar(100) NOT NULL COMMENT '操作动作',
  `description` varchar(255) NOT NULL COMMENT '操作描述',
  `params` json DEFAULT NULL COMMENT '请求参数',
  `result` json DEFAULT NULL COMMENT '操作结果',
  `operation_time` timestamp NOT NULL COMMENT '操作时间',
  `execution_time` decimal(8,3) DEFAULT NULL COMMENT '执行时间(秒)',
  PRIMARY KEY (`id`),
  KEY `admin_operation_logs_admin_id_index` (`admin_id`),
  KEY `admin_operation_logs_username_index` (`username`),
  KEY `admin_operation_logs_method_index` (`method`),
  KEY `admin_operation_logs_path_index` (`path`),
  KEY `admin_operation_logs_action_index` (`action`),
  KEY `admin_operation_logs_operation_time_index` (`operation_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='管理员操作日志表';

-- 系统配置表
CREATE TABLE IF NOT EXISTS `system_configs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL COMMENT '配置键',
  `config_value` text COMMENT '配置值',
  `config_group` varchar(50) NOT NULL DEFAULT 'basic' COMMENT '配置分组',
  `config_type` varchar(20) NOT NULL DEFAULT 'string' COMMENT '配置类型:string,number,boolean,json,file',
  `config_name` varchar(100) NOT NULL COMMENT '配置名称',
  `config_desc` varchar(255) DEFAULT NULL COMMENT '配置描述',
  `config_options` json DEFAULT NULL COMMENT '下拉选项 {"value":"label",...}',
  `config_depends` json DEFAULT NULL COMMENT '显示依赖 {"field":"xxx","value":"yyy"} 或 {"field":"xxx","value":["a","b"]}',
  `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:1启用,0禁用',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_configs_key_unique` (`config_key`),
  KEY `system_configs_group_index` (`config_group`),
  KEY `system_configs_status_index` (`status`),
  KEY `system_configs_sort_order_index` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='系统配置表';

-- ============================================================
-- 部门表
-- ============================================================
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '上级部门ID',
  `name` varchar(100) NOT NULL COMMENT '部门名称',
  `code` varchar(50) DEFAULT NULL COMMENT '部门编码',
  `leader` varchar(50) DEFAULT NULL COMMENT '负责人',
  `phone` varchar(20) DEFAULT NULL COMMENT '联系电话',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1正常 0禁用',
  `sort` int(10) NOT NULL DEFAULT 0 COMMENT '排序',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `created_by` int(10) unsigned DEFAULT NULL COMMENT '创建人',
  `updated_by` int(10) unsigned DEFAULT NULL COMMENT '更新人',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='部门表';

-- ============================================================
-- 数据字典表
-- ============================================================
CREATE TABLE IF NOT EXISTS `dictionaries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '字典名称',
  `code` varchar(100) NOT NULL COMMENT '字典编码（唯一标识）',
  `description` varchar(500) DEFAULT '' COMMENT '描述',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='数据字典表';

CREATE TABLE IF NOT EXISTS `dictionary_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `dictionary_id` int unsigned NOT NULL COMMENT '字典ID',
  `label` varchar(100) NOT NULL COMMENT '标签（显示文本）',
  `value` varchar(100) NOT NULL COMMENT '值',
  `tag_type` varchar(50) DEFAULT '' COMMENT '标签类型（success/warning/danger/info）',
  `description` varchar(500) DEFAULT '' COMMENT '描述',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dictionary_id` (`dictionary_id`),
  KEY `idx_status` (`status`),
  UNIQUE KEY `uk_dict_value` (`dictionary_id`, `value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='数据字典项表';

-- ============================================================
-- 文件管理表
-- ============================================================
CREATE TABLE IF NOT EXISTS `files` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '文件名',
  `path` varchar(500) NOT NULL COMMENT '相对路径',
  `url` varchar(500) NOT NULL COMMENT '完整URL',
  `mime_type` varchar(100) NOT NULL DEFAULT '' COMMENT 'MIME类型',
  `extension` varchar(20) NOT NULL DEFAULT '' COMMENT '文件扩展名',
  `size` bigint unsigned NOT NULL DEFAULT 0 COMMENT '文件大小（字节）',
  `group` varchar(100) NOT NULL DEFAULT '默认' COMMENT '分组',
  `category_id` int unsigned NOT NULL DEFAULT 0 COMMENT '文件分类ID',
  `upload_by` int unsigned NOT NULL DEFAULT 0 COMMENT '上传者ID',
  `storage` varchar(50) NOT NULL DEFAULT 'local' COMMENT '存储方式：local/oss/cos/qiniu',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_group` (`group`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_mime_type` (`mime_type`),
  KEY `idx_upload_by` (`upload_by`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='文件管理表';

-- ============================================================
-- 通知消息表
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL COMMENT '通知标题',
  `content` text COMMENT '通知内容',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '类型：1系统通知 2待办提醒 3业务消息',
  `sender_id` int(10) unsigned DEFAULT NULL COMMENT '发送者ID（NULL为系统）',
  `target_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '目标类型：1全部 2指定用户',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1正常 0禁用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_sender_id` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='通知消息表';

CREATE TABLE IF NOT EXISTS `notification_reads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` int(10) unsigned NOT NULL COMMENT '通知ID',
  `admin_id` int(10) unsigned NOT NULL COMMENT '管理员ID',
  `read_at` datetime DEFAULT NULL COMMENT '阅读时间',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_notification_admin` (`notification_id`, `admin_id`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='通知已读记录表';

-- ============================================================
-- 定时任务表
-- ============================================================
CREATE TABLE IF NOT EXISTS `cron_jobs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '任务名称',
  `command` varchar(255) NOT NULL COMMENT '执行命令（ThinkPHP命令）',
  `expression` varchar(100) NOT NULL COMMENT 'Cron表达式（如 */5 * * * *）',
  `description` varchar(255) DEFAULT NULL COMMENT '任务描述',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `last_run_at` datetime DEFAULT NULL COMMENT '上次执行时间',
  `last_result` text COMMENT '上次执行结果',
  `last_status` tinyint(1) DEFAULT NULL COMMENT '上次执行状态：1成功 0失败',
  `run_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '累计执行次数',
  `sort` int(10) NOT NULL DEFAULT 0 COMMENT '排序',
  `created_by` int(10) unsigned DEFAULT NULL COMMENT '创建人',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='定时任务表';

CREATE TABLE IF NOT EXISTS `cron_job_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cron_job_id` int(10) unsigned NOT NULL COMMENT '任务ID',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1成功 0失败',
  `output` text COMMENT '输出内容',
  `error` text COMMENT '错误信息',
  `started_at` datetime DEFAULT NULL COMMENT '开始时间',
  `finished_at` datetime DEFAULT NULL COMMENT '结束时间',
  `duration` int(10) unsigned DEFAULT 0 COMMENT '执行耗时(毫秒)',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cron_job_id` (`cron_job_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='定时任务执行日志表';

-- ============================================================
-- 支付订单表
-- ============================================================
CREATE TABLE IF NOT EXISTS `payment_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL COMMENT '用户ID',
  `biz_type` varchar(30) DEFAULT NULL COMMENT '业务类型',
  `business_order_no` varchar(64) DEFAULT NULL COMMENT '业务订单号；同一业务可对应多次渠道支付尝试',
  `client_type` varchar(20) DEFAULT NULL COMMENT '客户端类型',
  `order_no` varchar(64) NOT NULL COMMENT '商户订单号',
  `trade_no` varchar(128) DEFAULT NULL COMMENT '第三方交易号',
  `channel` varchar(20) NOT NULL COMMENT '支付渠道：alipay/wechat',
  `trade_type` varchar(20) NOT NULL DEFAULT 'native' COMMENT '交易类型：native/jsapi/h5/app/page/wap',
  `subject` varchar(255) NOT NULL DEFAULT '' COMMENT '订单标题',
  `body` varchar(500) DEFAULT NULL COMMENT '订单描述',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '订单金额(元)',
  `refund_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额(元)',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '状态：creating/pending/closing/paid/closed/refunded',
  `active_business_key` varchar(191) GENERATED ALWAYS AS (
    CASE
      WHEN `biz_type` = 'order'
       AND `business_order_no` IS NOT NULL
       AND `business_order_no` <> ''
       AND `status` IN ('creating','pending','closing','paid','refunded')
      THEN CONCAT('order:', `business_order_no`)
      ELSE NULL
    END
  ) STORED,
  `notify_data` text COMMENT '回调原始数据(JSON)',
  `extra` text COMMENT '扩展数据(JSON)',
  `provider_account_id` varchar(128) DEFAULT NULL COMMENT '签发时收款主体：支付宝app_id/微信mch_id',
  `provider_app_id` varchar(128) DEFAULT NULL COMMENT '签发时客户端AppID',
  `provider_expires_at` datetime DEFAULT NULL COMMENT '已签发支付凭据的渠道失效时间',
  `provider_attempt_token` varchar(64) DEFAULT NULL COMMENT '当前渠道创建尝试栅栏',
  `provider_request_hash` char(64) DEFAULT NULL COMMENT '当前支付调起参数指纹',
  `provider_reconcile_retry_count` int unsigned NOT NULL DEFAULT 0 COMMENT '渠道对账连续失败次数',
  `provider_reconcile_next_at` datetime DEFAULT NULL COMMENT '下次渠道对账时间',
  `provider_reconcile_last_error` varchar(500) NOT NULL DEFAULT '' COMMENT '最近渠道对账错误',
  `paid_at` datetime DEFAULT NULL COMMENT '支付时间',
  `refunded_at` datetime DEFAULT NULL COMMENT '退款时间',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_trade_no` (`trade_no`),
  KEY `idx_channel` (`channel`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_biz_type` (`biz_type`),
  KEY `idx_business_order_no` (`biz_type`,`business_order_no`,`id`),
  UNIQUE KEY `uk_active_business_payment` (`active_business_key`),
  KEY `idx_provider_reconcile_due` (`status`,`provider_reconcile_next_at`,`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='支付订单表';

-- ============================================================
-- 消息模板表
-- ============================================================
CREATE TABLE IF NOT EXISTS `message_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '模板名称',
  `code` varchar(50) NOT NULL COMMENT '模板标识',
  `sms_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '启用短信',
  `sms_template_id` varchar(100) DEFAULT '' COMMENT '短信模板ID',
  `sms_content` varchar(500) DEFAULT '' COMMENT '短信内容预览',
  `wechat_official_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '启用公众号模板消息',
  `wechat_official_template_id` varchar(100) DEFAULT '' COMMENT '公众号模板ID',
  `wechat_official_url` varchar(500) DEFAULT '' COMMENT '模板消息跳转URL',
  `wechat_mini_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '启用小程序订阅消息',
  `wechat_mini_template_id` varchar(100) DEFAULT '' COMMENT '小程序模板ID',
  `wechat_mini_page` varchar(200) DEFAULT '' COMMENT '小程序跳转页面',
  `variables` json DEFAULT NULL COMMENT '模板变量定义',
  `remark` varchar(500) DEFAULT '' COMMENT '备注',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:1启用,0禁用',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='消息模板表';

-- ============================================================
-- 消息发送记录表
-- ============================================================
CREATE TABLE IF NOT EXISTS `message_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint(20) unsigned DEFAULT NULL COMMENT '模板ID',
  `template_code` varchar(50) DEFAULT '' COMMENT '模板标识',
  `event_key` varchar(191) DEFAULT NULL COMMENT '领域事件幂等键，NULL表示手工发送',
  `channel` varchar(20) NOT NULL COMMENT '发送通道:sms,wechat_official,wechat_mini',
  `receiver` varchar(200) NOT NULL COMMENT '接收者',
  `content` text COMMENT '发送内容',
  `variables` json DEFAULT NULL COMMENT '模板变量值',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0待发送,1成功,2失败',
  `error_msg` text COMMENT '错误信息',
  `sent_at` timestamp NULL DEFAULT NULL COMMENT '发送时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_message_event_receiver` (`event_key`, `channel`, `receiver`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_channel` (`channel`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='消息发送记录表';

-- ============================================================
-- 微信自动回复表
-- ============================================================
CREATE TABLE IF NOT EXISTS `wechat_auto_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL COMMENT '类型:keyword,subscribe,default',
  `keyword` varchar(200) DEFAULT '' COMMENT '关键词',
  `match_type` varchar(10) DEFAULT 'exact' COMMENT '匹配方式:exact,fuzzy',
  `reply_type` varchar(10) DEFAULT 'text' COMMENT '回复类型:text,image,news',
  `content` text COMMENT '回复内容',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:1启用,0禁用',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_keyword` (`keyword`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='微信自动回复表';

-- ============================================================
-- 用户表（C端移动端用户）
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机号',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `password` varchar(255) DEFAULT NULL COMMENT '密码',
  `gender` tinyint(1) NOT NULL DEFAULT 0 COMMENT '性别：0未知 1男 2女',
  `birthday` date DEFAULT NULL COMMENT '生日',
  `openid` varchar(128) DEFAULT NULL COMMENT '微信openid',
  `oa_openid` varchar(128) DEFAULT NULL COMMENT '公众号openid',
  `unionid` varchar(128) DEFAULT NULL COMMENT '微信unionid',
  `mini_openid` varchar(128) DEFAULT NULL COMMENT '小程序openid',
  `last_login_ip` varchar(45) DEFAULT NULL COMMENT '最后登录IP',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `login_count` int(11) NOT NULL DEFAULT 0 COMMENT '登录次数',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1正常 0禁用',
  `member_level_id` int DEFAULT 0 COMMENT '会员等级ID',
  `growth_value` int DEFAULT 0 COMMENT '成长值',
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '余额',
  `commission_debt` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '分销佣金退款冲正待偿债务',
  `points_debt` int NOT NULL DEFAULT 0 COMMENT '订单奖励退款冲正待偿积分债务',
  `points` int DEFAULT 0 COMMENT '积分',
  `total_points` int DEFAULT 0 COMMENT '累计积分',
  `total_consume` decimal(10,2) DEFAULT 0.00 COMMENT '累计消费',
  `order_count` int DEFAULT 0 COMMENT '订单数',
  `is_distributor` tinyint(1) DEFAULT 0 COMMENT '是否分销员',
  `distributor_level_id` int DEFAULT 0 COMMENT '分销等级ID',
  `inviter_id` int DEFAULT 0 COMMENT '邀请人ID',
  `invite_code` varchar(20) DEFAULT '' COMMENT '邀请码',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_mobile` (`mobile`),
  KEY `idx_openid` (`openid`),
  KEY `idx_oa_openid` (`oa_openid`),
  KEY `idx_unionid` (`unionid`),
  KEY `idx_mini_openid` (`mini_openid`),
  KEY `idx_status` (`status`),
  KEY `idx_member_level_id` (`member_level_id`),
  KEY `idx_inviter_id` (`inviter_id`),
  KEY `idx_invite_code` (`invite_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户表';

-- ============================================================
-- 公告表
-- ============================================================
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL COMMENT '标题',
  `content` text COMMENT '内容',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '类型：1通知 2更新 3活动',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0草稿 1已发布',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `publish_at` datetime DEFAULT NULL COMMENT '发布时间',
  `admin_id` int(10) unsigned NOT NULL COMMENT '管理员ID',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='公告表';

-- ============================================================
-- 用户反馈表
-- ============================================================
CREATE TABLE IF NOT EXISTS `feedbacks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `type` varchar(30) NOT NULL DEFAULT 'suggestion' COMMENT '类型：suggestion/bug/complaint/other',
  `content` text COMMENT '反馈内容',
  `images` text COMMENT '图片路径JSON数组',
  `contact` varchar(100) DEFAULT NULL COMMENT '联系方式',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0待处理 1处理中 2已回复 3已关闭',
  `reply` text COMMENT '管理员回复',
  `replied_at` datetime DEFAULT NULL COMMENT '回复时间',
  `replied_by` int(10) unsigned DEFAULT NULL COMMENT '回复人ID',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户反馈表';

-- ============================================================
-- 地区表
-- ============================================================
CREATE TABLE IF NOT EXISTS `regions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '父级ID',
  `name` varchar(50) NOT NULL COMMENT '名称',
  `code` varchar(20) NOT NULL DEFAULT '' COMMENT '编码',
  `level` tinyint(1) NOT NULL DEFAULT 1 COMMENT '层级：1省 2市 3区',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='地区表';

-- ============================================================
-- APP版本表
-- ============================================================
CREATE TABLE IF NOT EXISTS `app_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `platform` varchar(20) NOT NULL COMMENT '平台',
  `version` varchar(20) NOT NULL COMMENT '版本号',
  `version_code` int(10) unsigned NOT NULL COMMENT '版本编码',
  `download_url` varchar(500) NOT NULL DEFAULT '' COMMENT '下载地址',
  `description` text COMMENT '版本描述',
  `force_update` tinyint(1) NOT NULL DEFAULT 0 COMMENT '强制更新：0否 1是',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_platform_version` (`platform`, `version_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='APP版本表';

-- ============================================================
-- 数据导入表
-- ============================================================
CREATE TABLE IF NOT EXISTS `data_imports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL COMMENT '模块',
  `filename` varchar(200) NOT NULL COMMENT '文件名',
  `total_count` int(11) NOT NULL DEFAULT 0 COMMENT '总条数',
  `success_count` int(11) NOT NULL DEFAULT 0 COMMENT '成功条数',
  `fail_count` int(11) NOT NULL DEFAULT 0 COMMENT '失败条数',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0处理中 1完成 2失败',
  `errors` text COMMENT '错误信息JSON',
  `admin_id` int(10) unsigned NOT NULL COMMENT '管理员ID',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_module` (`module`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='数据导入表';

-- ============================================================
-- 用户站内通知表
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID，0为全体',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `content` text COMMENT '内容',
  `type` varchar(30) NOT NULL DEFAULT 'system' COMMENT '类型：system/order/payment/feedback',
  `biz_id` bigint(20) unsigned DEFAULT NULL COMMENT '关联业务ID',
  `event_key` varchar(191) DEFAULT NULL COMMENT '领域事件幂等键',
  `extra` text COMMENT '额外数据JSON',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  UNIQUE KEY `uk_user_notification_event` (`user_id`, `event_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户站内通知表';

-- ============================================================
-- 用户通知已读记录表
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_notification_reads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` bigint(20) unsigned NOT NULL COMMENT '通知ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `read_at` datetime DEFAULT NULL COMMENT '阅读时间',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_notification_user_unique` (`notification_id`, `user_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户通知已读记录表';

-- ============================================================
-- 帮助中心
-- ============================================================
CREATE TABLE IF NOT EXISTS `help_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '分类名称',
  `icon` varchar(255) DEFAULT '' COMMENT '分类图标',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=启用 / 0=停用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='帮助分类';

CREATE TABLE IF NOT EXISTS `helps` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned NOT NULL COMMENT '所属分类',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `summary` varchar(500) DEFAULT '' COMMENT '摘要',
  `content` longtext NOT NULL COMMENT '富文本内容',
  `view_count` int unsigned NOT NULL DEFAULT 0 COMMENT '阅读量',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=草稿 / 1=已发布',
  `admin_id` int unsigned NOT NULL COMMENT '创建管理员 ID',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='帮助';

-- ============================================================
-- 余额变动记录表
-- ============================================================
CREATE TABLE IF NOT EXISTS `balance_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `amount` decimal(10,2) NOT NULL COMMENT '变动金额',
  `before_balance` decimal(10,2) NOT NULL COMMENT '变动前余额',
  `after_balance` decimal(10,2) NOT NULL COMMENT '变动后余额',
  `type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '类型:1充值,2消费,3退款,4后台调整,5分销佣金结算,6分销提现冻结,7分销提现退回,8分销佣金退款冲正',
  `source` varchar(50) NOT NULL DEFAULT '' COMMENT '来源标识',
  `event_key` varchar(191) DEFAULT NULL COMMENT '领域事件幂等键',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `operator_id` int(10) unsigned DEFAULT NULL COMMENT '操作管理员ID',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_balance_event_key` (`user_id`, `event_key`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='余额变动记录';

-- ============================================================
-- 积分变动记录表
-- ============================================================
CREATE TABLE IF NOT EXISTS `points_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `points` int(11) NOT NULL COMMENT '变动积分',
  `before_points` int(11) NOT NULL COMMENT '变动前积分',
  `after_points` int(11) NOT NULL COMMENT '变动后积分',
  `type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '类型:1后台调整,2注册赠送,3签到,4消费赠送,5消费扣减,6积分商城,7抽奖中奖,8抽奖扣减,9充值赠送,10消费奖励冲正',
  `source` varchar(50) NOT NULL DEFAULT '' COMMENT '来源标识',
  `event_key` varchar(191) DEFAULT NULL COMMENT '领域事件幂等键',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `operator_id` int(10) unsigned DEFAULT NULL COMMENT '操作管理员ID',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_points_event_key` (`user_id`, `event_key`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_points_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='积分变动记录';

-- ============================================================
-- 积分债务审计流水
-- ============================================================
CREATE TABLE IF NOT EXISTS `points_debt_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `delta` int NOT NULL COMMENT '债务变动：正数增加，负数偿还',
  `before_debt` int NOT NULL COMMENT '变动前债务',
  `after_debt` int NOT NULL COMMENT '变动后债务',
  `source` varchar(100) NOT NULL DEFAULT '' COMMENT '业务来源',
  `event_key` varchar(191) DEFAULT NULL COMMENT '领域事件幂等键',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '说明',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_points_debt_event_key` (`event_key`),
  KEY `idx_points_debt_user` (`user_id`),
  KEY `idx_points_debt_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='积分债务变动记录';

-- ============================================================
-- 队列失败任务表
-- ============================================================
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `connection` varchar(255) NOT NULL COMMENT '连接名',
  `queue` varchar(255) NOT NULL COMMENT '队列名',
  `payload` longtext NOT NULL COMMENT '任务数据',
  `exception` longtext NOT NULL COMMENT '异常信息',
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '失败时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='队列失败任务表';

-- ============================================================
-- Shop 商城系统扩展表
-- ============================================================

CREATE TABLE IF NOT EXISTS `file_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int unsigned NOT NULL DEFAULT 0 COMMENT '父分类ID，0为顶级',
  `name` varchar(50) NOT NULL COMMENT '分类名称',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='文件分类表';

CREATE TABLE IF NOT EXISTS `goods_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int DEFAULT '0' COMMENT '父分类ID',
  `name` varchar(50) DEFAULT NULL COMMENT '分类名称',
  `icon` varchar(255) DEFAULT '' COMMENT '图标',
  `sort` int DEFAULT '0' COMMENT '排序',
  `level` tinyint DEFAULT '1' COMMENT '层级',
  `path` varchar(255) DEFAULT '0' COMMENT '祖先链',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态:1启用,0禁用',
  `is_show` tinyint(1) DEFAULT '1' COMMENT '是否前端展示',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='商品分类';

CREATE TABLE IF NOT EXISTS `goods_brands` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL COMMENT '品牌名称',
  `logo` varchar(255) DEFAULT '' COMMENT 'Logo',
  `description` text COMMENT '描述',
  `sort` int DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态:1启用,0禁用',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='商品品牌';

CREATE TABLE IF NOT EXISTS `goods_unit_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL DEFAULT '' COMMENT '分组编码（唯一）',
  `name` varchar(40) NOT NULL DEFAULT '' COMMENT '分组名称',
  `tone` varchar(20) NOT NULL DEFAULT 'blue' COMMENT '色调',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:1启用,0禁用',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='计量单位分组';

CREATE TABLE IF NOT EXISTS `goods_units` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL DEFAULT '' COMMENT '单位编码（唯一）',
  `name` varchar(20) DEFAULT NULL COMMENT '单位名称',
  `name_en` varchar(40) NOT NULL DEFAULT '' COMMENT '英文名',
  `group_id` int unsigned NOT NULL DEFAULT 0 COMMENT '所属分组ID',
  `decimal_places` tinyint NOT NULL DEFAULT 2 COMMENT '小数位数',
  `is_base` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否基准单位:1是,0否',
  `ratio` decimal(20,6) NOT NULL DEFAULT 1.000000 COMMENT '相对基准单位的换算系数（基准为 1）',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态:1启用,0禁用',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='计量单位';

CREATE TABLE IF NOT EXISTS `goods_spec_names` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `spu_id` int DEFAULT NULL COMMENT 'SPU ID',
  `name` varchar(50) DEFAULT NULL COMMENT '规格名',
  `sort` int DEFAULT '0' COMMENT '排序',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `spu_id` (`spu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='规格名';

CREATE TABLE IF NOT EXISTS `goods_spec_values` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `spec_name_id` int DEFAULT NULL COMMENT '规格名ID',
  `value` varchar(100) DEFAULT NULL COMMENT '规格值',
  `sort` int DEFAULT '0' COMMENT '排序',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `spec_name_id` (`spec_name_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='规格值';

CREATE TABLE IF NOT EXISTS `goods_attribute_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL COMMENT '分组名称',
  `category_id` int DEFAULT '0' COMMENT '分类ID',
  `sort` int DEFAULT '0' COMMENT '排序',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='属性分组';

CREATE TABLE IF NOT EXISTS `goods_attributes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int DEFAULT NULL COMMENT '分组ID',
  `name` varchar(50) DEFAULT NULL COMMENT '属性名称',
  `type` varchar(20) DEFAULT 'input' COMMENT 'input/select/multi_select',
  `options` json DEFAULT NULL COMMENT '预设值',
  `sort` int DEFAULT '0' COMMENT '排序',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='属性定义';

CREATE TABLE IF NOT EXISTS `goods_attribute_values` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `spu_id` int DEFAULT NULL COMMENT 'SPU ID',
  `attribute_id` int DEFAULT NULL COMMENT '属性ID',
  `value` varchar(255) DEFAULT '' COMMENT '属性值',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `spu_id` (`spu_id`),
  KEY `attribute_id` (`attribute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='SPU属性值';

CREATE TABLE IF NOT EXISTS `goods_spec_templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL COMMENT '模板名称',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '模板备注',
  `items` json DEFAULT NULL COMMENT '规格项 [{name, values:[]}]',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1启用 0禁用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='规格模板';

CREATE TABLE IF NOT EXISTS `goods_freight_templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL COMMENT '模板名称',
  `charge_type` enum('weight','volume','piece') DEFAULT 'piece' COMMENT '计费方式',
  `is_free` tinyint(1) DEFAULT '0' COMMENT '是否免运费',
  `sort` int DEFAULT '0' COMMENT '排序',
  `no_delivery_region_ids` json DEFAULT NULL COMMENT '不送达区域（省级ID数组）',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='运费模板';

CREATE TABLE IF NOT EXISTS `goods_freight_template_rules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int DEFAULT NULL COMMENT '模板ID',
  `region_ids` json DEFAULT NULL COMMENT '地区ID列表',
  `first_unit` decimal(10,2) DEFAULT '1.00' COMMENT '首件/首重/首体积',
  `first_price` decimal(10,2) DEFAULT '0.00' COMMENT '首费',
  `continue_unit` decimal(10,2) DEFAULT '1.00' COMMENT '续件/续重/续体积',
  `continue_price` decimal(10,2) DEFAULT '0.00' COMMENT '续费',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='运费规则';

CREATE TABLE IF NOT EXISTS `goods_freight_template_free_rules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int unsigned NOT NULL COMMENT '关联模板ID',
  `region_ids` json NOT NULL COMMENT '包邮区域（省级ID数组）',
  `free_num` int NOT NULL DEFAULT 0 COMMENT '满几件包邮（0不启用）',
  `free_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '满多少元包邮（0不启用）',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='运费模板包邮规则';

CREATE TABLE IF NOT EXISTS `delivery_staff` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '姓名',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:1在岗,0休息',
  `current_lat` decimal(10, 7) DEFAULT NULL COMMENT '当前位置纬度',
  `current_lng` decimal(10, 7) DEFAULT NULL COMMENT '当前位置经度',
  `location_updated_at` datetime DEFAULT NULL COMMENT '位置上报时间',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='配送员';

CREATE TABLE IF NOT EXISTS `delivery_orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL COMMENT '关联订单ID',
  `staff_id` int unsigned NOT NULL DEFAULT 0 COMMENT '配送员ID',
  `platform` varchar(20) NOT NULL DEFAULT 'merchant' COMMENT '配送平台:merchant/dada/fengniao/uupt/shansong/sfsc',
  `platform_order_id` varchar(64) DEFAULT NULL COMMENT '三方平台运单号',
  `platform_status` varchar(32) NOT NULL DEFAULT '' COMMENT '三方平台原始状态码（透传）',
  `rider_name` varchar(50) NOT NULL DEFAULT '' COMMENT '三方骑手姓名',
  `rider_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '三方骑手电话',
  `rider_lat` decimal(10, 7) DEFAULT NULL COMMENT '骑手实时纬度',
  `rider_lng` decimal(10, 7) DEFAULT NULL COMMENT '骑手实时经度',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '状态:pending/assigned/picking/picked/delivering/completed/cancelled',
  `distance` decimal(6,2) NOT NULL DEFAULT 0.00 COMMENT '配送距离(km)',
  `fee` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '配送费',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `dest_lat` decimal(10, 7) DEFAULT NULL COMMENT '目的地纬度（geocoded 收货地址）',
  `dest_lng` decimal(10, 7) DEFAULT NULL COMMENT '目的地经度',
  `assigned_at` datetime DEFAULT NULL COMMENT '分配时间',
  `dispatched_at` datetime DEFAULT NULL COMMENT '三方发单成功时间',
  `dispatch_fail_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '最近一次发单失败原因',
  `callback_raw` json DEFAULT NULL COMMENT '最近一次三方回调原始报文（排障用）',
  `picked_at` datetime DEFAULT NULL COMMENT '取货时间',
  `completed_at` datetime DEFAULT NULL COMMENT '完成时间',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_delivery_order_id` (`order_id`),
  UNIQUE KEY `uk_platform_order` (`platform`, `platform_order_id`),
  KEY `staff_id` (`staff_id`),
  KEY `status` (`status`),
  KEY `idx_platform_status` (`platform`, `status`),
  KEY `idx_dest_geo` (`dest_lat`, `dest_lng`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='配送记录';

CREATE TABLE IF NOT EXISTS `delivery_order_tracks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `delivery_order_id` int unsigned NOT NULL COMMENT '关联配送记录ID',
  `lat` decimal(10, 7) NOT NULL COMMENT '纬度',
  `lng` decimal(10, 7) NOT NULL COMMENT '经度',
  `platform_status` varchar(32) NOT NULL DEFAULT '' COMMENT '上报时的平台原始状态',
  `reported_at` datetime NOT NULL COMMENT '轨迹点时间',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_delivery_order` (`delivery_order_id`, `reported_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='配送轨迹点（三方回调/同步追加）';

CREATE TABLE IF NOT EXISTS `delivery_shifts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int unsigned NOT NULL COMMENT '配送员ID',
  `weekday` tinyint unsigned NOT NULL COMMENT '周几 1=周一..7=周日',
  `start_time` time NOT NULL COMMENT '上班时间',
  `end_time` time NOT NULL COMMENT '下班时间',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_staff_weekday` (`staff_id`, `weekday`),
  KEY `idx_weekday_time` (`weekday`, `start_time`, `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='配送员班次（周模板）';

CREATE TABLE IF NOT EXISTS `delivery_exception_tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_no` varchar(20) NOT NULL COMMENT '业务单号 EX20260429001',
  `type` enum('delay','wrong_delivery','complaint','reassign','weather') NOT NULL COMMENT '类型',
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open' COMMENT '状态',
  `delivery_order_id` int unsigned DEFAULT NULL COMMENT '关联配送单（可空，泛事件无关联）',
  `title` varchar(100) NOT NULL COMMENT '简述',
  `description` text DEFAULT NULL COMMENT '详情',
  `evidence` json DEFAULT NULL COMMENT '图片附件 URL 数组',
  `reporter` varchar(50) DEFAULT NULL COMMENT '报告人姓名',
  `contact` varchar(100) DEFAULT NULL COMMENT '联系方式',
  `resolution_note` text DEFAULT NULL COMMENT '解决说明',
  `handled_by` int unsigned DEFAULT NULL COMMENT '处理 admin id',
  `handled_at` datetime DEFAULT NULL COMMENT '处理时间',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ticket_no` (`ticket_no`),
  KEY `idx_type_status` (`type`, `status`),
  KEY `idx_delivery_order` (`delivery_order_id`),
  KEY `idx_handled_by` (`handled_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='配送异常工单';

CREATE TABLE IF NOT EXISTS `express_companies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '公司名称',
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '编码',
  `logo` varchar(255) NOT NULL DEFAULT '' COMMENT 'Logo',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:1启用,0禁用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='物流公司';

CREATE TABLE IF NOT EXISTS `waybill_templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '模版名称',
  `express_code` varchar(50) NOT NULL DEFAULT '' COMMENT '快递鸟 ShipperCode',
  `express_name` varchar(50) NOT NULL DEFAULT '' COMMENT '物流公司名称',
  `exp_type` varchar(20) NOT NULL DEFAULT '1' COMMENT '业务类型 ExpType',
  `exp_type_name` varchar(50) NOT NULL DEFAULT '' COMMENT '业务类型名称',
  `template_size` varchar(20) NOT NULL DEFAULT '' COMMENT '模版尺寸 TemplateSize，空=默认',
  `template_size_label` varchar(50) NOT NULL DEFAULT '' COMMENT '模版尺寸展示名',
  `pay_type` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '邮费支付方式 PayType:1现付,2到付,3月结',
  `need_pickup` tinyint(1) NOT NULL DEFAULT 0 COMMENT '快递员上门揽件:1是,0否（映射 IsNotice 0/1）',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认模版:1是,0否',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:1启用,0禁用',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_express_code` (`express_code`),
  KEY `idx_status_sort` (`status`, `sort`),
  KEY `idx_is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='电子面单模版';

CREATE TABLE IF NOT EXISTS `goods_spu` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `spu_no` varchar(32) DEFAULT NULL COMMENT 'SPU编号',
  `name` varchar(200) DEFAULT NULL COMMENT '商品名称',
  `subtitle` varchar(300) DEFAULT '' COMMENT '副标题',
  `category_id` int DEFAULT '0' COMMENT '分类ID',
  `brand_id` int DEFAULT '0' COMMENT '品牌ID',
  `unit_id` int DEFAULT '0' COMMENT '单位ID',
  `type` enum('physical','virtual','combo') DEFAULT 'physical' COMMENT '类型',
  `images` json DEFAULT NULL COMMENT '图片列表',
  `video` varchar(255) DEFAULT '' COMMENT '主图视频',
  `description` longtext COMMENT '描述',
  `detail` longtext COMMENT '详情(富文本)',
  `min_price` decimal(10,2) DEFAULT '0.00' COMMENT '最低价',
  `max_price` decimal(10,2) DEFAULT '0.00' COMMENT '最高价',
  `total_stock` int DEFAULT '0' COMMENT '总库存',
  `sales_count` int DEFAULT '0' COMMENT '销售量',
  `view_count` int DEFAULT '0' COMMENT '浏览量',
  `status` enum('draft','on_sale','off_sale') DEFAULT 'draft' COMMENT '状态',
  `delivery_modes` json DEFAULT NULL COMMENT '支持的配送方式数组，NULL 视为 [express]',
  `sort` int DEFAULT '0' COMMENT '排序',
  `is_recommend` tinyint(1) DEFAULT '0' COMMENT '是否推荐',
  `is_new` tinyint(1) DEFAULT '0' COMMENT '是否新品',
  `is_hot` tinyint(1) DEFAULT '0' COMMENT '是否热销',
  `freight_template_id` int DEFAULT '0' COMMENT '运费模板ID',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `spu_no` (`spu_no`),
  KEY `category_id` (`category_id`),
  KEY `brand_id` (`brand_id`),
  KEY `status` (`status`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='商品SPU';

CREATE TABLE IF NOT EXISTS `goods_sku` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `spu_id` int DEFAULT NULL COMMENT 'SPU ID',
  `sku_no` varchar(32) DEFAULT NULL COMMENT 'SKU编号',
  `spec_value_ids` json DEFAULT NULL COMMENT '规格值ID组合',
  `spec_text` varchar(200) DEFAULT '' COMMENT '规格文本冗余',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '售价',
  `cost_price` decimal(10,2) DEFAULT '0.00' COMMENT '成本价',
  `market_price` decimal(10,2) DEFAULT '0.00' COMMENT '市场价',
  `stock` int DEFAULT '0' COMMENT '库存',
  `sales_count` int DEFAULT '0' COMMENT '销售量',
  `image` varchar(255) DEFAULT '' COMMENT '图片',
  `weight` decimal(10,3) DEFAULT '0.000' COMMENT '重量(kg)',
  `volume` decimal(10,3) DEFAULT '0.000' COMMENT '体积(m³)',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态:1启用,0禁用',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku_no` (`sku_no`),
  KEY `spu_id` (`spu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='商品SKU';

CREATE TABLE IF NOT EXISTS `goods_virtual_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sku_id` int DEFAULT NULL COMMENT 'SKU ID',
  `content` text COMMENT '卡密内容(加密)',
  `order_item_id` int DEFAULT '0' COMMENT '订单项ID',
  `status` enum('unused','sold') DEFAULT 'unused' COMMENT '状态',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `sku_id` (`sku_id`),
  KEY `status` (`status`),
  KEY `order_item_id` (`order_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='虚拟商品卡密';

CREATE TABLE IF NOT EXISTS `goods_combo_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `combo_spu_id` int DEFAULT NULL COMMENT '套餐SPU ID',
  `item_sku_id` int DEFAULT NULL COMMENT '子项SKU ID',
  `quantity` int DEFAULT '1' COMMENT '数量',
  `sort` int DEFAULT '0' COMMENT '排序',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `combo_spu_id` (`combo_spu_id`),
  KEY `item_sku_id` (`item_sku_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='组合套餐子项';

-- ============================================================
-- 订单系统 (Phase 2)
-- ============================================================

CREATE TABLE IF NOT EXISTS `order_orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) DEFAULT NULL COMMENT '订单号',
  `user_id` int DEFAULT NULL COMMENT '用户ID',
  `status` enum('pending','paid','shipped','completed','cancelled','closed') DEFAULT 'pending' COMMENT '订单状态',
  `delivery_type` varchar(20) NOT NULL DEFAULT 'express' COMMENT '配送方式 express|merchant|pickup',
  `pickup_store_id` int unsigned DEFAULT NULL COMMENT '自提门店 ID',
  `pickup_code` varchar(8) DEFAULT NULL COMMENT '自提码',
  `pickup_at` datetime DEFAULT NULL COMMENT '核销时间',
  `pickup_verified_by` int unsigned DEFAULT NULL COMMENT '核销人 admin id',
  `pickup_status` varchar(20) DEFAULT NULL COMMENT 'pending|verified|timeout|cancelled',
  `pickup_timeout_at` datetime DEFAULT NULL COMMENT '超时时间点',
  `goods_amount` decimal(10,2) DEFAULT '0.00' COMMENT '商品总额',
  `freight_amount` decimal(10,2) DEFAULT '0.00' COMMENT '运费',
  `discount_amount` decimal(10,2) DEFAULT '0.00' COMMENT '优惠减免',
  `pay_amount` decimal(10,2) DEFAULT '0.00' COMMENT '实付金额',
  `pay_type` varchar(20) DEFAULT '' COMMENT '支付方式',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `ship_time` datetime DEFAULT NULL COMMENT '发货时间',
  `receive_time` datetime DEFAULT NULL COMMENT '收货时间',
  `buyer_remark` varchar(500) DEFAULT '' COMMENT '买家备注',
  `seller_remark` varchar(500) DEFAULT '' COMMENT '卖家备注',
  `cancel_reason` varchar(200) DEFAULT '' COMMENT '取消原因',
  `auto_cancel_at` datetime DEFAULT NULL COMMENT '自动取消时间',
  `virtual_fulfillment_status` varchar(20) NOT NULL DEFAULT 'none' COMMENT '虚拟发货：none/pending/failed/completed',
  `virtual_fulfillment_error` varchar(255) NOT NULL DEFAULT '' COMMENT '最近虚拟发货异常',
  `cancel_effects_status` varchar(20) NOT NULL DEFAULT 'none' COMMENT '取消副作用：none/pending/failed/completed',
  `cancel_effects_error` varchar(255) NOT NULL DEFAULT '' COMMENT '最近取消副作用异常',
  `parent_order_id` int unsigned DEFAULT NULL COMMENT '直接父订单ID：拆单=原订单；合单=存活订单（仅被合并订单设置）',
  `relation_type` varchar(20) NOT NULL DEFAULT 'none' COMMENT '订单血缘：none/split_child/merge_absorbed',
  `payment_root_order_id` int unsigned DEFAULT NULL COMMENT '支付权威根订单ID（拆单链扁平化指针，NULL=自身即根）',
  `address_snapshot` json DEFAULT NULL COMMENT '地址快照',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `auto_cancel_at` (`auto_cancel_at`),
  KEY `created_at` (`created_at`),
  KEY `idx_pickup` (`pickup_store_id`, `pickup_status`, `pickup_at`),
  KEY `idx_virtual_fulfillment` (`virtual_fulfillment_status`,`status`),
  KEY `idx_cancel_effects` (`status`,`cancel_effects_status`),
  KEY `idx_parent_order_id` (`parent_order_id`),
  KEY `idx_payment_root_order_id` (`payment_root_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单';

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL COMMENT '订单ID',
  `spu_id` int DEFAULT NULL COMMENT 'SPU ID',
  `sku_id` int DEFAULT NULL COMMENT 'SKU ID',
  `flash_item_id` int unsigned DEFAULT NULL COMMENT '秒杀活动商品ID，普通订单为空',
  `goods_name` varchar(200) DEFAULT NULL COMMENT '商品名称',
  `goods_image` varchar(255) DEFAULT '' COMMENT '商品图片',
  `spec_text` varchar(200) DEFAULT '' COMMENT '规格描述',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '单价',
  `quantity` int DEFAULT '1' COMMENT '数量',
  `total_amount` decimal(10,2) DEFAULT '0.00' COMMENT '小计',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单优惠分摊',
  `freight_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单运费分摊',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '该行净实付金额',
  `is_reviewed` tinyint(1) DEFAULT '0' COMMENT '是否已评价',
  `refund_status` tinyint DEFAULT '0' COMMENT '0无,1申请中,2已退款',
  `split_from_item_id` int unsigned DEFAULT NULL COMMENT '数量拆分时的来源订单项ID（整行迁移/普通行为NULL）',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `spu_id` (`spu_id`),
  KEY `sku_id` (`sku_id`),
  KEY `flash_item_id` (`flash_item_id`),
  KEY `idx_split_from_item_id` (`split_from_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单项';

CREATE TABLE IF NOT EXISTS `order_adjust_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL COMMENT '主订单：改价=目标订单；拆单=父订单；合单=存活订单',
  `action` varchar(20) NOT NULL COMMENT '操作类型：split/merge/price_adjust',
  `related_order_ids` json DEFAULT NULL COMMENT '关联订单：拆单=[子单ID]；合单=[被合并订单ID...]',
  `admin_id` int unsigned NOT NULL COMMENT '操作管理员ID',
  `before_snapshot` json NOT NULL COMMENT '操作前关键字段快照',
  `after_snapshot` json NOT NULL COMMENT '操作后关键字段快照',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '操作原因/备注',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_action` (`action`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单拆合/改价审计流水（append-only）';

CREATE TABLE IF NOT EXISTS `order_payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL COMMENT '订单ID',
  `payment_order_id` bigint unsigned DEFAULT NULL COMMENT '真实支付单ID',
  `pay_type` varchar(20) DEFAULT '' COMMENT '支付方式',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '支付金额',
  `status` tinyint DEFAULT '0' COMMENT '0待支付,1已支付,2已退款',
  `trade_no` varchar(64) DEFAULT '' COMMENT '第三方交易号',
  `paid_at` datetime DEFAULT NULL COMMENT '支付时间',
  `refunded_at` datetime DEFAULT NULL COMMENT '退款时间',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  UNIQUE KEY `uk_order_payment_order_id` (`payment_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单支付记录';

CREATE TABLE IF NOT EXISTS `order_logistics` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL COMMENT '订单ID',
  `express_company` varchar(50) DEFAULT '' COMMENT '快递公司',
  `express_no` varchar(50) DEFAULT '' COMMENT '快递单号',
  `waybill_no` varchar(50) DEFAULT '' COMMENT '运单号',
  `traces` json DEFAULT NULL COMMENT '物流轨迹',
  `status` tinyint DEFAULT '0' COMMENT '0待揽收,1运输中,2已签收',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `express_no` (`express_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='物流信息';

CREATE TABLE IF NOT EXISTS `order_refunds` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `refund_no` varchar(32) DEFAULT NULL COMMENT '售后单号',
  `order_id` int DEFAULT NULL COMMENT '订单ID',
  `order_item_id` int DEFAULT NULL COMMENT '订单项ID',
  `user_id` int DEFAULT NULL COMMENT '用户ID',
  `type` enum('refund_only','return_refund','exchange') DEFAULT 'refund_only' COMMENT '售后类型',
  `status` enum('pending','approved','returning','received','refunding','retryable_failed','manual_review','refunded','rejected') DEFAULT 'pending' COMMENT '售后状态',
  `reason` varchar(200) DEFAULT '' COMMENT '申请原因',
  `description` text COMMENT '详情描述',
  `images` json DEFAULT NULL COMMENT '凭证图片',
  `refund_amount` decimal(10,2) DEFAULT '0.00' COMMENT '退款金额',
  `refund_trade_no` varchar(128) NOT NULL DEFAULT '' COMMENT '退款标识：微信refund_id；支付宝out_request_no/商户退款单号',
  `refund_trade_no_source` varchar(32) NOT NULL DEFAULT '' COMMENT '退款标识来源',
  `provider_status` varchar(32) NOT NULL DEFAULT '' COMMENT '最近一次支付渠道退款状态',
  `provider_requested_at` datetime DEFAULT NULL COMMENT '最近一次向支付渠道发起退款时间',
  `provider_checked_at` datetime DEFAULT NULL COMMENT '最近一次观察或查询支付渠道退款状态时间',
  `failure_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '渠道明确失败或人工复核原因',
  `retry_count` int unsigned NOT NULL DEFAULT 0 COMMENT '重新发起退款次数',
  `refunded_at` datetime DEFAULT NULL COMMENT '支付渠道退款成功并完成本地结算时间',
  `return_express_company` varchar(50) DEFAULT '' COMMENT '退货快递公司',
  `return_express_no` varchar(50) DEFAULT '' COMMENT '退货快递单号',
  `admin_remark` varchar(500) DEFAULT '' COMMENT '客服备注',
  `refuse_reason` varchar(200) DEFAULT '' COMMENT '拒绝原因',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `refund_no` (`refund_no`),
  KEY `order_id` (`order_id`),
  KEY `order_item_id` (`order_item_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `idx_refund_provider_reconcile` (`status`,`provider_checked_at`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='售后单';

-- ============================================================
-- 订单完成会员权益快照与不可变调整流水
-- ============================================================
CREATE TABLE IF NOT EXISTS `order_member_rewards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL COMMENT '订单ID（每单唯一）',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `eligible_item_ids` json NOT NULL COMMENT '完成时计入奖励的订单项ID',
  `reward_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '完成时奖励基数（分摊行pay_amount之和）',
  `points_rate` decimal(10,4) NOT NULL DEFAULT 1.0000 COMMENT '完成时积分倍率快照',
  `points` int NOT NULL DEFAULT 0 COMMENT '理论奖励积分',
  `points_credited` int NOT NULL DEFAULT 0 COMMENT '理论事件实际进入累计积分的份额',
  `points_debt_offset` int NOT NULL DEFAULT 0 COMMENT '奖励时用于偿还历史积分债务的份额',
  `growth` int NOT NULL DEFAULT 0 COMMENT '理论奖励成长值',
  `consume_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '理论累计消费增加额',
  `order_count` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '理论订单数增加额',
  `origin` varchar(20) NOT NULL DEFAULT 'native' COMMENT '快照来源：native/legacy_import',
  `verification_status` varchar(20) NOT NULL DEFAULT 'verified' COMMENT '证据状态：verified/partial/unverified',
  `verified_points` int NOT NULL DEFAULT 0 COMMENT '有订单级证据、允许自动冲正的积分',
  `verified_points_credited` int NOT NULL DEFAULT 0 COMMENT '有证据进入累计积分、允许自动冲正的份额',
  `verified_growth` int NOT NULL DEFAULT 0 COMMENT '有订单级证据、允许自动冲正的成长值',
  `verified_consume_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '有订单级证据、允许自动冲正的消费额',
  `verified_order_count` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '有订单级证据、允许自动冲正的订单数',
  `evidence` json DEFAULT NULL COMMENT '历史导入证据、冲突与理论值快照',
  `review_status` varchar(20) NOT NULL DEFAULT 'none' COMMENT '人工复核状态：none/pending/resolved',
  `review_resolution` varchar(30) NOT NULL DEFAULT '' COMMENT '复核结论：exclude_unverified',
  `review_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '人工复核依据与说明',
  `review_operator_id` int unsigned DEFAULT NULL COMMENT '复核管理员ID',
  `reviewed_at` datetime DEFAULT NULL COMMENT '复核结案时间',
  `refunded_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '奖励期内累计退款额',
  `reversed_points` int NOT NULL DEFAULT 0 COMMENT '累计已冲正可验证积分',
  `reversed_points_credited` int NOT NULL DEFAULT 0 COMMENT '累计已冲正可验证累计积分份额',
  `reversed_growth` int NOT NULL DEFAULT 0 COMMENT '累计已冲正可验证成长值',
  `reversed_consume_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '累计已冲正可验证消费额',
  `reversed_order_count` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '累计已冲正可验证订单数',
  `awarded_at` datetime NOT NULL COMMENT '订单完成时间快照',
  `fully_reversed_at` datetime DEFAULT NULL COMMENT '全部可验证且无待复核权益的全额冲正时间',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_member_reward_order` (`order_id`),
  KEY `idx_order_member_reward_user` (`user_id`),
  KEY `idx_order_member_reward_review` (`review_status`, `verification_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单会员权益快照';

CREATE TABLE IF NOT EXISTS `order_member_reward_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reward_id` bigint unsigned NOT NULL COMMENT '权益快照ID',
  `order_id` int unsigned NOT NULL COMMENT '订单ID',
  `refund_id` int unsigned DEFAULT NULL COMMENT '退款ID，发放时为空',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `action` varchar(30) NOT NULL COMMENT 'award|award_imported|refund_reverse|refund_ignored|review_resolved',
  `event_key` varchar(191) NOT NULL COMMENT '不可变事件幂等键',
  `refund_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '本事件退款额',
  `points` int NOT NULL DEFAULT 0 COMMENT '本次积分调整（冲正为负）',
  `points_credited_reversed` int NOT NULL DEFAULT 0 COMMENT '本次累计积分冲回份额',
  `growth` int NOT NULL DEFAULT 0 COMMENT '本次成长值调整',
  `consume_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '本次消费额调整',
  `order_count` tinyint NOT NULL DEFAULT 0 COMMENT '本次订单数调整',
  `points_debt_added` int NOT NULL DEFAULT 0 COMMENT '可用积分不足产生的债务',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '审计说明',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_member_adjustment_event` (`event_key`),
  UNIQUE KEY `uk_order_member_adjustment_refund_action` (`refund_id`, `action`),
  KEY `idx_order_member_adjustment_reward` (`reward_id`),
  KEY `idx_order_member_adjustment_order` (`order_id`),
  KEY `idx_order_member_adjustment_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单会员权益不可变调整流水';

CREATE TABLE IF NOT EXISTS `order_reviews` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_item_id` int DEFAULT NULL COMMENT '订单项ID',
  `user_id` int DEFAULT NULL COMMENT '用户ID',
  `spu_id` int DEFAULT NULL COMMENT 'SPU ID',
  `sku_id` int DEFAULT NULL COMMENT 'SKU ID',
  `rating` tinyint DEFAULT '5' COMMENT '评分1-5',
  `content` text COMMENT '评价内容',
  `images` json DEFAULT NULL COMMENT '评价图片',
  `is_anonymous` tinyint(1) DEFAULT '0' COMMENT '是否匿名',
  `reply_content` text COMMENT '商家回复',
  `reply_at` datetime DEFAULT NULL COMMENT '回复时间',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `order_item_id` (`order_item_id`),
  KEY `user_id` (`user_id`),
  KEY `spu_id` (`spu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='商品评价';

CREATE TABLE IF NOT EXISTS `order_refund_reasons` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '原因名称',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:1启用,0禁用',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='退款原因模板';

CREATE TABLE IF NOT EXISTS `order_invoices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL DEFAULT 0 COMMENT '订单ID',
  `order_no` varchar(40) NOT NULL DEFAULT '' COMMENT '订单号（冗余）',
  `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `type` varchar(20) NOT NULL DEFAULT 'personal' COMMENT '抬头类型: personal/company/vat',
  `invoice_type` varchar(20) NOT NULL DEFAULT 'electronic' COMMENT '发票形式: electronic/paper',
  `title` varchar(120) NOT NULL DEFAULT '' COMMENT '发票抬头',
  `tax_no` varchar(40) NOT NULL DEFAULT '' COMMENT '纳税人识别号',
  `bank_name` varchar(80) NOT NULL DEFAULT '' COMMENT '开户银行',
  `bank_account` varchar(80) NOT NULL DEFAULT '' COMMENT '银行账号',
  `company_address` varchar(200) NOT NULL DEFAULT '' COMMENT '注册地址',
  `company_phone` varchar(40) NOT NULL DEFAULT '' COMMENT '注册电话',
  `recipient_name` varchar(40) NOT NULL DEFAULT '' COMMENT '收件人',
  `recipient_phone` varchar(40) NOT NULL DEFAULT '' COMMENT '联系电话',
  `recipient_email` varchar(80) NOT NULL DEFAULT '' COMMENT '收件邮箱',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '开票金额',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '发票内容',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '状态: pending/processing/issued/cancelled',
  `file_url` varchar(255) NOT NULL DEFAULT '' COMMENT '开票文件 URL',
  `admin_remark` varchar(255) NOT NULL DEFAULT '' COMMENT '管理员备注',
  `issued_at` datetime DEFAULT NULL COMMENT '开票时间',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单发票';

-- ============================================================
-- 会员/分销 (Phase 3)
-- ============================================================

CREATE TABLE IF NOT EXISTS `member_levels` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL COMMENT '等级名称',
  `icon` varchar(255) DEFAULT '' COMMENT '等级图标',
  `growth_min` int DEFAULT '0' COMMENT '所需最低成长值',
  `discount` decimal(3,2) DEFAULT '1.00' COMMENT '折扣率',
  `points_rate` decimal(3,1) DEFAULT '1.0' COMMENT '积分倍率',
  `free_freight` tinyint(1) DEFAULT '0' COMMENT '是否免邮',
  `privileges` json DEFAULT NULL COMMENT '特权配置',
  `sort` int DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态:1启用,0禁用',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员等级';

CREATE TABLE IF NOT EXISTS `member_addresses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL COMMENT '用户ID',
  `name` varchar(50) DEFAULT NULL COMMENT '收货人姓名',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `province` varchar(50) DEFAULT '' COMMENT '省份',
  `city` varchar(50) DEFAULT '' COMMENT '城市',
  `district` varchar(50) DEFAULT '' COMMENT '区县',
  `detail` varchar(200) DEFAULT '' COMMENT '详细地址',
  `lng` decimal(10,6) DEFAULT NULL COMMENT '经度',
  `lat` decimal(10,6) DEFAULT NULL COMMENT '纬度',
  `region_code` varchar(20) DEFAULT '' COMMENT '区域编码',
  `is_default` tinyint(1) DEFAULT '0' COMMENT '是否默认地址',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='收货地址';

CREATE TABLE IF NOT EXISTS `member_favorites` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL COMMENT '用户ID',
  `spu_id` int DEFAULT NULL COMMENT '商品SPU ID',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_spu_unique` (`user_id`,`spu_id`),
  KEY `user_id` (`user_id`),
  KEY `spu_id` (`spu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='收藏';

CREATE TABLE IF NOT EXISTS `member_browse_histories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT '用户ID',
  `spu_id` int NOT NULL COMMENT '商品SPU ID',
  `viewed_at` datetime NOT NULL COMMENT '最近浏览时间',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_spu_unique` (`user_id`,`spu_id`),
  KEY `user_viewed_idx` (`user_id`,`viewed_at`),
  KEY `spu_id` (`spu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='浏览记录';

CREATE TABLE IF NOT EXISTS `member_cart` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL COMMENT '用户ID',
  `sku_id` int DEFAULT NULL COMMENT '商品SKU ID',
  `quantity` int DEFAULT '1' COMMENT '数量',
  `selected` tinyint(1) DEFAULT '1' COMMENT '是否选中',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_sku_unique` (`user_id`,`sku_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='购物车';

CREATE TABLE IF NOT EXISTS `recharge_packages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '充值金额',
  `gift_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '赠送金额',
  `gift_points` int NOT NULL DEFAULT '0' COMMENT '赠送积分',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:1启用,0禁用',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='充值套餐';

CREATE TABLE IF NOT EXISTS `member_recharge_orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL COMMENT '用户ID',
  `package_id` int DEFAULT NULL COMMENT '套餐ID',
  `order_no` varchar(32) DEFAULT NULL COMMENT '充值单号',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '充值金额',
  `gift_amount` decimal(10,2) DEFAULT '0.00' COMMENT '赠送金额',
  `gift_points` int NOT NULL DEFAULT '0' COMMENT '赠送积分',
  `pay_type` varchar(20) DEFAULT '' COMMENT '支付方式',
  `payment_order_id` bigint unsigned DEFAULT NULL COMMENT '支付订单ID',
  `status` int DEFAULT '0' COMMENT '状态:0待支付,1已支付',
  `paid_at` datetime DEFAULT NULL COMMENT '支付时间',
  `settled_at` datetime DEFAULT NULL COMMENT '资产、积分、成长值全部结算完成时间',
  `expected_growth_value` int NOT NULL DEFAULT 0 COMMENT '本次充值理论应发成长值',
  `growth_review_status` varchar(20) NOT NULL DEFAULT 'none' COMMENT '成长值复核:none/pending/resolved',
  `growth_review_resolution` varchar(30) NOT NULL DEFAULT '' COMMENT '复核结论:confirmed_applied/confirmed_missing',
  `growth_review_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '待复核原因或人工复核依据',
  `growth_review_operator_id` int unsigned DEFAULT NULL COMMENT '复核管理员ID',
  `growth_reviewed_at` datetime DEFAULT NULL COMMENT '复核时间',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  UNIQUE KEY `uk_recharge_payment_order_id` (`payment_order_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_recharge_growth_review` (`growth_review_status`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='充值订单';

CREATE TABLE IF NOT EXISTS `member_growth_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `value` int NOT NULL COMMENT '本次成长值变动',
  `before_growth` int NOT NULL DEFAULT 0 COMMENT '变动前成长值',
  `after_growth` int NOT NULL DEFAULT 0 COMMENT '变动后成长值',
  `source` varchar(191) DEFAULT NULL COMMENT '领域事件幂等来源',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_growth_source` (`user_id`, `source`),
  KEY `idx_growth_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员成长值流水';

CREATE TABLE IF NOT EXISTS `user_auths` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL COMMENT '用户ID',
  `platform` enum('wechat_mp','wechat_oa','alipay','douyin','phone') DEFAULT NULL COMMENT '登录平台',
  `openid` varchar(128) DEFAULT '' COMMENT 'OpenID',
  `unionid` varchar(128) DEFAULT '' COMMENT 'UnionID',
  `nickname` varchar(100) DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_openid_unique` (`platform`,`openid`),
  KEY `user_id` (`user_id`),
  KEY `unionid` (`unionid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='多渠道登录绑定';

CREATE TABLE IF NOT EXISTS `user_login_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL COMMENT '用户ID',
  `login_at` datetime NOT NULL COMMENT '登录时间',
  `login_ip` varchar(45) DEFAULT NULL COMMENT '登录IP',
  PRIMARY KEY (`id`),
  KEY `idx_user_login_at` (`user_id`, `login_at`),
  KEY `idx_login_at` (`login_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户登录日志（用于留存计算）';

-- ============================================================
-- 广告位 / 广告
-- ============================================================
CREATE TABLE IF NOT EXISTS `marketing_ad_positions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL COMMENT '位置编码，C 端按此取',
  `name` varchar(100) NOT NULL COMMENT '位置名称',
  `description` varchar(255) DEFAULT '' COMMENT '用途说明',
  `recommended_width` smallint unsigned DEFAULT 0 COMMENT '建议素材宽 px，0=不限',
  `recommended_height` smallint unsigned DEFAULT 0 COMMENT '建议素材高 px，0=不限',
  `is_carousel` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=轮播多广告 / 0=仅取 sort 最大',
  `sort` int NOT NULL DEFAULT 0 COMMENT 'admin 列表排序',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=启用 / 0=停用',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告位';

CREATE TABLE IF NOT EXISTS `marketing_ads` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `position_id` int unsigned NOT NULL COMMENT '所属广告位',
  `title` varchar(100) NOT NULL COMMENT 'admin 标识名（C 端不展示）',
  `image` varchar(500) NOT NULL COMMENT '图片 URL',
  `link` varchar(500) DEFAULT '' COMMENT '跳转链接，空=无跳转',
  `start_at` datetime DEFAULT NULL COMMENT '上架时间，NULL=立即生效',
  `end_at` datetime DEFAULT NULL COMMENT '下架时间，NULL=永久',
  `sort` int NOT NULL DEFAULT 0 COMMENT '同位置内排序，desc 先出',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=启用 / 0=禁用',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_position_status` (`position_id`, `status`),
  KEY `idx_time_range` (`start_at`, `end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告';

-- ============================================================
-- 用户分组模块 (User Group)
-- ============================================================

CREATE TABLE IF NOT EXISTS `user_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '分组名称',
  `description` varchar(200) NOT NULL DEFAULT '' COMMENT '分组描述',
  `rules` json DEFAULT NULL COMMENT '自动规则 JSON',
  `auto_update` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动刷新:1是,0否',
  `user_count` int NOT NULL DEFAULT '0' COMMENT '用户数缓存',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:1启用,0禁用',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_auto_update` (`auto_update`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户分组';

CREATE TABLE IF NOT EXISTS `user_group_relations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL COMMENT '用户ID',
  `group_id` int unsigned NOT NULL COMMENT '分组ID',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_group` (`user_id`, `group_id`),
  KEY `idx_group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户分组关系';

-- ============================================================
-- 财务模块 (Finance)
-- ============================================================

CREATE TABLE IF NOT EXISTS `finance_transactions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `transaction_no` varchar(32) NOT NULL DEFAULT '' COMMENT '交易流水号',
  `event_key` varchar(191) DEFAULT NULL COMMENT '领域事件幂等键，NULL表示历史/手工流水',
  `type` enum('income','expense','refund') NOT NULL DEFAULT 'income' COMMENT '交易类型：income收入/expense支出/refund退款',
  `biz_type` varchar(30) NOT NULL DEFAULT '' COMMENT '业务类型：order/recharge/withdrawal/refund',
  `biz_id` int NOT NULL DEFAULT '0' COMMENT '业务记录ID',
  `biz_no` varchar(50) NOT NULL DEFAULT '' COMMENT '业务单号',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `payment_channel` varchar(20) NOT NULL DEFAULT '' COMMENT '支付渠道',
  `trade_no` varchar(64) NOT NULL DEFAULT '' COMMENT '第三方交易号',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '用户ID',
  `remark` varchar(200) NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_no` (`transaction_no`),
  UNIQUE KEY `uk_finance_event_key` (`event_key`),
  KEY `type` (`type`),
  KEY `biz_type` (`biz_type`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='财务交易流水';

-- ============================================================
-- 用户标签 (User Tags)
-- ============================================================

CREATE TABLE IF NOT EXISTS `user_tags` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '标签名称',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '标签描述',
  `color` varchar(20) NOT NULL DEFAULT '#409eff' COMMENT '标签颜色',
  `group_type` enum('consume','behavior','lifecycle','social') NOT NULL DEFAULT 'social' COMMENT '分组：消费力/行为偏好/生命周期/社交属性',
  `rules` json DEFAULT NULL COMMENT '规则定义 {logic, conditions:[]}',
  `auto_update` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动更新（cron 重算）',
  `user_count` int NOT NULL DEFAULT '0' COMMENT '覆盖用户数（缓存）',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1启用 0停用',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_group_type` (`group_type`, `sort`),
  KEY `idx_auto_update` (`auto_update`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户标签';

CREATE TABLE IF NOT EXISTS `user_tag_relations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0' COMMENT '用户ID',
  `tag_id` int NOT NULL DEFAULT '0' COMMENT '标签ID',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_tag` (`user_id`, `tag_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户标签关联';

CREATE TABLE IF NOT EXISTS `diy_pages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `page_type` varchar(30) NOT NULL DEFAULT 'custom' COMMENT '页面类型: home/category/custom',
  `platform` varchar(10) NOT NULL DEFAULT 'uniapp' COMMENT '平台: uniapp/pc',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '页面标题',
  `components` json DEFAULT NULL COMMENT '组件树JSON',
  `page_settings` json DEFAULT NULL COMMENT '页面设置(背景色/背景图等)',
  `is_published` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=已发布,0=未发布',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否为生效页面',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1启用,0禁用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type_platform` (`page_type`, `platform`),
  KEY `idx_default` (`page_type`, `platform`, `is_default`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='装修页面';

CREATE TABLE IF NOT EXISTS `diy_themes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '主题名称',
  `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面预览图',
  `platform` varchar(10) NOT NULL DEFAULT 'uniapp' COMMENT '平台: uniapp/pc',
  `page_type` varchar(30) NOT NULL DEFAULT 'home' COMMENT '页面类型: home/custom',
  `components` json DEFAULT NULL COMMENT '预设组件配置JSON',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=系统预设,0=用户保存',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1启用,0禁用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_platform_type` (`platform`, `page_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='装修主题';

CREATE TABLE IF NOT EXISTS `diy_page_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL COMMENT '关联 diy_pages.id',
  `version_no` int(11) NOT NULL COMMENT '页内版本号 (1,2,3...)',
  `title` varchar(100) NOT NULL COMMENT '快照: 页面标题',
  `components` json NOT NULL COMMENT '快照: 组件树',
  `page_settings` json DEFAULT NULL COMMENT '快照: 背景设置',
  `note` varchar(255) DEFAULT NULL COMMENT '发布备注 (可选)',
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT '操作管理员 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_page` (`page_id`, `created_at`),
  KEY `idx_page_version` (`page_id`, `version_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='页面历史版本';

-- ============================================================
-- 门店表（v2.4.0）
-- ============================================================
CREATE TABLE IF NOT EXISTS `stores` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '门店名',
  `code` varchar(32) NOT NULL DEFAULT '' COMMENT '门店编码',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '完整地址（展示用）',
  `province` varchar(50) NOT NULL DEFAULT '' COMMENT '省',
  `city` varchar(50) NOT NULL DEFAULT '' COMMENT '市',
  `district` varchar(50) NOT NULL DEFAULT '' COMMENT '区/县',
  `detail` varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址（街道门牌）',
  `region_code` varchar(20) NOT NULL DEFAULT '' COMMENT '区/县编码（regions.code）',
  `lng` decimal(10,7) NOT NULL DEFAULT 0 COMMENT '经度',
  `lat` decimal(10,7) NOT NULL DEFAULT 0 COMMENT '纬度',
  `phone` varchar(20) DEFAULT NULL COMMENT '电话',
  `business_hours` json DEFAULT NULL COMMENT '营业时间 JSON',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
  `sort` int NOT NULL DEFAULT 100 COMMENT '排序',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='门店';

-- ============================================================
-- v2.5.0：会员详情聚合表（操作日志、运营备注）
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_operation_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL COMMENT '用户ID',
  `category` varchar(20) NOT NULL DEFAULT 'other' COMMENT '分类:login/asset/level/order/service/profile/other',
  `event_key` varchar(191) DEFAULT NULL COMMENT '领域事件幂等键，NULL表示非领域事件日志',
  `event_code` varchar(64) NOT NULL DEFAULT '' COMMENT '事件标识 e.g. order.placed',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '事件标题',
  `description` varchar(500) DEFAULT '' COMMENT '事件描述',
  `icon` varchar(64) DEFAULT '' COMMENT '前端 lucide 图标类',
  `tone` varchar(20) DEFAULT '' COMMENT '前端色调（如 #10b981）',
  `ref_type` varchar(40) DEFAULT '' COMMENT '关联实体类型 order/balance_log/...',
  `ref_id` bigint unsigned DEFAULT NULL COMMENT '关联实体ID',
  `meta` json DEFAULT NULL COMMENT '扩展元数据',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_operation_event_key` (`event_key`),
  KEY `idx_user_created` (`user_id`, `created_at`),
  KEY `idx_user_category` (`user_id`, `category`),
  KEY `idx_event_code` (`event_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户操作日志（会员详情聚合）';

CREATE TABLE IF NOT EXISTS `member_remarks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL COMMENT '用户ID',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '备注内容',
  `operator_id` int unsigned DEFAULT NULL COMMENT '操作管理员ID',
  `operator_name` varchar(50) DEFAULT '' COMMENT '操作人冗余昵称',
  `created_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员运营备注';

-- 插件管理 / 插件运行时

-- 已安装插件注册表
CREATE TABLE IF NOT EXISTS `plugins` (
  `code`         varchar(64)  NOT NULL COMMENT '插件唯一标识（与目录名一致）',
  `name`         varchar(60)  NOT NULL COMMENT '显示名',
  `version`      varchar(32)  NOT NULL COMMENT '当前已安装版本',
  `category`     varchar(32)  NOT NULL COMMENT 'core|channel_market|value_added',
  `parent_menu`  varchar(60)  NOT NULL COMMENT '父级菜单 name',
  `description`  varchar(500) DEFAULT '',
  `author`       varchar(60)  DEFAULT '',
  `icon`         varchar(255) DEFAULT '' COMMENT '相对插件目录的图标路径',
  `palette`      json         DEFAULT NULL COMMENT '[hex, hex] 渐变色',
  `recommended`  tinyint(1)   NOT NULL DEFAULT 0,
  `source`       varchar(16)  NOT NULL COMMENT 'bundled|downloaded',
  `status`       varchar(16)  NOT NULL DEFAULT 'installed' COMMENT 'installed|disabled',
  `manifest`     json         NOT NULL COMMENT '完整清单快照',
  `installed_at` datetime     NOT NULL,
  `upgraded_at`  datetime     DEFAULT NULL,
  `created_at`   datetime     NOT NULL,
  `updated_at`   datetime     NOT NULL,
  PRIMARY KEY (`code`),
  KEY `idx_category` (`category`),
  KEY `idx_status`   (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='已安装插件注册表';

-- 插件安装审计日志
CREATE TABLE IF NOT EXISTS `plugin_install_logs` (
  `id`           bigint unsigned NOT NULL AUTO_INCREMENT,
  `plugin_code`  varchar(64)   NOT NULL,
  `action`       varchar(16)   NOT NULL COMMENT 'install|uninstall|upgrade|enable|disable',
  `version_from` varchar(32)   DEFAULT NULL,
  `version_to`   varchar(32)   DEFAULT NULL,
  `status`       varchar(16)   NOT NULL COMMENT 'success|failed',
  `message`      text          DEFAULT NULL,
  `operator_id`  bigint unsigned DEFAULT NULL COMMENT '操作管理员ID',
  `created_at`   datetime      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_plugin` (`plugin_code`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='插件安装审计日志';

-- 插件 migration 执行记录
CREATE TABLE IF NOT EXISTS `plugin_migrations` (
  `id`          bigint unsigned NOT NULL AUTO_INCREMENT,
  `plugin_code` varchar(64) NOT NULL,
  `version`     varchar(32) NOT NULL,
  `executed_at` datetime    NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plugin_version` (`plugin_code`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='插件 migration 执行记录';

CREATE TABLE IF NOT EXISTS `plugin_builds` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `target` varchar(20) NOT NULL COMMENT 'admin|pc',
  `trigger` varchar(30) NOT NULL COMMENT 'install|upgrade|uninstall|manual',
  `plugin_code` varchar(64) DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '0queued 1running 2success 3failed 5skipped',
  `log` longtext,
  `artifact_path` varchar(255) NOT NULL DEFAULT '',
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `operator_id` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_plugin` (`plugin_code`),
  KEY `idx_target_status` (`target`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='后台/PC 云编译任务';

CREATE TABLE IF NOT EXISTS `mobile_builds` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `platform` varchar(20) NOT NULL COMMENT 'h5|mp-weixin',
  `trigger` varchar(30) NOT NULL,
  `plugin_code` varchar(64) DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '0queued 1running 2success 3failed 4uploaded 5skipped 6cancelled',
  `log` longtext,
  `artifact_path` varchar(255) NOT NULL DEFAULT '',
  `upload_result_json` longtext,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `operator_id` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_plugin` (`plugin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='H5/小程序渠道编译任务';

CREATE TABLE IF NOT EXISTS `mobile_channel_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `wechat_appid` varchar(64) NOT NULL DEFAULT '',
  `wechat_upload_key` text,
  `wechat_upload_version` varchar(32) NOT NULL DEFAULT '1.0.0',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='小程序 CI 上传配置（单店一份）';

-- ============================================================
-- 框架数据库升级记录表（php think yd:update 使用）
-- ============================================================
CREATE TABLE IF NOT EXISTS `system_upgrades` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(20) NOT NULL COMMENT '已应用的版本号',
  `applied_at` datetime NOT NULL COMMENT '应用时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='框架数据库升级记录';
