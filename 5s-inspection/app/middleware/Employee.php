<?php
declare(strict_types=1);

namespace app\middleware;

use app\model\Area;
use app\model\User;
use think\facade\Session;
use think\facade\View;
use think\Request;
use think\Response;

/**
 * 员工角色中间件
 * - 已登录员工: 直接放行
 * - 已登录其他角色: 403
 * - 未登录访问扫码页: 令牌合法则放行(由控制器处理展示), 非法令牌返回404; 其余跳登录
 */
class Employee extends Auth
{
    protected ?int $needRole = User::ROLE_EMPLOYEE;

    public function handle(Request $request, \Closure $next): Response
    {
        $uid  = Session::get('user_id');
        $path = $request->pathinfo();

        if (!$uid && str_starts_with($path, 'scan/')) {
            $token = (string) $request->route('token', '');
            $valid = $token !== '' && Area::where('scan_token', $token)->where('status', 1)->count() > 0;
            if (!$valid) {
                return Response::create('二维码无效或区域已停用 (404)', 'html', 404);
            }
            return redirect('/login?back=' . urlencode($request->url()));
        }

        return parent::handle($request, $next);
    }
}
