<?php
declare(strict_types=1);

namespace app\api\controller\v1\payment;

use core\base\Controller;
use app\api\validate\v1\payment\PaymentValidate;
use app\service\payment\PaymentService;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '支付', description: '支付订单创建、查询及异步回调')]
class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    #[OA\Post(
        path: '/payment/create',
        summary: '创建支付订单',
        security: [['bearerAuth' => []]],
        tags: ['支付'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['channel', 'order_no'],
                properties: [
                    new OA\Property(property: 'channel', type: 'string', description: '支付渠道（wechat/alipay）'),
                    new OA\Property(property: 'order_no', type: 'string', description: '当前用户的商城订单号'),
                    new OA\Property(property: 'trade_type', type: 'string', description: '微信交易类型（jsapi/h5/app/native）'),
                    new OA\Property(property: 'openid', type: 'string', description: 'JSAPI 支付时用户 openid'),
                    new OA\Property(property: 'return_url', type: 'string', description: '支付宝同步回跳地址'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '创建成功，返回支付参数', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function create(): Response
    {
        try {
            $input = $this->request->only([
                'channel', 'order_no', 'trade_type',
                'return_url', 'quit_url', 'openid', 'client_ip',
            ]);
            $input = $this->validate($input, PaymentValidate::class, [], false, 'create');

            $channel = (string)$input['channel'];
            $userId = $this->getUserId();
            $params  = $input;
            unset($params['channel']);

            // 微信支付：依据 X-Client-Type 自动决定 trade_type + appid + openid
            // miniapp → jsapi + mini_app_id + mini_openid
            // wechat_h5 → jsapi + official_app_id + oa_openid
            // 其他 → h5（client_ip 必填）
            if ($channel === 'wechat') {
                $clientType = $this->getClientType();
                $params['client_type'] = $clientType;
                $resolved = $this->paymentService->resolveWechatPayParams($clientType, $userId);
                $params['trade_type'] = $resolved['trade_type'];
                $params['appid']      = $resolved['appid'];
                if (!empty($resolved['openid'])) {
                    $params['openid'] = $resolved['openid'];
                }
                if ($resolved['trade_type'] === 'h5') {
                    $params['client_ip'] = $this->request->ip();
                }
            }

            $result = $this->paymentService->createOrderForUser($userId, $channel, $params);
            return $this->success(lang('messages.create_success'), $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Get(
        path: '/payment/query',
        summary: '查询订单支付状态',
        security: [['bearerAuth' => []]],
        tags: ['支付'],
        parameters: [
            new OA\Parameter(name: 'order_no', in: 'query', required: true, description: '订单号', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '查询成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function query(): Response
    {
        try {
            $orderNo = (string)$this->request->param('order_no', '');
            if (empty($orderNo)) {
                return $this->error(lang('business.params_incomplete'));
            }

            $result = $this->paymentService->queryOrderForUser($this->getUserId(), $orderNo);
            return $this->success(lang('messages.query_success'), $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Post(
        path: '/payment/notify/alipay',
        summary: '支付宝异步回调（供支付宝服务器调用）',
        tags: ['支付'],
        responses: [
            new OA\Response(response: 200, description: 'success'),
        ]
    )]
    #[OA\Post(
        path: '/payment/notify/wechat',
        summary: '微信支付异步回调（供微信服务器调用）',
        tags: ['支付'],
        responses: [
            new OA\Response(response: 200, description: '{"code":"SUCCESS"}'),
        ]
    )]
    public function wechatNotify(): Response
    {
        try {
            $body = $this->request->getContent();
            $params = json_decode($body, true) ?: [];

            // 传递签名头信息用于验签
            $params['_headers'] = [
                'Wechatpay-Timestamp' => $this->request->header('Wechatpay-Timestamp', ''),
                'Wechatpay-Nonce'     => $this->request->header('Wechatpay-Nonce', ''),
                'Wechatpay-Signature' => $this->request->header('Wechatpay-Signature', ''),
                'Wechatpay-Serial'    => $this->request->header('Wechatpay-Serial', ''),
            ];
            $params['_body'] = $body;

            $result = $this->paymentService->handleNotify('wechat', $params);

            // handleNotify 返 'fail' 时给微信返 4xx 让其不再重试逻辑层错误（微信 v3 看 HTTP code）
            if ($result === 'fail') {
                return response(json_encode(['code' => 'FAIL', 'message' => '处理失败']), 400, ['Content-Type' => 'application/json']);
            }

            // 成功（result 通常是 driver successResponse 字符串，可能是 'success' 或 JSON）
            return response($result, 200, ['Content-Type' => 'application/json']);
        } catch (\Throwable $e) {
            // 任何意外异常都不让框架抛 500（避免微信疯狂重试）；写日志后返 fail
            \think\facade\Log::error('微信支付回调异常: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response(json_encode(['code' => 'FAIL', 'message' => $e->getMessage()]), 400, ['Content-Type' => 'application/json']);
        }
    }

    public function alipayNotify(): Response
    {
        try {
            $params = $this->request->param();
            $result = $this->paymentService->handleNotify('alipay', $params);
            return response($result, 200, ['Content-Type' => 'text/plain']);
        } catch (\Throwable $e) {
            \think\facade\Log::error('支付宝回调异常: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response('fail', 200, ['Content-Type' => 'text/plain']);
        }
    }
}
