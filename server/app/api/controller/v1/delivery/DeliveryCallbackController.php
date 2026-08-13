<?php
declare(strict_types=1);

namespace app\api\controller\v1\delivery;

use app\service\delivery\DeliveryOrderService;
use app\service\delivery\platform\DeliveryPlatformRegistry;
use core\base\Controller;
use think\facade\Log;
use think\Response;
use OpenApi\Attributes as OA;

/**
 * 三方同城配送平台回调入口
 *
 * 与支付回调同款约束：全 try/catch，绝不让框架抛 500（避免平台疯狂重试逻辑层错误），
 * 应答报文按各平台 ackResponse 约定组装。
 */
#[OA\Tag(name: '同城配送回调', description: '三方同城配送平台状态回调（供平台服务器调用）')]
class DeliveryCallbackController extends Controller
{
    protected DeliveryOrderService $deliveryOrderService;
    protected DeliveryPlatformRegistry $deliveryPlatformRegistry;

    #[OA\Post(
        path: '/delivery/callback/{platform}',
        summary: '三方配送平台状态回调',
        tags: ['同城配送回调'],
        parameters: [new OA\Parameter(name: 'platform', in: 'path', required: true, description: 'dada/fengniao/uupt/shansong/sfsc', schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: '按平台约定格式应答')]
    )]
    public function notify(): Response
    {
        $platform = (string)$this->request->param('platform', '');
        $ok = false;

        try {
            // body 兜底：post() 与原始 JSON 合并（post 优先）
            $json    = json_decode((string)$this->request->getContent(), true);
            $payload = array_merge(is_array($json) ? $json : [], (array)$this->request->post());
            $headers = array_change_key_case((array)$this->request->header(), CASE_LOWER);

            $ok = $this->deliveryOrderService->handleCallback($platform, $payload, $headers);
        } catch (\Throwable $e) {
            Log::error('同城配送回调异常: ' . $e->getMessage(), [
                'platform' => $platform,
                'trace'    => $e->getTraceAsString(),
            ]);
            $ok = false;
        }

        try {
            $ack = $this->deliveryPlatformRegistry->resolve($platform)->ackResponse($ok);
        } catch (\Throwable) {
            // 未知平台码等异常：给通用应答
            $ack = ['body' => $ok ? 'success' : 'fail', 'httpCode' => $ok ? 200 : 400, 'contentType' => 'text/plain'];
        }
        return response($ack['body'], $ack['httpCode'], ['Content-Type' => $ack['contentType']]);
    }
}
