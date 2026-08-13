<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\member;

use app\adminapi\validate\v1\member\AccountFundValidate;
use app\service\member\AccountFundService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '会员资金', description: '余额流水 / 充值订单 / 提现审核 / 打款')]
class AccountFundController extends Controller
{
    protected AccountFundService $accountFundService;

    #[Permission('member.fund.list')]
    #[OA\Get(
        path: '/member/account-fund/stats',
        summary: '资金概览',
        security: [['bearerAuth' => []]],
        tags: ['会员资金'],
        description: '返回总余额 / 总充值 / 本月充值 / 待审核提现 / 待打款提现等聚合数据',
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function stats(): Response { return $this->success('获取成功', $this->accountFundService->getStats()); }

    #[Permission('member.fund.list')]
    #[OA\Get(
        path: '/member/account-fund/balance-logs',
        summary: '余额流水（分页）',
        security: [['bearerAuth' => []]],
        tags: ['会员资金'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'keyword', in: 'query', description: '会员关键词', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type', in: 'query', description: '变动类型', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function balanceLogs(): Response { return $this->paginate($this->accountFundService->getBalanceLogs($this->getRequestData())); }

    #[Permission('member.fund.list')]
    #[OA\Get(
        path: '/member/account-fund/recharge-orders',
        summary: '充值订单（分页）',
        security: [['bearerAuth' => []]],
        tags: ['会员资金'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'status', in: 'query', description: '0=待支付 1=已支付', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function rechargeOrders(): Response { return $this->paginate($this->accountFundService->getRechargeOrders($this->getRequestData())); }

    #[Permission('member.fund.list')]
    #[OA\Get(
        path: '/member/account-fund/withdrawals',
        summary: '提现申请（分页）',
        security: [['bearerAuth' => []]],
        tags: ['会员资金'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'status', in: 'query', description: 'pending/approved/paid/rejected', schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function withdrawals(): Response { return $this->paginate($this->accountFundService->getWithdrawals($this->getRequestData())); }

    #[Permission('member.fund.withdraw.approve')]
    #[OA\Post(
        path: '/member/account-fund/withdrawals/{id}/approve',
        summary: '审核通过提现',
        security: [['bearerAuth' => []]],
        tags: ['会员资金'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '审核通过', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function approveWithdrawal(): Response
    {
        $id = (int)$this->request->param('id');
        $this->accountFundService->approveWithdrawal($id);
        return $this->success('审核通过');
    }

    #[Permission('member.fund.withdraw.approve')]
    #[OA\Post(
        path: '/member/account-fund/withdrawals/{id}/reject',
        summary: '驳回提现',
        security: [['bearerAuth' => []]],
        tags: ['会员资金'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['remark'],
                properties: [new OA\Property(property: 'remark', type: 'string', description: '拒绝原因（最多 255 字）')]
            )
        ),
        responses: [new OA\Response(response: 200, description: '已拒绝', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function rejectWithdrawal(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->validate($this->request->post(), AccountFundValidate::class, [], false, 'reject_withdrawal');
        $this->accountFundService->rejectWithdrawal($id, trim((string)$data['remark']));
        return $this->success('已拒绝');
    }

    #[Permission('member.fund.withdraw.pay')]
    #[OA\Post(
        path: '/member/account-fund/withdrawals/{id}/pay',
        summary: '提现打款',
        security: [['bearerAuth' => []]],
        tags: ['会员资金'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '打款完成', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function payWithdrawal(): Response
    {
        $id = (int)$this->request->param('id');
        $data = $this->validate($this->request->post(), AccountFundValidate::class, [], false, 'pay_withdrawal');
        $this->accountFundService->payWithdrawal(
            $id,
            trim((string)$data['payout_reference']),
            trim((string)($data['payout_proof'] ?? '')),
            $this->getUserId()
        );
        return $this->success('线下打款结果已登记');
    }

    #[Permission('member.fund.export')]
    #[OA\Get(
        path: '/member/account-fund/export',
        summary: '导出资金流水 xlsx',
        security: [['bearerAuth' => []]],
        tags: ['会员资金'],
        responses: [new OA\Response(response: 200, description: '导出文件流')]
    )]
    public function export(): Response { return $this->accountFundService->exportXlsx($this->getRequestData()); }
}
