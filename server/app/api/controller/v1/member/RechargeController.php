<?php
declare(strict_types=1);

namespace app\api\controller\v1\member;

use app\api\validate\v1\member\RechargeValidate;
use app\service\member\MemberRechargeService;
use app\service\member\RechargePackageService;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '余额充值', description: '充值套餐查询 + 充值下单')]
class RechargeController extends Controller
{
    protected MemberRechargeService $rechargeService;
    protected RechargePackageService $rechargePackageService;

    /**
     * 获取启用中的充值套餐列表（公开，无需登录）
     */
    #[OA\Get(
        path: '/recharge/packages',
        summary: '充值套餐列表（公开）',
        tags: ['余额充值'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function packages(): Response
    {
        $list = $this->rechargePackageService->getActiveList();
        return $this->success('success', $list);
    }

    /**
     * 创建充值订单
     */
    #[OA\Post(
        path: '/recharge/create',
        summary: '创建充值订单',
        security: [['bearerAuth' => []]],
        tags: ['余额充值'],
        description: '套餐充值传 package_id（金额和赠送权益均从服务端套餐读取）；自定义充值传 amount。支付形态和微信身份由 X-Client-Type 与当前登录用户在服务端解析。',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['channel'],
                properties: [
                    new OA\Property(property: 'package_id', type: 'integer', description: '套餐 ID，0=自定义金额'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', description: '自定义金额（package_id=0 时必填，1.00~10000.00）'),
                    new OA\Property(property: 'channel', type: 'string', enum: ['alipay', 'wechat']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '充值订单创建成功，返回支付参数', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '套餐不存在/已下架 / 金额非法 / 校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function create(): Response
    {
        try {
            // 只读取业务选择；trade_type/appid/openid/client_ip 与所有赠送权益
            // 均由服务端根据可信请求上下文和套餐配置生成。
            $input = $this->request->only(['package_id', 'amount', 'channel']);
            $data = $this->validate($input, RechargeValidate::class, [], false, 'create');
            $result = $this->rechargeService->createRechargeForClient(
                $this->getUserId(),
                $data['amount'] ?? 0,
                (string)$data['channel'],
                $this->getClientType(),
                $this->request->ip(),
                (int)($data['package_id'] ?? 0)
            );

            return $this->success('充值订单创建成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
