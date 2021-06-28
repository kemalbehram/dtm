<?php


namespace app\admin\middleware;

use app\common\service\AuthService;
use think\Request;

/**
 * 检测用户登录和节点权限
 * Class CheckAdmin
 * @package app\admin\middleware
 */
class CheckAdmin
{

    use \app\common\traits\JumpTrait;

    public function handle(Request $request, \Closure $next)
    {
        $adminConfig = config('admin');
        $adminId = session('admin.id');
        $expireTime = session('admin.expire_time');
        $authService = new AuthService($adminId);
        $currentNode = $authService->getCurrentNode();
        $currentController = parse_name($request->controller());

        // 验证登录
        if (!in_array($currentController, $adminConfig['no_login_controller']) &&
            !in_array($currentNode, $adminConfig['no_login_node'])) {
            empty($adminId) && $this->redirect(__url(config('app.admin_alias_name').'/login/index'));

            // 判断是否登录过期
            if ($expireTime !== true && time() > $expireTime) {
                session('admin', null);
                $this->error('登录已过期，请重新登录', [], __url(config('app.admin_alias_name').'/login/index'));
            }
        }

        // 验证权限
        if (!in_array($currentController, $adminConfig['no_auth_controller']) &&
            !in_array($currentNode, $adminConfig['no_auth_node'])) {
            $check = $authService->checkNode($currentNode);
            !$check && $this->error('无权限访问');
        }

        return $next($request);
    }

}