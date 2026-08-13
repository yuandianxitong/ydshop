<?php
declare(strict_types=1);

namespace app\model\notification;

use core\base\Model;

class UserNotification extends Model
{
    protected $name = 'user_notifications';

    protected $fillable = [
        'user_id', 'title', 'content', 'type', 'biz_id', 'event_key', 'extra',
    ];

    protected $type = [
        'user_id' => 'integer',
        'biz_id'  => 'integer',
    ];

    protected $append = ['type_text'];

    // 类型常量
    public const TYPE_SYSTEM   = 'system';
    public const TYPE_ORDER    = 'order';
    public const TYPE_PAYMENT  = 'payment';
    public const TYPE_FEEDBACK = 'feedback';

    public const TYPES = [
        self::TYPE_SYSTEM,
        self::TYPE_ORDER,
        self::TYPE_PAYMENT,
        self::TYPE_FEEDBACK,
    ];

    /**
     * extra 字段获取器
     */
    public function getExtraAttr($value): array
    {
        return $this->getJsonAttr($value);
    }

    /**
     * extra 字段修改器
     */
    public function setExtraAttr($value): string
    {
        return $this->setJsonAttr($value);
    }

    /**
     * 类型文本获取器
     */
    public function getTypeTextAttr($value, $data): string
    {
        $map = [
            self::TYPE_SYSTEM   => '系统通知',
            self::TYPE_ORDER    => '订单消息',
            self::TYPE_PAYMENT  => '支付消息',
            self::TYPE_FEEDBACK => '反馈消息',
        ];
        return $map[$data['type'] ?? self::TYPE_SYSTEM] ?? '未知';
    }
}
