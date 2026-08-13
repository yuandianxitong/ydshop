<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\member;

use app\adminapi\validate\v1\member\MemberValidate;
use app\model\user\BalanceLog;
use app\model\user\PointsLog;
use app\service\marketing\AdminCouponIssueService;
use app\service\member\MemberRemarkService;
use app\service\member\MemberSmsService;
use app\service\user\UserManageService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '会员管理', description: '会员列表/详情/KPI/资金调整/优惠券发放/短信/备注')]
class MemberController extends Controller
{
    protected UserManageService $userManageService;
    protected AdminCouponIssueService $couponIssueService;
    protected MemberRemarkService $remarkService;
    protected MemberSmsService $smsService;

    #[Permission('member.list')]
    #[OA\Get(
        path: '/member',
        summary: '会员列表',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'keyword', in: 'query', description: '昵称/手机/邮箱搜索', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\Parameter(name: 'member_level_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
        ]
    )]
    public function index(): Response
    {
        $params = $this->getRequestData();
        $result = $this->userManageService->getUserList($params);
        return $this->success('获取成功', $result);
    }

    #[Permission('member.list')]
    #[OA\Get(
        path: '/member/{id}',
        summary: '会员详情',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '用户不存在', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(): Response
    {
        $id     = (int) $this->request->param('id');
        $result = $this->userManageService->getUserDetail($id);
        if (!$result) {
            return $this->error('用户不存在');
        }
        return $this->success('获取成功', $result);
    }

    /**
     * 单用户 KPI 聚合：累计 GMV / 订单数 / 客单价 / 最后下单时间 / 余额 / 积分
     */
    #[Permission('member.list')]
    #[OA\Get(
        path: '/member/{id}/stats',
        summary: '会员 KPI 聚合',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        description: '会员详情顶部档案条 4 KPI 用：gmv / orders / avg_amount / last_order_at / balance / points',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function stats(): Response
    {
        $id = (int)$this->request->param('id');
        return $this->success('获取成功', $this->userManageService->getUserStats($id));
    }

    #[Permission('member.update')]
    #[OA\Post(
        path: '/member/{id}/adjust-balance',
        summary: '调整余额',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float', description: '正数=增加 / 负数=扣减，范围 [-100000, 100000]'),
                    new OA\Property(property: 'remark', type: 'string', description: '操作备注'),
                    new OA\Property(property: 'type', type: 'integer', description: 'BalanceLog 类型枚举，默认 admin_adjust'),
                    new OA\Property(property: 'source', type: 'string', description: '幂等 source key，默认 admin_adjust'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '调整成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '余额不足 / 校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function adjustBalance(): Response
    {
        $id     = (int) $this->request->param('id');
        $data   = $this->validate($this->request->post(), MemberValidate::class, [], false, 'adjust_balance');
        $amount = (float) $data['amount'];
        $remark = (string) ($data['remark'] ?? '');
        // 资金证据的 type/source 必须由服务端命名；禁止请求伪造 order:/recharge:/
        // distribution_settle: 等保留业务前缀污染历史对账。
        $this->userManageService->adjustBalance(
            $id,
            $amount,
            $remark,
            BalanceLog::TYPE_ADMIN_ADJUST,
            'admin_adjust',
            $this->getUserId()
        );
        return $this->success('余额调整成功');
    }

    #[Permission('member.update')]
    #[OA\Post(
        path: '/member/{id}/adjust-points',
        summary: '调整积分',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['points'],
                properties: [
                    new OA\Property(property: 'points', type: 'integer', description: '正数=增加 / 负数=扣减，范围 [-1000000, 1000000]'),
                    new OA\Property(property: 'remark', type: 'string'),
                    new OA\Property(property: 'type', type: 'integer', description: 'PointsLog 类型枚举'),
                    new OA\Property(property: 'source', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '调整成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '积分不足 / 校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function adjustPoints(): Response
    {
        $id     = (int) $this->request->param('id');
        $data   = $this->validate($this->request->post(), MemberValidate::class, [], false, 'adjust_points');
        $points = (int) $data['points'];
        $remark = (string) ($data['remark'] ?? '');
        $this->userManageService->adjustPoints(
            $id,
            $points,
            $remark,
            PointsLog::TYPE_ADMIN_ADJUST,
            'admin_adjust',
            $this->getUserId()
        );
        return $this->success('积分调整成功');
    }

    #[Permission('member.list')]
    #[OA\Get(
        path: '/member/{id}/preference',
        summary: '偏好分析',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        description: '消费趋势 / 品类 / 支付 / 时段 / 地区 / 退款率 / 复购率',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function preference(): Response
    {
        $id = (int)$this->request->param('id');
        return $this->success('获取成功', $this->userManageService->getUserPreference($id));
    }

    #[Permission('member.list')]
    #[OA\Get(
        path: '/member/{id}/lifecycle',
        summary: '生命周期阶段',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        description: '5~7 个阶段（注册→认知→首单→复购→等级→沉睡→召回）+ 下一阶段建议',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function lifecycle(): Response
    {
        $id = (int)$this->request->param('id');
        return $this->success('获取成功', $this->userManageService->getUserLifecycle($id));
    }

    #[Permission('member.list')]
    #[OA\Get(
        path: '/member/{id}/operation-logs',
        summary: '操作日志（分类计数+分页）',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'category', in: 'query', description: '分类过滤', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功，含 categories 分类计数 + list 分页', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function operationLogs(): Response
    {
        $id       = (int)$this->request->param('id');
        $params   = $this->getRequestData();
        $category = $params['category'] ?? null;
        $page     = (int)($params['page']  ?? 1);
        $limit    = (int)($params['limit'] ?? 20);
        return $this->success('获取成功', $this->userManageService->getOperationLogs($id, $category, $page, $limit));
    }

    #[Permission('member.update')]
    #[OA\Put(
        path: '/member/{id}',
        summary: '编辑会员资料',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'nickname', type: 'string'),
                new OA\Property(property: 'mobile', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'gender', type: 'integer', enum: [0, 1, 2]),
                new OA\Property(property: 'birthday', type: 'string', format: 'date'),
                new OA\Property(property: 'avatar', type: 'string'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: '保存成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function update(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->validate((array)$this->request->put(), MemberValidate::class, [], false, 'update_profile');
        $this->userManageService->updateProfile($id, $data);
        return $this->success('保存成功');
    }

    // ─────── 备注 ───────

    #[Permission('member.list')]
    #[OA\Get(
        path: '/member/{id}/remarks',
        summary: '会员备注列表',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function remarks(): Response
    {
        $id = (int)$this->request->param('id');
        return $this->success('获取成功', ['list' => $this->remarkService->listByUser($id)]);
    }

    #[Permission('member.remark')]
    #[OA\Post(
        path: '/member/{id}/remarks',
        summary: '新增会员备注',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [new OA\Property(property: 'content', type: 'string', description: '备注内容（最多 1000 字）')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '已添加', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function addRemark(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->validate($this->request->post(), MemberValidate::class, [], false, 'add_remark');
        $row  = $this->remarkService->add($id, (string)$data['content'], $this->getUserId());
        return $this->success('已添加', $row);
    }

    #[Permission('member.remark')]
    #[OA\Delete(
        path: '/member/{id}/remarks/{rid}',
        summary: '删除会员备注',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'rid', in: 'path', required: true, description: '备注 ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '已删除', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function deleteRemark(): Response
    {
        $id  = (int)$this->request->param('id');
        $rid = (int)$this->request->param('rid');
        $this->remarkService->delete($id, $rid);
        return $this->success('已删除');
    }

    // ─────── 优惠券 ───────

    #[Permission('member.list')]
    #[OA\Get(
        path: '/member/{id}/coupons',
        summary: '会员持有的优惠券',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', description: 'unused / used / expired', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
        ]
    )]
    public function coupons(): Response
    {
        $id     = (int)$this->request->param('id');
        $params = $this->getRequestData();
        $status = $params['status'] ?? null;
        $page   = (int)($params['page']  ?? 1);
        $limit  = (int)($params['limit'] ?? 20);
        return $this->success('获取成功', $this->couponIssueService->getUserCoupons($id, $status, $page, $limit));
    }

    #[Permission('member.coupon')]
    #[OA\Get(
        path: '/member/issuable-coupons',
        summary: '可发放优惠券（发券弹窗下拉）',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function issuableCoupons(): Response
    {
        return $this->success('获取成功', ['list' => $this->couponIssueService->getIssuableCoupons()]);
    }

    #[Permission('member.coupon')]
    #[OA\Post(
        path: '/member/{id}/coupons',
        summary: '发放优惠券给会员',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['coupon_id'],
                properties: [
                    new OA\Property(property: 'coupon_id', type: 'integer'),
                    new OA\Property(property: 'count', type: 'integer', minimum: 1, maximum: 100, description: '发放张数，默认 1'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '发放成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function issueCoupon(): Response
    {
        $id     = (int)$this->request->param('id');
        $data   = $this->validate($this->request->post(), MemberValidate::class, [], false, 'issue_coupon');
        $count  = max(1, (int)($data['count'] ?? 1));
        $result = $this->couponIssueService->issueToUser($id, (int)$data['coupon_id'], $count);
        return $this->success('发放成功', $result);
    }

    // ─────── 短信 ───────

    #[Permission('member.sms')]
    #[OA\Get(
        path: '/member/sms-templates',
        summary: '可用短信模板',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function smsTemplates(): Response
    {
        return $this->success('获取成功', ['list' => $this->smsService->getTemplates()]);
    }

    #[Permission('member.sms')]
    #[OA\Post(
        path: '/member/{id}/sms',
        summary: '给会员发送短信',
        security: [['bearerAuth' => []]],
        tags: ['会员管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['template_code'],
                properties: [
                    new OA\Property(property: 'template_code', type: 'string'),
                    new OA\Property(property: 'variables', type: 'object', description: '模板变量 key-value'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '已发送', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function sendSms(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->validate($this->request->post(), MemberValidate::class, [], false, 'send_sms');
        $this->smsService->send($id, (string)$data['template_code'], (array)($data['variables'] ?? []));
        return $this->success('已发送');
    }
}
