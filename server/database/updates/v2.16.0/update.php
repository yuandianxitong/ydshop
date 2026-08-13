<?php
declare(strict_types=1);

/**
 * 幂等补充 waybill_templates 列（兼容此前已建旧表结构的环境）。
 * 注：若 v2.16.0 已应用，后续补齐走 v2.16.1。
 *
 * @return callable(PDO, string): void
 */
return static function (PDO $pdo, string $prefix): void {
    $table = $prefix . 'waybill_templates';
    $existsStmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
    $exists = $existsStmt ? $existsStmt->fetchColumn() : false;
    if ($existsStmt) {
        $existsStmt->closeCursor();
    }
    if (!$exists) {
        return;
    }

    $safeTable = '`' . str_replace('`', '``', $table) . '`';
    $columns = [];
    $colStmt = $pdo->query('SHOW COLUMNS FROM ' . $safeTable);
    if ($colStmt) {
        foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columns[(string)$row['Field']] = true;
        }
        $colStmt->closeCursor();
    }

    if (!isset($columns['need_pickup'])) {
        $pdo->exec(
            'ALTER TABLE ' . $safeTable . ' '
            . "ADD COLUMN `need_pickup` tinyint(1) NOT NULL DEFAULT 0 COMMENT '快递员上门揽件:1是,0否（映射 IsNotice 0/1）' AFTER `pay_type`"
        );
        $columns['need_pickup'] = true;
    }
    if (!isset($columns['is_default'])) {
        $after = isset($columns['need_pickup']) ? 'need_pickup' : 'pay_type';
        $pdo->exec(
            'ALTER TABLE ' . $safeTable . ' '
            . "ADD COLUMN `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认模版:1是,0否' AFTER `{$after}`"
        );
        $columns['is_default'] = true;
    }

    try {
        $pdo->exec(
            'ALTER TABLE ' . $safeTable . ' '
            . "MODIFY COLUMN `pay_type` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '邮费支付方式 PayType:1现付,2到付,3月结'"
        );
    } catch (Throwable) {
        // ignore
    }

    $indexExists = false;
    $idxStmt = $pdo->query('SHOW INDEX FROM ' . $safeTable);
    if ($idxStmt) {
        foreach ($idxStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ((string)($row['Key_name'] ?? '') === 'idx_is_default') {
                $indexExists = true;
                break;
            }
        }
        $idxStmt->closeCursor();
    }
    if (!$indexExists && isset($columns['is_default'])) {
        try {
            $pdo->exec('ALTER TABLE ' . $safeTable . ' ADD KEY `idx_is_default` (`is_default`)');
        } catch (Throwable) {
            // ignore
        }
    }
};
