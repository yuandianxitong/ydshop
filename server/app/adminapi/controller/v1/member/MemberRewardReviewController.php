<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\member;

use app\adminapi\validate\v1\member\MemberRewardReviewValidate;
use app\service\member\MemberRechargeService;
use app\service\member\OrderMemberRewardService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;

/** 历史订单会员权益证据的人工复核入口。 */
class MemberRewardReviewController extends Controller
{
    protected OrderMemberRewardService $orderMemberRewardService;
    protected MemberRechargeService $memberRechargeService;

    #[Permission('member.reward_review.list')]
    public function index(): Response
    {
        return $this->success(
            '获取成功',
            $this->orderMemberRewardService->getReviewList($this->getRequestData())
        );
    }

    #[Permission('member.reward_review.resolve')]
    public function resolve(): Response
    {
        $id = (int)$this->request->param('id');
        $data = $this->validate(
            $this->request->post(),
            MemberRewardReviewValidate::class,
            [],
            false,
            'resolve'
        );
        $result = $this->orderMemberRewardService->resolveUnverifiedAsNotAttributed(
            $id,
            $this->getUserId(),
            (string)$data['reason']
        );
        return $this->success(
            ($result['applied'] ?? false) ? '复核已结案' : '该复核已结案',
            $result
        );
    }

    #[Permission('member.reward_review.list')]
    public function rechargeIndex(): Response
    {
        return $this->success(
            '获取成功',
            $this->memberRechargeService->getGrowthReviewList($this->getRequestData())
        );
    }

    #[Permission('member.reward_review.resolve')]
    public function resolveRecharge(): Response
    {
        $id = (int)$this->request->param('id');
        $data = $this->validate(
            $this->request->post(),
            MemberRewardReviewValidate::class,
            [],
            false,
            'resolveRecharge'
        );
        $result = $this->memberRechargeService->resolveGrowthReview(
            $id,
            $this->getUserId(),
            (string)$data['resolution'],
            (string)$data['reason']
        );
        return $this->success(
            ($result['applied'] ?? false) ? '充值成长值复核已结案' : '该复核已结案',
            $result
        );
    }
}
