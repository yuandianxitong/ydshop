<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\wechat;

use core\base\Controller;
use app\adminapi\validate\v1\wechat\OfficialAccountValidate;
use app\service\wechat\OfficialAccountService;
use app\service\message\MessageService;
use core\attribute\Permission;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '微信公众号', description: '公众号菜单、模板消息、粉丝管理')]
class OfficialAccountController extends Controller
{
    protected OfficialAccountService $service;
    protected MessageService $messageService;

    /**
     * 获取自定义菜单
     */
    #[Permission('channel.official.menu')]
    #[OA\Get(
        path: '/wechat/official/menu',
        summary: '获取公众号自定义菜单',
        security: [['bearerAuth' => []]],
        tags: ['微信公众号'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function getMenu(): Response
    {
        try {
            $result = $this->service->getMenu();
            return $this->success(lang('messages.get_success'), $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 创建自定义菜单
     */
    #[Permission('channel.official.menu.create')]
    #[OA\Post(
        path: '/wechat/official/menu',
        summary: '创建公众号自定义菜单',
        security: [['bearerAuth' => []]],
        tags: ['微信公众号'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['button'],
                properties: [
                    new OA\Property(property: 'button', type: 'array', items: new OA\Items(type: 'object'), description: '菜单按钮列表'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function createMenu(): Response
    {
        try {
            $data   = $this->validate($this->request->param(), OfficialAccountValidate::class, [], false, 'create_menu');
            $result = $this->service->createMenu((array)$data['button']);
            return $this->success(lang('messages.create_success'), $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除自定义菜单
     */
    #[Permission('channel.official.menu.delete')]
    #[OA\Delete(
        path: '/wechat/official/menu',
        summary: '删除公众号自定义菜单',
        security: [['bearerAuth' => []]],
        tags: ['微信公众号'],
        responses: [
            new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function deleteMenu(): Response
    {
        try {
            $result = $this->service->deleteMenu();
            return $this->success(lang('messages.delete_success'), $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 发送模板消息
     */
    #[Permission('channel.official.config.send')]
    #[OA\Post(
        path: '/wechat/official/template/send',
        summary: '发送公众号模板消息',
        security: [['bearerAuth' => []]],
        tags: ['微信公众号'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code', 'receivers'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', description: '消息模板编码'),
                    new OA\Property(property: 'receivers', type: 'object', description: '接收者', properties: [
                        new OA\Property(property: 'phone', type: 'string', description: '手机号'),
                        new OA\Property(property: 'openid', type: 'string', description: '公众号OpenID'),
                        new OA\Property(property: 'mini_openid', type: 'string', description: '小程序OpenID'),
                    ]),
                    new OA\Property(property: 'data', type: 'object', description: '模板变量数据'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '发送成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function sendTemplate(): Response
    {
        try {
            $data = $this->validate($this->request->param(), OfficialAccountValidate::class, [], false, 'send_template');
            $result = $this->messageService->send(
                (string)$data['code'],
                $data['receivers'],
                (array)($data['data'] ?? [])
            );
            return $this->success(lang('messages.send_success'), $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取粉丝列表
     */
    #[Permission('channel.official.config')]
    #[OA\Get(
        path: '/wechat/official/followers',
        summary: '获取公众号粉丝列表',
        security: [['bearerAuth' => []]],
        tags: ['微信公众号'],
        parameters: [
            new OA\Parameter(name: 'next_openid', in: 'query', description: '下一个OpenID(用于分页)', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function followers(): Response
    {
        try {
            $nextOpenid = (string)$this->request->param('next_openid', '');
            $result = $this->service->getUserList($nextOpenid);
            return $this->success(lang('messages.get_success'), $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取用户信息
     */
    #[Permission('channel.official.config')]
    #[OA\Get(
        path: '/wechat/official/user-info',
        summary: '获取公众号用户信息',
        security: [['bearerAuth' => []]],
        tags: ['微信公众号'],
        parameters: [
            new OA\Parameter(name: 'openid', in: 'query', required: true, description: '用户OpenID', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function userInfo(): Response
    {
        try {
            $data   = $this->validate($this->request->param(), OfficialAccountValidate::class, [], false, 'user_info');
            $result = $this->service->getUserInfo((string)$data['openid']);
            return $this->success(lang('messages.get_success'), $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
