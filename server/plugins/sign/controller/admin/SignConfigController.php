<?php
declare(strict_types=1);

namespace plugins\sign\controller\admin;

use app\service\system\SystemConfigService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '签到配置', description: '签到奖励规则 + 补签设置')]
class SignConfigController extends Controller
{
    protected SystemConfigService $systemConfigService;

    private const KEYS = [
        'sign.points_base',
        'sign.points_increment',
        'sign.points_max',
        'sign.continuous_bonus_days',
        'sign.continuous_bonus_points',
        'sign.makeup_enabled',
        'sign.makeup_currency',
        'sign.makeup_price',
        'sign.makeup_days_limit',
    ];

    private const DEFAULTS = [
        'sign.points_base'             => 1,
        'sign.points_increment'        => 1,
        'sign.points_max'              => 7,
        'sign.continuous_bonus_days'   => 7,
        'sign.continuous_bonus_points' => 10,
        'sign.makeup_enabled'          => '0',
        'sign.makeup_currency'         => 'points',
        'sign.makeup_price'            => 5,
        'sign.makeup_days_limit'       => 7,
    ];

    private const TYPE_MAP = [
        'sign.makeup_enabled'  => 'string',
        'sign.makeup_currency' => 'string',
    ];

    #[Permission('marketing.sign.config.view')]
    #[OA\Get(
        path: '/marketing/sign/config',
        summary: '获取签到配置',
        security: [['bearerAuth' => []]],
        tags: ['签到配置'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function getConfig(): Response
    {
        $result = [];
        foreach (self::KEYS as $key) {
            $default = self::DEFAULTS[$key];
            $value   = $this->systemConfigService->getConfigValue($key, $default);
            $type    = self::TYPE_MAP[$key] ?? 'int';

            $result[$key] = $type === 'string' ? (string)$value : (int)$value;
        }
        return $this->success('获取成功', $result);
    }

    #[Permission('marketing.sign.config.update')]
    #[OA\Put(
        path: '/marketing/sign/config',
        summary: '更新签到配置',
        security: [['bearerAuth' => []]],
        tags: ['签到配置'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sign.points_base', type: 'integer'),
                new OA\Property(property: 'sign.points_increment', type: 'integer'),
                new OA\Property(property: 'sign.points_max', type: 'integer'),
                new OA\Property(property: 'sign.continuous_bonus_days', type: 'integer'),
                new OA\Property(property: 'sign.continuous_bonus_points', type: 'integer'),
                new OA\Property(property: 'sign.makeup_enabled', type: 'string', enum: ['0', '1']),
                new OA\Property(property: 'sign.makeup_currency', type: 'string', enum: ['points', 'balance']),
                new OA\Property(property: 'sign.makeup_price', type: 'integer'),
                new OA\Property(property: 'sign.makeup_days_limit', type: 'integer'),
            ])
        ),
        responses: [new OA\Response(response: 200, description: '保存成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function updateConfig(): Response
    {
        $data = $this->getRequestData();

        foreach (self::KEYS as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $raw  = $data[$key];
            $type = self::TYPE_MAP[$key] ?? 'int';

            $value = $type === 'string'
                ? (string)$raw
                : (string)max(0, (int)$raw);

            $this->systemConfigService->upsertConfigValue($key, $value, [
                'config_group' => 'sign',
                'config_type'  => $type === 'string' ? 'string' : 'number',
                'config_name'  => $key,
            ]);
        }

        return $this->success('保存成功');
    }
}
