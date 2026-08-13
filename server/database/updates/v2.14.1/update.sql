-- v2.14.1: 菜单「历史权益复核」更名为「权益复核」

UPDATE `menus`
SET `title` = '权益复核', `updated_at` = NOW()
WHERE `id` = 978
  AND `path` = '/member/reward-review'
  AND `title` <> '权益复核';

UPDATE `permissions`
SET `title` = '权益复核',
    `description` = '查看订单与充值会员权益证据',
    `updated_at` = NOW()
WHERE `name` = 'member.reward_review.list'
  AND (`title` <> '权益复核' OR COALESCE(`description`, '') <> '查看订单与充值会员权益证据');
