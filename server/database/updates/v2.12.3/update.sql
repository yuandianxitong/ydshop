-- v2.12.3 账务与订单一致性升级
-- 基线：v2.12.0（v2.12.1 / v2.12.2 仍需按版本顺序先执行）。
--
-- 重要：先完成所有历史唯一性预检。下列冲突不能靠删除一行
-- “猜”出经济事实，所以必须在任何 ALTER TABLE 之前停止升级：
--   1. 一个业务订单存在多条旧 order_payments；
--   2. 真实 payment_order_id 重复或与业务单身份/金额/渠道冲突；
--   3. 充值单的真实支付单绑定重复或冲突；
--   4. 历史成功退款证据缺失、重复、累计超额或无法解释旧支付字段；
--   5. 一个订单存在多条同城配送单。
--   6. 旧支付单仍处于 pending（升级前没有收款主体/过期快照，需人工核对）。
--   7. 旧支付商户单号可同时映射到多个商城业务订单。
-- 这保证检查失败时数据库仍处于完整的 v2.12.0 结构，不会留下半升级状态。

DROP PROCEDURE IF EXISTS `assert_v2123_upgrade_safe`;
DELIMITER $$
CREATE PROCEDURE `assert_v2123_upgrade_safe`()
BEGIN
  DECLARE `conflict_count` bigint unsigned DEFAULT 0;

  SELECT COUNT(*) INTO `conflict_count`
  FROM `payment_orders`
  WHERE BINARY `status` IN ('pending', 'creating', 'closing');
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: legacy unresolved payment orders require manual account audit';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM `menus`
  WHERE (`id` = 978 AND BINARY COALESCE(`path`, '') <> '/member/reward-review')
     OR (`id` = 979 AND BINARY COALESCE(`permission`, '') <> 'member.reward_review.resolve');
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: member reward review menu ids are occupied';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM `menus`
  WHERE (`id` = 951 AND BINARY COALESCE(`permission`, '') <> 'distribution.commission.settle')
     OR (`id` = 952 AND BINARY COALESCE(`permission`, '') <> 'distribution.commission.review');
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: distribution commission review menu ids are occupied';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM (
    SELECT `order_id`
    FROM `order_payments`
    WHERE `order_id` IS NOT NULL AND `order_id` > 0
    GROUP BY `order_id`
    HAVING COUNT(*) > 1
  ) AS `duplicate_business_payments`;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: an order has multiple legacy order_payments';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM (
    SELECT `payment_order_id`
    FROM `order_payments`
    WHERE `payment_order_id` > 0
    GROUP BY `payment_order_id`
    HAVING COUNT(*) > 1
  ) AS `duplicate_payment_links`;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: duplicate order payment_order_id';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM `order_payments` AS `business_payment`
  LEFT JOIN `order_orders` AS `business_order`
    ON `business_order`.`id` = `business_payment`.`order_id`
   AND `business_order`.`deleted_at` IS NULL
  LEFT JOIN `payment_orders` AS `payment`
    ON `payment`.`id` = `business_payment`.`payment_order_id`
   AND `payment`.`deleted_at` IS NULL
  WHERE `business_payment`.`payment_order_id` > 0
    AND (
      `business_order`.`id` IS NULL
      OR `payment`.`id` IS NULL
      OR COALESCE(`business_order`.`order_no`, '') = ''
      OR BINARY COALESCE(`payment`.`biz_type`, '') NOT IN ('', 'order')
      OR COALESCE(`business_payment`.`status`, -1) NOT IN (0, 1, 2)
      OR (
        `business_payment`.`status` = 1
        AND BINARY `payment`.`status` NOT IN ('paid', 'refunded')
      )
      OR (
        `business_payment`.`status` = 2
        AND BINARY `payment`.`status` <> 'refunded'
      )
      OR (
        BINARY `payment`.`order_no` <> BINARY `business_order`.`order_no`
        AND BINARY `payment`.`order_no` <> BINARY CONCAT('ORD_', `business_order`.`order_no`)
      )
      OR (
        ABS(COALESCE(`business_payment`.`amount`, 0) - COALESCE(`payment`.`total_amount`, 0)) >= 0.005
        AND NOT (
          COALESCE(`business_payment`.`amount`, 0) = 0
          AND ABS(COALESCE(`business_order`.`pay_amount`, 0) - COALESCE(`payment`.`total_amount`, 0)) < 0.005
        )
      )
      OR (
        COALESCE(`payment`.`user_id`, 0) > 0
        AND COALESCE(`business_order`.`user_id`, 0) > 0
        AND `payment`.`user_id` <> `business_order`.`user_id`
      )
      OR (
        COALESCE(`business_payment`.`pay_type`, '') <> ''
        AND COALESCE(`payment`.`channel`, '') <> ''
        AND BINARY `business_payment`.`pay_type` <> BINARY `payment`.`channel`
      )
      OR (
        COALESCE(`business_payment`.`trade_no`, '') <> ''
        AND COALESCE(`payment`.`trade_no`, '') <> ''
        AND BINARY `business_payment`.`trade_no` <> BINARY `payment`.`trade_no`
      )
    );
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: order payment link conflicts with provider payment';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM `order_payments`
  WHERE `payment_order_id` < 0;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: negative order payment_order_id';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM `order_payments` AS `business_payment`
  INNER JOIN `order_orders` AS `business_order`
    ON `business_order`.`id` = `business_payment`.`order_id`
   AND `business_order`.`deleted_at` IS NULL
  INNER JOIN `payment_orders` AS `prefixed_payment`
    ON BINARY `prefixed_payment`.`order_no` = BINARY CONCAT('ORD_', `business_order`.`order_no`)
   AND `prefixed_payment`.`deleted_at` IS NULL
   AND BINARY COALESCE(`prefixed_payment`.`biz_type`, '') IN ('', 'order')
  INNER JOIN `payment_orders` AS `raw_payment`
    ON BINARY `raw_payment`.`order_no` = BINARY `business_order`.`order_no`
   AND `raw_payment`.`id` <> `prefixed_payment`.`id`
   AND `raw_payment`.`deleted_at` IS NULL
   AND BINARY COALESCE(`raw_payment`.`biz_type`, '') IN ('', 'order')
  WHERE `business_payment`.`payment_order_id` IS NULL
     OR `business_payment`.`payment_order_id` = 0;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: ambiguous raw/prefixed order payment candidates';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM (
    SELECT `payment_order_id`
    FROM `member_recharge_orders`
    WHERE `payment_order_id` > 0
    GROUP BY `payment_order_id`
    HAVING COUNT(*) > 1
  ) AS `duplicate_recharge_links`;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: duplicate recharge payment_order_id';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM `order_payments` AS `business_payment`
  INNER JOIN `member_recharge_orders` AS `recharge`
    ON `recharge`.`payment_order_id` = `business_payment`.`payment_order_id`
  WHERE `business_payment`.`payment_order_id` > 0;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: payment_order_id shared by order and recharge';
  END IF;

  -- 即使当前字段是 0/NULL，也必须在加唯一键前检查“候选支付单”
  -- 是否反向命中多个业务单。否则顺序 UPDATE 会把同一 ID 写给多行，
  -- 直到 ALTER UNIQUE 才失败，留下半升级状态。
  SELECT COUNT(*) INTO `conflict_count`
  FROM (
    SELECT `payment`.`id`
    FROM `payment_orders` AS `payment`
    INNER JOIN `order_orders` AS `business_order`
      ON (
        BINARY `payment`.`order_no` = BINARY `business_order`.`order_no`
        OR BINARY `payment`.`order_no` = BINARY CONCAT('ORD_', `business_order`.`order_no`)
      )
     AND `business_order`.`deleted_at` IS NULL
    INNER JOIN `order_payments` AS `business_payment`
      ON `business_payment`.`order_id` = `business_order`.`id`
    WHERE `payment`.`deleted_at` IS NULL
      AND BINARY COALESCE(`payment`.`biz_type`, '') IN ('', 'order')
      AND (
        `business_payment`.`payment_order_id` IS NULL
        OR `business_payment`.`payment_order_id` = 0
        OR `business_payment`.`payment_order_id` = `payment`.`id`
      )
    GROUP BY `payment`.`id`
    HAVING COUNT(DISTINCT `business_payment`.`id`) > 1
  ) AS `ambiguous_order_payment_candidates`;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: provider payment matches multiple business orders';
  END IF;

  -- 不依赖 order_payments 的全量单号映射检查。形如业务单 X 与 ORD_X
  -- 同时存在时，同一个旧商户单号可能有两种解释，禁止升级脚本猜测。
  SELECT COUNT(*) INTO `conflict_count`
  FROM (
    SELECT `payment`.`id`
    FROM `payment_orders` AS `payment`
    INNER JOIN `order_orders` AS `business_order`
      ON (
        BINARY `payment`.`order_no` = BINARY `business_order`.`order_no`
        OR BINARY `payment`.`order_no` = BINARY CONCAT('ORD_', `business_order`.`order_no`)
      )
     AND `business_order`.`deleted_at` IS NULL
    WHERE `payment`.`deleted_at` IS NULL
      AND BINARY COALESCE(`payment`.`biz_type`, '') IN ('', 'order')
    GROUP BY `payment`.`id`
    HAVING COUNT(DISTINCT `business_order`.`id`) > 1
  ) AS `ambiguous_provider_business_reference`;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: provider payment order reference is ambiguous';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM (
    SELECT `business_order`.`id`
    FROM `order_orders` AS `business_order`
    INNER JOIN `payment_orders` AS `payment`
      ON (
        BINARY `payment`.`order_no` = BINARY `business_order`.`order_no`
        OR BINARY `payment`.`order_no` = BINARY CONCAT('ORD_', `business_order`.`order_no`)
      )
     AND `payment`.`deleted_at` IS NULL
    WHERE `business_order`.`deleted_at` IS NULL
      AND BINARY COALESCE(`payment`.`biz_type`, '') IN ('', 'order')
      AND BINARY `payment`.`status` IN ('paid', 'refunded')
    GROUP BY `business_order`.`id`
    HAVING COUNT(DISTINCT `payment`.`id`) > 1
  ) AS `duplicate_terminal_business_payments`;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: multiple terminal provider payments match one business order';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM (
    SELECT `payment`.`id`
    FROM `payment_orders` AS `payment`
    INNER JOIN `member_recharge_orders` AS `recharge`
      ON (
        BINARY `payment`.`order_no` = BINARY `recharge`.`order_no`
        OR BINARY `payment`.`order_no` = BINARY CONCAT('RCH_', `recharge`.`order_no`)
      )
    WHERE `payment`.`deleted_at` IS NULL
      AND BINARY COALESCE(`payment`.`biz_type`, '') IN ('', 'recharge')
      AND (
        `recharge`.`payment_order_id` IS NULL
        OR `recharge`.`payment_order_id` = 0
        OR `recharge`.`payment_order_id` = `payment`.`id`
      )
    GROUP BY `payment`.`id`
    HAVING COUNT(DISTINCT `recharge`.`id`) > 1
  ) AS `ambiguous_recharge_payment_candidates`;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: provider payment matches multiple recharges';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM `payment_orders` AS `payment`
  INNER JOIN `order_orders` AS `business_order`
    ON (
      BINARY `payment`.`order_no` = BINARY `business_order`.`order_no`
      OR BINARY `payment`.`order_no` = BINARY CONCAT('ORD_', `business_order`.`order_no`)
    )
   AND `business_order`.`deleted_at` IS NULL
  INNER JOIN `order_payments` AS `business_payment`
    ON `business_payment`.`order_id` = `business_order`.`id`
  INNER JOIN `member_recharge_orders` AS `recharge`
    ON (
      BINARY `payment`.`order_no` = BINARY `recharge`.`order_no`
      OR BINARY `payment`.`order_no` = BINARY CONCAT('RCH_', `recharge`.`order_no`)
    )
  WHERE `payment`.`deleted_at` IS NULL
    AND BINARY COALESCE(`payment`.`biz_type`, '') = ''
    AND (
      `business_payment`.`payment_order_id` IS NULL
      OR `business_payment`.`payment_order_id` = 0
      OR `business_payment`.`payment_order_id` = `payment`.`id`
    )
    AND (
      `recharge`.`payment_order_id` IS NULL
      OR `recharge`.`payment_order_id` = 0
      OR `recharge`.`payment_order_id` = `payment`.`id`
    );
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: provider payment ambiguously matches order and recharge';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM `member_recharge_orders` AS `recharge`
  LEFT JOIN `payment_orders` AS `payment`
    ON `payment`.`id` = `recharge`.`payment_order_id`
   AND `payment`.`deleted_at` IS NULL
  WHERE `recharge`.`payment_order_id` > 0
    AND (
      `payment`.`id` IS NULL
      OR COALESCE(`recharge`.`order_no`, '') = ''
      OR BINARY COALESCE(`payment`.`biz_type`, '') NOT IN ('', 'recharge')
      OR COALESCE(`recharge`.`status`, -1) NOT IN (0, 1)
      OR (
        `recharge`.`status` = 1
        AND BINARY `payment`.`status` NOT IN ('paid', 'refunded')
      )
      OR (
        BINARY `payment`.`order_no` <> BINARY `recharge`.`order_no`
        AND BINARY `payment`.`order_no` <> BINARY CONCAT('RCH_', `recharge`.`order_no`)
      )
      OR ABS(COALESCE(`recharge`.`amount`, 0) - COALESCE(`payment`.`total_amount`, 0)) >= 0.005
      OR (
        COALESCE(`payment`.`user_id`, 0) > 0
        AND COALESCE(`recharge`.`user_id`, 0) > 0
        AND `payment`.`user_id` <> `recharge`.`user_id`
      )
      OR (
        COALESCE(`recharge`.`pay_type`, '') <> ''
        AND COALESCE(`payment`.`channel`, '') <> ''
        AND BINARY `recharge`.`pay_type` <> BINARY `payment`.`channel`
      )
    );
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: recharge link conflicts with provider payment';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM `member_recharge_orders`
  WHERE `payment_order_id` < 0;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: negative recharge payment_order_id';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM `member_recharge_orders` AS `recharge`
  INNER JOIN `payment_orders` AS `prefixed_payment`
    ON BINARY `prefixed_payment`.`order_no` = BINARY CONCAT('RCH_', `recharge`.`order_no`)
   AND `prefixed_payment`.`deleted_at` IS NULL
   AND BINARY COALESCE(`prefixed_payment`.`biz_type`, '') IN ('', 'recharge')
  INNER JOIN `payment_orders` AS `raw_payment`
    ON BINARY `raw_payment`.`order_no` = BINARY `recharge`.`order_no`
   AND `raw_payment`.`id` <> `prefixed_payment`.`id`
   AND `raw_payment`.`deleted_at` IS NULL
   AND BINARY COALESCE(`raw_payment`.`biz_type`, '') IN ('', 'recharge')
  WHERE `recharge`.`payment_order_id` IS NULL
     OR `recharge`.`payment_order_id` = 0;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: ambiguous raw/prefixed recharge payment candidates';
  END IF;

  -- v2.12.0 允许商品行部分退款，但任意一次成功都会把 payment_orders
  -- 标成 refunded，且 refund_amount 只保存当次金额。新版本将该字段解释为
  -- 累计退款额，所以必须先证明每条成功退款的订单、商品行和支付单身份。
  SELECT COUNT(*) INTO `conflict_count`
  FROM `order_refunds` AS `refund`
  LEFT JOIN `order_orders` AS `business_order`
    ON `business_order`.`id` = `refund`.`order_id`
   AND `business_order`.`deleted_at` IS NULL
  LEFT JOIN `order_items` AS `order_item`
    ON `order_item`.`id` = `refund`.`order_item_id`
  LEFT JOIN `order_payments` AS `business_payment`
    ON `business_payment`.`order_id` = `refund`.`order_id`
  LEFT JOIN `payment_orders` AS `payment`
    ON `payment`.`id` = `business_payment`.`payment_order_id`
   AND `payment`.`deleted_at` IS NULL
  WHERE BINARY `refund`.`status` = 'refunded'
    AND (
      `refund`.`deleted_at` IS NOT NULL
      OR `business_order`.`id` IS NULL
      OR `order_item`.`id` IS NULL
      OR COALESCE(`order_item`.`order_id`, 0) <> COALESCE(`refund`.`order_id`, 0)
      OR COALESCE(`refund`.`refund_amount`, 0) <= 0
      OR ROUND(COALESCE(`refund`.`refund_amount`, 0) * 100)
         > ROUND(COALESCE(`order_item`.`total_amount`, 0) * 100)
      OR `business_payment`.`id` IS NULL
      OR COALESCE(`business_payment`.`payment_order_id`, 0) <= 0
      OR COALESCE(`business_payment`.`status`, -1) NOT IN (1, 2)
      OR `payment`.`id` IS NULL
      OR BINARY `payment`.`status` NOT IN ('paid', 'refunded')
      OR ROUND(COALESCE(`payment`.`total_amount`, 0) * 100) <= 0
      OR (
        BINARY `payment`.`order_no` <> BINARY `business_order`.`order_no`
        AND BINARY `payment`.`order_no` <> BINARY CONCAT('ORD_', `business_order`.`order_no`)
      )
      OR (
        COALESCE(`refund`.`user_id`, 0) > 0
        AND COALESCE(`business_order`.`user_id`, 0) > 0
        AND `refund`.`user_id` <> `business_order`.`user_id`
      )
    );
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: invalid successful order_refund evidence';
  END IF;

  -- 同一商品行多次成功退款会同时意味着重复回库，不能仅凭金额合计判断
  -- 哪次是真实经济事件，必须结合渠道账单人工审计。
  SELECT COUNT(*) INTO `conflict_count`
  FROM (
    SELECT `order_item_id`
    FROM `order_refunds`
    WHERE BINARY `status` = 'refunded'
      AND `deleted_at` IS NULL
    GROUP BY `order_item_id`
    HAVING COUNT(*) > 1
  ) AS `duplicate_successful_item_refunds`;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: item has multiple successful refunds';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM (
    SELECT
      `payment`.`id`,
      ROUND(SUM(`refund`.`refund_amount`) * 100) AS `successful_refund_cents`,
      ROUND(`payment`.`total_amount` * 100) AS `payment_total_cents`
    FROM `order_refunds` AS `refund`
    INNER JOIN `order_payments` AS `business_payment`
      ON `business_payment`.`order_id` = `refund`.`order_id`
    INNER JOIN `payment_orders` AS `payment`
      ON `payment`.`id` = `business_payment`.`payment_order_id`
     AND `payment`.`deleted_at` IS NULL
    WHERE BINARY `refund`.`status` = 'refunded'
      AND `refund`.`deleted_at` IS NULL
    GROUP BY `payment`.`id`, `payment`.`total_amount`
    HAVING `successful_refund_cents` > `payment_total_cents`
  ) AS `over_refunded_payments`;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: successful refund aggregate exceeds payment total';
  END IF;

  -- 旧字段可能是累计值，也可能是旧代码最后一次成功退款的单次值。
  -- 只有等于累计值、等于其中一笔成功退款或为旧缺省 0 才有确定解释。
  SELECT COUNT(*) INTO `conflict_count`
  FROM (
    SELECT
      `payment`.`id`,
      ROUND(COALESCE(`payment`.`refund_amount`, 0) * 100) AS `stored_refund_cents`,
      ROUND(COALESCE(`payment`.`total_amount`, 0) * 100) AS `payment_total_cents`,
      ROUND(SUM(`refund`.`refund_amount`) * 100) AS `successful_refund_cents`,
      MAX(
        CASE
          WHEN ROUND(`refund`.`refund_amount` * 100)
             = ROUND(COALESCE(`payment`.`refund_amount`, 0) * 100)
          THEN 1 ELSE 0
        END
      ) AS `stored_matches_one_event`
    FROM `order_refunds` AS `refund`
    INNER JOIN `order_payments` AS `business_payment`
      ON `business_payment`.`order_id` = `refund`.`order_id`
    INNER JOIN `payment_orders` AS `payment`
      ON `payment`.`id` = `business_payment`.`payment_order_id`
     AND `payment`.`deleted_at` IS NULL
    WHERE BINARY `refund`.`status` = 'refunded'
      AND `refund`.`deleted_at` IS NULL
    GROUP BY `payment`.`id`, `payment`.`refund_amount`, `payment`.`total_amount`
    HAVING `stored_refund_cents` < 0
       OR `stored_refund_cents` > `payment_total_cents`
       OR (
         `stored_refund_cents` > 0
         AND `stored_refund_cents` <> `successful_refund_cents`
         AND `stored_matches_one_event` = 0
       )
  ) AS `conflicting_payment_refund_amounts`;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: payment refund amount conflicts with successful refunds';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM `payment_orders`
  WHERE BINARY `status` = 'refunded'
    AND `deleted_at` IS NULL
    AND (
      ROUND(COALESCE(`total_amount`, 0) * 100) <= 0
      OR ROUND(COALESCE(`refund_amount`, 0) * 100) < 0
      OR ROUND(COALESCE(`refund_amount`, 0) * 100)
         > ROUND(COALESCE(`total_amount`, 0) * 100)
    );
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: invalid refunded payment amount';
  END IF;

  -- 任意“已退款但未全退”的支付单都将被新代码视为可继续退款。
  -- 若找不到唯一正向绑定且已成功的 order_refunds 证据，则不能猜测其
  -- 业务域或累计额，必须停在结构变更之前等待渠道账单审计。
  SELECT COUNT(*) INTO `conflict_count`
  FROM `payment_orders` AS `payment`
  LEFT JOIN (
    SELECT
      `business_payment`.`payment_order_id`,
      ROUND(SUM(`refund`.`refund_amount`) * 100) AS `successful_refund_cents`
    FROM `order_payments` AS `business_payment`
    INNER JOIN `order_refunds` AS `refund`
      ON `refund`.`order_id` = `business_payment`.`order_id`
     AND BINARY `refund`.`status` = 'refunded'
     AND `refund`.`deleted_at` IS NULL
    WHERE `business_payment`.`payment_order_id` > 0
    GROUP BY `business_payment`.`payment_order_id`
  ) AS `refund_evidence`
    ON `refund_evidence`.`payment_order_id` = `payment`.`id`
  WHERE BINARY `payment`.`status` = 'refunded'
    AND `payment`.`deleted_at` IS NULL
    AND ROUND(COALESCE(`payment`.`refund_amount`, 0) * 100)
        < ROUND(COALESCE(`payment`.`total_amount`, 0) * 100)
    AND `refund_evidence`.`payment_order_id` IS NULL;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: partial refunded payment lacks provable refund evidence';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM (
    SELECT `source`
    FROM `balance_logs`
    WHERE `source` LIKE 'distribution_settle:%'
    GROUP BY `source`
    HAVING COUNT(*) > 1
  ) AS `duplicate_distribution_settlement_logs`;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: duplicate distribution settlement source evidence';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM `balance_logs` AS `settlement_log`
  LEFT JOIN `distribution_commissions` AS `commission`
    ON `settlement_log`.`source` = CONCAT('distribution_settle:', `commission`.`id`)
  WHERE `settlement_log`.`source` LIKE 'distribution_settle:%'
    AND (
      `commission`.`id` IS NULL
      OR `settlement_log`.`user_id` <> `commission`.`user_id`
      OR `settlement_log`.`type` <> 5
      OR `settlement_log`.`amount` < 0
      OR `settlement_log`.`amount` > `commission`.`amount` + 0.004
      OR ABS((`settlement_log`.`before_balance` + `settlement_log`.`amount`)
        - `settlement_log`.`after_balance`) >= 0.005
    );
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: invalid distribution settlement ledger evidence';
  END IF;

  SELECT COUNT(*) INTO `conflict_count`
  FROM (
    SELECT `order_id`
    FROM `delivery_orders`
    GROUP BY `order_id`
    HAVING COUNT(*) > 1
  ) AS `duplicate_delivery_orders`;
  IF `conflict_count` > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'v2.12.3 preflight: duplicate delivery order_id';
  END IF;
END$$
DELIMITER ;

CALL `assert_v2123_upgrade_safe`();
DROP PROCEDURE IF EXISTS `assert_v2123_upgrade_safe`;

-- 与 user_notifications.id 的 bigint 主键保持一致，避免通知量超过
-- unsigned int 上限后已读关系无法写入。
ALTER TABLE `user_notification_reads`
  MODIFY COLUMN `notification_id` bigint unsigned NOT NULL COMMENT '通知ID';

ALTER TABLE `user_notifications`
  MODIFY COLUMN `biz_id` bigint unsigned DEFAULT NULL COMMENT '关联业务ID',
  ADD COLUMN `event_key` varchar(191) DEFAULT NULL COMMENT '领域事件幂等键' AFTER `biz_id`,
  ADD UNIQUE KEY `uk_user_notification_event` (`user_id`, `event_key`);

-- 支付创建/取消分别使用 creating/closing 作为短暂操作屏障。字段类型
-- 不变，但升级库也更新注释，避免与新安装 schema 的状态契约漂移。
ALTER TABLE `payment_orders`
  ADD COLUMN `business_order_no` varchar(64) DEFAULT NULL
    COMMENT '业务订单号；同一业务可对应多次渠道支付尝试' AFTER `biz_type`,
  ADD COLUMN `provider_account_id` varchar(128) DEFAULT NULL
    COMMENT '签发时收款主体：支付宝app_id/微信mch_id' AFTER `extra`,
  ADD COLUMN `provider_app_id` varchar(128) DEFAULT NULL
    COMMENT '签发时客户端AppID' AFTER `provider_account_id`,
  ADD COLUMN `provider_expires_at` datetime DEFAULT NULL
    COMMENT '已签发支付凭据的渠道失效时间' AFTER `provider_app_id`,
  ADD COLUMN `provider_attempt_token` varchar(64) DEFAULT NULL
    COMMENT '当前渠道创建尝试栅栏' AFTER `provider_expires_at`,
  ADD COLUMN `provider_request_hash` char(64) DEFAULT NULL
    COMMENT '当前支付调起参数指纹' AFTER `provider_attempt_token`,
  ADD COLUMN `provider_reconcile_retry_count` int unsigned NOT NULL DEFAULT 0
    COMMENT '渠道对账连续失败次数' AFTER `provider_request_hash`,
  ADD COLUMN `provider_reconcile_next_at` datetime DEFAULT NULL
    COMMENT '下次渠道对账时间' AFTER `provider_reconcile_retry_count`,
  ADD COLUMN `provider_reconcile_last_error` varchar(500) NOT NULL DEFAULT ''
    COMMENT '最近渠道对账错误' AFTER `provider_reconcile_next_at`,
  ADD KEY `idx_provider_reconcile_due` (`status`,`provider_reconcile_next_at`,`updated_at`),
  MODIFY COLUMN `status` varchar(20) NOT NULL DEFAULT 'pending'
  COMMENT '状态：creating/pending/closing/paid/closed/refunded';

-- 订单行分摊字段必须先于下方佣金 base_amount 回填创建。
-- 历史行暂保持 0，首次售后/会员奖励/佣金处理时会按整数分
-- 确定性惰性分摊；不在 SQL 中用浮点比例破坏尾差守恒。

ALTER TABLE `order_items`
  ADD COLUMN `flash_item_id` int unsigned DEFAULT NULL COMMENT '秒杀活动商品ID，普通订单为空' AFTER `sku_id`,
  ADD COLUMN `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '订单优惠分摊' AFTER `total_amount`,
  ADD COLUMN `freight_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '订单运费分摊' AFTER `discount_amount`,
  ADD COLUMN `pay_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '该行净实付金额' AFTER `freight_amount`,
  ADD KEY `flash_item_id` (`flash_item_id`);

ALTER TABLE `order_orders`
  ADD COLUMN `virtual_fulfillment_status` varchar(20) NOT NULL DEFAULT 'none'
    COMMENT '虚拟发货：none/pending/failed/completed' AFTER `auto_cancel_at`,
  ADD COLUMN `virtual_fulfillment_error` varchar(255) NOT NULL DEFAULT ''
    COMMENT '最近虚拟发货异常' AFTER `virtual_fulfillment_status`,
  ADD COLUMN `cancel_effects_status` varchar(20) NOT NULL DEFAULT 'none'
    COMMENT '取消副作用：none/pending/failed/completed' AFTER `virtual_fulfillment_error`,
  ADD COLUMN `cancel_effects_error` varchar(255) NOT NULL DEFAULT ''
    COMMENT '最近取消副作用异常' AFTER `cancel_effects_status`,
  ADD KEY `idx_virtual_fulfillment` (`virtual_fulfillment_status`,`status`),
  ADD KEY `idx_cancel_effects` (`status`,`cancel_effects_status`);

-- 原子 create-once 依赖的事件键。历史行全部保持 NULL，MySQL
-- 唯一索引允许多个 NULL，因此不会将旧流水误认为同一新事件。
ALTER TABLE `balance_logs`
  ADD COLUMN `event_key` varchar(191) DEFAULT NULL COMMENT '领域事件幂等键' AFTER `source`,
  ADD UNIQUE KEY `uk_balance_event_key` (`user_id`, `event_key`),
  ADD KEY `idx_source` (`source`);

ALTER TABLE `points_logs`
  ADD COLUMN `event_key` varchar(191) DEFAULT NULL COMMENT '领域事件幂等键' AFTER `source`,
  ADD UNIQUE KEY `uk_points_event_key` (`user_id`, `event_key`),
  ADD KEY `idx_points_source` (`source`);

ALTER TABLE `finance_transactions`
  ADD COLUMN `event_key` varchar(191) DEFAULT NULL COMMENT '领域事件幂等键，NULL表示历史/手工流水' AFTER `transaction_no`,
  ADD UNIQUE KEY `uk_finance_event_key` (`event_key`);

ALTER TABLE `message_logs`
  ADD COLUMN `event_key` varchar(191) DEFAULT NULL COMMENT '领域事件幂等键，NULL表示手工发送' AFTER `template_code`,
  ADD UNIQUE KEY `uk_message_event_receiver` (`event_key`, `channel`, `receiver`);

ALTER TABLE `user_operation_logs`
  ADD COLUMN `event_key` varchar(191) DEFAULT NULL COMMENT '领域事件幂等键，NULL表示非领域事件日志' AFTER `category`,
  ADD UNIQUE KEY `uk_user_operation_event_key` (`event_key`);

-- 为旧 0/空绑定只接管能由业务单号、用户、金额、渠道和交易号
-- 唯一证明的 payment_orders。不唯一或证据不完整的行保持 NULL，由
-- payment:resync 或人工对账处理，绝不猜测绑定。
-- 已绑定行若只缺失 amount，且订单实付与支付单严格相等，
-- 则这两份独立证据足以安全补齐该非身份字段。
UPDATE `order_payments` AS `business_payment`
INNER JOIN `order_orders` AS `business_order`
  ON `business_order`.`id` = `business_payment`.`order_id`
 AND `business_order`.`deleted_at` IS NULL
INNER JOIN `payment_orders` AS `payment`
 ON `payment`.`id` = `business_payment`.`payment_order_id`
 AND `payment`.`deleted_at` IS NULL
 AND BINARY COALESCE(`payment`.`biz_type`, '') IN ('', 'order')
SET `business_payment`.`amount` = `payment`.`total_amount`
WHERE `business_payment`.`payment_order_id` > 0
  AND COALESCE(`business_payment`.`amount`, 0) = 0
  AND ABS(COALESCE(`business_order`.`pay_amount`, 0) - COALESCE(`payment`.`total_amount`, 0)) < 0.005;

UPDATE `order_payments` AS `business_payment`
INNER JOIN `order_orders` AS `business_order`
  ON `business_order`.`id` = `business_payment`.`order_id`
 AND `business_order`.`deleted_at` IS NULL
INNER JOIN `payment_orders` AS `payment`
  ON (
    BINARY `payment`.`order_no` = BINARY `business_order`.`order_no`
    OR BINARY `payment`.`order_no` = BINARY CONCAT('ORD_', `business_order`.`order_no`)
 )
 AND `payment`.`deleted_at` IS NULL
 AND BINARY COALESCE(`payment`.`biz_type`, '') IN ('', 'order')
LEFT JOIN `payment_orders` AS `alternative_payment`
  ON `alternative_payment`.`id` <> `payment`.`id`
 AND (
   BINARY `alternative_payment`.`order_no` = BINARY `business_order`.`order_no`
   OR BINARY `alternative_payment`.`order_no` = BINARY CONCAT('ORD_', `business_order`.`order_no`)
 )
 AND `alternative_payment`.`deleted_at` IS NULL
 AND BINARY COALESCE(`alternative_payment`.`biz_type`, '') IN ('', 'order')
SET `business_payment`.`payment_order_id` = `payment`.`id`,
    `business_payment`.`amount` = IF(
      COALESCE(`business_payment`.`amount`, 0) = 0,
      `payment`.`total_amount`,
      `business_payment`.`amount`
    )
WHERE (`business_payment`.`payment_order_id` IS NULL OR `business_payment`.`payment_order_id` = 0)
  AND `alternative_payment`.`id` IS NULL
  AND (
    ABS(COALESCE(`business_payment`.`amount`, 0) - COALESCE(`payment`.`total_amount`, 0)) < 0.005
    OR (
      COALESCE(`business_payment`.`amount`, 0) = 0
      AND ABS(COALESCE(`business_order`.`pay_amount`, 0) - COALESCE(`payment`.`total_amount`, 0)) < 0.005
    )
  )
  AND (
    COALESCE(`payment`.`user_id`, 0) = 0
    OR COALESCE(`business_order`.`user_id`, 0) = 0
    OR `payment`.`user_id` = `business_order`.`user_id`
  )
  AND (
    COALESCE(`business_payment`.`pay_type`, '') = ''
    OR COALESCE(`payment`.`channel`, '') = ''
    OR BINARY `business_payment`.`pay_type` = BINARY `payment`.`channel`
  )
  AND (
    COALESCE(`business_payment`.`trade_no`, '') = ''
    OR COALESCE(`payment`.`trade_no`, '') = ''
    OR BINARY `business_payment`.`trade_no` = BINARY `payment`.`trade_no`
  )
  AND (
    `business_payment`.`status` = 0
    OR (`business_payment`.`status` = 1 AND BINARY `payment`.`status` IN ('paid', 'refunded'))
    OR (`business_payment`.`status` = 2 AND BINARY `payment`.`status` = 'refunded')
  );

-- 用已成功且未软删除的 order_refunds 重建累计退款额。前置检查已经证明
-- 每条证据的订单/商品行/支付单关系、金额上限和旧字段语义均无歧义：
-- 累计小于支付额时恢复 paid/status=1，以允许其他商品行继续退款；
-- 只有累计恰好等于支付额时才保留 refunded/status=2。
UPDATE `payment_orders` AS `payment`
INNER JOIN `order_payments` AS `business_payment`
  ON `business_payment`.`payment_order_id` = `payment`.`id`
INNER JOIN (
  SELECT
    `refund`.`order_id`,
    ROUND(SUM(`refund`.`refund_amount`), 2) AS `successful_refund_amount`,
    MAX(COALESCE(`refund`.`updated_at`, `refund`.`created_at`)) AS `last_refunded_at`
  FROM `order_refunds` AS `refund`
  WHERE BINARY `refund`.`status` = 'refunded'
    AND `refund`.`deleted_at` IS NULL
  GROUP BY `refund`.`order_id`
) AS `successful_refunds`
  ON `successful_refunds`.`order_id` = `business_payment`.`order_id`
SET `payment`.`refund_amount` = `successful_refunds`.`successful_refund_amount`,
    `payment`.`status` = IF(
      ROUND(`successful_refunds`.`successful_refund_amount` * 100)
        < ROUND(`payment`.`total_amount` * 100),
      'paid',
      'refunded'
    ),
    `payment`.`refunded_at` = IF(
      ROUND(`successful_refunds`.`successful_refund_amount` * 100)
        < ROUND(`payment`.`total_amount` * 100),
      NULL,
      COALESCE(`payment`.`refunded_at`, `successful_refunds`.`last_refunded_at`)
    ),
    `payment`.`updated_at` = NOW(),
    `business_payment`.`status` = IF(
      ROUND(`successful_refunds`.`successful_refund_amount` * 100)
        < ROUND(`payment`.`total_amount` * 100),
      1,
      2
    ),
    `business_payment`.`refunded_at` = IF(
      ROUND(`successful_refunds`.`successful_refund_amount` * 100)
        < ROUND(`payment`.`total_amount` * 100),
      NULL,
      COALESCE(`business_payment`.`refunded_at`, `successful_refunds`.`last_refunded_at`)
    ),
    `business_payment`.`updated_at` = NOW()
WHERE `payment`.`deleted_at` IS NULL
  AND BINARY `payment`.`status` IN ('paid', 'refunded');

-- 绑定已由上述证据链确定后，可以安全补齐旧 payment_orders
-- 缺失的用户/业务类型/交易号/发生时间，保证 payment:resync
-- 不会把可证明的历史已支付单误报为无交易号。
UPDATE `payment_orders` AS `payment`
INNER JOIN `order_payments` AS `business_payment`
  ON `business_payment`.`payment_order_id` = `payment`.`id`
INNER JOIN `order_orders` AS `business_order`
  ON `business_order`.`id` = `business_payment`.`order_id`
 AND `business_order`.`deleted_at` IS NULL
SET `payment`.`user_id` = IF(
      COALESCE(`payment`.`user_id`, 0) = 0,
      `business_order`.`user_id`,
      `payment`.`user_id`
    ),
    `payment`.`biz_type` = IF(
      COALESCE(`payment`.`biz_type`, '') = '',
      'order',
      `payment`.`biz_type`
    ),
    `payment`.`trade_no` = IF(
      BINARY `payment`.`status` IN ('paid', 'refunded')
        AND COALESCE(`payment`.`trade_no`, '') = '',
      NULLIF(`business_payment`.`trade_no`, ''),
      `payment`.`trade_no`
    ),
    `payment`.`paid_at` = IF(
      BINARY `payment`.`status` IN ('paid', 'refunded'),
      COALESCE(`payment`.`paid_at`, `business_payment`.`paid_at`),
      `payment`.`paid_at`
    )
WHERE `payment`.`deleted_at` IS NULL;

UPDATE `order_payments`
SET `payment_order_id` = NULL
WHERE `payment_order_id` = 0;

ALTER TABLE `order_payments`
  DROP INDEX `payment_order_id`,
  MODIFY COLUMN `payment_order_id` bigint unsigned DEFAULT NULL COMMENT '真实支付单ID',
  ADD UNIQUE KEY `uk_order_payment_order_id` (`payment_order_id`);

ALTER TABLE `member_recharge_orders`
  ADD COLUMN `settled_at` datetime DEFAULT NULL COMMENT '资产、积分、成长值全部结算完成时间' AFTER `paid_at`,
  ADD COLUMN `expected_growth_value` int NOT NULL DEFAULT 0 COMMENT '本次充值理论应发成长值' AFTER `settled_at`,
  ADD COLUMN `growth_review_status` varchar(20) NOT NULL DEFAULT 'none' COMMENT '成长值复核:none/pending/resolved' AFTER `expected_growth_value`,
  ADD COLUMN `growth_review_resolution` varchar(30) NOT NULL DEFAULT '' COMMENT '复核结论:confirmed_applied/confirmed_missing' AFTER `growth_review_status`,
  ADD COLUMN `growth_review_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '待复核原因或人工复核依据' AFTER `growth_review_resolution`,
  ADD COLUMN `growth_review_operator_id` int unsigned DEFAULT NULL COMMENT '复核管理员ID' AFTER `growth_review_reason`,
  ADD COLUMN `growth_reviewed_at` datetime DEFAULT NULL COMMENT '复核时间' AFTER `growth_review_operator_id`,
  ADD KEY `idx_recharge_growth_review` (`growth_review_status`,`id`);

UPDATE `member_recharge_orders` AS `recharge`
INNER JOIN `payment_orders` AS `payment`
  ON (
    BINARY `payment`.`order_no` = BINARY `recharge`.`order_no`
    OR BINARY `payment`.`order_no` = BINARY CONCAT('RCH_', `recharge`.`order_no`)
 )
 AND `payment`.`deleted_at` IS NULL
 AND BINARY COALESCE(`payment`.`biz_type`, '') IN ('', 'recharge')
LEFT JOIN `payment_orders` AS `alternative_payment`
  ON `alternative_payment`.`id` <> `payment`.`id`
 AND (
   BINARY `alternative_payment`.`order_no` = BINARY `recharge`.`order_no`
   OR BINARY `alternative_payment`.`order_no` = BINARY CONCAT('RCH_', `recharge`.`order_no`)
 )
 AND `alternative_payment`.`deleted_at` IS NULL
 AND BINARY COALESCE(`alternative_payment`.`biz_type`, '') IN ('', 'recharge')
SET `recharge`.`payment_order_id` = `payment`.`id`
WHERE (`recharge`.`payment_order_id` IS NULL OR `recharge`.`payment_order_id` = 0)
  AND `alternative_payment`.`id` IS NULL
  AND ABS(COALESCE(`recharge`.`amount`, 0) - COALESCE(`payment`.`total_amount`, 0)) < 0.005
  AND (
    COALESCE(`payment`.`user_id`, 0) = 0
    OR COALESCE(`recharge`.`user_id`, 0) = 0
    OR `payment`.`user_id` = `recharge`.`user_id`
  )
  AND (
    COALESCE(`recharge`.`pay_type`, '') = ''
    OR COALESCE(`payment`.`channel`, '') = ''
    OR BINARY `recharge`.`pay_type` = BINARY `payment`.`channel`
  )
  AND (
    `recharge`.`status` = 0
    OR (`recharge`.`status` = 1 AND BINARY `payment`.`status` IN ('paid', 'refunded'))
  );

UPDATE `payment_orders` AS `payment`
INNER JOIN `member_recharge_orders` AS `recharge`
  ON `recharge`.`payment_order_id` = `payment`.`id`
SET `payment`.`user_id` = IF(
      COALESCE(`payment`.`user_id`, 0) = 0,
      `recharge`.`user_id`,
      `payment`.`user_id`
    ),
    `payment`.`biz_type` = IF(
      COALESCE(`payment`.`biz_type`, '') = '',
      'recharge',
      `payment`.`biz_type`
    ),
    `payment`.`paid_at` = IF(
      BINARY `payment`.`status` IN ('paid', 'refunded'),
      COALESCE(`payment`.`paid_at`, `recharge`.`paid_at`),
      `payment`.`paid_at`
    )
WHERE `payment`.`deleted_at` IS NULL;

-- 商城支付与渠道商户单号解耦。先使用已经由上方严格证据链绑定的
-- order_payments 回填；已明确标记为 order 的未绑定历史记录，仅按通过
-- preflight 唯一性检查的 raw/ORD_ 单号映射回填。
UPDATE `payment_orders` AS `payment`
INNER JOIN `order_payments` AS `business_payment`
  ON `business_payment`.`payment_order_id` = `payment`.`id`
INNER JOIN `order_orders` AS `business_order`
  ON `business_order`.`id` = `business_payment`.`order_id`
 AND `business_order`.`deleted_at` IS NULL
SET `payment`.`business_order_no` = `business_order`.`order_no`
WHERE `payment`.`deleted_at` IS NULL
  AND BINARY `payment`.`biz_type` = 'order';

UPDATE `payment_orders` AS `payment`
INNER JOIN `order_orders` AS `business_order`
  ON (
    BINARY `payment`.`order_no` = BINARY `business_order`.`order_no`
    OR BINARY `payment`.`order_no` = BINARY CONCAT('ORD_', `business_order`.`order_no`)
  )
 AND `business_order`.`deleted_at` IS NULL
SET `payment`.`business_order_no` = `business_order`.`order_no`
WHERE `payment`.`deleted_at` IS NULL
  AND BINARY `payment`.`biz_type` = 'order'
  AND (`payment`.`business_order_no` IS NULL OR `payment`.`business_order_no` = '');

-- closed 历史尝试不占活动槽；creating/pending/closing/paid/refunded
-- 在数据库层共享一个唯一槽，兜底阻止并发路径签发第二笔有效凭据。
ALTER TABLE `payment_orders`
  ADD COLUMN `active_business_key` varchar(191) GENERATED ALWAYS AS (
    CASE
      WHEN `biz_type` = 'order'
       AND `business_order_no` IS NOT NULL
       AND `business_order_no` <> ''
       AND `status` IN ('creating','pending','closing','paid','refunded')
      THEN CONCAT('order:', `business_order_no`)
      ELSE NULL
    END
  ) STORED,
  ADD KEY `idx_business_order_no` (`biz_type`,`business_order_no`,`id`),
  ADD UNIQUE KEY `uk_active_business_payment` (`active_business_key`);

UPDATE `member_recharge_orders`
SET `payment_order_id` = NULL
WHERE `payment_order_id` = 0;

ALTER TABLE `member_recharge_orders`
  MODIFY COLUMN `payment_order_id` bigint unsigned DEFAULT NULL COMMENT '支付订单ID',
  ADD UNIQUE KEY `uk_recharge_payment_order_id` (`payment_order_id`);

ALTER TABLE `delivery_orders`
  DROP INDEX `order_id`,
  ADD UNIQUE KEY `uk_delivery_order_id` (`order_id`);

UPDATE `system_configs`
SET `config_options` = '{"kdniao":"快递鸟"}',
    `config_value` = IF(`config_value` = 'kuaidi100', '', `config_value`),
    `updated_at` = NOW()
WHERE `config_key` = 'waybill_provider';

UPDATE `system_configs`
SET `config_value` = 'merchant',
    `config_options` = '{"merchant":"商家配送"}',
    `updated_at` = NOW()
WHERE `config_key` = 'local_delivery_platform';

DELETE FROM `system_configs`
WHERE `config_key` IN ('local_delivery_app_key', 'local_delivery_app_secret', 'local_delivery_shop_address');

DELETE FROM `system_configs`
WHERE `config_key` LIKE 'new_user_gift.rules.%';

-- 分销佣金退款冲正、债务及不可变审计账本
ALTER TABLE `users`
  ADD COLUMN `commission_debt` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '分销佣金退款冲正待偿债务' AFTER `balance`;

ALTER TABLE `distribution_commissions`
  ADD COLUMN `identity_key` varchar(64) DEFAULT NULL COMMENT '新佣金唯一身份：order_item_id:level；历史重复行可为空' AFTER `level`,
  ADD COLUMN `base_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '佣金基数：商品总额减优惠分摊，不含运费' AFTER `identity_key`,
  ADD COLUMN `credited_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '结算抵债后实际进入余额金额' AFTER `rate`,
  ADD COLUMN `reversed_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '累计冲正佣金金额' AFTER `credited_amount`,
  ADD COLUMN `reversed_at` datetime DEFAULT NULL COMMENT '冲正时间' AFTER `settled_at`,
  ADD COLUMN `reversal_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '取消或冲正原因' AFTER `reversed_at`,
  ADD COLUMN `review_status` varchar(20) NOT NULL DEFAULT 'none' COMMENT '人工复核：none/pending/resolved' AFTER `reversal_reason`,
  ADD COLUMN `review_kind` varchar(30) NOT NULL DEFAULT '' COMMENT '复核类型：refund/settlement/entitlement/withdrawal_evidence' AFTER `review_status`,
  ADD COLUMN `review_context` json DEFAULT NULL COMMENT '系统隔离时的不可变业务上下文' AFTER `review_kind`,
  ADD COLUMN `review_resolution` varchar(30) NOT NULL DEFAULT '' COMMENT '复核结论：verified_refund_applied/verified_settlement' AFTER `review_context`,
  ADD COLUMN `review_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '待复核原因或人工复核依据' AFTER `review_resolution`,
  ADD COLUMN `review_verified_credited_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '人工证据确认的原始入账金额' AFTER `review_reason`,
  ADD COLUMN `review_operator_id` int unsigned DEFAULT NULL COMMENT '复核管理员ID' AFTER `review_verified_credited_amount`,
  ADD COLUMN `reviewed_at` datetime DEFAULT NULL COMMENT '复核结案时间' AFTER `review_operator_id`,
  ADD KEY `idx_commission_review` (`review_status`,`id`),
  MODIFY COLUMN `status` enum('pending','settled','cancelled','reversed') DEFAULT 'pending' COMMENT '状态';

-- v2.12.0 旧佣金是按商品行原小计计算，且旧行的新分摊字段此时为 0。
-- 因此这里保留其真实历史计佣基数，不反向改写已形成的 amount；
-- v2.12.3 部署后创建的新佣金才严格使用“小计 - 优惠分摊”净额基数。
UPDATE `distribution_commissions` AS `commission`
INNER JOIN `order_items` AS `item` ON `item`.`id` = `commission`.`order_item_id`
SET `commission`.`base_amount` = GREATEST(`item`.`total_amount` - `item`.`discount_amount`, 0)
WHERE `commission`.`base_amount` = 0;

-- 正数结算流水是历史经济实现的最终证据。先回填 settled/credited 快照，既让
-- 后台“实际入账”统计准确，也保证后续保留的重复行在退款时走余额/债务追偿，
-- 而不是被 pending 分支误判为从未入账。
UPDATE `distribution_commissions` AS `commission`
INNER JOIN (
  SELECT
    `ledger`.`source`,
    MAX(`ledger`.`amount`) AS `credited_amount`,
    MIN(`ledger`.`created_at`) AS `settled_at`
  FROM `balance_logs` AS `ledger`
  INNER JOIN `distribution_commissions` AS `evidence_commission`
    ON `ledger`.`source` = CONCAT('distribution_settle:', `evidence_commission`.`id`)
   AND `ledger`.`user_id` = `evidence_commission`.`user_id`
  WHERE `ledger`.`type` = 5
    AND `ledger`.`amount` > 0
    AND ABS((`ledger`.`before_balance` + `ledger`.`amount`) - `ledger`.`after_balance`) < 0.005
  GROUP BY `ledger`.`source`
) AS `settlement_evidence`
  ON `settlement_evidence`.`source` = CONCAT('distribution_settle:', `commission`.`id`)
SET `commission`.`status` = 'settled',
    `commission`.`credited_amount` = GREATEST(
      `commission`.`credited_amount`,
      `settlement_evidence`.`credited_amount`
    ),
    `commission`.`settled_at` = COALESCE(
      `commission`.`settled_at`,
      `settlement_evidence`.`settled_at`
    )
WHERE `commission`.`status` IN ('pending', 'settled');

-- 旧版完成事件可能重复生成同商品行/层级佣金。canonical 必须优先选择已有
-- 正数 distribution_settle 流水的经济实现行，再按最小 id；不能简单 MIN(id)，
-- 否则“旧 pending 无流水成为 canonical + 旧 settled 已入账保留”会再次双付。
UPDATE `distribution_commissions` AS `commission`
INNER JOIN (
  SELECT
    COALESCE(
      MIN(CASE WHEN `settlement_log`.`id` IS NOT NULL THEN `candidate`.`id` END),
      MIN(`candidate`.`id`)
    ) AS `canonical_id`
  FROM `distribution_commissions` AS `candidate`
  LEFT JOIN `balance_logs` AS `settlement_log`
    ON `settlement_log`.`source` = CONCAT('distribution_settle:', `candidate`.`id`)
   AND `settlement_log`.`user_id` = `candidate`.`user_id`
   AND `settlement_log`.`type` = 5
   AND ABS((`settlement_log`.`before_balance` + `settlement_log`.`amount`)
     - `settlement_log`.`after_balance`) < 0.005
   AND `settlement_log`.`amount` > 0
  WHERE `candidate`.`order_item_id` IS NOT NULL
    AND `candidate`.`level` IS NOT NULL
  GROUP BY `candidate`.`order_item_id`, `candidate`.`level`
) AS `canonical` ON `canonical`.`canonical_id` = `commission`.`id`
SET `commission`.`identity_key` = CONCAT(`commission`.`order_item_id`, ':', `commission`.`level`);

ALTER TABLE `distribution_commissions`
  ADD UNIQUE KEY `uk_commission_identity` (`identity_key`);

ALTER TABLE `distribution_withdrawals`
  ADD COLUMN `payout_reference` varchar(100) DEFAULT NULL COMMENT '线下打款渠道流水号' AFTER `paid_at`,
  ADD COLUMN `payout_proof` varchar(500) NOT NULL DEFAULT '' COMMENT '线下打款凭证地址/说明' AFTER `payout_reference`,
  ADD COLUMN `payout_operator_id` int unsigned DEFAULT NULL COMMENT '确认打款管理员ID' AFTER `payout_proof`,
  ADD COLUMN `ledger_review_status` varchar(20) NOT NULL DEFAULT 'none' COMMENT '资金流水复核：none/pending/resolved' AFTER `payout_operator_id`,
  ADD COLUMN `ledger_review_resolution` varchar(30) NOT NULL DEFAULT '' COMMENT '核实资金事实：debited_not_refunded/not_debited/already_refunded' AFTER `ledger_review_status`,
  ADD COLUMN `ledger_review_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '复核依据或异常原因' AFTER `ledger_review_resolution`,
  ADD COLUMN `ledger_review_context` json DEFAULT NULL COMMENT '流水异常及人工证据上下文' AFTER `ledger_review_reason`,
  ADD COLUMN `ledger_review_operator_id` int unsigned DEFAULT NULL COMMENT '复核管理员ID' AFTER `ledger_review_context`,
  ADD COLUMN `ledger_reviewed_at` datetime DEFAULT NULL COMMENT '复核时间' AFTER `ledger_review_operator_id`,
  ADD KEY `idx_withdrawal_ledger_review` (`ledger_review_status`,`id`),
  ADD UNIQUE KEY `uk_payout_reference` (`type`,`payout_reference`);

-- NULL identity_key 代表同商品行/层级下非最早的历史重复记录。没有真实正数
-- 结算流水的重复 pending/settled 从未形成经济权益，升级时直接取消，避免
-- 自动补账或人工结算重复发钱；已有正数流水的重复 settled 保留用于退款追偿。
UPDATE `distribution_commissions` AS `commission`
LEFT JOIN `balance_logs` AS `settlement_log`
  ON `settlement_log`.`source` = CONCAT('distribution_settle:', `commission`.`id`)
 AND `settlement_log`.`user_id` = `commission`.`user_id`
 AND `settlement_log`.`type` = 5
 AND ABS((`settlement_log`.`before_balance` + `settlement_log`.`amount`)
   - `settlement_log`.`after_balance`) < 0.005
 AND `settlement_log`.`amount` > 0
SET `commission`.`status` = 'cancelled',
    `commission`.`reversed_amount` = GREATEST(`commission`.`reversed_amount`, `commission`.`amount`),
    `commission`.`reversed_at` = COALESCE(`commission`.`reversed_at`, NOW()),
    `commission`.`reversal_reason` = '升级清理：历史重复佣金未实际入账，禁止再次结算'
WHERE `commission`.`identity_key` IS NULL
  AND `commission`.`status` IN ('pending', 'settled')
  AND `settlement_log`.`id` IS NULL;

CREATE TABLE IF NOT EXISTS `distribution_commission_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_key` varchar(191) NOT NULL COMMENT '幂等事件键',
  `commission_id` int unsigned NOT NULL COMMENT '佣金记录ID',
  `refund_id` int unsigned DEFAULT NULL COMMENT '退款记录ID，结算动作为空',
  `order_id` int unsigned NOT NULL DEFAULT 0 COMMENT '订单ID',
  `order_item_id` int unsigned NOT NULL DEFAULT 0 COMMENT '订单明细ID',
  `user_id` bigint unsigned NOT NULL COMMENT '分销用户ID',
  `action` varchar(40) NOT NULL COMMENT 'settle/settle_legacy/refund_cancel_pending/refund_cancel_legacy/refund_reverse/review_verified/review_settlement_verified',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '本次结算或冲正佣金总额',
  `balance_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '余额变化：入账为正，扣回为负',
  `debt_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '债务变化：新增为正，偿还为负',
  `refund_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '本次退款金额',
  `cumulative_refund_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '事件携带的累计退款金额',
  `balance_before` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '调整前余额',
  `balance_after` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '调整后余额',
  `debt_before` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '调整前佣金债务',
  `debt_after` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '调整后佣金债务',
  `reason` varchar(255) NOT NULL DEFAULT '' COMMENT '调整原因',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_distribution_adjustment_event` (`event_key`),
  UNIQUE KEY `uk_commission_refund_action` (`commission_id`, `refund_id`, `action`),
  KEY `idx_adjustment_user` (`user_id`),
  KEY `idx_adjustment_refund` (`refund_id`),
  KEY `idx_adjustment_order_item` (`order_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='分销佣金结算与退款冲正账本';

INSERT IGNORE INTO `system_configs`
  (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
VALUES
  ('distribution.settle_days', '7', 'distribution', 'number', '佣金冻结天数', '订单完成后佣金保持待结算的天数', NULL, NULL, 1, 1, NOW(), NOW()),
  ('distribution.commission_reconcile_from', DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'), 'distribution', 'string', '佣金补偿起始时间', '仅幂等重算本次升级后完成的订单，避免追溯旧佣金', NULL, NULL, 2, 1, NOW(), NOW());

INSERT IGNORE INTO `system_configs`
  (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
VALUES
  ('payment.reconcile_from', DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'), 'payment', 'string', '支付消费者补偿起始时间', '自动补偿仅重放该时间后本地已支付记录，避免重新广播全部历史支付', NULL, NULL, 1, 1, NOW(), NOW());

INSERT INTO `cron_jobs`
  (`name`, `command`, `expression`, `description`, `status`, `created_at`, `updated_at`)
SELECT
  '分销退款佣金对账', 'distribution:reconcile-refunds', '30 3 * * *',
  '重放已退款订单并补齐佣金冲正账本', 1, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `cron_jobs` WHERE `command` = 'distribution:reconcile-refunds'
);

UPDATE `cron_jobs`
SET `status` = 1, `updated_at` = NOW()
WHERE `command` = 'distribution:reconcile-refunds';

-- 退款渠道标识与财务/支付最终一致性补偿
ALTER TABLE `order_refunds`
  ADD COLUMN `refund_trade_no` varchar(128) NOT NULL DEFAULT '' COMMENT '退款标识：微信refund_id；支付宝out_request_no/商户退款单号' AFTER `refund_amount`,
  ADD COLUMN `refund_trade_no_source` varchar(32) NOT NULL DEFAULT '' COMMENT '退款标识来源' AFTER `refund_trade_no`,
  ADD COLUMN `provider_status` varchar(32) NOT NULL DEFAULT '' COMMENT '最近一次支付渠道退款状态' AFTER `refund_trade_no_source`,
  ADD COLUMN `provider_requested_at` datetime DEFAULT NULL COMMENT '最近一次向支付渠道发起退款时间' AFTER `provider_status`,
  ADD COLUMN `provider_checked_at` datetime DEFAULT NULL COMMENT '最近一次观察或查询支付渠道退款状态时间' AFTER `provider_requested_at`,
  ADD COLUMN `failure_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '渠道明确失败或人工复核原因' AFTER `provider_checked_at`,
  ADD COLUMN `retry_count` int unsigned NOT NULL DEFAULT 0 COMMENT '重新发起退款次数' AFTER `failure_reason`,
  ADD COLUMN `refunded_at` datetime DEFAULT NULL COMMENT '支付渠道退款成功并完成本地结算时间' AFTER `retry_count`,
  MODIFY COLUMN `status` enum('pending','approved','returning','received','refunding','retryable_failed','manual_review','refunded','rejected') DEFAULT 'pending' COMMENT '售后状态',
  ADD KEY `idx_refund_provider_reconcile` (`status`, `provider_checked_at`, `id`);

UPDATE `order_refunds`
SET `refunded_at` = COALESCE(`updated_at`, `created_at`)
WHERE `status` = 'refunded' AND `refunded_at` IS NULL;

INSERT INTO `cron_jobs`
  (`name`, `command`, `expression`, `description`, `status`, `created_at`, `updated_at`)
SELECT
  '支付成功消费者对账', 'payment:reconcile', '5 3 * * *',
  '仅重放发布边界后的本地已支付单，补齐订单、充值和财务消费者', 1, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `cron_jobs` WHERE `command` = 'payment:reconcile'
);

INSERT INTO `cron_jobs`
  (`name`, `command`, `expression`, `description`, `status`, `created_at`, `updated_at`)
SELECT
  '退款渠道状态对账', 'refund:reconcile', '10 3 * * *',
  '查询长时间退款中的渠道状态，仅在明确成功后完成本地结算', 1, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `cron_jobs` WHERE `command` = 'refund:reconcile'
);

INSERT INTO `cron_jobs`
  (`name`, `command`, `expression`, `description`, `status`, `created_at`, `updated_at`)
SELECT
  '财务流水对账', 'finance:reconcile', '20 3 * * *',
  '按游标补齐支付、退款和提现财务流水', 1, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `cron_jobs` WHERE `command` = 'finance:reconcile'
);

UPDATE `cron_jobs`
SET `status` = 1, `updated_at` = NOW()
WHERE `command` IN ('payment:reconcile', 'refund:reconcile', 'finance:reconcile');

INSERT INTO `cron_jobs`
  (`name`, `command`, `expression`, `description`, `status`, `created_at`, `updated_at`)
SELECT
  '分销佣金自动结算', 'distribution:settle', '10 4 * * *',
  '补偿完成事件漏佣并结算已过冻结期佣金', 1, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `cron_jobs` WHERE `command` = 'distribution:settle'
);

UPDATE `cron_jobs`
SET `status` = 1, `updated_at` = NOW()
WHERE `command` = 'distribution:settle';

-- 订单会员权益 provenance、积分债务与退款冲正账本
ALTER TABLE `users`
  ADD COLUMN `points_debt` int NOT NULL DEFAULT 0 COMMENT '订单奖励退款冲正待偿积分债务' AFTER `commission_debt`;

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

CREATE TABLE IF NOT EXISTS `order_member_rewards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL COMMENT '订单ID（每单唯一）',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `eligible_item_ids` json NOT NULL COMMENT '完成时计入奖励的订单项ID',
  `reward_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '完成时奖励基数',
  `points_rate` decimal(10,4) NOT NULL DEFAULT 1.0000 COMMENT '完成时积分倍率理论快照',
  `points` int NOT NULL DEFAULT 0 COMMENT '理论奖励积分',
  `points_credited` int NOT NULL DEFAULT 0 COMMENT '理论事件实际进入累计积分份额',
  `points_debt_offset` int NOT NULL DEFAULT 0 COMMENT '奖励时抵扣历史积分债务份额',
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

INSERT INTO `permissions`
  (`name`,`title`,`group`,`description`,`guard_name`,`status`,`sort`,`created_at`,`updated_at`)
SELECT 'distribution.commission.review', '佣金证据复核', '用户管理',
  '提交已核实退款与结算证据并执行佣金冲正', 'admin', 1, 482, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `permissions` WHERE `name` = 'distribution.commission.review'
);

INSERT INTO `permissions`
  (`name`,`title`,`group`,`description`,`guard_name`,`status`,`sort`,`created_at`,`updated_at`)
SELECT 'member.reward_review.list', '历史权益复核', '用户管理',
  '查看历史订单会员权益证据', 'admin', 1, 480, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `permissions` WHERE `name` = 'member.reward_review.list'
);

INSERT INTO `permissions`
  (`name`,`title`,`group`,`description`,`guard_name`,`status`,`sort`,`created_at`,`updated_at`)
SELECT 'member.reward_review.resolve', '权益复核结案', '用户管理',
  '确认未验证聚合权益不归属于订单并留痕结案', 'admin', 1, 481, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `permissions` WHERE `name` = 'member.reward_review.resolve'
);

INSERT INTO `menus`
  (`id`,`parent_id`,`type`,`title`,`name`,`path`,`component`,`redirect`,`icon`,`permission`,`is_hidden`,`is_cache`,`is_affix`,`is_iframe`,`external_link`,`breadcrumb`,`active_menu`,`meta`,`status`,`sort`,`created_at`,`updated_at`)
SELECT 951, 950, 3, '批量结算', NULL, NULL, NULL, NULL, NULL,
  'distribution.commission.settle', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `id` = 951);

INSERT INTO `menus`
  (`id`,`parent_id`,`type`,`title`,`name`,`path`,`component`,`redirect`,`icon`,`permission`,`is_hidden`,`is_cache`,`is_affix`,`is_iframe`,`external_link`,`breadcrumb`,`active_menu`,`meta`,`status`,`sort`,`created_at`,`updated_at`)
SELECT 952, 950, 3, '证据复核', NULL, NULL, NULL, NULL, NULL,
  'distribution.commission.review', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 2, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `id` = 952);

INSERT INTO `menus`
  (`id`,`parent_id`,`type`,`title`,`name`,`path`,`component`,`redirect`,`icon`,`permission`,`is_hidden`,`is_cache`,`is_affix`,`is_iframe`,`external_link`,`breadcrumb`,`active_menu`,`meta`,`status`,`sort`,`created_at`,`updated_at`)
SELECT 978, 900, 2, '历史权益复核', 'MemberRewardReview', '/member/reward-review',
  'member/reward-review/index', NULL, 'i-lucide:shield-check', 'member.reward_review.list',
  0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 12, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `id` = 978);

INSERT INTO `menus`
  (`id`,`parent_id`,`type`,`title`,`name`,`path`,`component`,`redirect`,`icon`,`permission`,`is_hidden`,`is_cache`,`is_affix`,`is_iframe`,`external_link`,`breadcrumb`,`active_menu`,`meta`,`status`,`sort`,`created_at`,`updated_at`)
SELECT 979, 978, 3, '复核结案', NULL, NULL, NULL, NULL, NULL,
  'member.reward_review.resolve', 0, 1, 0, 0, NULL, 1, NULL, NULL, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `id` = 979);

INSERT INTO `role_permissions` (`role_id`,`permission_id`,`created_at`,`updated_at`)
SELECT 1, `permission`.`id`, NOW(), NOW()
FROM `permissions` AS `permission`
WHERE `permission`.`name` IN (
    'member.reward_review.list',
    'member.reward_review.resolve',
    'distribution.commission.review'
  )
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` AS `assigned`
    WHERE `assigned`.`role_id` = 1 AND `assigned`.`permission_id` = `permission`.`id`
  );

INSERT INTO `role_menus` (`role_id`,`menu_id`,`created_at`,`updated_at`)
SELECT 1, `menu`.`id`, NOW(), NOW()
FROM `menus` AS `menu`
WHERE `menu`.`id` IN (951, 952, 978, 979)
  AND NOT EXISTS (
    SELECT 1 FROM `role_menus` AS `assigned`
    WHERE `assigned`.`role_id` = 1 AND `assigned`.`menu_id` = `menu`.`id`
  );

-- 近期渠道主动对账：仅扫描发布边界后的未终结支付，游标按支付单 ID 前进；
-- provider 查询失败不会推进游标，避免丢失“回调已丢但渠道已扣款”的交易。
INSERT INTO `system_configs`
  (`config_key`,`config_value`,`config_group`,`config_type`,`config_name`,`config_desc`,`sort_order`,`status`,`created_at`,`updated_at`)
SELECT 'payment.provider_reconcile_cursor', '0', 'payment', 'number', '支付渠道对账游标',
  '近期渠道对账最后处理的支付单 ID', 90, 1, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `system_configs` WHERE `config_key` = 'payment.provider_reconcile_cursor'
);

INSERT INTO `system_configs`
  (`config_key`,`config_value`,`config_group`,`config_type`,`config_name`,`config_desc`,`sort_order`,`status`,`created_at`,`updated_at`)
SELECT 'payment.provider_reconcile_from', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d %H:%i:%s'),
  'payment', 'string', '支付渠道对账起始时间', '仅扫描创建时间不早于该边界的支付单', 91, 1, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `system_configs` WHERE `config_key` = 'payment.provider_reconcile_from'
);

INSERT INTO `system_configs`
  (`config_key`,`config_value`,`config_group`,`config_type`,`config_name`,`config_desc`,`sort_order`,`status`,`created_at`,`updated_at`)
SELECT 'payment.provider_reconcile_safe_delay_seconds', '300', 'payment', 'number', '支付渠道对账安全延迟',
  '仅扫描更新时间早于当前时间减该秒数的支付单', 92, 1, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `system_configs` WHERE `config_key` = 'payment.provider_reconcile_safe_delay_seconds'
);

INSERT INTO `system_configs`
  (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
SELECT
  'member_reward.snapshot_started_at', DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'),
  'member_reward', 'string', '订单会员权益快照启用时间',
  '边界前历史订单只导入证据快照，不重复发放且不冲正未验证权益',
  NULL, NULL, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `system_configs` WHERE `config_key` = 'member_reward.snapshot_started_at'
);

INSERT INTO `cron_jobs`
  (`name`, `command`, `expression`, `description`, `status`, `created_at`, `updated_at`)
SELECT
  '订单会员权益对账', 'member:reconcile-order-rewards', '45 3 * * *',
  '补齐完成订单奖励 provenance 快照与退款冲正', 1, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `cron_jobs` WHERE `command` = 'member:reconcile-order-rewards'
);

UPDATE `cron_jobs`
SET `status` = 1, `updated_at` = NOW()
WHERE `command` = 'member:reconcile-order-rewards';

-- 新安装 init.sql 和老站升级必须具有相同的提现规则默认值。
INSERT IGNORE INTO `system_configs`
  (`config_key`, `config_value`, `config_group`, `config_type`, `config_name`, `config_desc`, `config_options`, `config_depends`, `sort_order`, `status`, `created_at`, `updated_at`)
VALUES
  ('withdrawal_min_amount',  '50',                 'withdrawal', 'number', '提现起点',   '单笔提现最低金额（元）',              NULL, NULL, 1, 1, NOW(), NOW()),
  ('withdrawal_max_amount',  '50000',              'withdrawal', 'number', '单笔上限',   '单笔提现最高金额（元）',              NULL, NULL, 2, 1, NOW(), NOW()),
  ('withdrawal_daily_count', '3',                  'withdrawal', 'number', '每日次数',   '每个用户每日最多申请次数，0 表示不限', NULL, NULL, 3, 1, NOW(), NOW()),
  ('withdrawal_fee_rate',    '0.6',                'withdrawal', 'number', '手续费率',   '百分数，0.6 表示 0.6%',                  NULL, NULL, 4, 1, NOW(), NOW()),
  ('withdrawal_fee_min',     '1',                  'withdrawal', 'number', '最低手续费', '启用费率时的最低手续费（元）',          NULL, NULL, 5, 1, NOW(), NOW()),
  ('withdrawal_fee_max',     '25',                 'withdrawal', 'number', '最高手续费', '启用费率时的最高手续费（元），0 不限',   NULL, NULL, 6, 1, NOW(), NOW()),
  ('withdrawal_channels',    'wechat,alipay,bank', 'withdrawal', 'string', '到账方式',   '允许的提现渠道，英文逗号分隔',              NULL, NULL, 7, 1, NOW(), NOW());
