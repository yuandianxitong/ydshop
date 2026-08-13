-- v2.18.0：分销 / AI 从核心抽出为付费插件。去掉核心种子菜单与权限，业务表保留供 plugin:install 认领。

-- 菜单
DELETE rm FROM `role_menus` rm
INNER JOIN `menus` m ON m.id = rm.menu_id
WHERE m.`name` IN (
    'Ai', 'AiConfig', 'AiTask', 'AiPrompt', 'AiAnalysis', 'AiText',
    'Distribution', 'Distributor', 'DistributionCommission', 'DistributionLevel', 'DistributionWithdrawal'
  )
  OR m.`permission` LIKE 'ai.%'
  OR m.`permission` IN ('ai', 'distribution')
  OR m.`permission` LIKE 'distribution.%';

DELETE FROM `menus`
WHERE `name` IN (
    'Ai', 'AiConfig', 'AiTask', 'AiPrompt', 'AiAnalysis', 'AiText',
    'Distribution', 'Distributor', 'DistributionCommission', 'DistributionLevel', 'DistributionWithdrawal'
  )
  OR `permission` LIKE 'ai.%'
  OR `permission` IN ('ai', 'distribution')
  OR `permission` LIKE 'distribution.%';

-- 权限
DELETE rp FROM `role_permissions` rp
INNER JOIN `permissions` p ON p.id = rp.permission_id
WHERE p.`name` = 'ai' OR p.`name` LIKE 'ai.%' OR p.`name` LIKE 'distribution.%';

DELETE FROM `permissions`
WHERE `name` = 'ai' OR `name` LIKE 'ai.%' OR `name` LIKE 'distribution.%';

-- 定时任务：插件未安装时不要再调度
DELETE FROM `cron_jobs`
WHERE `command` IN ('distribution:settle', 'distribution:reconcile-refunds');
