-- ============================================================
-- 元点Shop - 初始数据
-- ============================================================

-- 插入超级管理员角色
INSERT INTO `roles` (`id`, `name`, `title`, `description`, `data_scope`, `is_system`, `status`, `sort`, `created_at`, `updated_at`) VALUES
    (1, 'super_admin', '超级管理员', '系统超级管理员，拥有所有权限', 1, 1, 1, 0, NOW(), NOW());

-- ============================================================
-- 权限数据
-- ============================================================
INSERT INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`) VALUES
    -- 系统管理（原有）
    (1, 'system', '系统管理', '系统管理', '系统管理权限', 'admin', 1, 0, NOW(), NOW()),
    -- 管理员管理
    (2, 'system.admin', '管理员管理', '系统管理', '管理员管理权限', 'admin', 1, 1, NOW(), NOW()),
    (3, 'system.admin.list', '管理员列表', '系统管理', '查看管理员列表', 'admin', 1, 2, NOW(), NOW()),
    (4, 'system.admin.create', '创建管理员', '系统管理', '创建新管理员', 'admin', 1, 3, NOW(), NOW()),
    (5, 'system.admin.update', '编辑管理员', '系统管理', '编辑管理员信息', 'admin', 1, 4, NOW(), NOW()),
    (6, 'system.admin.delete', '删除管理员', '系统管理', '删除管理员', 'admin', 1, 5, NOW(), NOW()),
    (7, 'system.admin.status', '管理员状态', '系统管理', '修改管理员状态', 'admin', 1, 6, NOW(), NOW()),
    -- 角色管理
    (8, 'system.role', '角色管理', '系统管理', '角色管理权限', 'admin', 1, 7, NOW(), NOW()),
    (9, 'system.role.list', '角色列表', '系统管理', '查看角色列表', 'admin', 1, 8, NOW(), NOW()),
    (10, 'system.role.create', '创建角色', '系统管理', '创建新角色', 'admin', 1, 9, NOW(), NOW()),
    (11, 'system.role.update', '编辑角色', '系统管理', '编辑角色信息', 'admin', 1, 10, NOW(), NOW()),
    (12, 'system.role.delete', '删除角色', '系统管理', '删除角色', 'admin', 1, 11, NOW(), NOW()),
    (13, 'system.role.permission', '角色授权', '系统管理', '为角色分配权限', 'admin', 1, 12, NOW(), NOW()),
    -- 权限管理
    (14, 'system.permission', '权限管理', '系统管理', '权限管理', 'admin', 1, 13, NOW(), NOW()),
    (15, 'system.permission.list', '权限列表', '系统管理', '查看权限列表', 'admin', 1, 14, NOW(), NOW()),
    (16, 'system.permission.create', '创建权限', '系统管理', '创建新权限', 'admin', 1, 15, NOW(), NOW()),
    (17, 'system.permission.update', '编辑权限', '系统管理', '编辑权限信息', 'admin', 1, 16, NOW(), NOW()),
    (18, 'system.permission.delete', '删除权限', '系统管理', '删除权限', 'admin', 1, 17, NOW(), NOW()),
    -- 菜单管理
    (19, 'system.menu', '菜单管理', '系统管理', '菜单管理权限', 'admin', 1, 18, NOW(), NOW()),
    (20, 'system.menu.list', '菜单列表', '系统管理', '查看菜单列表', 'admin', 1, 19, NOW(), NOW()),
    (21, 'system.menu.create', '创建菜单', '系统管理', '创建新菜单', 'admin', 1, 20, NOW(), NOW()),
    (22, 'system.menu.update', '编辑菜单', '系统管理', '编辑菜单信息', 'admin', 1, 21, NOW(), NOW()),
    (23, 'system.menu.delete', '删除菜单', '系统管理', '删除菜单', 'admin', 1, 22, NOW(), NOW()),
    -- 日志管理
    (24, 'system.log', '日志管理', '系统管理', '日志管理权限', 'admin', 1, 23, NOW(), NOW()),
    (25, 'system.log.login', '登录日志', '系统管理', '查看登录日志', 'admin', 1, 24, NOW(), NOW()),
    (26, 'system.log.operation', '操作日志', '系统管理', '查看操作日志', 'admin', 1, 25, NOW(), NOW()),
    -- 部门管理
    (27, 'system.department', '部门管理', '系统管理', '部门管理权限', 'admin', 1, 26, NOW(), NOW()),
    (28, 'system.department.list', '部门列表', '系统管理', '查看部门列表', 'admin', 1, 27, NOW(), NOW()),
    (29, 'system.department.create', '创建部门', '系统管理', '创建新部门', 'admin', 1, 28, NOW(), NOW()),
    (30, 'system.department.update', '编辑部门', '系统管理', '编辑部门信息', 'admin', 1, 29, NOW(), NOW()),
    (31, 'system.department.delete', '删除部门', '系统管理', '删除部门', 'admin', 1, 30, NOW(), NOW()),
    -- 数据字典
    (32, 'system.dictionary', '数据字典', '系统管理', '数据字典管理权限', 'admin', 1, 31, NOW(), NOW()),
    (33, 'system.dictionary.list', '字典列表', '系统管理', '查看字典列表', 'admin', 1, 32, NOW(), NOW()),
    (34, 'system.dictionary.create', '创建字典', '系统管理', '创建数据字典', 'admin', 1, 33, NOW(), NOW()),
    (35, 'system.dictionary.update', '编辑字典', '系统管理', '编辑数据字典', 'admin', 1, 34, NOW(), NOW()),
    (36, 'system.dictionary.delete', '删除字典', '系统管理', '删除数据字典', 'admin', 1, 35, NOW(), NOW()),
    -- 文件管理
    (37, 'system.file', '文件管理', '系统管理', '文件管理权限', 'admin', 1, 36, NOW(), NOW()),
    (38, 'system.file.list', '文件列表', '系统管理', '查看文件列表', 'admin', 1, 37, NOW(), NOW()),
    (39, 'system.file.delete', '删除文件', '系统管理', '删除文件', 'admin', 1, 38, NOW(), NOW()),
    -- 通知管理
    (40, 'system.notification', '通知管理', '系统管理', '通知管理权限', 'admin', 1, 39, NOW(), NOW()),
    (41, 'system.notification.list', '通知列表', '系统管理', '查看通知列表', 'admin', 1, 40, NOW(), NOW()),
    (42, 'system.notification.create', '发布通知', '系统管理', '发布系统通知', 'admin', 1, 41, NOW(), NOW()),
    (43, 'system.notification.update', '编辑通知', '系统管理', '编辑通知内容', 'admin', 1, 42, NOW(), NOW()),
    (44, 'system.notification.delete', '删除通知', '系统管理', '删除通知', 'admin', 1, 43, NOW(), NOW()),
    -- 定时任务
    (45, 'system.cron_job', '定时任务', '系统管理', '定时任务管理权限', 'admin', 1, 44, NOW(), NOW()),
    (46, 'system.cron_job.list', '任务列表', '系统管理', '查看定时任务列表', 'admin', 1, 45, NOW(), NOW()),
    (47, 'system.cron_job.create', '创建任务', '系统管理', '创建定时任务', 'admin', 1, 46, NOW(), NOW()),
    (48, 'system.cron_job.update', '编辑任务', '系统管理', '编辑定时任务', 'admin', 1, 47, NOW(), NOW()),
    (49, 'system.cron_job.delete', '删除任务', '系统管理', '删除定时任务', 'admin', 1, 48, NOW(), NOW()),
    (50, 'system.cron_job.run', '执行任务', '系统管理', '手动执行定时任务', 'admin', 1, 49, NOW(), NOW()),
    -- 系统配置
    (51, 'system.config', '系统配置', '系统管理', '系统配置管理权限', 'admin', 1, 50, NOW(), NOW()),
    (52, 'system.config.list', '配置列表', '系统管理', '查看系统配置', 'admin', 1, 51, NOW(), NOW()),
    (53, 'system.config.update', '修改配置', '系统管理', '修改系统配置', 'admin', 1, 52, NOW(), NOW()),
    -- 代码生成器
    (54, 'system.generator', '代码生成器', '开发工具', '代码生成器权限', 'admin', 1, 53, NOW(), NOW()),
    -- API文档
    (55, 'system.api_doc', 'API文档', '开发工具', 'API文档查看权限', 'admin', 1, 54, NOW(), NOW()),
    -- 消息管理（系统管理子模块）
    (60, 'system.message', '消息管理', '系统管理', '消息管理权限', 'admin', 1, 60, NOW(), NOW()),
    (61, 'system.message.template', '消息模板', '系统管理', '消息模板管理', 'admin', 1, 61, NOW(), NOW()),
    (62, 'system.message.template.list', '模板列表', '系统管理', '查看消息模板列表', 'admin', 1, 62, NOW(), NOW()),
    (63, 'system.message.template.create', '创建模板', '系统管理', '创建消息模板', 'admin', 1, 63, NOW(), NOW()),
    (64, 'system.message.template.update', '编辑模板', '系统管理', '编辑消息模板', 'admin', 1, 64, NOW(), NOW()),
    (65, 'system.message.template.delete', '删除模板', '系统管理', '删除消息模板', 'admin', 1, 65, NOW(), NOW()),
    (66, 'system.message.log', '发送记录', '系统管理', '查看发送记录', 'admin', 1, 66, NOW(), NOW()),

    (67, 'system.license', '产品授权', '系统管理', '产品授权管理权限', 'admin', 1, 67, NOW(), NOW()),
    (68, 'system.license.list', '查看授权', '系统管理', '查看产品授权状态', 'admin', 1, 68, NOW(), NOW()),
    (69, 'system.license.activate', '激活授权', '系统管理', '激活/校验/清除产品授权', 'admin', 1, 69, NOW(), NOW()),
    -- 渠道管理
    (70, 'channel', '渠道管理', '渠道管理', '渠道管理权限', 'admin', 1, 70, NOW(), NOW()),
    (71, 'channel.official', '公众号管理', '渠道管理', '公众号管理权限', 'admin', 1, 71, NOW(), NOW()),
    (72, 'channel.official.config', '公众号配置', '渠道管理', '公众号配置管理', 'admin', 1, 72, NOW(), NOW()),
    (73, 'channel.official.menu', '自定义菜单', '渠道管理', '公众号菜单管理', 'admin', 1, 73, NOW(), NOW()),
    (74, 'channel.official.auto_reply', '自动回复', '渠道管理', '公众号自动回复管理', 'admin', 1, 74, NOW(), NOW()),
    (75, 'channel.miniapp', '小程序管理', '渠道管理', '小程序管理权限', 'admin', 1, 75, NOW(), NOW()),
    (76, 'channel.miniapp.config', '小程序配置', '渠道管理', '小程序配置管理', 'admin', 1, 76, NOW(), NOW()),
    -- 协议管理
    -- 公告管理
    (86, 'announcement', '公告管理', '内容管理', '公告管理权限', 'admin', 1, 86, NOW(), NOW()),
    (87, 'announcement.list', '公告列表', '内容管理', '查看公告列表', 'admin', 1, 87, NOW(), NOW()),
    (88, 'announcement.detail', '公告详情', '内容管理', '查看公告详情', 'admin', 1, 88, NOW(), NOW()),
    (89, 'announcement.create', '创建公告', '内容管理', '创建公告', 'admin', 1, 89, NOW(), NOW()),
    (90, 'announcement.update', '编辑公告', '内容管理', '编辑公告', 'admin', 1, 90, NOW(), NOW()),
    (91, 'announcement.status', '公告状态', '内容管理', '修改公告状态', 'admin', 1, 91, NOW(), NOW()),
    (92, 'announcement.delete', '删除公告', '内容管理', '删除公告', 'admin', 1, 92, NOW(), NOW()),
    -- 反馈管理
    (93, 'feedback', '反馈管理', '内容管理', '反馈管理权限', 'admin', 1, 93, NOW(), NOW()),
    (94, 'feedback.list', '反馈列表', '内容管理', '查看反馈列表', 'admin', 1, 94, NOW(), NOW()),
    (95, 'feedback.detail', '反馈详情', '内容管理', '查看反馈详情', 'admin', 1, 95, NOW(), NOW()),
    (96, 'feedback.reply', '回复反馈', '内容管理', '回复用户反馈', 'admin', 1, 96, NOW(), NOW()),
    (97, 'feedback.close', '关闭反馈', '内容管理', '关闭反馈', 'admin', 1, 97, NOW(), NOW()),
    (98, 'feedback.delete', '删除反馈', '内容管理', '删除反馈', 'admin', 1, 98, NOW(), NOW()),
    -- 区域管理
    (100, 'region', '区域管理', '应用管理', '区域管理权限', 'admin', 1, 100, NOW(), NOW()),
    (101, 'region.list', '区域列表', '应用管理', '查看区域列表', 'admin', 1, 101, NOW(), NOW()),
    (102, 'region.detail', '区域详情', '应用管理', '查看区域详情', 'admin', 1, 102, NOW(), NOW()),
    (103, 'region.create', '创建区域', '应用管理', '创建区域', 'admin', 1, 103, NOW(), NOW()),
    (104, 'region.update', '编辑区域', '应用管理', '编辑区域', 'admin', 1, 104, NOW(), NOW()),
    (105, 'region.delete', '删除区域', '应用管理', '删除区域', 'admin', 1, 105, NOW(), NOW()),
    -- 应用版本
    (106, 'version', '应用版本', '应用管理', '应用版本管理权限', 'admin', 1, 106, NOW(), NOW()),
    (107, 'version.list', '版本列表', '应用管理', '查看版本列表', 'admin', 1, 107, NOW(), NOW()),
    (108, 'version.detail', '版本详情', '应用管理', '查看版本详情', 'admin', 1, 108, NOW(), NOW()),
    (109, 'version.create', '创建版本', '应用管理', '创建版本', 'admin', 1, 109, NOW(), NOW()),
    (110, 'version.update', '编辑版本', '应用管理', '编辑版本', 'admin', 1, 110, NOW(), NOW()),
    (111, 'version.delete', '删除版本', '应用管理', '删除版本', 'admin', 1, 111, NOW(), NOW()),
    -- 文章栏目
    -- 文章管理
    -- 用户管理
    (150, 'user', '用户管理', '用户管理', '用户管理权限', 'admin', 1, 150, NOW(), NOW()),
    (151, 'user.list', '用户列表', '用户管理', '查看用户列表', 'admin', 1, 151, NOW(), NOW()),
    (152, 'user.detail', '用户详情', '用户管理', '查看用户详情', 'admin', 1, 152, NOW(), NOW()),
    (153, 'user.adjust-balance', '调整余额', '用户管理', '调整用户余额', 'admin', 1, 153, NOW(), NOW()),
    (154, 'user.adjust-points', '调整积分', '用户管理', '调整用户积分', 'admin', 1, 154, NOW(), NOW()),
    (155, 'user.status', '用户状态', '用户管理', '启用/禁用用户', 'admin', 1, 155, NOW(), NOW()),
    (156, 'user.balance-logs', '余额记录', '用户管理', '查看余额记录', 'admin', 1, 156, NOW(), NOW()),
    (157, 'user.points-logs', '积分记录', '用户管理', '查看积分记录', 'admin', 1, 157, NOW(), NOW()),
    -- 开放平台
    (160, 'channel.open', '开放平台', '渠道管理', '开放平台管理权限', 'admin', 1, 160, NOW(), NOW()),
    (161, 'channel.open.config', '开放平台配置', '渠道管理', '开放平台配置管理', 'admin', 1, 161, NOW(), NOW()),
    -- 插件管理（已安装 / 插件市场）
    (1820, 'plugin.installed', '插件管理-已安装', '插件管理', '插件管理-已安装', 'admin', 1, 1820, NOW(), NOW()),
    (1821, 'plugin.market',    '插件管理-市场',   '插件管理', '插件管理-市场',   'admin', 1, 1821, NOW(), NOW()),
    -- ============================================================
    -- 商品中心权限
    -- ============================================================
    (200, 'goods', '商品中心', '商品中心', '商品中心权限', 'admin', 1, 200, NOW(), NOW()),
    (201, 'goods.spu', '商品中心', '商品中心', '商品中心权限', 'admin', 1, 201, NOW(), NOW()),
    (202, 'goods.spu.list', '商品列表', '商品中心', '查看商品列表', 'admin', 1, 202, NOW(), NOW()),
    (203, 'goods.spu.create', '创建商品', '商品中心', '创建新商品', 'admin', 1, 203, NOW(), NOW()),
    (204, 'goods.spu.update', '编辑商品', '商品中心', '编辑商品信息', 'admin', 1, 204, NOW(), NOW()),
    (205, 'goods.spu.delete', '删除商品', '商品中心', '删除商品', 'admin', 1, 205, NOW(), NOW()),
    (210, 'goods.goods-category', '分类管理', '商品中心', '商品分类管理权限', 'admin', 1, 210, NOW(), NOW()),
    (211, 'goods.goods-category.list', '分类列表', '商品中心', '查看分类列表', 'admin', 1, 211, NOW(), NOW()),
    (212, 'goods.goods-category.create', '创建分类', '商品中心', '创建商品分类', 'admin', 1, 212, NOW(), NOW()),
    (213, 'goods.goods-category.update', '编辑分类', '商品中心', '编辑商品分类', 'admin', 1, 213, NOW(), NOW()),
    (214, 'goods.goods-category.delete', '删除分类', '商品中心', '删除商品分类', 'admin', 1, 214, NOW(), NOW()),
    (220, 'goods.goods-brand', '品牌管理', '商品中心', '商品品牌管理权限', 'admin', 1, 220, NOW(), NOW()),
    (221, 'goods.goods-brand.list', '品牌列表', '商品中心', '查看品牌列表', 'admin', 1, 221, NOW(), NOW()),
    (222, 'goods.goods-brand.create', '创建品牌', '商品中心', '创建商品品牌', 'admin', 1, 222, NOW(), NOW()),
    (223, 'goods.goods-brand.update', '编辑品牌', '商品中心', '编辑商品品牌', 'admin', 1, 223, NOW(), NOW()),
    (224, 'goods.goods-brand.delete', '删除品牌', '商品中心', '删除商品品牌', 'admin', 1, 224, NOW(), NOW()),
    (230, 'goods.goods-unit', '计量单位', '商品中心', '计量单位管理权限', 'admin', 1, 230, NOW(), NOW()),
    (231, 'goods.goods-unit.list', '单位列表', '商品中心', '查看单位列表', 'admin', 1, 231, NOW(), NOW()),
    (232, 'goods.goods-unit.create', '创建单位', '商品中心', '创建计量单位', 'admin', 1, 232, NOW(), NOW()),
    (233, 'goods.goods-unit.update', '编辑单位', '商品中心', '编辑计量单位', 'admin', 1, 233, NOW(), NOW()),
    (234, 'goods.goods-unit.delete', '删除单位', '商品中心', '删除计量单位', 'admin', 1, 234, NOW(), NOW()),
    (240, 'goods.goods-attribute-group', '属性分组', '商品中心', '属性分组管理权限', 'admin', 1, 240, NOW(), NOW()),
    (241, 'goods.goods-attribute-group.list', '分组列表', '商品中心', '查看属性分组列表', 'admin', 1, 241, NOW(), NOW()),
    (242, 'goods.goods-attribute-group.create', '创建分组', '商品中心', '创建属性分组', 'admin', 1, 242, NOW(), NOW()),
    (243, 'goods.goods-attribute-group.update', '编辑分组', '商品中心', '编辑属性分组', 'admin', 1, 243, NOW(), NOW()),
    (244, 'goods.goods-attribute-group.delete', '删除分组', '商品中心', '删除属性分组', 'admin', 1, 244, NOW(), NOW()),
    (250, 'goods.goods-attribute', '属性管理', '商品中心', '商品属性管理权限', 'admin', 1, 250, NOW(), NOW()),
    (251, 'goods.goods-attribute.list', '属性列表', '商品中心', '查看属性列表', 'admin', 1, 251, NOW(), NOW()),
    (252, 'goods.goods-attribute.create', '创建属性', '商品中心', '创建商品属性', 'admin', 1, 252, NOW(), NOW()),
    (253, 'goods.goods-attribute.update', '编辑属性', '商品中心', '编辑商品属性', 'admin', 1, 253, NOW(), NOW()),
    (254, 'goods.goods-attribute.delete', '删除属性', '商品中心', '删除商品属性', 'admin', 1, 254, NOW(), NOW()),
    (260, 'goods.goods-freight-template', '运费模板', '商品中心', '运费模板管理权限', 'admin', 1, 260, NOW(), NOW()),
    (261, 'goods.goods-freight-template.list', '模板列表', '商品中心', '查看运费模板列表', 'admin', 1, 261, NOW(), NOW()),
    (262, 'goods.goods-freight-template.create', '创建模板', '商品中心', '创建运费模板', 'admin', 1, 262, NOW(), NOW()),
    (263, 'goods.goods-freight-template.update', '编辑模板', '商品中心', '编辑运费模板', 'admin', 1, 263, NOW(), NOW()),
    (264, 'goods.goods-freight-template.delete', '删除模板', '商品中心', '删除运费模板', 'admin', 1, 264, NOW(), NOW()),
    (265, 'goods.goods-spec-template', '规格模板', '商品中心', '规格模板管理权限', 'admin', 1, 265, NOW(), NOW()),
    (266, 'goods.goods-spec-template.list', '模板列表', '商品中心', '查看规格模板列表', 'admin', 1, 266, NOW(), NOW()),
    (267, 'goods.goods-spec-template.create', '创建模板', '商品中心', '创建规格模板', 'admin', 1, 267, NOW(), NOW()),
    (268, 'goods.goods-spec-template.update', '编辑模板', '商品中心', '编辑规格模板', 'admin', 1, 268, NOW(), NOW()),
    (269, 'goods.goods-spec-template.delete', '删除模板', '商品中心', '删除规格模板', 'admin', 1, 269, NOW(), NOW()),
    -- ============================================================
    -- 订单中心权限
    -- ============================================================
    (300, 'order', '订单中心', '订单中心', '订单中心权限', 'admin', 1, 300, NOW(), NOW()),
    (301, 'order.list', '订单列表', '订单中心', '查看订单列表', 'admin', 1, 301, NOW(), NOW()),
    (302, 'order.cancel', '取消订单', '订单中心', '取消订单操作', 'admin', 1, 302, NOW(), NOW()),
    (303, 'order.update', '订单编辑', '订单中心', '编辑订单信息', 'admin', 1, 303, NOW(), NOW()),
    (304, 'order.ship', '发货操作', '订单中心', '订单发货操作', 'admin', 1, 304, NOW(), NOW()),
    (310, 'order.refund.list', '售后列表', '订单中心', '查看售后列表', 'admin', 1, 310, NOW(), NOW()),
    (311, 'order.refund.approve', '售后审核', '订单中心', '审核售后申请', 'admin', 1, 311, NOW(), NOW()),
    (312, 'order.refund.reject', '售后拒绝', '订单中心', '拒绝售后申请', 'admin', 1, 312, NOW(), NOW()),
    (320, 'order.review.list', '评价列表', '订单中心', '查看评价列表', 'admin', 1, 320, NOW(), NOW()),
    (321, 'order.review.reply', '评价回复', '订单中心', '回复用户评价', 'admin', 1, 321, NOW(), NOW()),
    (333, 'order.split', '拆分订单', '订单中心', '已支付未发货订单拆分为多单', 'admin', 1, 333, NOW(), NOW()),
    (334, 'order.merge', '合并订单', '订单中心', '同用户多笔待付款订单合并', 'admin', 1, 334, NOW(), NOW()),
    (335, 'order.price-adjust', '订单改价', '订单中心', '待付款订单改价（商品单价/运费/优惠）', 'admin', 1, 335, NOW(), NOW()),
    (336, 'order.delete', '删除订单', '订单中心', '软删除已取消/已关闭订单', 'admin', 1, 336, NOW(), NOW()),
    (330, 'order.invoice.list', '发票列表', '订单中心', '查看发票列表', 'admin', 1, 330, NOW(), NOW()),
    (331, 'order.invoice.update', '发票处理', '订单中心', '受理/开票/作废', 'admin', 1, 331, NOW(), NOW()),
    (332, 'order.invoice.delete', '发票删除', '订单中心', '删除发票', 'admin', 1, 332, NOW(), NOW()),
    -- ============================================================
    -- 会员权限
    -- ============================================================
    (400, 'member', '会员管理', '用户管理', '会员管理权限', 'admin', 1, 400, NOW(), NOW()),
    (401, 'member.list', '用户列表', '用户管理', '查看用户列表', 'admin', 1, 401, NOW(), NOW()),
    (402, 'member.update', '用户编辑', '用户管理', '编辑用户信息(调整余额/积分)', 'admin', 1, 402, NOW(), NOW()),
    (410, 'member.level.list', '等级列表', '用户管理', '查看会员等级列表', 'admin', 1, 410, NOW(), NOW()),
    (411, 'member.level.create', '创建等级', '用户管理', '创建会员等级', 'admin', 1, 411, NOW(), NOW()),
    (412, 'member.level.update', '编辑等级', '用户管理', '编辑会员等级', 'admin', 1, 412, NOW(), NOW()),
    (413, 'member.level.delete', '删除等级', '用户管理', '删除会员等级', 'admin', 1, 413, NOW(), NOW()),
    (414, 'member.tag.list', '标签列表', '用户管理', '查看用户标签列表', 'admin', 1, 414, NOW(), NOW()),
    (415, 'member.tag.create', '创建标签', '用户管理', '创建用户标签', 'admin', 1, 415, NOW(), NOW()),
    (416, 'member.tag.update', '编辑标签', '用户管理', '编辑用户标签', 'admin', 1, 416, NOW(), NOW()),
    (417, 'member.tag.delete', '删除标签', '用户管理', '删除用户标签', 'admin', 1, 417, NOW(), NOW()),
    (418, 'member.tag.assign', '打标签', '用户管理', '为用户批量打标签', 'admin', 1, 418, NOW(), NOW()),
    (419, 'member.tag.remove', '移除标签', '用户管理', '批量移除用户标签', 'admin', 1, 419, NOW(), NOW()),
    (403, 'member.statistics', '用户统计', '用户管理', '查看用户统计数据', 'admin', 1, 403, NOW(), NOW()),
    (404, 'member.coupon',         '发放优惠券', '用户管理', '会员详情发券',         'admin', 1, 404, NOW(), NOW()),
    (405, 'member.remark',         '运营备注',   '用户管理', '会员详情备注 CRUD',    'admin', 1, 405, NOW(), NOW()),
    (406, 'member.address.update', '修改地址',   '用户管理', '会员详情地址 CRUD',    'admin', 1, 406, NOW(), NOW()),
    (407, 'member.sms',            '发送短信',   '用户管理', '会员详情发送短信',     'admin', 1, 407, NOW(), NOW()),
    (450, 'member.address.list',          '地址簿列表', '用户管理', '查看会员地址簿', 'admin', 1, 450, NOW(), NOW()),
    (451, 'member.address.delete',        '删除地址',   '用户管理', '删除会员地址',   'admin', 1, 451, NOW(), NOW()),
    (460, 'member.fund.list',             '账户资金',   '用户管理', '查看账户资金',   'admin', 1, 460, NOW(), NOW()),
    (461, 'member.fund.withdraw.approve', '审核提现',   '用户管理', '审核会员提现',   'admin', 1, 461, NOW(), NOW()),
    (462, 'member.fund.withdraw.pay',     '提现打款',   '用户管理', '会员提现打款',   'admin', 1, 462, NOW(), NOW()),
    (480, 'member.reward_review.list',    '权益复核', '用户管理', '查看订单与充值会员权益证据', 'admin', 1, 480, NOW(), NOW()),
    (481, 'member.reward_review.resolve', '权益复核结案', '用户管理', '确认未验证聚合权益不归属于订单并留痕结案', 'admin', 1, 481, NOW(), NOW()),
    -- ============================================================
    -- 营销管理权限
    -- ============================================================
    -- ============================================================
    -- 物流配送权限
    -- ============================================================
    (350, 'delivery', '物流配送', '物流配送', '物流配送权限', 'admin', 1, 350, NOW(), NOW()),
    (351, 'delivery.express', '物流公司', '物流配送', '物流公司管理权限', 'admin', 1, 351, NOW(), NOW()),
    (352, 'delivery.express.list', '公司列表', '物流配送', '查看物流公司列表', 'admin', 1, 352, NOW(), NOW()),
    (353, 'delivery.express.create', '新增公司', '物流配送', '新增物流公司', 'admin', 1, 353, NOW(), NOW()),
    (354, 'delivery.express.update', '编辑公司', '物流配送', '编辑物流公司', 'admin', 1, 354, NOW(), NOW()),
    (355, 'delivery.express.delete', '删除公司', '物流配送', '删除物流公司', 'admin', 1, 355, NOW(), NOW()),
    -- 配送员管理
    (360, 'delivery.staff', '配送员管理', '物流配送', '配送员管理权限', 'admin', 1, 360, NOW(), NOW()),
    (361, 'delivery.staff.list', '配送员列表', '物流配送', '查看配送员列表', 'admin', 1, 361, NOW(), NOW()),
    (362, 'delivery.staff.create', '新增配送员', '物流配送', '新增配送员', 'admin', 1, 362, NOW(), NOW()),
    (363, 'delivery.staff.update', '编辑配送员', '物流配送', '编辑配送员', 'admin', 1, 363, NOW(), NOW()),
    (364, 'delivery.staff.delete', '删除配送员', '物流配送', '删除配送员', 'admin', 1, 364, NOW(), NOW()),
    -- 配送记录
    (370, 'delivery.order', '配送记录', '物流配送', '配送记录权限', 'admin', 1, 370, NOW(), NOW()),
    (371, 'delivery.order.list', '配送记录列表', '物流配送', '查看配送记录', 'admin', 1, 371, NOW(), NOW()),
    (372, 'delivery.order.update', '配送操作', '物流配送', '分配配送员/更新状态', 'admin', 1, 372, NOW(), NOW()),
    -- 财务导出权限（子项目 3）
    (463, 'finance.balance.export',     '导出余额流水', '财务管理', '导出会员余额流水', 'admin', 1, 463, NOW(), NOW()),
    (464, 'finance.transaction.export', '导出资金流水', '财务管理', '导出资金流水',     'admin', 1, 464, NOW(), NOW()),
    (465, 'finance.withdrawal.export',  '导出提现单',   '财务管理', '导出提现申请',     'admin', 1, 465, NOW(), NOW()),
    (466, 'finance.overview.export',    '导出月报',     '财务管理', '导出财务月报',     'admin', 1, 466, NOW(), NOW()),
    (467, 'finance.points.export',      '导出积分流水', '财务管理', '导出积分流水',     'admin', 1, 467, NOW(), NOW()),
    (468, 'member.address.export',      '导出地址簿',   '用户管理', '导出会员地址',       'admin', 1, 468, NOW(), NOW()),
    (469, 'member.fund.export',         '导出对账单',   '用户管理', '导出账户资金对账单', 'admin', 1, 469, NOW(), NOW()),
    (470, 'delivery.order.export',          '导出配送记录', '物流配送', '导出配送记录列表', 'admin', 1, 470, NOW(), NOW()),
    (471, 'delivery.staff.export',          '导出配送员',   '物流配送', '导出配送员花名册', 'admin', 1, 471, NOW(), NOW()),
    (473, 'finance.points.rules',           '积分规则查看', '财务管理', '查看积分规则总览',   'admin', 1, 473, NOW(), NOW()),
    (474, 'delivery.exception.list',        '异常工单列表', '物流配送', '查看异常工单',       'admin', 1, 474, NOW(), NOW()),
    (475, 'delivery.exception.create',      '创建异常工单', '物流配送', '创建异常工单',       'admin', 1, 475, NOW(), NOW()),
    (476, 'delivery.exception.update',      '处理异常工单', '物流配送', '编辑/状态流转',     'admin', 1, 476, NOW(), NOW()),
    (477, 'delivery.exception.delete',      '删除异常工单', '物流配送', '软删异常工单',       'admin', 1, 477, NOW(), NOW()),
    (478, 'delivery.shift.list',            '班次列表',     '物流配送', '查看配送员班次',         'admin', 1, 478, NOW(), NOW()),
    (479, 'delivery.shift.manage',          '班次管理',     '物流配送', '新增/编辑/删除班次',     'admin', 1, 479, NOW(), NOW()),
    (484, 'order.waybill.print',            '打印电子面单', '订单管理', '调用面单网关批量生成',   'admin', 1, 484, NOW(), NOW());

-- ============================================================
-- 广告位默认数据
-- ============================================================
INSERT INTO `marketing_ad_positions` (`code`, `name`, `description`, `recommended_width`, `recommended_height`, `is_carousel`, `sort`, `status`, `created_at`, `updated_at`) VALUES
('home_top_banner',     '首页顶部 Banner',  '首页大屏轮播，1200×460',     1200, 460, 1, 1, 1, NOW(), NOW()),
('goods_list_side_1',   '商品列表页侧栏 1',  '商品列表页右侧 240×360',      240, 360, 0, 2, 1, NOW(), NOW()),
('cart_footer_promo',   '购物车页底部推荐',  '购物车页底部横幅 1200×120', 1200, 120, 0, 3, 1, NOW(), NOW());

-- ============================================================
-- 菜单数据
-- ============================================================
INSERT INTO `menus` (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`) VALUES
  -- 控制台
  (1, 0, 2, '控制台', 'Workbench', '/workbench', 'workbench/index', NULL, 'i-svg:gauge', NULL, 0, 1, 1, 0, NULL, 1, NULL, NULL, 1, 0, NOW(), NOW()),

  -- ===== 系统管理 =====
  (2, 0, 1, '系统管理', 'System', '/system', 'LAYOUT', NULL, 'i-svg:settings', 'system', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 900, NOW(), NOW()),

  -- 管理员管理
  (10, 2, 2, '管理员管理', 'SystemAdmin', '/system/admin', '/system/admin/index', NULL, 'i-svg:user', 'system.admin.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (11, 10, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'system.admin.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (12, 10, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'system.admin.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (13, 10, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'system.admin.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (14, 10, 3, '状态', NULL, NULL, NULL, NULL, NULL, 'system.admin.status', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),

  -- 角色管理
  (20, 2, 2, '角色管理', 'SystemRole', '/system/role', '/system/role/index', NULL, 'i-svg:users', 'system.role.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (21, 20, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'system.role.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (22, 20, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'system.role.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (23, 20, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'system.role.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (24, 20, 3, '授权', NULL, NULL, NULL, NULL, NULL, 'system.role.permission', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (25, 20, 3, '状态', NULL, NULL, NULL, NULL, NULL, 'system.role.status', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW()),

  -- 部门管理
  (30, 2, 2, '部门管理', 'SystemDepartment', '/system/department', '/system/department/index', NULL, 'i-svg:network', 'system.department.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (31, 30, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'system.department.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (32, 30, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'system.department.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (33, 30, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'system.department.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),

  -- 权限管理
  (40, 2, 2, '权限管理', 'SystemPermission', '/system/permission', '/system/permission/index', NULL, 'i-svg:lock', 'system.permission.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),

  -- 菜单管理
  (50, 2, 2, '菜单管理', 'SystemMenu', '/system/menu', '/system/menu/index', NULL, 'i-svg:layout-grid', 'system.menu.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW()),
  (51, 50, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'system.menu.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (52, 50, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'system.menu.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (53, 50, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'system.menu.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),

  -- 数据字典
  (60, 2, 2, '数据字典', 'SystemDictionary', '/system/dictionary', '/system/dictionary/index', NULL, 'i-svg:library-big', 'system.dictionary.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 6, NOW(), NOW()),
  (61, 60, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'system.dictionary.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (62, 60, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'system.dictionary.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (63, 60, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'system.dictionary.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),

  -- 文件管理
  (70, 2, 2, '文件管理', 'SystemFile', '/system/file', '/system/file/index', NULL, 'i-svg:folder-open', 'system.file.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 7, NOW(), NOW()),
  (72, 70, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'system.file.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (71, 70, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'system.file.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),

  -- 通知管理

  -- 定时任务
  (90, 2, 2, '定时任务', 'SystemCronJob', '/system/cron-job', '/system/cron-job/index', NULL, 'i-svg:bolt', 'system.cron_job.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 9, NOW(), NOW()),
  (91, 90, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'system.cron_job.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (92, 90, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'system.cron_job.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (93, 90, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'system.cron_job.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (94, 90, 3, '执行', NULL, NULL, NULL, NULL, NULL, 'system.cron_job.run', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (95, 90, 3, '清空日志', NULL, NULL, NULL, NULL, NULL, 'system.cron_job.clear', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW()),

  -- 系统配置
  (100, 2, 2, '系统配置', 'SystemConfig', '/system/config', '/system/config/index', NULL, 'i-svg:cog', 'system.config.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 10, NOW(), NOW()),
  (101, 100, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'system.config.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),

  -- 产品授权
  (102, 2, 2, '产品授权', 'SystemLicense', '/system/license', '/system/license/index', NULL, 'i-svg:lock', 'system.license.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 10, NOW(), NOW()),
  (103, 102, 3, '激活', NULL, NULL, NULL, NULL, NULL, 'system.license.activate', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),

  -- 日志管理（目录）
  (110, 2, 1, '日志管理', 'SystemLog', '/system/log', 'LAYOUT', NULL, 'i-svg:scroll-text', 'system.log', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 11, NOW(), NOW()),
  (111, 110, 2, '登录日志', 'SystemLoginLog', '/system/log/login', '/system/log/login', NULL, NULL, 'system.log.login', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (112, 110, 2, '操作日志', 'SystemOperationLog', '/system/log/operation', '/system/log/operation', NULL, NULL, 'system.log.operation', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (113, 110, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'system.log.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (114, 110, 3, '清空', NULL, NULL, NULL, NULL, NULL, 'system.log.clear', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),

  -- ===== 开发工具 =====
  (3, 0, 1, '开发工具', 'DevTools', '/dev-tools', 'LAYOUT', NULL, 'i-svg:cpu', NULL, 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 950, NOW(), NOW()),

  -- 代码生成器
  (200, 3, 2, '代码生成器', 'DevGenerator', '/dev-tools/generator', '/system/generator/index', NULL, 'i-svg:file-sliders', 'system.generator.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (201, 200, 3, '生成', NULL, NULL, NULL, NULL, NULL, 'system.generator.generate', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),

  -- API文档
  (210, 3, 2, 'API文档', 'DevApiDoc', '/dev-tools/api-doc', '/system/api-doc/index', NULL, 'i-svg:notebook-text', 'system.api_doc', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),

  -- ===== 消息管理（系统管理子模块） =====
  (120, 2, 1, '消息管理', 'SystemMessage', '/system/message', 'LAYOUT', '/system/message/template', 'i-svg:message-circle-more', 'system.message', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 12, NOW(), NOW()),
  (121, 120, 2, '消息模板', 'SystemMessageTemplate', '/system/message/template', '/system/message/template/index', NULL, 'i-svg:receipt-text', 'system.message.template.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (122, 120, 2, '发送记录', 'SystemMessageLog', '/system/message/log', '/system/message/log/index', NULL, 'i-svg:layout-list', 'system.message.log.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (123, 121, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'system.message.template.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (124, 121, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'system.message.template.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (125, 121, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'system.message.template.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (126, 121, 3, '发送测试', NULL, NULL, NULL, NULL, NULL, 'system.message.template.send', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),

  -- ===== 渠道管理 =====
  -- 公众号（目录，归属应用管理）
  (5, 16, 1, '公众号', 'ChannelOfficial', '/channel/official', 'LAYOUT', '/channel/official/config', 'i-svg:compass', 'channel.official', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (400, 5, 2, '公众号配置', 'ChannelOfficialConfig', '/channel/official/config', '/channel/official/config', NULL, 'i-svg:settings', 'channel.official.config', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (401, 400, 3, '发送模板', NULL, NULL, NULL, NULL, NULL, 'channel.official.config.send', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (410, 5, 2, '自定义菜单', 'ChannelOfficialMenu', '/channel/official/menu', '/channel/official/menu', NULL, 'i-svg:layout-grid', 'channel.official.menu', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (411, 410, 3, '创建', NULL, NULL, NULL, NULL, NULL, 'channel.official.menu.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (412, 410, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'channel.official.menu.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (420, 5, 2, '自动回复', 'ChannelAutoReply', '/channel/official/auto-reply', '/channel/official/auto-reply', NULL, 'i-svg:message-square', 'channel.official.auto_reply', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (421, 420, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'channel.official.auto_reply.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (422, 420, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'channel.official.auto_reply.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (423, 420, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'channel.official.auto_reply.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),

  -- 小程序（目录 + 配置子菜单）
  (6, 16, 1, '小程序', 'ChannelMiniApp', '/channel/miniapp', 'LAYOUT', '/channel/miniapp/config', 'i-svg:smartphone', 'channel.miniapp', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (500, 6, 2, '小程序配置', 'ChannelMiniAppConfig', '/channel/miniapp/config', '/channel/miniapp/config', NULL, 'i-svg:settings', 'channel.miniapp.config', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),

  -- ===== 公告/反馈（系统管理下） =====
  (1710, 2, 2, '公告管理', 'ContentAnnouncement', '/content/announcement', '/content/announcement/index', NULL, 'i-svg:bell-ring', 'announcement.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 20, NOW(), NOW()),
  (1711, 1710, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'announcement.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1712, 1710, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'announcement.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (1713, 1710, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'announcement.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (1714, 1710, 3, '状态', NULL, NULL, NULL, NULL, NULL, 'announcement.status', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (1720, 2, 2, '反馈管理', 'ContentFeedback', '/content/feedback', '/content/feedback/index', NULL, 'i-svg:message-square-text', 'feedback.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 21, NOW(), NOW()),
  (1721, 1720, 3, '回复', NULL, NULL, NULL, NULL, NULL, 'feedback.reply', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1722, 1720, 3, '关闭', NULL, NULL, NULL, NULL, NULL, 'feedback.close', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (1723, 1720, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'feedback.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),

  -- ===== 帮助管理 =====
  (1730, 2, 1, '帮助管理', 'ContentHelpManage', '/content/help-manage', 'LAYOUT', '/content/help', 'i-lucide:circle-help', 'content.help_manage', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 25, NOW(), NOW()),
  (1731, 1730, 2, '帮助分类', 'ContentHelpCategory', '/content/help-category', '/content/help-category/index', NULL, 'i-lucide:folder-tree', 'content.help_category.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1732, 1730, 2, '帮助列表', 'ContentHelp', '/content/help', '/content/help/index', NULL, 'i-lucide:file-question', 'content.help.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  -- 文章资讯（目录）
  -- 文章栏目
  -- 文章管理

  -- ===== 插件管理 =====
  (8, 0, 1, '插件管理', 'Plugin', '/plugins', 'LAYOUT', '/plugins/installed', 'i-svg:box', 'plugin.installed', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 60, NOW(), NOW()),
  (1820, 8, 2, '已安装插件', 'PluginInstalled', '/plugins/installed', '/plugins/installed/index', NULL, 'i-lucide:layout-grid',  'plugin.installed', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1821, 8, 2, '插件市场',   'PluginMarket',    '/plugins/market',    '/plugins/market/index',    NULL, 'i-lucide:shopping-bag', 'plugin.market',    0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (1800, 1300, 2, '区域管理', 'AppRegion', '/app/region', '/content/region/index', NULL, 'i-svg:map-pinned', 'region.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 8, NOW(), NOW()),
  (1801, 1800, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'region.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1802, 1800, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'region.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (1803, 1800, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'region.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (1810, 2, 2, '应用版本', 'AppVersion', '/app/version', '/content/version/index', NULL, 'i-svg:arrow-up-from-line', 'version.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 15, NOW(), NOW()),
  (1811, 1810, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'version.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1812, 1810, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'version.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (1813, 1810, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'version.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),

  -- ===== 商品中心 =====
  (700, 0, 1, '商品中心', 'Goods', '/goods', 'LAYOUT', '/goods/goods-spu', 'i-svg:shopping-cart', 'goods', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 20, NOW(), NOW()),
  (710, 700, 2, '商品列表', 'GoodsSpu', '/goods/goods-spu', 'goods/goods-spu/index', NULL, 'i-svg:shopping-cart', 'goods.goods-spu', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (711, 700, 2, '商品编辑', 'GoodsSpuEdit', '/goods/goods-spu/edit', 'goods/goods-spu/edit', NULL, NULL, 'goods.goods-spu.edit', 1, 0, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (712, 710, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'goods.goods-spu.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (713, 710, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'goods.goods-spu.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (714, 710, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'goods.goods-spu.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (720, 700, 2, '商品分类', 'GoodsCategory', '/goods/goods-category', 'goods/goods-category/index', NULL, 'i-svg:layout-grid', 'goods.goods-category', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (721, 720, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'goods.goods-category.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (722, 720, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'goods.goods-category.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (723, 720, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'goods.goods-category.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (730, 700, 2, '品牌管理', 'GoodsBrand', '/goods/goods-brand', 'goods/goods-brand/index', NULL, 'i-svg:star', 'goods.goods-brand', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (731, 730, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'goods.goods-brand.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (732, 730, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'goods.goods-brand.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (733, 730, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'goods.goods-brand.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (740, 700, 2, '计量单位', 'GoodsUnit', '/goods/goods-unit', 'goods/goods-unit/index', NULL, 'i-lucide:coins', 'goods.goods-unit', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (741, 740, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'goods.goods-unit.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (742, 740, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'goods.goods-unit.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (743, 740, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'goods.goods-unit.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (750, 700, 2, '属性分组', 'GoodsAttributeGroup', '/goods/goods-attribute-group', 'goods/goods-attribute-group/index', NULL, 'i-svg:library-big', 'goods.goods-attribute-group', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW()),
  (751, 750, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'goods.goods-attribute-group.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (752, 750, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'goods.goods-attribute-group.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (753, 750, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'goods.goods-attribute-group.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (760, 700, 2, '商品属性', 'GoodsAttribute', '/goods/goods-attribute', 'goods/goods-attribute/index', NULL, 'i-svg:layout-list', 'goods.goods-attribute', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 6, NOW(), NOW()),
  (761, 760, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'goods.goods-attribute.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (762, 760, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'goods.goods-attribute.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (763, 760, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'goods.goods-attribute.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (770, 700, 2, '运费模板', 'GoodsFreightTemplate', '/goods/goods-freight-template', 'goods/goods-freight-template/index', NULL, 'i-svg:truck', 'goods.goods-freight-template', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 7, NOW(), NOW()),
  (771, 770, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'goods.goods-freight-template.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (772, 770, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'goods.goods-freight-template.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (773, 770, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'goods.goods-freight-template.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (774, 700, 2, '运费模板编辑', 'GoodsFreightTemplateEdit', '/goods/goods-freight-template/edit', 'goods/goods-freight-template/edit', NULL, NULL, 'goods.goods-freight-template.update', 1, 0, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (780, 700, 2, '规格模板', 'GoodsSpecTemplate', '/goods/goods-spec-template', 'goods/goods-spec-template/index', NULL, 'i-svg:layers', 'goods.goods-spec-template', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 8, NOW(), NOW()),
  (781, 780, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'goods.goods-spec-template.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (782, 780, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'goods.goods-spec-template.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (783, 780, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'goods.goods-spec-template.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),

  -- ===== 订单中心 =====
  (800, 0, 1, '订单中心', 'Order', '/order', 'LAYOUT', '/order/order-list', 'i-svg:file-text', 'order', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 30, NOW(), NOW()),
  (810, 800, 2, '订单列表', 'OrderList', '/order/order-list', 'order/order-list/index', NULL, 'i-svg:layout-list', 'order.order', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (811, 800, 2, '订单详情', 'OrderDetail', '/order/order-detail', 'order/order-detail/index', NULL, NULL, 'order.order', 1, 0, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (812, 810, 3, '取消订单', NULL, NULL, NULL, NULL, NULL, 'order.cancel', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (813, 810, 3, '发货', NULL, NULL, NULL, NULL, NULL, 'order.ship', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (814, 810, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'order.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (815, 810, 3, '拆单', NULL, NULL, NULL, NULL, NULL, 'order.split', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (816, 810, 3, '合单', NULL, NULL, NULL, NULL, NULL, 'order.merge', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW()),
  (817, 810, 3, '改价', NULL, NULL, NULL, NULL, NULL, 'order.price-adjust', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 6, NOW(), NOW()),
  (818, 810, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'order.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 7, NOW(), NOW()),
  (820, 800, 2, '售后管理', 'OrderRefund', '/order/order-refund', 'order/order-refund/index', NULL, 'i-svg:rotate-ccw', 'order.refund', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (821, 820, 3, '同意退款', NULL, NULL, NULL, NULL, NULL, 'order.refund.approve', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (822, 820, 3, '拒绝退款', NULL, NULL, NULL, NULL, NULL, 'order.refund.reject', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (830, 800, 2, '评价管理', 'OrderReview', '/order/order-review', 'order/order-review/index', NULL, 'i-svg:message-circle-more', 'order.review', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (831, 830, 3, '回复', NULL, NULL, NULL, NULL, NULL, 'order.review.reply', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (840, 800, 2, '交易设置', 'OrderSettings', '/order/order-settings', 'order/order-settings/index', NULL, 'i-svg:settings', 'order.settings', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (841, 800, 2, '退货原因', 'OrderRefundReason', '/order/order-refund-reason', 'order/order-refund-reason/index', NULL, 'i-lucide:file-x', 'order.refund-reason', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW()),
  (842, 841, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'order.refund-reason.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (843, 841, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'order.refund-reason.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (844, 841, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'order.refund-reason.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (850, 800, 2, '发货管理', 'OrderShip', '/order/order-ship', 'order/order-ship/index', NULL, 'i-svg:truck', 'order.order', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 6, NOW(), NOW()),
  (860, 800, 2, '发票管理', 'OrderInvoice', '/order/order-invoice', 'order/order-invoice/index', NULL, 'i-svg:file-text', 'order.invoice.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 7, NOW(), NOW()),
  (861, 860, 3, '受理/开票/作废', NULL, NULL, NULL, NULL, NULL, 'order.invoice.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (862, 860, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'order.invoice.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),

  -- ===== 用户管理 =====
  (900, 0, 1, '用户管理', 'Member', '/member', 'LAYOUT', '/member/member-list', 'i-lucide:user', 'member', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 40, NOW(), NOW()),
  (910, 900, 2, '用户列表', 'MemberList', '/member/member-list', 'member/member-list/index', NULL, 'i-lucide:user-circle', 'member.user', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (911, 910, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'member.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (912, 910, 3, '发短信', NULL, NULL, NULL, NULL, NULL, 'member.sms', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (913, 910, 3, '送优惠券', NULL, NULL, NULL, NULL, NULL, 'member.coupon', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (914, 910, 3, '运营备注', NULL, NULL, NULL, NULL, NULL, 'member.remark', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (915, 910, 3, '修改地址', NULL, NULL, NULL, NULL, NULL, 'member.address.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW()),
  (920, 900, 2, '会员等级', 'MemberLevel', '/member/member-level', 'member/member-level/index', NULL, 'i-lucide:medal', 'member.level', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (921, 920, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'member.level.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (922, 920, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'member.level.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (923, 920, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'member.level.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (924, 900, 2, '用户标签', 'UserTag', '/member/user-tag', 'member/user-tag/index', NULL, 'i-svg:tag', 'member.tag', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (925, 924, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'member.tag.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (926, 924, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'member.tag.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (927, 924, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'member.tag.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (928, 924, 3, '打标签', NULL, NULL, NULL, NULL, NULL, 'member.tag.assign', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (929, 924, 3, '移除标签', NULL, NULL, NULL, NULL, NULL, 'member.tag.remove', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW()),
  (985, 924, 3, '刷新标签', NULL, NULL, NULL, NULL, NULL, 'member.tag.refresh', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 6, NOW(), NOW()),
  (960, 900, 2, '用户统计', 'MemberStatistics', '/member/statistics', 'member/statistics/index', NULL, 'i-lucide:bar-chart-3', 'member.statistics', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW()),
  (961, 900, 2, '用户分组', 'UserGroup', '/member/user-group', 'member/user-group/index', NULL, 'i-svg:library-big', 'member.user_group', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (962, 961, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'member.user_group.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (963, 961, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'member.user_group.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (964, 961, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'member.user_group.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (965, 900, 2, '充值套餐', 'MemberRechargePackage', '/member/recharge-package', 'member/recharge-package/index', NULL, 'i-lucide:credit-card', 'member.recharge-package', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 9, NOW(), NOW()),
  (966, 965, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'member.recharge-package.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (967, 965, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'member.recharge-package.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (968, 965, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'member.recharge-package.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (970, 900, 2, '地址簿', 'AddressBook', '/member/address-book', 'member/address-book/index', NULL, 'i-svg:map-pin', 'member.address.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 10, NOW(), NOW()),
  (971, 970, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'member.address.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (975, 900, 2, '账户资金', 'AccountFund', '/member/account-fund', 'member/account-fund/index', NULL, 'i-svg:wallet', 'member.fund.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 11, NOW(), NOW()),
  (976, 975, 3, '审核提现', NULL, NULL, NULL, NULL, NULL, 'member.fund.withdraw.approve', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (977, 975, 3, '提现打款', NULL, NULL, NULL, NULL, NULL, 'member.fund.withdraw.pay', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (978, 900, 2, '权益复核', 'MemberRewardReview', '/member/reward-review', 'member/reward-review/index', NULL, 'i-lucide:shield-check', 'member.reward_review.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 12, NOW(), NOW()),
  (979, 978, 3, '复核结案', NULL, NULL, NULL, NULL, NULL, 'member.reward_review.resolve', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  -- ===== 财务管理 =====
  (1200, 0, 1, '财务管理', 'Finance', '/finance', 'LAYOUT', '/finance/overview', 'i-svg:wallet', 'finance', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 45, NOW(), NOW()),
  (1210, 1200, 2, '财务概览', 'FinanceOverview', '/finance/overview', 'finance/overview/index', NULL, 'i-lucide:bar-chart-3', 'finance.overview', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1220, 1200, 2, '资金流水', 'FinanceTransaction', '/finance/transaction', 'finance/transaction/index', NULL, 'i-svg:layout-list', 'finance.transaction', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (1230, 1200, 2, '余额明细', 'FinanceBalanceLog', '/finance/balance-log', 'finance/balance-log/index', NULL, 'i-svg:wallet', 'finance.balance-log', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (1240, 1200, 2, '积分明细', 'FinancePointsLog', '/finance/points-log', 'finance/points-log/index', NULL, 'i-svg:star', 'finance.points-log', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (1245, 1200, 2, '积分规则', 'FinancePointsRules', '/finance/points-rules', 'finance/points-rules/index', NULL, 'i-lucide:gift', 'finance.points.rules', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW()),
  (1250, 1200, 2, '提现管理', 'FinanceWithdrawal', '/finance/withdrawal', 'finance/withdrawal/index', NULL, 'i-svg:wallet', 'finance.withdrawal', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW()),

  -- ===== 营销管理 =====
  (1000, 0, 1, '营销管理', 'Marketing', '/marketing', 'LAYOUT', '/marketing/coupon', 'i-lucide:gift', 'marketing', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 50, NOW(), NOW()),
  -- 注意：8/16/1060 的 sort 已在前面就位（60/70/80），保持 v2.7.0 顺序一致

  -- ===== 页面装修 =====
  (1060, 0, 1, '页面装修', 'Diy', '/diy', 'LAYOUT', '/diy/page', 'i-lucide:paintbrush', 'diy', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 80, NOW(), NOW()),
  (1061, 1060, 2, '页面装修', 'DiyPage', '/diy/page', 'diy/page/index', NULL, 'i-lucide:layout-template', 'diy.page.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1064, 1060, 2, '模板中心', 'DiyTemplate', '/diy/template', 'diy/template/index', NULL, 'i-lucide:wand-sparkles', 'diy.template.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (1070, 1060, 2, '装修编辑器', 'DiyEditor', '/diy/editor', 'diy/editor/index', NULL, NULL, 'diy.page.update', 1, 0, 0, 0, NULL, 1, NULL, NULL, 1, 6, NOW(), NOW()),
  (1071, 1061, 3, '装修', NULL, NULL, NULL, NULL, NULL, 'diy.page.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1072, 1061, 3, '发布', NULL, NULL, NULL, NULL, NULL, 'diy.page.publish', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (1073, 1061, 3, '设为默认', NULL, NULL, NULL, NULL, NULL, 'diy.page.set_default', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (1074, 1061, 3, '历史版本', NULL, NULL, NULL, NULL, NULL, 'diy.page.version.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 6, NOW(), NOW()),
  (1075, 1064, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'diy.template.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1076, 1064, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'diy.template.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (1077, 1064, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'diy.template.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (1078, 1061, 3, '新建', NULL, NULL, NULL, NULL, NULL, 'diy.page.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (1079, 1061, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'diy.page.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW()),
  (1080, 1060, 2, '分类配置', 'DiyCategory', '/diy/category', 'diy/category/index', NULL, 'i-svg:layout-grid', 'diy.category.manage', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 7, NOW(), NOW()),
  (1081, 1061, 3, '恢复版本', NULL, NULL, NULL, NULL, NULL, 'diy.page.version.restore', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 7, NOW(), NOW()),
  (1085, 1060, 2, '底部导航', 'DiyTabbar', '/diy/tabbar', 'diy/tabbar/index', NULL, 'i-lucide:menu', 'diy.tabbar.manage', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 8, NOW(), NOW()),
  (1090, 1060, 2, '商城风格', 'DiyStyle', '/diy/style', 'diy/style/index', NULL, 'i-lucide:paintbrush', 'diy.style.manage', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 9, NOW(), NOW()),
  (1095, 1060, 2, 'PC 头部/底部', 'DiyPcMenu', '/diy/pc-menu', 'diy/pc-menu/index', NULL, 'i-lucide:layout-panel-top', 'diy.pc_menu.manage', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 10, NOW(), NOW()),

  -- ===== 开放平台（渠道管理子菜单） =====
  (15, 16, 1, '开放平台', 'ChannelOpen', '/channel/open', 'LAYOUT', '/channel/open/config', 'i-svg:globe', 'channel.open', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW()),
  (550, 15, 2, '开放平台配置', 'ChannelOpenConfig', '/channel/open/config', '/channel/open/config', NULL, 'i-svg:settings', 'channel.open.config', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  -- Channel 一级菜单（容纳公众号/小程序/开放平台）
  (16, 0, 1, '渠道管理', 'Channel', '/channel', 'LAYOUT', '/channel/official', 'i-lucide:radio-tower', NULL, 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 70, NOW(), NOW()),

  -- ===== 物流配送 =====
  (1300, 0, 1, '物流配送', 'Delivery', '/delivery', 'LAYOUT', '/delivery/express-company', 'i-svg:truck', 'delivery', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 35, NOW(), NOW()),
  (1310, 1300, 2, '物流公司', 'ExpressCompany', '/delivery/express-company', 'delivery/express-company/index', NULL, 'i-svg:truck', 'delivery.express', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1311, 1310, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'delivery.express.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1312, 1310, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'delivery.express.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (1313, 1310, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'delivery.express.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (1320, 1300, 2, '电子面单', 'WaybillConfig', '/delivery/waybill-config', 'delivery/waybill-config/index', NULL, 'i-svg:receipt-text', 'delivery', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (1330, 1300, 2, '配送设置', 'LocalDeliveryConfig', '/delivery/local-config', 'delivery/local-config/index', NULL, 'i-svg:settings', 'delivery', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (1340, 1300, 2, '配送员管理', 'DeliveryStaff', '/delivery/staff', 'delivery/staff/index', NULL, 'i-lucide:user', 'delivery.staff', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (1341, 1340, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'delivery.staff.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1342, 1340, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'delivery.staff.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (1343, 1340, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'delivery.staff.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (1350, 1300, 2, '配送记录', 'DeliveryOrder', '/delivery/order', 'delivery/order/index', NULL, 'i-svg:layout-list', 'delivery.order', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 5, NOW(), NOW()),
  (1360, 1300, 2, '异常工单', 'DeliveryException', '/delivery/exception', 'delivery/exception/index', NULL, 'i-svg:alert-triangle', 'delivery.exception.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 6, NOW(), NOW()),
  (1361, 1360, 3, '新增',     NULL, NULL, NULL, NULL, NULL, 'delivery.exception.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1362, 1360, 3, '处理',     NULL, NULL, NULL, NULL, NULL, 'delivery.exception.update', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (1363, 1360, 3, '删除',     NULL, NULL, NULL, NULL, NULL, 'delivery.exception.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (1370, 1300, 2, '实时地图', 'DeliveryMap',   '/delivery/map',   'delivery/map/index',   NULL, 'i-svg:map',            'delivery.order.list',   0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 7, NOW(), NOW()),
  (1380, 1300, 2, '排班管理', 'DeliveryShift', '/delivery/shift', 'delivery/shift/index', NULL, 'i-svg:calendar-clock', 'delivery.shift.list',   0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 8, NOW(), NOW()),
  (1381, 1380, 3, '管理',     NULL, NULL, NULL, NULL, NULL, 'delivery.shift.manage', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW());

-- ===== 门店管理 (v2.4.0) =====
INSERT INTO `menus` (`id`, `parent_id`, `type`, `title`, `name`, `path`, `component`, `redirect`, `icon`, `permission`, `is_hidden`, `is_cache`, `is_affix`, `is_iframe`, `external_link`, `breadcrumb`, `active_menu`, `meta`, `status`, `sort`, `created_at`, `updated_at`) VALUES
  (1400, 0, 1, '门店管理', 'Store', '/store', 'LAYOUT', '/store/index', 'i-svg:store', 'store', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 32, NOW(), NOW()),
  (1410, 1400, 2, '门店列表', 'StoreIndex', '/store/index', 'store/index', NULL, 'i-svg:layout-list', 'store.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1411, 1410, 3, '新增', NULL, NULL, NULL, NULL, NULL, 'store.create', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1412, 1410, 3, '编辑', NULL, NULL, NULL, NULL, NULL, 'store.edit', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()),
  (1413, 1410, 3, '删除', NULL, NULL, NULL, NULL, NULL, 'store.delete', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 3, NOW(), NOW()),
  (1414, 1410, 3, '订单核销', NULL, NULL, NULL, NULL, NULL, 'order.pickup.verify', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 4, NOW(), NOW()),
  (1050, 1000, 1, '广告管理', 'AdManage', '/marketing/ad-manage', 'LAYOUT', '/marketing/ad-position', 'i-lucide:image', 'marketing.ad_manage', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 50, NOW(), NOW()),
  (1051, 1050, 2, '广告位', 'MarketingAdPosition', '/marketing/ad-position', 'marketing/ad-position/index', NULL, 'i-lucide:layout-grid', 'marketing.ad_position.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()),
  (1052, 1050, 2, '广告', 'MarketingAd', '/marketing/ad', 'marketing/ad/index', NULL, 'i-lucide:image-plus', 'marketing.ad.list', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW());

-- ============================================================
-- 为超级管理员角色分配所有权限和菜单
-- ============================================================
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`)
SELECT 1, id, NOW(), NOW() FROM `permissions`;

INSERT INTO `role_menus` (`role_id`, `menu_id`, `created_at`, `updated_at`)
SELECT 1, id, NOW(), NOW() FROM `menus`;

-- ============================================================
-- 数据字典初始数据
-- ============================================================
INSERT INTO `dictionaries` (`name`, `code`, `description`, `status`, `sort`, `created_at`, `updated_at`) VALUES
('性别', 'gender', '用户性别', 1, 0, NOW(), NOW()),
('状态', 'common_status', '通用启用/禁用状态', 1, 1, NOW(), NOW());

INSERT INTO `dictionary_items` (`dictionary_id`, `label`, `value`, `tag_type`, `status`, `sort`, `created_at`, `updated_at`) VALUES
(1, '男', '1', '', 1, 0, NOW(), NOW()),
(1, '女', '2', '', 1, 1, NOW(), NOW()),
(1, '未知', '0', 'info', 1, 2, NOW(), NOW()),
(2, '启用', '1', 'success', 1, 0, NOW(), NOW()),
(2, '禁用', '0', 'danger', 1, 1, NOW(), NOW());

-- ============================================================
-- 部门初始数据
-- ============================================================
INSERT INTO `departments` (`id`, `parent_id`, `name`, `code`, `leader`, `sort`, `status`, `created_at`, `updated_at`) VALUES
(1, 0, '总公司', 'HQ', '管理员', 0, 1, NOW(), NOW()),
(2, 1, '技术部', 'TECH', NULL, 1, 1, NOW(), NOW()),
(3, 1, '市场部', 'MARKET', NULL, 2, 1, NOW(), NOW()),
(4, 1, '财务部', 'FINANCE', NULL, 3, 1, NOW(), NOW()),
(5, 2, '前端组', 'TECH-FE', NULL, 1, 1, NOW(), NOW()),
(6, 2, '后端组', 'TECH-BE', NULL, 2, 1, NOW(), NOW());

-- ============================================================
-- 定时任务示例数据
-- ============================================================
INSERT INTO `cron_jobs` (`name`, `command`, `expression`, `description`, `status`, `created_at`, `updated_at`) VALUES
('超时订单自动取消', 'order:auto-cancel', '*/5 * * * *', '超时未支付订单自动取消并释放库存', 1, NOW(), NOW()),
('发货订单自动确认', 'order:auto-confirm', '0 1 * * *', '发货超期订单自动确认收货', 1, NOW(), NOW()),
('完成订单自动好评', 'order:auto-review', '30 1 * * *', '完成超期订单自动默认好评', 1, NOW(), NOW()),
('自提核销超时扫描', 'pickup:scan-timeout', '*/10 * * * *', '自提订单核销超时扫描处理', 1, NOW(), NOW()),
('拼团超时自动失败', 'group-buy:expire', '*/5 * * * *', '拼团超时未成团自动标记失败并触发退款补偿', 1, NOW(), NOW()),
('支付成功消费者对账', 'payment:reconcile', '*/15 * * * *', '仅重放发布边界后的本地已支付单，补齐订单、充值和财务消费者', 1, NOW(), NOW()),
('退款渠道状态对账', 'refund:reconcile', '*/5 * * * *', '查询长时间退款中的渠道状态，仅在明确成功后完成本地结算', 1, NOW(), NOW()),
('财务流水对账', 'finance:reconcile', '*/15 * * * *', '按游标补齐支付、退款和提现财务流水', 1, NOW(), NOW()),
('订单会员权益对账', 'member:reconcile-order-rewards', '*/30 * * * *', '补齐完成订单奖励快照与退款冲正', 1, NOW(), NOW()),
('用户标签自动刷新', 'user-tag:refresh', '0 * * * *', '按规则重新计算自动用户标签', 1, NOW(), NOW()),
('用户分群自动刷新', 'user-group:refresh', '15 * * * *', '按规则重新计算自动用户分群', 1, NOW(), NOW()),
('日志归档清理', 'log:archive', '0 2 * * *', '清理过期管理员操作与登录日志', 1, NOW(), NOW());

-- ============================================================
-- 系统配置种子数据
-- ============================================================

INSERT INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES

-- ===== 基础配置 (basic) =====
('site_name', '元点Shop', 'basic', 'string', '网站名称', '显示在浏览器标题栏和系统Logo旁', NULL, NULL, 1, 1, NOW(), NOW()),
('site_url', 'http://localhost', 'basic', 'string', '网站地址', '网站访问地址，用于生成完整链接', NULL, NULL, 2, 1, NOW(), NOW()),
('site_logo', '/storage/uploads/images/logo.png', 'basic', 'file', '网站Logo', '建议尺寸 200x50，支持 PNG/SVG 格式', NULL, NULL, 3, 1, NOW(), NOW()),
('site_favicon', '/storage/uploads/images/favicon.ico', 'basic', 'file', '网站图标', '浏览器标签页图标，建议 32x32 ICO/PNG 格式', NULL, NULL, 4, 1, NOW(), NOW()),
('site_description', '一款通用的后台管理系统', 'basic', 'string', '网站描述', '用于SEO和网站简介', NULL, NULL, 5, 1, NOW(), NOW()),
('site_keywords', '后台管理,管理系统,Admin', 'basic', 'string', 'SEO关键词', '多个关键词用英文逗号分隔', NULL, NULL, 6, 1, NOW(), NOW()),
('site_icp', '', 'basic', 'string', 'ICP备案号', '如：京ICP备XXXXXXXX号', NULL, NULL, 7, 1, NOW(), NOW()),
('site_copyright', 'Copyright © 2024 Dev007. All rights reserved.', 'basic', 'string', '版权信息', '显示在页面底部的版权声明', NULL, NULL, 8, 1, NOW(), NOW()),
('site_phone', '', 'basic', 'string', '联系电话', '网站管理员联系电话', NULL, NULL, 9, 1, NOW(), NOW()),
('site_email', '', 'basic', 'string', '联系邮箱', '网站管理员联系邮箱', NULL, NULL, 10, 1, NOW(), NOW()),
('site_address', '', 'basic', 'string', '联系地址', '公司或团队地址', NULL, NULL, 11, 1, NOW(), NOW()),
('site_status', '1', 'basic', 'boolean', '网站开关', '关闭后前台将显示维护提示', NULL, NULL, 12, 1, NOW(), NOW()),
('site_close_tip', '网站维护中，请稍后再试...', 'basic', 'string', '关闭提示', '网站关闭时显示的提示信息', NULL, NULL, 13, 1, NOW(), NOW()),
('user_register', '1', 'basic', 'boolean', '开放注册', '是否允许新用户注册', NULL, NULL, 14, 1, NOW(), NOW()),
('login_captcha', '1', 'basic', 'boolean', '登录验证码', '登录时是否需要输入验证码', NULL, NULL, 15, 1, NOW(), NOW()),
('password_min_length', '6', 'basic', 'number', '密码最小长度', '用户密码最少字符数', NULL, NULL, 16, 1, NOW(), NOW()),
('login_max_retry', '5', 'basic', 'number', '登录失败上限', '连续登录失败后锁定账号的次数', NULL, NULL, 17, 1, NOW(), NOW()),
('login_lock_duration', '30', 'basic', 'number', '锁定时长(分钟)', '账号被锁定后的等待时间', NULL, NULL, 18, 1, NOW(), NOW()),

-- ===== 邮件配置 (email) =====
('smtp_host', '', 'email', 'string', 'SMTP服务器', '例如：smtp.qq.com、smtp.163.com', NULL, NULL, 1, 1, NOW(), NOW()),
('smtp_port', '465', 'email', 'number', 'SMTP端口', '常用端口：25(不加密)、465(SSL)、587(TLS)', NULL, NULL, 2, 1, NOW(), NOW()),
('smtp_user', '', 'email', 'string', 'SMTP用户名', '通常为发件人邮箱地址', NULL, NULL, 3, 1, NOW(), NOW()),
('smtp_pass', '', 'email', 'string', 'SMTP密码', 'SMTP授权码或密码', NULL, NULL, 4, 1, NOW(), NOW()),
('smtp_from_address', '', 'email', 'string', '发件人地址', '发件人邮箱地址', NULL, NULL, 5, 1, NOW(), NOW()),
('smtp_from_name', '元点Shop', 'email', 'string', '发件人名称', '收件人看到的发件人名称', NULL, NULL, 6, 1, NOW(), NOW()),
('smtp_encryption', 'ssl', 'email', 'select', '加密方式', '邮件传输加密方式', '{"ssl":"SSL","tls":"TLS","none":"不加密"}', NULL, 7, 1, NOW(), NOW()),
('email_test_address', '', 'email', 'string', '测试收件地址', '用于发送测试邮件的收件人地址', NULL, NULL, 8, 1, NOW(), NOW()),

-- ===== 短信配置 (sms) =====
('sms_driver', 'aliyun', 'sms', 'select', '短信服务商', '选择短信发送服务商', '{"aliyun":"阿里云","tencent":"腾讯云"}', NULL, 1, 1, NOW(), NOW()),
('sms_access_key', '', 'sms', 'string', 'AccessKey ID', '短信服务商提供的 AccessKey ID', NULL, NULL, 2, 1, NOW(), NOW()),
('sms_access_secret', '', 'sms', 'string', 'AccessKey Secret', '短信服务商提供的 AccessKey Secret', NULL, NULL, 3, 1, NOW(), NOW()),
('sms_sign_name', '', 'sms', 'string', '短信签名', '已审核通过的短信签名', NULL, NULL, 4, 1, NOW(), NOW()),

-- ===== 支付配置 (payment) =====
('pay_alipay_enabled', '0', 'payment', 'boolean', '启用支付宝', '是否开启支付宝支付', NULL, NULL, 1, 1, NOW(), NOW()),
('pay_alipay_app_id', '', 'payment', 'string', '支付宝AppID', '支付宝开放平台应用AppID', NULL, '{"field":"pay_alipay_enabled","value":"1"}', 2, 1, NOW(), NOW()),
('pay_alipay_private_key', '', 'payment', 'string', '应用私钥', '支付宝应用私钥(RSA2)', NULL, '{"field":"pay_alipay_enabled","value":"1"}', 3, 1, NOW(), NOW()),
('pay_alipay_public_key', '', 'payment', 'string', '支付宝公钥', '支付宝公钥', NULL, '{"field":"pay_alipay_enabled","value":"1"}', 4, 1, NOW(), NOW()),
('pay_alipay_notify_url', '', 'payment', 'string', '异步通知地址', '支付宝异步回调通知URL', NULL, '{"field":"pay_alipay_enabled","value":"1"}', 5, 1, NOW(), NOW()),
('pay_wechat_enabled', '0', 'payment', 'boolean', '启用微信支付', '是否开启微信支付', NULL, NULL, 6, 1, NOW(), NOW()),
('pay_wechat_app_id', '', 'payment', 'string', '微信AppID', '微信公众号或小程序AppID', NULL, '{"field":"pay_wechat_enabled","value":"1"}', 7, 1, NOW(), NOW()),
('pay_wechat_mch_id', '', 'payment', 'string', '微信商户号', '微信支付商户号', NULL, '{"field":"pay_wechat_enabled","value":"1"}', 8, 1, NOW(), NOW()),
('pay_wechat_api_v3_key', '', 'payment', 'string', '微信APIv3密钥', '微信支付APIv3密钥（用于回调资源解密）', NULL, '{"field":"pay_wechat_enabled","value":"1"}', 10, 1, NOW(), NOW()),
('pay_wechat_serial_no', '', 'payment', 'string', '商户API证书序列号', '商户API证书序列号（请求 Authorization 签名使用，非微信支付公钥ID）', NULL, '{"field":"pay_wechat_enabled","value":"1"}', 11, 1, NOW(), NOW()),
('pay_wechat_private_key_path', '', 'payment', 'string', '商户API私钥文件', '商户API私钥 PEM 文件路径（apiclient_key.pem，相对 server/ 或绝对路径）', NULL, '{"field":"pay_wechat_enabled","value":"1"}', 12, 1, NOW(), NOW()),
('pay_wechat_public_key_id', '', 'payment', 'string', '微信支付公钥ID', '微信支付公钥ID（必须以 PUB_KEY_ID_ 开头，用于应答/回调验签；请勿填平台证书序列号）', NULL, '{"field":"pay_wechat_enabled","value":"1"}', 13, 1, NOW(), NOW()),
('pay_wechat_public_key_path', '', 'payment', 'string', '微信支付公钥文件', '微信支付公钥 PEM 文件路径（pub_key.pem，相对 server/ 或绝对路径）', NULL, '{"field":"pay_wechat_enabled","value":"1"}', 14, 1, NOW(), NOW()),
('pay_wechat_notify_url', '/api/payment/notify/wechat', 'payment', 'string', '异步通知地址', '微信支付异步回调通知URL（相对路径会自动补全域名）', NULL, '{"field":"pay_wechat_enabled","value":"1"}', 15, 1, NOW(), NOW()),

-- ===== 存储配置 (storage) =====
('storage_driver', 'local', 'storage', 'select', '存储方式', '选择文件存储方式', '{"local":"本地存储","aliyun":"阿里云OSS","tencent":"腾讯云COS","qiniu":"七牛云"}', NULL, 1, 1, NOW(), NOW()),
('storage_upload_max_size', '10', 'storage', 'number', '最大上传(MB)', '单个文件最大上传大小，单位MB', NULL, NULL, 2, 1, NOW(), NOW()),
('storage_upload_allowed_ext', 'jpg,jpeg,png,gif,svg,webp,bmp,doc,docx,xls,xlsx,ppt,pptx,pdf,zip,rar,txt,csv', 'storage', 'string', '允许的文件类型', '允许上传的文件扩展名，英文逗号分隔', NULL, NULL, 3, 1, NOW(), NOW()),
('storage_image_max_size', '5', 'storage', 'number', '图片最大(MB)', '单张图片最大上传大小，单位MB', NULL, NULL, 4, 1, NOW(), NOW()),
-- 阿里云 OSS
('storage_oss_access_key', '', 'storage', 'string', 'OSS AccessKey', '阿里云OSS AccessKey ID', NULL, '{"field":"storage_driver","value":"aliyun"}', 10, 1, NOW(), NOW()),
('storage_oss_access_secret', '', 'storage', 'string', 'OSS AccessSecret', '阿里云OSS AccessKey Secret', NULL, '{"field":"storage_driver","value":"aliyun"}', 11, 1, NOW(), NOW()),
('storage_oss_bucket', '', 'storage', 'string', 'OSS Bucket', '阿里云OSS Bucket名称', NULL, '{"field":"storage_driver","value":"aliyun"}', 12, 1, NOW(), NOW()),
('storage_oss_endpoint', '', 'storage', 'string', 'OSS Endpoint', '阿里云OSS 访问域名，如 oss-cn-hangzhou.aliyuncs.com', NULL, '{"field":"storage_driver","value":"aliyun"}', 13, 1, NOW(), NOW()),
('storage_oss_domain', '', 'storage', 'string', 'OSS 自定义域名', '绑定的自定义域名，用于生成访问URL', NULL, '{"field":"storage_driver","value":"aliyun"}', 14, 1, NOW(), NOW()),
-- 腾讯云 COS
('storage_cos_secret_id', '', 'storage', 'string', 'COS SecretId', '腾讯云COS SecretId', NULL, '{"field":"storage_driver","value":"tencent"}', 20, 1, NOW(), NOW()),
('storage_cos_secret_key', '', 'storage', 'string', 'COS SecretKey', '腾讯云COS SecretKey', NULL, '{"field":"storage_driver","value":"tencent"}', 21, 1, NOW(), NOW()),
('storage_cos_bucket', '', 'storage', 'string', 'COS Bucket', '腾讯云COS Bucket名称（含AppId后缀，如 bucket-1250000000）', NULL, '{"field":"storage_driver","value":"tencent"}', 22, 1, NOW(), NOW()),
('storage_cos_region', '', 'storage', 'string', 'COS Region', '腾讯云COS 地域，如 ap-guangzhou', NULL, '{"field":"storage_driver","value":"tencent"}', 23, 1, NOW(), NOW()),
('storage_cos_domain', '', 'storage', 'string', 'COS 自定义域名', '绑定的自定义域名，用于生成访问URL', NULL, '{"field":"storage_driver","value":"tencent"}', 24, 1, NOW(), NOW()),
-- 七牛云
('storage_qiniu_access_key', '', 'storage', 'string', '七牛 AccessKey', '七牛云 AccessKey', NULL, '{"field":"storage_driver","value":"qiniu"}', 30, 1, NOW(), NOW()),
('storage_qiniu_secret_key', '', 'storage', 'string', '七牛 SecretKey', '七牛云 SecretKey', NULL, '{"field":"storage_driver","value":"qiniu"}', 31, 1, NOW(), NOW()),
('storage_qiniu_bucket', '', 'storage', 'string', '七牛 Bucket', '七牛云存储空间名称', NULL, '{"field":"storage_driver","value":"qiniu"}', 32, 1, NOW(), NOW()),
('storage_qiniu_domain', '', 'storage', 'string', '七牛访问域名', '七牛云存储空间绑定的域名（含协议，如 https://cdn.example.com）', NULL, '{"field":"storage_driver","value":"qiniu"}', 33, 1, NOW(), NOW()),

-- ===== 公众号配置 (wechat_official) =====
('wechat_official_name', '', 'wechat_official', 'string', '公众号名称', '微信公众号名称', NULL, NULL, 1, 1, NOW(), NOW()),
('wechat_official_original_id', '', 'wechat_official', 'string', '原始ID', '公众号原始ID，如 gh_xxxxxxxx', NULL, NULL, 2, 1, NOW(), NOW()),
('wechat_official_qrcode', '', 'wechat_official', 'file', '公众号二维码', '公众号二维码图片，建议 200x200', NULL, NULL, 3, 1, NOW(), NOW()),
('wechat_official_app_id', '', 'wechat_official', 'string', 'AppID', '微信公众号AppID（开发者ID）', NULL, NULL, 10, 1, NOW(), NOW()),
('wechat_official_app_secret', '', 'wechat_official', 'string', 'AppSecret', '微信公众号AppSecret（开发者密码）', NULL, NULL, 11, 1, NOW(), NOW()),
('wechat_official_token', '', 'wechat_official', 'string', 'Token', '微信公众号消息校验Token', NULL, NULL, 20, 1, NOW(), NOW()),
('wechat_official_aes_key', '', 'wechat_official', 'string', 'EncodingAESKey', '微信公众号消息加解密密钥（43位字符）', NULL, NULL, 21, 1, NOW(), NOW()),
('wechat_official_encrypt_type', '1', 'wechat_official', 'select', '消息加密方式', '1=明文模式 2=兼容模式 3=安全模式，需与微信后台保持一致', '{"1":"明文模式","2":"兼容模式","3":"安全模式"}', NULL, 22, 1, NOW(), NOW()),

-- ===== 小程序配置 (wechat_mini) =====
('wechat_mini_name', '', 'wechat_mini', 'string', '小程序名称', '微信小程序名称', NULL, NULL, 1, 1, NOW(), NOW()),
('wechat_mini_original_id', '', 'wechat_mini', 'string', '原始ID', '小程序原始ID，如 gh_xxxxxxxx', NULL, NULL, 2, 1, NOW(), NOW()),
('wechat_mini_qrcode', '', 'wechat_mini', 'file', '小程序二维码', '小程序二维码图片，建议 200x200', NULL, NULL, 3, 1, NOW(), NOW()),
('wechat_mini_app_id', '', 'wechat_mini', 'string', 'AppID', '微信小程序AppID', NULL, NULL, 10, 1, NOW(), NOW()),
('wechat_mini_app_secret', '', 'wechat_mini', 'string', 'AppSecret', '微信小程序AppSecret', NULL, NULL, 11, 1, NOW(), NOW()),
('wechat_mini_msg_token', '', 'wechat_mini', 'string', 'Token', '消息推送校验Token', NULL, NULL, 20, 1, NOW(), NOW()),
('wechat_mini_msg_aes_key', '', 'wechat_mini', 'string', 'EncodingAESKey', '消息推送加解密密钥（43位字符）', NULL, NULL, 21, 1, NOW(), NOW()),
('wechat_mini_msg_format', 'JSON', 'wechat_mini', 'select', '数据格式', '消息推送数据格式', '{"JSON":"JSON","XML":"XML"}', NULL, 22, 1, NOW(), NOW()),
('wechat_mini_encrypt_type', '1', 'wechat_mini', 'select', '消息加密方式', '1=明文模式 2=兼容模式 3=安全模式，需与微信后台保持一致', '{"1":"明文模式","2":"兼容模式","3":"安全模式"}', NULL, 23, 1, NOW(), NOW()),

-- ===== 开放平台配置 (wechat_open) =====
('wechat_open_app_id', '', 'wechat_open', 'string', 'AppID', '微信开放平台网站应用AppID', NULL, NULL, 1, 1, NOW(), NOW()),
('wechat_open_app_secret', '', 'wechat_open', 'string', 'AppSecret', '微信开放平台网站应用AppSecret', NULL, NULL, 2, 1, NOW(), NOW());

-- ===== 预置消息模板 =====
INSERT INTO `message_templates` (`name`, `code`, `sms_enabled`, `sms_template_id`, `sms_content`, `wechat_official_enabled`, `wechat_official_template_id`, `wechat_official_url`, `wechat_mini_enabled`, `wechat_mini_template_id`, `wechat_mini_page`, `variables`, `remark`, `status`, `created_at`, `updated_at`) VALUES
('登录验证码', 'login_captcha', 1, '', '您的登录验证码为${code}，5分钟内有效，请勿泄露给他人。', 0, '', '', 0, '', '', '[{"key":"code","name":"验证码","example":"6789"}]', '用户登录时发送的验证码通知', 1, NOW(), NOW()),
('注册验证码', 'register_captcha', 1, '', '您的注册验证码为${code}，5分钟内有效，请勿泄露给他人。', 0, '', '', 0, '', '', '[{"key":"code","name":"验证码","example":"1234"}]', '用户注册时发送的验证码通知', 1, NOW(), NOW()),
('找回密码', 'reset_password', 1, '', '您正在找回密码，验证码为${code}，5分钟内有效。', 0, '', '', 0, '', '', '[{"key":"code","name":"验证码","example":"5678"}]', '用户找回密码时发送的验证码通知', 1, NOW(), NOW()),
('绑定手机', 'bind_mobile', 1, '', '您正在绑定手机号，验证码为${code}，5分钟内有效。', 0, '', '', 0, '', '', '[{"key":"code","name":"验证码","example":"9012"}]', '用户绑定手机号时发送的验证码通知', 1, NOW(), NOW()),
('变更手机', 'change_mobile', 1, '', '您正在变更手机号，验证码为${code}，5分钟内有效。', 0, '', '', 0, '', '', '[{"key":"code","name":"验证码","example":"3456"}]', '用户变更手机号时发送的验证码通知', 1, NOW(), NOW()),
('用户注册欢迎', 'user_register', 0, '', '恭喜您注册成功！感谢您的信任与支持。', 0, '', '', 0, '', '', '[]', '用户注册成功后发送的欢迎通知', 1, NOW(), NOW()),
('支付成功通知', 'payment_success', 0, '', '您的订单${order_no}已支付成功，支付金额${amount}元。', 0, '', '', 0, '', '', '[{"key":"order_no","name":"订单号","example":"202603250001"},{"key":"amount","name":"支付金额","example":"99.00"}]', '用户支付成功后发送的通知', 1, NOW(), NOW()),
('反馈已收到', 'feedback_received', 0, '', '您的反馈我们已收到，将尽快为您处理，感谢您的支持！', 0, '', '', 0, '', '', '[]', '用户提交反馈后发送的确认通知', 1, NOW(), NOW());

-- ===== 同城配送配置 (local_delivery) =====
INSERT INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
('delivery_mode_express_enabled', '1', 'local_delivery', 'boolean', '启用快递配送', '系统内置快递方式总开关', NULL, NULL, 0, 1, NOW(), NOW()),
('delivery_mode_pickup_enabled', '1', 'local_delivery', 'boolean', '启用到店自提', '系统内置自提方式总开关（与 v2.4.0 自提门店模块联动）', NULL, NULL, 0, 1, NOW(), NOW()),
('delivery_mode_offline_enabled', '0', 'local_delivery', 'boolean', '启用线下自提', '系统内置仓库自取总开关（B 端预约制）', NULL, NULL, 0, 1, NOW(), NOW()),
('local_delivery_enabled', '0', 'local_delivery', 'boolean', '启用同城配送', '启用后支持同城配送方式', NULL, NULL, 1, 1, NOW(), NOW()),
('local_delivery_platform', 'merchant', 'local_delivery', 'select', '默认发单平台', '同城配送默认发单平台（商家配送或三方平台）', '{"merchant":"商家配送","dada":"达达配送","fengniao":"蜂鸟配送","uupt":"UU跑腿","shansong":"闪送","sfsc":"顺丰同城"}', NULL, 2, 1, NOW(), NOW()),
('local_delivery_radius', '5', 'local_delivery', 'number', '配送范围(km)', '以店铺地址为圆心的配送半径', NULL, NULL, 3, 1, NOW(), NOW()),
('local_delivery_fee', '5', 'local_delivery', 'number', '配送费(元)', '同城配送费用', NULL, NULL, 4, 1, NOW(), NOW()),
('local_delivery_free_amount', '0', 'local_delivery', 'number', '免配送费金额(元)', '订单满此金额免配送费，0表示不启用', NULL, NULL, 5, 1, NOW(), NOW()),
('local_delivery_time_start', '08:00', 'local_delivery', 'string', '配送开始时间', '每日配送服务开始时间', NULL, NULL, 6, 1, NOW(), NOW()),
('local_delivery_time_end', '22:00', 'local_delivery', 'string', '配送结束时间', '每日配送服务结束时间', NULL, NULL, 7, 1, NOW(), NOW()),
('local_delivery_per_km_fee', '1', 'local_delivery', 'number', '每公里费(元)', '同城配送每公里追加费用，实际运费=起步费+ceil(距离)×每公里费', NULL, NULL, 8, 1, NOW(), NOW()),
('local_delivery_auto_dispatch_enabled', '0', 'local_delivery', 'boolean', '支付后自动发单', '开启后订单支付成功自动向默认发单平台下单（仅默认平台为三方平台时生效）', NULL, NULL, 10, 1, NOW(), NOW()),
('local_delivery_shop_name', '', 'local_delivery', 'string', '寄件门店名称', '三方配送取件门店名称', NULL, NULL, 11, 1, NOW(), NOW()),
('local_delivery_shop_phone', '', 'local_delivery', 'string', '寄件门店电话', '三方配送取件联系电话', NULL, NULL, 12, 1, NOW(), NOW()),
('local_delivery_shop_province', '', 'local_delivery', 'string', '寄件门店省份', '三方配送取件地址省份', NULL, NULL, 13, 1, NOW(), NOW()),
('local_delivery_shop_city', '', 'local_delivery', 'string', '寄件门店城市', '三方配送取件地址城市', NULL, NULL, 14, 1, NOW(), NOW()),
('local_delivery_shop_district', '', 'local_delivery', 'string', '寄件门店区县', '三方配送取件地址区县', NULL, NULL, 15, 1, NOW(), NOW()),
('local_delivery_shop_address', '', 'local_delivery', 'string', '寄件门店详细地址', '三方配送取件详细地址', NULL, NULL, 16, 1, NOW(), NOW()),
('local_delivery_shop_lat', '', 'local_delivery', 'string', '寄件门店纬度', '三方配送取件坐标（骑手取件与计费需要）', NULL, NULL, 17, 1, NOW(), NOW()),
('local_delivery_shop_lng', '', 'local_delivery', 'string', '寄件门店经度', '三方配送取件坐标（骑手取件与计费需要）', NULL, NULL, 18, 1, NOW(), NOW()),
('local_delivery_notify_domain', '', 'local_delivery', 'string', '回调通知域名', '三方配送回调地址域名（含协议，如 https://shop.example.com），留空时使用当前请求域名', NULL, NULL, 19, 1, NOW(), NOW()),
('local_delivery_dada_enabled', '0', 'local_delivery', 'boolean', '启用达达配送', '启用达达配送三方平台', NULL, NULL, 20, 1, NOW(), NOW()),
('local_delivery_dada_app_key', '', 'local_delivery', 'string', '达达 AppKey', '达达开放平台 AppKey', NULL, NULL, 21, 1, NOW(), NOW()),
('local_delivery_dada_app_secret', '', 'local_delivery', 'string', '达达 AppSecret', '达达开放平台 AppSecret', NULL, NULL, 22, 1, NOW(), NOW()),
('local_delivery_dada_source_id', '', 'local_delivery', 'string', '达达商户编号', '达达开放平台商户 source_id', NULL, NULL, 23, 1, NOW(), NOW()),
('local_delivery_dada_shop_no', '', 'local_delivery', 'string', '达达门店编号', '达达平台门店编号 shop_no', NULL, NULL, 24, 1, NOW(), NOW()),
('local_delivery_fengniao_enabled', '0', 'local_delivery', 'boolean', '启用蜂鸟配送', '启用蜂鸟即配三方平台', NULL, NULL, 30, 1, NOW(), NOW()),
('local_delivery_fengniao_app_key', '', 'local_delivery', 'string', '蜂鸟 AppKey', '蜂鸟开放平台 AppId', NULL, NULL, 31, 1, NOW(), NOW()),
('local_delivery_fengniao_app_secret', '', 'local_delivery', 'string', '蜂鸟 AppSecret', '蜂鸟开放平台 SecretKey', NULL, NULL, 32, 1, NOW(), NOW()),
('local_delivery_fengniao_shop_id', '', 'local_delivery', 'string', '蜂鸟门店编号', '蜂鸟平台门店 ID', NULL, NULL, 33, 1, NOW(), NOW()),
('local_delivery_uupt_enabled', '0', 'local_delivery', 'boolean', '启用UU跑腿', '启用UU跑腿三方平台', NULL, NULL, 40, 1, NOW(), NOW()),
('local_delivery_uupt_app_key', '', 'local_delivery', 'string', 'UU跑腿 AppKey', 'UU跑腿开放平台 AppKey', NULL, NULL, 41, 1, NOW(), NOW()),
('local_delivery_uupt_app_secret', '', 'local_delivery', 'string', 'UU跑腿 AppSecret', 'UU跑腿开放平台 AppSecret', NULL, NULL, 42, 1, NOW(), NOW()),
('local_delivery_uupt_shop_id', '', 'local_delivery', 'string', 'UU跑腿门店编号', 'UU跑腿平台门店/账号编号', NULL, NULL, 43, 1, NOW(), NOW()),
('local_delivery_shansong_enabled', '0', 'local_delivery', 'boolean', '启用闪送', '启用闪送三方平台', NULL, NULL, 50, 1, NOW(), NOW()),
('local_delivery_shansong_app_key', '', 'local_delivery', 'string', '闪送 AppKey', '闪送开放平台 ClientId', NULL, NULL, 51, 1, NOW(), NOW()),
('local_delivery_shansong_app_secret', '', 'local_delivery', 'string', '闪送 AppSecret', '闪送开放平台 AppSecret', NULL, NULL, 52, 1, NOW(), NOW()),
('local_delivery_shansong_shop_id', '', 'local_delivery', 'string', '闪送门店编号', '闪送平台门店 ID（可空）', NULL, NULL, 53, 1, NOW(), NOW()),
('local_delivery_sfsc_enabled', '0', 'local_delivery', 'boolean', '启用顺丰同城', '启用顺丰同城急送三方平台', NULL, NULL, 60, 1, NOW(), NOW()),
('local_delivery_sfsc_app_key', '', 'local_delivery', 'string', '顺丰同城 开发者ID', '顺丰同城开放平台 partnerID(dev_id)', NULL, NULL, 61, 1, NOW(), NOW()),
('local_delivery_sfsc_app_secret', '', 'local_delivery', 'string', '顺丰同城 校验码', '顺丰同城开放平台 checkWord', NULL, NULL, 62, 1, NOW(), NOW()),
('local_delivery_sfsc_shop_id', '', 'local_delivery', 'string', '顺丰同城门店编号', '顺丰同城平台门店 ID', NULL, NULL, 63, 1, NOW(), NOW());

-- ===== 电子面单配置 (waybill) =====
INSERT INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
('waybill_enabled', '0', 'waybill', 'boolean', '启用电子面单', '启用后可在发货时选择电子面单模版生成运单', NULL, NULL, 1, 1, NOW(), NOW()),
('waybill_provider', '', 'waybill', 'select', '面单服务商', '选择电子面单服务商', '{"kdniao":"快递鸟"}', NULL, 2, 1, NOW(), NOW()),
('waybill_app_key', '', 'waybill', 'string', '用户 ID（EBusinessID）', '快递鸟用户 ID（EBusinessID）', NULL, NULL, 3, 1, NOW(), NOW()),
('waybill_app_secret', '', 'waybill', 'string', 'API Key（AppKey）', '快递鸟 API Key（AppKey），用于签名', NULL, NULL, 4, 1, NOW(), NOW()),
('waybill_sender_name', '', 'waybill', 'string', '发件人姓名', '电子面单发件人姓名', NULL, NULL, 10, 1, NOW(), NOW()),
('waybill_sender_phone', '', 'waybill', 'string', '发件人电话', '电子面单发件人联系电话', NULL, NULL, 11, 1, NOW(), NOW()),
('waybill_sender_province', '', 'waybill', 'string', '发件人省份', '发件人所在省份', NULL, NULL, 12, 1, NOW(), NOW()),
('waybill_sender_city', '', 'waybill', 'string', '发件人城市', '发件人所在城市', NULL, NULL, 13, 1, NOW(), NOW()),
('waybill_sender_district', '', 'waybill', 'string', '发件人区县', '发件人所在区县', NULL, NULL, 14, 1, NOW(), NOW()),
('waybill_sender_address', '', 'waybill', 'string', '详细地址', '发件人详细地址（不含省市区）', NULL, NULL, 15, 1, NOW(), NOW()),
('waybill_lodop_enabled', '1', 'waybill', 'boolean', '启用 Lodop 打印', '启用后优先通过 C-Lodop 打印电子面单 HTML', NULL, NULL, 20, 1, NOW(), NOW()),
('waybill_lodop_http_port', '8000', 'waybill', 'string', 'Lodop HTTP 端口', 'C-Lodop HTTP 服务端口，默认 8000（备用可试 18000）', NULL, NULL, 21, 1, NOW(), NOW()),
('waybill_lodop_https_port', '8443', 'waybill', 'string', 'Lodop HTTPS 端口', 'C-Lodop HTTPS 服务端口，默认 8443', NULL, NULL, 22, 1, NOW(), NOW());

-- Banner 配置
INSERT INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
('banner_list', '[]', 'banner', 'json', '轮播图列表', '首页轮播图配置，JSON数组格式：[{"image":"图片地址","url":"跳转链接","title":"标题"}]', NULL, NULL, 1, 1, NOW(), NOW());

-- 分类页样式配置
INSERT INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
('category_page_style_uniapp', 'style2', 'diy', 'string', '移动端分类页骨架', '移动端分类页骨架(style1/style2/style3)', NULL, NULL, 1, 1, NOW(), NOW());

-- 底部导航配置
INSERT INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
('tabbar_config', '[{"name":"首页","path":"/pages/index/index","icon":"","activeIcon":""},{"name":"分类","path":"/pages/category/index","icon":"","activeIcon":""},{"name":"购物车","path":"/pages/cart/index","icon":"","activeIcon":""},{"name":"我的","path":"/pages/my/index","icon":"","activeIcon":""}]', 'diy', 'json', '底部导航配置', '底部导航配置', NULL, NULL, 2, 1, NOW(), NOW()),
('tabbar_colors', '{"text":"#94a3b8","active":"#4f6bff","bg":"#ffffff"}', 'diy', 'json', '底部导航颜色', '底部导航文字色/高亮色/背景色', NULL, NULL, 3, 1, NOW(), NOW()),
('pc_header_menu', '[{"label":"首页","path":"/"},{"label":"热销榜单","path":"/goods?sort=sales"},{"label":"新品推荐","path":"/goods?sort=newest"},{"label":"好物优选","path":"/goods?is_recommend=1"},{"label":"限时秒杀","path":"/marketing/flash-sale"},{"label":"领券中心","path":"/marketing/coupon"},{"label":"商城资讯","path":"/article"},{"label":"帮助中心","path":"/help"}]', 'diy', 'json', 'PC 头部导航菜单', 'PC 商城头部导航 8 项默认配置', NULL, NULL, 4, 1, NOW(), NOW()),
('pc_footer_config', '{"columns":[{"title":"关于我们","links":[{"label":"关于元点","path":"/about"},{"label":"联系我们","path":"/contact"}]},{"title":"帮助中心","links":[{"label":"用户协议","path":"/article/agreement"},{"label":"隐私政策","path":"/article/privacy"}]},{"title":"友情链接","links":[{"label":"管理后台","path":"/admin/"}]},{"title":"联系方式","links":[{"label":"邮箱：642508814@qq.com","path":""},{"label":"微信：Vince_Dorian","path":""}]}],"copyright":"© {YEAR} 元点Shop. All rights reserved. Powered by yd-admin"}', 'diy', 'json', 'PC 底部菜单 / 版权', 'PC 商城底部 4 列 + 版权文本默认配置', NULL, NULL, 5, 1, NOW(), NOW());

-- 商城风格配置
INSERT INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
('theme_primary_color', '#2979ff', 'shop_style', 'string', '主题颜色', '按钮、链接、Tab高亮等主色调', NULL, NULL, 1, 1, NOW(), NOW()),
('theme_auxiliary_color', '#ff9900', 'shop_style', 'string', '辅助颜色', '价格、促销标签等辅助色', NULL, NULL, 2, 1, NOW(), NOW()),
('theme_text_color', '#333333', 'shop_style', 'string', '文字颜色', '主文字颜色', NULL, NULL, 3, 1, NOW(), NOW()),
('theme_bg_color', '#f5f5f5', 'shop_style', 'string', '背景颜色', '页面底色', NULL, NULL, 4, 1, NOW(), NOW());

-- ============================================================
-- AI 配置默认数据
-- ============================================================
INSERT INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
('ai.default_driver',      'openai', 'ai', 'string', 'AI 默认驱动',          'AI 默认驱动名称（openai/claude/qwen/deepseek/gemini）', NULL, NULL, 1, 1, NOW(), NOW()),
('ai.enabled_drivers',     '[]',     'ai', 'json',   'AI 启用驱动列表',      'AI 启用驱动名单（json 数组），空表示按 api_key 自动判定',     NULL, NULL, 2, 1, NOW(), NOW()),

('ai.openai.api_key',      '', 'ai', 'string', 'OpenAI API Key',  'OpenAI API Key',  NULL, NULL, 10, 1, NOW(), NOW()),
('ai.openai.model',        '', 'ai', 'string', 'OpenAI 模型',     'OpenAI 模型名称', NULL, NULL, 11, 1, NOW(), NOW()),
('ai.openai.base_url',     '', 'ai', 'string', 'OpenAI Base URL', 'OpenAI 接口地址', NULL, NULL, 12, 1, NOW(), NOW()),

('ai.claude.api_key',      '', 'ai', 'string', 'Claude API Key',  'Anthropic Claude API Key',  NULL, NULL, 20, 1, NOW(), NOW()),
('ai.claude.model',        '', 'ai', 'string', 'Claude 模型',     'Claude 模型名称', NULL, NULL, 21, 1, NOW(), NOW()),
('ai.claude.base_url',     '', 'ai', 'string', 'Claude Base URL', 'Claude 接口地址', NULL, NULL, 22, 1, NOW(), NOW()),

('ai.qwen.api_key',        '', 'ai', 'string', '通义千问 API Key',  '阿里云通义千问 API Key',  NULL, NULL, 30, 1, NOW(), NOW()),
('ai.qwen.model',          '', 'ai', 'string', '通义千问 模型',     '通义千问模型名称', NULL, NULL, 31, 1, NOW(), NOW()),
('ai.qwen.base_url',       '', 'ai', 'string', '通义千问 Base URL', '通义千问接口地址', NULL, NULL, 32, 1, NOW(), NOW()),
('ai.qwen.image_model',    'wanx2.1-t2i-turbo', 'ai', 'string', '通义万相图像模型', '通义万相 text2image 模型名', NULL, NULL, 33, 1, NOW(), NOW()),

('ai.deepseek.api_key',    '', 'ai', 'string', 'DeepSeek API Key',  'DeepSeek API Key',  NULL, NULL, 40, 1, NOW(), NOW()),
('ai.deepseek.model',      '', 'ai', 'string', 'DeepSeek 模型',     'DeepSeek 模型名称', NULL, NULL, 41, 1, NOW(), NOW()),
('ai.deepseek.base_url',   '', 'ai', 'string', 'DeepSeek Base URL', 'DeepSeek 接口地址', NULL, NULL, 42, 1, NOW(), NOW()),

('ai.gemini.api_key',      '', 'ai', 'string', 'Gemini API Key',  'Google Gemini API Key',  NULL, NULL, 50, 1, NOW(), NOW()),
('ai.gemini.model',        '', 'ai', 'string', 'Gemini 模型',     'Gemini 模型名称', NULL, NULL, 51, 1, NOW(), NOW()),
('ai.gemini.base_url',     '', 'ai', 'string', 'Gemini Base URL', 'Gemini 接口地址', NULL, NULL, 52, 1, NOW(), NOW()),
('amap.web_api_key',      '',     'amap', 'string', 'AMap Web 服务 API Key',  'AMap REST API Key（geocoding 用，仅后端使用）',     NULL, NULL, 1, 1, NOW(), NOW()),
('amap.js_api_key',       '',     'amap', 'string', 'AMap JS API Key',        'AMap JS API Key（前端地图渲染）',                  NULL, NULL, 2, 1, NOW(), NOW()),
('amap.js_security_code', '',     'amap', 'string', 'AMap JS 安全密钥',       'AMap JS 安全密钥（v2.x 起 JS API 强制）',          NULL, NULL, 3, 1, NOW(), NOW()),
('amap.default_city',     '北京', 'amap', 'string', 'AMap 默认城市',          'Geocoding 默认城市限定（提高识别率）',              NULL, NULL, 4, 1, NOW(), NOW());

-- 退款原因模板
INSERT INTO `order_refund_reasons` (`name`, `sort`, `status`, `created_at`, `updated_at`) VALUES
('不想要了/拍错了',  1, 1, NOW(), NOW()),
('商品质量问题',      2, 1, NOW(), NOW()),
('商品与描述不符',    3, 1, NOW(), NOW()),
('少发/漏发/错发',    4, 1, NOW(), NOW()),
('包装破损/商品损坏', 5, 1, NOW(), NOW()),
('发货太慢',          6, 1, NOW(), NOW()),
('其他原因',          7, 1, NOW(), NOW());

-- 用户标签默认数据
INSERT INTO `user_tags` (`name`, `color`, `sort`, `created_at`, `updated_at`) VALUES
('高消费', '#f56c6c', 1, NOW(), NOW()),
('低活跃', '#909399', 2, NOW(), NOW()),
('退款多', '#e6a23c', 3, NOW(), NOW()),
('新客',   '#67c23a', 4, NOW(), NOW()),
('VIP',    '#409eff', 5, NOW(), NOW());

-- 充值套餐默认数据
INSERT INTO `recharge_packages` (`amount`, `gift_amount`, `gift_points`, `sort`, `status`, `created_at`, `updated_at`) VALUES
(50.00,  0.00,   0,   1, 1, NOW(), NOW()),
(100.00, 10.00,  50,  2, 1, NOW(), NOW()),
(200.00, 30.00,  100, 3, 1, NOW(), NOW()),
(500.00, 100.00, 300, 4, 1, NOW(), NOW());

-- ============================================================
-- 物流公司默认数据
-- ============================================================
INSERT INTO `express_companies` (`id`, `name`, `code`, `logo`, `sort`, `status`, `created_at`, `updated_at`) VALUES
(1, '顺丰速运', 'SF', '', 1, 1, NOW(), NOW()),
(2, '中通快递', 'ZTO', '', 2, 1, NOW(), NOW()),
(3, '圆通速递', 'YTO', '', 3, 1, NOW(), NOW()),
(4, '申通快递', 'STO', '', 4, 1, NOW(), NOW()),
(5, '韵达速递', 'YD', '', 5, 1, NOW(), NOW()),
(6, '百世快递', 'HTKY', '', 6, 1, NOW(), NOW()),
(7, '极兔速递', 'JTSD', '', 7, 1, NOW(), NOW()),
(8, '京东物流', 'JD', '', 8, 1, NOW(), NOW()),
(9, '邮政EMS', 'EMS', '', 9, 1, NOW(), NOW()),
(10, '德邦快递', 'DBL', '', 10, 1, NOW(), NOW());

-- ============================================================
-- 文件分类默认数据
-- ============================================================
INSERT INTO `file_categories` (`id`, `parent_id`, `name`, `sort`, `created_at`, `updated_at`) VALUES
(1, 0, '系统图片', 1, NOW(), NOW()),
(2, 0, '商品图片', 2, NOW(), NOW()),
(3, 0, '品牌图片', 3, NOW(), NOW()),
(4, 0, '文章图片', 4, NOW(), NOW());

-- ============================================================
-- 装修页面默认数据
-- ============================================================
INSERT INTO `diy_pages` (`id`, `page_type`, `platform`, `title`, `components`, `is_published`, `is_default`, `sort`, `status`, `created_at`, `updated_at`) VALUES
(1, 'home', 'uniapp', '移动端首页', '[{"id":"comp_1","type":"search-bar","props":{"placeholder":"搜索商品","border_radius":20,"bg_color":"#f5f5f5"}},{"id":"comp_2","type":"banner","props":{"items":[{"image":"/storage/diy-defaults/home/banner/01.jpg","url":"","title":""},{"image":"/storage/diy-defaults/home/banner/02.jpg","url":"","title":""},{"image":"/storage/diy-defaults/home/banner/03.jpg","url":"","title":""}],"autoplay":true,"interval":3000,"height":360}},{"id":"comp_3","type":"category-nav","props":{"style":"icon-grid","rows":2,"columns":5,"items":[{"icon":"/storage/diy-defaults/home/category/01.png","title":"限时秒杀","link":"/modules/marketing/pages/flash-sale"},{"icon":"/storage/diy-defaults/home/category/02.png","title":"拼团活动","link":"/modules/marketing/pages/group-buy"},{"icon":"/storage/diy-defaults/home/category/03.png","title":"商品分类","link":"/modules/goods/pages/category"},{"icon":"/storage/diy-defaults/home/category/04.png","title":"签到","link":"/modules/user/pages/sign"},{"icon":"/storage/diy-defaults/home/category/05.png","title":"浏览记录","link":"/modules/user/pages/history"},{"icon":"/storage/diy-defaults/home/category/06.png","title":"抽奖活动","link":"/modules/marketing/pages/lottery"},{"icon":"/storage/diy-defaults/home/category/07.png","title":"积分商城","link":"/modules/marketing/pages/points-mall"},{"icon":"/storage/diy-defaults/home/category/08.png","title":"分销中心","link":"/modules/distribution/pages/index"},{"icon":"/storage/diy-defaults/home/category/09.png","title":"我的订单","link":"/modules/order/pages/list"},{"icon":"/storage/diy-defaults/home/category/10.png","title":"优惠券","link":"/modules/marketing/pages/coupon"}]}},{"id":"comp_4","type":"coupon-list","props":{"coupon_ids":[],"style":"horizontal","themeColor":"#ff4d4f","btnText":"立即领取","showCondition":true}},{"id":"comp_5","type":"seckill","props":{"activity_id":null,"limit":4,"showCountdown":true,"countdownStyle":"standard","showProgress":true,"themeColor":"#ff4d4f"}},{"id":"comp_6","type":"image-cube","props":{"rows":2,"cols":2,"gap":8,"borderRadius":4,"items":[{"image":"/storage/diy-defaults/home/cube/01.png","link":"","rowStart":1,"colStart":1,"rowSpan":1,"colSpan":1},{"image":"/storage/diy-defaults/home/cube/02.png","link":"","rowStart":1,"colStart":2,"rowSpan":1,"colSpan":1},{"image":"/storage/diy-defaults/home/cube/03.png","link":"","rowStart":2,"colStart":1,"rowSpan":1,"colSpan":1},{"image":"/storage/diy-defaults/home/cube/04.png","link":"","rowStart":2,"colStart":2,"rowSpan":1,"colSpan":1}]}},{"id":"comp_7","type":"title-bar","props":{"title":"热销推荐","subtitle":"","align":"left","more_url":"/pages/goods/list","more_text":"查看更多"}},{"id":"comp_8","type":"goods-grid","props":{"title":"","source":"tag","goods_ids":[],"category_id":null,"tag":"hot","limit":10,"columns":2}}]', 1, 1, 0, 1, NOW(), NOW()),
(2, 'home', 'pc', 'PC端首页', '[{"id":"comp_1","type":"banner","props":{"items":[{"image":"/storage/diy-defaults/home/pc/banner/01.jpg","url":"","title":""}],"autoplay":true,"interval":3000,"height":460}},{"id":"comp_2","type":"image-cube","props":{"rows":1,"cols":5,"gap":8,"borderRadius":4,"marginTop":16,"marginBottom":16,"items":[{"image":"/storage/diy-defaults/home/pc/cube/01.png","link":"","rowStart":1,"colStart":1,"rowSpan":1,"colSpan":1},{"image":"/storage/diy-defaults/home/pc/cube/02.png","link":"","rowStart":1,"colStart":2,"rowSpan":1,"colSpan":1},{"image":"/storage/diy-defaults/home/pc/cube/03.png","link":"","rowStart":1,"colStart":3,"rowSpan":1,"colSpan":1},{"image":"/storage/diy-defaults/home/pc/cube/04.png","link":"","rowStart":1,"colStart":4,"rowSpan":1,"colSpan":1},{"image":"/storage/diy-defaults/home/pc/cube/05.png","link":"","rowStart":1,"colStart":5,"rowSpan":1,"colSpan":1}]}},{"id":"comp_3","type":"title-bar","props":{"title":"热销推荐","subtitle":"精选好物","align":"center","more_url":"/goods","more_text":"查看更多"}},{"id":"comp_4","type":"goods-grid","props":{"title":"","source":"tag","goods_ids":[],"category_id":null,"tag":"hot","limit":8,"columns":4}},{"id":"comp_5","type":"title-bar","props":{"title":"新品上架","subtitle":"","align":"center","more_url":"/goods?sort=new","more_text":"查看更多"}},{"id":"comp_6","type":"goods-grid","props":{"title":"","source":"tag","goods_ids":[],"category_id":null,"tag":"new","limit":8,"columns":4}}]', 1, 1, 0, 1, NOW(), NOW()),
(3, 'category', 'uniapp', '移动端分类页', '[]', 0, 1, 0, 1, NOW(), NOW()),
(4, 'category', 'pc', 'PC端分类页', '[]', 0, 1, 0, 1, NOW(), NOW());

-- ============================================================
-- 装修主题默认数据
-- ============================================================
-- diy_themes 暂不预置模板，由后期设计后单独入库

-- sign.* 配置种子已迁入 plugins/sign/database/install.sql，由插件安装写入

INSERT IGNORE INTO `permissions`
  (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
VALUES
  (498, 'member.tag.refresh',                      '刷新标签',     '用户管理', '按规则立即重算标签覆盖', 'admin', 1, 498, NOW(), NOW());

-- v1.5.8 新人礼包多礼包持久化
-- 删除老 4 条 new_user_gift.* seed（如有）
DELETE FROM `system_configs` WHERE config_key IN (
  'new_user_gift.enabled',
  'new_user_gift.points',
  'new_user_gift.balance',
  'new_user_gift.coupon_ids'
);

-- ============================================================
-- v2.4.0 自提门店配置
-- ============================================================
INSERT IGNORE INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
  ('pickup_timeout_days', '7',        'pickup', 'number', '自提超时天数', '下单后多少天未核销自动置超时', NULL, NULL, 100, 1, NOW(), NOW()),
  ('default_city_lng',   '116.4074', 'pickup', 'string', '默认城市经度', '用户拒绝定位时降级使用',       NULL, NULL, 200, 1, NOW(), NOW()),
  ('default_city_lat',   '39.9042',  'pickup', 'string', '默认城市纬度', '用户拒绝定位时降级使用',       NULL, NULL, 300, 1, NOW(), NOW());

-- 订单会员权益快照发布边界。升级安装的增量 SQL 必须写成实际部署时刻；
-- 补偿命令对边界前订单只做保守快照导入，不重复发放旧权益。
INSERT IGNORE INTO `system_configs`
  (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
VALUES
  ('member_reward.snapshot_started_at', '2026-07-14 00:00:00', 'member_reward', 'string', '订单会员权益快照启用时间', '边界前历史订单补偿时只导入快照、不重复发放', NULL, NULL, 1, 1, NOW(), NOW());

-- 自动支付消费者补偿只覆盖发布边界后的支付；手工 payment:resync 仍可全量排障。
INSERT IGNORE INTO `system_configs`
  (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
VALUES
  ('payment.reconcile_from', '2026-07-14 00:00:00', 'payment', 'string', '支付消费者补偿起始时间', '自动补偿仅重放该时间后本地已支付记录，避免重新广播全部历史支付', NULL, NULL, 1, 1, NOW(), NOW());

-- ============================================================
-- 会员等级演示数据（普通/白银/黄金/铂金/钻石）
-- ============================================================
INSERT INTO `member_levels`
  (`id`, `name`, `icon`, `growth_min`, `discount`, `points_rate`, `free_freight`, `privileges`, `sort`, `status`, `created_at`, `updated_at`)
VALUES
  (1, '普通会员', '', 0,     1.00, 1.0, 0, NULL, 1, 1, NOW(), NOW()),
  (2, '白银会员', '', 500,   0.98, 1.1, 0, NULL, 2, 1, NOW(), NOW()),
  (3, '黄金会员', '', 2000,  0.95, 1.2, 0, NULL, 3, 1, NOW(), NOW()),
  (4, '铂金会员', '', 8000,  0.92, 1.5, 0, NULL, 4, 1, NOW(), NOW()),
  (5, '钻石会员', '', 30000, 0.88, 2.0, 1, NULL, 5, 1, NOW(), NOW());

-- ============================================================
-- 用户分组演示数据（与前端「分组模板」一致：近30天活跃 / 新注册(7天内) / 沉睡用户 / 高积分用户）
-- ============================================================
INSERT INTO `user_groups`
  (`id`, `name`, `description`, `rules`, `auto_update`, `user_count`, `sort`, `status`, `created_at`, `updated_at`)
VALUES
  (1, '近 30 天活跃', '最近 30 天有登录的会员',
   JSON_OBJECT(
     'logic', 'AND',
     'conditions', JSON_ARRAY(JSON_OBJECT('field','last_login_time','op','>=','value','30_days_ago','exclude', FALSE))
   ),
   1, 0, 1, 1, NOW(), NOW()),
  (2, '新注册（7 天内）', '近 7 天注册的会员',
   JSON_OBJECT(
     'logic', 'AND',
     'conditions', JSON_ARRAY(JSON_OBJECT('field','created_at','op','>=','value','7_days_ago','exclude', FALSE))
   ),
   1, 0, 2, 1, NOW(), NOW()),
  (3, '沉睡用户', '90 天未登录的会员',
   JSON_OBJECT(
     'logic', 'AND',
     'conditions', JSON_ARRAY(JSON_OBJECT('field','last_login_time','op','<','value','90_days_ago','exclude', FALSE))
   ),
   1, 0, 3, 1, NOW(), NOW()),
  (4, '高积分用户', '积分 ≥ 1000 的会员',
   JSON_OBJECT(
     'logic', 'AND',
     'conditions', JSON_ARRAY(JSON_OBJECT('field','points','op','>=','value',1000,'exclude', FALSE))
   ),
   1, 0, 4, 1, NOW(), NOW());

-- ============================================================
-- 计量单位分组演示数据（6 组：计数/重量/容量/长度/面积/包装）
-- ============================================================
INSERT INTO `goods_unit_groups`
  (`id`, `code`, `name`, `tone`, `sort`, `status`, `created_at`, `updated_at`)
VALUES
  (1, 'count',  '计数', 'blue',   1, 1, NOW(), NOW()),
  (2, 'weight', '重量', 'amber',  2, 1, NOW(), NOW()),
  (3, 'volume', '容量', 'cyan',   3, 1, NOW(), NOW()),
  (4, 'length', '长度', 'teal',   4, 1, NOW(), NOW()),
  (5, 'area',   '面积', 'violet', 5, 1, NOW(), NOW()),
  (6, 'pack',   '包装', 'rose',   6, 1, NOW(), NOW());

-- ============================================================
-- 计量单位演示数据（含 group_id / is_base / ratio，覆盖原 demo-shop.sql 中的 30 条并扩充 8 条）
-- ratio 含义：1 当前单位 = X 基准单位（每组基准单位 is_base=1, ratio=1）
-- ============================================================
INSERT INTO `goods_units`
  (`id`, `code`, `name`, `name_en`, `group_id`, `decimal_places`, `is_base`, `ratio`, `sort`, `status`, `created_at`, `updated_at`)
VALUES
  -- 计数（基准：件）
  (1,  'jian',   '件',     'piece',     1, 0, 1, 1.000000,   1,  1, NOW(), NOW()),
  (2,  'tao',    '套',     'set',       1, 0, 0, 1.000000,   2,  1, NOW(), NOW()),
  (3,  'tiao',   '条',     'strip',     1, 0, 0, 1.000000,   3,  1, NOW(), NOW()),
  (4,  'shuang', '双',     'pair',      1, 0, 0, 1.000000,   4,  1, NOW(), NOW()),
  (5,  'duan',   '段',     'segment',   1, 0, 0, 1.000000,   5,  1, NOW(), NOW()),
  (11, 'pian',   '片',     'piece',     1, 0, 0, 1.000000,  11,  1, NOW(), NOW()),
  (12, 'zhi',    '支',     'piece',     1, 0, 0, 1.000000,  12,  1, NOW(), NOW()),
  (14, 'kuai',   '块',     'piece',     1, 0, 0, 1.000000,  14,  1, NOW(), NOW()),
  (15, 'ge',     '个',     'piece',     1, 0, 0, 1.000000,  15,  1, NOW(), NOW()),
  (16, 'tai',    '台',     'unit',      1, 0, 0, 1.000000,  16,  1, NOW(), NOW()),
  (17, 'bu',     '部',     'unit',      1, 0, 0, 1.000000,  17,  1, NOW(), NOW()),
  (18, 'zhang',  '张',     'sheet',     1, 0, 0, 1.000000,  18,  1, NOW(), NOW()),
  (19, 'chuan',  '串',     'string',    1, 0, 0, 1.000000,  19,  1, NOW(), NOW()),
  (20, 'fu',     '副',     'pair',      1, 0, 0, 1.000000,  20,  1, NOW(), NOW()),
  (21, 'ba',     '把',     'handful',   1, 0, 0, 1.000000,  21,  1, NOW(), NOW()),
  (22, 'gen',    '根',     'piece',     1, 0, 0, 1.000000,  22,  1, NOW(), NOW()),
  -- 重量（基准：克）
  (24, 'g',      '克',     'gram',      2, 2, 1, 1.000000,  24,  1, NOW(), NOW()),
  (25, 'kg',     '千克',   'kilogram',  2, 2, 0, 1000.000000, 25,  1, NOW(), NOW()),
  (26, 'jin',    '斤',     'jin',       2, 2, 0, 500.000000,  26,  1, NOW(), NOW()),
  (32, 'ton',    '吨',     'ton',       2, 4, 0, 1000000.000000, 32, 1, NOW(), NOW()),
  -- 容量（基准：毫升）
  (28, 'ml',     '毫升',   'milliliter',3, 2, 1, 1.000000,  28,  1, NOW(), NOW()),
  (27, 'l',      '升',     'liter',     3, 3, 0, 1000.000000, 27,  1, NOW(), NOW()),
  -- 长度（基准：厘米）
  (30, 'cm',     '厘米',   'centimeter',4, 2, 1, 1.000000,  30,  1, NOW(), NOW()),
  (29, 'm',      '米',     'meter',     4, 2, 0, 100.000000,  29,  1, NOW(), NOW()),
  (31, 'mm',     '毫米',   'millimeter',4, 2, 0, 0.100000,   31,  1, NOW(), NOW()),
  (33, 'cun',    '寸',     'cun',       4, 2, 0, 3.333333,   33,  1, NOW(), NOW()),
  (34, 'chi',    '尺',     'chi',       4, 2, 0, 33.333333,  34,  1, NOW(), NOW()),
  -- 面积（基准：平方米）
  (35, 'sqm',    '平方米', 'square_meter',     5, 2, 1, 1.000000,    35, 1, NOW(), NOW()),
  (36, 'sqcm',   '平方厘米','square_centimeter',5, 2, 0, 0.000100,    36, 1, NOW(), NOW()),
  (37, 'sqdm',   '平方分米','square_decimeter', 5, 2, 0, 0.010000,    37, 1, NOW(), NOW()),
  (38, 'mu',     '亩',     'mu',                5, 2, 0, 666.666667,  38, 1, NOW(), NOW()),
  -- 包装（基准：盒）
  (6,  'he',     '盒',     'box',       6, 0, 1, 1.000000,   6,  1, NOW(), NOW()),
  (7,  'dai',    '袋',     'bag',       6, 0, 0, 1.000000,   7,  1, NOW(), NOW()),
  (8,  'ping',   '瓶',     'bottle',    6, 0, 0, 1.000000,   8,  1, NOW(), NOW()),
  (9,  'guan',   '罐',     'can',       6, 0, 0, 1.000000,   9,  1, NOW(), NOW()),
  (10, 'bao',    '包',     'pack',      6, 0, 0, 1.000000,  10,  1, NOW(), NOW()),
  (13, 'tube',   '管',     'tube',      6, 0, 0, 1.000000,  13,  1, NOW(), NOW()),
  (23, 'juan',   '卷',     'roll',      6, 0, 0, 1.000000,  23,  1, NOW(), NOW());

-- ============================================================
-- 帮助中心默认分类
-- ============================================================
INSERT INTO `help_categories` (`id`, `name`, `icon`, `sort`, `status`, `created_at`, `updated_at`) VALUES
(1, '购物问题', '', 1, 1, NOW(), NOW()),
(2, '退换货',   '', 2, 1, NOW(), NOW()),
(3, '账号问题', '', 3, 1, NOW(), NOW()),
(4, '支付问题', '', 4, 1, NOW(), NOW()),
(5, '物流配送', '', 5, 1, NOW(), NOW());

-- ============================================================
-- 规格模板演示数据（8 个常用商品模板）
-- ============================================================
INSERT INTO `goods_spec_templates`
  (`id`, `name`, `description`, `items`, `sort`, `status`, `created_at`, `updated_at`)
VALUES
  (1, '服装尺码', '常用上下装尺码',
   '[{"name":"尺码","values":["S","M","L","XL","XXL","XXXL"]}]',
   1, 1, NOW(), NOW()),
  (2, '颜色', '常用颜色集合',
   '[{"name":"颜色","values":["红色","黑色","白色","灰色","蓝色","粉色","黄色","绿色"]}]',
   2, 1, NOW(), NOW()),
  (3, '鞋码', '中码鞋码 36-44',
   '[{"name":"鞋码","values":["36","37","38","39","40","41","42","43","44"]}]',
   3, 1, NOW(), NOW()),
  (4, '手机颜色+内存', '中端手机常用 SKU 维度',
   '[{"name":"颜色","values":["黑色","白色","蓝色","金色"]},{"name":"内存","values":["8GB+128GB","8GB+256GB","12GB+256GB","12GB+512GB"]}]',
   4, 1, NOW(), NOW()),
  (5, '手机颜色+存储+版本', '旗舰手机三维 SKU',
   '[{"name":"颜色","values":["原色钛金属","蓝色钛金属","白色钛金属","黑色钛金属"]},{"name":"存储","values":["256GB","512GB","1TB"]},{"name":"版本","values":["国行","美版","日版"]}]',
   5, 1, NOW(), NOW()),
  (6, '饮料规格', '容量 + 口味',
   '[{"name":"容量","values":["330ml","500ml","1L","2L"]},{"name":"口味","values":["原味","无糖","柠檬","蜜桃"]}]',
   6, 1, NOW(), NOW()),
  (7, '化妆品规格', '容量 + 套装',
   '[{"name":"规格","values":["30ml","50ml","100ml","200ml"]},{"name":"套装","values":["单瓶","套装两件","套装三件"]}]',
   7, 1, NOW(), NOW()),
  (8, '生鲜重量', '常用重量分档',
   '[{"name":"重量","values":["500g","1kg","2kg","5kg","10kg"]}]',
   8, 1, NOW(), NOW());

-- ============================================================
-- 提现规则默认配置（费率为百分数：0.6 = 0.6%）
-- ============================================================
INSERT IGNORE INTO `system_configs`
  (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
VALUES
  ('withdrawal_min_amount',  '50',                 'withdrawal', 'number', '提现起点',   '单笔提现最低金额（元）',          NULL, NULL, 1, 1, NOW(), NOW()),
  ('withdrawal_max_amount',  '50000',              'withdrawal', 'number', '单笔上限',   '单笔提现最高金额（元）',          NULL, NULL, 2, 1, NOW(), NOW()),
  ('withdrawal_daily_count', '3',                  'withdrawal', 'number', '每日次数',   '每个用户每日最多申请次数，0 表示不限', NULL, NULL, 3, 1, NOW(), NOW()),
  ('withdrawal_fee_rate',    '0.6',                'withdrawal', 'number', '手续费率',   '百分数，0.6 表示 0.6%',              NULL, NULL, 4, 1, NOW(), NOW()),
  ('withdrawal_fee_min',     '1',                  'withdrawal', 'number', '最低手续费', '启用费率时的最低手续费（元）',      NULL, NULL, 5, 1, NOW(), NOW()),
  ('withdrawal_fee_max',     '25',                 'withdrawal', 'number', '最高手续费', '启用费率时的最高手续费（元），0 不限', NULL, NULL, 6, 1, NOW(), NOW()),
  ('withdrawal_channels',    'wechat,alipay,bank', 'withdrawal', 'string', '到账方式',   '允许的提现渠道，英文逗号分隔',          NULL, NULL, 7, 1, NOW(), NOW());

INSERT IGNORE INTO `system_configs`
  (`config_key`,`config_value`,`config_group`,`config_type`,`config_name`,`config_desc`,`sort_order`,`status`,`created_at`,`updated_at`)
VALUES
  ('payment.provider_reconcile_cursor','0','payment','number','支付渠道对账游标','近期渠道对账最后处理的支付单 ID',90,1,NOW(),NOW()),
  ('payment.provider_reconcile_from',DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 DAY),'%Y-%m-%d %H:%i:%s'),'payment','string','支付渠道对账起始时间','仅扫描创建时间不早于该边界的支付单',91,1,NOW(),NOW()),
  ('payment.provider_reconcile_safe_delay_seconds','300','payment','number','支付渠道对账安全延迟','仅扫描更新时间早于当前时间减该秒数的支付单',92,1,NOW(),NOW());
