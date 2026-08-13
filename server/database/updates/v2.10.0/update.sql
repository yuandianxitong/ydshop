-- v2.10.0: AI 助手（商品字段内嵌 + 图像生成 + 删除补货推荐）
-- 幂等：所有变更可重复执行不报错
--
-- 实际列名说明（与计划文档差异，已按 init.sql 真实表结构调整）：
--   permissions:      (id, name, title, `group`, description, guard_name, status, sort, ...)
--                     ※ 计划文档误写为 (key, name, group_name, remark, ...)
--   menus:            (id, parent_id, type, title, name, path, component, redirect, icon, permission, ...)
--                     ※ 计划文档误写为 (name, component_name, permission_key)；
--                     实际「中文显示名」用 title，「路由 name」用 name，「权限标识」用 permission
--   roles:            super_admin 角色用 name='super_admin' 而非 code
--   role_permissions: (role_id, permission_id, created_at, updated_at)

-- 1) 删 restock 历史任务
DELETE FROM `ai_tasks` WHERE `type`='restock';

-- 2) ai_tasks.type enum：扩展 → 迁移 → 精简
ALTER TABLE `ai_tasks`
  MODIFY COLUMN `type` ENUM('analysis','restock','description','text','image') DEFAULT NULL COMMENT '任务类型';
UPDATE `ai_tasks` SET `type`='text' WHERE `type`='description';
ALTER TABLE `ai_tasks`
  MODIFY COLUMN `type` ENUM('analysis','text','image') DEFAULT NULL COMMENT '任务类型';

-- 3) ai_prompt_templates 加 scene 列与索引（双重幂等：列与索引分别检查）
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ai_prompt_templates' AND COLUMN_NAME='scene');
SET @idx := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ai_prompt_templates' AND INDEX_NAME='idx_type_scene');
SET @sql := IF(@col=0,
  'ALTER TABLE `ai_prompt_templates` ADD COLUMN `scene` varchar(20) DEFAULT NULL COMMENT ''细分场景'' AFTER `type`, ADD INDEX `idx_type_scene` (`type`,`scene`)',
  IF(@idx=0,
    'ALTER TABLE `ai_prompt_templates` ADD INDEX `idx_type_scene` (`type`,`scene`)',
    'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) restock 默认模板删除
DELETE FROM `ai_prompt_templates` WHERE `type`='restock' AND `is_default`=1;

-- 5) description 默认模板迁到 text/description
UPDATE `ai_prompt_templates`
  SET `type`='text', `scene`='description', `name`='商品描述生成'
  WHERE `type`='description' AND `is_default`=1;

-- 6) 插入 4 条新默认模板（幂等）
INSERT INTO `ai_prompt_templates` (`type`, `scene`, `name`, `is_default`, `status`, `system_prompt`, `user_prompt_template`, `variables`, `created_at`, `updated_at`)
SELECT 'text','title','商品标题生成',1,1,
  '你是一位资深电商运营。请严格按 JSON 返回 {"titles":["...","...","..."]}，3 个候选。',
  '名称参考：{{name}}\n分类：{{category}}\n品牌：{{brand}}\n规格：{{specs}}\n属性：{{attributes}}\n当前文案：{{current_text}}\n风格：{{style}}',
  '{"name":"参考名称","category":"分类","brand":"品牌","specs":"规格","attributes":"属性","current_text":"当前文案","style":"风格"}',
  NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `ai_prompt_templates` WHERE `type`='text' AND `scene`='title' AND `is_default`=1);

INSERT INTO `ai_prompt_templates` (`type`, `scene`, `name`, `is_default`, `status`, `system_prompt`, `user_prompt_template`, `variables`, `created_at`, `updated_at`)
SELECT 'text','detail','商品详情生成',1,1,
  '你是一位专业电商详情页文案。输出 HTML 片段。风格：{{style}}。',
  '请为以下商品撰写详情页内容（HTML）：\n名称：{{name}}\n分类：{{category}}\n品牌：{{brand}}\n规格：{{specs}}\n属性：{{attributes}}\n当前文案：{{current_text}}',
  '{"name":"名称","category":"分类","brand":"品牌","specs":"规格","attributes":"属性","current_text":"当前文案","style":"风格"}',
  NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `ai_prompt_templates` WHERE `type`='text' AND `scene`='detail' AND `is_default`=1);

INSERT INTO `ai_prompt_templates` (`type`, `scene`, `name`, `is_default`, `status`, `system_prompt`, `user_prompt_template`, `variables`, `created_at`, `updated_at`)
SELECT 'image','cover','商品主图 Prompt',1,1,
  '',
  '{{name}}, {{brand}} 品牌, {{category}}, 商品摄影, 纯白背景, 45 度俯视, 高清, 4K{{prompt_extra}}',
  '{"name":"名称","brand":"品牌","category":"分类","prompt_extra":"额外提示词"}',
  NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `ai_prompt_templates` WHERE `type`='image' AND `scene`='cover' AND `is_default`=1);

INSERT INTO `ai_prompt_templates` (`type`, `scene`, `name`, `is_default`, `status`, `system_prompt`, `user_prompt_template`, `variables`, `created_at`, `updated_at`)
SELECT 'image','gallery','商品场景图 Prompt',1,1,
  '',
  '{{name}}, {{brand}} 品牌, {{category}}, 生活场景, 自然光, 电商场景图{{prompt_extra}}',
  '{"name":"名称","brand":"品牌","category":"分类","prompt_extra":"额外提示词"}',
  NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `ai_prompt_templates` WHERE `type`='image' AND `scene`='gallery' AND `is_default`=1);

-- 7) 删菜单 1150/1151（补货推荐及其子按钮） + 改 1160/1161（文案生成 → 文案批量）
-- 先清理子表 role_menus，再删父表 menus，避免外键/孤儿
DELETE FROM `role_menus` WHERE `menu_id` IN (1150, 1151);
DELETE FROM `menus` WHERE `id` IN (1150, 1151);
UPDATE `menus`
  SET `title`='文案批量', `name`='AiText', `path`='/ai/text', `component`='ai/text/index', `permission`='ai.text'
  WHERE `id`=1160;
UPDATE `menus`
  SET `permission`='ai.text.run'
  WHERE `id`=1161;

-- 8) 权限：删 640、改 650、加 660
DELETE FROM `permissions` WHERE `id`=640;
UPDATE `permissions` SET `name`='ai.text.run', `title`='文案生成', `description`='执行AI文案生成' WHERE `id`=650;
INSERT INTO `permissions` (`id`, `name`, `title`, `group`, `description`, `guard_name`, `status`, `sort`, `created_at`, `updated_at`)
SELECT 660, 'ai.image.run', '图像生成', 'AI助手', '执行AI图像生成', 'admin', 1, 660, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `id`=660);

-- 9) super_admin 角色补 660 授权
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`)
SELECT r.`id`, 660, NOW(), NOW() FROM `roles` r
WHERE r.`name`='super_admin' AND NOT EXISTS (
  SELECT 1 FROM `role_permissions` rp WHERE rp.`role_id`=r.`id` AND rp.`permission_id`=660
);

-- 10) ai.qwen.image_model 系统配置
INSERT INTO `system_configs` (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
SELECT 'ai.qwen.image_model', 'wanx2.1-t2i-turbo', 'ai', 'string', '通义万相图像模型', '通义万相 text2image 模型名', NULL, NULL, 33, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `system_configs` WHERE `config_key`='ai.qwen.image_model');
