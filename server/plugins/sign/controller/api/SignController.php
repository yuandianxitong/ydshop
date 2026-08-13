<?php
declare(strict_types=1);

namespace plugins\sign\controller\api;

use plugins\sign\service\MemberSignService;
use core\base\Controller;
use core\helper\UserAgentParser;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '会员签到', description: '签到 / 补签 / 日历 / 配置')]
class SignController extends Controller
{
    protected MemberSignService $memberSignService;

    /**
     * 用户签到
     */
    #[OA\Post(
        path: '/sign/checkin',
        summary: '签到',
        security: [['bearerAuth' => []]],
        tags: ['会员签到'],
        responses: [
            new OA\Response(response: 200, description: '签到成功，返回奖励积分 / 连续签到天数', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '今日已签到', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function checkin(): Response
    {
        try {
            $userId = $this->getUserId();
            $ua     = (string) $this->request->header('user-agent', '');
            $source = UserAgentParser::parse($ua);
            $result = $this->memberSignService->checkin($userId, $source);
            return $this->success('签到成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 用户补签
     */
    #[OA\Post(
        path: '/sign/makeup',
        summary: '补签',
        security: [['bearerAuth' => []]],
        tags: ['会员签到'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['date'],
                properties: [
                    new OA\Property(property: 'date', type: 'string', format: 'date', description: '补签日期 YYYY-MM-DD'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '补签成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '日期格式错误 / 已签到 / 不在可补签范围', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function makeup(): Response
    {
        try {
            $userId = $this->getUserId();
            $date   = (string) $this->request->post('date', '');
            if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return $this->error('补签日期格式错误');
            }

            $ua     = (string) $this->request->header('user-agent', '');
            $source = UserAgentParser::parse($ua);
            $result = $this->memberSignService->makeup($userId, $date, $source);
            return $this->success('补签成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取签到日历
     */
    #[OA\Get(
        path: '/sign/calendar',
        summary: '签到日历',
        security: [['bearerAuth' => []]],
        tags: ['会员签到'],
        parameters: [
            new OA\Parameter(name: 'month', in: 'query', description: 'YYYY-MM 月份（默认当前月）', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function calendar(): Response
    {
        try {
            $userId = $this->getUserId();
            $month  = $this->request->get('month', date('Y-m'));
            $result = $this->memberSignService->getCalendar($userId, $month);
            return $this->success('success', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取签到配置（公开接口）
     */
    #[OA\Get(
        path: '/sign/config',
        summary: '签到配置（公开）',
        tags: ['会员签到'],
        description: '返回签到奖励规则 / 补签条件 / 连续签到加成等',
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function config(): Response
    {
        try {
            $result = $this->memberSignService->getConfig();
            return $this->success('success', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
