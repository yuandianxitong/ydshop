<?php
declare(strict_types=1);

namespace app\service\order;

use app\model\order\OrderOrder;
use app\repository\order\OrderItemRepository;
use app\repository\order\OrderOrderRepository;
use app\repository\order\OrderReviewRepository;
use core\base\Service;
use core\exception\BusinessException;

class OrderReviewService extends Service
{
    protected OrderReviewRepository $orderReviewRepository;
    protected OrderItemRepository $orderItemRepository;
    protected OrderOrderRepository $orderOrderRepository;

    /**
     * 用户提交评价
     *
     * @param int   $userId 用户ID
     * @param array $data   [order_item_id, rating, content, images, is_anonymous]
     */
    public function create(int $userId, array $data): array
    {
        $item = $this->orderItemRepository->find((int)($data['order_item_id'] ?? 0));
        if (!$item) {
            throw new BusinessException('订单商品不存在');
        }

        $order = $this->orderOrderRepository->findByIdAndUser((int)$item['order_id'], $userId);
        if (!$order || ($order['status'] ?? '') !== OrderOrder::STATUS_COMPLETED) {
            throw new BusinessException('订单不存在或未完成');
        }

        if ((int)($item['is_reviewed'] ?? 0) !== 0) {
            throw new BusinessException('该商品已评价，请勿重复提交');
        }

        // 评分限制在 1-5 之间
        $rating = max(1, min(5, (int)($data['rating'] ?? 5)));

        $review = $this->orderReviewRepository->create([
            'order_item_id' => (int)$item['id'],
            'user_id'       => $userId,
            'spu_id'        => (int)$item['spu_id'],
            'sku_id'        => (int)$item['sku_id'],
            'rating'        => $rating,
            'content'       => $data['content'] ?? '',
            'images'        => $data['images'] ?? [],
            'is_anonymous'  => (int)($data['is_anonymous'] ?? 0),
        ]);

        $this->orderItemRepository->markReviewed((int)$item['id']);

        return $review;
    }

    /**
     * 管理员回复评价
     */
    public function reply(int $reviewId, string $content): void
    {
        $review = $this->orderReviewRepository->find($reviewId);
        if (!$review) {
            throw new BusinessException('评价不存在');
        }

        if (!empty($review['reply_content'])) {
            throw new BusinessException('该评价已回复，请勿重复操作');
        }

        $this->orderReviewRepository->update($reviewId, [
            'reply_content' => $content,
            'reply_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * C 端 - 获取某商品的评价列表（分页）
     */
    public function getListBySpu(int $spuId, int $page = 1, int $limit = 15): array
    {
        $result = $this->orderReviewRepository->getListBySpu($spuId, $page, $limit);
        $list   = $result['list'];
        $total  = $result['total'];

        // 匿名评价隐藏用户信息
        $list = array_map(function (array $item) {
            if ((int)($item['is_anonymous'] ?? 0) === 1) {
                $item['user_id'] = 0;
            }
            return $item;
        }, $list);

        return [
            'list'       => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int)ceil($total / max($limit, 1)),
            ],
        ];
    }

    /**
     * 管理端 - 获取所有评价列表（支持过滤）
     */
    public function getAdminList(array $params): array
    {
        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);
        return $this->orderReviewRepository->getAdminPageList($params, $page, $limit);
    }
}
